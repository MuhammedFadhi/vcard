<?php
ob_start();
include('includes/config.php');
include('includes/functions.php');
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Login & Register | DigitalCard</title>

  <link rel="icon" type="image/png" href="https://sada.effedo.app/themes/default/admin/assets/images/icon.png">
  <link rel="apple-touch-icon" href="https://sada.effedo.app/themes/default/admin/assets/images/icon.png">
  <link rel="shortcut icon" href="https://sada.effedo.app/themes/default/admin/assets/images/icon.png" type="image/png">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root {
      --primary: #6366f1;
      --primary-dark: #4f46e5;
      --primary-light: #818cf8;
      --secondary: #8b5cf6;
      --accent: #4285F4;
      --success: #10b981;
      --danger: #ef4444;
      --gray-50: #f9fafb;
      --gray-100: #f3f4f6;
      --gray-200: #e5e7eb;
      --gray-300: #d1d5db;
      --gray-400: #9ca3af;
      --gray-600: #4b5563;
      --gray-700: #374151;
      --gray-900: #111827;
      --white: #fff;
      --black: #000;
      --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
      --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
      --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
      --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
      --shadow-2xl: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    }

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    }

    body {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      background-attachment: fixed;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
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

    .form {
      position: relative;
      background: var(--white);
      width: 100%;
      max-width: 650px;
      border-radius: 28px;
      box-shadow: var(--shadow-2xl);
      overflow: hidden;
      transition: height 0.3s ease;
      margin: 40px auto;
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

    .form-toggle {
      position: absolute;
      top: 60px;
      right: 60px;
      background: var(--white);
      width: 50px;
      height: 50px;
      border-radius: 50%;
      transform: translate(0,-25%) scale(0);
      opacity: 0;
      cursor: pointer;
      transition: all 0.3s ease;
      z-index: 10;
      box-shadow: var(--shadow-xl);
    }

    .form-toggle:hover {
      transform: translate(0,-25%) scale(1.1);
      box-shadow: 0 8px 30px rgba(0, 0, 0, 0.2);
    }

    .form-toggle.visible {
      transform: translate(0,-25%) scale(1);
      opacity: 1;
    }

    .form-toggle:before,
    .form-toggle:after {
      content: '';
      position: absolute;
      top: 50%;
      left: 50%;
      width: 24px;
      height: 3px;
      background: var(--primary);
      transform: translate(-50%, -50%) rotate(45deg);
      transition: 0.3s;
      border-radius: 10px;
    }

    .form-toggle:after {
      transform: translate(-50%, -50%) rotate(-45deg);
    }

    .form-panel {
      padding: 60px;
      box-sizing: border-box;
      transition: 0.3s ease;
    }

    .form-panel.one {
      z-index: 1;
      position: relative;
      background: var(--white);
    }

    .form-panel.two {
      position: absolute;
      top: 0;
      left: 95%;
      background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
      width: 100%;
      min-height: 100%;
      height: auto;
      color: var(--white);
      cursor: pointer;
      transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
      z-index: 5;
      overflow-y: auto;
      padding: 60px;
      padding-bottom: 90px;
      box-sizing: border-box;
    }

    .form-panel.two::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: radial-gradient(circle at top right, rgba(255,255,255,0.1) 0%, transparent 60%);
      pointer-events: none;
      z-index: 0;
    }

    .form-panel.two .form-header,
    .form-panel.two .form-content {
      position: relative;
      z-index: 1;
    }

    .form-panel.two.active {
      left: 0;
      cursor: default;
    }

    .form-panel.two:not(.active):hover {
      left: 90%;
      box-shadow: -5px 0 20px rgba(0,0,0,0.2);
    }

    .form-panel.two.active:hover {
      left: 0;
      box-shadow: none;
    }

    .form-header {
      margin-bottom: 40px;
      position: relative;
    }

    .form-header h1 {
      margin: 0;
      background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      font-size: 32px;
      font-weight: 800;
      letter-spacing: -0.02em;
    }

    .form-panel.two .form-header h1 {
      background: var(--white);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    .form-header::after {
      content: '';
      display: block;
      width: 60px;
      height: 4px;
      background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
      border-radius: 10px;
      margin-top: 12px;
    }

    .form-panel.two .form-header::after {
      background: rgba(255, 255, 255, 0.6);
    }

    .form-group {
      margin-bottom: 24px;
    }

    label {
      font-size: 13px;
      text-transform: uppercase;
      color: var(--gray-700);
      display: block;
      margin-bottom: 8px;
      font-weight: 700;
      letter-spacing: 0.5px;
    }

    .form-panel.two label {
      color: rgba(255, 255, 255, 0.95);
      text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
    }

    input {
      width: 100%;
      padding: 14px 16px;
      border: 2px solid var(--gray-200);
      border-radius: 14px;
      font-size: 15px;
      transition: all 0.3s ease;
      font-weight: 500;
      color: var(--gray-900);
      background: var(--white);
    }

    input::placeholder {
      color: var(--gray-400);
    }

    .form-panel.two input {
      background: rgba(255, 255, 255, 0.15);
      color: var(--white);
      border: 2px solid rgba(255, 255, 255, 0.3);
      backdrop-filter: blur(10px);
    }

    .form-panel.two input::placeholder {
      color: rgba(255, 255, 255, 0.6);
    }

    input:focus {
      border-color: var(--primary);
      box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
      outline: none;
    }

    .form-panel.two input:focus {
      border-color: rgba(255, 255, 255, 0.8);
      box-shadow: 0 0 0 4px rgba(255, 255, 255, 0.1);
    }

    input[type="file"] {
      padding: 12px;
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
      margin-right: 12px;
      transition: all 0.3s ease;
    }

    input[type="file"]::file-selector-button:hover {
      transform: scale(1.05);
      box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
    }

    .form-panel.two input[type="file"]::file-selector-button {
      background: rgba(255, 255, 255, 0.95);
      color: var(--primary);
    }

    button {
      width: 100%;
      padding: 16px;
      border: none;
      border-radius: 14px;
      background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
      color: #fff;
      text-transform: uppercase;
      font-weight: 700;
      cursor: pointer;
      transition: all 0.3s ease;
      font-size: 15px;
      letter-spacing: 0.5px;
      box-shadow: 0 8px 20px rgba(99, 102, 241, 0.3);
    }

    .form-panel.two button {
      background: var(--white);
      color: var(--primary);
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
    }

    button:hover {
      transform: translateY(-2px);
      box-shadow: 0 12px 30px rgba(99, 102, 241, 0.4);
    }

    .form-panel.two button:hover {
      box-shadow: 0 12px 30px rgba(0, 0, 0, 0.3);
    }

    button:active {
      transform: translateY(0);
    }

    .form-footer {
      text-align: center;
      margin-top: 20px;
      font-size: 14px;
      font-weight: 500;
    }

    .form-footer a {
      color: var(--primary);
      text-decoration: none;
      font-weight: 600;
      transition: all 0.3s ease;
      border-bottom: 2px solid transparent;
    }

    .form-footer a:hover {
      border-bottom-color: var(--primary);
    }

    .form-panel.two .form-footer a {
      color: var(--white);
      border-bottom-color: transparent;
    }

    .form-panel.two .form-footer a:hover {
      border-bottom-color: var(--white);
    }

    .error-message {
      background: rgba(239, 68, 68, 0.1);
      border: 2px solid var(--danger);
      color: var(--danger);
      padding: 12px 16px;
      border-radius: 12px;
      margin-top: 16px;
      font-weight: 600;
      font-size: 14px;
      text-align: center;
      animation: shake 0.5s ease;
    }

    @keyframes shake {
      0%, 100% { transform: translateX(0); }
      25% { transform: translateX(-10px); }
      75% { transform: translateX(10px); }
    }

    .form-panel.two .error-message {
      background: rgba(255, 255, 255, 0.2);
      border-color: rgba(255, 255, 255, 0.5);
      color: var(--white);
    }

    @media (max-width: 768px) {
      .form {
        max-width: 100%;
        border-radius: 20px;
        margin: 20px;
      }

      .form-panel {
        padding: 40px 30px;
      }

      .form-panel.two {
        padding: 40px 30px 80px;
      }

      .form-header h1 {
        font-size: 26px;
      }

      .form-toggle {
        top: 40px;
        right: 30px;
        width: 44px;
        height: 44px;
      }

      .form-toggle:before,
      .form-toggle:after {
        width: 20px;
      }
    }

    @media (max-width: 600px) {
      body {
        padding: 10px;
      }

      .form {
        width: 100%;
        margin: 10px;
        height: auto !important;
      }

      .form-panel {
        padding: 30px 24px;
      }

      .form-panel.two,
      .form-panel.one {
        overflow-y: auto;
        max-height: 90vh;
        padding: 30px 24px 60px;
      }

      .form-panel.two:not(.active) {
        left: 100%;
      }

      .form-panel.two:not(.active):hover {
        left: 100%;
      }

      .form-header h1 {
        font-size: 24px;
      }

      .form-toggle {
        top: 30px;
        right: 24px;
      }

      input {
        padding: 12px 14px;
        font-size: 14px;
      }

      button {
        padding: 14px;
        font-size: 14px;
      }
    }

    @media (max-height: 700px) {
      .form-panel.two,
      .form-panel.one {
        overflow-y: auto;
        max-height: 90vh;
      }
    }

    /* Custom Scrollbar */
    .form-panel::-webkit-scrollbar {
      width: 8px;
    }

    .form-panel::-webkit-scrollbar-track {
      background: var(--gray-100);
    }

    .form-panel::-webkit-scrollbar-thumb {
      background: var(--gray-300);
      border-radius: 10px;
    }

    .form-panel.two::-webkit-scrollbar-track {
      background: rgba(255, 255, 255, 0.1);
    }

    .form-panel.two::-webkit-scrollbar-thumb {
      background: rgba(255, 255, 255, 0.3);
    }
  </style>
</head>
<body>
  <div class="form">
    <div class="form-toggle"></div>

    <!-- LOGIN PANEL -->
    <div class="form-panel one">
    <div class="form-header" style="display:flex; flex-direction:column; align-items:center;">
      <img src="https://effedo.app/vcard/effedo_logo.png" alt="Effedo" style="max-height:55px; width:auto; margin-bottom:40px;">
      <h1>Company Login</h1>
    </div>

      <div class="form-content">
        <form method="POST">
          <div class="form-group">
            <label>Email Address</label>
            <input type="email" name="email" placeholder="Enter your email" required>
          </div>
          <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" placeholder="Enter your password" required>
          </div>
          <div class="form-group">
            <button type="submit" name="login">Log In</button>
          </div>
          <div class="form-footer">
            <a href="#" class="switch-register">Don't have an account? Sign Up</a>
          </div>
        </form>
        <?php
        if(isset($_POST['login'])){
          $email = $_POST['email'];
          $pass = $_POST['password'];
          $result = $conn->query("SELECT * FROM companies WHERE email='$email' LIMIT 1");
          if($result->num_rows>0){
              $row = $result->fetch_assoc();
              if(password_verify($pass,$row['password'])){
                  $_SESSION['company_id']=$row['id'];
                  $_SESSION['company_slug']=$row['company_slug'];
                  header("Location: ".$row['company_slug']);
                  exit;
              } else {
                  echo "<div class='error-message'>Invalid Password</div>";
              }
          } else {
              echo "<div class='error-message'>No Account Found</div>";
          }
        }
        ?>
      </div>
    </div>

    <!-- REGISTER PANEL -->
    <div class="form-panel two">
      <div class="form-header" style="display:flex; flex-direction:column; align-items:center;">
    <img src="https://effedo.app/vcard/effedo_logo.png" alt="Effedo" style="max-height:55px; width:auto; margin-bottom:10px;">
    <h1>Register Company</h1>
  </div>

      <div class="form-content">
        <form method="POST" enctype="multipart/form-data">
          <div class="form-group">
            <label>Company Name</label>
            <input type="text" name="company_name" placeholder="Enter company name" required>
          </div>
          <div class="form-group">
            <label>Company Logo</label>
            <input type="file" name="logo" accept="image/*">
          </div>
          <div class="form-group">
            <label>Your Name</label>
            <input type="text" name="created_by" placeholder="Who created this account">
          </div>
          <div class="form-group">
            <label>Email Address</label>
            <input type="email" name="email" placeholder="Enter your email" required>
          </div>
          <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" placeholder="Create a password" required>
          </div>
          <div class="form-group">
            <button type="submit" name="register">Register</button>
          </div>
          <div class="form-footer">
            <a href="#" class="switch-login">Already have an account? Login</a>
          </div>
        </form>
        <?php
        if(isset($_POST['register'])){
            $company_name = trim($_POST['company_name']);
            $slug = make_slug($company_name, 'company');
            $created_by = $_POST['created_by'];
            $email = $_POST['email'];
            $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

            $check = $conn->prepare("SELECT id FROM companies WHERE company_slug=?");
            $check->bind_param("s", $slug);
            $check->execute();
            $check->store_result();
            if($check->num_rows > 0){ $slug .= '-'.rand(100,999); }
            $check->close();

            $company_dir_fs = UPLOAD_FS . $slug . DIRECTORY_SEPARATOR;
            ensure_dir($company_dir_fs.'logos');
            ensure_dir($company_dir_fs.'employees');

            $logo_rel = null;
            if(!empty($_FILES['logo']['name'])){
                $fname = time().'_'.basename($_FILES['logo']['name']);
                $dest_fs = $company_dir_fs.'logos'.DIRECTORY_SEPARATOR.$fname;
                if(move_uploaded_file($_FILES['logo']['tmp_name'], $dest_fs)){
                    $logo_rel = 'uploads/'.$slug.'/logos/'.$fname;
                }
            }

            $stmt = $conn->prepare("INSERT INTO companies (company_name, company_slug, logo, created_by, email, password)
                                    VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $company_name, $slug, $logo_rel, $created_by, $email, $password);



            

            // if($stmt->execute()){
            //     echo "<script>alert('Company Registered Successfully!'); window.location='index.php';</script>";
            // } else {
            //     echo "<div class='error-message'>Error: ".$stmt->error."</div>";
            // }





            // custom code for auto login into dashboard page

            if ($stmt->execute()) {
    // Get the newly inserted company ID
    $new_company_id = $stmt->insert_id;

    // Create a session for the new user
    $_SESSION['company_id'] = $new_company_id;
    $_SESSION['company_slug'] = $slug;

    // Optional: small PHP alert before redirect
    // echo "<script>alert('Company Registered Successfully!');</script>";

    // Instantly redirect to company dashboard (server-side, no flicker)
    header("Location: $slug");
    exit; // stop further output
} else {
    echo "<div class='error-message'>Error: " . $stmt->error . "</div>";
}
$stmt->close();

            $stmt->close();
        }
        ?>
      </div>
    </div>
  </div>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script>
$(document).ready(function(){
  var $form = $('.form'),
      $panelOne = $('.form-panel.one'),
      $panelTwo = $('.form-panel.two'),
      $toggle = $('.form-toggle');

  function resizeForm() {
    // wait a bit for panel transition to finish (important on mobile)
    setTimeout(function(){
      var $active = $panelTwo.hasClass('active') ? $panelTwo : $panelOne;
      var newHeight = $active.outerHeight(true);

      // Mobile fix — force natural height instead of animation
      if (window.innerWidth <= 600) {
        $form.css({
          'height': 'auto',
          'min-height': newHeight + 'px'
        });
      } else {
        $form.stop().animate({ height: newHeight }, 300);
      }
    }, 200); // wait 0.2s for smoother transition and proper height detection
  }

  $('.switch-register').on('click', function(e){
    e.preventDefault();
    $toggle.addClass('visible');
    $panelOne.addClass('hidden');
    $panelTwo.addClass('active');
    resizeForm();
  });

  $('.switch-login').on('click', function(e){
    e.preventDefault();
    $toggle.removeClass('visible');
    $panelOne.removeClass('hidden');
    $panelTwo.removeClass('active');
    resizeForm();
  });

  $toggle.on('click', function(e){
    e.preventDefault();
    $(this).toggleClass('visible');
    $panelOne.toggleClass('hidden');
    $panelTwo.toggleClass('active');
    resizeForm();
  });

  // run once on load
  resizeForm();

  // re-adjust when viewport size changes or keyboard opens
  $(window).on('resize orientationchange', resizeForm);
});
</script>

</body>
</html>
<?php ob_end_flush(); ?>
