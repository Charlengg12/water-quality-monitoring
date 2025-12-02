<?php
header("Content-Type: application/json");
require_once "../includes/db.php";

if (!isset($_GET['station_id'])) {
    echo json_encode(["error" => "No station_id provided"]);
    exit;
}

$station_id = intval($_GET['station_id']);

$sql = "SELECT * FROM water_data 
        WHERE station_id = $station_id 
        ORDER BY timestamp DESC 
        LIMIT 1";

$result = $conn->query($sql);

if ($result->num_rows === 0) {
    echo json_encode(["error" => "No data found"]);
    exit;
}

$row = $result->fetch_assoc();

echo json_encode([
    "source" => "mysql",
    "tds" => floatval($row["tds"]),
    "ph" => floatval($row["ph_level"]),
    "turbidity" => floatval($row["turbidity"]),
    "lead" => floatval($row["lead"]),
    "color" => floatval($row["color"]),
    "timestamp" => $row["timestamp"]
]);
?>
