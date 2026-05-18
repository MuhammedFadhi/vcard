<?php
session_start();
include('includes/config.php');

// Block direct access if not logged in
if(!isset($_SESSION['company_id'])) { header("Location: index.php"); exit; }

$company_id  = $_SESSION['company_id'];
$company_slug = $_GET['company'] ?? ($_SESSION['company_slug'] ?? '');



// custom code for color theme

$stmt = $conn->prepare("SELECT id, company_name, company_slug, logo, created_by, email, theme_color1, theme_color2, social_links FROM companies WHERE id=? LIMIT 1");
$stmt->bind_param("i", $company_id);
$stmt->execute();
$company = $stmt->get_result()->fetch_assoc();
$stmt->close();


// custom code for server issue


// Decode company social links for default pre-fill
$social_defaults = json_decode($company['social_links'] ?? '{}', true);
$default_linkedin  = $social_defaults['linkedin']  ?? '';
$default_instagram = $social_defaults['instagram'] ?? '';
$default_facebook  = $social_defaults['facebook']  ?? '';
$default_twitter   = $social_defaults['twitter']   ?? '';
$default_website   = $social_defaults['website']   ?? '';



$theme_color1 = $company['theme_color1'] ?? '#667eea';
$theme_color2 = $company['theme_color2'] ?? '#764ba2';






