<?php
// C:\xampp\htdocs\digitalcard\dashboard.php
session_start();
include('includes/config.php');


// Ensure user is logged in
if (!isset($_SESSION['company_id'], $_SESSION['company_slug'])) {
  header("Location: index.php");
  exit;
}


$session_company_id   = (int) $_SESSION['company_id'];
$session_company_slug = $_SESSION['company_slug'];

// Fetch theme colors
$theme_query = $conn->query("SELECT theme_color1, theme_color2 FROM companies WHERE id={$session_company_id} LIMIT 1");
$theme = $theme_query->fetch_assoc();
$theme_color1 = $theme['theme_color1'] ?? '#667eea';
$theme_color2 = $theme['theme_color2'] ?? '#764ba2';



$session_company_id   = (int) $_SESSION['company_id'];
$session_company_slug = $_SESSION['company_slug'];

// Company slug from clean URL (/digitalcard/{company})
$requested_company_slug = $_GET['company'] ?? '';

// Security: prevent viewing another company's dashboard by changing the slug manually
if ($requested_company_slug && $requested_company_slug !== $session_company_slug) {
  header("Location: " . APP_BASE . $session_company_slug);
  exit;
}

$company_slug  = $session_company_slug;
$dashboard_url = APP_BASE . $company_slug;

// Fetch company info
$company_res = $conn->query("SELECT id, company_name, company_slug, logo FROM companies WHERE id={$session_company_id} LIMIT 1");
if (!$company_res || $company_res->num_rows === 0) {
  header("Location: logout.php");
  exit;
}
$company = $company_res->fetch_assoc();

// Company logo
$company_logo_web = '';
if (!empty($company['logo'])) {
  $company_logo_web = APP_BASE . ltrim($company['logo'], '/\\');
}

