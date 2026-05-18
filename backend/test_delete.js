const db = require('./db');

async function testDelete() {
    try {
        // Create a dummy employee
        const [res] = await db.execute('INSERT INTO employees (company_id, emp_name, emp_slug) VALUES (12, "Test Delete", "test-delete")');
        const id = res.insertId;
        console.log(`Created test employee with ID: ${id}`);

        // Try to delete it
        const [delRes] = await db.execute('DELETE FROM employees WHERE id = ? AND company_id = ?', [id, 12]);
        console.log(`Deleted rows: ${delRes.affectedRows}`);
        
        if (delRes.affectedRows === 1) {
            console.log('Delete successful!');
        } else {
            console.log('Delete failed - no rows affected.');
        }
    } catch (err) {
        console.error('Error during test:', err);
    } finally {
        process.exit();
    }
}

testDelete();
