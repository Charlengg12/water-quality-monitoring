<?php
include("../includes/db.php");
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $current_password = $_POST['current-password'] ?? '';
    $new_password     = $_POST['new-password'] ?? '';
    $confirm_password = $_POST['confirm-password'] ?? '';

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        echo "<script>alert('All fields are required.'); window.location='settings.php';</script>";
        exit();
    }

    // Fetch current password hash
    $stmt = $conn->prepare("SELECT password FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($hashed_password);
    $stmt->fetch();
    $stmt->close();

    // Verify current password
    if (!password_verify($current_password, $hashed_password)) {
        echo "<script>alert('Current password is incorrect.'); window.location='settings.php';</script>";
        exit();
    }

    // Password length check
    if (mb_strlen($new_password) < 8) {
        echo "<script>alert('Password must be at least 8 characters long.'); window.location='settings.php';</script>";
        exit();
    }

    // Check new passwords match
    if ($new_password !== $confirm_password) {
        echo "<script>alert('New passwords do not match.'); window.location='settings.php';</script>";
        exit();
    }

    // Hash new password
    $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    // Update password in DB
    $update = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
    $update->bind_param("si", $new_hashed_password, $user_id);
    if ($update->execute()) {
        echo "<script>alert('Password updated successfully!'); window.location='settings.php';</script>";
    } else {
        echo "<script>alert('Error updating password. Please try again.'); window.location='settings.php';</script>";
    }
    $update->close();
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Change Password</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
    body {
        background: #001f3f;
        color: #f0f0f0;
        font-family: 'Segoe UI', sans-serif;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
    }
    .container-box {
        background-color: rgba(0,0,0,0.8);
        padding: 30px;
        border-radius: 20px;
        box-shadow: 0 0 30px rgba(0, 191, 255, 0.3);
        width: 100%;
        max-width: 420px;
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
    .btn-update {
        background-color: #00bfff;
        color: #000;
        font-weight: bold;
        border-radius: 10px;
        padding: 10px;
        transition: 0.3s;
    }
    .btn-update:hover {
        background-color: #00e6ff;
        box-shadow: 0 0 12px #00e6ff;
    }
    .input-group-text.pw-toggle {
        background: transparent;
        border: 1px solid #333;
        border-left: none;
        color: #ccc;
        cursor: pointer;
        transition: 0.3s;
    }
    .input-group-text.pw-toggle:hover { color: #00e6ff; }
    .input-group .form-control { border-right: none; }
    .strength-indicator, .match-indicator { font-size: 0.9rem; margin-top: 5px; }
    .strength-weak { color: #ff4d4f; }
    .strength-medium { color: #fa8c16; }
    .strength-strong { color: #52c41a; }
</style>
</head>
<body>
<div class="container-box">
    <h2 class="text-center mb-4" style="color:#00d4ff;">Change Password</h2>
    <form method="POST" action="">
        <div class="mb-3">
            <label class="form-label">Current Password</label>
            <div class="input-group">
                <input type="password" class="form-control" name="current-password" id="current_password" required>
                <span class="input-group-text pw-toggle" id="toggleCurrent"><i class="fa-regular fa-eye" id="eyeCurrent"></i></span>
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label">New Password</label>
            <div class="input-group">
                <input type="password" class="form-control" name="new-password" id="new_password" required>
                <span class="input-group-text pw-toggle" id="toggleNew"><i class="fa-regular fa-eye" id="eyeNew"></i></span>
            </div>
            <div class="strength-indicator" id="strengthText"></div>
            <div class="password-help" style="color:#ffcccb;">Password must be at least 8 characters long.</div>
        </div>
        <div class="mb-3">
            <label class="form-label">Confirm New Password</label>
            <div class="input-group">
                <input type="password" class="form-control" name="confirm-password" id="confirm_password" required>
                <span class="input-group-text pw-toggle" id="toggleConfirm"><i class="fa-regular fa-eye" id="eyeConfirm"></i></span>
            </div>
            <div class="match-indicator" id="matchText"></div>
        </div>
        <button type="submit" class="btn btn-update w-100">Update Password</button>
    </form>
</div>

<script>
const currentPwd = document.getElementById('current_password');
const newPwd = document.getElementById('new_password');
const confirmPwd = document.getElementById('confirm_password');

const toggleCurrent = document.getElementById('toggleCurrent');
const toggleNew = document.getElementById('toggleNew');
const toggleConfirm = document.getElementById('toggleConfirm');

const eyeCurrent = document.getElementById('eyeCurrent');
const eyeNew = document.getElementById('eyeNew');
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

toggleCurrent.onclick = () => toggle(currentPwd, eyeCurrent);
toggleNew.onclick = () => toggle(newPwd, eyeNew);
toggleConfirm.onclick = () => toggle(confirmPwd, eyeConfirm);

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

newPwd.addEventListener('input', () => {
    const s = checkStrength(newPwd.value);
    strengthText.textContent = s.txt ? 'Password Strength: ' + s.txt : '';
    strengthText.className = 'strength-indicator ' + s.cls;
    checkMatch();
});

confirmPwd.addEventListener('input', checkMatch);

function checkMatch() {
    if (!confirmPwd.value) { matchText.textContent = ''; return; }
    if (newPwd.value === confirmPwd.value) {
        matchText.textContent = 'Passwords match ✓';
        matchText.className = 'match-indicator strength-strong';
    } else {
        matchText.textContent = 'Passwords do not match';
        matchText.className = 'match-indicator strength-weak';
    }
}

document.querySelector('form').addEventListener('submit', e => {
    if (newPwd.value.length < 8) {
        alert('Password must be at least 8 characters.');
        e.preventDefault();
    } else if (newPwd.value !== confirmPwd.value) {
        alert('Passwords do not match.');
        e.preventDefault();
    }
});
</script>
</body>
</html>
