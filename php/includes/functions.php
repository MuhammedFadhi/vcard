<?php
/**
 * Ensure a directory exists (recursive, windows-safe).
 */
function ensure_dir($path) {
    if (!is_dir($path)) {
        @mkdir($path, 0777, true);
    }
}

/**
 * Build a safe slug from a name (company or employee).
 * $mode: 'company' keeps spaces → hyphens; strips punctuation.
 */
function make_slug($name, $mode='company') {
    $name = trim($name);
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9 ]+/', '', $name)));
    $slug = preg_replace('/\s+/', '-', $slug);
    return trim($slug, '-');
}

/**
 * Convert a relative path saved in DB to a full web URL.
 * Example: 'uploads/sada/employees/file.jpg' -> '/digitalcard/uploads/sada/employees/file.jpg'
 */
function web_url($relative) {
    $relative = ltrim($relative, '/\\');
    // normalize and prepend uploads path
    return UPLOAD_WEB . preg_replace('#^uploads/+#','', $relative);
}

/**
 * Generate a unique 6-digit numeric code for an employee within a company.
 */
function generate_emp_code_numeric(mysqli $conn, int $company_id): string {
    while (true) {
        $code = (string) random_int(100000, 999999); // 6-digit random number
        $q = $conn->prepare("SELECT 1 FROM employees WHERE company_id=? AND emp_code=? LIMIT 1");
        $q->bind_param("is", $company_id, $code);
        $q->execute();
        $exists = $q->get_result()->fetch_row();
        $q->close();
        if (!$exists) return $code;
    }
}

/**
 * Build the public URL for an employee card using emp_code.
 * Example: employee_public_url('ait', '123456') -> https://yourdomain.com/digitalcard/ait/123456
 */
function employee_public_url(string $company_slug, string $emp_code): string {
    return APP_URL . rawurlencode($company_slug) . '/' . rawurlencode($emp_code);
}
