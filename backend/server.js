const express = require('express');
const cors = require('cors');
const session = require('express-session');
const FileStore = require('session-file-store')(session);
const multer = require('multer');
const path = require('path');
const fs = require('fs');
const bcrypt = require('bcrypt');
const supabase = require('./db'); // Now points to Supabase
const { sendOTPEmail, sendWelcomeInvite } = require('./utils/email'); // Now uses SendGrid

const app = express();
require('dotenv').config();

// Middleware
app.use(cors({ origin: "http://localhost:5173", credentials: true }));
app.use(express.json());

const baseUploadsPath = process.env.VERCEL ? '/tmp' : path.join(__dirname, '../uploads');
app.use('/uploads', express.static(baseUploadsPath));

// Session
app.use(session({
    store: new FileStore({ path: './sessions' }),
    secret: process.env.SESSION_SECRET || 'vcard_secret_123',
    resave: false,
    saveUninitialized: false,
    cookie: { secure: false, httpOnly: true, maxAge: 24 * 60 * 60 * 1000 }
}));

// Multer for uploads
const storage = multer.diskStorage({
    destination: (req, file, cb) => {
        const companySlug = req.session.company_slug || 'temp';
        const type = req.url.includes('employees') ? 'employees' : 'logos';
        const dir = path.join(baseUploadsPath, companySlug, type);
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

// Test Database Connection Route
app.get('/api/test-db', async (req, res) => {
    try {
        const { data, error } = await supabase.from('companies').select('id').limit(1);
        if (error) throw error;
        
        res.json({ 
            success: true, 
            message: "Successfully connected to the Supabase database!",
            tables_found: ["companies", "employees"]
        });
    } catch (error) {
        res.status(500).json({ 
            success: false, 
            message: "Failed to connect to database.",
            error_code: error.code,
            error_message: error.message
        });
    }
});

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

        const { data, error } = await supabase
            .from('companies')
            .insert([{
                company_name,
                company_slug: slug,
                logo: logo_rel,
                created_by,
                email,
                password: hashedPassword
            }])
            .select();

        if (error) throw error;

        req.session.company_id = data[0].id;
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
        const { data, error } = await supabase
            .from('companies')
            .select('*')
            .eq('email', email);
            
        if (error) throw error;
        const company = data[0];

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
            const { data, error } = await supabase
                .from('employees')
                .select(`
                    *,
                    companies (
                        company_name,
                        company_slug,
                        theme_color1,
                        theme_color2
                    )
                `)
                .eq('id', req.session.employee_id)
                .single();
                
            if (data && !error) {
                // Flatten to match MySQL output
                const employee = {
                    ...data,
                    company_name: data.companies.company_name,
                    company_slug: data.companies.company_slug,
                    theme_color1: data.companies.theme_color1,
                    theme_color2: data.companies.theme_color2
                };
                delete employee.companies;
                return res.json({ loggedIn: true, role: 'employee', employee });
            }
        }

        if (req.session.company_id) {
            const { data, error } = await supabase
                .from('companies')
                .select('*')
                .eq('id', req.session.company_id)
                .single();
            if (data && !error) return res.json({ loggedIn: true, role: 'admin', company: data });
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
        const { data, error } = await supabase
            .from('employees')
            .select(`*, companies(company_name)`)
            .eq('email', email)
            .single();

        if (error || !data) return res.status(404).json({ message: 'Email not found' });

        const employee = data;
        const otp = Math.floor(100000 + Math.random() * 900000).toString();
        const expiry = new Date(Date.now() + 10 * 60 * 1000).toISOString(); // 10 mins

        await supabase
            .from('employees')
            .update({ otp_code: otp, otp_expiry: expiry })
            .eq('id', employee.id);

        await sendOTPEmail(email, otp, employee.companies.company_name);
        res.json({ success: true });
    } catch (err) {
        res.status(500).json({ message: 'Failed to send OTP' });
    }
});

