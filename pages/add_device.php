<?php
require_once __DIR__ . "/../includes/db.php";
session_start();

$message = "";
$errorMsg = "";

// Fetch LGU locations for dropdown
$lguQuery = $conn->query("SELECT DISTINCT lgu_location FROM users WHERE lgu_location IS NOT NULL AND lgu_location != '' ");
$lguLocations = [];
while ($row = $lguQuery->fetch_assoc()) {
    $lguLocations[] = $row['lgu_location'];
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Correct checkbox mapping
    $role = ($_POST["user_type"] === "personal") 
            ? "personal_user" 
            : "refilling_station_owner";

    // Form fields
    $name = trim($_POST["name"]);
    $location = trim($_POST["location"]);
    $lgu_location = trim($_POST["lgu_location"] ?? "");
    $owner_name = trim($_POST["owner_name"]);
    $address = trim($_POST["address"]);
    $contact_no = trim($_POST["contact_no"]);
    $email = trim($_POST["email"]);
    $device_sensor_id = trim($_POST["device_sensor_id"]);

    $registered_by = $_SESSION["user_id"];

    // Insert into DB
    $stmt = $conn->prepare("
        INSERT INTO refilling_stations 
        (user_id, role, name, location, lgu_location, owner_name, address, contact_no, email, device_sensor_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "isssssssss",
        $registered_by,
        $role,
        $name,
        $location,
        $lgu_location,
        $owner_name,
        $address,
        $contact_no,
        $email,
        $device_sensor_id
    );

    if ($stmt->execute()) {
        $message = "success";
    } else {
        $message = "error";
        $errorMsg = $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register Device - Water Quality Monitoring System</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
body {
    background-color: #0d1117;
    color: #f0f0f0;
    font-family: 'Segoe UI', sans-serif;
    display: flex;
    flex-direction: column;
    align-items: center;
    min-height: 100vh;
    padding-top: 40px;
}
.container-box {
    width: 100%;
    max-width: 420px;
    background-color: rgba(0, 0, 0, 0.8);
    border-radius: 20px;
    box-shadow: 0 0 30px rgba(0, 191, 255, 0.3);
    padding: 35px;
    margin-bottom: 40px;
}
h2 {
    text-align: center;
    color: #00d4ff;
    margin-bottom: 25px;
    font-weight: 600;
}
.form-label { color: #ccc; }

.form-control {
    background-color: #1e1e1e;
    border: 1px solid #333;
    color: #fff;
    border-radius: 10px;
}
.form-control:focus {
    border-color: #00e6e6;
    box-shadow: 0 0 10px #00e6e6;
}

.btn-submit {
    background-color: #00bfff;
    color: #000;
    font-weight: bold;
    border-radius: 10px;
    padding: 10px;
}
.btn-submit:hover {
    background-color: #00e6ff;
    box-shadow: 0 0 12px #00e6ff;
}

.dropdown-wrapper {
    position: relative;
}
.dropdown-wrapper select {
    appearance: none;
    padding-right: 45px;
}
.dropdown-wrapper::after {
    content: "\f078";
    font-family: "Font Awesome 6 Free";
    font-weight: 900;
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: #00e6ff;
    pointer-events: none;
    font-size: 14px;
}

/* POPUP */
#popupSuccess, #popupError {
    display:none;
    position:fixed;
    top:0; left:0;
    width:100%; height:100%;
    background:rgba(0,0,0,0.65);
    backdrop-filter:blur(3px);
    justify-content:center;
    align-items:center;
    z-index:9999;
}
.popup-box {
    background:#111;
    padding:25px 30px;
    border-radius:15px;
    text-align:center;
    box-shadow:0 0 20px #00d4ff;
    max-width:320px;
}
.popup-btn {
    background:#00bfff;
    padding:8px 25px;
    border:none;
    border-radius:8px;
    font-weight:bold;
    cursor:pointer;
    margin-top:10px;
}

.user-type {
    background:#1e1e1e;
    border:1px solid #333;
    border-radius:10px;
    padding:15px;
    margin-bottom:15px;
}
.user-type label { 
    color:#ddd; 
    display:flex; 
    gap:10px; 
    cursor:pointer; 
}
.user-type input[type="checkbox"] {
    appearance:none;
    width:20px;
    height:20px;
    border:2px solid #00bfff;
    border-radius:5px;
    position:relative;
}
.user-type input[type="checkbox"]:checked {
    background:#00e6ff;
    box-shadow:0 0 8px #00e6ff;
}
.user-type input[type="checkbox"]:checked::after {
    content:"✔";
    position:absolute;
    font-size:14px;
    left:4px;
    top:0px;
}
</style>
</head>

<body>

<!-- SUCCESS POPUP -->
<div id="popupSuccess">
    <div class="popup-box">
        <h4 style="color:#00e6ff;">✅ Station registered successfully!</h4>
        <button onclick="closePopup('popupSuccess')" class="popup-btn">OK</button>
    </div>
</div>

<!-- ERROR POPUP -->
<div id="popupError">
    <div class="popup-box">
        <h4 style="color:#ff4444;">❌ Error occurred!</h4>
        <p style="color:white; font-size:14px;"><?php echo $errorMsg; ?></p>
        <button onclick="closePopup('popupError')" class="popup-btn">OK</button>
    </div>
</div>

<div class="container-box">
    <h2>REGISTER DEVICE</h2>
    <form method="POST">

        <div class="user-type">
            <label class="form-label d-block mb-1">User Type:</label>
            <label><input type="checkbox" name="user_type" value="personal"> Personal Use</label>
            <label><input type="checkbox" name="user_type" value="owner"> Water Refilling Station</label>
        </div>

        <div class="mb-3">
            <label class="form-label">Station / Device Name</label>
            <input type="text" class="form-control" name="name" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Station / Device Location</label>
            <input type="text" class="form-control" name="location" required>
        </div>

        <!-- LGU DROPDOWN SHOW ONLY IF owner IS CHECKED -->
        <div class="mb-3" id="lguBox" style="display:none;">
            <label class="form-label">Select LGU Location to Register</label>
            <div class="dropdown-wrapper">
                <select class="form-control" name="lgu_location">
                    <option value="" disabled selected hidden>Select LGU Location</option>
                    <?php foreach ($lguLocations as $loc): ?>
                        <option value="<?php echo htmlspecialchars($loc); ?>">
                            <?php echo htmlspecialchars($loc); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Owner Name</label>
            <input type="text" class="form-control" name="owner_name" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Address</label>
            <input type="text" class="form-control" name="address" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Contact Number</label>
            <input type="text" class="form-control" name="contact_no" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Email Address</label>
            <input type="email" class="form-control" name="email" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Device Sensor ID</label>
            <input type="text" class="form-control" name="device_sensor_id" required>
        </div>

        <button type="submit" class="btn btn-submit w-100">Register</button>

    </form>
</div>

<script>
// Single checkbox selection + LGU dropdown toggle
document.querySelectorAll('input[name="user_type"]').forEach(box => {
    box.addEventListener("change", () => {

        document.querySelectorAll('input[name="user_type"]').forEach(other => {
            if (other !== box) other.checked = false;
        });

        const lguBox = document.getElementById("lguBox");
        const lguSelect = document.querySelector('select[name="lgu_location"]');

        if (box.value === "owner" && box.checked) {
            lguBox.style.display = "block";
            lguSelect.required = true;
        } else {
            lguBox.style.display = "none";
            lguSelect.required = false;
        }
    });
});

function closePopup(id) {
    document.getElementById(id).style.display = "none";
}

// Popups
document.addEventListener("DOMContentLoaded", function() {
    <?php if ($message === "success"): ?>
        document.getElementById("popupSuccess").style.display = "flex";
    <?php endif; ?>

    <?php if ($message === "error"): ?>
        document.getElementById("popupError").style.display = "flex";
    <?php endif; ?>
});
</script>

</body>
</html>
