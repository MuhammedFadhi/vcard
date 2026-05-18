<?php
// Company Overview/Edit
session_start();
include('includes/config.php');
include('includes/functions.php');

if (!isset($_SESSION['company_id'], $_SESSION['company_slug'])) {
  header("Location: index.php"); exit;
}

$company_id   = (int)$_SESSION['company_id'];
$company_slug = $_GET['company'] ?? $_SESSION['company_slug'];

// fetch company with theme colors
$stmt = $conn->prepare("SELECT id, company_name, company_slug, logo, created_by, email, theme_color1, theme_color2, social_links FROM companies WHERE id=? LIMIT 1");
$stmt->bind_param("i", $company_id);
$stmt->execute();
$company = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Decode social media links (if any)
$social = json_decode($company['social_links'] ?? '{}', true);
$linkedin  = $social['linkedin']  ?? '';
$instagram = $social['instagram'] ?? '';
$facebook  = $social['facebook']  ?? '';
$twitter   = $social['twitter']   ?? '';
$website = $social['website'] ?? '';



if (!$company) { header("Location: logout.php"); exit; }

// Set default colors if not set
$theme_color1 = $company['theme_color1'] ?? '#667eea';
$theme_color2 = $company['theme_color2'] ?? '#764ba2';

$err = ""; $ok = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $company_name = trim($_POST['company_name'] ?? $company['company_name']);
  $created_by   = trim($_POST['created_by']   ?? $company['created_by']);
  $email        = trim($_POST['email']        ?? $company['email']);
  $newpass      = trim($_POST['new_password'] ?? "");
  $theme_color1 = trim($_POST['theme_color1'] ?? $theme_color1);
  $theme_color2 = trim($_POST['theme_color2'] ?? $theme_color2);


  $linkedin  = trim($_POST['linkedin'] ?? '');
$instagram = trim($_POST['instagram'] ?? '');
$facebook  = trim($_POST['facebook'] ?? '');
$twitter   = trim($_POST['twitter'] ?? '');
$website = trim($_POST['website'] ?? '');
$website = trim($_POST['website'] ?? '');



$social_links = json_encode([
    "linkedin"  => $linkedin,
    "instagram" => $instagram,
    "facebook"  => $facebook,
    "twitter"   => $twitter,
      "website"   => $website
], JSON_UNESCAPED_SLASHES);


  $logo_rel = $company['logo'];

  // upload logo
  if (!empty($_FILES['logo']['name']) && is_uploaded_file($_FILES['logo']['tmp_name'])) {
    $dir = UPLOAD_FS . $company_slug . DIRECTORY_SEPARATOR . 'logos' . DIRECTORY_SEPARATOR;
    ensure_dir($dir);
    $fname   = time().'_'.basename($_FILES['logo']['name']);
    $dest_fs = $dir.$fname;
    if (move_uploaded_file($_FILES['logo']['tmp_name'], $dest_fs)) {
      if (!empty($logo_rel)) {
        $old = realpath(__DIR__ . DIRECTORY_SEPARATOR . $logo_rel);
        if ($old && is_file($old)) @unlink($old);
      }
      $logo_rel = 'uploads/'.$company_slug.'/logos/'.$fname;
    }
  }

  if ($company_name === '' || $email === '') {
    $err = "Company name and email are required.";
  } else {
    if ($newpass !== '') {
      $hash = password_hash($newpass, PASSWORD_BCRYPT);
      $q = $conn->prepare("UPDATE companies
      SET company_name=?, created_by=?, email=?, logo=?, password=?, theme_color1=?, theme_color2=?, social_links=?
      WHERE id=?");
  $q->bind_param("ssssssssi", $company_name, $created_by, $email, $logo_rel, $hash, $theme_color1, $theme_color2, $social_links, $company_id);

    } else {
      $q = $conn->prepare("UPDATE companies
      SET company_name=?, created_by=?, email=?, logo=?, theme_color1=?, theme_color2=?, social_links=?
      WHERE id=?");
  $q->bind_param("sssssssi", $company_name, $created_by, $email, $logo_rel, $theme_color1, $theme_color2, $social_links, $company_id);

    }
    if ($q->execute()) {
      $ok = "Company profile updated successfully!";
      $company['company_name'] = $company_name;
      $company['created_by']   = $created_by;
      $company['email']        = $email;
      $company['logo']         = $logo_rel;
      $company['theme_color1'] = $theme_color1;
      $company['theme_color2'] = $theme_color2;
      $company['social_links'] = $social_links;

    } else {
      $err = "DB Error: ".$q->error;
    }
    $q->close();
  }
}

$logo_web = !empty($company['logo']) ? APP_BASE.ltrim($company['logo'], '/\\') : '';
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title><?= htmlspecialchars($company['company_name']); ?> • Overview</title>
<meta name="viewport" content="width=device-width, initial-scale=1" />
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
    --warning: #f59e0b;
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
    --shadow-2xl: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
  } */




