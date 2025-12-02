<?php
require_once __DIR__ . "/../includes/db.php";

$fullname = "Super Admin";
$email = "jerichoabaya9@gmail.com";
$password = password_hash("admin123", PASSWORD_DEFAULT);
$number = "09123456789";
$role = "super_admin";

$stmt = $conn->prepare("INSERT INTO users (fullname, email, password, number, role) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("sssss", $fullname, $email, $password, $number, $role);

if ($stmt->execute()) {
    echo "✅ Super Admin account created successfully!<br>";
    echo "Email: $email<br>Password: admin123<br>";
    echo "Role: $role";
} else {
    echo "❌ Error: " . $conn->error;
}
?>
