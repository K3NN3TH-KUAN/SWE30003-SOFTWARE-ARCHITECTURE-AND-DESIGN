<?php
session_start();
require_once '../classes/Database.php';

// Check if admin is logged in
if (!isset($_SESSION['adminID'])) {
    header('Location: login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();
$message = "";

// Handle Add Trip
if (isset($_POST['add_trip'])) {
    $origin = $_POST['origin'];
    $destination = $_POST['destination'];
    $tripDate = $_POST['tripDate'];
    $tripTime = $_POST['tripTime'];
    $totalAmount = $_POST['totalAmount'];
    $maxSeats = $_POST['maxSeats'];
    $availableSeats = $_POST['availableSeats'];
    $stmt = $db->prepare("INSERT INTO trip (origin, destination, tripDate, tripTime, totalAmount, maxSeats, availableSeats) VALUES (?, ?, ?, ?, ?, ?, ?)");
    if ($stmt->execute([$origin, $destination, $tripDate, $tripTime, $totalAmount, $maxSeats, $availableSeats])) {
        $message = "Trip added successfully!";
    } else {
        $message = "Failed to add trip.";
    }
}

// Handle Edit Trip
if (isset($_POST['edit_trip'])) {
    $id = $_POST['tripID'];
    $origin = $_POST['origin'];
    $destination = $_POST['destination'];
    $tripDate = $_POST['tripDate'];
    $tripTime = $_POST['tripTime'];
    $totalAmount = $_POST['totalAmount'];
    $maxSeats = $_POST['maxSeats'];
    $availableSeats = $_POST['availableSeats'];
    $stmt = $db->prepare("UPDATE trip SET origin=?, destination=?, tripDate=?, tripTime=?, totalAmount=?, maxSeats=?, availableSeats=? WHERE tripID=?");
    if ($stmt->execute([$origin, $destination, $tripDate, $tripTime, $totalAmount, $maxSeats, $availableSeats, $id])) {
        $message = "Trip updated successfully!";
    } else {
        $message = "Failed to update trip.";
    }
}

// Handle Delete Trip
if (isset($_POST['delete_trip'])) {
    $id = $_POST['tripID'];
    $stmt = $db->prepare("DELETE FROM trip WHERE tripID=?");
    $stmt->execute([$id]);
    $message = "Trip deleted successfully!";
}

// Fetch all trips
$stmt = $db->query("SELECT * FROM trip");
$trips = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Trips - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #e0e7ff 0%, #f8fafc 100%);
            min-height: 100vh;
        }
        .table-responsive {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 2px 12px rgba(99,102,241,0.08);
        }
        .action-buttons .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
        .status-badge {
            padding: 0.35rem 0.65rem;
            border-radius: 2rem;
            font-size: 0.875rem;
        }
        .status-Available {
            background-color: #d1fae5;
            color: #065f46;
        }
        .status-Booked {
            background-color: #fee2e2;
            color: #991b1b;
        }
        .status-Rescheduled {
            background-color: #fef08a;
            color: #92400e;
        }
        .status-Cancelled {
            background-color: #e0e7ff;
            color: #3730a3;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="fw-bold">Manage Trips</h1>
            <div>
                <a href="admin_dashboard.php" class="btn btn-outline-primary me-2">
                    <i class="bi bi-house-door"></i> Dashboard
                </a>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTripModal">
                    <i class="bi bi-plus-lg"></i> Add New Trip
                </button>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="table-responsive p-4">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Origin</th>
                        <th>Destination</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Max Seats</th>
                        <th>Available Seats</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($trips as $trip): ?>
                    <tr>
                        <td><?= $trip['tripID'] ?></td>
                        <td><?= htmlspecialchars($trip['origin']) ?></td>
                        <td><?= htmlspecialchars($trip['destination']) ?></td>
                        <td><?= htmlspecialchars($trip['tripDate']) ?></td>
                        <td><?= htmlspecialchars($trip['tripTime']) ?></td>
                        <td><?= htmlspecialchars($trip['totalAmount']) ?></td>
                        <td>
                            <span class="status-badge status-<?= $trip['tripStatus'] ?>">
                                <?= $trip['tripStatus'] ?>
                            </span>
                        </td>
                        <td><?= $trip['maxSeats'] ?></td>
                        <td><?= $trip['availableSeats'] ?></td>
                        <td class="action-buttons">
                            <button type="button" class="btn btn-primary btn-sm edit-btn"
                                data-bs-toggle="modal"
                                data-bs-target="#editTripModal"
                                data-id="<?= $trip['tripID'] ?>"
                                data-origin="<?= htmlspecialchars($trip['origin'], ENT_QUOTES) ?>"
                                data-destination="<?= htmlspecialchars($trip['destination'], ENT_QUOTES) ?>"
                                data-date="<?= $trip['tripDate'] ?>"
                                data-time="<?= $trip['tripTime'] ?>"
                                data-amount="<?= $trip['totalAmount'] ?>"
                                data-status="<?= $trip['tripStatus'] ?>"
                                data-maxseats="<?= $trip['maxSeats'] ?>"
                                data-availableseats="<?= $trip['availableSeats'] ?>">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button type="button" class="btn btn-danger btn-sm delete-btn"
                                data-bs-toggle="modal"
                                data-bs-target="#deleteTripModal"
                                data-id="<?= $trip['tripID'] ?>">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add Trip Modal -->
    <div class="modal fade" id="addTripModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="post" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Trip</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="add_trip" value="1">
                    <div class="mb-2">
                        <label>Origin</label>
                        <input type="text" name="origin" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label>Destination</label>
                        <input type="text" name="destination" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label>Date</label>
                        <input type="date" name="tripDate" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label>Time</label>
                        <input type="time" name="tripTime" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label>Amount</label>
                        <input type="number" name="totalAmount" class="form-control" step="0.01" required>
                    </div>
                    <div class="mb-2">
                        <label>Max Seats</label>
                        <input type="number" name="maxSeats" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label>Available Seats</label>
                        <input type="number" name="availableSeats" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Trip</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Trip Modal -->
    <div class="modal fade" id="editTripModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="post" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Trip</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="tripID" id="edit-trip-id">
                    <input type="hidden" name="edit_trip" value="1">
                    <div class="mb-2">
                        <label>Origin</label>
                        <input type="text" name="origin" id="edit-trip-origin" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label>Destination</label>
                        <input type="text" name="destination" id="edit-trip-destination" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label>Date</label>
                        <input type="date" name="tripDate" id="edit-trip-date" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label>Time</label>
                        <input type="time" name="tripTime" id="edit-trip-time" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label>Amount</label>
                        <input type="number" name="totalAmount" id="edit-trip-amount" class="form-control" step="0.01" required>
                    </div>
                    <div class="mb-2">
                        <label>Max Seats</label>
                        <input type="number" name="maxSeats" id="edit-trip-maxseats" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label>Available Seats</label>
                        <input type="number" name="availableSeats" id="edit-trip-availableseats" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Trip Modal -->
    <div class="modal fade" id="deleteTripModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="post" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Trip</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="tripID" id="delete-trip-id">
                    <input type="hidden" name="delete_trip" value="1">
                    <p>Are you sure you want to delete this trip?</p>
                    <p class="text-danger">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Populate Edit Modal
        document.querySelectorAll('.edit-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.getElementById('edit-trip-id').value = this.getAttribute('data-id');
                document.getElementById('edit-trip-origin').value = this.getAttribute('data-origin');
                document.getElementById('edit-trip-destination').value = this.getAttribute('data-destination');
                document.getElementById('edit-trip-date').value = this.getAttribute('data-date');
                document.getElementById('edit-trip-time').value = this.getAttribute('data-time');
                document.getElementById('edit-trip-amount').value = this.getAttribute('data-amount');
                document.getElementById('edit-trip-maxseats').value = this.getAttribute('data-maxseats');
                document.getElementById('edit-trip-availableseats').value = this.getAttribute('data-availableseats');
            });
        });
        // Populate Delete Modal
        document.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.getElementById('delete-trip-id').value = this.getAttribute('data-id');
            });
        });
    </script>
</body>
</html>
