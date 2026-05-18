const db = require('./db');
const { sendOTPEmail } = require('./utils/email');
const bcrypt = require('bcrypt');

async function testAuth() {
    console.log("Testing Admin Login...");
    const [rows] = await db.execute('SELECT * FROM companies WHERE email = ?', ['muhammedfadhil111@gmail.com']);
    const company = rows[0];
    
    if (company) {
        console.log("Admin found. Password in DB:", company.password);
        const match = await bcrypt.compare('Aa@123456', company.password);
        console.log("Bcrypt Match:", match);
        console.log("Plaintext Match:", 'Aa@123456' === company.password);
    } else {
        console.log("Admin not found.");
    }
    
    console.log("\nTesting OTP Email...");
    try {
        const result = await sendOTPEmail('muhammedfadhil111@gmail.com', '123456', 'a360');
        console.log("SendGrid Result:", result);
    } catch(err) {
        console.log("SendGrid Error:", err);
    }

    process.exit(0);
}

testAuth();
