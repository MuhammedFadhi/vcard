const db = require('./db');
async function check() { 
    try {
        const [rows] = await db.execute('SELECT password FROM companies WHERE email = "muhammedfadhil111@gmail.com"'); 
        console.log("Password hash:", rows[0].password); 
    } catch(e) {
        console.log(e);
    }
    process.exit(0); 
} 
check();