// Fetch company (optional header info)
$company = $conn->query("SELECT * FROM companies WHERE id='$company_id'")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Create Employee Card | <?= strtoupper($company['company_name']); ?></title>
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
    --shadow-2xl: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
  } */




  :root {
    /* 🌈 Dynamic theme colors (auto from PHP) */
    --primary: <?= $theme_color1 ?>;
    --primary-dark: <?= $theme_color2 ?>;

    /* Derived tints (lighter variants of theme colors) */
    --primary-light: <?= $theme_color1 ?>cc; /* ~80% opacity for softer tone */
    --secondary: <?= $theme_color2 ?>b3;     /* ~70% opacity for secondary tint */

    /* Gradient mix for use anywhere */
    --primary-gradient: linear-gradient(135deg, <?= $theme_color1 ?> 0%, <?= $theme_color2 ?> 100%);

    /* ✅ Static colors (neutral palette) */
    --success: #10b981;
    --danger: #ef4444;
    --gray-50: #f9fafb;
    --gray-100: #f3f4f6;
    --gray-200: #e5e7eb;
    --gray-300: #d1d5db;
    --gray-600: #4b5563;
    --gray-700: #374151;
    --gray-900: #111827;

    /* ✅ Shadows */
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
    /* custom code for color theme */
    background: linear-gradient(135deg, <?= $theme_color1 ?> 0%, <?= $theme_color2 ?> 100%);
    /* background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); */
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
    margin-bottom: 2rem;
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

  /* Builder Layout */
  .builder-wrap {
    display: grid;
    grid-template-columns: 1.2fr 0.8fr;
    gap: 24px;
    max-width: 1400px;
    margin: 0 auto;
    padding-bottom: 3rem;
  }

  @media (max-width: 992px) {
    .builder-wrap {
      grid-template-columns: 1fr;
    }
  }

  /* Panel */
  .panel {
    background: rgba(255, 255, 255, 0.98);
    backdrop-filter: blur(20px);
    border-radius: 24px;
    box-shadow: var(--shadow-xl);
    border: 1px solid rgba(255, 255, 255, 0.3);
    padding: 2rem;
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

  .panel > h5 {
    font-size: 1.5rem;
    font-weight: 800;
    color: var(--gray-900);
    margin-bottom: 1.5rem;
    letter-spacing: -0.02em;
    display: flex;
    align-items: center;
    gap: 12px;
  }

  .panel > h5::before {
    content: '';
    display: inline-block;
    width: 5px;
    height: 32px;
    background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
    border-radius: 10px;
  }

  /* Section */
  .section {
    border: 2px solid var(--gray-100);
    border-radius: 18px;
    padding: 1.25rem;
    margin-bottom: 1.25rem;
    background: white;
    transition: all 0.3s ease;
  }

  .section:hover {
    border-color: var(--gray-200);
    box-shadow: var(--shadow-lg);
  }

  .section-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1rem;
    padding-bottom: 0.75rem;
    border-bottom: 2px solid var(--gray-100);
  }

  .section-head h6 {
    margin: 0;
    font-weight: 700;
    color: var(--gray-900);
    font-size: 1.05rem;
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .section-head h6::before {
    content: '';
    display: inline-block;
    width: 4px;
    height: 20px;
    background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
    border-radius: 10px;
  }

  /* Toggle Switch */
  .switch {
    position: relative;
    width: 52px;
    height: 28px;
    display: inline-block;
  }

  .switch input {
    display: none;
  }

  .slider {
    position: absolute;
    cursor: pointer;
    inset: 0;
    background: var(--gray-300);
    border-radius: 30px;
    transition: 0.3s;
  }

  .slider:before {
    content: "";
    position: absolute;
    height: 22px;
    width: 22px;
    left: 3px;
    top: 3px;
    background: white;
    border-radius: 50%;
    transition: 0.3s;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.3);
  }

  input:checked + .slider {
    background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
  }

  input:checked + .slider:before {
    transform: translateX(24px);
  }

  /* Form Elements */
  label {
    font-weight: 600;
    color: var(--gray-700);
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
    display: block;
  }

  .form-control,
  .form-select {
    height: 46px;
    border: 2px solid var(--gray-200);
    border-radius: 12px;
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
    min-height: 90px;
  }

  .form-control:focus,
  .form-select:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
    outline: none;
  }

  .form-control::placeholder {
    color: darkgrey;
      /* color: var(--gray-400); */
  }

  .input-sm {
    height: 46px;
  }

  input[type="file"] {
    padding: 0.65rem 1rem;
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

  /* Preview Phone */
  .preview-phone {
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    border-radius: 32px;
    padding: 20px 16px 24px;
    width: 100%;
    max-width: 380px;
    margin-inline: auto;
    box-shadow: var(--shadow-2xl);
    position: sticky;
    top: 20px;
  }

  .preview-screen {
    background: #fff;
    border-radius: 24px;
    height: 640px;
    overflow: auto;
    box-shadow: inset 0 0 0 1px rgba(0, 0, 0, 0.1);
  }

  .preview-screen::-webkit-scrollbar {
    width: 6px;
  }

  .preview-screen::-webkit-scrollbar-track {
    background: var(--gray-100);
    border-radius: 10px;
  }

  .preview-screen::-webkit-scrollbar-thumb {
    background: var(--gray-300);
    border-radius: 10px;
  }

  .preview-screen::-webkit-scrollbar-thumb:hover {
    background: var(--gray-400);
  }

  .p-header {
    /* background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); */
       background:  linear-gradient(135deg, <?= $theme_color1 ?> 0%, <?= $theme_color2 ?> 100%);
    color: #fff;
    padding: 20px;
    border-top-left-radius: 24px;
    border-top-right-radius: 24px;
    position: relative;
    overflow: hidden;
  }

  .p-header::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.2) 0%, transparent 70%);
    animation: shimmer 4s infinite;
  }

  @keyframes shimmer {
    0%, 100% { transform: translate(-25%, -25%) rotate(0deg); }
    50% { transform: translate(25%, 25%) rotate(180deg); }
  }

  .p-header .d-flex {
    position: relative;
    z-index: 2;
  }

  .p-header img {
    height: 36px;
    width: 36px;
    border-radius: 8px;
    background: white;
    padding: 3px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
  }

  .p-header strong {
    font-weight: 700;
    text-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
  }

  .p-body {
    padding: 20px;
  }

  .p-avatar {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid white;
    background: var(--gray-100);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
    transition: transform 0.3s ease;
  }

  .preview-screen:hover .p-avatar {
    transform: scale(1.05);
  }

  .p-body h6 {
    font-weight: 700;
    color: var(--gray-900);
    font-size: 1.1rem;
  }

  .p-body small {
    color: var(--gray-600);
    font-weight: 500;
  }

  .quick-actions .btn {
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.85rem;
    padding: 8px 14px;
    border-width: 2px;
    transition: all 0.3s ease;
  }

  .quick-actions .btn-outline-primary {
    border: 2px solid transparent;
    color: <?= $theme_color1 ?>;
    border-radius: 8px;
    background:
      linear-gradient(#fff, #fff) padding-box,
      linear-gradient(135deg, <?= $theme_color1 ?> 0%, <?= $theme_color2 ?> 100%) border-box;
    background-clip: padding-box, border-box;
    background-origin: border-box;
    transition: all 0.3s ease;
  }

  .quick-actions .btn-outline-primary:hover {
    background: linear-gradient(135deg, <?= $theme_color1 ?> 0%, <?= $theme_color2 ?> 100%);
    color: #fff;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
  }


  /* .link-pill {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 14px;
    background: linear-gradient(135deg, #f0f4ff 0%, #e8f0fe 100%);
    border-radius: 12px;
    margin-bottom: 10px;
    font-size: 0.9rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    border: 2px solid transparent;
  }

  .link-pill:hover {
    background: linear-gradient(135deg, #e0e7ff 0%, #dbeafe 100%);
    border-color: var(--primary-light);
    transform: translateX(4px);
  }

  .link-pill i {
    color: var(--primary);
    font-size: 1.2rem;
  } */







/* custom code for color theme */


.link-pill {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 14px;
  background: linear-gradient(135deg, <?= $theme_color1 ?>1A 0%, <?= $theme_color2 ?>1A 100%);
  /* ^ 1A = ~10% opacity of your theme colors for a soft tint */
  border-radius: 12px;
  margin-bottom: 10px;
  font-size: 0.9rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.3s ease;
  border: 2px solid transparent;
}

.link-pill:hover {
  background: linear-gradient(135deg, <?= $theme_color1 ?>33 0%, <?= $theme_color2 ?>33 100%);
  /* ^ 33 = ~20% opacity for stronger hover glow */
  border-color: <?= $theme_color1 ?>;
  transform: translateX(4px);
}

.link-pill i {
  color: <?= $theme_color1 ?>;
  font-size: 1.2rem;
}

















  .footer-save {
    position: sticky;
    bottom: 0;
    background: rgba(255, 255, 255, 0.98);
    backdrop-filter: blur(20px);
    border-top: 2px solid var(--gray-100);
    padding: 1rem;
    text-align: right;
    border-bottom-left-radius: 24px;
    border-bottom-right-radius: 24px;
    margin: 0 -2rem -2rem;
  }

  .footer-save .btn-primary {
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
  }

  .footer-save .btn-primary:hover {
    transform: translateY(-3px) scale(1.02);
    box-shadow: 0 12px 30px rgba(99, 102, 241, 0.4);
  }

  .footer-save .btn-primary:active {
    transform: translateY(-1px);
  }

  /* Text Muted */
  .text-muted {
    color: var(--gray-600) !important;
    font-size: 0.85rem;
  }

  .text-secondary {
    color: var(--gray-600) !important;
  }

  /* Responsive */
  @media (max-width: 992px) {
    .preview-phone {
      position: static;
      max-width: 100%;
    }
  }

  @media (max-width: 768px) {
    body {
      padding: 0;
    }

    .navbar {
      margin-bottom: 1rem;
    }

    .builder-wrap {
      gap: 16px;
      padding: 0 12px 2rem;
    }

    .panel {
      padding: 1.5rem;
      border-radius: 20px;
    }

    .panel > h5 {
      font-size: 1.3rem;
    }

    .section {
      padding: 1rem;
      margin-bottom: 1rem;
    }

    .preview-phone {
      padding: 16px 12px 20px;
      border-radius: 24px;
    }

    .preview-screen {
      height: 560px;
    }

    .footer-save {
      margin: 0 -1.5rem -1.5rem;
    }

    .footer-save .btn-primary {
      width: 100%;
      justify-content: center;
    }
  }

  /* Loading Animation */
  .section {
    animation: slideIn 0.5s ease backwards;
  }

  .section:nth-child(1) { animation-delay: 0.1s; }
  .section:nth-child(2) { animation-delay: 0.2s; }
  .section:nth-child(3) { animation-delay: 0.3s; }
  .section:nth-child(4) { animation-delay: 0.4s; }
  .section:nth-child(5) { animation-delay: 0.5s; }

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
      <a href="<?= $company_slug ?: $_SESSION['company_slug']; ?>" class="btn btn-light btn-sm">
        <i class="bi bi-arrow-left"></i> Back
      </a>
      <a href="../logout.php" class="btn btn-outline-light btn-sm">
        <i class="bi bi-box-arrow-right"></i> Logout
      </a>
    </div>
  </nav>

  <div class="container">
    <div class="builder-wrap">

      <!-- LEFT: Form -->
      <div class="panel">
        <h5>Create Employee Digital Card</h5>
        <form id="empForm" action="save_employee.php" method="POST" enctype="multipart/form-data">
          <input type="hidden" name="company_id" value="<?= (int)$company_id; ?>">
          <input type="hidden" name="company_slug" value="<?= htmlspecialchars($company_slug ?: $_SESSION['company_slug']); ?>">

          <!-- Profile -->
          <div class="section">
            <div class="section-head">
              <h6>Profile</h6>
              <label class="switch">
                <input type="checkbox" checked id="toggle_profile">
                <span class="slider"></span>
              </label>
            </div>
            <div class="row g-3">
              <div class="col-md-6">
                <label>Employee Name</label>
                <input type="text" class="form-control input-sm" name="emp_name" id="emp_name" placeholder="Name" required>
              </div>
              <div class="col-md-6">
                <label>Designation</label>
                <input type="text" class="form-control input-sm" name="designation" id="designation" placeholder="Sales Manager">
              </div>
              <div class="col-md-6">
                <label>Profile Photo</label>
                <input type="file" class="form-control input-sm" name="photo" id="photo" accept="image/*">
              </div>
              <div class="col-md-6">
                <label>Primary Phone</label>
                <input type="text" class="form-control input-sm" name="phone" id="phone" placeholder="+9615xxxxxxxx">
              </div>
              <div class="col-md-6">
                <label>Business Email</label>
                <input type="email" class="form-control input-sm" name="email" id="email" placeholder="name@company.com">
              </div>
              <div class="col-md-6">
                <label>WhatsApp Number</label>
                <input type="text" class="form-control input-sm" name="whatsapp" id="whatsapp" placeholder="+9615xxxxxxxx">
              </div>
            </div>
          </div>

          <!-- About -->
          <div class="section">
            <div class="section-head">
              <h6>Heading + Text</h6>
              <label class="switch">
                <input type="checkbox" checked id="toggle_about">
                <span class="slider"></span>
              </label>
            </div>
            <div>
              <label>Headline</label>
              <input type="text" class="form-control input-sm mb-2" name="headline" id="headline" placeholder="About Me">
              <label>Description</label>
              <textarea class="form-control" rows="3" name="about" id="about" placeholder="Short profile or company pitch..."></textarea>
            </div>
          </div>

          <!-- Social Links -->
          <div class="section">
            <div class="section-head">
              <h6>Social Links</h6>
              <label class="switch">
                <input type="checkbox" id="toggle_social" checked>
                <span class="slider"></span>
              </label>
            </div>
            <div class="row g-3">
              <div class="col-md-6"><label>LinkedIn</label><input type="url" class="form-control input-sm" value="<?= htmlspecialchars($default_linkedin); ?>" name="linkedin" id="linkedin" placeholder="https://linkedin.com/in/..."></div>
              <div class="col-md-6"><label>Instagram</label><input type="url" class="form-control input-sm" value="<?= htmlspecialchars($default_instagram); ?>" name="instagram" id="instagram" placeholder="https://instagram.com/..."></div>
              <div class="col-md-6"><label>Facebook</label><input type="url" class="form-control input-sm" value="<?= htmlspecialchars($default_facebook); ?>" name="facebook" id="facebook" placeholder="https://facebook.com/..."></div>
              <div class="col-md-6"><label>X (Twitter)</label><input type="url" class="form-control input-sm" value="<?= htmlspecialchars($default_twitter); ?>" name="twitter" id="twitter" placeholder="https://x.com/..."></div>
            </div>
          </div>

          <!-- Links -->
          <div class="section">
            <div class="section-head">
              <h6>Links</h6>
              <label class="switch">
                <input type="checkbox" id="toggle_links" checked>
                <span class="slider"></span>
              </label>
            </div>
            <div class="row g-3">
              <div class="col-md-6"><label>Website</label><input type="url" class="form-control input-sm" value="<?= htmlspecialchars($default_website); ?>" name="website" id="website" placeholder="https://example.com"></div>
              <div class="col-md-6"><label>Google Maps (Office)</label><input type="url" class="form-control input-sm" name="maps" id="maps" placeholder="https://maps.google.com/..."></div>
              <div class="col-md-12"><label>Brochure / PDF URL</label><input type="url" class="form-control input-sm" name="brochure" id="brochure" placeholder="https://.../brochure.pdf"></div>
            </div>
          </div>

          <!-- Appointment -->
          <div class="section">
            <div class="section-head">
              <h6>Appointment / Calendar</h6>
              <label class="switch">
                <input type="checkbox" id="toggle_cal" checked>
                <span class="slider"></span>
              </label>
            </div>
            <label>Booking Link (Calendly/Zoho/Google)</label>
            <input type="url" class="form-control input-sm" name="calendar" id="calendar" placeholder="https://calendly.com/...">
          </div>

          <!-- Collect Contacts -->
          <div class="section">
            <div class="section-head">
              <h6>Collect Contacts</h6>
              <label class="switch">
                <input type="checkbox" id="toggle_collect">
                <span class="slider"></span>
              </label>
            </div>
            <small class="text-muted">Enable to show a small form on the card to capture lead name, email, phone.</small>
          </div>

          <div class="footer-save">
            <button type="submit" class="btn btn-primary">
              <i class="bi bi-save"></i> Save Card
            </button>
          </div>
        </form>
      </div>

      <!-- RIGHT: Live Preview (mobile) -->
      <div class="panel">
        <div class="preview-phone">
          <div class="preview-screen">
            <div class="p-header">
              <div class="d-flex align-items-center">
                <?php if(!empty($company['logo'])): ?>
                <img src="<?= htmlspecialchars($company['logo']); ?>" alt="logo">
                <?php endif; ?>
                <strong><?= htmlspecialchars($company['company_name']); ?></strong>
              </div>
            </div>
            <div class="p-body">
              <div class="text-center">
                <img id="pv_photo" src="assets/images/default-user.png" class="p-avatar mb-2" alt="avatar">
                <h6 id="pv_name" class="mb-0">Employee Name</h6>
                <small id="pv_role" class="text-muted d-block mb-2">Designation</small>
                <div class="quick-actions d-flex justify-content-center gap-2 mb-3">
                  <a id="pv_call" href="tel:" class="btn btn-outline-primary btn-sm"><i class="bi bi-telephone"></i> Call</a>
                  <a id="pv_mail" href="mailto:" class="btn btn-outline-primary btn-sm"><i class="bi bi-envelope"></i> Email</a>
                  <a id="pv_whatsapp" target="_blank" href="#" class="btn btn-outline-primary btn-sm"><i class="bi bi-whatsapp"></i> WhatsApp</a>
                </div>
              </div>

              <div id="sec_about">
                <h6 id="pv_headline" class="mb-1">About Me</h6>
                <p id="pv_about" class="text-secondary" style="font-size:14px;">Short profile or company pitch...</p>
              </div>

              <div id="sec_links" class="mt-3">
                <div id="pv_website" class="link-pill d-none"><i class="bi bi-globe2"></i> <span>Website</span></div>
                <div id="pv_maps" class="link-pill d-none"><i class="bi bi-geo-alt"></i> <span>Location</span></div>
                <div id="pv_brochure" class="link-pill d-none"><i class="bi bi-file-earmark-pdf"></i> <span>Brochure</span></div>
              </div>

              <div id="sec_social" class="mt-3">
                <div id="pv_linkedin" class="link-pill d-none"><i class="bi bi-linkedin"></i> <span>LinkedIn</span></div>
                <div id="pv_instagram" class="link-pill d-none"><i class="bi bi-instagram"></i> <span>Instagram</span></div>
                <div id="pv_facebook" class="link-pill d-none"><i class="bi bi-facebook"></i> <span>Facebook</span></div>
                <div id="pv_twitter" class="link-pill d-none"><i class="bi bi-twitter-x"></i> <span>X (Twitter)</span></div>
              </div>

              <div id="sec_cal" class="mt-3 d-none">
                <a id="pv_calendar" class="btn btn-primary w-100">Book an Appointment</a>
              </div>
            </div> <!-- p-body -->
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<script>
const byId = (id)=>document.getElementById(id);
function setPreview(){
  // Name/role
  byId('pv_name').textContent = byId('emp_name').value || 'Employee Name';
  byId('pv_role').textContent = byId('designation').value || 'Designation';

  // Call / Email / WhatsApp
  const phone = byId('phone').value.trim();
  const email = byId('email').value.trim();
  const wa    = byId('whatsapp').value.trim().replace(/\D/g,'');
  byId('pv_call').href = phone ? 'tel:'+phone : 'javascript:void(0)';
  byId('pv_mail').href = email ? 'mailto:'+email : 'javascript:void(0)';
  byId('pv_whatsapp').href = wa ? 'https://wa.me/'+wa : 'javascript:void(0)';

  // About
  byId('pv_headline').textContent = byId('headline').value || 'About Me';
  byId('pv_about').textContent    = byId('about').value || 'Short profile or company pitch...';

  // Links
  const website = byId('website').value.trim();
  const maps    = byId('maps').value.trim();
  const brochure= byId('brochure').value.trim();
  toggleLink('pv_website', website);
  toggleLink('pv_maps', maps);
  toggleLink('pv_brochure', brochure);

  // Social
  toggleLink('pv_linkedin', byId('linkedin').value.trim());
  toggleLink('pv_instagram', byId('instagram').value.trim());
  toggleLink('pv_facebook', byId('facebook').value.trim());
  toggleLink('pv_twitter', byId('twitter').value.trim());

  // Sections visibility from toggles
  byId('sec_about').style.display  = byId('toggle_about').checked ? '' : 'none';
  byId('sec_social').style.display = byId('toggle_social').checked ? '' : 'none';
  byId('sec_links').style.display  = byId('toggle_links').checked ? '' : 'none';
  byId('sec_cal').style.display    = byId('toggle_cal').checked ? '' : 'none';
}
function toggleLink(id, val){
  const el = byId(id);
  if(!el) return;
  if(val){ el.classList.remove('d-none'); el.onclick=()=>window.open(val,'_blank'); }
  else   { el.classList.add('d-none'); el.onclick=null; }
}
['emp_name','designation','phone','email','whatsapp','headline','about','website','maps','brochure','linkedin','instagram','facebook','twitter','calendar']
.forEach(id=>{ const el=byId(id); if(el){ el.addEventListener('input', setPreview); }});
['toggle_about','toggle_social','toggle_links','toggle_cal'].forEach(id=>{ byId(id).addEventListener('change', setPreview); });

// Photo preview
byId('photo').addEventListener('change', function(){
  if(this.files && this.files[0]){
    const reader = new FileReader();
    reader.onload = e => byId('pv_photo').src = e.target.result;
    reader.readAsDataURL(this.files[0]);
  }
});

// Initialize
setPreview();
</script>
</body>
</html>
