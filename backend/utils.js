const fs = require('fs');
const path = require('path');

/**
 * Build a safe slug from a name (company or employee).
 */
const makeSlug = (name) => {
    let slug = name.trim().toLowerCase();
    slug = slug.replace(/[^a-z0-9 ]+/g, '');
    slug = slug.replace(/\s+/g, '-');
    return slug.trim('-');
};

/**
 * Ensure a directory exists.
 */
const ensureDir = (dirPath) => {
    if (!fs.existsSync(dirPath)) {
        fs.mkdirSync(dirPath, { recursive: true });
    }
};

/**
 * Generate a unique 6-digit numeric code for an employee within a company.
 */
const generateEmpCodeNumeric = async (supabase, companyId) => {
    while (true) {
        const code = Math.floor(100000 + Math.random() * 900000).toString();
        const { data, error } = await supabase
            .from('employees')
            .select('id')
            .eq('company_id', companyId)
            .eq('emp_code', code)
            .maybeSingle();
            
        if (!data) return code;
    }
};

module.exports = {
    makeSlug,
    ensureDir,
    generateEmpCodeNumeric
};
