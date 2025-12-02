<?php 
include("../includes/db.php");
session_start();

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user info including role
$query = $conn->prepare("SELECT fullname, email, number, address, role, profile_pic FROM users WHERE user_id = ?");
$query->bind_param("i", $user_id);
$query->execute();
$result = $query->get_result();
$user = $result->fetch_assoc();

// Handle updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['field'], $_POST['value'])) {
    $field = $_POST['field'];
    $value = trim($_POST['value']);

    if (in_array($field, ['fullname', 'email', 'number', 'address'])) {
        $sql = "UPDATE users SET $field = ? WHERE user_id = ?";
        $update = $conn->prepare($sql);
        $update->bind_param("si", $value, $user_id);
        $update->execute();

        $user[$field] = $value;
        echo "<script>alert('Updated successfully!'); window.location='about.php';</script>";
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>About - Water Quality Testing & Monitoring System</title>
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
      padding: 40px;
      margin-top: 40px;
      box-shadow: 0 0 30px rgba(0, 198, 255, 0.1);
      width: 100%;
      max-width: 750px;
      text-align: center;
      position: relative;
    }
    .profile-pic {
      width: 140px;
      height: 140px;
      border-radius: 50%;
      border: 3px solid #fff;
      box-shadow: 0 0 15px #00c6ff;
      object-fit: cover;
      margin-bottom: 20px;
    }
    .btn-back {
      background: transparent;
      border: none;
      color: #00c6ff;
      font-size: 26px;
      position: absolute;
      top: 20px;
      left: 20px;
    }
    .btn-back:hover {
      color: #fff;
    }
    .info-row {
      margin: 30px 0;
      text-align: left;
    }
    .info-label {
      display: block;
      font-weight: 600;
      color: #00c6ff;
      font-size: 16px;
      margin-bottom: 8px;
      margin-left: 15%;
    }
    .info-text {
       background: #2a2f3a;
        border-radius: 8px;
        padding: 12px 20px;
        display: block;
        font-size: 18px;
        width: 70%;          
        max-width: 500px;    
        margin: 0 auto;      
        text-align: left;    
    }
    .btn-change {
      background: #00c6ff;
      border: none;
      color: #fff;
      font-size: 14px;
      padding: 6px 18px;
      border-radius: 20px;
      font-weight: 500;
      transition: background-color 0.2s ease-in-out;
      display: block;
      margin: 12px auto 0 auto; 
    }
    .btn-change:hover {
      background: #009ad9;
      color: #fff;
    }
    .account-icon {
      width: 24px;
      height: 24px;
      border: 1px solid white;
      box-shadow: 0 0 8px white;
      object-fit: cover;
    }
    .modal-content {
      background: #1f2733;
      color: #fff;
      border-radius: 15px;
    }
    .btn-save {
      background: #0072ff;
      border: none;
      color: #fff;
      font-weight: bold;
      padding: 8px 20px;
      border-radius: 8px;
    }
    .btn-save:hover {
      background: #005fcc;
    }
  </style>
</head>
<body>

  <!-- ✅ Navbar -->
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
        </ul>
      </div>
    </div>
  </nav>

  <!-- Profile Section -->
  <div class="container d-flex justify-content-center">
    <div class="container-box">
      <a href="account.php" class="btn-back"><i class="fas fa-arrow-left"></i></a>

      <img src="<?php echo !empty($user['profile_pic']) ? htmlspecialchars($user['profile_pic']) : 'https://cdn-icons-png.flaticon.com/512/847/847969.png'; ?>" 
           alt="Profile" class="profile-pic">

      <h3 class="mb-4"><i class="fas fa-user me-2"></i> About Me</h3>

      <!-- User Role -->
      <div class="info-row">
        <label class="info-label">User Type</label>
        <span class="info-text"><i class="fas fa-user-tag me-2"></i> <?php echo htmlspecialchars(ucfirst($user['role'])); ?></span>
      </div>

      <!-- Info Rows -->
      <div class="info-row">
        <label class="info-label">Full Name</label>
        <span class="info-text"><i class="fas fa-id-card me-2"></i> <?php echo htmlspecialchars($user['fullname']); ?></span>
        <button class="btn-change" data-bs-toggle="modal" data-bs-target="#editNameModal">Change Name</button>
      </div>

      <div class="info-row">
        <label class="info-label">Email Address</label>
        <span class="info-text"><i class="fas fa-envelope me-2"></i> <?php echo htmlspecialchars($user['email']); ?></span>
        <button class="btn-change" data-bs-toggle="modal" data-bs-target="#editEmailModal">Change Email</button>
      </div>

      <div class="info-row">
        <label class="info-label">Contact Number</label>
        <span class="info-text"><i class="fas fa-phone me-2"></i> <?php echo htmlspecialchars($user['number']); ?></span>
        <button class="btn-change" data-bs-toggle="modal" data-bs-target="#editNumberModal">Change Contact Number</button>
      </div>

      <div class="info-row">
        <label class="info-label">Address</label>
        <span class="info-text"><i class="fas fa-map-marker-alt me-2"></i> 
          <?php echo !empty($user['address']) ? htmlspecialchars($user['address']) : 'No address set'; ?>
        </span>
        <button class="btn-change" data-bs-toggle="modal" data-bs-target="#editAddressModal">Change Address</button>
      </div>
    </div>
  </div>

  <!-- Modals -->
  <!-- Name -->
  <div class="modal fade" id="editNameModal" tabindex="-1">
    <div class="modal-dialog">
      <form method="POST" class="modal-content">
        <div class="modal-header border-0">
          <h5 class="modal-title"><i class="fas fa-id-card me-2"></i> Change Name</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="field" value="fullname">
          <input type="text" name="value" class="form-control" value="<?php echo htmlspecialchars($user['fullname']); ?>" required>
        </div>
        <div class="modal-footer border-0">
          <button type="submit" class="btn-save">Save</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Email -->
  <div class="modal fade" id="editEmailModal" tabindex="-1">
    <div class="modal-dialog">
      <form method="POST" class="modal-content">
        <div class="modal-header border-0">
          <h5 class="modal-title"><i class="fas fa-envelope me-2"></i> Change Email</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="field" value="email">
          <input type="email" name="value" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
        </div>
        <div class="modal-footer border-0">
          <button type="submit" class="btn-save">Save</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Contact -->
  <div class="modal fade" id="editNumberModal" tabindex="-1">
    <div class="modal-dialog">
      <form method="POST" class="modal-content">
        <div class="modal-header border-0">
          <h5 class="modal-title"><i class="fas fa-phone me-2"></i> Change Contact Number</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="field" value="number">
          <input type="text" name="value" class="form-control" value="<?php echo htmlspecialchars($user['number']); ?>" required>
        </div>
        <div class="modal-footer border-0">
          <button type="submit" class="btn-save">Save</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Address -->
  <div class="modal fade" id="editAddressModal" tabindex="-1">
    <div class="modal-dialog">
      <form method="POST" class="modal-content">
        <div class="modal-header border-0">
          <h5 class="modal-title"><i class="fas fa-map-marker-alt me-2"></i> Change Address</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="field" value="address">
          <textarea name="value" class="form-control" rows="3" required><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
        </div>
        <div class="modal-footer border-0">
          <button type="submit" class="btn-save">Save</button>
        </div>
      </form>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
