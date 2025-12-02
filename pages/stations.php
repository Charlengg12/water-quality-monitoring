<?php
session_start();
include("../includes/db.php");
include("../includes/fetch_user.php");

if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit();
}

$user_id = $_SESSION['user_id'];

// Default/mock user data if not fetched
if (!isset($user)) {
  $user = ['profile_pic' => 'https://cdn-icons-png.flaticon.com/512/847/847969.png', 'role' => 'user'];
}

// Get current user's LGU location
$current_user_lgu = null;
if (isset($user['lgu_location'])) {
    $current_user_lgu = $user['lgu_location'];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Stations - Water Quality Monitor</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    /* Page */
    body {
      background: #0e1117;
      color: #fff;
      font-family: 'Segoe UI', sans-serif;
    }

    .navbar {
      background-color: #1f2733;
      box-shadow: 0 2px 10px rgba(0, 198, 255, 0.2);
    }

    .navbar-brand,
    .nav-link,
    .btn {
      color: #fff;
    }

    .navbar-nav .nav-link:hover {
      color: #00c6ff;
    }

    .container {
      margin-top: 40px;
    }

    .card {
      background-color: #1f2733;
      color: #fff;
      border: 1px solid #00c6ff;
      border-radius: 15px;
    }

    .card:hover {
      box-shadow: 0 0 15px #00c6ff;
      cursor: pointer;
    }

    .add-btn {
      background-color: #00c6ff;
      border: none;
    }

    .add-btn:hover {
      background-color: #00a3cc;
    }

    .delete-btn {
      background-color: #dc3545;
      border: none;
    }

    .edit-btn {
      background-color: #ffc107;
      border: none;
      color: #000;
    }

    .search-input {
      background-color: #1f2733;
      color: #fff;
      border: 1px solid #00c6ff;
    }

    .search-input:focus {
      background-color: #1f2733;
      color: #fff;
      box-shadow: 0 0 5px #00c6ff;
    }

    .account-icon {
      width: 24px;
      height: 24px;
      border: 1px solid white;
      box-shadow: 0 0 8px white;
      object-fit: cover;
    }

    /* --- About-style container-box (used inside modal) --- */
    .container-box {
      background: #1f2733;
      border-radius: 20px;
      padding: 36px;
      margin: 20px auto;
      box-shadow: 0 0 30px rgba(0, 198, 255, 0.1);
      width: 100%;
      max-width: 720px;
      text-align: center;
      position: relative;
      color: #fff;
    }

    .profile-pic {
      width: 120px;
      height: 120px;
      border-radius: 50%;
      border: 3px solid #fff;
      box-shadow: 0 0 12px #00c6ff;
      object-fit: cover;
      margin-bottom: 16px;
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
      margin: 22px 0;
      text-align: left;
    }

    .info-label {
      display: block;
      font-weight: 600;
      color: #00c6ff;
      font-size: 15px;
      margin-bottom: 8px;
      margin-left: 10%;
    }

    .info-text {
      background: #2a2f3a;
      border-radius: 8px;
      padding: 12px 20px;
      display: block;
      font-size: 17px;
      width: 70%;
      max-width: 560px;
      margin: 0 auto;
      text-align: left;
      color: #fff;
      word-break: break-word;
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

    .modal-content.bg-dark {
      background: transparent; /* we use our container-box for the actual box */
      border: none;
    }

    .modal-backdrop.show {
      backdrop-filter: blur(2px);
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

    /* small responsive tweaks */
    @media (max-width: 576px) {
      .info-label { margin-left: 5%; font-size: 14px; }
      .info-text { width: 88%; font-size: 15px; }
      .container-box { padding: 20px; }
    }
  </style>
</head>

<body>

  <nav class="navbar navbar-expand-lg">
    <div class="container-fluid">
      <a class="navbar-brand" href="#"><i class="fas fa-tint"></i> Water Quality Testing & Monitoring System</a>
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
              <img src="<?php echo htmlspecialchars($user['profile_pic']); ?>" alt="Account" class="rounded-circle account-icon">
              <span class="account-text">Account</span>
            </a>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <div class="container">

    <?php if (isset($_GET['success']) || isset($_GET['error']) || isset($_GET['status'])): ?>
      <div class="position-fixed top-0 end-0 p-3" style="z-index: 1100;">

        <?php if (isset($_GET['status']) && $_GET['status'] == 'added'): ?>
          <div class="toast align-items-center text-bg-success border-0 show" role="alert">
            <div class="d-flex">
              <div class="toast-body"> Station registered successfully!</div>
              <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
          </div>
        <?php endif; ?>

        <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
          <div class="toast align-items-center text-bg-success border-0 show" role="alert">
            <div class="d-flex">
              <div class="toast-body"> Station updated successfully!</div>
              <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
          </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
          <div class="toast align-items-center text-bg-danger border-0 show mt-2" role="alert">
            <div class="d-flex">
              <div class="toast-body">
                <?php if ($_GET['error'] == 'invalid_input'): ?>
                  Invalid input. Please fill out all fields.
                <?php elseif ($_GET['error'] == 'unauthorized'): ?>
                  You are not allowed to edit this station.
                <?php elseif ($_GET['error'] == 'update_failed'): ?>
                  Something went wrong while updating the station.
                <?php elseif ($_GET['error'] == 'add_failed'): ?>
                  Failed to add station.
                <?php else: ?>
                  An unknown error occurred.
                <?php endif; ?>
                <?php if (isset($_GET['msg'])) echo " (" . htmlspecialchars(urldecode($_GET['msg'])) . ")"; ?>
              </div>
              <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2>Water Sensor Station/s</h2>
      <div class="d-flex">
        <form style="max-width: 350px; margin-right: 10px;">
          <div class="input-group" style="height: 38px;">
            <input type="text" class="form-control search-input" placeholder="Search station..." id="searchInput" style="height: 38px; padding: 6px 12px;">
            <button class="btn btn-primary" type="button" id="searchBtn" disabled style="height: 38px;"><i class="fas fa-search"></i></button>
            <button class="btn btn-secondary" type="button" id="clearBtn" style="height: 38px; display: none;"><i class="fas fa-times"></i></button>
          </div>
        </form>

        <button class="btn add-btn" data-bs-toggle="modal" data-bs-target="#addExistingStationModal">
          <i class="fas fa-link"></i> Add Existing Station
        </button>
      </div>
    </div>

    <div id="stationsContainer" class="row">
      <?php
      if (empty($conn->connect_error)) {
        // Fetch stations based on user role and LGU location
        if ($user['role'] === 'lgu_menro' && $current_user_lgu) {
          // For LGU MENRO users: show all stations that have the same lgu_location
          $stmt = $conn->prepare("
            SELECT DISTINCT r.* 
            FROM refilling_stations r
            WHERE r.lgu_location = ?
            AND r.name IS NOT NULL
            ORDER BY r.name ASC
          ");
          $stmt->bind_param("s", $current_user_lgu);
          $stmt->execute();
          $result = $stmt->get_result();
          
        } else {
          // For other users: show their linked stations (original behavior)
          $stmt = $conn->prepare("
            SELECT r.* FROM refilling_stations r
            INNER JOIN user_stations us ON r.station_id = us.station_id
            WHERE us.user_id = ?
            ORDER BY r.name ASC
          ");
          $stmt->bind_param("i", $user_id);
          $stmt->execute();
          $result = $stmt->get_result();
        }

        if ($result->num_rows > 0):
          while ($station = $result->fetch_assoc()):
            // ensure keys exist to avoid notices
            $owner = $station['owner_name'] ?? '';
            $addr = $station['address'] ?? '';
            $contact = $station['contact_no'] ?? '';
            $emailS = $station['email'] ?? '';
            $sensor = $station['device_sensor_id'] ?? '';
      ?>
            <div class="col-md-4 mb-4 station-card">
              <a href="dashboard.php?station_id=<?= $station['station_id'] ?>" class="card p-3 text-decoration-none text-white">
                <h5><i class="fas fa-building"></i> <?= htmlspecialchars($station['name']) ?></h5>
                <p><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($station['location']) ?></p>
                <p><i class="fas fa-microchip"></i> Sensor ID: <?= htmlspecialchars($sensor) ?></p>
              </a>

              <div class="d-flex gap-2">
                <?php if ($user['role'] !== 'lgu_menro'): ?>
                  <!-- Only show remove button for non-LGU users -->
                  <form method="POST" action="delete_station.php" onsubmit="return confirm('Are you sure you want to delete this station?');">
                    <input type="hidden" name="station_id" value="<?= $station['station_id'] ?>">
                    <button class="btn btn-sm delete-btn mt-2"><i class="fas fa-trash"></i> Remove</button>
                  </form>
                <?php else: ?>
                  <!-- For LGU MENRO users, show a disabled remove button or hide it -->
                  <button class="btn btn-sm delete-btn mt-2" disabled style="opacity: 0.6;"><i class="fas fa-trash"></i> Remove</button>
                <?php endif; ?>

                <!-- Details button: opens About-style modal -->
                <button class="btn btn-sm edit-btn mt-2"
                  data-bs-toggle="modal"
                  data-bs-target="#editStationModal"
                  data-id="<?= $station['station_id'] ?>"
                  data-name="<?= htmlspecialchars($station['name']) ?>"
                  data-location="<?= htmlspecialchars($station['location']) ?>"
                  data-owner="<?= htmlspecialchars($owner) ?>"
                  data-address="<?= htmlspecialchars($addr) ?>"
                  data-contact="<?= htmlspecialchars($contact) ?>"
                  data-email="<?= htmlspecialchars($emailS) ?>"
                  data-sensor="<?= htmlspecialchars($sensor) ?>">
                  <i class="fas fa-edit"></i> Details
                </button>
              </div>
            </div>
          <?php endwhile;
        else: ?>
          <p class="text-center">No stations found.</p>
          <?php if ($user['role'] === 'lgu_menro'): ?>
            <p class="text-center text-muted">No refilling stations found in your LGU jurisdiction (LGU Location: <?= htmlspecialchars($current_user_lgu) ?>)</p>
          <?php endif; ?>
      <?php endif;
        $stmt->close();
      } else {
        echo '<p class="text-danger text-center">Database connection failed: ' . htmlspecialchars($conn->connect_error) . '</p>';
      }
      ?>
    </div>
  </div>

  <!-- Add Existing Station Modal -->
  <div class="modal fade" id="addExistingStationModal" tabindex="-1">
    <div class="modal-dialog">
      <form class="modal-content" method="POST" action="add_existing_station.php">
        <div class="modal-header bg-dark text-white">
          <h5 class="modal-title">Add Existing Station</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body bg-dark text-white">
          <div class="mb-3">
            <label class="form-label">Enter Device Sensor ID</label>
            <input type="text" class="form-control" name="device_sensor_id" required>
          </div>
        </div>
        <div class="modal-footer bg-dark">
          <button type="submit" class="btn btn-success">Add Station</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Edit Station Modal (Bootstrap modal with centered About-style container-box inside) -->
  <div class="modal fade" id="editStationModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content bg-dark">
        <div class="modal-body p-0">
          <!-- Centered About-style box -->
          <div class="container-box">
            <button type="button" class="btn-back" data-bs-dismiss="modal"><i class="fas fa-arrow-left"></i></button>

            <div class="text-center mb-2">
              <div style="font-size:36px; color:#00c6ff;"><i class="fas fa-building"></i></div>
              <h4 style="color:#fff; margin-top:8px;">Station / Device Details</h4>
            </div>

            <input type="hidden" id="editStationId">

            <!-- Station / Device Name -->
            <div class="info-row">
              <label class="info-label">Station / Device Name</label>
              <div class="info-text" id="viewStationName"></div>
              <!-- Show Change button for all users who can edit stations -->
              <button class="btn-change" onclick="openEditModal('modalEditName')">Change</button>
            </div>

            <!-- Station / Device Location -->
            <div class="info-row">
              <label class="info-label">Station / Device Location</label>
              <div class="info-text" id="viewStationLocation"></div>
              <!-- Show Change button for all users who can edit stations -->
              <button class="btn-change" onclick="openEditModal('modalEditLocation')">Change</button>
            </div>

            <!-- Owner Name -->
            <div class="info-row">
              <label class="info-label">Owner Name</label>
              <div class="info-text" id="viewOwner"></div>
              <!-- Show Change button for all users who can edit stations -->
              <button class="btn-change" onclick="openEditModal('modalEditOwner')">Change</button>
            </div>

            <!-- Address -->
            <div class="info-row">
              <label class="info-label">Address</label>
              <div class="info-text" id="viewAddress"></div>
              <!-- Show Change button for all users who can edit stations -->
              <button class="btn-change" onclick="openEditModal('modalEditAddress')">Change</button>
            </div>

            <!-- Contact Number -->
            <div class="info-row">
              <label class="info-label">Contact Number</label>
              <div class="info-text" id="viewContact"></div>
              <!-- Show Change button for all users who can edit stations -->
              <button class="btn-change" onclick="openEditModal('modalEditContact')">Change</button>
            </div>

            <!-- Email Address -->
            <div class="info-row">
              <label class="info-label">Email Address</label>
              <div class="info-text" id="viewEmail"></div>
              <!-- Show Change button for all users who can edit stations -->
              <button class="btn-change" onclick="openEditModal('modalEditEmail')">Change</button>
            </div>

            <!-- Device Sensor ID (read-only) -->
            <div class="info-row">
              <label class="info-label">Device Sensor ID</label>
              <div class="info-text" id="viewSensorId"></div>
            </div>

          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Individual Edit Modals (small About-style-like modals) -->
  <!-- Edit Name Modal -->
  <div class="modal fade" id="modalEditName" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog">
      <form method="POST" action="update_station.php" class="modal-content" onsubmit="handleFormSubmit(event, 'modalEditName')">
        <input type="hidden" name="station_id" id="modalNameId">
        <input type="hidden" name="field" value="name">
        <div class="modal-header bg-dark text-white border-0">
          <h5 class="modal-title"><i class="fas fa-id-card me-2"></i> Edit Station / Device Name</h5>
          <button type="button" class="btn-close btn-close-white" onclick="closeEditModal('modalEditName')"></button>
        </div>
        <div class="modal-body bg-dark text-white">
          <input type="text" name="value" id="modalNameValue" class="form-control" required>
        </div>
        <div class="modal-footer bg-dark border-0">
          <button type="submit" class="btn-save">Save</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Edit Location Modal -->
  <div class="modal fade" id="modalEditLocation" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog">
      <form method="POST" action="update_station.php" class="modal-content" onsubmit="handleFormSubmit(event, 'modalEditLocation')">
        <input type="hidden" name="station_id" id="modalLocationId">
        <input type="hidden" name="field" value="location">
        <div class="modal-header bg-dark text-white border-0">
          <h5 class="modal-title"><i class="fas fa-map-marker-alt me-2"></i> Edit Station / Device Location</h5>
          <button type="button" class="btn-close btn-close-white" onclick="closeEditModal('modalEditLocation')"></button>
        </div>
        <div class="modal-body bg-dark text-white">
          <input type="text" name="value" id="modalLocationValue" class="form-control" required>
        </div>
        <div class="modal-footer bg-dark border-0">
          <button type="submit" class="btn-save">Save</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Edit Owner Modal -->
  <div class="modal fade" id="modalEditOwner" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog">
      <form method="POST" action="update_station.php" class="modal-content" onsubmit="handleFormSubmit(event, 'modalEditOwner')">
        <input type="hidden" name="station_id" id="modalOwnerId">
        <input type="hidden" name="field" value="owner_name">
        <div class="modal-header bg-dark text-white border-0">
          <h5 class="modal-title"><i class="fas fa-user me-2"></i> Edit Owner Name</h5>
          <button type="button" class="btn-close btn-close-white" onclick="closeEditModal('modalEditOwner')"></button>
        </div>
        <div class="modal-body bg-dark text-white">
          <input type="text" name="value" id="modalOwnerValue" class="form-control" required>
        </div>
        <div class="modal-footer bg-dark border-0">
          <button type="submit" class="btn-save">Save</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Edit Address Modal -->
  <div class="modal fade" id="modalEditAddress" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog">
      <form method="POST" action="update_station.php" class="modal-content" onsubmit="handleFormSubmit(event, 'modalEditAddress')">
        <input type="hidden" name="station_id" id="modalAddressId">
        <input type="hidden" name="field" value="address">
        <div class="modal-header bg-dark text-white border-0">
          <h5 class="modal-title"><i class="fas fa-map-marker-alt me-2"></i> Edit Address</h5>
          <button type="button" class="btn-close btn-close-white" onclick="closeEditModal('modalEditAddress')"></button>
        </div>
        <div class="modal-body bg-dark text-white">
          <textarea name="value" id="modalAddressValue" class="form-control" rows="3" required></textarea>
        </div>
        <div class="modal-footer bg-dark border-0">
          <button type="submit" class="btn-save">Save</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Edit Contact Modal -->
  <div class="modal fade" id="modalEditContact" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog">
      <form method="POST" action="update_station.php" class="modal-content" onsubmit="handleFormSubmit(event, 'modalEditContact')">
        <input type="hidden" name="station_id" id="modalContactId">
        <input type="hidden" name="field" value="contact_no">
        <div class="modal-header bg-dark text-white border-0">
          <h5 class="modal-title"><i class="fas fa-phone me-2"></i> Edit Contact Number</h5>
          <button type="button" class="btn-close btn-close-white" onclick="closeEditModal('modalEditContact')"></button>
        </div>
        <div class="modal-body bg-dark text-white">
          <input type="text" name="value" id="modalContactValue" class="form-control" required>
        </div>
        <div class="modal-footer bg-dark border-0">
          <button type="submit" class="btn-save">Save</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Edit Email Modal -->
  <div class="modal fade" id="modalEditEmail" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog">
      <form method="POST" action="update_station.php" class="modal-content" onsubmit="handleFormSubmit(event, 'modalEditEmail')">
        <input type="hidden" name="station_id" id="modalEmailId">
        <input type="hidden" name="field" value="email">
        <div class="modal-header bg-dark text-white border-0">
          <h5 class="modal-title"><i class="fas fa-envelope me-2"></i> Edit Email Address</h5>
          <button type="button" class="btn-close btn-close-white" onclick="closeEditModal('modalEditEmail')"></button>
        </div>
        <div class="modal-body bg-dark text-white">
          <input type="email" name="value" id="modalEmailValue" class="form-control" required>
        </div>
        <div class="modal-footer bg-dark border-0">
          <button type="submit" class="btn-save">Save</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Bootstraps + JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Modal management functions
    function openEditModal(modalId) {
      const editModal = bootstrap.Modal.getInstance(document.getElementById('editStationModal'));
      editModal.hide();
      
      setTimeout(() => {
        const modal = new bootstrap.Modal(document.getElementById(modalId));
        modal.show();
      }, 300);
    }

    function closeEditModal(modalId) {
      const modal = bootstrap.Modal.getInstance(document.getElementById(modalId));
      modal.hide();
      
      setTimeout(() => {
        const editModal = new bootstrap.Modal(document.getElementById('editStationModal'));
        editModal.show();
      }, 300);
    }

    function handleFormSubmit(event, modalId) {
      event.preventDefault();
      
      // Get the form data
      const form = event.target;
      const formData = new FormData(form);
      
      // Show loading state
      const submitBtn = form.querySelector('.btn-save');
      const originalText = submitBtn.innerHTML;
      submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
      submitBtn.disabled = true;
      
      // Submit via AJAX
      fetch(form.action, {
        method: 'POST',
        body: formData
      })
      .then(response => {
        if (!response.ok) {
          throw new Error('Network response was not ok');
        }
        return response.json();
      })
      .then(data => {
        if (data.success) {
          // Update the displayed value in the main modal
          const field = formData.get('field');
          const value = formData.get('value');
          
          switch(field) {
            case 'name':
              document.getElementById('viewStationName').textContent = value;
              break;
            case 'location':
              document.getElementById('viewStationLocation').textContent = value;
              break;
            case 'owner_name':
              document.getElementById('viewOwner').textContent = value;
              break;
            case 'address':
              document.getElementById('viewAddress').textContent = value;
              break;
            case 'contact_no':
              document.getElementById('viewContact').textContent = value;
              break;
            case 'email':
              document.getElementById('viewEmail').textContent = value;
              break;
          }
          
          // Close the edit modal and reopen the main modal
          closeEditModal(modalId);
          
          // Show success message
          showToast(data.message || 'Station updated successfully!', 'success');
        } else {
          showToast(data.message || 'Error updating station', 'error');
        }
      })
      .catch(error => {
        console.error('Error:', error);
        showToast('Failed to update station. Please try again.', 'error');
      })
      .finally(() => {
        // Restore button state
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
      });
    }

    function showToast(message, type) {
      // Create and show a toast notification
      const toastContainer = document.querySelector('.position-fixed') || createToastContainer();
      const toastEl = document.createElement('div');
      toastEl.className = `toast align-items-center text-bg-${type === 'success' ? 'success' : 'danger'} border-0 show`;
      toastEl.innerHTML = `
        <div class="d-flex">
          <div class="toast-body">${message}</div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
      `;
      toastContainer.appendChild(toastEl);
      
      const bsToast = new bootstrap.Toast(toastEl, { delay: 3000 });
      bsToast.show();
      
      setTimeout(() => {
        toastEl.remove();
      }, 3000);
    }

    function createToastContainer() {
      const container = document.createElement('div');
      container.className = 'position-fixed top-0 end-0 p-3';
      container.style.zIndex = '1100';
      document.body.appendChild(container);
      return container;
    }

    const searchInput = document.getElementById("searchInput");
    const searchBtn = document.getElementById("searchBtn");
    const clearBtn = document.getElementById("clearBtn");
    const stationsContainer = document.getElementById("stationsContainer");

    function updateButtons() {
      const value = searchInput.value.trim();
      searchBtn.disabled = value === "";
      clearBtn.style.display = value === "" ? "none" : "inline-flex";
    }
    updateButtons();

    searchInput.addEventListener("input", function() {
      updateButtons();
      const value = this.value.trim();
      const xhr = new XMLHttpRequest();
      xhr.open("GET", "search_stations.php?search=" + encodeURIComponent(value), true);
      xhr.onload = function() {
        if (xhr.status === 200) {
          stationsContainer.innerHTML = xhr.responseText;
        }
      };
      xhr.send();
    });

    clearBtn.addEventListener("click", function() {
      searchInput.value = "";
      updateButtons();
      const xhr = new XMLHttpRequest();
      xhr.open("GET", "search_stations.php?search=", true);
      xhr.onload = function() {
        if (xhr.status === 200) {
          stationsContainer.innerHTML = xhr.responseText;
        }
      };
      xhr.send();
    });

    // EditStation modal fill (About-style modal inside)
    const editModalEl = document.getElementById('editStationModal');
    editModalEl.addEventListener('show.bs.modal', function(event) {
      const button = event.relatedTarget;

      const id = button.getAttribute('data-id') || '';
      const name = button.getAttribute('data-name') || '';
      const location = button.getAttribute('data-location') || '';
      const owner = button.getAttribute('data-owner') || '';
      const address = button.getAttribute('data-address') || '';
      const contact = button.getAttribute('data-contact') || '';
      const email = button.getAttribute('data-email') || '';
      const sensor = button.getAttribute('data-sensor') || '';

      document.getElementById('editStationId').value = id;

      document.getElementById('viewStationName').textContent = name;
      document.getElementById('viewStationLocation').textContent = location;
      document.getElementById('viewOwner').textContent = owner || 'No owner set';
      document.getElementById('viewAddress').textContent = address || 'No address set';
      document.getElementById('viewContact').textContent = contact || 'No contact set';
      document.getElementById('viewEmail').textContent = email || 'No email set';
      document.getElementById('viewSensorId').textContent = sensor || '';

      // Prepare individual edit modals with current values
      document.getElementById('modalNameId').value = id;
      document.getElementById('modalNameValue').value = name;
      document.getElementById('modalLocationId').value = id;
      document.getElementById('modalLocationValue').value = location;
      document.getElementById('modalOwnerId').value = id;
      document.getElementById('modalOwnerValue').value = owner;
      document.getElementById('modalAddressId').value = id;
      document.getElementById('modalAddressValue').value = address;
      document.getElementById('modalContactId').value = id;
      document.getElementById('modalContactValue').value = contact;
      document.getElementById('modalEmailId').value = id;
      document.getElementById('modalEmailValue').value = email;
    });

    // Toast auto show/hide for existing toasts
    document.querySelectorAll('.toast').forEach(toastEl => {
      const bsToast = new bootstrap.Toast(toastEl, { delay: 3000 });
      bsToast.show();
      setTimeout(() => { bsToast.hide(); }, 3000);
    });
  </script>
</body>
</html>