<?php 
session_start();
include("../includes/db.php");
include("../includes/fetch_user.php");

// Set timezone to Philippines
date_default_timezone_set('Asia/Manila');

// ---------------------------
// POST filters (page uses POST)
$filter_mode  = $_POST['filter_mode']  ?? ''; // expected: 'day' or 'month'
$filter_date  = $_POST['filter_date']  ?? ''; // yyyy-mm-dd
$filter_month = $_POST['filter_month'] ?? ''; // yyyy-mm
// ---------------------------

// Overall status - using the new thresholds (WARNING treated as PASSED)
function overallStatus($row) {
    // Helper: convert to float or return null if not numeric
    $toFloat = function($v) {
        return (is_numeric($v) || $v === '0' || $v === 0) ? (float)$v : null;
    };

    $hasFailed = false;

    // Evaluate parameters with the new thresholds - WARNING levels are treated as PASSED
    $v = $toFloat($row['color']);
    if ($v !== null && $v > 10.00) $hasFailed = true; // Only >10.00 is FAILED (==10.00 is WARNING but PASSED)

    $v = $toFloat($row['ph_level']);
    if ($v !== null && ($v < 5 || $v > 7)) $hasFailed = true; // Only <5 or >7 is FAILED (==7 is WARNING but PASSED)

    $v = $toFloat($row['turbidity']);
    if ($v !== null && $v > 5) $hasFailed = true; // Only >5 is FAILED (==5 is WARNING but PASSED)

    $v = $toFloat($row['tds']);
    if ($v !== null && $v > 10) $hasFailed = true; // Only >10 is FAILED (==10 is WARNING but PASSED)

    $v = $toFloat($row['lead']);
    if ($v !== null && $v > 0.01) $hasFailed = true; // Only >0.01 is FAILED (==0.01 is WARNING but PASSED)

    // Check other parameters with their existing thresholds
    $v = $toFloat($row['residual_chlorine']);
    if ($v !== null && ($v < 0.2 || $v > 1.0)) $hasFailed = true;

    $v = $toFloat($row['cadmium']);
    if ($v !== null && $v > 0.003) $hasFailed = true;

    $v = $toFloat($row['arsenic']);
    if ($v !== null && $v > 0.01) $hasFailed = true;

    $v = $toFloat($row['nitrate']);
    if ($v !== null && $v > 50) $hasFailed = true;

    return $hasFailed ? 'FAILED' : 'PASSED';
}

// Badge HTML
function badgeHtml($overall) {
    if ($overall === 'FAILED') return "<span class='badge badge-failed'>FAILED</span>";
    return "<span class='badge badge-passed'>PASSED</span>";
}

// Query results
$stations = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($filter_mode === 'day' && $filter_date || $filter_mode === 'month' && $filter_month)) {

    $fdate = $conn->real_escape_string($filter_date);
    $fmonth = $conn->real_escape_string($filter_month);

    $inner_where = "1";
    if ($filter_mode === 'day') {
        $inner_where = "DATE(timestamp) = '{$fdate}'";
    } elseif ($filter_mode === 'month') {
        $inner_where = "DATE_FORMAT(timestamp, '%Y-%m') = '{$fmonth}'";
    }

    // Get user role and LGU location
    $userRole = $user['role'] ?? '';
    $userLguLocation = $user['lgu_location'] ?? '';
    $userId = $_SESSION['user_id'] ?? 0;

    // Build the base query with role-based filtering
    $baseQuery = "SELECT 
            rs.station_id,
            rs.name AS station_name,
            rs.location AS station_location,
            rs.device_sensor_id,
            rs.lgu_location,
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
        LEFT JOIN (
            SELECT w1.*
            FROM water_data w1
            INNER JOIN (
                SELECT station_id, MAX(timestamp) AS latest
                FROM water_data
                WHERE {$inner_where}
                GROUP BY station_id
            ) w2 
              ON w1.station_id = w2.station_id
             AND w1.timestamp = w2.latest
        ) w 
          ON w.station_id = rs.station_id
        WHERE rs.name IS NOT NULL";

    // Add role-based filtering
    if ($userRole === 'lgu_menro' && !empty($userLguLocation)) {
        // LGU MENRO: Show all stations in their LGU location
        $sql = $baseQuery . " AND rs.lgu_location = '" . $conn->real_escape_string($userLguLocation) . "'";
    } elseif ($userRole === 'refilling_station_owner' || $userRole === 'personal_user') {
        // Refilling station owners and personal users: Show only their linked stations
        $sql = $baseQuery . " AND rs.station_id IN (
            SELECT station_id FROM user_stations WHERE user_id = " . (int)$userId . "
        )";
    } else {
        // Super admin or other roles: Show all stations (original behavior)
        $sql = $baseQuery;
    }

    $sql .= " ORDER BY rs.name ASC";

    $res = $conn->query($sql);
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            if (!$r['waterdata_id']) continue;
            $r['_overall'] = overallStatus($r);
            $stations[] = $r;
        }
        $res->free();
    }
}

