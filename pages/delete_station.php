<?php
include("../includes/db.php");
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['station_id'])) {
    $station_id = intval($_POST['station_id']);

    // Unlink station from this user only
    $stmt = $conn->prepare("DELETE FROM user_stations WHERE user_id = ? AND station_id = ?");
    $stmt->bind_param("ii", $user_id, $station_id);

    if ($stmt->execute()) {
        echo "<script>alert('Station removed from your account.'); window.location.href='stations.php';</script>";
    } else {
        echo "<script>alert('Error removing station.'); window.location.href='stations.php';</script>";
    }
}
?>
