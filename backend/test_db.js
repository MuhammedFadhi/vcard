const mysql = require('mysql2/promise');
require('dotenv').config();

async function testConnection() {
    console.log("==========================================");
    console.log("🔍 STARTING DATABASE CONNECTION TEST...");
    console.log("==========================================");
    
    const host = "69.72.248.201";
    const user = "effedoco_v_card";
    const pass = "e4YU(;NHLa]%broR";
    const dbName = "effedoco_v_card";

    console.log("\n[1] Checking Environment Variables:");
    console.log("DB_HOST:", host);
    console.log("DB_USER:", user);
    console.log("DB_NAME:", dbName);
    console.log("DB_PASS:", "***HIDDEN*** (But it exists)");
    
    console.log("\n[2] Attempting to connect to the database...");
    const timeoutSeconds = 10;
    
    try {
        // Create connection with a timeout so it doesn't hang forever
        const connection = await mysql.createConnection({
            host: host,
            user: user,
            password: pass,
            database: dbName,
            connectTimeout: timeoutSeconds * 1000 // 10 seconds timeout
        });

        console.log("\n✅ SUCCESS: Successfully connected to the MySQL Database!");
        
        // Debug Point 2: Run a simple query to ensure full access
        console.log("\n[3] Running a test query (SHOW TABLES)...");
        const [rows] = await connection.execute('SHOW TABLES');
        
        console.log("Tables found in database:");
        if (rows.length === 0) {
            console.log("  (Database is completely empty - No tables found!)");
            console.log("  👉 Remember to import your SQL file into cPanel!");
        } else {
            rows.forEach(row => console.log("  -", Object.values(row)[0]));
        }

        await connection.end();
        console.log("\n==========================================");
        console.log("🎉 TEST COMPLETED SUCCESSFULLY!");
        console.log("==========================================");

    } catch (error) {
        console.log("\n❌ FAILED: Could not connect to the database.");
        console.log("\n================ DEBUG INFO ================");
        console.log("Error Code:", error.code);
        console.log("Error Number:", error.errno);
        console.log("SQL State:", error.sqlState);
        console.log("Full Message:", error.message);
        
        console.log("\n================ DIAGNOSIS ================");
        if (error.code === 'ETIMEDOUT') {
            console.log("🔴 DIAGNOSIS: Connection Timed Out!");
            console.log("This usually means a FIREWALL is blocking the connection.");
            console.log("If this runs on Vercel, your cPanel hosting is blocking Vercel's IP addresses.");
            console.log("Fix: You must ask your hosting provider to open port 3306, OR use a cloud DB like TiDB Serverless.");
        } else if (error.code === 'ER_ACCESS_DENIED_ERROR') {
            console.log("🔴 DIAGNOSIS: Access Denied!");
            console.log("Your password or username is incorrect, OR the user hasn't been given privileges to this database.");
        } else if (error.code === 'ER_BAD_DB_ERROR') {
            console.log("🔴 DIAGNOSIS: Database Not Found!");
            console.log(`The database '${process.env.DB_NAME}' does not exist on this server.`);
        } else if (error.code === 'ENOTFOUND') {
            console.log("🔴 DIAGNOSIS: Host Not Found!");
            console.log(`The host '${process.env.DB_HOST}' is invalid or cannot be reached.`);
        } else {
            console.log("🔴 DIAGNOSIS: Unknown Error. Read the Full Message above.");
        }
        console.log("==========================================");
        process.exit(1);
    }
}

testConnection();
