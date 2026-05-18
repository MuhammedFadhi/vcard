const bcrypt = require('bcrypt');
const db = require('./db');
async function fix() {
    try {
        const hash = await bcrypt.hash('Aa@123456', 10);
        await db.execute('UPDATE companies SET password = ? WHERE email = "muhammedfadhil111@gmail.com"', [hash]);
        console.log('Password hashed successfully');
    } catch(e) {
        console.log(e);
    }
    process.exit(0);
}
fix();
