const express = require('express');
const cors = require('cors');
const session = require('express-session');
const MySQLStore = require('express-mysql-session')(session);
const multer = require('multer');
const path = require('path');
const fs = require('fs');
const bcrypt = require('bcrypt');
const db = require('./db'); // Now points to MySQL
const { sendOTPEmail, sendWelcomeInvite } = require('./utils/email'); // Now uses SendGrid

const app = express();
require('dotenv').config();

// Middleware
app.use(cors({ origin: "http://localhost:5173", credentials: true }));
app.use(express.json());
app.use('/uploads', express.static(path.join(__dirname, '../uploads')));

// Session Setup
const sessionStore = new MySQLStore({
    clearExpired: true,
    checkExpirationInterval: 900000,
    expiration: 86400000,
    createDatabaseTable: true
}, db);

app.use(session({
    store: sessionStore,
    secret: process.env.SESSION_SECRET || 'vcard_secret_123',
    resave: false,
    saveUninitialized: false,
    cookie: { secure: false, httpOnly: true, maxAge: 24 * 60 * 60 * 1000 }
}));

// Multer for uploads (Using /tmp for Vercel Serverless compatibility)
const storage = multer.diskStorage({
    destination: (req, file, cb) => {
        const companySlug = req.session.company_slug || 'temp';
        const type = req.url.includes('employees') ? 'employees' : 'logos';
        // Use /tmp for serverless environments, fallback to local uploads folder
        const baseDir = process.env.VERCEL ? '/tmp' : path.join(__dirname, '../uploads');
        const dir = path.join(baseDir, companySlug, type);
        fs.mkdirSync(dir, { recursive: true });
        cb(null, dir);
    },
    filename: (req, file, cb) => {
        cb(null, Date.now() + '_' + file.originalname);
    }
});
const upload = multer({ storage });

// Helpers
const makeSlug = (text) => text.toLowerCase().replace(/ /g, '-').replace(/[^\w-]+/g, '');

// --- AUTH ROUTES ---

// Registration
app.post('/api/auth/register', upload.single('logo'), async (req, res) => {
    try {
        const { company_name, created_by, email, password } = req.body;
        const slug = makeSlug(company_name);
        const hashedPassword = await bcrypt.hash(password, 10);
        let logo_rel = null;

        if (req.file) {
            logo_rel = `uploads/${slug}/logos/${req.file.filename}`;
        }

        const [result] = await db.execute(
            'INSERT INTO companies (company_name, company_slug, logo, created_by, email, password) VALUES (?, ?, ?, ?, ?, ?)',
            [company_name, slug, logo_rel, created_by, email, hashedPassword]
        );

        req.session.company_id = result.insertId;
        req.session.company_slug = slug;

        res.json({ success: true, company_slug: slug });
    } catch (error) {
        console.error('Registration Error:', error);
        res.status(500).json({ success: false, message: error.message });
    }
});

// Login
app.post('/api/auth/login', async (req, res) => {
    try {
        const { email, password } = req.body;
        const [rows] = await db.execute('SELECT * FROM companies WHERE email = ?', [email]);
        const company = rows[0];

        if (!company) return res.status(401).json({ success: false, message: 'No Account Found' });

        const match = await bcrypt.compare(password, company.password);
        if (!match && password !== company.password) { // Support plain text for legacy if needed
             return res.status(401).json({ success: false, message: 'Invalid Password' });
        }

        req.session.employee_id = null;
        req.session.company_id = company.id;
        req.session.company_slug = company.company_slug;

        res.json({ success: true, company_slug: company.company_slug });
    } catch (error) {
        res.status(500).json({ success: false, message: error.message });
    }
});

// Logout
app.post('/api/auth/logout', (req, res) => {
    req.session.destroy();
    res.json({ success: true });
});

// Check Session
app.get('/api/auth/me', async (req, res) => {
    try {
        if (req.session.employee_id) {
            const [rows] = await db.execute('SELECT e.*, c.company_name, c.company_slug, c.theme_color1, c.theme_color2 FROM employees e JOIN companies c ON e.company_id = c.id WHERE e.id = ?', [req.session.employee_id]);
            if (rows[0]) return res.json({ loggedIn: true, role: 'employee', employee: rows[0] });
        }

        if (req.session.company_id) {
            const [rows] = await db.execute('SELECT * FROM companies WHERE id = ?', [req.session.company_id]);
            if (rows[0]) return res.json({ loggedIn: true, role: 'admin', company: rows[0] });
        }
        res.json({ loggedIn: false });
    } catch (err) {
        res.status(500).json({ message: 'Session error' });
    }
});

