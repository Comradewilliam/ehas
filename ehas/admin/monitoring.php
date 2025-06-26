<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../includes/db.php';
require_once '../includes/session.php';
require_once '../includes/config.php';
require_once '../includes/header.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ' . BASE_URL . 'login.php');
    exit();
}

$error = '';
$success = '';
$appointments = [];
$filter_status = $_GET['status'] ?? ''; // 'pending', 'approved', 'rejected'

// Handle status updates for appointments
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['appointment_id']) && isset($_POST['new_status'])) {
    $appointment_id = $_POST['appointment_id'];
    $new_status = $_POST['new_status'];

    if (!in_array($new_status, ['approved', 'rejected', 'completed'])) {
        $error = 'Invalid status provided.';
    } else {
        $stmt = $conn->prepare("UPDATE appointments SET status = ? WHERE id = ?");
        $stmt->bind_param('si', $new_status, $appointment_id);
        if ($stmt->execute()) {
            $success = 'Appointment status updated successfully.';
        } else {
            $error = 'Error updating appointment status: ' . $stmt->error;
        }
        $stmt->close();
    }
}

// Fetch appointments with user, doctor, specialty, and hospital names
$query = "SELECT a.id, 
                 p.username as patient_name, 
                 d.username as doctor_name, 
                 s.name as specialty_name, 
                 h.name as hospital_name, 
                 a.appointment_time, 
                 a.reason, 
                 a.status
          FROM appointments a
          JOIN users p ON a.patient_id = p.id
          JOIN users d ON a.doctor_id = d.id
          JOIN specialties s ON a.specialty_id = s.id
          JOIN hospitals h ON a.hospital_id = h.id";

$params = '';
$param_values = [];

if (!empty($filter_status) && in_array($filter_status, ['pending', 'approved', 'rejected', 'completed'])) {
    $query .= " WHERE a.status = ?";
    $params .= 's';
    $param_values[] = &$filter_status;
}

$query .= " ORDER BY a.appointment_time DESC";

$stmt = $conn->prepare($query);
if ($stmt) {
    if (!empty($params)) {
        $stmt->bind_param($params, ...$param_values);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $appointments[] = $row;
    }
    $stmt->close();
} else {
    $error = 'Database query preparation error: ' . $conn->error;
}

?>
    <h2>Appointment Monitoring</h2>

    <?php if ($error): ?>
        <p class="error-message"> <?= htmlspecialchars($error) ?> </p>
    <?php endif; ?>
    <?php if ($success): ?>
        <p class="success-message"> <?= htmlspecialchars($success) ?> </p>
    <?php endif; ?>

    <div class="filter-options">
        <label for="status_filter">Filter by Status:</label>
        <select id="status_filter" onchange="location = this.value;">
            <option value="<?= BASE_URL ?>admin/monitoring.php" <?= empty($filter_status) ? 'selected' : '' ?>>All</option>
            <option value="<?= BASE_URL ?>admin/monitoring.php?status=pending" <?= ($filter_status === 'pending') ? 'selected' : '' ?>>Pending</option>
            <option value="<?= BASE_URL ?>admin/monitoring.php?status=approved" <?= ($filter_status === 'approved') ? 'selected' : '' ?>>Approved</option>
            <option value="<?= BASE_URL ?>admin/monitoring.php?status=rejected" <?= ($filter_status === 'rejected') ? 'selected' : '' ?>>Rejected</option>
            <option value="<?= BASE_URL ?>admin/monitoring.php?status=completed" <?= ($filter_status === 'completed') ? 'selected' : '' ?>>Completed</option>
        </select>
    </div>

    <?php if (empty($appointments)): ?>
        <p style="text-align: center;">No appointments found.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Patient</th>
                    <th>Doctor</th>
                    <th>Specialty</th>
                    <th>Hospital</th>
                    <th>Date & Time</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($appointments as $appointment): ?>
                    <tr>
                        <td><?= htmlspecialchars($appointment['id']) ?></td>
                        <td><?= htmlspecialchars($appointment['patient_name']) ?></td>
                        <td><?= htmlspecialchars($appointment['doctor_name']) ?></td>
                        <td><?= htmlspecialchars($appointment['specialty_name']) ?></td>
                        <td><?= htmlspecialchars($appointment['hospital_name']) ?></td>
                        <td><?= htmlspecialchars($appointment['appointment_time']) ?></td>
                        <td><?= htmlspecialchars($appointment['reason']) ?></td>
                        <td><?= htmlspecialchars(ucfirst($appointment['status'])) ?></td>
                        <td>
                            <?php if ($appointment['status'] === 'pending'): ?>
                                <form method="post" action="<?= BASE_URL ?>admin/monitoring.php?status=<?= htmlspecialchars($filter_status) ?>" style="display:inline;">
                                    <input type="hidden" name="appointment_id" value="<?= $appointment['id'] ?>">
                                    <input type="hidden" name="new_status" value="approved">
                                    <button type="submit" onclick="return confirm('Approve this appointment?');">Approve</button>
                                </form>
                                <form method="post" action="<?= BASE_URL ?>admin/monitoring.php?status=<?= htmlspecialchars($filter_status) ?>" style="display:inline;">
                                    <input type="hidden" name="appointment_id" value="<?= $appointment['id'] ?>">
                                    <input type="hidden" name="new_status" value="rejected">
                                    <button type="submit" onclick="return confirm('Reject this appointment?');">Reject</button>
                                </form>
                            <?php elseif ($appointment['status'] === 'approved'): ?>
                                <form method="post" action="<?= BASE_URL ?>admin/monitoring.php?status=<?= htmlspecialchars($filter_status) ?>" style="display:inline;">
                                    <input type="hidden" name="appointment_id" value="<?= $appointment['id'] ?>">
                                    <input type="hidden" name="new_status" value="completed">
                                    <button type="submit" onclick="return confirm('Mark this appointment as completed?');">Mark as Completed</button>
                                </form>
                            <?php else: ?>
                                No actions
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <p><a href="<?= BASE_URL ?>admin/index.php">Back to Dashboard</a></p>
<?php require_once '../includes/footer.php'; ?>