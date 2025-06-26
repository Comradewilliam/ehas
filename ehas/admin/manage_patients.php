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

// Handle patient deletion
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];

    // Check if the patient has any associated appointments
    $stmt_check_appointments = $conn->prepare("SELECT COUNT(*) FROM appointments WHERE patient_id = ?");
    $stmt_check_appointments->bind_param('i', $delete_id);
    $stmt_check_appointments->execute();
    $appointment_count = $stmt_check_appointments->get_result()->fetch_row()[0];
    $stmt_check_appointments->close();

    if ($appointment_count > 0) {
        $error = 'Cannot delete patient with pending or approved appointments. Please manage their appointments first.';
    } else {
        // Delete the patient's user record
        $stmt_delete = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'patient'");
        $stmt_delete->bind_param('i', $delete_id);
        if ($stmt_delete->execute()) {
            if ($stmt_delete->affected_rows > 0) {
                $success = 'Patient deleted successfully.';
            } else {
                $error = 'Patient not found or already deleted.';
            }
        } else {
            $error = 'Error deleting patient: ' . $stmt_delete->error;
        }
        $stmt_delete->close();
    }
}

// Fetch all patients
$patients = [];
$query = "SELECT u.id, u.username, u.email, u.phone, u.gender, r.name as region_name, d.name as district_name 
          FROM users u 
          LEFT JOIN regions r ON u.region_id = r.id
          LEFT JOIN districts d ON u.district_id = d.id
          WHERE u.role = 'patient' ORDER BY u.username";
$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    $patients[] = $row;
}

?>
    <h2>Manage Patients</h2>

    <?php if ($error): ?>
        <p class="error-message"> <?= htmlspecialchars($error) ?> </p>
    <?php endif; ?>
    <?php if ($success): ?>
        <p class="success-message"> <?= htmlspecialchars($success) ?> </p>
    <?php endif; ?>

    <?php if (empty($patients)): ?>
        <p style="text-align: center;">No patients registered yet.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Gender</th>
                    <th>Region</th>
                    <th>District</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($patients as $patient): ?>
                    <tr>
                        <td><?= htmlspecialchars($patient['id']) ?></td>
                        <td><?= htmlspecialchars($patient['username']) ?></td>
                        <td><?= htmlspecialchars($patient['email']) ?></td>
                        <td><?= htmlspecialchars($patient['phone']) ?></td>
                        <td><?= htmlspecialchars($patient['gender'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($patient['region_name'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($patient['district_name'] ?? 'N/A') ?></td>
                        <td>
                            <a href="<?= BASE_URL ?>admin/edit_patient.php?id=<?= $patient['id'] ?>">Edit</a> |
                            <a href="<?= BASE_URL ?>admin/manage_patients.php?delete_id=<?= $patient['id'] ?>" onclick="return confirm('Are you sure you want to delete this patient? This action cannot be undone.');">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <p><a href="<?= BASE_URL ?>admin/index.php">Back to Dashboard</a></p>
<?php require_once '../includes/footer.php'; ?>