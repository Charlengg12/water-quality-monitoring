<?php
// pages/download.php
session_start();
include("../includes/db.php"); // mysqli connection $conn

// Set timezone to Philippines
date_default_timezone_set('Asia/Manila');

$stationId = isset($_GET['station_id']) ? (int)$_GET['station_id'] : null;
$testId    = isset($_GET['test_id']) ? (int)$_GET['test_id'] : null;
$date      = isset($_GET['date']) ? trim($_GET['date']) : null;

// --- Fetch logged-in user info ---
$user = null;
if (isset($_SESSION['user_id'])) {
    $sql = "SELECT fullname, email, number FROM users WHERE user_id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $res = $stmt->get_result();
        $user = $res->fetch_assoc();
        $stmt->close();
    }
}

// --- Fetch station info ---
$station = null;
if ($stationId) {
    $sql = "SELECT * FROM refilling_stations WHERE station_id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $stationId);
        $stmt->execute();
        $res = $stmt->get_result();
        $station = $res->fetch_assoc();
        $stmt->close();
    }
}

// --- Fetch water data - FIXED TO GET ONLY ONE RESULT ---
$result = null; // Changed to single result
if ($stationId) {
    if ($testId) {
        // Get specific test by ID
        $sql = "SELECT * FROM water_data WHERE waterdata_id = ? AND station_id = ? LIMIT 1";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("ii", $testId, $stationId);
            $stmt->execute();
            $res = $stmt->get_result();
            $result = $res->fetch_assoc(); // Single result
            $stmt->close();
        }
    } elseif ($date) {
        // Get latest test for specific date
        $sql = "SELECT * FROM water_data WHERE station_id = ? AND DATE(timestamp) = ? ORDER BY timestamp DESC LIMIT 1";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("is", $stationId, $date);
            $stmt->execute();
            $res = $stmt->get_result();
            $result = $res->fetch_assoc(); // Single result
            $stmt->close();
        }
    } else {
        // Get latest test overall
        $sql = "SELECT * FROM water_data WHERE station_id = ? ORDER BY timestamp DESC LIMIT 1";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("i", $stationId);
            $stmt->execute();
            $res = $stmt->get_result();
            $result = $res->fetch_assoc(); // Single result
            $stmt->close();
        }
    }
}

// Pick display date
$displayDate = $date;
if (!$displayDate && $result && isset($result['timestamp'])) {
    $displayDate = substr($result['timestamp'], 0, 10);
}

// Get current date and time for report generation (Philippines time)
$dateGenerated = date('F j, Y h:i A');

//statement
$summaryText = null;
$summaryBg = "#28a745"; // green default
$summaryColor = "#fff";

if ($result) {
    $primary = $result; // use the single result

    // helper: convert to float or return null if not numeric
    $toFloat = function($v) {
        return (is_numeric($v) || $v === '0' || $v === 0) ? (float)$v : null;
    };

    $hasWarning = false;
    $hasFailed  = false;

    // Check each parameter against new thresholds
    $v = $toFloat($primary['color']);
    if ($v !== null) {
        if ($v == 10.00) $hasWarning = true;
        elseif ($v > 10.00) $hasFailed = true;
    }

    $v = $toFloat($primary['ph_level']);
    if ($v !== null) {
        if ($v == 7) $hasWarning = true;
        elseif ($v < 5 || $v > 7) $hasFailed = true;
    }

    $v = $toFloat($primary['turbidity']);
    if ($v !== null) {
        if ($v == 5) $hasWarning = true;
        elseif ($v > 5) $hasFailed = true;
    }

    $v = $toFloat($primary['tds']);
    if ($v !== null) {
        if ($v == 10) $hasWarning = true;
        elseif ($v > 10) $hasFailed = true;
    }

    $v = $toFloat($primary['lead']);
    if ($v !== null) {
        if ($v == 0.01) $hasWarning = true;
        elseif ($v > 0.01) $hasFailed = true;
    }

    // Decide summary text / color (matches your requested wording)
    if ($hasFailed) {
        $summaryText  = "❌ Test Failed — One or more parameter/s exceeded safe limits. Immediate action required.";
        $summaryBg    = "#dc3545"; // red
        $summaryColor = "#ffffff";
    } elseif ($hasWarning) {
        $summaryText  = "⚠️ Test Passed with Warning — One or more parameter/s reached warning levels. Attention is advised.";
        $summaryBg    = "#ffc107"; // yellow
        $summaryColor = "#000000";
    } else {
        $summaryText  = "✅ Test Passed — All parameters are within safe or neutral limits.";
        $summaryBg    = "#28a745"; // green
        $summaryColor = "#ffffff";
    }
}

