<?php
include("../includes/db.php");
session_start();

// User must be logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

// Only allow POST requests
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$user_id   = $_SESSION['user_id'];
$station_id = intval($_POST['station_id']);
$field      = trim($_POST['field']);
$value      = trim($_POST['value']);

// Validate input
if (empty($station_id) || empty($field) || $value === "") {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit();
}

// Allowed fields to update (PREVENT SQL injection)
$allowed_fields = [
    "name",
    "location",
    "owner_name",
    "address",
    "contact_no",
    "email"
];

if (!in_array($field, $allowed_fields)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid field']);
    exit();
}

// Verify station belongs to user (same logic as old system)
$check = $conn->prepare("
    SELECT r.station_id
    FROM refilling_stations r
    INNER JOIN user_stations us ON r.station_id = us.station_id
    WHERE r.station_id = ? AND us.user_id = ?
");
$check->bind_param("ii", $station_id, $user_id);
$check->execute();
$result = $check->get_result();

if ($result->num_rows === 0) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Build dynamic SQL safely
$query = "UPDATE refilling_stations SET $field = ? WHERE station_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("si", $value, $station_id);

if ($stmt->execute()) {
    // Return JSON response for AJAX
    echo json_encode(['success' => true, 'message' => 'Station updated successfully']);
    exit();
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Update failed: ' . $stmt->error]);
    exit();
}
?>