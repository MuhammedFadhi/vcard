<?php
session_start();
include('includes/config.php');
include('includes/functions.php');

if (!isset($_SESSION['company_id'])) {
    http_response_code(403);
    exit('Unauthorized');
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// -------------------------
// DO NOT ECHO ANYTHING HERE
// -------------------------

$company_id   = (int)$_POST['company_id'];
$company_slug = $_POST['company_slug'] ?? ($_SESSION['company_slug'] ?? '');

$emp_name    = trim($_POST['emp_name'] ?? '');
$designation = trim($_POST['designation'] ?? '');
$phone       = trim($_POST['phone'] ?? '');
$email       = trim($_POST['email'] ?? '');
$whatsapp    = trim($_POST['whatsapp'] ?? '');
$headline    = trim($_POST['headline'] ?? '');
$about       = trim($_POST['about'] ?? '');
$linkedin    = trim($_POST['linkedin'] ?? '');
$instagram   = trim($_POST['instagram'] ?? '');
$facebook    = trim($_POST['facebook'] ?? '');
$twitter     = trim($_POST['twitter'] ?? '');
$website     = trim($_POST['website'] ?? '');
$maps        = trim($_POST['maps'] ?? '');
$brochure    = trim($_POST['brochure'] ?? '');
$calendar    = trim($_POST['calendar'] ?? '');

if ($emp_name === '') die("Employee name is required.");

$emp_slug = make_slug($emp_name, 'employee');
$emp_code = generate_emp_code_numeric($conn, $company_id);

// Create folders
$emp_dir_fs = UPLOAD_FS . $company_slug . DIRECTORY_SEPARATOR . 'employees' . DIRECTORY_SEPARATOR;
ensure_dir($emp_dir_fs);

// Photo
$photo_rel = null;
if (!empty($_FILES['photo']['name'])) {
    $fname = time() . '_' . basename($_FILES['photo']['name']);
    $dest_fs = $emp_dir_fs . $fname;
    if (move_uploaded_file($_FILES['photo']['tmp_name'], $dest_fs)) {
        $photo_rel = 'uploads/' . $company_slug . '/employees/' . $fname;
    }
}

// JSON
$card = [
    "contact"  => ["phone" => $phone, "email" => $email, "whatsapp" => $whatsapp],
    "about"    => ["headline" => $headline, "text" => $about],
    "social"   => ["linkedin" => $linkedin, "instagram" => $instagram, "facebook" => $facebook, "twitter" => $twitter],
    "links"    => ["website" => $website, "maps" => $maps, "brochure" => $brochure],
    "calendar" => $calendar
];
$card_json = json_encode($card, JSON_UNESCAPED_SLASHES);

// Insert
$stmt = $conn->prepare("INSERT INTO employees
    (company_id, emp_name, emp_slug, emp_code, designation, phone, email, photo, card_data)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

$stmt->bind_param("issssssss", $company_id, $emp_name, $emp_slug, $emp_code, $designation, $phone, $email, $photo_rel, $card_json);

if ($stmt->execute()) {
    // Redirect
    header("Location: " . $company_slug . "/" . $emp_code);
    exit;
} else {
    echo "DB Error: " . $stmt->error;
}
