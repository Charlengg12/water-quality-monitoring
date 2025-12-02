<?php  
include("../includes/db.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_type = $_POST['user_type'] ?? '';
    $device_id = $_POST['device_id'] ?? '';
    $fullname = $_POST['fullname'] ?? '';
    $email = $_POST['email'] ?? '';
    $number = $_POST['number'] ?? '';
    $address = $_POST['address'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Server-side password checks
    if (mb_strlen($password) < 8) {
        echo "<script>alert('Password must be at least 8 characters long!'); window.history.back();</script>";
        exit;
    }
    if ($password !== $confirm_password) {
        echo "<script>alert('Passwords do not match!'); window.history.back();</script>";
        exit;
    }

    // Convert user_type → role for DB
    if ($user_type === "owner") {
        $role = "refilling_station_owner";
    } else {
        $role = "personal_user";
    }

    // Validate Device ID using refilling_stations
    if (in_array($user_type, ['personal', 'owner'])) {
        $checkDevice = $conn->prepare("SELECT station_id FROM refilling_stations WHERE device_sensor_id = ?");
        $checkDevice->bind_param("s", $device_id);
        $checkDevice->execute();
        $checkDevice->store_result();

        if ($checkDevice->num_rows == 0) {
            echo "<script>alert('Registration unsuccessful: Device ID not found in refilling stations!'); window.history.back();</script>";
            exit;
        }
        $checkDevice->close();
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // INSERT without device_id (removed)
    $stmt = $conn->prepare("
        INSERT INTO users (fullname, email, number, address, password, role)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("ssssss", $fullname, $email, $number, $address, $hashed_password, $role);

    if ($stmt->execute()) {
        echo "<script>alert('Registration successful!'); window.location.href='login.php';</script>";
    } else {
        echo "<script>alert('Error during registration.');</script>";
    }

    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register - Water Quality Monitoring System</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    body {
      background: #001f3f;
      color: #f0f0f0;
      font-family: 'Segoe UI', sans-serif;
      height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      overflow: hidden;
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

    .scroll-container {
      width: 100%;
      max-width: 420px;
      max-height: 90vh;
      overflow-y: auto;
      background-color: rgba(0, 0, 0, 0.75);
      border-radius: 20px;
      box-shadow: 0 0 30px rgba(0, 191, 255, 0.3);
      padding: 35px;
      position: relative;
      z-index: 2;
    }
    .scroll-container::-webkit-scrollbar { width: 6px; }
    .scroll-container::-webkit-scrollbar-thumb {
      background-color: #00d4ff;
      border-radius: 10px;
    }

    h2 {
      text-align: center;
      color: #00d4ff;
      margin-bottom: 25px;
      font-weight: 600;
      font-size: 22px;
    }
    .droplet {
      text-align: center;
      font-size: 40px;
      color: #00bfff;
      margin-bottom: 10px;
    }

    .form-label { color: #ccc; }
    .form-control {
      background-color: #1e1e1e;
      border: 1px solid #333;
      color: #fff;
      border-radius: 10px;
    }
    .form-control:focus {
      border-color: #00e6e6;
      box-shadow: 0 0 10px #00e6e6;
    }

    .btn-login {
      background-color: #00bfff;
      color: #000;
      font-weight: bold;
      border-radius: 10px;
      padding: 10px;
      transition: 0.3s;
    }
    .btn-login:hover {
      background-color: #00e6ff;
      box-shadow: 0 0 12px #00e6ff;
    }

    .user-type {
      background-color: #1e1e1e;
      border: 1px solid #333;
      border-radius: 10px;
      padding: 15px;
      margin-bottom: 15px;
    }
    .user-type label {
      display: flex;
      align-items: center;
      gap: 10px;
      cursor: pointer;
      color: #ddd;
      margin-bottom: 8px;
      transition: 0.3s;
    }
    .user-type input[type="checkbox"] {
      appearance: none;
      width: 20px;
      height: 20px;
      border: 2px solid #00bfff;
      border-radius: 5px;
      cursor: pointer;
      position: relative;
    }
    .user-type input[type="checkbox"]:checked {
      background: #00e6ff;
      box-shadow: 0 0 8px #00e6ff;
    }
    .user-type input[type="checkbox"]:checked::after {
      content: "✔";
      position: absolute;
      color: #001f3f;
      font-weight: bold;
      font-size: 14px;
      left: 3px;
      top: -2px;
    }

    .input-group-text.pw-toggle {
      background: transparent;
      border: 1px solid #333;
      border-left: none;
      color: #ccc;
      cursor: pointer;
      transition: 0.3s;
    }
    .input-group-text.pw-toggle:hover {
      color: #00e6ff;
    }
    .input-group .form-control { border-right: none; }

    .password-help { font-size: 0.9rem; color: #ffcccb; margin-top: 5px; }

    .strength-indicator,
    .match-indicator {
      margin-top: 5px;
      font-size: 0.9rem;
    }
    .strength-weak { color: #ff4d4f; }
    .strength-medium { color: #fa8c16; }
    .strength-strong { color: #52c41a; }

    .footer-links { text-align: center; margin-top: 20px; }
    .footer-links a { color: #00e6e6; text-decoration: none; }
  </style>
</head>
<body>
  <div class="waves"></div>
  <div class="scroll-container">
    <div class="droplet">💧</div>
    <h2>WATER QUALITY TESTING & MONITORING SYSTEM</h2>

    <form method="POST" action="">
      <div class="user-type">
        <label class="form-label mb-2 d-block">Register As:</label>
        <label><input type="checkbox" name="user_type" id="personal" value="personal"> Personal Use</label>
        <label><input type="checkbox" name="user_type" id="owner" value="owner"> Water Refilling Station Owner</label>
      </div>

      <div class="mb-3">
        <label class="form-label">Device ID</label>
        <input type="text" class="form-control" name="device_id" required>
      </div>

      <div class="mb-3">
        <label class="form-label">Full Name</label>
        <input type="text" class="form-control" name="fullname" required>
      </div>

      <div class="mb-3">
        <label class="form-label">Email Address</label>
        <input type="email" class="form-control" name="email" required>
      </div>

      <div class="mb-3">
        <label class="form-label">Phone Number</label>
        <input type="text" class="form-control" name="number" required>
      </div>

      <div class="mb-3">
        <label class="form-label">Address</label>
        <input type="text" class="form-control" name="address" required>
      </div>

      <div class="mb-3">
        <label class="form-label">Password</label>
        <div class="input-group">
          <input type="password" class="form-control" name="password" id="password" minlength="8" required>
          <span class="input-group-text pw-toggle" id="togglePassword"><i class="fa-regular fa-eye" id="eyePwd"></i></span>
        </div>
        <div class="password-help">Password must be at least 8 characters long.</div>
        <div id="strengthText" class="strength-indicator"></div>
      </div>

      <div class="mb-3">
        <label class="form-label">Confirm Password</label>
        <div class="input-group">
          <input type="password" class="form-control" name="confirm_password" id="confirm_password" required>
          <span class="input-group-text pw-toggle" id="toggleConfirm"><i class="fa-regular fa-eye" id="eyeConfirm"></i></span>
        </div>
        <div id="matchText" class="match-indicator"></div>
      </div>

      <button type="submit" class="btn btn-login w-100">Register</button>
    </form>

    <div class="footer-links mt-3">
      <a href="login.php">Already have an account? Login</a>
    </div>
  </div>

<script>
// Checkbox logic
const checkboxes = document.querySelectorAll('input[name="user_type"]');
checkboxes.forEach(box => {
  box.addEventListener('change', () => {
    checkboxes.forEach(other => { if (other !== box) other.checked = false; });
  });
});

// Password toggle
const password = document.getElementById('password');
const confirm = document.getElementById('confirm_password');
const togglePwd = document.getElementById('togglePassword');
const toggleConfirm = document.getElementById('toggleConfirm');
const eyePwd = document.getElementById('eyePwd');
const eyeConfirm = document.getElementById('eyeConfirm');

function toggle(input, icon) {
  if (input.type === 'password') {
    input.type = 'text';
    icon.className = 'fa-regular fa-eye-slash';
  } else {
    input.type = 'password';
    icon.className = 'fa-regular fa-eye';
  }
}
togglePwd.onclick = () => toggle(password, eyePwd);
toggleConfirm.onclick = () => toggle(confirm, eyeConfirm);

// Strength + Match
const strengthText = document.getElementById('strengthText');
const matchText = document.getElementById('matchText');

function checkStrength(pw) {
  const hasUpper = /[A-Z]/.test(pw);
  const hasNum = /\d/.test(pw);
  const hasSpecial = /[^A-Za-z0-9]/.test(pw);
  const len = pw.length >= 8;
  const score = [hasUpper, hasNum, hasSpecial, len].filter(Boolean).length;

  if (!pw) return { txt: '', cls: '' };
  if (!len) return { txt: 'Weak (min 8 chars)', cls: 'strength-weak' };
  if (score <= 2) return { txt: 'Weak', cls: 'strength-weak' };
  if (score === 3) return { txt: 'Medium', cls: 'strength-medium' };
  return { txt: 'Strong', cls: 'strength-strong' };
}

password.addEventListener('input', () => {
  const s = checkStrength(password.value);
  strengthText.textContent = s.txt ? 'Password Strength: ' + s.txt : '';
  strengthText.className = 'strength-indicator ' + s.cls;
  checkMatch();
});

confirm.addEventListener('input', checkMatch);

function checkMatch() {
  if (!confirm.value) { matchText.textContent = ''; return; }
  if (password.value === confirm.value) {
    matchText.textContent = 'Passwords match ✓';
    matchText.className = 'match-indicator strength-strong';
  } else {
    matchText.textContent = 'Passwords do not match';
    matchText.className = 'match-indicator strength-weak';
  }
}

document.querySelector('form').addEventListener('submit', e => {
  if (password.value.length < 8) {
    alert('Password must be at least 8 characters.');
    e.preventDefault();
  } else if (password.value !== confirm.value) {
    alert('Passwords do not match.');
    e.preventDefault();
  }
});
</script>
</body>
</html>
