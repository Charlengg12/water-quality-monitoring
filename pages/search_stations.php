<?php
include("../includes/db.php");
include("../includes/fetch_user.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$search = isset($_GET['search']) ? trim($_GET['search']) : "";

// Build query with JOIN
if ($search !== "") {
    $stmt = $conn->prepare("
        SELECT r.* 
        FROM refilling_stations r
        INNER JOIN user_stations us ON r.station_id = us.station_id
        WHERE us.user_id = ? 
          AND (r.name LIKE ? OR r.location LIKE ?)
        ORDER BY r.name ASC
    ");
    $like = "%" . $search . "%";
    $stmt->bind_param("iss", $user_id, $like, $like);
} else {
    $stmt = $conn->prepare("
        SELECT r.* 
        FROM refilling_stations r
        INNER JOIN user_stations us ON r.station_id = us.station_id
        WHERE us.user_id = ?
        ORDER BY r.name ASC
    ");
    $stmt->bind_param("i", $user_id);
}

$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0):
    while ($station = $result->fetch_assoc()):
?>
    <div class="col-md-4 mb-4 station-card">
      <a href="dashboard.php?station_id=<?= $station['station_id'] ?>" class="card p-3 text-decoration-none text-white">
        <h5><i class="fas fa-building"></i> <?= htmlspecialchars($station['name']) ?></h5>
        <p><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($station['location']) ?></p>
        <p><i class="fas fa-microchip"></i> Sensor ID: <?= htmlspecialchars($station['device_sensor_id']) ?></p>
      </a>

      <div class="d-flex gap-2">
        <!-- Delete -->
        <form method="POST" action="delete_station.php" onsubmit="return confirm('Are you sure you want to delete this station?');">
          <input type="hidden" name="station_id" value="<?= $station['station_id'] ?>">
          <button class="btn btn-sm delete-btn mt-2"><i class="fas fa-trash"></i> Delete</button>
        </form>

        <!-- Edit -->
        <button class="btn btn-sm edit-btn mt-2"
          data-bs-toggle="modal"
          data-bs-target="#editStationModal"
          data-id="<?= $station['station_id'] ?>"
          data-name="<?= htmlspecialchars($station['name']) ?>"
          data-location="<?= htmlspecialchars($station['location']) ?>">
          <i class="fas fa-edit"></i> Edit
        </button>
      </div>
    </div>
<?php
    endwhile;
else:
    echo '<p class="text-center">No stations found.</p>';
endif;

$stmt->close();
$conn->close();
?>
