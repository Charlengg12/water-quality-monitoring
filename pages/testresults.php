<?php
session_start();

// Persist selected station across pages using session
if (isset($_GET['station_id'])) {
    $_SESSION['station_id'] = (int)$_GET['station_id'];
}
$station_id = $_SESSION['station_id'] ?? null;

// helper for appending station_id to internal links
$sid_q = $station_id ? '?station_id=' . (int)$station_id : '';

include("../includes/db.php");
include("../includes/fetch_user.php");

// Fetch station info if available
$station = null;
$device_sensor_id = null;
if ($station_id) {
    $sql = "SELECT * FROM refilling_stations WHERE station_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $station_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $station = $res->fetch_assoc();
    $stmt->close();
    
    // Get the device_sensor_id for this station
    if ($station) {
        $device_sensor_id = $station['device_sensor_id'];
    }
}

// Fetch available test runs (water_data) with actual parameter values
$testRunsResult = null;
if ($device_sensor_id) {
    // Use device_sensor_id to get test data with all parameters
    $sql = "SELECT * FROM water_data WHERE device_sensor_id = ? ORDER BY timestamp DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $device_sensor_id);
    $stmt->execute();
    $testRunsResult = $stmt->get_result();
} else {
    // If no specific station, show all test data for debugging
    $sql = "SELECT * FROM water_data ORDER BY timestamp DESC LIMIT 10";
    $testRunsResult = $conn->query($sql);
}

// Parameter status rules (same as in reports.php)
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

// Overall status
function overallStatus($row) {
    $params = ['color','ph_level','turbidity','tds','residual_chlorine','lead','cadmium','arsenic','nitrate'];
    foreach ($params as $p) {
        if (isset($row[$p]) && $row[$p] !== null && $row[$p] !== '') {
            if (getParamStatus($p, $row[$p]) === 'failed') return 'FAILED';
        }
    }
    return 'PASSED';
}

// Badge HTML
function badgeHtml($overall) {
    if ($overall === 'FAILED') return "<span class='badge badge-failed'>FAILED</span>";
    return "<span class='badge badge-passed'>PASSED</span>";
}

