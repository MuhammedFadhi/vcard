<?php
include('includes/config.php');
include('includes/functions.php');
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Register | DigitalCard</title>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --accent: #4285F4;
      --gray: rgba(0, 0, 0, 0.6);
      --light-gray: rgba(0, 0, 0, 0.1);
    }
    * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Roboto', sans-serif; }
    body {
      background: linear-gradient(45deg, rgba(66,183,245,0.8) 0%, rgba(66,245,189,0.4) 100%);
      min-height: 100vh; display: flex; align-items: center; justify-content: center;
    }
    .form-container {
      background: #fff;
      width: 100%; max-width: 450px;
      border-radius: 8px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.1);
      padding: 40px 30px;
      transition: 0.3s ease;
    }
    .form-container:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(0,0,0,0.1); }
    h3 {
      color: var(--accent);
      text-align: center;
      margin-bottom: 25px;
      font-weight: 700;
      letter-spacing: .5px;
    }
    .form-group { margin-bottom: 18px; }
    label {
      font-size: 13px;
      text-transform: uppercase;
      color: var(--gray);
      display: block;
      margin-bottom: 6px;
      font-weight: 500;
    }
    input {
      width: 100%;
      padding: 12px 14px;
      border: 1px solid var(--light-gray);
      border-radius: 4px;
      font-size: 14px;
      transition: border 0.3s ease, box-shadow 0.3s ease;
    }
    input:focus {
      border-color: var(--accent);
      box-shadow: 0 0 0 2px rgba(66,133,244,0.2);
      outline: none;
    }
    .btn {
      width: 100%;
      background: var(--accent);
      color: #fff;
      border: none;
      border-radius: 4px;
      padding: 12px;
      font-size: 15px;
      font-weight: 500;
      cursor: pointer;
      transition: 0.3s ease;
      text-transform: uppercase;
    }
    .btn:hover { background: #2c6be0; }
    p {
      text-align: center;
      font-size: 13px;
      color: var(--gray);
      margin-top: 15px;
    }
    a { color: var(--accent); text-decoration: none; }
    a:hover { text-decoration: underline; }
    .error-msg {
      text-align: center;
      color: #d9534f;
      font-size: 14px;
      margin-top: 10px;
    }
    @media(max-width: 480px) {
      .form-container { padding: 30px 20px; width: 90%; }
    }
  </style>
</head>
<body>
  <div class="form-container">
    <h3>Company Sign Up</h3>
    <form action="" method="POST" enctype="multipart/form-data">
      <div class="form-group">
        <label>Company Name</label>
        <input type="text" name="company_name" required>
      </div>
      <div class="form-group">
        <label>Company Logo</label>
        <input type="file" name="logo" accept="image/*">
      </div>
      <div class="form-group">
        <label>Your Name (Who Created)</label>
        <input type="text" name="created_by" required>
      </div>
      <div class="form-group">
        <label>Email Address</label>
        <input type="email" name="email" required>
      </div>
      <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" required>
      </div>
      <button type="submit" name="register" class="btn">Register</button>
      <p>Already registered? <a href="index.php">Login</a></p>
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

        if($stmt->execute()){
            echo "<script>alert('Company Registered Successfully!'); window.location='index.php';</script>";
        } else {
            echo "<div class='error-msg'>Error: ".$stmt->error."</div>";
        }
        $stmt->close();
    }
    ?>
  </div>
</body>
</html>