// --- EMPLOYEE AUTH ---

app.post('/api/auth/employee/request-otp', async (req, res) => {
    const { email } = req.body;
    try {
        const [rows] = await db.execute('SELECT e.*, c.company_name FROM employees e JOIN companies c ON e.company_id = c.id WHERE e.email = ?', [email]);
        const employee = rows[0];

        if (!employee) return res.status(404).json({ message: 'Email not found' });

        const otp = Math.floor(100000 + Math.random() * 900000).toString();
        const expiry = new Date(Date.now() + 10 * 60 * 1000); // 10 mins

        await db.execute('UPDATE employees SET otp_code = ?, otp_expiry = ? WHERE id = ?', [otp, expiry, employee.id]);

        await sendOTPEmail(email, otp, employee.company_name);
        res.json({ success: true });
    } catch (err) {
        res.status(500).json({ message: 'Failed to send OTP' });
    }
});

app.post('/api/auth/employee/verify-otp', async (req, res) => {
    const { email, otp } = req.body;
    try {
        const [rows] = await db.execute('SELECT e.*, c.company_slug FROM employees e JOIN companies c ON e.company_id = c.id WHERE e.email = ? AND e.otp_code = ? AND e.otp_expiry > NOW()', [email, otp]);
        const employee = rows[0];

        if (!employee) return res.status(401).json({ message: 'Invalid or expired code' });

        await db.execute('UPDATE employees SET otp_code = NULL, otp_expiry = NULL WHERE id = ?', [employee.id]);

        req.session.employee_id = employee.id;
        req.session.company_id = employee.company_id;
        req.session.company_slug = employee.company_slug;

        res.json({ success: true, company_slug: employee.company_slug });
    } catch (err) {
        res.status(500).json({ message: 'Verification failed' });
    }
});

// --- EMPLOYEE PRIVATE DASHBOARD ROUTES ---

app.get('/api/employee/me', async (req, res) => {
    if (!req.session.employee_id) return res.status(401).json({ message: 'Unauthorized' });
    try {
        const [rows] = await db.execute(`
            SELECT e.*, c.company_name, c.company_slug, c.theme_color1, c.theme_color2 
            FROM employees e 
            JOIN companies c ON e.company_id = c.id 
            WHERE e.id = ?
        `, [req.session.employee_id]);
        
        const emp = rows[0];
        if (!emp) return res.status(404).json({ message: 'Employee not found' });

        // Map for frontend state
        emp.full_name = emp.emp_name;
        emp.companies = {
            theme_color1: emp.theme_color1,
            theme_color2: emp.theme_color2,
            company_name: emp.company_name
        };

        // Parse card_data for individual fields
        if (emp.card_data) {
            const cd = typeof emp.card_data === 'string' ? JSON.parse(emp.card_data) : emp.card_data;
            emp.website = cd.links?.website || '';
            emp.linkedin = cd.social?.linkedin || '';
            emp.instagram = cd.social?.instagram || '';
            emp.facebook = cd.social?.facebook || '';
            emp.twitter = cd.social?.twitter || '';
        }

        res.json({ success: true, employee: emp });
    } catch (err) {
        console.error(err);
        res.status(500).json({ message: 'Failed to fetch profile' });
    }
});