// Status badge with color
function paramStatusBadge($param, $value) {
    $status = getParamStatus($param, $value);
    $badgeClass = '';
    switch ($status) {
        case 'safe': $badgeClass = 'bg-success'; break;
        case 'neutral': $badgeClass = 'bg-warning'; break;
        case 'warning': $badgeClass = 'bg-warning text-dark'; break;
        case 'failed': $badgeClass = 'bg-danger'; break;
        default: $badgeClass = 'bg-secondary';
    }
    return "<span class='badge $badgeClass'>" . strtoupper($status ?: 'N/A') . "</span>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Test Results - Water Quality Monitor</title>

  <!-- Bootstrap & Font Awesome -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />

  <style>
    body { background: #0e1117; font-family: 'Segoe UI', sans-serif; color: #fff; }
    .navbar { background-color: #1f2733; box-shadow: 0 2px 10px rgba(0, 198, 255, 0.2); }
    .navbar-brand, .nav-link, .btn { color: #fff; }
    .navbar-nav .nav-link:hover { color: #00c6ff; }

    .container { background: #1f2733; border-radius: 20px; padding: 30px; margin-top: 30px; box-shadow: 0 0 30px rgba(0, 198, 255, 0.1); }

    .station-name { font-size: 22px; font-weight: bold; color: #00c6ff; text-align: center; }
    .station-address { font-size: 14px; color: #ccc; text-align: center; margin-bottom: 4px; }
    .sensor-id { text-align: center; }
    .sensor-id p { margin: 0; }
    .timestamp { text-align: center; font-size: 14px; color: #aaa; margin-top: 6px; }

    .status-label { display: inline-flex; align-items: center; justify-content: center; gap: 8px; font-size: 20px; font-weight: bold; margin: 0 auto 6px auto; padding: 6px 20px; border: 2px solid black; border-radius: 30px; background: transparent; color: white; }
    .status-dot { display: inline-block; width: 14px; height: 14px; border-radius: 50%; }
    .status-online .status-dot { background: #28a745; }  /* green dot */
    .status-offline .status-dot { background: #dc3545; } /* red dot */

    .hover-card:hover { background-color: #1c1c1c !important; box-shadow: 0 4px 12px rgba(0,0,0,0.4); transition: 0.2s ease; }
    .account-icon { width: 24px; height: 24px; border: 1px solid white; box-shadow: 0 0 8px white; object-fit: cover; }
    
    .badge-failed { background:#5d0f1a; color:#e3342f; padding:6px 10px; border-radius:8px; font-weight:700; }
    .badge-passed { background:#0d3d19; color:#38c172; padding:6px 10px; border-radius:8px; font-weight:700; }
    
    .param-table th { background: #2d3748; color: #00c6ff; }
    .param-value { font-weight: bold; font-size: 1.1em; }
    
    /* Print-specific styles */
    @media print {
      .no-print { display: none !important; }
      body { background: white; color: black; }
      .container { background: white; box-shadow: none; border: 1px solid #ddd; }
      .navbar { display: none; }
      .btn { display: none; }
      .card { border: 1px solid #ddd; }
      .card-body { color: black; }
      .param-table th { background: #f8f9fa !important; color: #000 !important; }
    }
  </style>
</head>
<body>
  <nav class="navbar navbar-expand-lg no-print">
    <div class="container-fluid">
      <a class="navbar-brand" href="#"><i class="fas fa-tint"></i> Water Quality Testing & Monitoring System</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item"><a class="nav-link" href="dashboard.php<?= $sid_q ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
          <li class="nav-item"><a class="nav-link" href="notifications.php<?= $sid_q ?>"><i class="fas fa-bell"></i> Notifications</a></li>
          <li class="nav-item"><a class="nav-link" href="stations.php<?= $sid_q ?>"><i class="fas fa-building"></i> Stations</a></li>
          <li class="nav-item"><a class="nav-link active" href="testresults.php<?= $sid_q ?>"><i class="fas fa-clipboard-list"></i> Test Results</a></li>

          <li class="nav-item">
            <?php
              $profilePic = (!empty($user['profile_pic'])) ? $user['profile_pic'] : 'https://cdn-icons-png.flaticon.com/512/847/847969.png';
            ?>
            <a class="nav-link d-flex align-items-center" href="account.php<?= $sid_q ?>" style="gap: 8px;">
              <img src="<?= htmlspecialchars($profilePic) ?>" alt="Account" class="rounded-circle account-icon">
              <span class="account-text">Account</span>
            </a>
          </li>

        </ul>
      </div>
    </div>
  </nav>

  <div class="container mt-4">
    <!-- Header with station info and action buttons -->
    <div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
      <div>
        <h2 class="text-white">
          <i class="fas fa-clipboard-check"></i> Water Quality Test Results
        </h2>
        <?php if ($station): ?>
          <div class="text-muted">
            <span class="me-3"><i class="fas fa-building me-1"></i> <?= htmlspecialchars($station['name']) ?></span>
            <span><i class="fas fa-map-marker-alt me-1"></i> <?= htmlspecialchars($station['location']) ?></span>
          </div>
        <?php endif; ?>
      </div>
      
      <div class="no-print">
        <button id="printBtn" class="btn btn-outline-light me-2">
          <i class="fas fa-print"></i> Print
        </button>
        <button id="downloadBtn" class="btn btn-primary">
          <i class="fas fa-download"></i> Download
        </button>
      </div>
    </div>

    <!-- Station status and info -->
    <div class="row mb-4">
      <div class="col-md-8 mx-auto text-center">
        <?php if ($station): ?>
          <div class="status-label status-online">ONLINE <span class="status-dot"></span></div>
          <div class="station-name"><?= htmlspecialchars($station['name']) ?></div>
          <div class="station-address"><?= htmlspecialchars($station['location']) ?></div>
          <div class="sensor-id"><p><strong>Sensor ID:</strong> <?= htmlspecialchars($station['device_sensor_id']) ?></p></div>
        <?php else: ?>
          <div class="status-label status-offline">OFFLINE <span class="status-dot"></span></div>
          <div class="station-name">No Station Selected</div>
          <div class="station-address">—</div>
          <div class="sensor-id"><p class="text-warning m-0">Please Select Station First!</p></div>
        <?php endif; ?>
        <div class="timestamp">Date: <span id="date"></span> | Time: <span id="time"></span></div>
      </div>
    </div>

    <!-- Alert for no station -->
    <?php if (!$station_id): ?>
      <div id="selectStationAlert" class="alert alert-warning mt-3 text-center">
        ⚠️ Please select a station first before viewing test results.
      </div>
    <?php endif; ?>

    <!-- TEST RUNS LIST -->
    <div class="mt-3">
      <h5 class="text-white mb-3">
        <i class="fas fa-history me-2"></i> Available Test Runs
        <?php if ($testRunsResult && $testRunsResult->num_rows > 0): ?>
          <span class="badge bg-primary"><?= $testRunsResult->num_rows ?> tests found</span>
        <?php endif; ?>
      </h5>

      <div id="testRunsList" class="d-flex flex-column gap-3">
        <?php if ($testRunsResult && $testRunsResult->num_rows > 0): ?>
          <?php while ($row = $testRunsResult->fetch_assoc()): ?>
            <?php
              $date = date("Y-m-d", strtotime($row['timestamp']));
              $time = date("h:i A", strtotime($row['timestamp']));
              $overall = overallStatus($row);
            ?>
            <div class="card bg-dark text-white shadow-sm border-0 rounded-3">
              <div class="card-header d-flex justify-content-between align-items-center" style="background: #2d3748;">
                <div>
                  <h6 class="mb-1 fw-bold">
                    <i class="fas fa-calendar-day me-2"></i> <?= $date ?>
                    <small class="text-secondary ms-2"><i class="fas fa-clock me-1"></i> <?= $time ?></small>
                  </h6>
                  <small class="text-muted">Test ID: <?= $row['waterdata_id'] ?></small>
                </div>
                <div>
                  <?= badgeHtml($overall) ?>
                </div>
              </div>
              <div class="card-body">
                <!-- Water Parameters Table - Single Column -->
                <div class="table-responsive">
                  <table class="table table-dark table-bordered param-table">
                    <thead>
                      <tr>
                        <th>Parameter</th>
                        <th>Value</th>
                        <th>Status</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr>
                        <td><strong>pH Level</strong></td>
                        <td class="param-value"><?= $row['ph_level'] ?? 'N/A' ?></td>
                        <td><?= paramStatusBadge('ph_level', $row['ph_level'] ?? null) ?></td>
                      </tr>
                      <tr>
                        <td><strong>Color</strong></td>
                        <td class="param-value"><?= $row['color'] ?? 'N/A' ?></td>
                        <td><?= paramStatusBadge('color', $row['color'] ?? null) ?></td>
                      </tr>
                      <tr>
                        <td><strong>Turbidity</strong></td>
                        <td class="param-value"><?= $row['turbidity'] ?? 'N/A' ?></td>
                        <td><?= paramStatusBadge('turbidity', $row['turbidity'] ?? null) ?></td>
                      </tr>
                      <tr>
                        <td><strong>TDS</strong></td>
                        <td class="param-value"><?= $row['tds'] ?? 'N/A' ?></td>
                        <td><?= paramStatusBadge('tds', $row['tds'] ?? null) ?></td>
                      </tr>
                      <tr>
                        <td><strong>Residual Chlorine</strong></td>
                        <td class="param-value"><?= $row['residual_chlorine'] ?? 'N/A' ?></td>
                        <td><?= paramStatusBadge('residual_chlorine', $row['residual_chlorine'] ?? null) ?></td>
                      </tr>
                      <tr>
                        <td><strong>Lead</strong></td>
                        <td class="param-value"><?= $row['lead'] ?? 'N/A' ?></td>
                        <td><?= paramStatusBadge('lead', $row['lead'] ?? null) ?></td>
                      </tr>
                      <tr>
                        <td><strong>Cadmium</strong></td>
                        <td class="param-value"><?= $row['cadmium'] ?? 'N/A' ?></td>
                        <td><?= paramStatusBadge('cadmium', $row['cadmium'] ?? null) ?></td>
                      </tr>
                      <tr>
                        <td><strong>Arsenic</strong></td>
                        <td class="param-value"><?= $row['arsenic'] ?? 'N/A' ?></td>
                        <td><?= paramStatusBadge('arsenic', $row['arsenic'] ?? null) ?></td>
                      </tr>
                      <tr>
                        <td><strong>Nitrate</strong></td>
                        <td class="param-value"><?= $row['nitrate'] ?? 'N/A' ?></td>
                        <td><?= paramStatusBadge('nitrate', $row['nitrate'] ?? null) ?></td>
                      </tr>
                      <tr>
                        <td colspan="3" class="text-center bg-secondary">
                          <small><strong>Device Sensor:</strong> <?= $row['device_sensor_id'] ?? 'N/A' ?></small>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
                
                <div class="d-flex justify-content-end gap-2 no-print mt-3">
                  <button class="btn btn-sm btn-success download-test" data-id="<?= $row['waterdata_id'] ?>">
                    <i class="fas fa-download"></i> Download Report
                  </button>
                </div>
              </div>
            </div>
          <?php endwhile; ?>
        <?php else: ?>
          <div class="text-center py-4">
            <i class="fas fa-vial fa-3x text-muted mb-3"></i>
            <p class="text-muted">No test results found for this station.</p>
            <?php if ($station): ?>
              <p class="text-warning">Device Sensor ID: <?= htmlspecialchars($station['device_sensor_id']) ?></p>
              <p class="text-info">Make sure water data is properly linked to this sensor ID.</p>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div> <!-- /.container -->

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    // Live date/time
    function updateTime() {
      const now = new Date();
      document.getElementById('time').textContent = now.toLocaleTimeString();
      document.getElementById('date').textContent = now.toLocaleDateString();
    }
    setInterval(updateTime, 1000);
    updateTime();

    // Print functionality
    document.getElementById('printBtn')?.addEventListener('click', function() {
      window.print();
    });

    // Download functionality
    document.getElementById('downloadBtn')?.addEventListener('click', function() {
      // Create a blob with the HTML content for download
      const content = document.documentElement.outerHTML;
      const blob = new Blob([content], { type: 'text/html' });
      const url = URL.createObjectURL(blob);
      
      // Create a temporary link and trigger download
      const a = document.createElement('a');
      a.href = url;
      a.download = 'water-quality-test-results-' + new Date().toISOString().slice(0, 10) + '.html';
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      URL.revokeObjectURL(url);
    });

    // Individual test download
    document.querySelectorAll('.download-test').forEach(button => {
      button.addEventListener('click', function(e) {
        e.stopPropagation(); // Prevent card click event
        const testId = this.getAttribute('data-id');
        
        // In a real implementation, you would fetch the specific test data
        // and generate a downloadable report
        alert('Downloading test ID: ' + testId);
        
        // For now, redirect to download.php with appropriate parameters
        window.open('download.php?station_id=<?= (int)$station_id; ?>&test_id=' + encodeURIComponent(testId) + '&action=download', '_blank');
      });
    });
  </script>
</body>
</html>