app.post('/api/auth/employee/verify-otp', async (req, res) => {
    const { email, otp } = req.body;
    try {
        const { data, error } = await supabase
            .from('employees')
            .select(`*, companies(company_slug)`)
            .eq('email', email)
            .eq('otp_code', otp)
            .gt('otp_expiry', new Date().toISOString())
            .single();

        if (error || !data) return res.status(401).json({ message: 'Invalid or expired code' });
        const employee = data;

        await supabase
            .from('employees')
            .update({ otp_code: null, otp_expiry: null })
            .eq('id', employee.id);

        req.session.employee_id = employee.id;
        req.session.company_id = employee.company_id;
        req.session.company_slug = employee.companies.company_slug;

        res.json({ success: true, company_slug: employee.companies.company_slug });
    } catch (err) {
        res.status(500).json({ message: 'Verification failed' });
    }
});

// --- EMPLOYEE PRIVATE DASHBOARD ROUTES ---

app.get('/api/employee/me', async (req, res) => {
    if (!req.session.employee_id) return res.status(401).json({ message: 'Unauthorized' });
    try {
        const { data, error } = await supabase
            .from('employees')
            .select(`
                *,
                companies (
                    company_name,
                    company_slug,
                    theme_color1,
                    theme_color2
                )
            `)
            .eq('id', req.session.employee_id)
            .single();
            
        if (error || !data) return res.status(404).json({ message: 'Employee not found' });
        
        const emp = data;

        // Map for frontend state
        emp.full_name = emp.emp_name;
        emp.companies = {
            theme_color1: emp.companies.theme_color1,
            theme_color2: emp.companies.theme_color2,
            company_name: emp.companies.company_name
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
        const { data, error: fetchError } = await supabase
            .from('employees')
            .select('card_data')
            .eq('id', req.session.employee_id)
            .single();
            
        if (fetchError) throw fetchError;
        
        let cardData = {};
        if (data && data.card_data) {
            cardData = typeof data.card_data === 'string' ? JSON.parse(data.card_data) : data.card_data;
        }

        // Update card_data structure
        cardData.social = { linkedin, instagram, facebook, twitter };
        if (!cardData.links) cardData.links = {};
        cardData.links.website = website;
        if (!cardData.contact) cardData.contact = {};
        cardData.contact.phone = phone;

        let updateData = {
            emp_name: full_name,
            designation,
            phone,
            card_data: cardData // Supabase handles JSON natively
        };

        if (req.file) {
            updateData.photo = `uploads/${req.session.company_slug}/employees/${req.file.filename}`;
        }

        const { error: updateError } = await supabase
            .from('employees')
            .update(updateData)
            .eq('id', req.session.employee_id);
            
        if (updateError) throw updateError;
        
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
        const { data, error } = await supabase
            .from('companies')
            .select('*')
            .eq('id', req.session.company_id)
            .single();
            
        if (error) throw error;
        res.json(data);
    } catch (err) {
        res.status(500).json({ message: 'Failed to fetch settings' });
    }
});

app.put('/api/company/update', upload.single('logo'), async (req, res) => {
    if (!req.session.company_id) return res.status(401).json({ message: 'Unauthorized' });
    try {
        const { company_name, created_by, theme_color1, theme_color2, social_links } = req.body;
        let updateData = { company_name, created_by, theme_color1, theme_color2, social_links };

        if (req.file) {
            updateData.logo = `uploads/${req.session.company_slug}/logos/${req.file.filename}`;
        }

        const { error } = await supabase
            .from('companies')
            .update(updateData)
            .eq('id', req.session.company_id);
            
        if (error) throw error;
        res.json({ success: true });
    } catch (err) {
        res.status(500).json({ message: 'Update failed' });
    }
});

// --- EMPLOYEE CRUD ---

app.get('/api/employees', async (req, res) => {
    if (!req.session.company_id) return res.status(401).json({ message: 'Unauthorized' });
    try {
        const { data, error } = await supabase
            .from('employees')
            .select('id, emp_name, emp_code, emp_slug, designation, photo')
            .eq('company_id', req.session.company_id);
            
        if (error) throw error;
        res.json(data);
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
        const card_data = {
            contact: { phone, email, whatsapp },
            about: { headline, text: about },
            social: { linkedin, instagram, facebook, twitter },
            links: { website, maps, brochure }
        };

        const { error } = await supabase
            .from('employees')
            .insert([{
                company_id: companyId,
                emp_name,
                emp_slug,
                designation,
                phone,
                email,
                photo: photo_rel,
                card_data,
                emp_code
            }]);

        if (error) throw error;

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
        const { data, error } = await supabase
            .from('employees')
            .select('*')
            .eq('id', req.params.id)
            .eq('company_id', req.session.company_id)
            .single();
            
        if (error || !data) return res.status(404).json({ message: 'Not Found' });
        
        // Handle card_data parsing if needed for the frontend form
        const emp = data;
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
        const card_data = {
            contact: { phone, email, whatsapp },
            about: { headline, text: about },
            social: { linkedin, instagram, facebook, twitter },
            links: { website, maps, brochure }
        };

        let updateData = {
            emp_name,
            designation,
            phone,
            email,
            card_data
        };

        if (req.file) {
            updateData.photo = `uploads/${req.session.company_slug}/employees/${req.file.filename}`;
        }

        const { error } = await supabase
            .from('employees')
            .update(updateData)
            .eq('id', req.params.id)
            .eq('company_id', req.session.company_id);

        if (error) throw error;
        res.json({ success: true });
    } catch (err) {
        res.status(500).json({ message: 'Update failed' });
    }
});

app.delete('/api/employees/:id', async (req, res) => {
    if (!req.session.company_id) return res.status(401).json({ message: 'Unauthorized' });
    try {
        // Fetch employee to get photo path for cleanup
        const { data, error: fetchError } = await supabase
            .from('employees')
            .select('photo')
            .eq('id', req.params.id)
            .eq('company_id', req.session.company_id)
            .single();

        if (fetchError || !data) return res.status(404).json({ message: 'Employee not found' });

        const employee = data;

        // Delete photo from disk if it exists
        if (employee.photo) {
            const photoPath = path.join(__dirname, '..', employee.photo);
            if (fs.existsSync(photoPath)) {
                fs.unlinkSync(photoPath);
            }
        }

        // Delete from database
        const { error: deleteError } = await supabase
            .from('employees')
            .delete()
            .eq('id', req.params.id)
            .eq('company_id', req.session.company_id);
            
        if (deleteError) throw deleteError;
        
        res.json({ success: true });
    } catch (err) {
        console.error('Delete Error:', err);
        res.status(500).json({ message: 'Delete failed' });
    }
});

// --- PUBLIC CARD VIEW ---
app.get('/api/public/card/:companySlug/:empCode', async (req, res) => {
    try {
        const { data: companyData, error: companyError } = await supabase
            .from('companies')
            .select('id, company_name, theme_color1, theme_color2, logo, social_links')
            .eq('company_slug', req.params.companySlug)
            .single();
            
        if (companyError || !companyData) return res.status(404).json({ message: 'Company not found' });
        
        const { data: employeeData, error: empError } = await supabase
            .from('employees')
            .select('*')
            .eq('emp_code', req.params.empCode)
            .eq('company_id', companyData.id)
            .single();

        if (empError || !employeeData) return res.status(404).json({ message: 'Card not found' });

        const employee = {
            ...employeeData,
            company_name: companyData.company_name,
            theme_color1: companyData.theme_color1,
            theme_color2: companyData.theme_color2,
            company_logo: companyData.logo,
            company_social: companyData.social_links
        };

        // Parse JSON fields
        if (employee.card_data) employee.card_data = typeof employee.card_data === 'string' ? JSON.parse(employee.card_data) : employee.card_data;
        if (employee.company_social) employee.company_social = typeof employee.company_social === 'string' ? JSON.parse(employee.company_social) : employee.company_social;

        res.json(employee);
    } catch (err) {
        res.status(500).json({ message: 'Error' });
    }
});

const PORT = process.env.PORT || 5000;
app.listen(PORT, () => console.log(`🚀 Supabase Server running on http://localhost:${PORT}`));
