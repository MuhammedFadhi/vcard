<?php
// session_start();
// include('includes/config.php');
//
// if(!isset($_SESSION['company_id'])) { header("Location: index.php"); exit; }
//
// $company_id  = (int)$_SESSION['company_id'];
// $company_slug = $_GET['company'] ?? ($_SESSION['company_slug'] ?? '');
// $emp_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
//
// if(!$emp_id){ http_response_code(400); exit('Missing employee id'); }
//
// // Fetch company (optional header use)
// $company = $conn->query("SELECT * FROM companies WHERE id=$company_id")->fetch_assoc();
//
// // Fetch employee (belonging to same company)
// $stmt = $conn->prepare("SELECT * FROM employees WHERE id=? AND company_id=? LIMIT 1");
// $stmt->bind_param("ii", $emp_id, $company_id);
// $stmt->execute();
// $emp = $stmt->get_result()->fetch_assoc();
// $stmt->close();
//
// if(!$emp){ http_response_code(404); exit('Employee not found'); }
//
// $card = $emp['card_data'] ? json_decode($emp['card_data'], true) : [];
// $contact  = $card['contact'] ?? [];
// $about    = $card['about'] ?? [];
// $social   = $card['social'] ?? [];
// $links    = $card['links'] ?? [];
// $calendar = $card['calendar'] ?? '';














// this is custom code for color theme and above are the complete php code
session_start();
include('includes/config.php');

if (!isset($_SESSION['company_id'])) {
    header("Location: index.php");
    exit;
}

// Basic IDs and parameters
$company_id   = (int)$_SESSION['company_id'];
$company_slug = $_GET['company'] ?? ($_SESSION['company_slug'] ?? '');
$emp_id       = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$emp_id) {
    http_response_code(400);
    exit('Missing employee id');
}

/* ---- Fetch company with theme colors ---- */
// $stmt = $conn->prepare("SELECT id, company_name, company_slug, logo, created_by, email, theme_color1, theme_color2
//                         FROM companies WHERE id=? LIMIT 1");
// $stmt->bind_param("i", $company_id);
// $stmt->execute();
// $company = $stmt->get_result()->fetch_assoc();
// $stmt->close();




