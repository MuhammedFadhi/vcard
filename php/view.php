<?php
// C:\xampp\htdocs\digitalcard\view.php
include('includes/config.php');

if (!function_exists('h')) {
  function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
}




/* ---- Slugs / Params ---- */
$company_slug  = $_GET['company']  ?? '';
$employee_code = $_GET['employee'] ?? '';

if ($company_slug === '' || $employee_code === '') {
  http_response_code(404);
  exit('Not found');
}

/* ---- Company ---- */
// $cstmt = $conn->prepare("SELECT id, company_name, company_slug, logo FROM companies WHERE company_slug=? LIMIT 1");
// $cstmt->bind_param("s", $company_slug);
// $cstmt->execute();
// $company = $cstmt->get_result()->fetch_assoc();
// $cstmt->close();




// custom code for server issue


$cstmt = $conn->prepare("SELECT id, company_name, company_slug, logo FROM companies WHERE company_slug=? LIMIT 1");
$cstmt->bind_param("s", $company_slug);
$cstmt->execute();

/* ---- FIX for servers without get_result() ---- */
$meta = $cstmt->result_metadata();
$fields = [];
$row = [];

// Bind all columns dynamically
while ($field = $meta->fetch_field()) {
    $fields[] = &$row[$field->name];
}
call_user_func_array([$cstmt, 'bind_result'], $fields);

// Fetch row
$cstmt->fetch();
$company = $row;

$cstmt->close();







if(!$company){
  http_response_code(404);
  exit('Company not found');
}

$company_id = $company['id']; // ✅ define it here
/* ---- Fetch theme colors ---- */
$theme_query = $conn->query("SELECT theme_color1, theme_color2 FROM companies WHERE id={$company_id} LIMIT 1");

if ($theme_query && $theme_query->num_rows > 0) {
    $theme = $theme_query->fetch_assoc();
} else {
    $theme = ['theme_color1' => '#667eea', 'theme_color2' => '#764ba2'];
}

$theme_color1 = $theme['theme_color1'];
$theme_color2 = $theme['theme_color2'];

/* ---- Employee ---- */
if (!ctype_digit($employee_code)) {
    $redir = $conn->prepare("SELECT emp_code FROM employees WHERE company_id=? AND emp_slug=? LIMIT 1");
    $redir->bind_param("is", $company['id'], $employee_code);
    $redir->execute();
    if ($r = $redir->get_result()->fetch_assoc()) {
        header("Location: " . APP_BASE . $company_slug . '/' . $r['emp_code'], true, 301);
        exit;
    }
    $redir->close();
    http_response_code(404);
    exit('Employee not found');
}

// $estmt = $conn->prepare("SELECT id, emp_name, emp_slug, emp_code, designation, phone, email, photo, card_data
//                          FROM employees WHERE company_id=? AND emp_code=? LIMIT 1");
// $estmt->bind_param("is", $company['id'], $employee_code);
// $estmt->execute();
// $emp = $estmt->get_result()->fetch_assoc();
// $estmt->close();



// custom code for server issue

