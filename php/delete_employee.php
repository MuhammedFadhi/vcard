<?php
// delete_employee.php
session_start();
include('includes/config.php');

// Must be logged in
if (!isset($_SESSION['company_id'], $_SESSION['company_slug'])) {
  header("Location: index.php");
  exit;
}

$company_id   = (int)$_SESSION['company_id'];
$company_slug = $_POST['company'] ?? $_SESSION['company_slug'];
$emp_id       = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if (!$emp_id) {
  header("Location: ".$company_slug."?msg=".urlencode("Missing employee ID"));
  exit;
}

// Fetch the employee belonging to this company (prevents cross-company deletes)
$stmt = $conn->prepare("SELECT id, photo FROM employees WHERE id=? AND company_id=? LIMIT 1");
$stmt->bind_param("ii", $emp_id, $company_id);
$stmt->execute();
$emp = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$emp) {
  header("Location: ".$company_slug."?msg=".urlencode("Employee not found"));
  exit;
}

// Delete photo file if present
if (!empty($emp['photo'])) {
  // $emp['photo'] is a relative path like: uploads/sada/employees/file.jpg
  $fs_path = realpath(__DIR__ . DIRECTORY_SEPARATOR . $emp['photo']);
  if ($fs_path && is_file($fs_path)) {
    @unlink($fs_path);
  }
}

// Delete the DB row
$dstmt = $conn->prepare("DELETE FROM employees WHERE id=? AND company_id=?");
$dstmt->bind_param("ii", $emp_id, $company_id);
$ok = $dstmt->execute();
$dstmt->close();

$msg = $ok ? "Employee card deleted" : "Delete failed";
header("Location: /vcard/" . $company_slug . "?msg=" . urlencode($msg));
exit;
