<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../includes/db.php';
require_once '../includes/session.php';
require_once '../includes/config.php';
require_once '../includes/header.php';

// Check if user is logged in and is a patient
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header('Location: ' . BASE_URL . 'login.php');
    exit();
}

$patient_id = $_SESSION['user_id'];
$error = '';
$success = '';
$appointments = [];

// Handle cancellation
if (isset($_POST['cancel_appointment'])) {
    $appointment_id = $_POST['appointment_id'] ?? '';

    if (empty($appointment_id)) {
        $error = 'Appointment ID is required to cancel.';
    } else {
        $stmt = $conn->prepare("UPDATE appointments SET status = 'canceled' WHERE id = ? AND patient_id = ? AND status = 'pending'");
        $stmt->bind_param('ii', $appointment_id, $patient_id);
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $success = 'Appointment canceled successfully.';
            } else {
                $error = 'Could not cancel appointment. It might not be pending or does not belong to you.';
            }
        } else {
            $error = 'Error canceling appointment: ' . $stmt->error;
        }
        $stmt->close();
    }
}

// Fetch patient's appointments
$query = "SELECT a.id, a.appointment_date, a.reason, a.status, d.username as doctor_username, s.name as specialty_name, h.name as hospital_name 
          FROM appointments a 
          JOIN users d ON a.doctor_id = d.id 
          JOIN specialties s ON d.specialty_id = s.id 
          JOIN hospitals h ON d.hospital_id = h.id 
          WHERE a.patient_id = ? 
          ORDER BY a.appointment_date DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $patient_id);
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
                    <th>Doctor</th>
                    <th>Specialty</th>
                    <th>Hospital</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($appointments as $appointment): ?>
                    <tr>
                        <td><?= htmlspecialchars($appointment['id']) ?></td>
                        <td><?= htmlspecialchars($appointment['appointment_date']) ?></td>
                        <td><?= htmlspecialchars($appointment['doctor_username']) ?></td>
                        <td><?= htmlspecialchars($appointment['specialty_name']) ?></td>
                        <td><?= htmlspecialchars($appointment['hospital_name']) ?></td>
                        <td><?= htmlspecialchars($appointment['reason']) ?></td>
                        <td><?= htmlspecialchars(ucfirst($appointment['status'])) ?></td>
                        <td>
                            <?php if ($appointment['status'] === 'pending'): ?>
                                <form action="<?= BASE_URL ?>patient/appointments.php" method="post" style="display:inline;">
                                    <input type="hidden" name="appointment_id" value="<?= $appointment['id'] ?>">
                                    <button type="submit" name="cancel_appointment" onclick="return confirm('Are you sure you want to cancel this appointment?');" style="background-color: #dc3545;">Cancel</button>
                                </form>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
<?php require_once '../includes/footer.php'; ?> 