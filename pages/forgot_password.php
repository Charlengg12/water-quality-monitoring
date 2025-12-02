<?php
include("../includes/db.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];

    $stmt = $conn->prepare("SELECT password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($hashed_password);
        $stmt->fetch();

        echo "<script>alert('Your account exists. Please contact the administrator to reset your password securely.'); window.location.href='index.php';</script>";
    } else {
        echo "<script>alert('No account found with that email.');</script>";
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Forgot Password - Water Quality Testing & Monitoring System</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      margin: 0;
      font-family: 'Segoe UI', sans-serif;
      height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      overflow: hidden;
      background: #001f3f;
      position: relative;
      color: #f0f0f0;
    }

    .waves {
      position: absolute;
      bottom: 0;
      width: 100%;
      height: 150px;
      background: #0077be;
      border-radius: 100% 100% 0 0;
      animation: waveMove 6s ease-in-out infinite alternate;
      opacity: 0.4;
      z-index: 0;
    }

    .waves::before,
    .waves::after {
      content: '';
      position: absolute;
      top: 0;
      width: 100%;
      height: 100%;
      background: inherit;
      border-radius: inherit;
      opacity: 0.6;
    }

    .waves::before {
      animation: waveMove 8s ease-in-out infinite alternate-reverse;
    }

    .waves::after {
      animation: waveMove 10s ease-in-out infinite alternate;
    }

    @keyframes waveMove {
      0% { transform: translateX(0) scaleY(1); }
      100% { transform: translateX(-50%) scaleY(1.05); }
    }

    .login-container {
      background-color: rgba(0, 0, 0, 0.75);
      padding: 40px;
      border-radius: 20px;
      box-shadow: 0 0 30px rgba(0, 191, 255, 0.3);
      width: 100%;
      max-width: 400px;
      z-index: 2;
      position: relative;
    }

    .login-container h2 {
      text-align: center;
      margin-bottom: 30px;
      font-weight: 600;
      color: #00d4ff;
      font-size: 28px;
      letter-spacing: 1px;
    }

    .form-label {
      color: #ccc;
    }

    .form-control {
      border-radius: 10px;
      background-color: #1e1e1e;
      border: 1px solid #333;
      color: #fff;
    }

    .form-control:focus {
      background-color: #1e1e1e;
      color: #fff;
      box-shadow: 0 0 10px #00e6e6;
      border-color: #00e6e6;
    }

    .btn-login {
      background-color: #00bfff;
      color: #000;
      font-weight: bold;
      border-radius: 10px;
      padding: 10px;
      transition: background-color 0.3s ease;
    }

    .btn-login:hover {
      background-color: #00e6ff;
      box-shadow: 0 0 12px #00e6ff;
    }

    .footer-links {
      margin-top: 20px;
      text-align: center;
      font-size: 14px;
    }

    .footer-links a {
      color: #00e6e6;
      text-decoration: none;
      margin: 0 8px;
    }

    .footer-links a:hover {
      text-decoration: underline;
    }

    .footer-text {
      text-align: center;
      margin-top: 20px;
      color: #aaa;
      font-size: 13px;
    }

    .droplet {
      text-align: center;
      margin-bottom: 20px;
      font-size: 40px;
      color: #00bfff;
    }
  </style>
</head>
<body>

  <div class="waves"></div>

  <div class="login-container">
    <div class="droplet">💧</div>
    <h2><span style="font-size: 25px;">WATER QUALITY TESTING & MONITORING SYSTEM</span></h2>
    <form method="POST" action="">
      <div class="mb-3">
        <label for="email" class="form-label">Enter your email address</label>
        <input type="email" class="form-control" name="email" required>
      </div>
      <button class="btn btn-login w-100" type="submit">Submit</button>
    </form>

    <div class="footer-links">
      <a href="login.php">Back to Login</a>
    </div>

    <div class="footer-text">© 2025 Water Quality Testing & Monitoring System</div>
  </div>

</body>
</html>