$estmt = $conn->prepare("SELECT id, emp_name, emp_slug, emp_code, designation, phone,
                         email, photo, card_data
                         FROM employees
                         WHERE company_id=? AND emp_code=? LIMIT 1");

$estmt->bind_param("is", $company['id'], $employee_code);
$estmt->execute();

// Store result to avoid memory fragmentation
$estmt->store_result();

// Bind variables
$estmt->bind_result(
    $emp_id,
    $emp_name,
    $emp_slug,
    $emp_code,
    $designation,
    $phone,
    $email,
    $photo,
    $card_data
);

$estmt->fetch();

$emp = [
    "id" => $emp_id,
    "emp_name" => $emp_name,
    "emp_slug" => $emp_slug,
    "emp_code" => $emp_code,
    "designation" => $designation,
    "phone" => $phone,
    "email" => $email,
    "photo" => $photo,
    "card_data" => $card_data
];

$estmt->close();

















if(!$emp){
  http_response_code(404);
  exit('Employee not found');
}

/* ---- Card JSON ---- */
$card    = $emp['card_data'] ? json_decode($emp['card_data'], true) : [];
$contact = $card['contact']  ?? [];
$about   = $card['about']    ?? [];
$social  = $card['social']   ?? [];
$links   = $card['links']    ?? [];
$calendar= $card['calendar'] ?? '';

/* ---- Image URLs ---- */
$logo_web  = !empty($company['logo']) ? APP_BASE . ltrim($company['logo'], '/\\') : '';
$emp_name = trim($emp['emp_name'] ?? 'User');
$photo_rel = !empty($emp['photo']) ? $emp['photo'] : '';
$photo_path = $_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($photo_rel, '/\\');

if (!empty($photo_rel) && file_exists($photo_path)) {
    $photo_web = APP_BASE . ltrim($photo_rel, '/\\');
} else {
    $photo_web = 'https://ui-avatars.com/api/?' . http_build_query([
        'name' => $emp_name,
        'background' => '667eea',
        'color' => 'ffffff',
        'size' => 230,
        'bold' => true,
        'rounded' => true
    ]);
}

/* ---- Quick actions ---- */
$tel_href = !empty($contact['phone'])    ? 'tel:'.preg_replace('/\s+/', '', $contact['phone']) : '';
$mailto   = !empty($contact['email'])    ? 'mailto:'.$contact['email'] : '';
$wa_num   = !empty($contact['whatsapp']) ? preg_replace('/\D+/', '', $contact['whatsapp']) : '';
$wa_href  = $wa_num ? ('https://wa.me/'.$wa_num) : '';

$dashboard_url = APP_BASE . $company_slug;
$vcf_url       = APP_BASE.'generate_vcf.php?eid='.(int)$emp['id'];

// Current page URL for QR code
$current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= h($emp['emp_name']); ?> | <?= h($company['company_name']); ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1" />
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
  :root {
    --primary: #6366f1;
    --primary-dark: #4f46e5;
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
    --gray-800: #1f2937;
    --gray-900: #111827;
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
    background: linear-gradient(135deg, <?= $theme_color1 ?> 0%, <?= $theme_color2 ?> 100%);
    background-attachment: fixed;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    min-height: 100vh;
    padding: 20px;
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

  .back-btn {
    position: fixed;
    top: 20px;
    left: 20px;
    z-index: 1000;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 12px;
    padding: 10px 18px;
    font-weight: 600;
    color: var(--gray-900);
    text-decoration: none;
    box-shadow: var(--shadow-lg);
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .back-btn:hover {
    background: white;
    transform: translateX(-4px);
    box-shadow: var(--shadow-xl);
    color: var(--primary);
  }

  .card-shell {
    max-width: 500px;
    margin: 80px auto 40px;
    background: white;
    border-radius: 32px;
    box-shadow: var(--shadow-2xl);
    overflow: hidden;
    position: relative;
    z-index: 1;
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

  .header {
    background: linear-gradient(135deg, <?= $theme_color1 ?> 0%, <?= $theme_color2 ?> 100%);
    padding: 24px;
    position: relative;
    overflow: hidden;
  }

  .header::before {
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

  .brand {
    display: flex;
    align-items: center;
    gap: 12px;
    position: relative;
    z-index: 2;
  }

  .brand img {
    height: 42px;
    width: 42px;
    border-radius: 10px;
    object-fit: cover;
    background: white;
    padding: 4px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  }

  .brand strong {
    color: white;
    font-size: 1.1rem;
    font-weight: 700;
    text-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
    letter-spacing: -0.01em;
  }

  .inner {
    padding: 28px;
    text-align: center;
    padding-top: 80px;
  }

  .avatar {
    width: 130px;
    height: 130px;
    border-radius: 50%;
    object-fit: cover;
    border: 6px solid white;
    background: var(--gray-100);
    display: block;
    margin: -75px auto 0;
    box-shadow: 0 12px 30px rgba(0, 0, 0, 0.2);
    transition: transform 0.3s ease;
    position: relative;
    z-index: 10;
  }

  .card-shell:hover .avatar {
    transform: scale(1.05);
  }

  .name {
    font-size: 1.75rem;
    font-weight: 800;
    margin-top: 20px;
    color: var(--gray-900);
    letter-spacing: -0.02em;
  }

  .role {
    font-size: 1rem;
    color: var(--gray-600);
    margin-top: 4px;
    font-weight: 500;
  }

  .quick {
    display: flex;
    gap: 10px;
    justify-content: center;
    flex-wrap: wrap;
    margin-top: 24px;
  }

  .quick .btn {
    border-radius: 14px;
    font-weight: 600;
    padding: 10px 18px;
    transition: all 0.3s ease;
    border-width: 2px;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 6px;
  }

  .quick .btn-outline-primary {
  position: relative;
  display: inline-block;
  border: 2px solid transparent;
  border-radius: 8px;
  color: <?= $theme_color1 ?>;
  background:
    linear-gradient(#fff, #fff) padding-box,
    linear-gradient(135deg, <?= $theme_color1 ?>, <?= $theme_color2 ?>) border-box;
  background-clip: padding-box, border-box;
  background-origin: border-box;
  transition: all 0.3s ease;
}

  .quick .btn-outline-primary:hover {
    background: linear-gradient(135deg, <?= $theme_color1 ?> 0%, <?= $theme_color2 ?> 100%);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(99, 102, 241, 0.3);
  }

  .quick .btn-primary {
    background: linear-gradient(135deg, <?= $theme_color1 ?> 0%, <?= $theme_color2 ?> 100%);
    border: none;
    color: white;
    box-shadow: 0 8px 20px rgba(99, 102, 241, 0.3);
  }

  .quick .btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 30px rgba(99, 102, 241, 0.4);
  }

  .sections {
    padding: 0 20px 20px;
  }

  .section {
    border: 2px solid var(--gray-100);
    border-radius: 20px;
    overflow: hidden;
    margin-top: 16px;
    background: white;
    transition: all 0.3s ease;
  }

  .section:hover {
    border-color: var(--gray-200);
    box-shadow: var(--shadow-lg);
    transform: translateY(-2px);
  }

  /* .section .head {
    background: linear-gradient(135deg, #f0f4ff 0%, #e8f0fe 100%);
    color: var(--primary);
    padding: 14px 16px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 1rem;
    border-bottom: 2px solid var(--gray-100);
  } */


  /* custom code for color theme */


  .section .head {
    background: linear-gradient(135deg, #f0f4ff 0%, #e8f0fe 100%);
    background-clip: padding-box; /* keeps the light bg */
    padding: 14px 16px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 1rem;
    border-bottom: 2px solid var(--gray-100);
  }

  /* Gradient text inside the header */
  .section .head h1,
  .section .head h2,
  .section .head span,
  .section .head .title {
    background: linear-gradient(135deg, <?= $theme_color1 ?>, <?= $theme_color2 ?>);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    color: transparent;
  }

















  .section .head i {
    font-size: 1.1rem;
  }

  .download-pills {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
  }

  .save-pill {
    background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
    color: white;
    border-radius: 999px;
    padding: 8px 14px;
    font-weight: 600;
    font-size: 0.8rem;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 6px;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
    cursor: pointer;
    border: none;
  }

  .save-pill:hover {
    transform: scale(1.05);
    box-shadow: 0 6px 16px rgba(99, 102, 241, 0.4);
    color: white;
  }

  .save-pill.qr-pill {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
  }

  .save-pill.qr-pill:hover {
    box-shadow: 0 6px 16px rgba(16, 185, 129, 0.4);
  }

  .rowline {
    display: flex;
    gap: 16px;
    padding: 14px 16px;
    border-top: 1px solid var(--gray-100);
    transition: background 0.2s ease;
  }

  .rowline:first-child {
    border-top: none;
  }

  .rowline:hover {
    background: var(--gray-50);
  }

  .label {
    font-size: 0.85rem;
    /* color: var(--gray-600); */
      color: darkgrey;
    font-weight: 600;
    min-width: 90px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }

  /* .value {
    font-size: 0.95rem;
    color: var(--gray-900);
    font-weight: 500;
    flex: 1;
  }

  .value a {
    text-decoration: none;
    color: var(--primary);
    transition: color 0.2s ease;
  }

  .value a:hover {
    color: var(--primary-dark);
    text-decoration: underline;
  } */








  /* custom code for color theme */
  .value {
    font-size: 0.95rem;
    /* color: var(--gray-900); */
    color: black;
    font-weight: 500;
    flex: 1;
  }

  .value a {
    text-decoration: none;
    /* background: linear-gradient(135deg, <?= $theme_color1 ?>, <?= $theme_color2 ?>); */
    background: black;

    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    color: transparent; /* fallback */
    transition: all 0.3s ease;
  }

  .value a:hover {
    background: linear-gradient(135deg, <?= $theme_color2 ?>, <?= $theme_color1 ?>);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    text-decoration: underline;
    opacity: 0.9;
  }

















  .link-item {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 16px;
    border-top: 1px solid var(--gray-100);
    text-decoration: none;
    color: var(--gray-900);
    transition: all 0.3s ease;
    font-weight: 500;
  }

  .link-item:first-child {
    border-top: none;
  }

  .link-item:hover {
    background: linear-gradient(135deg, #f0f4ff 0%, #e8f0fe 100%);
    padding-left: 20px;
  }

  .link-item i {
    font-size: 1.4rem;
    min-width: 24px;
  }

  .footer {
    text-align: center;
    color: var(--gray-600);
    font-size: 0.85rem;
    padding: 20px;
    background: var(--gray-50);
    border-top: 2px solid var(--gray-100);
    font-weight: 500;
  }

  .footer a {
    text-decoration: none;
    color: var(--primary);
    font-weight: 600;
    transition: color 0.2s ease;
  }

  .footer a:hover {
    color: var(--primary-dark);
  }

  /* Responsive */
  @media (max-width: 768px) {
    body {
      padding: 10px;
    }

    .back-btn {
      top: 10px;
      left: 10px;
      padding: 8px 14px;
      font-size: 0.9rem;
    }

    .card-shell {
      margin: 60px auto 20px;
      border-radius: 24px;
    }

    .header {
      padding: 20px;
    }

    .inner {
      padding: 20px;
    }

    .avatar {
      width: 110px;
      height: 110px;
      margin: 0 auto;
    }

    .name {
      font-size: 1.5rem;
    }

    .role {
      font-size: 0.9rem;
    }

    .quick {
      flex-direction: column;
    }

    .quick .btn {
      width: 100%;
      justify-content: center;
    }

    .sections {
      padding: 0 12px 12px;
    }

    .section {
      margin-top: 12px;
      border-radius: 16px;
    }

    .label {
      min-width: 70px;
      font-size: 0.75rem;
    }

    .value {
      font-size: 0.9rem;
    }

    .download-pills {
      flex-direction: column;
    }
  }

  /* Loading animation for sections */
  .section {
    animation: slideIn 0.5s ease backwards;
  }

  .section:nth-child(1) { animation-delay: 0.1s; }
  .section:nth-child(2) { animation-delay: 0.2s; }
  .section:nth-child(3) { animation-delay: 0.3s; }
  .section:nth-child(4) { animation-delay: 0.4s; }

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

<a href="<?= h($dashboard_url); ?>" class="back-btn" style="display:none;">
  <i class="bi bi-arrow-left"></i> Back
</a>

<div class="card-shell">
  <div class="header">
    <div class="brand">
      <?php if($logo_web): ?><img src="<?= h($logo_web); ?>" alt="logo"><?php endif; ?>
      <strong><?= h($company['company_name']); ?></strong>
    </div>
  </div>
  <?php
  $photo_file = $emp['photo'] ?? ''; // get filename from DB
  $emp_name   = trim($emp['emp_name'] ?? 'User');

  if (!empty($photo_file)) {
      if (strpos($photo_file, 'uploads/') === false) {
          $photo_web = 'https://effedo.app/vcard/uploads/employees/' . $photo_file;
      } else {
          $photo_web = 'https://effedo.app/vcard/' . $photo_file;
      }
  } else {
      // $photo_web = 'https://ui-avatars.com/api/?name=' . urlencode($emp_name) . '&background=667eea&color=ffffff&size=200';


      // custom code for color theme

      // Convert theme color (e.g. #667eea) to clean hex (without #)
$avatar_bg = ltrim($theme_color1, '#');

// Use dynamic color in fallback avatar
$photo_web = 'https://ui-avatars.com/api/?name=' . urlencode($emp_name) .
             '&background=' . $avatar_bg . '&color=ffffff&size=200';

  }
  ?>

  <div class="inner">
    <img src="<?= h($photo_web); ?>" class="avatar" alt="<?= h($emp_name); ?>">
    <div class="name"><?= h($emp['emp_name']); ?></div>
    <div class="role"><?= h($emp['designation']); ?></div>
    <div class="quick">
      <?php if($tel_href): ?>
        <a class="btn btn-outline-primary btn-sm" href="<?= h($tel_href); ?>">
          <i class="bi bi-telephone-fill"></i> Call
        </a>
      <?php endif; ?>
      <?php if($mailto): ?>
        <a class="btn btn-outline-primary btn-sm" href="<?= h($mailto); ?>">
          <i class="bi bi-envelope-fill"></i> Email
        </a>
      <?php endif; ?>
      <?php if($wa_href): ?>
        <a class="btn btn-outline-primary btn-sm" target="_blank" href="<?= h($wa_href); ?>">
          <i class="bi bi-whatsapp"></i> WhatsApp
        </a>
      <?php endif; ?>
      <a class="btn btn-primary btn-sm" href="<?= h($vcf_url); ?>">
        <i class="bi bi-person-plus-fill"></i> Save Contact
      </a>
    </div>
  </div>

  <div class="sections">
    <div class="section">
      <div class="head">
        <span><i class="bi bi-person-lines-fill me-2"></i>Contact Information</span>
        <div class="download-pills">
          <!-- <a class="save-pill" href="<?= h($vcf_url); ?>">
            <i class="bi bi-download"></i> Download
          </a> -->
          <button class="save-pill qr-pill" onclick="downloadQRCode()">
            <i class="bi bi-qr-code"></i> QR Code
          </button>
        </div>
      </div>
      <div>
        <div class="rowline">
          <div class="label">Name</div>
          <div class="value"><?= h($emp['emp_name']); ?></div>
        </div>
        <?php if($contact['phone'] ?? ''): ?>
        <div class="rowline">
          <div class="label">Mobile</div>
          <div class="value"><a href="<?= h($tel_href); ?>"><?= h($contact['phone']); ?></a></div>
        </div>
        <?php endif; ?>
        <?php if($contact['whatsapp'] ?? ''): ?>
        <div class="rowline">
          <div class="label">WhatsApp</div>
          <div class="value"><a target="_blank" href="<?= h($wa_href); ?>"><?= h($contact['whatsapp']); ?></a></div>
        </div>
        <?php endif; ?>
        <?php if($contact['email'] ?? ''): ?>
        <div class="rowline">
          <div class="label">Email</div>
          <div class="value"><a href="<?= h($mailto); ?>"><?= h($contact['email']); ?></a></div>
        </div>
        <?php endif; ?>
        <div class="rowline">
          <div class="label">Company</div>
          <div class="value"><?= h($company['company_name']); ?></div>
        </div>
      </div>
    </div>

    <?php if(($links['website'] ?? '') || ($links['brochure'] ?? '')): ?>
    <div class="section">
      <div class="head">
        <span><i class="bi bi-link-45deg me-2"></i>Web Links</span>
      </div>
      <div>
        <?php if($links['website'] ?? ''): ?>
          <a class="link-item" target="_blank" href="<?= h($links['website']); ?>">
            <i class="bi bi-globe2 text-primary"></i>
            <span>Visit Website</span>
          </a>
        <?php endif; ?>
        <?php if($links['brochure'] ?? ''): ?>
          <a class="link-item" target="_blank" href="<?= h($links['brochure']); ?>">
            <i class="bi bi-file-earmark-pdf text-danger"></i>
            <span>View Brochure</span>
          </a>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

    <?php if(($social['facebook'] ?? '') || ($social['linkedin'] ?? '') || ($social['instagram'] ?? '') || ($social['twitter'] ?? '')): ?>
    <div class="section">
      <div class="head">
        <span><i class="bi bi-share me-2"></i>Social Media</span>
      </div>
      <div>
        <?php if($social['facebook'] ?? ''): ?>
          <a class="link-item" target="_blank" href="<?= h($social['facebook']); ?>">
            <i class="bi bi-facebook" style="color: #1877f2;"></i>
            <span>Facebook</span>
          </a>
        <?php endif; ?>
        <?php if($social['linkedin'] ?? ''): ?>
          <a class="link-item" target="_blank" href="<?= h($social['linkedin']); ?>">
            <i class="bi bi-linkedin" style="color: #0a66c2;"></i>
            <span>LinkedIn</span>
          </a>
        <?php endif; ?>
        <?php if($social['instagram'] ?? ''): ?>
          <a class="link-item" target="_blank" href="<?= h($social['instagram']); ?>">
            <i class="bi bi-instagram" style="color: #e4405f;"></i>
            <span>Instagram</span>
          </a>
        <?php endif; ?>
        <?php if($social['twitter'] ?? ''): ?>
          <a class="link-item" target="_blank" href="<?= h($social['twitter']); ?>">
            <i class="bi bi-twitter-x" style="color: #000000;"></i>
            <span>X (Twitter)</span>
          </a>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <div class="footer">
  <!-- Get your own   <a href="<?= h($dashboard_url); ?>"> vCard </a> for free!<br> -->
     Powered by <a href="https://effedo.com">effedo.com</a>
  </div>
</div>

<!-- QR Code Library -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

<script>
function downloadQRCode() {
  // URL to encode in QR code (current page URL or VCF URL)
  const qrUrl = "<?= h($current_url); ?>";
  const fileName = "<?= h(preg_replace('/[^a-zA-Z0-9_-]/', '_', $emp['emp_name'])); ?>-QRCode.png";

  // Create temporary container
  const tempDiv = document.createElement('div');
  tempDiv.style.position = 'absolute';
  tempDiv.style.left = '-9999px';
  document.body.appendChild(tempDiv);

  // Generate QR code
  new QRCode(tempDiv, {
    text: qrUrl,
    width: 512,
    height: 512,
    colorDark: "#000000",
    colorLight: "#ffffff",
    correctLevel: QRCode.CorrectLevel.H
  });

  // Wait for QR code to render
  setTimeout(() => {
    const canvas = tempDiv.querySelector('canvas');
    if (canvas) {
      // Convert to blob and download
      canvas.toBlob((blob) => {
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = fileName;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
        document.body.removeChild(tempDiv);
      });
    }
  }, 100);
}
</script>

</body>
</html>
