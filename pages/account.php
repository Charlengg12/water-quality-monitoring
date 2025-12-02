<?php
include("../includes/db.php");
session_start();

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$query = $conn->prepare("SELECT fullname, profile_pic FROM users WHERE user_id = ?");
$query->bind_param("i", $user_id);
$query->execute();
$result = $query->get_result();
$user = $result->fetch_assoc();

// Handle profile picture upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_pic'])) {
    $uploadDir = "../uploads/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $fileName = time() . "_" . basename($_FILES['profile_pic']['name']);
    $targetFile = $uploadDir . $fileName;
    $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

    // Validate file type & size
    if (($fileType === "jpg" || $fileType === "jpeg" || $fileType === "png") && $_FILES['profile_pic']['size'] <= 2 * 1024 * 1024) {
        if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $targetFile)) {
            // Store absolute path for browser
            $path = "/watermonitoringsystem-main/uploads/" . $fileName;

            $update = $conn->prepare("UPDATE users SET profile_pic = ? WHERE user_id = ?");
            $update->bind_param("si", $path, $user_id);
            $update->execute();

            $user['profile_pic'] = $path; // update current session data
        }
    } else {
        echo "<script>alert('Only JPG, JPEG, PNG files under 2MB are allowed.');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Account - Water Quality Testing & Monitoring System</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <style>
    body {
      background: #0e1117;
      font-family: 'Segoe UI', sans-serif;
      color: #fff;
    }
    .navbar {
      background-color: #1f2733;
      box-shadow: 0 2px 10px rgba(0, 198, 255, 0.2);
    }
    .navbar-brand, .nav-link {
      color: #fff;
    }
    .navbar-nav .nav-link:hover {
      color: #00c6ff;
    }
    .container-box {
      background: #1f2733;
      border-radius: 20px;
      padding: 30px;
      margin-top: 30px;
      box-shadow: 0 0 30px rgba(0, 198, 255, 0.1);
      max-width: 600px;
    }
    .profile-pic {
      width: 120px;
      height: 120px;
      border-radius: 50%;
      border: 3px solid #fff;
      box-shadow: 0 0 15px #00c6ff;
      object-fit: cover;
    }
    .account-title {
      font-size: 22px;
      font-weight: bold;
      color: #00c6ff;
      margin-top: 15px;
    }
    .btn-account {
      background: #0072ff;
      color: white;
      font-weight: bold;
      border-radius: 10px;
      padding: 10px 20px;
      margin: 10px 5px;
      display: inline-block;
    }
    .btn-account:hover {
      background: #005fcc;
      color: #fff;
    }
    .footer-text {
      text-align: center;
      margin-top: 15px;
      color: #777;
      font-size: 14px;
    }
    #uploadForm {
      display: none;
      margin-top: 15px;
    }
  </style>
</head>
<body>
  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg">
    <div class="container-fluid">
      <a class="navbar-brand" href="dashboard.php"><i class="fas fa-tint"></i> Water Quality Testing & Monitoring System</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
          <li class="nav-item"><a class="nav-link" href="stations.php"><i class="fas fa-building"></i> Devices/Stations</a></li>
          <li class="nav-item"><a class="nav-link" href="reports.php"><i class="fas fa-chart-line"></i> Reports</a></li>
          <li class="nav-item"><a class="nav-link" href="notifications.php"><i class="fas fa-bell"></i> Notifications</a></li>
         
  <li class="nav-item">
  <a class="nav-link d-flex align-items-center" href="account.php" style="gap: 8px;">
    <img 
      src="<?php echo !empty($user['profile_pic']) ? htmlspecialchars($user['profile_pic']) : 'https://cdn-icons-png.flaticon.com/512/847/847969.png'; ?>" 
      alt="Account" 
      class="rounded-circle account-icon">
    <span class="account-text">Account</span>
  </a>
  </li>

  <style>
  .account-icon {
    width: 24px;
    height: 24px;
    border: 1px solid white;          /* white edge */
    box-shadow: 0 0 8px white;        /* glowing effect */
    object-fit: cover;                /* keeps image inside circle */
  }
  </style>
  
        </ul>
      </div>
    </div>
  </nav>

  <!-- Account Section -->
  <div class="container d-flex justify-content-center">
    <div class="container-box text-center">
      <img src="<?php echo !empty($user['profile_pic']) ? $user['profile_pic'] : 'https://cdn-icons-png.flaticon.com/512/847/847969.png'; ?>" 
           alt="Profile" class="profile-pic mb-3">

      <div class="account-title"><?php echo htmlspecialchars($user['fullname']); ?></div>
      <p class="account-subtitle">Manage your settings and preferences</p>

      <!-- Button to show upload form -->
      <button class="btn btn-account" onclick="toggleUploadForm()">
        <?php echo empty($user['profile_pic']) ? "Upload Profile Picture" : "Change Profile Picture"; ?>
      </button>

      <!-- Upload Form (hidden initially) -->
      <form method="POST" enctype="multipart/form-data" id="uploadForm">
        <div class="mt-3">
          <input type="file" name="profile_pic" class="form-control" accept=".jpg,.jpeg,.png" required>
        </div>
        <button type="submit" class="btn btn-account mt-2">Save</button>
      </form>

      <!-- ✅ New About Button -->
      <div class="mt-3">
        <a href="about.php" class="btn btn-account">
          <i class="fas fa-info-circle me-2"></i> About
        </a>
      </div>

      <!-- Settings & Logout -->
      <div class="mt-3">
        <a href="settings.php" class="btn btn-account"><i class="fas fa-cog me-2"></i> Settings</a>
        <a href="logout.php" class="btn btn-account"><i class="fas fa-sign-out-alt me-2"></i> Log Out</a>
      </div>

      <div class="footer-text mt-4">© 2025 Water Quality Testing & Monitoring System</div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    function toggleUploadForm() {
      const form = document.getElementById("uploadForm");
      form.style.display = form.style.display === "none" ? "block" : "none";
    }
  </script>
</body>
</html>