// Format date for display
$displayDate = '';
if ($filter_mode === 'day' && $filter_date) {
    $displayDate = date('F j, Y', strtotime($filter_date));
} elseif ($filter_mode === 'month' && $filter_month) {
    $displayDate = date('F Y', strtotime($filter_month . '-01'));
}

// Get current date and time for report generation (Philippines time)
$dateGenerated = date('F j, Y h:i A');

// Separate stations by status
$passedStations = array_filter($stations, function($station) {
    return $station['_overall'] === 'PASSED';
});
$failedStations = array_filter($stations, function($station) {
    return $station['_overall'] === 'FAILED';
});

// Get user details for the report
$userRole = $user['role'] ?? '';
$userLocation = $user['lgu_location'] ?? '';
$userFullname = $user['fullname'] ?? '';
$userEmail = $user['email'] ?? '';
$userContact = $user['number'] ?? ''; // Using 'number' column from users table
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Reports - Water Quality</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"/>

  <!-- Include html2pdf library -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

  <style>
    /* SCREEN STYLES */
    body { 
        background:#0e1117; 
        color:#fff; 
        font-family:'Segoe UI',sans-serif; 
    }
    .navbar { 
        background:#1f2733; 
        box-shadow:0 2px 10px rgba(0,198,255,0.12); 
    }
    .navbar .nav-link,
    .navbar .navbar-brand,
    .navbar .nav-link i { 
        color: #fff !important; 
    }
    .navbar .nav-link:hover,
    .navbar .navbar-brand:hover { 
        color:#d9d9d9 !important; 
    }
    .account-icon { 
        width:24px; 
        height:24px; 
        object-fit:cover; 
        border:1px solid #fff; 
        box-shadow:0 0 8px #fff; 
    }
    .container-box { 
        background:#1f2733; 
        padding:22px; 
        border-radius:14px; 
        margin-top:20px; 
    }
    .badge-failed { 
        background:#dc3545; 
        color:#fff; 
        padding:6px 12px; 
        border-radius:12px; 
        font-weight:bold; 
    }
    .badge-passed { 
        background:#28a745; 
        color:#fff; 
        padding:6px 12px; 
        border-radius:12px; 
        font-weight:bold; 
    }
    .no-data { 
        text-align:center; 
        padding:40px 0; 
        color:#bbb; 
    }
    
    /* PRINT AREA STYLES (Visible on screen) */
    .print-area { 
        background:#fff !important; 
        color:#000 !important; 
        padding:30px; 
        border-radius:8px; 
        margin-top:20px; 
        box-shadow:0 0 10px rgba(0,0,0,0.1);
        display: block !important;
    }
    
    .table-light-custom { 
        background:#fff; 
        color:#000; 
    }
    .table-light-custom thead th { 
        background:#f8f9fa; 
        color:#000; 
        border:1px solid #dee2e6; 
        font-weight:600; 
    }
    .table-light-custom tbody td { 
        border:1px solid #dee2e6; 
        vertical-align:middle; 
    }
    
    .section-header { 
        background: #f8f9fa; 
        color: #000; 
        padding: 15px; 
        border-radius: 8px; 
        margin-bottom: 20px; 
        border-left: 5px solid;
        font-weight: bold;
        font-size: 18px;
    }
    .passed-header { border-left-color: #28a745; }
    .failed-header { border-left-color: #dc3545; }

    /* PRINT-ONLY ELEMENTS (Hidden on screen) */
    .print-only { 
        display: none !important; 
    }

    /* PRINT HEADER STYLES */
    .print-header { 
        text-align: center; 
        margin-bottom: 30px; 
        padding-bottom: 20px; 
        border-bottom: 2px solid #000;
    }
    .print-title { 
        font-size: 24px; 
        font-weight: bold; 
        margin-bottom: 10px;
    }
    .print-subtitle {
        font-size: 18px;
        margin-bottom: 5px;
    }
    .print-date {
        font-size: 16px;
        margin-bottom: 20px;
    }
    .print-conducted-by {
        margin-top: 40px;
        padding-top: 20px;
        border-top: 1px solid #dee2e6;
    }
    .print-conducted-by table {
        width: 100%;
        border-collapse: collapse;
    }
    .print-conducted-by th,
    .print-conducted-by td {
        border: 1px solid #dee2e6;
        padding: 8px;
        text-align: left;
    }
    .print-conducted-by th {
        background: #f8f9fa;
        font-weight: bold;
    }

    /* PRINT STYLES */
    @media print {
        /* Hide everything except the print area */
        body * {
            visibility: hidden;
            margin: 0 !important;
            padding: 0 !important;
        }
        
        .print-area,
        .print-area * {
            visibility: visible;
        }
        
        .print-area {
            position: absolute !important;
            left: 0 !important;
            top: 0 !important;
            width: 100% !important;
            height: auto !important;
            background: white !important;
            color: black !important;
            margin: 0 !important;
            padding: 20px !important;
            box-shadow: none !important;
            border: none !important;
            border-radius: 0 !important;
            font-size: 12px !important;
        }
        
        /* Show print-only elements */
        .print-only {
            display: block !important;
            visibility: visible !important;
        }
        
        /* Hide non-print elements */
        .no-print {
            display: none !important;
        }
        
        /* Adjust table for print */
        .table-light-custom {
            width: 100% !important;
            font-size: 10px !important;
        }
        
        .table-light-custom th,
        .table-light-custom td {
            padding: 4px !important;
        }
        
        /* Adjust badges for print */
        .badge-failed, 
        .badge-passed {
            border: 1px solid #000 !important;
            background: transparent !important;
            color: #000 !important;
            padding: 2px 6px !important;
            font-size: 10px !important;
        }
        
        /* Print header styles */
        .print-header { 
            margin-bottom: 20px !important; 
            padding-bottom: 15px !important; 
        }
        .print-title { 
            font-size: 18px !important; 
            margin-bottom: 5px !important;
        }
        .print-subtitle {
            font-size: 14px !important;
            margin-bottom: 5px !important;
        }
        .print-date {
            font-size: 12px !important;
            margin-bottom: 15px !important;
        }
        
        /* Page breaks */
        .section-header {
            page-break-after: avoid;
        }
        table {
            page-break-inside: auto;
        }
        tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }
    }
  </style>
</head>
<body>
  <nav class="navbar navbar-expand-lg no-print">
    <div class="container-fluid">
      <a class="navbar-brand" href="#"><i class="fas fa-tint"></i> Water Quality Testing & Monitoring System</a>

      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
          <li class="nav-item"><a class="nav-link" href="stations.php"><i class="fas fa-building"></i> Devices/Stations</a></li>
          <li class="nav-item"><a class="nav-link" href="reports.php"><i class="fas fa-chart-line"></i> Reports</a></li>
          <li class="nav-item"><a class="nav-link" href="notifications.php"><i class="fas fa-bell"></i> Notifications</a></li>
          
          <li class="nav-item">
            <a class="nav-link d-flex align-items-center" href="account.php" style="gap:8px;">
                <img 
                  src="<?php echo !empty($user['profile_pic']) ? htmlspecialchars($user['profile_pic']) : 'https://cdn-icons-png.flaticon.com/512/847/847969.png'; ?>" 
                  class="rounded-circle account-icon">
                <span>Account</span>
            </a>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <div class="container container-box no-print">
    <h3 class="mb-3">Reports</h3>

    <!-- filter form -->
    <form method="POST" class="row g-2 align-items-center mb-4 no-print">
      <div class="col-auto">
        <select name="filter_mode" class="form-select bg-dark text-white border-secondary" onchange="this.form.submit()">
          <option value="">-- Choose filter --</option>
          <option value="day" <?= $filter_mode==='day'?'selected':''; ?>>Specific Day</option>
          <option value="month" <?= $filter_mode==='month'?'selected':''; ?>>Month</option>
        </select>
      </div>

      <?php if ($filter_mode === 'day'): ?>
        <div class="col-auto">
          <input type="date" name="filter_date" class="form-control bg-dark text-white border-secondary" value="<?= htmlspecialchars($filter_date) ?>" onchange="this.form.submit()">
        </div>
      <?php elseif ($filter_mode === 'month'): ?>
        <div class="col-auto">
          <input type="month" name="filter_month" class="form-control bg-dark text-white border-secondary" value="<?= htmlspecialchars($filter_month) ?>" onchange="this.form.submit()">
        </div>
      <?php else: ?>
        <div class="col-auto">
          <small class="text-muted">Select "Specific Day" or "Month" to display results.</small>
        </div>
      <?php endif; ?>

      <?php if (!empty($stations)): ?>
        <div class="col-auto ms-auto">
          <button id="printBtn" type="button" class="btn btn-outline-light"><i class="fas fa-print"></i> Print</button>
          <button id="downloadPdf" type="button" class="btn btn-success"><i class="fas fa-download"></i> Download PDF</button>
        </div>
      <?php endif; ?>
    </form>

    <?php if (!empty($stations)): ?>
      <div class="row mb-3 no-print">
        <div class="col-md-6">
          <div class="input-group">
            <span class="input-group-text bg-dark text-white border-secondary">
              <i class="fas fa-search"></i>
            </span>
            <input 
              id="searchBox" 
              class="form-control bg-dark text-white border-secondary" 
              placeholder="Search station name..."
            />
          </div>
        </div>
      </div>

      <!-- PASSED / FAILED BUTTONS -->
      <div class="row mb-3 no-print">
        <div class="col-auto d-flex gap-2">
          <button type="button" id="goPassed" class="btn btn-success">
            <i class="fas fa-check-circle"></i> PASSED
          </button>
          <button type="button" id="goFailed" class="btn btn-danger">
            <i class="fas fa-times-circle"></i> FAILED
          </button>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <!-- PRINT AREA - Separate from main container -->
  <div class="print-area" id="printArea">
    <?php if (empty($stations)): ?>
      <div class="no-data" style="color:#666;">
        <p><strong>No results.</strong></p>
        <p>Select a day or month to view results.</p>
      </div>
    <?php else: ?>
      <!-- PRINT HEADER - Only visible in print/PDF -->
      <div class="print-only print-header">
        <div class="print-title">
          WATER QUALITY TESTING & MONITORING SYSTEM
        </div>
        <?php if ($userRole === 'lgu_menro' && !empty($userLocation)): ?>
          <div class="print-subtitle">
            LGU MENRO - <?= strtoupper(htmlspecialchars($userLocation)) ?>
          </div>
        <?php endif; ?>
        <div class="print-date">
          DATE GENERATED: <span id="dateGeneratedPlaceholder"><?= strtoupper($dateGenerated) ?></span>
        </div>
      </div>

      <!-- PASSED STATIONS SECTION -->
      <?php if (!empty($passedStations)): ?>
        <div class="section-header passed-header">
          ✅ PASSED WATER QUALITY RESULTS FOR <?= strtoupper($displayDate) ?>
        </div>
        <div class="table-responsive mb-5">
          <table class="table table-light-custom">
            <thead>
              <tr>
                <th>Station</th>
                <th>Location</th>
                <th>Latest Test</th>
                <th>Status</th>
                <th class="no-print">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($passedStations as $s): ?>
                <tr class="result-row passed-row">
                  <td class="station-name"><?= htmlspecialchars($s['station_name']) ?></td>
                  <td><?= htmlspecialchars($s['station_location']) ?></td>
                  <td><?= htmlspecialchars(date("Y-m-d h:i A", strtotime($s['timestamp']))) ?></td>
                  <td><?= badgeHtml($s['_overall']) ?></td>
                  <td class="no-print">
                    <button class="btn btn-sm btn-info view-test" 
                       data-station-id="<?= urlencode($s['station_id']) ?>" 
                       data-test-date="<?= urlencode(substr($s['timestamp'], 0, 10)) ?>">
                      <i class="fas fa-eye"></i> View
                    </button>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>

      <!-- FAILED STATIONS SECTION -->
      <?php if (!empty($failedStations)): ?>
        <div class="section-header failed-header">
          ❌ FAILED WATER QUALITY RESULTS FOR <?= strtoupper($displayDate) ?>
        </div>
        <div class="table-responsive">
          <table class="table table-light-custom">
            <thead>
              <tr>
                <th>Station</th>
                <th>Location</th>
                <th>Latest Test</th>
                <th>Status</th>
                <th class="no-print">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($failedStations as $s): ?>
                <tr class="result-row failed-row">
                  <td class="station-name"><?= htmlspecialchars($s['station_name']) ?></td>
                  <td><?= htmlspecialchars($s['station_location']) ?></td>
                  <td><?= htmlspecialchars(date("Y-m-d h:i A", strtotime($s['timestamp']))) ?></td>
                  <td><?= badgeHtml($s['_overall']) ?></td>
                  <td class="no-print">
                    <button class="btn btn-sm btn-info view-test" 
                       data-station-id="<?= urlencode($s['station_id']) ?>" 
                       data-test-date="<?= urlencode(substr($s['timestamp'], 0, 10)) ?>">
                      <i class="fas fa-eye"></i> View
                    </button>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>

      <!-- TEST CONDUCTED BY SECTION - Only visible in print/PDF -->
      <div class="print-only print-conducted-by">
        <h4>Test Conducted By:</h4>
        <table class="table table-bordered">
          <tr>
            <th>Name</th>
            <td><?= !empty($userFullname) ? htmlspecialchars($userFullname) : 'N/A' ?></td>
          </tr>
          <tr>
            <th>Email</th>
            <td><?= !empty($userEmail) ? htmlspecialchars($userEmail) : 'N/A' ?></td>
          </tr>
          <tr>
            <th>Contact No.</th>
            <td><?= !empty($userContact) ? htmlspecialchars($userContact) : 'N/A' ?></td>
          </tr>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

  <script>
  // Function to get accurate Philippines time
  function getPhilippinesTime() {
    const now = new Date();
    
    // Convert to Philippines time (UTC+8)
    const philippinesOffset = 8 * 60; // UTC+8 in minutes
    const localOffset = now.getTimezoneOffset(); // in minutes
    const philippinesTime = new Date(now.getTime() + (localOffset + philippinesOffset) * 60000);
    
    // Format the date manually to ensure correct format
    const months = [
        'JANUARY', 'FEBRUARY', 'MARCH', 'APRIL', 'MAY', 'JUNE',
        'JULY', 'AUGUST', 'SEPTEMBER', 'OCTOBER', 'NOVEMBER', 'DECEMBER'
    ];
    
    const month = months[philippinesTime.getMonth()];
    const day = philippinesTime.getDate();
    const year = philippinesTime.getFullYear();
    
    let hours = philippinesTime.getHours();
    const minutes = philippinesTime.getMinutes().toString().padStart(2, '0');
    const ampm = hours >= 12 ? 'PM' : 'AM';
    
    // Convert to 12-hour format
    hours = hours % 12;
    hours = hours ? hours : 12; // the hour '0' should be '12'
    
    return `${month} ${day}, ${year} ${hours}:${minutes} ${ampm}`;
  }

  // Function to update date generated with current Philippines time
  function updateDateGenerated() {
    const dateString = getPhilippinesTime();
    
    // Update all instances in the current page
    document.querySelectorAll('#dateGeneratedPlaceholder').forEach(el => {
        el.textContent = dateString;
    });
    
    return dateString;
  }

  // Update date when page loads
  document.addEventListener('DOMContentLoaded', function() {
    updateDateGenerated();
    
    // Also update every minute to keep time accurate
    setInterval(updateDateGenerated, 60000);
  });

  // Search filter
  const searchBox = document.getElementById('searchBox');
  if (searchBox) {
    searchBox.addEventListener('input', (e) => {
      const q = e.target.value.trim().toLowerCase();
      document.querySelectorAll('.result-row').forEach(row => {
        const name = row.querySelector('.station-name').textContent.toLowerCase();
        row.style.display = name.includes(q) ? '' : 'none';
      });
    });
  }

  // Print function - update time before printing
  const printBtn = document.getElementById('printBtn');
  if (printBtn) {
    printBtn.addEventListener('click', () => {
      updateDateGenerated();
      setTimeout(() => {
        window.print();
      }, 100);
    });
  }

  // PDF Download function - creates exact replica of print view
  const downloadPdf = document.getElementById('downloadPdf');
  if (downloadPdf) {
    downloadPdf.addEventListener('click', function() {
      const currentDate = updateDateGenerated();
      
      // Show loading state
      const originalText = downloadPdf.innerHTML;
      downloadPdf.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating PDF...';
      downloadPdf.disabled = true;

      // Create a complete HTML document that exactly replicates the print view
      const printContent = `
        <!DOCTYPE html>
        <html>
        <head>
          <title>Water Quality Report - <?= $displayDate ?></title>
          <style>
            body { 
              font-family: Arial, sans-serif; 
              margin: 0 auto; 
              padding: 20px; 
              color: #000; 
              background: white;
              font-size: 12px;
              max-width: 100%;
              box-sizing: border-box;
            }
            .print-header { 
              text-align: center; 
              margin-bottom: 20px; 
              padding-bottom: 15px; 
              border-bottom: 2px solid #000;
            }
            .print-title { 
              font-size: 18px; 
              font-weight: bold; 
              margin-bottom: 5px;
            }
            .print-subtitle {
              font-size: 14px;
              margin-bottom: 5px;
            }
            .print-date {
              font-size: 12px;
              margin-bottom: 15px;
            }
            .section-header { 
              background: #f8f9fa; 
              color: #000; 
              padding: 12px; 
              border-radius: 6px; 
              margin-bottom: 15px; 
              border-left: 5px solid;
              font-weight: bold;
              font-size: 16px;
              page-break-after: avoid;
            }
            .passed-header { border-left-color: #28a745; }
            .failed-header { border-left-color: #dc3545; }
            table { 
              width: 100%; 
              border-collapse: collapse; 
              margin-bottom: 15px;
              font-size: 10px;
              page-break-inside: auto;
            }
            th, td { 
              border: 1px solid #dee2e6; 
              padding: 6px; 
              text-align: left; 
            }
            th { 
              background: #f8f9fa; 
              font-weight: bold; 
            }
            .badge { 
              padding: 3px 8px; 
              border-radius: 3px; 
              font-weight: bold; 
              border: 1px solid #000;
              font-size: 9px;
              display: inline-block;
            }
            .badge-failed { background: transparent; color: #000; }
            .badge-passed { background: transparent; color: #000; }
            .print-conducted-by {
              margin-top: 30px;
              padding-top: 15px;
              border-top: 1px solid #dee2e6;
              font-size: 11px;
            }
            .print-conducted-by table {
              font-size: 11px;
              width: 100%;
            }
            @media print {
              body { margin: 0 auto; padding: 20px; }
            }
          </style>
        </head>
        <body>
          <div class="print-header">
            <div class="print-title">
              WATER QUALITY TESTING & MONITORING SYSTEM
            </div>
            <?php if ($userRole === 'lgu_menro' && !empty($userLocation)): ?>
              <div class="print-subtitle">
                LGU MENRO - <?= strtoupper(htmlspecialchars($userLocation)) ?>
              </div>
            <?php endif; ?>
            <div class="print-date">
              DATE GENERATED: ${currentDate}
            </div>
          </div>

          <?php if (!empty($passedStations)): ?>
            <div class="section-header passed-header">
              ✅ PASSED WATER QUALITY RESULTS FOR <?= strtoupper($displayDate) ?>
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
                <?php foreach ($passedStations as $s): ?>
                  <tr>
                    <td><?= htmlspecialchars($s['station_name']) ?></td>
                    <td><?= htmlspecialchars($s['station_location']) ?></td>
                    <td><?= htmlspecialchars(date("Y-m-d h:i A", strtotime($s['timestamp']))) ?></td>
                    <td><?= $s['_overall'] === 'FAILED' ? '<span class="badge badge-failed">FAILED</span>' : '<span class="badge badge-passed">PASSED</span>' ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php endif; ?>

          <?php if (!empty($failedStations)): ?>
            <div class="section-header failed-header">
              ❌ FAILED WATER QUALITY RESULTS FOR <?= strtoupper($displayDate) ?>
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
                <?php foreach ($failedStations as $s): ?>
                  <tr>
                    <td><?= htmlspecialchars($s['station_name']) ?></td>
                    <td><?= htmlspecialchars($s['station_location']) ?></td>
                    <td><?= htmlspecialchars(date("Y-m-d h:i A", strtotime($s['timestamp']))) ?></td>
                    <td><?= $s['_overall'] === 'FAILED' ? '<span class="badge badge-failed">FAILED</span>' : '<span class="badge badge-passed">PASSED</span>' ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php endif; ?>

          <div class="print-conducted-by">
            <h4 style="margin: 0 0 10px 0; font-size: 14px;">Test Conducted By:</h4>
            <table>
              <tr>
                <th>Name</th>
                <td><?= !empty($userFullname) ? htmlspecialchars($userFullname) : 'N/A' ?></td>
              </tr>
              <tr>
                <th>Email</th>
                <td><?= !empty($userEmail) ? htmlspecialchars($userEmail) : 'N/A' ?></td>
              </tr>
              <tr>
                <th>Contact No.</th>
                <td><?= !empty($userContact) ? htmlspecialchars($userContact) : 'N/A' ?></td>
              </tr>
            </table>
          </div>
        </body>
        </html>
      `;

      // Create a temporary element to hold our print content
      const tempElement = document.createElement('div');
      tempElement.innerHTML = printContent;
      
      const opt = {
        margin: [0.5, 0.5, 0.5, 0.5], // Normal margins
        filename: 'water_quality_report_<?= $displayDate ?>.pdf',
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { 
          scale: 2,
          useCORS: true,
          logging: false
        },
        jsPDF: { 
          unit: 'in', 
          format: 'letter', 
          orientation: 'portrait' 
        },
        pagebreak: { mode: 'avoid-all' } // Prevent page breaks
      };

      // Generate PDF from our custom HTML content
      html2pdf().set(opt).from(tempElement).save().then(() => {
        // Reset button state
        downloadPdf.innerHTML = originalText;
        downloadPdf.disabled = false;
      }).catch((error) => {
        console.error('PDF generation failed:', error);
        alert('Failed to generate PDF. Please try again.');
        downloadPdf.innerHTML = originalText;
        downloadPdf.disabled = false;
      });
    });
  }

  // Scroll to PASSED or FAILED sections
  function scrollToStatus(status) {
    if (status === "PASSED") {
      const passedHeader = document.querySelector('.passed-header');
      if (passedHeader) {
        passedHeader.scrollIntoView({ behavior: "smooth", block: "center" });
      }
    } else if (status === "FAILED") {
      const failedHeader = document.querySelector('.failed-header');
      if (failedHeader) {
        failedHeader.scrollIntoView({ behavior: "smooth", block: "center" });
      }
    }
  }

  document.getElementById("goPassed")?.addEventListener("click", () => {
    scrollToStatus("PASSED");
  });

  document.getElementById("goFailed")?.addEventListener("click", () => {
    scrollToStatus("FAILED");
  });

  // View button click handler
  document.querySelectorAll('.view-test').forEach(button => {
    button.addEventListener('click', function() {
      const stationId = this.getAttribute('data-station-id');
      const testDate = this.getAttribute('data-test-date');
      
      if (stationId) {
        const url = `download.php?station_id=${stationId}&date=${testDate}`;
        window.open(url, '_blank');
      }
    });
  });

  // Add keyboard shortcuts
  document.addEventListener('keydown', function(e) {
    if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
      e.preventDefault();
      updateDateGenerated();
      setTimeout(() => {
        window.print();
      }, 100);
    }
  });
  </script>
</body>
</html>