// Fetch employees with emp_code
$employees_res = $conn->query("SELECT id, emp_name, emp_code, emp_slug, designation, photo
                               FROM employees
                               WHERE company_id={$session_company_id}
                               ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title><?= htmlspecialchars(strtoupper($company['company_name'])); ?> • Dashboard</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --primary: #6366f1;
      --primary-dark: #4f46e5;
      --primary-light: #818cf8;
      --secondary: #8b5cf6;
      --success: #10b981;
      --danger: #ef4444;
      --warning: #f59e0b;
      --dark: #1e293b;
      --light: #f8fafc;
      --gray-50: #f9fafb;
      --gray-100: #f3f4f6;
      --gray-200: #e5e7eb;
      --gray-300: #d1d5db;
      --gray-600: #4b5563;
      --gray-700: #374151;
      --gray-900: #111827;
      --card-radius: 20px;
      --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
      --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
      --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
      --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
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

    .brand-wrap {
      display: flex;
      align-items: center;
      gap: 12px;
      text-decoration: none;
      transition: transform 0.3s ease;
    }

    .brand-wrap:hover {
      transform: scale(1.02);
    }

    .brand-wrap img {
      height: 45px;
      width: 45px;
      border-radius: 12px;
      object-fit: cover;
      box-shadow: var(--shadow);
    }

    .brand-wrap span {
      color: var(--gray-900);
      font-weight: 700;
      font-size: 1.1rem;
      letter-spacing: -0.02em;
    }

    .nav-actions .btn {
      border-radius: 12px;
      padding: 8px 16px;
      font-weight: 600;
      transition: all 0.3s ease;
      border: 2px solid transparent;
    }

    .nav-actions .btn-light {
      background: var(--gray-100);
      color: var(--gray-700);
      border-color: var(--gray-200);
    }

    .nav-actions .btn-light:hover {
      background: var(--gray-200);
      transform: translateY(-2px);
      box-shadow: var(--shadow);
    }

    .nav-actions .btn-outline-danger {
      border-color: var(--danger);
      color: var(--danger);
    }

    .nav-actions .btn-outline-danger:hover {
      background: var(--danger);
      color: white;
      transform: translateY(-2px);
      box-shadow: var(--shadow);
    }

    /* Page Header */
    .page-header {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(20px);
      border-radius: 24px;
      padding: 2rem;
      margin: 2rem 0;
      box-shadow: var(--shadow-lg);
      border: 1px solid rgba(255, 255, 255, 0.3);
    }

    .page-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 1.5rem;
      flex-wrap: wrap;
    }

    .page-head h4 {
      font-size: 1.75rem;
      font-weight: 700;
      color: var(--gray-900);
      margin-bottom: 0.5rem;
      letter-spacing: -0.02em;
    }

    .page-head small {
      color: var(--gray-600);
      font-size: 0.95rem;
    }

    .create-btn {
      background: linear-gradient(135deg, <?= $theme_color1 ?> 0%, <?= $theme_color2 ?> 100%);

      border: none;
      border-radius: 16px;
      padding: 14px 28px;
      font-weight: 700;
      color: white;
      box-shadow: 0 8px 20px rgba(99, 102, 241, 0.3);
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      display: inline-flex;
      align-items: center;
      gap: 8px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      font-size: 0.9rem;
    }

    .create-btn:hover {
      transform: translateY(-3px) scale(1.02);
      box-shadow: 0 12px 30px rgba(99, 102, 241, 0.4);
    }

    .create-btn:active {
      transform: translateY(-1px);
    }

    /* Employee Cards */
    .emp-card {
      background: white;
      border: none;
      border-radius: var(--card-radius);
      overflow: hidden;
      box-shadow: var(--shadow);
      transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
      position: relative;
      height: 100%;
    }

    .emp-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: linear-gradient(135deg, rgba(99, 102, 241, 0.05) 0%, rgba(139, 92, 246, 0.05) 100%);
      opacity: 0;
      transition: opacity 0.4s ease;
      z-index: 0;
    }

    .emp-card:hover {
      transform: translateY(-8px) scale(1.02);
      box-shadow: var(--shadow-xl);
    }

    .emp-card:hover::before {
      opacity: 1;
    }

    .emp-card .header {
      background: linear-gradient(135deg, <?= $theme_color1 ?> 0%, <?= $theme_color2 ?> 100%);
      height: 100px;
      position: relative;
      overflow: hidden;
    }

    .emp-card .header::before {
      content: '';
      position: absolute;
      top: -50%;
      left: -50%;
      width: 200%;
      height: 200%;
      background: radial-gradient(circle, rgba(255,255,255,0.2) 0%, transparent 70%);
      animation: shimmer 3s infinite;
    }

    @keyframes shimmer {
      0%, 100% { transform: translate(-25%, -25%); }
      50% { transform: translate(25%, 25%); }
    }

    .emp-card .card-body {
      padding: 1.5rem;
      text-align: center;
      position: relative;
      z-index: 1;
    }

    .emp-photo {
      width: 100px;
      height: 100px;
      border-radius: 50%;
      object-fit: cover;
      border: 5px solid white;
      background: var(--gray-100);
      margin-top: -50px;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
      transition: all 0.3s ease;
      position: relative;
      z-index: 2;
    }

    .emp-card:hover .emp-photo {
      transform: scale(1.1);
      box-shadow: 0 12px 30px rgba(0, 0, 0, 0.2);
    }

    .emp-card h6 {
      font-size: 1.1rem;
      font-weight: 700;
      color: var(--gray-900);
      margin-top: 1rem;
      margin-bottom: 0.25rem;
      letter-spacing: -0.01em;
    }

    .emp-card small {
      color: var(--gray-600);
      font-size: 0.875rem;
      display: block;
      margin-bottom: 1.25rem;
      font-weight: 500;
    }

    /* Action Buttons */
    .emp-actions {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      gap: 0.5rem;
    }

    .emp-actions .btn {
      border-radius: 10px;
      padding: 8px 14px;
      font-weight: 600;
      font-size: 0.85rem;
      transition: all 0.3s ease;
      border-width: 2px;
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }

    .emp-actions .btn-outline-primary {
      border-color: var(--primary);
      color: var(--primary);
    }

    .emp-actions .btn-outline-primary:hover {
      background: var(--primary);
      color: white;
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
    }

    .emp-actions .btn-outline-secondary {
      border-color: var(--gray-300);
      color: var(--gray-700);
    }

    .emp-actions .btn-outline-secondary:hover {
      /* background: var(--gray-700); */
      background:  #fd0;

      color: white;
      border-color: var(--gray-700);
      transform: translateY(-2px);
    }

    .emp-actions .btn-outline-dark {
      border-color: var(--gray-600);
      color: var(--gray-600);
    }

    .emp-actions .btn-outline-dark:hover {
      background: var(--gray-900);
      color: white;
      border-color: var(--gray-900);
      transform: translateY(-2px);
    }

    .emp-actions .btn-outline-danger {
      border-color: var(--danger);
      color: var(--danger);
    }

    .emp-actions .btn-outline-danger:hover {
      background: var(--danger);
      color: white;
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
    }

    /* Empty State */
    .empty {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(20px);
      border-radius: 24px;
      padding: 4rem 2rem;
      text-align: center;
      box-shadow: var(--shadow-lg);
      border: 1px solid rgba(255, 255, 255, 0.3);
      margin: 2rem 0;
    }

    .empty .display-6 {
      font-size: 4rem;
      animation: float 3s ease-in-out infinite;
    }

    @keyframes float {
      0%, 100% { transform: translateY(0); }
      50% { transform: translateY(-10px); }
    }

    .empty h5 {
      font-size: 1.5rem;
      font-weight: 700;
      color: var(--gray-900);
      margin-bottom: 0.75rem;
    }

    .empty p {
      color: var(--gray-600);
      margin-bottom: 1.5rem;
      font-size: 1rem;
    }

    /* Responsive */
    @media (max-width: 768px) {
      .page-header {
        padding: 1.5rem;
        margin: 1.5rem 0;
      }

      .page-head {
        flex-direction: column;
        align-items: flex-start;
      }

      .page-head h4 {
        font-size: 1.5rem;
      }

      .create-btn {
        width: 100%;
        justify-content: center;
      }

      .emp-photo {
        width: 90px;
        height: 90px;
        margin-top: -45px;
      }

      .emp-card .header {
        height: 90px;
      }

      .emp-actions {
        flex-direction: column;
      }

      .emp-actions .btn {
        width: 100%;
      }

      .empty {
        padding: 3rem 1.5rem;
      }
    }

    /* Loading Animation */
    @keyframes fadeInUp {
      from {
        opacity: 0;
        transform: translateY(30px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .emp-card {
      animation: fadeInUp 0.6s ease backwards;
    }

    .emp-card:nth-child(1) { animation-delay: 0.1s; }
    .emp-card:nth-child(2) { animation-delay: 0.2s; }
    .emp-card:nth-child(3) { animation-delay: 0.3s; }
    .emp-card:nth-child(4) { animation-delay: 0.4s; }
    .emp-card:nth-child(5) { animation-delay: 0.5s; }
    .emp-card:nth-child(6) { animation-delay: 0.6s; }

    /* Scrollbar */
    ::-webkit-scrollbar {
      width: 10px;
    }

    ::-webkit-scrollbar-track {
      background: rgba(255, 255, 255, 0.1);
    }

    ::-webkit-scrollbar-thumb {
      background: rgba(255, 255, 255, 0.3);
      border-radius: 10px;
    }

    ::-webkit-scrollbar-thumb:hover {
      background: rgba(255, 255, 255, 0.5);
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




  </style>
</head>
<body>

<div class="content-wrapper">
  <!-- Navbar -->
  <nav class="navbar">
    <div class="container-fluid px-4">
      <a class="brand-wrap" href="<?= APP_BASE . $company_slug; ?>">
        <?php if ($company_logo_web): ?>
          <img src="<?= htmlspecialchars($company_logo_web); ?>" alt="logo">
        <?php endif; ?>
        <span><?= htmlspecialchars(strtoupper($company['company_name'])); ?></span>
      </a>
      <div class="nav-actions d-flex gap-2">
        <a class="btn btn-light btn-sm" href="overview.php?company=<?= htmlspecialchars($company_slug); ?>">
          <i class="bi bi-gear"></i> Settings
        </a>
        <a class="btn btn-outline-danger btn-sm" href="logout.php">
          <i class="bi bi-box-arrow-right"></i> Logout
        </a>
      </div>
    </div>
  </nav>

  <!-- Main Content -->
  <div class="container">
    <div class="page-header">
      <div class="page-head">
        <div>
          <h4>Employee Digital Cards</h4>
          <small>Create and manage employee business cards for <?= htmlspecialchars($company['company_name']); ?>.</small>
        </div>
        <div>
          <a href="create_employee.php?company=<?= htmlspecialchars($company_slug); ?>" class="create-btn">
            <i class="bi bi-plus-lg"></i> Create New Card
          </a>
        </div>
      </div>
    </div>

    <?php if ($employees_res && $employees_res->num_rows > 0): ?>
      <div class="row g-4 mb-5">
        <?php
while($emp = $employees_res->fetch_assoc()):
    $emp_name = trim($emp['emp_name'] ?? 'User');
    $photo_file = $emp['photo'] ?? ''; // value from DB

    // ✅ STEP 1: Build the correct photo URL
    if (!empty($photo_file)) {
        // if DB only has filename like "default.png"
        if (strpos($photo_file, 'uploads/') === false) {
            $photo_web = 'https://effedo.app/vcard/uploads/employees/' . $photo_file;
        } else {
            // if DB already has "uploads/employees/default.png"
            $photo_web = 'https://effedo.app/vcard/' . $photo_file;
        }
    } else {
        // fallback avatar
        $photo_web = 'https://ui-avatars.com/api/?name=' . urlencode($emp_name) . '&background=667eea&color=ffffff&size=200';


        // custom code for color theme

        // Convert theme color (e.g. #667eea) to clean hex (without #)
      $avatar_bg = ltrim($theme_color1, '#');

      // Use dynamic color in fallback avatar
      $photo_web = 'https://ui-avatars.com/api/?name=' . urlencode($emp_name) .
               '&background=' . $avatar_bg . '&color=ffffff&size=200';





    }

    // ✅ STEP 2: Build other URLs as before
    $public_url_abs = APP_URL . $company_slug . '/' . $emp['emp_code'];
    $edit_url = 'edit_employee.php?id=' . (int)$emp['id'] . '&company=' . urlencode($company_slug);
?>





        <div class="col-xl-3 col-lg-4 col-md-6">
          <div class="card emp-card">
            <div class="header"></div>
            <div class="card-body">
              <img class="emp-photo"
                   src="<?= htmlspecialchars($photo_web); ?>"
                   alt="<?= htmlspecialchars($emp_name); ?>"
                   onerror="this.onerror=null;this.src='https://ui-avatars.com/api/?name=<?= urlencode($emp_name) ?>&background=667eea&color=ffffff&size=200';">

              <h6><?= htmlspecialchars($emp['emp_name']); ?></h6>
              <small><?= htmlspecialchars($emp['designation']); ?></small>

              <div class="emp-actions">
                <a href="<?= htmlspecialchars($public_url_abs); ?>" class="btn btn-outline-primary btn-sm" target="_blank" rel="noopener noreferrer">
                  <i class="bi bi-eye"></i> View
                </a>

                <a href="<?= htmlspecialchars($edit_url); ?>" class="btn btn-outline-secondary btn-sm">
                  <i class="bi bi-pencil"></i> Edit
                </a>

                <!-- Copy Button -->
  <button class="btn btn-outline-dark btn-sm" type="button"
    onclick="navigator.clipboard.writeText('<?= htmlspecialchars($public_url_abs); ?>')
             .then(()=>alert('Link copied to clipboard!'))">
    <i class="bi bi-clipboard"></i> Copy
  </button>

  <!-- QR Code Download Button -->
  <button class="save-pill qr-pill" type="button" onclick="downloadQRCodeDashboard('<?= htmlspecialchars($public_url_abs); ?>', '<?= preg_replace('/[^a-zA-Z0-9_-]/', '_', $emp['emp_name']); ?>')">
    <i class="bi bi-qr-code"></i> QR Code
  </button>


                <form action="delete_employee.php" method="POST" class="d-inline" onsubmit="return confirm('Delete this employee card permanently?');">
                  <input type="hidden" name="id" value="<?= (int)$emp['id']; ?>">
                  <input type="hidden" name="company" value="<?= htmlspecialchars($company_slug); ?>">
                  <button type="submit" class="btn btn-outline-danger btn-sm">
                    <i class="bi bi-trash"></i>
                  </button>
                </form>
              </div>
            </div>
          </div>
        </div>
        <?php endwhile; ?>
      </div>
    <?php else: ?>
      <div class="empty">
        <div class="display-6 mb-3">✨</div>
        <h5>No Employee Cards Yet</h5>
        <p>Start by creating your first digital business card for an employee.</p>
        <a href="create_employee.php?company=<?= htmlspecialchars($company_slug); ?>" class="create-btn">
          <i class="bi bi-plus-lg"></i> Create First Card
        </a>
      </div>
    <?php endif; ?>

    <div class="mb-5"></div>
  </div>
</div>












<!-- QR Code Library -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

<script>
function downloadQRCodeDashboard(vcardUrl, empName) {
  const fileName = empName + "-QRCode.png";

  // Create hidden container
  const tempDiv = document.createElement('div');
  tempDiv.style.position = 'absolute';
  tempDiv.style.left = '-9999px';
  document.body.appendChild(tempDiv);

  // Generate QR code for vCard URL
  new QRCode(tempDiv, {
    text: vcardUrl,
    width: 512,
    height: 512,
    colorDark: "#000000",
    colorLight: "#ffffff",
    correctLevel: QRCode.CorrectLevel.H
  });

  // Wait for render, then auto-download
  setTimeout(() => {
    const canvas = tempDiv.querySelector('canvas');
    if (canvas) {
      canvas.toBlob((blob) => {
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = fileName;
        link.click();
        URL.revokeObjectURL(url);
        document.body.removeChild(tempDiv);
      });
    }
  }, 200);
}
</script>

</body>
</html>