// --- Helper function for status badge ---
function getParameterStatus($parameter, $value) {
    $value = is_numeric($value) || $value === '0' || $value === 0 ? (float)$value : null;
    
    if ($value === null) return ['status' => 'unknown', 'text' => 'Unknown'];
    
    switch ($parameter) {
        case 'color':
            if ($value < 9) return ['status' => 'safe', 'text' => 'Safe'];
            if ($value >= 9 && $value < 10) return ['status' => 'neutral', 'text' => 'Neutral'];
            if ($value == 10.00) return ['status' => 'warning', 'text' => 'Warning'];
            if ($value > 10.00) return ['status' => 'failed', 'text' => 'Failed'];
            break;
            
        case 'ph_level':
            if ($value >= 5 && $value < 6) return ['status' => 'safe', 'text' => 'Safe'];
            if ($value >= 6 && $value < 7) return ['status' => 'neutral', 'text' => 'Neutral'];
            if ($value == 7) return ['status' => 'warning', 'text' => 'Warning'];
            if ($value < 5 || $value > 7) return ['status' => 'failed', 'text' => 'Failed'];
            break;
            
        case 'turbidity':
            if ($value < 4) return ['status' => 'safe', 'text' => 'Safe'];
            if ($value >= 4 && $value < 5) return ['status' => 'neutral', 'text' => 'Neutral'];
            if ($value == 5) return ['status' => 'warning', 'text' => 'Warning'];
            if ($value > 5) return ['status' => 'failed', 'text' => 'Failed'];
            break;
            
        case 'tds':
            if ($value < 9) return ['status' => 'safe', 'text' => 'Safe'];
            if ($value >= 9 && $value < 10) return ['status' => 'neutral', 'text' => 'Neutral'];
            if ($value == 10) return ['status' => 'warning', 'text' => 'Warning'];
            if ($value > 10) return ['status' => 'failed', 'text' => 'Failed'];
            break;
            
        case 'lead':
            if ($value < 0.009) return ['status' => 'safe', 'text' => 'Safe'];
            if ($value >= 0.009 && $value < 0.01) return ['status' => 'neutral', 'text' => 'Neutral'];
            if ($value == 0.01) return ['status' => 'warning', 'text' => 'Warning'];
            if ($value > 0.01) return ['status' => 'failed', 'text' => 'Failed'];
            break;
    }
    
    return ['status' => 'unknown', 'text' => 'Unknown'];
}

