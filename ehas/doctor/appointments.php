<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../includes/db.php';
require_once '../includes/session.php';
require_once '../includes/config.php';
require_once '../includes/header.php';

// Check if user is logged in and is a doctor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header('Location: ' . BASE_URL . 'login.php');
    exit();
}

$doctor_id = $_SESSION['user_id']; // Use user_id directly from session as doctor_id for users table
$error = '';
$success = '';
$appointments = [];

// Handle status update
if (isset($_POST['update_status'])) {
    $appointment_id = $_POST['appointment_id'] ?? '';
    $new_status = $_POST['new_status'] ?? '';

    if (empty($appointment_id) || empty($new_status)) {
        $error = 'Appointment ID and new status are required.';
    } else {
        $stmt = $conn->prepare("UPDATE appointments SET status = ? WHERE id = ? AND doctor_id = ?");
        $stmt->bind_param('sii', $new_status, $appointment_id, $doctor_id);
        if ($stmt->execute()) {
            $success = 'Appointment status updated successfully.';
        } else {
            $error = 'Error updating status: ' . $stmt->error;
        }
        $stmt->close();
    }
}

// Fetch appointments for the logged-in doctor
$query = "SELECT a.id, a.appointment_time, a.reason, a.status, u.username as patient_username, u.email as patient_email 
          FROM appointments a 
          JOIN users u ON a.patient_id = u.id 
          WHERE a.doctor_id = ? 
          ORDER BY a.appointment_time DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $doctor_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $appointments[] = $row;
}
$stmt->close();

?>
    <h2>My Appointments</h2>

    <?php if ($error): ?>
        <p class="error-message"> <?= htmlspecialchars($error) ?> </p>
    <?php endif; ?>
    <?php if ($success): ?>
        <p class="success-message"> <?= htmlspecialchars($success) ?> </p>
    <?php endif; ?>

    <?php if (empty($appointments)): ?>
        <p style="text-align: center;">You have no appointments scheduled.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Date</th>
                    <th>Patient</th>
                    <th>Patient Email</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($appointments as $appointment): ?>
                    <tr>
                        <td><?= htmlspecialchars($appointment['id']) ?></td>
                        <td><?= htmlspecialchars($appointment['appointment_time']) ?></td>
                        <td><?= htmlspecialchars($appointment['patient_username']) ?></td>
                        <td><?= htmlspecialchars($appointment['patient_email']) ?></td>
                        <td><?= htmlspecialchars($appointment['reason']) ?></td>
                        <td><?= htmlspecialchars(ucfirst($appointment['status'])) ?></td>
                        <td>
                            <form action="<?= BASE_URL ?>doctor/appointments.php" method="post" style="display:inline;">
                                <input type="hidden" name="appointment_id" value="<?= $appointment['id'] ?>">
                                <select name="new_status" onchange="this.form.submit()">
                                    <option value="pending" <?= ($appointment['status'] === 'pending') ? 'selected' : '' ?>>Pending</option>
                                    <option value="approved" <?= ($appointment['status'] === 'approved') ? 'selected' : '' ?>>Approved</option>
                                    <option value="rejected" <?= ($appointment['status'] === 'rejected') ? 'selected' : '' ?>>Rejected</option>
                                    <option value="completed" <?= ($appointment['status'] === 'completed') ? 'selected' : '' ?>>Completed</option>
                                </select>
                                <input type="hidden" name="update_status" value="1">
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
    <p><a href="<?= BASE_URL ?>doctor/index.php">Back to Dashboard</a></p>
<?php require_once '../includes/footer.php'; ?> 