<?php
require_once __DIR__ . "/../includes/db.php"; 
session_start();

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $account_name = trim($_POST["account_name"]);
    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);
    $confirm_password = trim($_POST["confirm_password"]);
    $number = trim($_POST["number"]);
    $location = trim($_POST["location"]);

    if (empty($account_name) || empty($email) || empty($password) || empty($confirm_password)) {
        $message = "⚠️ Please fill out all required fields.";
    } elseif ($password !== $confirm_password) {
        $message = "❌ Passwords do not match!";
    } elseif (strlen($password) < 8) {
        $message = "❌ Password must be at least 8 characters long!";
    } else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);

        // Check if location exists
        $check = $conn->prepare("SELECT location_id FROM lgu_locations WHERE location_name = ?");
        $check->bind_param("s", $location);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $check->bind_result($location_id);
            $check->fetch();
        } else {
            $insertLoc = $conn->prepare("INSERT INTO lgu_locations (location_name) VALUES (?)");
            $insertLoc->bind_param("s", $location);
            $insertLoc->execute();
            $location_id = $insertLoc->insert_id;
            $insertLoc->close();
        }
        $check->close();

        // FIXED INSERT — now stores BOTH text + numeric FK
        $stmt = $conn->prepare("
            INSERT INTO users (fullname, email, password, number, role, lgu_location, lgu_location_id)
            VALUES (?, ?, ?, ?, 'lgu_menro', ?, ?)
        ");

        $stmt->bind_param("sssssi", 
            $account_name, 
            $email, 
            $hashed, 
            $number, 
            $location,       // TEXT location
            $location_id     // FK location id
        );

        if ($stmt->execute()) {
            $message = "✅ LGU MENRO account created successfully!";
        } else {
            $message = "❌ Database Error: " . $stmt->error;
        }

        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register LGU MENRO Account</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
body {
    background-color: #0d1117;
    color: #f0f0f0;
    font-family: 'Segoe UI', sans-serif;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding-top: 60px;
    padding-bottom: 40px;
    min-height: 100vh;
    margin: 0;
}
.container-box {
    width: 100%;
    max-width: 420px;
    background-color: rgba(0, 0, 0, 0.85);
    border-radius: 20px;
    box-shadow: 0 0 30px rgba(0, 191, 255, 0.3);
    padding: 35px 30px;
}
h2 {
    text-align: center;
    color: #00d4ff;
    margin-bottom: 25px;
    font-weight: 600;
}
.droplet {
    text-align: center;
    font-size: 40px;
    color: #00bfff;
    margin-bottom: 10px;
}
.form-label {
    color: #ccc;
    font-weight: 500;
}
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
.btn-submit {
    background-color: #00bfff;
    color: #000;
    font-weight: bold;
    border-radius: 10px;
    padding: 10px;
    transition: 0.3s;
}
.btn-submit:hover {
    background-color: #00e6ff;
    box-shadow: 0 0 12px #00e6ff;
}
.helper-text {
    font-size: 13px;
    color: #aaa;
    margin-top: 5px;
}
.strength, .match-status {
    font-size: 14px;
    font-weight: bold;
    margin-top: 5px;
}
.strength.weak, .match-status.no {
    color: #ff4d4d;
}
.strength.medium {
    color: #ffaa00;
}
.strength.strong, .match-status.yes {
    color: #00ff7f;
}
</style>
</head>
<body>

<div class="container-box">
    <div class="droplet">💧</div>
    <h2>Create LGU MENRO Account</h2>

    <form method="POST">

        <div class="mb-3">
            <label class="form-label">Account Name</label>
            <input type="text" class="form-control" name="account_name" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Email Address</label>
            <input type="email" class="form-control" name="email" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Contact Number</label>
            <input type="text" class="form-control" name="number" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Municipality (LGU Location)</label>
            <input type="text" class="form-control" name="location" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Password</label>
            <div class="input-group">
                <input type="password" class="form-control" name="password" id="password" minlength="8" required>
                <span class="input-group-text pw-toggle" id="togglePassword"><i class="fa-regular fa-eye" id="eyePwd"></i></span>
            </div>
            <div class="helper-text">Password must be at least 8 characters long.</div>
            <div id="strengthText" class="strength"></div>
        </div>

        <div class="mb-3">
            <label class="form-label">Confirm Password</label>
            <div class="input-group">
                <input type="password" class="form-control" name="confirm_password" id="confirm_password" required>
                <span class="input-group-text pw-toggle" id="toggleConfirm"><i class="fa-regular fa-eye" id="eyeConfirm"></i></span>
            </div>
            <div id="matchText" class="match-status"></div>
        </div>

        <button type="submit" class="btn btn-submit w-100">Register Account</button>
    </form>
</div>

<script>
const password = document.getElementById('password');
const confirmPwd = document.getElementById('confirm_password');
const togglePwd = document.getElementById('togglePassword');
const toggleConfirm = document.getElementById('toggleConfirm');
const eyePwd = document.getElementById('eyePwd');
const eyeConfirm = document.getElementById('eyeConfirm');
const strengthText = document.getElementById('strengthText');
const matchText = document.getElementById('matchText');

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
toggleConfirm.onclick = () => toggle(confirmPwd, eyeConfirm);

password.addEventListener('input', () => {
    const val = password.value;
    let strength = '';
    let colorClass = '';

    if (val.length >= 8 && /[A-Z]/.test(val) && /[0-9]/.test(val) && /[^A-Za-z0-9]/.test(val)) {
        strength = 'Strong';
        colorClass = 'strong';
    } else if (val.length >= 6 && /[A-Z]/.test(val) && /[0-9]/.test(val)) {
        strength = 'Medium';
        colorClass = 'medium';
    } else if (val.length > 0) {
        strength = 'Weak';
        colorClass = 'weak';
    }

    strengthText.textContent = strength ? `Password Strength: ${strength}` : '';
    strengthText.className = `strength ${colorClass}`;
});

function checkMatch() {
    if (confirmPwd.value.length === 0) {
        matchText.textContent = '';
        matchText.className = 'match-status';
        return;
    }
    if (password.value === confirmPwd.value) {
        matchText.textContent = '✅ Passwords Match';
        matchText.className = 'match-status yes';
    } else {
        matchText.textContent = '❌ Passwords Do Not Match';
        matchText.className = 'match-status no';
    }
}
password.addEventListener('input', checkMatch);
confirmPwd.addEventListener('input', checkMatch);
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<?php if (!empty($message)) : ?>
<div class="modal fade" id="msgModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="background:#1c1c1c; color:#fff; border-radius:15px;">
      <div class="modal-header border-0">
        <h5 class="modal-title">Notification</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body text-center">
        <?= $message ?>
      </div>
      <div class="modal-footer border-0">
        <button type="button" class="btn btn-primary w-100" data-bs-dismiss="modal">OK</button>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    var myModal = new bootstrap.Modal(document.getElementById('msgModal'));
    myModal.show();
});
</script>
<?php endif; ?>

</body>
</html>
