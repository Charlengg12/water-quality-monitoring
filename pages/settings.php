<?php  
include("../includes/db.php");
include("../includes/fetch_user.php");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Settings - Water Quality Monitoring System</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"/>
  <style>
    body {
      background: #0e1117;
      font-family: 'Segoe UI', sans-serif;
      color: #fff;
    }
    .navbar {
      background-color: #1f2733;
      box-shadow: 0 2px 10px rgba(0, 198, 255, 0.2);
    }
    .navbar-brand, .nav-link, .btn {
      color: #fff;
    }
    .navbar-nav .nav-link:hover {
      color: #00c6ff;
    }
    .container {
      background: #1f2733;
      border-radius: 20px;
      padding: 30px;
      margin-top: 30px;
      box-shadow: 0 0 30px rgba(0, 198, 255, 0.1);
    }
    .section-title {
      font-size: 22px;
      font-weight: bold;
      color: #00c6ff;
      text-align: center;
      margin-bottom: 30px;
    }
    .form-control {
      border-radius: 10px;
      padding-right: 2.5rem; 
      background-color: #1e1e1e;
      border: 1px solid #333;
      color: #fff;
    }
    .form-control:focus {
      border-color: #00e6e6;
      box-shadow: 0 0 10px #00e6e6;
    }
    .btn-settings {
      background-color: #0072ff;
      color: white;
      font-weight: bold;
      border-radius: 10px;
      padding: 10px;
    }
    .btn-settings:hover {
      background-color: #005fcc;
    }
    .footer-text {
      text-align: center;
      margin-top: 15px;
      color: #777;
      font-size: 14px;
    }
    .modal-content {
      background-color: #1f2733;
      color: white;
      border-radius: 15px;
    }
    .modal-header {
      border-bottom: 1px solid #444;
    }
    .modal-footer {
      border-top: 1px solid #444;
    }
    .btn-close {
      background-color: white;
    }
    /* Eye toggle styling */
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
    .input-group .form-control {
      border-right: none;
    }
    /* Password indicators */
    .strength-text {
      font-size: 0.9rem;
      margin-top: 5px;
    }
    .strength-weak { color: #ff4d4f; }
    .strength-medium { color: #00c6ff; }
    .strength-strong { color: #00e6e6; }
    .match-text { font-size: 0.9rem; margin-top: 5px; }
  </style>
</head>
<body>
  <nav class="navbar navbar-expand-lg">
    <div class="container-fluid">
      <a class="navbar-brand" href="#"><i class="fas fa-tint"></i> Water Quality Testing & Monitoring System</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
          <li class="nav-item"><a class="nav-link" href="stations.php"><i class="fas fa-building"></i> Devices/Stations</a></li>
          <li class="nav-item"><a class="nav-link" href="reports.php"><i class="fas fa-chart-line"></i> Reports</a></li>
          <li class="nav-item"><a class="nav-link" href="notifications.php"><i class="fas fa-bell"></i> Notifications</a></li>
          
          <li class="nav-item">
            <a class="nav-link d-flex align-items-center" href="account.php" style="gap: 8px;">
              <img 
                src="<?php echo htmlspecialchars($user['profile_pic']); ?>" 
                alt="Account" 
                class="rounded-circle account-icon">
              <span class="account-text">Account</span>
            </a>
          </li>
          <style>
            .account-icon {
              width: 24px;
              height: 24px;
              border: 1px solid white;
              box-shadow: 0 0 8px white;
              object-fit: cover;
            }
          </style>
        </ul>
      </div>
    </div>
  </nav>

  <div class="container text-center">
    <div class="text-start">
      <a href="account.php" class="back-btn"><i class="fas fa-arrow-left"></i></a>
    </div>
    <style>
      .back-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: transparent;
        color: #00c6ff;
        font-size: 1.2rem;
        text-decoration: none;
        margin-bottom: 15px;
      }
      .back-btn:hover {
        background: rgba(0, 198, 255, 0.1);
        color: #0072ff;
      }
    </style>

    <div class="section-title">Settings</div>
    <button class="btn btn-settings me-2" data-bs-toggle="modal" data-bs-target="#changePasswordModal">Change Password</button>
    <button class="btn btn-settings" data-bs-toggle="modal" data-bs-target="#forgotPasswordModal">Forgot Password</button>
    <div class="footer-text mt-4">© 2025 Water Quality Testing & Monitoring System</div>
  </div>

  <!-- Change Password Modal -->
  <div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="changePasswordLabel">Change Password</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form action="change-password.php" method="POST" id="changePasswordForm">
          <div class="modal-body">
            <div class="mb-3">
              <label for="current-password" class="form-label">Current Password</label>
              <div class="input-group">
                <input type="password" class="form-control" id="current-password" name="current-password" required>
                <span class="input-group-text pw-toggle" onclick="togglePassword('current-password', this)">
                  <i class="fa-regular fa-eye" id="eyeCurrent"></i>
                </span>
              </div>
            </div>

            <div class="mb-3">
              <label for="new-password" class="form-label">New Password</label>
              <div class="input-group">
                <input type="password" class="form-control" id="new-password" name="new-password" required>
                <span class="input-group-text pw-toggle" onclick="togglePassword('new-password', this)">
                  <i class="fa-regular fa-eye" id="eyeNew"></i>
                </span>
              </div>
              <div class="mt-1" style="color:#ffcccb; font-size:0.9rem;">Password must be at least 8 characters long.</div>
              <div id="strengthText" class="strength-text"></div>
            </div>

            <div class="mb-3">
              <label for="confirm-password" class="form-label">Confirm New Password</label>
              <div class="input-group">
                <input type="password" class="form-control" id="confirm-password" name="confirm-password" required>
                <span class="input-group-text pw-toggle" onclick="togglePassword('confirm-password', this)">
                  <i class="fa-regular fa-eye" id="eyeConfirm"></i>
                </span>
              </div>
              <div id="matchText" class="match-text"></div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="submit" class="btn btn-settings w-100">Change Password</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Forgot Password Modal -->
  <div class="modal fade" id="forgotPasswordModal" tabindex="-1" aria-labelledby="forgotPasswordLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="forgotPasswordLabel">Forgot Password</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form action="forgot-password.php" method="POST">
          <div class="modal-body">
            <div class="mb-3 position-relative">
              <label for="email" class="form-label">Enter your Email</label>
              <input type="email" class="form-control" id="email" name="email" required />
            </div>
          </div>
          <div class="modal-footer">
            <button type="submit" class="btn btn-settings w-100">Reset Password</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Eye toggle logic
    function togglePassword(fieldId, icon) {
        const input = document.getElementById(fieldId);
        if (!input) return;

        if (input.type === 'password') {
            input.type = 'text';
            icon.firstElementChild.className = 'fa-regular fa-eye-slash';
        } else {
            input.type = 'password';
            icon.firstElementChild.className = 'fa-regular fa-eye';
        }
        input.focus();
    }

    // Password strength and match indicators
    const newPwd = document.getElementById('new-password');
    const confirmPwd = document.getElementById('confirm-password');
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

    function checkMatch() {
      if (!confirmPwd.value) { 
        matchText.textContent = ''; 
        matchText.className = 'match-text';
        return; 
      }
      if (newPwd.value === confirmPwd.value) {
        matchText.textContent = 'Passwords match ✓';
        matchText.className = 'match-text strength-strong';
      } else {
        matchText.textContent = 'Passwords do not match';
        matchText.className = 'match-text strength-weak';
      }
    }

    newPwd.addEventListener('input', () => {
      const s = checkStrength(newPwd.value);
      strengthText.textContent = s.txt ? 'Password Strength: ' + s.txt : '';
      strengthText.className = 'strength-text ' + s.cls;
      checkMatch();
    });

    confirmPwd.addEventListener('input', checkMatch);

    document.getElementById('changePasswordForm').addEventListener('submit', e => {
      if (newPwd.value.length < 8) {
        alert('Password must be at least 8 characters long.');
        e.preventDefault();
      } else if (newPwd.value !== confirmPwd.value) {
        alert('Passwords do not match.');
        e.preventDefault();
      }
    });
  </script>
</body>
</html>
