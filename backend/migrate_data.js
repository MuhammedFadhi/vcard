const mysql = require('mysql2/promise');
const { createClient } = require('@supabase/supabase-js');
require('dotenv').config();

// Supabase Client
const supabaseUrl = process.env.SUPABASE_URL;
const supabaseKey = process.env.SUPABASE_SERVICE_KEY || process.env.SUPABASE_ANON_KEY;

if (!supabaseUrl || !supabaseKey) {
    console.error('❌ Missing SUPABASE_URL or SUPABASE_KEY in .env file');
    process.exit(1);
}

const supabase = createClient(supabaseUrl, supabaseKey);

async function migrate() {
    console.log('🔄 Starting migration from MySQL to Supabase...');

    let connection;
    try {
        // Clear target tables first to avoid conflicts with test data
        console.log('🧹 Clearing existing data in Supabase...');
        await supabase.from('employees').delete().neq('id', 0);
        await supabase.from('companies').delete().neq('id', 0);

        // Connect to MySQL
        connection = await mysql.createConnection({
            host: process.env.DB_HOST,
            user: process.env.DB_USER,
            password: process.env.DB_PASS,
            database: process.env.DB_NAME
        });
        console.log('✅ Connected to MySQL');

        // 1. Migrate Companies
        console.log('\n--- Migrating Companies ---');
        const [companies] = await connection.execute('SELECT * FROM companies');
        console.log(`Found ${companies.length} companies to migrate.`);

        if (companies.length > 0) {
            const { error: compError } = await supabase.from('companies').upsert(companies);
            if (compError) throw compError;
            console.log('✅ Companies migrated successfully.');
        }

        // 2. Migrate Employees
        console.log('\n--- Migrating Employees ---');
        const [employees] = await connection.execute('SELECT * FROM employees');
        console.log(`Found ${employees.length} employees to migrate.`);

        if (employees.length > 0) {
            // Ensure card_data is parsed properly if it's a string, though upserting a JSON string into JSONB often works automatically
            // Let's do it safely
            const formattedEmployees = employees.map(emp => {
                let cardData = emp.card_data;
                if (typeof cardData === 'string') {
                    try {
                        cardData = JSON.parse(cardData);
                    } catch (e) {
                        // ignore if invalid JSON
                    }
                }
                return {
                    ...emp,
                    card_data: cardData
                };
            });

            const { error: empError } = await supabase.from('employees').upsert(formattedEmployees);
            if (empError) throw empError;
            console.log('✅ Employees migrated successfully.');
        }

        console.log('\n🎉 Migration complete!');

    } catch (error) {
        console.error('\n❌ Migration failed:', error);
    } finally {
        if (connection) {
            await connection.end();
        }
    }
}

migrate();
