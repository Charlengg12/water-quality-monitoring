<?php 
session_start();
require_once __DIR__ . "/../includes/db.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);

    // Fetch user credentials and actual role from database
    $stmt = $conn->prepare("SELECT user_id, password, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row["password"])) {
            $_SESSION["user_id"] = $row["user_id"];
            $_SESSION["role"] = $row["role"];

            // Redirect based on actual role from database
            switch ($row["role"]) {
                case "super_admin":
                    header("Location: super_admin_dashboard.php");
                    break;
                case "lgu_menro":
                    header("Location: dashboard.php");
                    break;
                case "refilling_station_owner":
                    header("Location: dashboard.php");
                    break;
                case "personal_user":
                default:
                    header("Location: dashboard.php");
                    break;
            }
            exit;
        } else {
            $error = "❌ Invalid password.";
        }
    } else {
        $error = "❌ Invalid email.";
    }

    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login - Water Quality Testing & Monitoring System</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet" />
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
    @keyframes waveMove {
      0% { transform: translateX(0); }
      100% { transform: translateX(-50%); }
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
      font-size: 26px;
    }
    .form-label { color: #ccc; }
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

    /* Custom Dropdown (UI only) */
    .dropdown-container {
      position: relative;
      width: 100%;
      margin-bottom: 1rem;
    }
    .dropdown-selected {
      background: #1e1e1e;
      border: 1px solid #333;
      border-radius: 10px;
      padding: 10px;
      color: #ccc;
      cursor: pointer;
      position: relative;
    }
    .dropdown-selected::after {
      content: '\25BC';
      position: absolute;
      right: 10px;
      color: #aaa;
    }
    .dropdown-options {
      display: none;
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      background: #0d1117;
      border: 1px solid #00bfff;
      border-radius: 10px;
      z-index: 10;
      box-shadow: 0 0 15px rgba(0,191,255,0.3);
    }
    .dropdown-options.show {
      display: block;
    }
    .dropdown-options div {
      padding: 10px;
      color: #fff;
      cursor: pointer;
      transition: background 0.2s;
    }
    .dropdown-options div:hover {
      background: #fff;
      color: #000;
    }

    .btn-login {
      background-color: #00bfff;
      color: #000;
      font-weight: bold;
      border-radius: 10px;
      padding: 10px;
    }
    .btn-login:hover {
      background-color: #00e6ff;
      box-shadow: 0 0 12px #00e6ff;
    }
    .footer-links { text-align: center; margin-top: 15px; }
    .footer-links a { color: #00e6e6; text-decoration: none; margin: 0 8px; }
    .footer-text { text-align: center; margin-top: 10px; font-size: 13px; color: #aaa; }
    .droplet { text-align: center; font-size: 40px; color: #00bfff; margin-bottom: 15px; }

    .password-wrapper {
      position: relative;
    }
    .toggle-password {
      position: absolute;
      top: 70%;
      right: 12px;
      transform: translateY(-40%);
      cursor: pointer;
      color: #aaa;
    }
    .toggle-password:hover { color: #00e6ff; }
  </style>
</head>
<body>
  <div class="waves"></div>

  <div class="login-container">
    <div class="droplet">💧</div>
    <h2>WATER QUALITY TESTING & MONITORING SYSTEM</h2>

    <?php if (!empty($error)): ?>
      <div id="errorAlert" class="alert alert-danger text-center"><?php echo $error; ?></div>
    <?php endif; ?>

    <form action="login.php" method="POST">
      <label class="form-label">Login as</label>
      <div class="dropdown-container">
        <div class="dropdown-selected" id="dropdownSelected">Select Role</div>
        <div class="dropdown-options" id="dropdownOptions">
          <div data-value="Personal Use">Personal Use</div>
          <div data-value="Water Refilling Station Owner">Water Refilling Station Owner</div>
          <div data-value="LGU MENRO">LGU MENRO</div>
          <div data-value="Super Admin">Super Admin</div>
        </div>
        <input type="hidden" name="role" id="roleInput">
      </div>

      <div class="mb-3">
        <label for="username" class="form-label">Email</label>
        <input type="text" class="form-control" id="username" name="username" required>
      </div>

      <div class="mb-3 password-wrapper">
        <label for="password" class="form-label">Password</label>
        <input type="password" class="form-control" id="password" name="password" required>
        <i class="fa-solid fa-eye-slash toggle-password" id="togglePassword"></i>
      </div>

      <button type="submit" class="btn btn-login w-100">Login</button>
    </form>

    <div class="footer-links">
      <a href="forgot_password.php">Forgot Password?</a> |
      <a href="register.php">Register</a>
    </div>
    <div class="footer-text">© 2025 Water Quality Testing & Monitoring System</div>
  </div>

  <script>
    // Password toggle
    const togglePassword = document.getElementById("togglePassword");
    const passwordField = document.getElementById("password");
    togglePassword.addEventListener("click", function () {
      const isPassword = passwordField.type === "password";
      passwordField.type = isPassword ? "text" : "password";
      this.classList.toggle("fa-eye");
      this.classList.toggle("fa-eye-slash");
    });

    // Custom Dropdown Logic (for UI only)
    const dropdownSelected = document.getElementById("dropdownSelected");
    const dropdownOptions = document.getElementById("dropdownOptions");
    const roleInput = document.getElementById("roleInput");

    dropdownSelected.addEventListener("click", () => {
      dropdownOptions.classList.toggle("show");
    });

    dropdownOptions.querySelectorAll("div").forEach(option => {
      option.addEventListener("click", () => {
        const selectedValue = option.getAttribute("data-value");
        dropdownSelected.textContent = selectedValue;
        roleInput.value = selectedValue;
        dropdownOptions.classList.remove("show");
      });
    });

    document.addEventListener("click", (event) => {
      if (!dropdownSelected.contains(event.target) && !dropdownOptions.contains(event.target)) {
        dropdownOptions.classList.remove("show");
      }
    });
  </script>
</body>
</html>