// custom code for server issuesg
$stmt = $conn->prepare("SELECT id, company_name, company_slug, logo, created_by, email, theme_color1, theme_color2
                        FROM companies WHERE id=? LIMIT 1");
$stmt->bind_param("i", $company_id);
$stmt->execute();

// Bind results manually (SAFE METHOD)
$stmt->bind_result(
    $c_id,
    $c_name,
    $c_slug,
    $c_logo,
    $c_created_by,
    $c_email,
    $c_theme1,
    $c_theme2
);

$stmt->fetch();

$company = [
    "id"            => $c_id,
    "company_name"  => $c_name,
    "company_slug"  => $c_slug,
    "logo"          => $c_logo,
    "created_by"    => $c_created_by,
    "email"         => $c_email,
    "theme_color1"  => $c_theme1,
    "theme_color2"  => $c_theme2
];

$stmt->close();
















if (!$company) {
    http_response_code(404);
    exit('Company not found');
}

// Set theme colors with fallbacks
$theme_color1 = $company['theme_color1'] ?: '#667eea';
$theme_color2 = $company['theme_color2'] ?: '#764ba2';

/* ---- Fetch employee (must belong to same company) ---- */
// $stmt = $conn->prepare("SELECT * FROM employees WHERE id=? AND company_id=? LIMIT 1");
// $stmt->bind_param("ii", $emp_id, $company_id);
// $stmt->execute();
// $emp = $stmt->get_result()->fetch_assoc();
// $stmt->close();


// custom code for server issue

$stmt = $conn->prepare("
    SELECT id, company_id, emp_name, emp_slug, emp_code, designation, phone,
           email
    FROM employees
    WHERE id=? AND company_id=?
    LIMIT 1
");
$stmt->bind_param("ii", $emp_id, $company_id);
$stmt->execute();

$stmt->store_result();  // VERY IMPORTANT (prevents 4GB allocation)

$stmt->bind_result(
    $e_id,
    $e_company_id,
    $e_name,
    $e_slug,
    $e_code,
    $e_designation,
    $e_phone,
    $e_email
);

$stmt->fetch();

$emp = [
    "id"          => $e_id,
    "company_id"  => $e_company_id,
    "emp_name"    => $e_name,
    "emp_slug"    => $e_slug,
    "emp_code"    => $e_code,
    "designation" => $e_designation,
    "phone"       => $e_phone,
    "email"       => $e_email
];

$stmt->close();


















if (!$emp) {
    http_response_code(404);
    exit('Employee not found');
}

/* ---- Decode card data ---- */
// $card     = $emp['card_data'] ? json_decode($emp['card_data'], true) : [];

// custom code for server issue
$card = isset($emp['card_data']) && !empty($emp['card_data'])
    ? json_decode($emp['card_data'], true)
    : [];















$contact  = $card['contact']  ?? [];
$about    = $card['about']    ?? [];
$social   = $card['social']   ?? [];
$links    = $card['links']    ?? [];
$calendar = $card['calendar'] ?? '';


?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Employee | <?= htmlspecialchars($emp['emp_name']); ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
  /* :root {
    --primary: #6366f1;
    --primary-dark: #4f46e5;
    --primary-light: #818cf8;
    --secondary: #8b5cf6;
    --success: #10b981;
    --danger: #ef4444;
    --gray-50: #f9fafb;
    --gray-100: #f3f4f6;
    --gray-200: #e5e7eb;
    --gray-300: #d1d5db;
    --gray-600: #4b5563;
    --gray-700: #374151;
    --gray-900: #111827;
    --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
  } */


/* custom code for color */
  :root {
  /* Dynamic theme colors from PHP */
  --primary: <?= $theme_color1 ?>;
  --primary-dark: <?= $theme_color2 ?>;

  /* Auto-adjusted lighter and secondary tones based on theme */
  --primary-light: <?= $theme_color1 ?>80; /* 80 = 50% opacity of main */
  --secondary: <?= $theme_color2 ?>cc; /* cc = 80% opacity (lighter variant) */

  /* Reusable gradient */
  --primary-gradient: linear-gradient(135deg, <?= $theme_color1 ?> 0%, <?= $theme_color2 ?> 100%);

  /* Grays and others */
  --success: #10b981;
  --danger: #ef4444;
  --gray-50: #f9fafb;
  --gray-100: #f3f4f6;
  --gray-200: #e5e7eb;
  --gray-300: #d1d5db;
  --gray-600: #4b5563;
  --gray-700: #374151;
  --gray-900: #111827;

  /* Shadows */
  --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
  --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
  --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
}

  * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
  }

  body {
    /* background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); */
  background:  linear-gradient(135deg, <?= $theme_color1 ?> 0%, <?= $theme_color2 ?> 100%);
    background-attachment: fixed;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    min-height: 100vh;
    position: relative;
  }

  body::before {
    content: '';
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg width="60" height="60" xmlns="http://www.w3.org/2000/svg"><defs><pattern id="grid" width="60" height="60" patternUnits="userSpaceOnUse"><path d="M 60 0 L 0 0 0 60" fill="none" stroke="rgba(255,255,255,0.05)" stroke-width="1"/></pattern></defs><rect width="100%" height="100%" fill="url(%23grid)"/></svg>');
    pointer-events: none;
    z-index: 0;
  }

  .content-wrapper {
    position: relative;
    z-index: 1;
  }

  /* Navbar */
  .navbar {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
    border-bottom: 1px solid rgba(255, 255, 255, 0.3);
    padding: 1rem 0;
  }

  .navbar img {
    height: 42px;
    width: 42px;
    border-radius: 10px;
    object-fit: cover;
    box-shadow: var(--shadow);
  }

  .navbar-brand {
    color: var(--gray-900) !important;
    font-weight: 700;
    font-size: 1.1rem;
    letter-spacing: -0.02em;
    margin: 0;
  }

  .navbar .btn {
    border-radius: 12px;
    padding: 8px 16px;
    font-weight: 600;
    transition: all 0.3s ease;
    border: 2px solid transparent;
  }

  .navbar .btn-light {
    background: var(--gray-100);
    color: var(--gray-700);
    border-color: var(--gray-200);
  }

  .navbar .btn-light:hover {
    background: var(--gray-200);
    transform: translateY(-2px);
    box-shadow: var(--shadow);
  }

  .navbar .btn-outline-light {
    border-color: var(--danger);
    color: var(--danger);
    background: transparent;
  }

  .navbar .btn-outline-light:hover {
    background: var(--danger);
    color: white;
    transform: translateY(-2px);
    box-shadow: var(--shadow);
  }

  /* Panel */
  .panel {
    background: rgba(255, 255, 255, 0.98);
    backdrop-filter: blur(20px);
    border-radius: 28px;
    box-shadow: var(--shadow-xl);
    border: 1px solid rgba(255, 255, 255, 0.3);
    padding: 2.5rem;
    max-width: 1000px;
    margin: 2.5rem auto;
    animation: fadeInUp 0.6s ease;
  }

  @keyframes fadeInUp {
    from {
      opacity: 0;
      transform: translateY(40px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  .panel h5 {
    font-size: 1.75rem;
    font-weight: 800;
    color: var(--gray-900);
    margin-bottom: 1.5rem;
    letter-spacing: -0.02em;
    display: flex;
    align-items: center;
    gap: 12px;
  }

  .panel h5::before {
    content: '';
    display: inline-block;
    width: 5px;
    height: 32px;
    background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
    border-radius: 10px;
  }

  /* Form Sections */
  .section-divider {
    margin: 2rem 0;
    border: 0;
    height: 2px;
    background: linear-gradient(90deg, transparent, var(--gray-200), transparent);
    position: relative;
  }

  .section-divider::after {
    content: attr(data-label);
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: white;
    padding: 0 1rem;
    color: var(--gray-600);
    font-size: 0.85rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
  }

  /* Form Labels */
  label {
    font-weight: 600;
    color: var(--gray-700);
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
    letter-spacing: -0.01em;
    display: flex;
    align-items: center;
    gap: 6px;
  }

  label i {
    color: var(--primary);
    font-size: 1rem;
  }

  /* Form Controls */
  .form-control,
  .form-select {
    height: 48px;
    border: 2px solid var(--gray-200);
    border-radius: 14px;
    padding: 0 1rem;
    font-size: 0.95rem;
    font-weight: 500;
    color: var(--gray-900);
    transition: all 0.3s ease;
    background: white;
  }

  textarea.form-control {
    height: auto;
    padding: 0.75rem 1rem;
    min-height: 100px;
  }

  .form-control:focus,
  .form-select:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
    outline: none;
  }

  .form-control::placeholder {
    color: var(--gray-400);
  }

  .input-sm {
    height: 48px;
  }

  /* File Input */
  input[type="file"] {
    padding: 0.7rem 1rem;
    cursor: pointer;
  }

  input[type="file"]::file-selector-button {
    background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
    color: white;
    border: none;
    border-radius: 10px;
    padding: 8px 16px;
    font-weight: 600;
    cursor: pointer;
    margin-right: 1rem;
    transition: all 0.3s ease;
  }

  input[type="file"]::file-selector-button:hover {
    transform: scale(1.05);
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
  }

  /* Small Text */
  .text-muted {
    font-size: 0.85rem;
    color: var(--gray-600) !important;
    font-weight: 500;
  }

  /* Form Groups */
  .row.g-3 {
    row-gap: 1.5rem !important;
  }

  /* Submit Button */
  .btn-primary {
    background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
    border: none;
    border-radius: 16px;
    padding: 14px 32px;
    font-weight: 700;
    color: white;
    box-shadow: 0 8px 20px rgba(99, 102, 241, 0.3);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    display: inline-flex;
    align-items: center;
    gap: 10px;
    font-size: 1rem;
    letter-spacing: -0.01em;
  }

  .btn-primary:hover {
    transform: translateY(-3px) scale(1.02);
    box-shadow: 0 12px 30px rgba(99, 102, 241, 0.4);
  }

  .btn-primary:active {
    transform: translateY(-1px);
  }

  .btn-primary i {
    font-size: 1.1rem;
  }

  /* Section Headers with Icons */
  .section-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 2rem 0 1.5rem;
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--gray-800);
  }

  .section-header i {
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, var(--primary-light) 0%, var(--secondary) 100%);
    color: white;
    border-radius: 10px;
    font-size: 1rem;
  }

  /* Responsive */
  @media (max-width: 768px) {
    .panel {
      padding: 1.5rem;
      margin: 1.5rem;
      border-radius: 20px;
    }

    .panel h5 {
      font-size: 1.4rem;
    }

    .section-header {
      font-size: 1rem;
    }

    .form-control,
    .form-select,
    .input-sm {
      height: 44px;
      font-size: 0.9rem;
    }

    .btn-primary {
      width: 100%;
      justify-content: center;
      padding: 12px 24px;
    }
  }

  /* Form Animation */
  .col-md-6,
  .col-12 {
    animation: slideIn 0.5s ease backwards;
  }

  .col-md-6:nth-child(1) { animation-delay: 0.05s; }
  .col-md-6:nth-child(2) { animation-delay: 0.1s; }
  .col-md-6:nth-child(3) { animation-delay: 0.15s; }
  .col-md-6:nth-child(4) { animation-delay: 0.2s; }

  @keyframes slideIn {
    from {
      opacity: 0;
      transform: translateX(-20px);
    }
    to {
      opacity: 1;
      transform: translateX(0);
    }
  }

  /* Custom HR */
  hr {
    margin: 2rem 0;
    border: 0;
    height: 2px;
    background: linear-gradient(90deg, transparent, var(--gray-200), transparent);
  }