function badge($text, $type = "safe") {
    $class = "badge-" . strtolower($type);
    return "<span class='badge $class'>$text</span>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Download Report</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <style>
    body { background:#f0f0f0; font-family:Arial, sans-serif; margin:0; padding:20px; }
    .btn { background:#007bff; color:#fff; border:none; padding:8px 16px; border-radius:4px; cursor:pointer; margin-left:5px; }
    .btn:hover { background:#0056b3; }
    .btn:disabled { background:#6c757d; cursor:not-allowed; }
    .pdf-document { background:#fff; color:#000; width:800px; min-height:1000px; margin:20px auto; padding:40px; border:1px solid #ccc; box-shadow:0 0 10px rgba(0,0,0,0.2); display:flex; flex-direction:column; justify-content:space-between; }
    h2,h3 { margin:0; }
    .meta { text-align:center; margin:10px 0 20px; font-size:14px; color:#333; }
    .date-generated { text-align:center; margin:5px 0 10px; font-size:14px; color:#333; font-weight:bold; }
    table { width:100%; border-collapse:collapse; margin-top:20px; }
    table, th, td { border:1px solid #000; }
    th, td { padding:8px; text-align:center; }
    .no-results { text-align:center; padding:40px 0; color:#666; }
    .footer { text-align:right; margin-top:50px; font-size:14px; padding-top:40px; }
    /* Badge styles */
    .badge { 
      padding: 4px 10px; 
      border-radius: 12px; 
      font-size: 12px; 
      color: #fff; 
      font-weight: bold; 
    }
    .badge-safe { background: #0051ff; }
    .badge-neutral { background: #28a745; }
    .badge-warning { background: #ff8800; }
    .badge-failed { background: #dc3545; }
    .badge-unknown { background: #6c757d; }

    /* --- Print fixes for badge colors --- */
    @media print {
      body { 
        -webkit-print-color-adjust: exact !important; 
        print-color-adjust: exact !important;
        background: white !important;
        margin: 0 !important;
        padding: 0 !important;
      }
      
      .btn { display: none !important; }

      .badge-safe    { background: #0051ff !important; color:#fff !important; border:1px solid #0051ff; }
      .badge-neutral { background: #28a745 !important; color:#fff !important; border:1px solid #28a745; }
      .badge-warning { background: #ff8800 !important; color:#fff !important; border:1px solid #ff8800; }
      .badge-failed  { background: #dc3545 !important; color:#fff !important; border:1px solid #dc3545; }
      .badge-unknown { background: #6c757d !important; color:#fff !important; border:1px solid #6c757d; }
      
      .pdf-document { 
        box-shadow: none !important; 
        border: none !important; 
        margin: 0 !important; 
        padding: 20px !important;
        width: 100% !important;
      }
    }
    
    .loading { opacity: 0.7; pointer-events: none; }
  </style>
</head>
<body>
  <div style="text-align:right; margin-bottom:20px;">
    <button id="downloadBtn" class="btn">Download PDF</button>
    <button id="printBtn" class="btn">Print</button>
  </div>

  <div class="pdf-document" id="reportContent">
    <div>
      <h2 style="text-align:center;"><br>WATER QUALITY TESTING & MONITORING SYSTEM<br><br> WATER QUALITY TEST RESULTS<br></h2>
      <h3 style="text-align:center; margin-top:8px;"><br><?= $station ? htmlspecialchars($station['name']) : 'Unknown Station' ?></h3>

      <?php if ($station && !empty($station['location'])): ?>
        <p style="text-align:center; margin:4px 0; font-size:14px; color:#000000;">
          <span style="font-weight:bold;">📍 Address:</span> <?= htmlspecialchars($station['location']) ?>
        </p>
      <?php endif; ?>

      <?php if (!empty($station['device_sensor_id'])): ?>
    <p style="text-align:center; margin:2px 0; font-size:14px; color:#000000;">
      <span style="font-weight:bold;">Sensor ID:</span> <?= htmlspecialchars($station['device_sensor_id']) ?>
    </p>
  <?php endif; ?>

      <!-- DATE GENERATED SECTION - ADDED ABOVE DATE AND TIME TESTED -->
      <div class="date-generated">
        <strong>DATE GENERATED:</strong> <span id="dateGeneratedPlaceholder"><?= strtoupper($dateGenerated) ?></span>
      </div>

      <?php if ($result): ?>
        <?php 
          $runDate = date("Y-m-d", strtotime($result['timestamp']));
          $runTime = date("h:i A", strtotime($result['timestamp']));
        ?>
        <p class="meta">
          <strong>DATE AND TIME TESTED:</strong> <?= htmlspecialchars($runDate) ?>  
          <strong>|</strong> <?= htmlspecialchars($runTime) ?>
        </p>
      <?php else: ?>
        <p class="meta"><strong>DATE AND TIME TESTED:</strong> — <strong>|</strong> —</p>
      <?php endif; ?>

      <?php if (!$result): ?>
        <div class="no-results">No results found for this station/date.</div>
      <?php else: ?>
        <br><br>
        <table>
          <thead>
            <tr><th>Parameter</th><th>Value</th><th>Status</th></tr>
          </thead>
          <tbody>
            <!-- SINGLE RESULT DISPLAY - NO LOOP -->
            <?php
            $colorStatus = getParameterStatus('color', $result['color']);
            $phStatus = getParameterStatus('ph_level', $result['ph_level']);
            $turbidityStatus = getParameterStatus('turbidity', $result['turbidity']);
            $tdsStatus = getParameterStatus('tds', $result['tds']);
            $leadStatus = getParameterStatus('lead', $result['lead']);
            ?>
            <tr><td>Color (CU)</td><td><?= htmlspecialchars($result['color']) ?></td><td><?= badge($colorStatus['text'], $colorStatus['status']) ?></td></tr>
            <tr><td>pH</td><td><?= htmlspecialchars($result['ph_level']) ?></td><td><?= badge($phStatus['text'], $phStatus['status']) ?></td></tr>
            <tr><td>Turbidity (NTU)</td><td><?= htmlspecialchars($result['turbidity']) ?></td><td><?= badge($turbidityStatus['text'], $turbidityStatus['status']) ?></td></tr>
            <tr><td>Total Dissolved Solids (mg/L)</td><td><?= htmlspecialchars($result['tds']) ?></td><td><?= badge($tdsStatus['text'], $tdsStatus['status']) ?></td></tr>
            <tr><td>Lead (mg/L)</td><td><?= htmlspecialchars($result['lead']) ?></td><td><?= badge($leadStatus['text'], $leadStatus['status']) ?></td></tr>
          </tbody>
        </table>
      <?php endif; ?>

      <?php if ($summaryText !== null): ?>
  <div style="text-align:center; margin:10px 0 20px; padding:12px; border-radius:8px;
              background:<?= htmlspecialchars($summaryBg) ?>; color:<?= htmlspecialchars($summaryColor) ?>;
              font-weight:700; box-shadow: 0 2px 8px rgba(0,0,0,0.12);">
    <?= htmlspecialchars($summaryText) ?>
  </div>
<?php endif; ?>

      <?php if ($user): ?>
        <br>
        <h3 style="margin-top:30px;">Test Conducted By:</h3>
        <table style="width:100%; border-collapse:collapse; margin-top:10px;">
          <tr>
            <th style="border:1px solid #000; padding:8px; text-align:left;">Name</th>
            <td style="border:1px solid #000; padding:8px;"><?= htmlspecialchars($user['fullname']) ?></td>
          </tr>
          <tr>
            <th style="border:1px solid #000; padding:8px; text-align:left;">Email</th>
            <td style="border:1px solid #000; padding:8px;"><?= htmlspecialchars($user['email']) ?></td>
          </tr>
          <tr>
            <th style="border:1px solid #000; padding:8px; text-align:left;">Contact No.</th>
            <td style="border:1px solid #000; padding:8px;"><?= htmlspecialchars($user['number']) ?></td>
          </tr>
        </table>
      <?php endif; ?>
    </div>
  </div>

  <!-- JS Libraries -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

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
    });

    // Download PDF button - Fixed version
    document.getElementById("downloadBtn").addEventListener("click", function () {
        const currentDate = updateDateGenerated();
        console.log("Download button clicked");
        
        // Check if libraries are loaded
        if (typeof html2canvas === 'undefined') {
            alert("Error: html2canvas library not loaded. Please check your internet connection.");
            return;
        }
        
        if (typeof jspdf === 'undefined') {
            alert("Error: jsPDF library not loaded. Please check your internet connection.");
            return;
        }

        const { jsPDF } = window.jspdf;
        const element = document.getElementById("reportContent");
        
        // Show loading state
        const originalText = this.textContent;
        this.textContent = "Generating PDF...";
        this.disabled = true;
        document.body.classList.add('loading');

        console.log("Starting html2canvas...");
        
        html2canvas(element, {
            scale: 2,
            useCORS: true,
            logging: true,
            backgroundColor: "#ffffff",
            allowTaint: true,
            foreignObjectRendering: false
        }).then(canvas => {
            console.log("Canvas created successfully");
            const imgData = canvas.toDataURL("image/png", 1.0);
            console.log("Image data generated");
            
            const pdf = new jsPDF("p", "mm", "a4");
            const pageWidth = pdf.internal.pageSize.getWidth();
            const pageHeight = pdf.internal.pageSize.getHeight();
            
            const imgWidth = pageWidth;
            const imgHeight = (canvas.height * imgWidth) / canvas.width;

            // Add image to PDF
            pdf.addImage(imgData, "PNG", 0, 0, imgWidth, imgHeight);
            
            // Generate filename
            const stationName = document.querySelector("h3") ? 
                document.querySelector("h3").innerText.trim().replace(/[^a-zA-Z0-9]/g, "_") : "Report";
            const dateElement = document.querySelector(".meta");
            let dateText = "UnknownDate";
            if (dateElement) {
                const dateMatch = dateElement.innerText.match(/Date:\s*([^|]+)/);
                dateText = dateMatch ? dateMatch[1].trim().replace(/[^a-zA-Z0-9]/g, "_") : "UnknownDate";
            }
            
            const filename = stationName + "_" + dateText + ".pdf";

            console.log("Saving PDF as: " + filename);
            pdf.save(filename);
            console.log("PDF saved successfully");
            
        }).catch(error => {
            console.error("Error generating PDF:", error);
            alert("Error generating PDF: " + error.message + "\nPlease try the Print button instead.");
        }).finally(() => {
            // Restore button state
            this.textContent = originalText;
            this.disabled = false;
            document.body.classList.remove('loading');
        });
    });

    // Print button - Fixed version
    document.getElementById("printBtn").addEventListener("click", function () {
        updateDateGenerated();
        const printContents = document.getElementById("reportContent").innerHTML;
        const originalContents = document.body.innerHTML;

        document.body.innerHTML = `
            <!DOCTYPE html>
            <html>
            <head>
                <title>Water Quality Report</title>
                <style>
                    body { font-family: Arial, sans-serif; margin: 20px; }
                    .badge { padding: 4px 10px; border-radius: 12px; font-size: 12px; color: #fff; font-weight: bold; }
                    .badge-safe { background: #0051ff !important; }
                    .badge-neutral { background: #28a745 !important; }
                    .badge-warning { background: #ff8800 !important; }
                    .badge-failed { background: #dc3545 !important; }
                    .badge-unknown { background: #6c757d !important; }
                    table, th, td { border: 1px solid #000; border-collapse: collapse; padding: 8px; }
                    .date-generated { text-align:center; margin:5px 0 10px; font-size:14px; color:#333; font-weight:bold; }
                </style>
            </head>
            <body>${printContents}</body>
            </html>
        `;
        
        window.print();
        
        // Short delay before reloading to ensure print dialog opens
        setTimeout(() => {
            location.reload();
        }, 100);
    });
  </script>
</body>
</html>