/* custom code for color theme above are the og code */


:root {
  /* Primary theme colors (dynamic from PHP) */
  --primary: <?= $theme_color1 ?>;
  --primary-dark: <?= $theme_color2 ?>;
  --primary-light: <?= $theme_color1 ?>;
  --secondary: <?= $theme_color2 ?>;

  /* Reusable linear gradient variable */
  --primary-gradient: linear-gradient(135deg, <?= $theme_color1 ?> 0%, <?= $theme_color2 ?> 100%);

  /* Alert & system colors */
  --success: #10b981;
  --danger: #ef4444;
  --warning: #f59e0b;

  /* Neutral grays */
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
  --shadow-2xl: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
}



























  * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
  }

  body {
    background: linear-gradient(135deg, <?= htmlspecialchars($theme_color1) ?> 0%, <?= htmlspecialchars($theme_color2) ?> 100%);
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

  .navbar-brand {
    display: flex;
    align-items: center;
    gap: 12px;
    text-decoration: none;
    transition: transform 0.3s ease;
  }

  .navbar-brand:hover {
    transform: scale(1.02);
  }

  .navbar-brand img {
    height: 42px;
    width: 42px;
    border-radius: 10px;
    object-fit: cover;
    box-shadow: var(--shadow);
  }

  .navbar-brand span {
    color: var(--gray-900);
    font-weight: 700;
    font-size: 1.1rem;
    letter-spacing: -0.02em;
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

  /* Wrapper */
  .wrap {
    max-width: 900px;
    margin: 2.5rem auto;
    padding: 0 1rem;
  }

  /* Card */
  .card {
    background: rgba(255, 255, 255, 0.98);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 28px;
    box-shadow: var(--shadow-xl);
    padding: 2.5rem;
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

  .card h4 {
    font-size: 1.75rem;
    font-weight: 800;
    color: var(--gray-900);
    margin-bottom: 1.5rem;
    letter-spacing: -0.02em;
    display: flex;
    align-items: center;
    gap: 12px;
  }

  .card h4::before {
    content: '';
    display: inline-block;
    width: 5px;
    height: 32px;
    background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
    border-radius: 10px;
  }

  /* Alerts */
  .alert {
    border-radius: 16px;
    padding: 16px 20px;
    font-weight: 600;
    font-size: 0.95rem;
    border: 2px solid;
    margin-bottom: 1.5rem;
    animation: slideInDown 0.4s ease;
  }

  @keyframes slideInDown {
    from {
      opacity: 0;
      transform: translateY(-20px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  .alert-danger {
    background: rgba(239, 68, 68, 0.1);
    border-color: var(--danger);
    color: var(--danger);
  }

  .alert-success {
    background: rgba(16, 185, 129, 0.1);
    border-color: var(--success);
    color: var(--success);
  }

  /* Form Elements */
  .form-label {
    font-weight: 600;
    color: var(--gray-700);
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
    letter-spacing: -0.01em;
    display: flex;
    align-items: center;
    gap: 6px;
  }

  .form-control {
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

  .form-control:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
    outline: none;
  }

  .form-control::placeholder {
    color: darkgrey;
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

  /* Text Muted */
  .text-muted {
    font-size: 0.85rem;
    color: var(--gray-600) !important;
    font-weight: 500;
  }

  .text-muted a {
    color: var(--primary);
    text-decoration: none;
    font-weight: 600;
    transition: color 0.2s ease;
  }

  .text-muted a:hover {
    color: var(--primary-dark);
    text-decoration: underline;
  }

  /* Button */
  .btn-primary {
    background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
    border: none;
    border-radius: 14px;
    padding: 12px 28px;
    font-weight: 700;
    color: white;
    box-shadow: 0 8px 20px rgba(99, 102, 241, 0.3);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 1rem;
    letter-spacing: -0.01em;
  }

  .btn-primary:hover {
    transform: translateY(-3px) scale(1.02);
    box-shadow: 0 12px 30px rgba(99, 102, 241, 0.4);
    color: white;
  }

  .btn-primary:active {
    transform: translateY(-1px);
  }

  .btn-primary i {
    font-size: 1.1rem;
  }

  /* Row Gaps */
  .row.g-3 {
    row-gap: 1.5rem !important;
  }

  /* Responsive */
  @media (max-width: 768px) {
    .wrap {
      margin: 1.5rem auto;
      padding: 0 0.75rem;
    }

    .card {
      padding: 1.5rem;
      border-radius: 20px;
    }

    .card h4 {
      font-size: 1.4rem;
    }

    .form-control {
      height: 44px;
      font-size: 0.9rem;
    }

    .btn-primary {
      width: 100%;
      justify-content: center;
      padding: 12px 24px;
    }

    .navbar-brand span {
      font-size: 0.95rem;
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
  .col-12:nth-child(5) { animation-delay: 0.25s; }
  .col-12:nth-child(6) { animation-delay: 0.3s; }

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

  /* Info Section */
  .info-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: linear-gradient(135deg, #f0f4ff 0%, #e8f0fe 100%);
    color: var(--primary);
    padding: 8px 16px;
    border-radius: 12px;
    font-size: 0.85rem;
    font-weight: 600;
    margin-top: 0.5rem;
    border: 2px solid transparent;
    transition: all 0.3s ease;
  }

  .info-badge:hover {
    border-color: var(--primary-light);
    transform: translateY(-2px);
  }

  .info-badge i {
    font-size: 1rem;
  }

  /* Color Picker Section */
  .color-picker-section {
    background: linear-gradient(135deg, #f0f4ff 0%, #e8f0fe 100%);
    border: 2px solid var(--gray-100);
    border-radius: 20px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
  }

  .color-picker-section h5 {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--gray-900);
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .color-picker-wrapper {
    display: flex;
    align-items: center;
    gap: 12px;
  }

  .color-input-group {
    flex: 1;
  }

  .color-input-wrapper {
    position: relative;
    display: flex;
    align-items: center;
    gap: 12px;
    background: white;
    border: 2px solid var(--gray-200);
    border-radius: 12px;
    padding: 8px 12px;
    transition: all 0.3s ease;
  }

  .color-input-wrapper:hover {
    border-color: var(--primary);
  }

  .color-input-wrapper input[type="color"] {
    width: 50px;
    height: 50px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: transform 0.3s ease;
  }

  .color-input-wrapper input[type="color"]:hover {
    transform: scale(1.1);
  }

  .color-input-wrapper input[type="text"] {
    flex: 1;
    border: none;
    padding: 8px;
    font-size: 0.95rem;
    font-weight: 600;
    color: var(--gray-900);
    text-transform: uppercase;
    background: transparent;
  }

  .color-input-wrapper input[type="text"]:focus {
    outline: none;
  }

  .gradient-preview {
    height: 80px;
    border-radius: 12px;
    box-shadow: var(--shadow-lg);
    transition: all 0.3s ease;
    border: 3px solid white;
  }

  .gradient-preview:hover {
    transform: scale(1.02);
    box-shadow: var(--shadow-xl);
  }

  .preset-colors {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
    gap: 12px;
    margin-top: 1rem;
  }

  .preset-btn {
    height: 50px;
    border-radius: 12px;
    border: 3px solid white;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: var(--shadow);
    position: relative;
    overflow: hidden;
  }

  .preset-btn:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-xl);
    border-color: var(--gray-900);
  }

  .preset-btn::after {
    content: '✓';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%) scale(0);
    color: white;
    font-size: 1.5rem;
    font-weight: bold;
    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
    transition: transform 0.3s ease;
  }

  .preset-btn:active::after {
    transform: translate(-50%, -50%) scale(1);
  }
</style>
</head>
<body>

<div class="content-wrapper">
  <nav class="navbar navbar-dark px-3 py-2">
    <div class="container-fluid">
      <a class="navbar-brand" href="<?= APP_BASE.$company_slug; ?>">
        <?php if($logo_web): ?><img src="<?= htmlspecialchars($logo_web); ?>" alt="logo"><?php endif; ?>
        <span><?= htmlspecialchars(strtoupper($company['company_name'])); ?> SETTINGS</span>
      </a>
      <div class="d-flex gap-2">
        <a class="btn btn-light btn-sm" href="<?= APP_BASE.$_SESSION['company_slug']; ?>">
          <i class="bi bi-grid"></i> Dashboard
        </a>
        <a class="btn btn-outline-light btn-sm" href="logout.php">
          <i class="bi bi-box-arrow-right"></i> Logout
        </a>
      </div>
    </div>
  </nav>

  <div class="wrap">
    <div class="card bg-white">
      <h4>Company Profile</h4>

      <?php if($err): ?><div class="alert alert-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($err); ?></div><?php endif; ?>
      <?php if($ok):  ?><div class="alert alert-success"><i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($ok); ?></div><?php endif; ?>

      <form method="post" enctype="multipart/form-data" class="row g-3">

        <!-- Theme Color Picker Section -->
        <div class="col-12">
          <div class="color-picker-section">
            <h5><i class="bi bi-palette-fill"></i> Brand Theme Colors</h5>
            <p class="text-muted mb-3" style="font-size: 0.9rem;">
              <i class="bi bi-info-circle"></i> Choose your brand's gradient colors. This will apply across all pages.
            </p>

            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label"><i class="bi bi-circle-fill" style="color: <?= htmlspecialchars($theme_color1) ?>"></i> Primary Color</label>
                <div class="color-input-wrapper">
                  <input type="color" id="color1" name="theme_color1" value="<?= htmlspecialchars($theme_color1) ?>" onchange="updateGradientPreview()">
                  <input type="text" id="color1_text" value="<?= htmlspecialchars($theme_color1) ?>" onchange="updateFromText(1)" maxlength="7" pattern="#[0-9A-Fa-f]{6}">
                </div>
              </div>

              <div class="col-md-6">
                <label class="form-label"><i class="bi bi-circle-fill" style="color: <?= htmlspecialchars($theme_color2) ?>"></i> Secondary Color</label>
                <div class="color-input-wrapper">
                  <input type="color" id="color2" name="theme_color2" value="<?= htmlspecialchars($theme_color2) ?>" onchange="updateGradientPreview()">
                  <input type="text" id="color2_text" value="<?= htmlspecialchars($theme_color2) ?>" onchange="updateFromText(2)" maxlength="7" pattern="#[0-9A-Fa-f]{6}">
                </div>
              </div>

              <div class="col-12">
                <label class="form-label"><i class="bi bi-eye-fill"></i> Live Preview</label>
                <div id="gradientPreview" class="gradient-preview" style="background: linear-gradient(135deg, <?= htmlspecialchars($theme_color1) ?> 0%, <?= htmlspecialchars($theme_color2) ?> 100%);"></div>
              </div>

              <div class="col-12">
                <label class="form-label"><i class="bi bi-star-fill"></i> Preset Themes</label>
                <div class="preset-colors">
                  <div class="preset-btn" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%)" onclick="applyPreset('#667eea', '#764ba2')" title="Purple Dream"></div>
                  <div class="preset-btn" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%)" onclick="applyPreset('#f093fb', '#f5576c')" title="Pink Passion"></div>
                  <div class="preset-btn" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)" onclick="applyPreset('#4facfe', '#00f2fe')" title="Ocean Blue"></div>
                  <div class="preset-btn" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)" onclick="applyPreset('#43e97b', '#38f9d7')" title="Fresh Mint"></div>
                  <div class="preset-btn" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%)" onclick="applyPreset('#fa709a', '#fee140')" title="Sunset"></div>
                  <div class="preset-btn" style="background: linear-gradient(135deg, #30cfd0 0%, #330867 100%)" onclick="applyPreset('#30cfd0', '#330867')" title="Deep Sea"></div>
                  <div class="preset-btn" style="background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%)" onclick="applyPreset('#a8edea', '#fed6e3')" title="Pastel Dream"></div>
                  <div class="preset-btn" style="background: linear-gradient(135deg, #ff6e7f 0%, #bfe9ff 100%)" onclick="applyPreset('#ff6e7f', '#bfe9ff')" title="Cotton Candy"></div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-md-6">
          <label class="form-label"><i class="bi bi-building"></i> Company Name</label>
          <input type="text" name="company_name" class="form-control"
                 value="<?= htmlspecialchars($company['company_name']); ?>"
                 placeholder="Enter company name" required>
        </div>

        <div class="col-md-6">
          <label class="form-label"><i class="bi bi-person-badge"></i> Created By (Admin Name)</label>
          <input type="text" name="created_by" class="form-control"
                 value="<?= htmlspecialchars($company['created_by']); ?>"
                 placeholder="Admin name">
        </div>

        <div class="col-md-6">
          <label class="form-label"><i class="bi bi-envelope"></i> Email</label>
          <input type="email" name="email" class="form-control"
                 value="<?= htmlspecialchars($company['email']); ?>"
                 placeholder="company@example.com" required>
        </div>

        <div class="col-md-6">
          <label class="form-label"><i class="bi bi-image"></i> Replace Logo (optional)</label>
          <input type="file" name="logo" class="form-control" accept="image/*">
          <?php if($logo_web): ?>
            <small class="text-muted d-block mt-2">
              <i class="bi bi-check-circle-fill text-success"></i>
              Current logo: <a target="_blank" href="<?= htmlspecialchars($logo_web); ?>">view logo</a>
            </small>
          <?php endif; ?>
        </div>

        <div class="col-12">
          <label class="form-label"><i class="bi bi-shield-lock"></i> New Password (optional)</label>
          <input type="password" name="new_password" class="form-control"
                 placeholder="Leave blank to keep current password">
          <small class="text-muted d-block mt-2">
            <i class="bi bi-info-circle"></i> Only enter a password if you want to change it
          </small>
        </div>

        <!-- 🌐 Social Media Links Section -->
<div class="col-12 mt-4">
  <h5><i class="bi bi-globe"></i> Social Media Links</h5>
  <p class="text-muted" style="font-size: 0.9rem;">
    <i class="bi bi-info-circle"></i> Add your official company social media links. They will also show in your employee cards.
  </p>

  <div class="col-md-12 mb-3">
  <label class="form-label"><i class="bi bi-browser-chrome"></i> Website</label>
  <input type="url" name="website" class="form-control"
         placeholder="https://www.yourcompany.com"
         value="<?= htmlspecialchars($website ?? ''); ?>">
</div>


  <div class="row g-3">
    <div class="col-md-6">
      <label class="form-label"><i class="bi bi-linkedin"></i> LinkedIn</label>
      <input type="url" name="linkedin" class="form-control"
             placeholder="https://linkedin.com/company/yourpage"
             value="<?= htmlspecialchars($linkedin); ?>">
    </div>

    <div class="col-md-6">
      <label class="form-label"><i class="bi bi-instagram"></i> Instagram</label>
      <input type="url" name="instagram" class="form-control"
             placeholder="https://instagram.com/yourpage"
             value="<?= htmlspecialchars($instagram); ?>">
    </div>

    <div class="col-md-6">
      <label class="form-label"><i class="bi bi-facebook"></i> Facebook</label>
      <input type="url" name="facebook" class="form-control"
             placeholder="https://facebook.com/yourpage"
             value="<?= htmlspecialchars($facebook); ?>">
    </div>

    <div class="col-md-6">
      <label class="form-label"><i class="bi bi-twitter-x"></i> Twitter / X</label>
      <input type="url" name="twitter" class="form-control"
             placeholder="https://twitter.com/yourpage"
             value="<?= htmlspecialchars($twitter); ?>">
    </div>
  </div>
</div>
<!-- End Social Media Links Section -->


        <div class="col-12">
          <div class="info-badge">
            <i class="bi bi-link-45deg"></i>
            <span>Company Slug: <strong><?= htmlspecialchars($company['company_slug']); ?></strong></span>
          </div>
        </div>

        <div class="col-12 text-end">
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-save"></i> Save Changes
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function updateGradientPreview() {
  const color1 = document.getElementById('color1').value;
  const color2 = document.getElementById('color2').value;
  document.getElementById('color1_text').value = color1;
  document.getElementById('color2_text').value = color2;
  document.getElementById('gradientPreview').style.background =
    `linear-gradient(135deg, ${color1} 0%, ${color2} 100%)`;
}

function updateFromText(num) {
  const textValue = document.getElementById(`color${num}_text`).value;
  if (/^#[0-9A-Fa-f]{6}$/.test(textValue)) {
    document.getElementById(`color${num}`).value = textValue;
    updateGradientPreview();
  }
}

function applyPreset(color1, color2) {
  document.getElementById('color1').value = color1;
  document.getElementById('color2').value = color2;
  document.getElementById('color1_text').value = color1;
  document.getElementById('color2_text').value = color2;
  updateGradientPreview();
}

// Update text inputs when color picker changes
document.getElementById('color1').addEventListener('input', updateGradientPreview);
document.getElementById('color2').addEventListener('input', updateGradientPreview);
</script>

</body>
</html>
