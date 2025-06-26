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
$hospitals = [];

// Handle Add Hospital
if (isset($_POST['add_hospital'])) {
    $name = trim($_POST['name'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if (empty($name) || empty($address) || empty($phone) || empty($email)) {
        $error = 'All fields are required to add a hospital.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } else {
        $stmt = $conn->prepare("INSERT INTO hospitals (name, address, phone, email) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('ssss', $name, $address, $phone, $email);
        if ($stmt->execute()) {
            $success = 'Hospital added successfully.';
            // Clear form fields after successful submission
            $_POST = [];
        } else {
            $error = 'Error adding hospital: ' . $stmt->error;
        }
        $stmt->close();
    }
}

// Handle Edit Hospital
if (isset($_POST['edit_hospital'])) {
    $id = $_POST['id'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if (empty($id) || empty($name) || empty($address) || empty($phone) || empty($email)) {
        $error = 'All fields are required for updating a hospital.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } else {
        $stmt = $conn->prepare("UPDATE hospitals SET name = ?, address = ?, phone = ?, email = ? WHERE id = ?");
        $stmt->bind_param('ssssi', $name, $address, $phone, $email, $id);
        if ($stmt->execute()) {
            $success = 'Hospital updated successfully.';
        } else {
            $error = 'Error updating hospital: ' . $stmt->error;
        }
        $stmt->close();
    }
}

// Handle Delete Hospital
if (isset($_GET['delete'])) {
    $id = $_GET['delete'] ?? '';

    if (empty($id)) {
        $error = 'Hospital ID is required for deletion.';
    } else {
        // Check if there are any doctors or appointments associated with this hospital
        $stmt_check_doctors = $conn->prepare("SELECT COUNT(*) FROM users WHERE hospital_id = ? AND role = 'doctor'");
        $stmt_check_doctors->bind_param('i', $id);
        $stmt_check_doctors->execute();
        $doctor_count = $stmt_check_doctors->get_result()->fetch_row()[0];
        $stmt_check_doctors->close();

        $stmt_check_appointments = $conn->prepare("SELECT COUNT(*) FROM appointments WHERE hospital_id = ?");
        $stmt_check_appointments->bind_param('i', $id);
        $stmt_check_appointments->execute();
        $appointment_count = $stmt_check_appointments->get_result()->fetch_row()[0];
        $stmt_check_appointments->close();

        if ($doctor_count > 0) {
            $error = 'Cannot delete hospital. There are doctors associated with this hospital. Please reassign them first.';
        } elseif ($appointment_count > 0) {
            $error = 'Cannot delete hospital. There are appointments associated with this hospital. Please manage them first.';
        } else {
            $stmt = $conn->prepare("DELETE FROM hospitals WHERE id = ?");
            $stmt->bind_param('i', $id);
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    $success = 'Hospital deleted successfully.';
                } else {
                    $error = 'Hospital not found or already deleted.';
                }
            } else {
                $error = 'Error deleting hospital: ' . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// Fetch hospitals for display (after any modifications)
$query = "SELECT id, name, address, phone, email FROM hospitals ORDER BY name";
$hospitals_result = $conn->query($query);
while ($row = $hospitals_result->fetch_assoc()) {
    $hospitals[] = $row;
}

?>
    <h2>Manage Hospitals</h2>

    <?php if ($error): ?>
        <p class="error-message"> <?= htmlspecialchars($error) ?> </p>
    <?php endif; ?>
    <?php if ($success): ?>
        <p class="success-message"> <?= htmlspecialchars($success) ?> </p>
    <?php endif; ?>

    <h3>Add New Hospital</h3>
    <form action="<?= BASE_URL ?>admin/manage_hospitals.php" method="post">
        <label for="name">Hospital Name:</label>
        <input type="text" id="name" name="name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required><br>
        <label for="address">Address:</label>
        <input type="text" id="address" name="address" value="<?= htmlspecialchars($_POST['address'] ?? '') ?>" required><br>
        <label for="phone">Phone:</label>
        <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" required><br>
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required><br><br>
        <button type="submit" name="add_hospital">Add Hospital</button>
    </form>

    <h3>Existing Hospitals</h3>
    <?php if (empty($hospitals)): ?>
        <p style="text-align: center;">No hospitals found.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Hospital Name</th>
                    <th>Address</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($hospitals as $hospital): ?>
                    <tr>
                        <td><?= htmlspecialchars($hospital['id']) ?></td>
                        <td><?= htmlspecialchars($hospital['name']) ?></td>
                        <td><?= htmlspecialchars($hospital['address'] ?? '') ?></td>
                        <td><?= htmlspecialchars($hospital['phone'] ?? '') ?></td>
                        <td><?= htmlspecialchars($hospital['email'] ?? '') ?></td>
                        <td>
                            <a href="#" onclick="
                                document.getElementById('edit_id').value='<?= $hospital['id'] ?>';
                                document.getElementById('edit_name').value='<?= htmlspecialchars($hospital['name'], ENT_QUOTES) ?>';
                                document.getElementById('edit_address').value='<?= htmlspecialchars($hospital['address'] ?? '', ENT_QUOTES) ?>';
                                document.getElementById('edit_phone').value='<?= htmlspecialchars($hospital['phone'] ?? '', ENT_QUOTES) ?>';
                                document.getElementById('edit_email').value='<?= htmlspecialchars($hospital['email'] ?? '', ENT_QUOTES) ?>';
                                document.getElementById('editHospitalForm').scrollIntoView({ behavior: 'smooth' });
                                return false;
                            ">Edit</a> |
                            <a href="<?= BASE_URL ?>admin/manage_hospitals.php?delete=<?= $hospital['id'] ?>" onclick="return confirm('Are you sure you want to delete this hospital?');">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <h3>Edit Hospital</h3>
    <form id="editHospitalForm" action="<?= BASE_URL ?>admin/manage_hospitals.php" method="post">
        <input type="hidden" id="edit_id" name="id">
        <label for="edit_name">Hospital Name:</label>
        <input type="text" id="edit_name" name="name" required><br>
        <label for="edit_address">Address:</label>
        <input type="text" id="edit_address" name="address" required><br>
        <label for="edit_phone">Phone:</label>
        <input type="text" id="edit_phone" name="phone" required><br>
        <label for="edit_email">Email:</label>
        <input type="email" id="edit_email" name="email" required><br><br>
        <button type="submit" name="edit_hospital">Update Hospital</button>
    </form>

    <p><a href="<?= BASE_URL ?>admin/index.php">Back to Dashboard</a></p>
<?php require_once '../includes/footer.php'; ?> 