app.put('/api/employee/update-me', upload.single('photo'), async (req, res) => {
    if (!req.session.employee_id) return res.status(401).json({ message: 'Unauthorized' });
    try {
        const { full_name, designation, phone, website, linkedin, instagram, facebook, twitter } = req.body;
        
        // Fetch current card_data to preserve other fields
        const [rows] = await db.execute('SELECT card_data FROM employees WHERE id = ?', [req.session.employee_id]);
        let cardData = {};
        if (rows[0] && rows[0].card_data) {
            cardData = typeof rows[0].card_data === 'string' ? JSON.parse(rows[0].card_data) : rows[0].card_data;
        }

        // Update card_data structure
        cardData.social = { linkedin, instagram, facebook, twitter };
        if (!cardData.links) cardData.links = {};
        cardData.links.website = website;
        if (!cardData.contact) cardData.contact = {};
        cardData.contact.phone = phone;

        let query = 'UPDATE employees SET emp_name = ?, designation = ?, phone = ?, card_data = ?';
        let params = [full_name, designation, phone, JSON.stringify(cardData)];

        if (req.file) {
            const photo_rel = `uploads/${req.session.company_slug}/employees/${req.file.filename}`;
            query += ', photo = ?';
            params.push(photo_rel);
        }

        query += ' WHERE id = ?';
        params.push(req.session.employee_id);

        await db.execute(query, params);
        res.json({ success: true });
    } catch (err) {
        console.error(err);
        res.status(500).json({ message: 'Update failed' });
    }
});

// --- COMPANY ROUTES ---

app.get('/api/company/settings', async (req, res) => {
    if (!req.session.company_id) return res.status(401).json({ message: 'Unauthorized' });
    try {
        const [rows] = await db.execute('SELECT * FROM companies WHERE id = ?', [req.session.company_id]);
        res.json(rows[0]);
    } catch (err) {
        res.status(500).json({ message: 'Failed to fetch settings' });
    }
});

app.put('/api/company/update', upload.single('logo'), async (req, res) => {
    if (!req.session.company_id) return res.status(401).json({ message: 'Unauthorized' });
    try {
        const { company_name, created_by, theme_color1, theme_color2, social_links } = req.body;
        let query = 'UPDATE companies SET company_name = ?, created_by = ?, theme_color1 = ?, theme_color2 = ?, social_links = ?';
        let params = [company_name, created_by, theme_color1, theme_color2, social_links];

        if (req.file) {
            const logo_rel = `uploads/${req.session.company_slug}/logos/${req.file.filename}`;
            query += ', logo = ?';
            params.push(logo_rel);
        }

        query += ' WHERE id = ?';
        params.push(req.session.company_id);

        await db.execute(query, params);
        res.json({ success: true });
    } catch (err) {
        res.status(500).json({ message: 'Update failed' });
    }
});

// --- EMPLOYEE CRUD ---

app.get('/api/employees', async (req, res) => {
    if (!req.session.company_id) return res.status(401).json({ message: 'Unauthorized' });
    try {
        const [rows] = await db.execute('SELECT id, emp_name, emp_code, emp_slug, designation, photo FROM employees WHERE company_id = ?', [req.session.company_id]);
        res.json(rows);
    } catch (err) {
        res.status(500).json({ message: 'Failed to fetch employees' });
    }
});

app.post('/api/employees', upload.single('photo'), async (req, res) => {
    if (!req.session.company_id) return res.status(401).json({ message: 'Unauthorized' });
    try {
        const { emp_name, designation, phone, email, whatsapp, linkedin, instagram, facebook, twitter, website, maps, brochure, headline, about } = req.body;
        const companyId = req.session.company_id;
        const companySlug = req.session.company_slug;
        const emp_code = Math.floor(100000 + Math.random() * 900000).toString();
        const emp_slug = makeSlug(emp_name);
        
        let photo_rel = null;
        if (req.file) photo_rel = `uploads/${companySlug}/employees/${req.file.filename}`;

        // Prepare card_data JSON for legacy compatibility
        const card_data = JSON.stringify({
            contact: { phone, email, whatsapp },
            about: { headline, text: about },
            social: { linkedin, instagram, facebook, twitter },
            links: { website, maps, brochure }
        });

        await db.execute(
            'INSERT INTO employees (company_id, emp_name, emp_slug, designation, phone, email, photo, card_data, emp_code) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [companyId, emp_name, emp_slug, designation, phone, email, photo_rel, card_data, emp_code]
        );

        if (email) await sendWelcomeInvite(email, req.session.company_slug);

        res.json({ success: true });
    } catch (err) {
        console.error(err);
        res.status(500).json({ message: 'Failed to create employee' });
    }
});

