<?php 
session_start();
require_once __DIR__ . "/../includes/db.php";

// Security check
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION["user_id"];
$stmt = $conn->prepare("SELECT fullname, role, profile_pic FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if ($user["role"] !== "super_admin") {
    echo "<h2>Access Denied</h2>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Super Admin Dashboard - Water Quality Testing & Monitoring System</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
  body {
    margin: 0;
    font-family: 'Segoe UI', sans-serif;
    height: 100vh;
    display: flex;
    background: #001f3f;
    color: #f0f0f0;
    overflow: hidden;
  }

  /* Sidebar */
  .sidebar {
    width: 250px;
    background-color: rgba(0, 0, 0, 0.85);
    box-shadow: 0 0 20px rgba(0,191,255,0.2);
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    padding: 20px 0;
    z-index: 2;
  }
  .sidebar h3 {
    color: #00d4ff;
    text-align: center;
    margin-bottom: 20px;
  }
  .sidebar a {
    display: block;
    color: #ddd;
    padding: 12px 20px;
    text-decoration: none;
    transition: 0.3s;
    border-left: 4px solid transparent;
  }
  .sidebar a:hover {
    background: #00bfff;
    color: #000;
    border-left: 4px solid #00e6ff;
  }
  .logout {
    color: #ff4d4f !important;
  }

  /* Main Content */
  .main-content {
    flex-grow: 1;
    position: relative;
    z-index: 1;
    background-color: rgba(0, 0, 0, 0.7);
    margin: 30px;
    border-radius: 15px;
    box-shadow: 0 0 30px rgba(0,191,255,0.3);
    overflow: hidden;
  }

  header {
    background: #0077be;
    color: #fff;
    padding: 15px 25px;
    border-radius: 15px 15px 0 0;
    font-size: 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
  }

  iframe {
    width: 100%;
    height: calc(100% - 60px);
    border: none;
  }

  .droplet {
    text-align: center;
    font-size: 40px;
    color: #00bfff;
  }

.account-icon {
  border-radius: 50%;
}

</style>
</head>
<body>
<div class="sidebar">
  <div>
    <div class="droplet">💧</div>
    <h3>Super Admin Dashboard</h3>
    <h4 style="font-size:17px; color: #00d4ff; text-align: center;">Water Quality Testing & Monitoring System</h4>
    <a href="add_device.php" target="contentFrame"><i class="fa-solid fa-microchip"></i> Register Device</a>
    <a href="add_lgu_account.php" target="contentFrame"><i class="fa-solid fa-building-columns"></i> Create LGU MENRO Account</a>
    <a href="add_super_admin.php" target="contentFrame"><i class="fa-solid fa-user-shield"></i> Create Super Admin Account</a> <!-- ✅ NEW TAB -->
    
  <a class="nav-link d-flex align-items-center" href="account.php" target="contentFrame" style="gap: 8px;">
    <img 
      src="<?php echo !empty($user['profile_pic']) ? htmlspecialchars($user['profile_pic']) : 'https://cdn-icons-png.flaticon.com/512/847/847969.png'; ?>" 
      alt="Account" 
      class="rounded-circle account-icon"
      style="width: 20px; height: 20px; object-fit: cover;">
    <span class="account-text">Account</span>
  </a>

  </div>
  <div>
    <a href="logout.php" class="logout"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
  </div>
</div>

<div class="main-content">
  <header>
    <span>Welcome, <?php echo htmlspecialchars($user["fullname"]); ?> 👋</span>
  </header>
  <iframe name="contentFrame"></iframe>
</div>
</body>
</html>
