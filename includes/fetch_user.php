<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include("db.php");

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$query = $conn->prepare("SELECT fullname, email, number, role, lgu_location, profile_pic FROM users WHERE user_id = ?");
$query->bind_param("i", $user_id);
$query->execute();
$result = $query->get_result();
$user = $result->fetch_assoc();

// Default avatar if none uploaded
if (empty($user['profile_pic'])) {
    $user['profile_pic'] = "https://cdn-icons-png.flaticon.com/512/847/847969.png";
}