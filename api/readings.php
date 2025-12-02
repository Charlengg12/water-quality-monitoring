<?php
/**
 * readings.php
 *
 * HTTP JSON endpoint that returns the latest readings for a station
 * using the SAME JSON shape as the ESP32 `/readings` handler.
 *
 * Expected query:
 *   GET /api/readings.php?station_id=1
 *
 * JSON response example:
 * {
 *   "TDS_Value": 120.5,
 *   "TDS_Status": "Safe",
 *   "PH_Value": 7.10,
 *   "PH_Status": "Neutral",
 *   "Turbidity_Value": 1.20,
 *   "Turbidity_Status": "Safe",
 *   "Lead_Value": 0.0100,
 *   "Lead_Status": "Neutral",
 *   "Color_Value": 9.50,
 *   "Color_Result": "Clear",
 *   "Color_Status": "Neutral",
 *   "source": "mysql"
 * }
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/db.php';

if (!isset($_GET['station_id'])) {
    http_response_code(400);
    echo json_encode([
        'error' => 'No station_id provided'
    ]);
    exit;
}

$station_id = (int) $_GET['station_id'];

if ($station_id <= 0) {
    http_response_code(400);
    echo json_encode([
        'error' => 'Invalid station_id'
    ]);
    exit;
}

// Adjust column names to match your actual `water_data` schema.
// This assumes the structure used in the newer dashboard.php insert:
//  tds_value, ph_value, turbidity_value, lead_value, color_value,
//  tds_status, ph_status, turbidity_status, lead_status, color_status, color_result
$sql = "
    SELECT 
        tds_value,
        ph_value,
        turbidity_value,
        lead_value,
        color_value,
        tds_status,
        ph_status,
        turbidity_status,
        lead_status,
        color_status,
        color_result
    FROM water_data
    WHERE station_id = ?
    ORDER BY timestamp DESC
    LIMIT 1
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database prepare error',
        'details' => $conn->error
    ]);
    exit;
}

$stmt->bind_param('i', $station_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    echo json_encode([
        'error' => 'No data found for this station'
    ]);
    $stmt->close();
    exit;
}

$row = $result->fetch_assoc();
$stmt->close();

// Build response with the same keys used by ESP32 `handleReadings()`
$response = [
    'TDS_Value'        => isset($row['tds_value']) ? (float)$row['tds_value'] : null,
    'TDS_Status'       => $row['tds_status'] ?? null,
    'PH_Value'         => isset($row['ph_value']) ? (float)$row['ph_value'] : null,
    'PH_Status'        => $row['ph_status'] ?? null,
    'Turbidity_Value'  => isset($row['turbidity_value']) ? (float)$row['turbidity_value'] : null,
    'Turbidity_Status' => $row['turbidity_status'] ?? null,
    'Lead_Value'       => isset($row['lead_value']) ? (float)$row['lead_value'] : null,
    'Lead_Status'      => $row['lead_status'] ?? null,
    'Color_Value'      => isset($row['color_value']) ? (float)$row['color_value'] : null,
    'Color_Result'     => $row['color_result'] ?? null,
    'Color_Status'     => $row['color_status'] ?? null,
    'source'           => 'mysql'
];

echo json_encode($response);
exit;


