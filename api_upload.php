<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

// Database connection
$host = "localhost";
$user = "root";
$pass = "";
$db = "water_monitoring";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die(json_encode(["status" => "error", "msg" => $conn->connect_error]));
}

// Read JSON from ESP32
$json = file_get_contents("php://input");
$data = json_decode($json, true);

if (!$data) {
    echo json_encode(["status" => "error", "msg" => "Invalid JSON"]);
    exit;
}

// Extract
$sensorId  = $data["sensorId"];

$tds       = $data["tds_val"];
$ph        = $data["ph_val"];
$turbidity = $data["turbidity_val"];
$lead      = $data["lead_val"];
$color     = $data["color_val"];

// Get station_id based on sensorId from refilling_stations table
$q = $conn->prepare("SELECT station_id FROM refilling_stations WHERE device_sensor_id = ?");
$q->bind_param("s", $sensorId);
$q->execute();
$result = $q->get_result();

if ($result->num_rows == 0) {
    echo json_encode(["status" => "error", "msg" => "Sensor ID not registered"]);
    exit;
}

$row = $result->fetch_assoc();
$station_id = $row["station_id"];

// Insert data into water_data table
$stmt = $conn->prepare("
INSERT INTO water_data (station_id, color, ph_level, turbidity, tds, lead, timestamp)
VALUES (?, ?, ?, ?, ?, ?, NOW())
");

$stmt->bind_param("idddds", $station_id, $color, $ph, $turbidity, $tds, $lead);

if ($stmt->execute()) {
    echo json_encode(["status" => "success", "msg" => "Data saved"]);
} else {
    echo json_encode(["status" => "error", "msg" => $stmt->error]);
}

$conn->close();
?>
