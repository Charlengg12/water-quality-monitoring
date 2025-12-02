<?php
session_start();
include("../includes/db.php");
include("../includes/fetch_user.php");

// dompdf
require_once __DIR__ . '/../vendor/dompdf/autoload.inc.php';
use Dompdf\Dompdf;

// get POST filters
$filter_mode  = $_POST['filter_mode']  ?? '';
$filter_date  = $_POST['filter_date']  ?? '';
$filter_month = $_POST['filter_month'] ?? '';

function getParamStatus($param, $v) {
    if ($v === null || $v === "") return null;
    $value = floatval($v);
    switch ($param) {
        case 'ph_level':
            if ($value >= 5.2 && $value <= 6.8) return "safe";
            if ($value == 5.1 || $value == 6.9) return "neutral";
            if ($value == 5.0 || $value == 7.0) return "warning";
            return "failed";
        case 'color':
            if ($value <= 8) return "safe";
            if ($value == 9) return "neutral";
            if ($value == 10) return "warning";
            return "failed";
        case 'turbidity':
            if ($value <= 3.0) return "safe";
            if ($value == 4.0) return "neutral";
            if ($value == 5.0) return "warning";
            return "failed";
        case 'tds':
            if ($value <= 8) return "safe";
            if ($value == 9) return "neutral";
            if ($value == 10) return "warning";
            return "failed";
        case 'residual_chlorine':
            if ($value >= 0.5 && $value <= 1.3) return "safe";
            if ($value == 0.4 || $value == 1.4) return "neutral";
            if ($value == 0.3 || $value == 1.5) return "warning";
            return "failed";
        case 'lead':
            if ($value <= 0.008) return "safe";
            if ($value == 0.009) return "neutral";
            if ($value == 0.01) return "warning";
            return "failed";
        case 'cadmium':
            if ($value <= 0.001) return "safe";
            if ($value == 0.002) return "neutral";
            if ($value == 0.003) return "warning";
            return "failed";
        case 'arsenic':
            if ($value <= 0.008) return "safe";
            if ($value == 0.009) return "neutral";
            if ($value == 0.01) return "warning";
            return "failed";
        case 'nitrate':
            if ($value <= 48) return "safe";
            if ($value == 49) return "neutral";
            if ($value == 50) return "warning";
            return "failed";
    }
    return null;
}

function overallStatus($row) {
    $params = ['color','ph_level','turbidity','tds','residual_chlorine','lead','cadmium','arsenic','nitrate'];
    foreach ($params as $p) {
        if (isset($row[$p]) && $row[$p] !== null && $row[$p] !== '') {
            if (getParamStatus($p, $row[$p]) === 'failed') return 'FAILED';
        }
    }
    return 'PASSED';
}

// BUILD FILTER
$inner_where = "1";

if ($filter_mode === 'day' && $filter_date) {
    $fdate = $conn->real_escape_string($filter_date);
    $inner_where = "DATE(timestamp) = '{$fdate}'";
} 
elseif ($filter_mode === 'month' && $filter_month) {
    $fmonth = $conn->real_escape_string($filter_month);
    $inner_where = "DATE_FORMAT(timestamp, '%Y-%m') = '{$fmonth}'";
}
else {
    die("No filter selected. Please go back and choose a day or month.");
}

// MAIN QUERY — FIXED (device_id → device_sensor_id)
$sql = <<<SQL
SELECT 
    rs.station_id,
    rs.name AS station_name,
    rs.location AS station_location,
    rs.device_sensor_id,

    d.device_sensor_id AS dev_sensor,

    w.waterdata_id,
    w.color,
    w.ph_level,
    w.turbidity,
    w.tds,
    w.residual_chlorine,
    w.lead,
    w.cadmium,
    w.arsenic,
    w.nitrate,
    w.timestamp

FROM refilling_stations rs

LEFT JOIN devices d 
    ON d.device_sensor_id = rs.device_sensor_id

LEFT JOIN (
    SELECT w1.*
    FROM water_data w1
    INNER JOIN (
        SELECT device_sensor_id, MAX(timestamp) AS latest
        FROM water_data
        WHERE {$inner_where}
        GROUP BY device_sensor_id
    ) w2 
        ON w1.device_sensor_id = w2.device_sensor_id
       AND w1.timestamp = w2.latest
) w 
    ON w.device_sensor_id = rs.device_sensor_id

ORDER BY rs.name ASC
SQL;

$res = $conn->query($sql);
if (!$res) die("SQL error: " . $conn->error);

// START HTML FOR PDF
$generated = date("Y-m-d H:i:s");
$image_path = $_SERVER['DOCUMENT_ROOT'] . "/mnt/data/parameters.png";

$html = <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Water Reports</title>
<style>
body{font-family: DejaVu Sans, Arial, sans-serif; color:#222;}
.header{display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;}
h2{margin:0;}
table{width:100%;border-collapse:collapse;font-size:13px;}
th,td{border:1px solid #ddd;padding:6px;text-align:left;}
th{background:#f2f2f2;}
.badge-failed{background:#5d0f1a;color:white;padding:4px 6px;border-radius:4px;font-size:12px;}
.badge-passed{background:#0d3d19;color:white;padding:4px 6px;border-radius:4px;font-size:12px;}
.small{font-size:11px;color:#555;}
.logo{max-height:60px;}
</style>
</head>
<body>

<div class="header">
  <div>
    <h2>Water Quality Reports</h2>
    <div class="small">Generated: {$generated}</div>
  </div>
  <div>
    <img src="{$image_path}" class="logo">
  </div>
</div>

<table>
<thead>
<tr>
  <th>Station</th>
  <th>Location</th>
  <th>Latest Test</th>
  <th>Status</th>
</tr>
</thead>
<tbody>
HTML;

// ADD ROWS
while ($row = $res->fetch_assoc()) {

    if (!$row['waterdata_id']) continue;

    $overall = overallStatus($row);
    $badge = ($overall === 'FAILED')
        ? '<span class="badge-failed">FAILED</span>'
        : '<span class="badge-passed">PASSED</span>';

    $station = htmlspecialchars($row['station_name']);
    $location = htmlspecialchars($row['station_location']);
    $time = htmlspecialchars(date("Y-m-d H:i", strtotime($row['timestamp'])));

    $html .= <<<ROW
<tr>
    <td>{$station}</td>
    <td>{$location}</td>
    <td>{$time}</td>
    <td>{$badge}</td>
</tr>
ROW;
}

$html .= "</tbody></table></body></html>";

// GENERATE PDF
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// FILENAME
$filename = 'reports_' . ($filter_mode==='day' ? $filter_date : $filter_month) . '.pdf';

// OUTPUT PDF
$dompdf->stream($filename, ["Attachment" => 1]);
exit;
?>
