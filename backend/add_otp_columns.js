const { createClient } = require('@supabase/supabase-js');
require('dotenv').config();

const supabase = createClient(
    process.env.SUPABASE_URL,
    process.env.SUPABASE_SERVICE_KEY || process.env.SUPABASE_ANON_KEY
);

async function addOtpColumns() {
    console.log('Adding OTP columns to companies table...');

    // Use the pg REST endpoint via rpc or raw query
    const { error } = await supabase.rpc('exec_sql', {
        sql: `
            ALTER TABLE public.companies 
            ADD COLUMN IF NOT EXISTS otp_code TEXT,
            ADD COLUMN IF NOT EXISTS otp_expiry TIMESTAMP WITH TIME ZONE;
        `
    });

    if (error) {
        // rpc not available - output the SQL for manual run
        console.log('\n⚠️  Could not run automatically. Please run this SQL in your Supabase SQL Editor:\n');
        console.log('------------------------------------------------------');
        console.log('ALTER TABLE public.companies');
        console.log('ADD COLUMN IF NOT EXISTS otp_code TEXT,');
        console.log('ADD COLUMN IF NOT EXISTS otp_expiry TIMESTAMP WITH TIME ZONE;');
        console.log('------------------------------------------------------\n');
    } else {
        console.log('✅ OTP columns added successfully!');
    }

    // Test by inserting a dummy value
    const { error: testError } = await supabase
        .from('companies')
        .update({ otp_code: null })
        .eq('id', 0); // Will not match anything, just tests schema

    if (!testError || testError.code === 'PGRST116') {
        console.log('✅ Schema verified: otp_code column exists in companies table.');
    } else {
        console.log('❌ Schema check failed:', testError.message);
        console.log('\n👆 Please run the SQL above in your Supabase SQL Editor and try again.\n');
    }

    process.exit(0);
}

addOtpColumns();
