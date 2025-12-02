<?php
include("../includes/db.php");
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $device_sensor_id = trim($_POST['device_sensor_id']);

    // Check if station exists
    $stmt = $conn->prepare("SELECT station_id FROM refilling_stations WHERE device_sensor_id = ?");
    $stmt->bind_param("s", $device_sensor_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $station_id = $row['station_id'];

        // Check if already linked
        $check = $conn->prepare("SELECT * FROM user_stations WHERE user_id = ? AND station_id = ?");
        $check->bind_param("ii", $user_id, $station_id);
        $check->execute();
        $check_result = $check->get_result();

        if ($check_result->num_rows > 0) {
            echo "<script>alert('You already added this station.'); window.location.href='stations.php';</script>";
            exit();
        }

        // Link user to station
        $insert = $conn->prepare("INSERT INTO user_stations (user_id, station_id) VALUES (?, ?)");
        $insert->bind_param("ii", $user_id, $station_id);

        if ($insert->execute()) {
            echo "<script>alert('Station added successfully!'); window.location.href='stations.php';</script>";
        } else {
            echo "<script>alert('Error adding station.'); window.location.href='stations.php';</script>";
        }
    } else {
        echo "<script>alert('Station not found. Please check the Device Sensor ID.'); window.location.href='stations.php';</script>";
    }
}
?>