</style>
</head>
<body>

<div class="content-wrapper">
  <nav class="navbar navbar-dark px-4 py-2">
    <div class="d-flex align-items-center gap-3">
      <?php if(!empty($company['logo'])): ?>
        <img src="<?= htmlspecialchars($company['logo']); ?>" alt="logo">
      <?php endif; ?>
      <span class="navbar-brand"><?= strtoupper($company['company_name']); ?></span>
    </div>
    <div class="d-flex gap-2">
      <!-- <a href="<?= htmlspecialchars($company_slug); ?>" class="btn btn-light btn-sm">
        <i class="bi bi-arrow-left"></i> Back
      </a> -->

      <a href="/vcard/<?= htmlspecialchars($company_slug); ?>" class="btn btn-light btn-sm">
  <i class="bi bi-arrow-left"></i> Back
</a>

      <a href="../logout.php" class="btn btn-outline-light btn-sm">
        <i class="bi bi-box-arrow-right"></i> Logout
      </a>
    </div>
  </nav>

  <div class="panel">
    <h5>Edit Employee Card</h5>

    <form action="update_employee.php" method="POST" enctype="multipart/form-data">
      <input type="hidden" name="id" value="<?= (int)$emp['id']; ?>">
      <input type="hidden" name="company_slug" value="<?= htmlspecialchars($company_slug); ?>">

      <div class="row g-3">
        <!-- Basic Information -->
        <div class="col-md-6">
          <label><i class="bi bi-person"></i> Employee Name</label>
          <input type="text" class="form-control input-sm" name="emp_name"
                 value="<?= htmlspecialchars($emp['emp_name']); ?>" required
                 placeholder="John Doe">
        </div>

        <div class="col-md-6">
          <label><i class="bi bi-briefcase"></i> Designation</label>
          <input type="text" class="form-control input-sm" name="designation"
                 value="<?= htmlspecialchars($emp['designation']); ?>"
                 placeholder="Senior Manager">
        </div>

        <div class="col-md-6">
          <label><i class="bi bi-image"></i> Replace Photo (optional)</label>
          <input type="file" class="form-control input-sm" name="photo" accept="image/*">
          <?php if(!empty($emp['photo'])): ?>
            <small class="text-muted d-block mt-2">
              <i class="bi bi-check-circle-fill text-success"></i>
              Current: <?= htmlspecialchars(basename($emp['photo'])); ?>
            </small>
          <?php endif; ?>
        </div>

        <div class="col-md-6">
          <label><i class="bi bi-telephone"></i> Primary Phone</label>
          <input type="text" class="form-control input-sm" name="phone"
                 value="<?= htmlspecialchars($contact['phone'] ?? ''); ?>"
                 placeholder="+1 234 567 8900">
        </div>

        <div class="col-md-6">
          <label><i class="bi bi-envelope"></i> Business Email</label>
          <input type="email" class="form-control input-sm" name="email"
                 value="<?= htmlspecialchars($contact['email'] ?? ''); ?>"
                 placeholder="john@company.com">
        </div>

        <div class="col-md-6">
          <label><i class="bi bi-whatsapp"></i> WhatsApp</label>
          <input type="text" class="form-control input-sm" name="whatsapp"
                 value="<?= htmlspecialchars($contact['whatsapp'] ?? ''); ?>"
                 placeholder="+1 234 567 8900">
        </div>

        <div class="col-12"><hr></div>

        <!-- About Section -->
        <div class="col-md-6">
          <label><i class="bi bi-pencil"></i> Headline</label>
          <input type="text" class="form-control input-sm" name="headline"
                 value="<?= htmlspecialchars($about['headline'] ?? ''); ?>"
                 placeholder="Passionate about innovation">
        </div>

        <div class="col-md-6">
          <label><i class="bi bi-calendar-check"></i> Booking Link (Calendly/Google)</label>
          <input type="url" class="form-control input-sm" name="calendar"
                 value="<?= htmlspecialchars($calendar); ?>"
                 placeholder="https://calendly.com/yourlink">
        </div>

        <div class="col-12">
          <label><i class="bi bi-chat-left-text"></i> About</label>
          <textarea class="form-control" rows="4" name="about"
                    placeholder="Tell us about yourself..."><?= htmlspecialchars($about['text'] ?? ''); ?></textarea>
        </div>

        <div class="col-12"><hr></div>

        <!-- Web Links -->
        <div class="col-md-6">
          <label><i class="bi bi-globe"></i> Website</label>
          <input type="url" class="form-control input-sm" name="website"
                 value="<?= htmlspecialchars($links['website'] ?? ''); ?>"
                 placeholder="https://www.yourwebsite.com">
        </div>

        <div class="col-md-6">
          <label><i class="bi bi-geo-alt"></i> Google Maps</label>
          <input type="url" class="form-control input-sm" name="maps"
                 value="<?= htmlspecialchars($links['maps'] ?? ''); ?>"
                 placeholder="https://maps.google.com/...">
        </div>

        <div class="col-12">
          <label><i class="bi bi-file-pdf"></i> Brochure (PDF URL)</label>
          <input type="url" class="form-control input-sm" name="brochure"
                 value="<?= htmlspecialchars($links['brochure'] ?? ''); ?>"
                 placeholder="https://yoursite.com/brochure.pdf">
        </div>

        <div class="col-12"><hr></div>

        <!-- Social Media -->
        <div class="col-md-6">
          <label><i class="bi bi-linkedin"></i> LinkedIn</label>
          <input type="url" class="form-control input-sm" name="linkedin"
                 value="<?= htmlspecialchars($social['linkedin'] ?? ''); ?>"
                 placeholder="https://linkedin.com/in/username">
        </div>

        <div class="col-md-6">
          <label><i class="bi bi-instagram"></i> Instagram</label>
          <input type="url" class="form-control input-sm" name="instagram"
                 value="<?= htmlspecialchars($social['instagram'] ?? ''); ?>"
                 placeholder="https://instagram.com/username">
        </div>

        <div class="col-md-6">
          <label><i class="bi bi-facebook"></i> Facebook</label>
          <input type="url" class="form-control input-sm" name="facebook"
                 value="<?= htmlspecialchars($social['facebook'] ?? ''); ?>"
                 placeholder="https://facebook.com/username">
        </div>

        <div class="col-md-6">
          <label><i class="bi bi-twitter-x"></i> X (Twitter)</label>
          <input type="url" class="form-control input-sm" name="twitter"
                 value="<?= htmlspecialchars($social['twitter'] ?? ''); ?>"
                 placeholder="https://x.com/username">
        </div>
      </div>

      <div class="text-end mt-4">
        <button type="submit" class="btn btn-primary">
          <i class="bi bi-save"></i> Save Changes
        </button>
      </div>
    </form>
  </div>
</div>

</body>
</html>
