<?php
require_once __DIR__ . "/../includes/db.php";
session_start();

$popup = ""; // for JS popup message

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $account_name = trim($_POST["account_name"]);
    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);
    $confirm_password = trim($_POST["confirm_password"]);
    $number = trim($_POST["number"]);
    $address = trim($_POST["address"]);

    if ($password !== $confirm_password) {
        $popup = "❌ Passwords do not match!";
    } else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (fullname, email, password, number, address, role) VALUES (?, ?, ?, ?, ?, 'super_admin')");
        $stmt->bind_param("sssss", $account_name, $email, $hashed, $number, $address);

        if ($stmt->execute()) {
            $popup = "✅ Super Admin account created successfully!";
        } else {
            $popup = "❌ Error: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register Super Admin Account</title>
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
    min-height: 100vh;
    padding-top: 40px;
    margin: 0;
}
.page-title {
    font-size: 28px;
    font-weight: 600;
    color: #00d4ff;
    margin-bottom: 20px;
    text-transform: uppercase;
    text-align: center;
    letter-spacing: 1px;
}
.container-box {
    width: 100%;
    max-width: 420px;
    background-color: rgba(0, 0, 0, 0.8);
    border-radius: 20px;
    box-shadow: 0 0 30px rgba(0, 191, 255, 0.3);
    padding: 35px;
    margin-bottom: 40px;
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
.form-label {
    color: #ccc;
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
.small-text {
    font-size: 12px;
    color: #999;
    margin-top: 5px;
}
.strength, .match-status {
    font-size: 13px;
    font-weight: 600;
    margin-top: 5px;
}
.strength.weak, .match-status.no { color: #ff4d4d; }
.strength.medium { color: #ffaa00; }
.strength.strong, .match-status.yes { color: #00ff7f; }
</style>
</head>
<body>

<?php if (!empty($popup)) : ?>
<script>
    alert("<?php echo $popup; ?>");
</script>
<?php endif; ?>

<div class="container-box">
    <div class="droplet">💧</div>
    <h2>Create Super Admin Account</h2>

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
            <label class="form-label">Address</label>
            <input type="text" class="form-control" name="address" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Password</label>
            <div class="input-group">
                <input type="password" class="form-control" name="password" id="password" minlength="8" required>
                <span class="input-group-text pw-toggle" id="togglePassword"><i class="fa-regular fa-eye" id="eyePwd"></i></span>
            </div>
            <div class="small-text">Password must be at least 8 characters long.</div>
            <div id="password-strength" class="strength"></div>
        </div>

        <div class="mb-3">
            <label class="form-label">Confirm Password</label>
            <div class="input-group">
                <input type="password" class="form-control" name="confirm_password" id="confirm_password" required>
                <span class="input-group-text pw-toggle" id="toggleConfirm"><i class="fa-regular fa-eye" id="eyeConfirm"></i></span>
            </div>
            <div id="match-status" class="match-status"></div>
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
const strengthText = document.getElementById('password-strength');
const matchText = document.getElementById('match-status');

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

// Password Strength Indicator
password.addEventListener('input', () => {
    const val = password.value;
    let strength = '';
    let score = 0;

    if (val.length >= 8) score++;
    if (/[A-Z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;

    if (val.length === 0) {
        strengthText.textContent = '';
        strengthText.className = 'strength';
        return;
    }

    if (score <= 1) {
        strength = 'Weak';
        strengthText.className = 'strength weak';
    } else if (score === 2 || score === 3) {
        strength = 'Medium';
        strengthText.className = 'strength medium';
    } else {
        strength = 'Strong';
        strengthText.className = 'strength strong';
    }

    strengthText.textContent = 'Password Strength: ' + strength;
});

// Password Match Indicator
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

</body>
</html>