app.get('/api/employees/:id', async (req, res) => {
    if (!req.session.company_id) return res.status(401).json({ message: 'Unauthorized' });
    try {
        const [rows] = await db.execute('SELECT * FROM employees WHERE id = ? AND company_id = ?', [req.params.id, req.session.company_id]);
        if (!rows[0]) return res.status(404).json({ message: 'Not Found' });
        
        // Handle card_data parsing if needed for the frontend form
        const emp = rows[0];
        if (emp.card_data) {
            const cd = typeof emp.card_data === 'string' ? JSON.parse(emp.card_data) : emp.card_data;
            emp.whatsapp = cd.contact?.whatsapp || '';
            emp.headline = cd.about?.headline || '';
            emp.about = cd.about?.text || '';
            emp.linkedin = cd.social?.linkedin || '';
            emp.instagram = cd.social?.instagram || '';
            emp.facebook = cd.social?.facebook || '';
            emp.twitter = cd.social?.twitter || '';
            emp.website = cd.links?.website || '';
            emp.maps = cd.links?.maps || '';
            emp.brochure = cd.links?.brochure || '';
        }
        res.json(emp);
    } catch (err) {
        res.status(500).json({ message: 'Error' });
    }
});

app.put('/api/employees/:id', upload.single('photo'), async (req, res) => {
    if (!req.session.company_id) return res.status(401).json({ message: 'Unauthorized' });
    try {
        const { emp_name, designation, phone, email, whatsapp, linkedin, instagram, facebook, twitter, website, maps, brochure, headline, about } = req.body;
        const card_data = JSON.stringify({
            contact: { phone, email, whatsapp },
            about: { headline, text: about },
            social: { linkedin, instagram, facebook, twitter },
            links: { website, maps, brochure }
        });

        let query = 'UPDATE employees SET emp_name = ?, designation = ?, phone = ?, email = ?, card_data = ?';
        let params = [emp_name, designation, phone, email, card_data];

        if (req.file) {
            const photo_rel = `uploads/${req.session.company_slug}/employees/${req.file.filename}`;
            query += ', photo = ?';
            params.push(photo_rel);
        }

        query += ' WHERE id = ? AND company_id = ?';
        params.push(req.params.id, req.session.company_id);

        await db.execute(query, params);
        res.json({ success: true });
    } catch (err) {
        res.status(500).json({ message: 'Update failed' });
    }
});

app.delete('/api/employees/:id', async (req, res) => {
    if (!req.session.company_id) return res.status(401).json({ message: 'Unauthorized' });
    try {
        // Fetch employee to get photo path for cleanup
        const [rows] = await db.execute('SELECT photo FROM employees WHERE id = ? AND company_id = ?', [req.params.id, req.session.company_id]);
        const employee = rows[0];

        if (!employee) return res.status(404).json({ message: 'Employee not found' });

        // Delete photo from disk if it exists
        if (employee.photo) {
            const photoPath = path.join(__dirname, '..', employee.photo);
            if (fs.existsSync(photoPath)) {
                fs.unlinkSync(photoPath);
            }
        }

        // Delete from database
        await db.execute('DELETE FROM employees WHERE id = ? AND company_id = ?', [req.params.id, req.session.company_id]);
        
        res.json({ success: true });
    } catch (err) {
        console.error('Delete Error:', err);
        res.status(500).json({ message: 'Delete failed' });
    }
});

// --- PUBLIC CARD VIEW ---
app.get('/api/public/card/:companySlug/:empCode', async (req, res) => {
    try {
        const [rows] = await db.execute(
            'SELECT e.*, c.company_name, c.theme_color1, c.theme_color2, c.logo as company_logo, c.social_links as company_social FROM employees e JOIN companies c ON e.company_id = c.id WHERE c.company_slug = ? AND e.emp_code = ?',
            [req.params.companySlug, req.params.empCode]
        );
        const employee = rows[0];
        if (!employee) return res.status(404).json({ message: 'Card not found' });

        // Parse JSON fields
        if (employee.card_data) employee.card_data = typeof employee.card_data === 'string' ? JSON.parse(employee.card_data) : employee.card_data;
        if (employee.company_social) employee.company_social = typeof employee.company_social === 'string' ? JSON.parse(employee.company_social) : employee.company_social;

        res.json(employee);
    } catch (err) {
        res.status(500).json({ message: 'Error' });
    }
});

const PORT = process.env.PORT || 5000;
app.listen(PORT, () => console.log(`🚀 MySQL Server running on http://localhost:${PORT}`));
