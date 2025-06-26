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
$specialties = [];

// Handle Add Specialty
if (isset($_POST['add_specialty'])) {
    $name = trim($_POST['name'] ?? '');

    if (empty($name)) {
        $error = 'Specialty name is required.';
    } else {
        $stmt = $conn->prepare("INSERT INTO specialties (name) VALUES (?)");
        $stmt->bind_param('s', $name);
        if ($stmt->execute()) {
            $success = 'Specialty added successfully.';
            // Clear form fields after successful submission
            $_POST = [];
        } else {
            $error = 'Error adding specialty: ' . $stmt->error;
        }
        $stmt->close();
    }
}

// Handle Edit Specialty
if (isset($_POST['edit_specialty'])) {
    $id = $_POST['id'] ?? '';
    $name = trim($_POST['name'] ?? '');

    if (empty($id) || empty($name)) {
        $error = 'Specialty ID and name are required for editing.';
    } else {
        $stmt = $conn->prepare("UPDATE specialties SET name = ? WHERE id = ?");
        $stmt->bind_param('si', $name, $id);
        if ($stmt->execute()) {
            $success = 'Specialty updated successfully.';
        } else {
            $error = 'Error updating specialty: ' . $stmt->error;
        }
        $stmt->close();
    }
}

// Handle Delete Specialty
if (isset($_GET['delete'])) {
    $id = $_GET['delete'] ?? '';

    if (empty($id)) {
        $error = 'Specialty ID is required for deletion.';
    } else {
        // Check if there are doctors associated with this specialty
        $stmt_check = $conn->prepare("SELECT COUNT(*) FROM users WHERE specialty_id = ? AND role = 'doctor'");
        $stmt_check->bind_param('i', $id);
        $stmt_check->execute();
        $stmt_check->bind_result($doctor_count);
        $stmt_check->fetch();
        $stmt_check->close();

        if ($doctor_count > 0) {
            $error = 'Cannot delete specialty with associated doctors. Please reassign doctors first.';
        } else {
            $stmt = $conn->prepare("DELETE FROM specialties WHERE id = ?");
            $stmt->bind_param('i', $id);
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    $success = 'Specialty deleted successfully.';
                } else {
                    $error = 'Specialty not found or already deleted.';
                }
            } else {
                $error = 'Error deleting specialty: ' . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// Fetch specialties for display (after any modifications)
$specialties_result = $conn->query("SELECT id, name FROM specialties ORDER BY name");
while ($row = $specialties_result->fetch_assoc()) {
    $specialties[] = $row;
}

?>
    <h2>Manage Specialties</h2>

    <?php if ($error): ?>
        <p class="error-message"> <?= htmlspecialchars($error) ?> </p>
    <?php endif; ?>
    <?php if ($success): ?>
        <p class="success-message"> <?= htmlspecialchars($success) ?> </p>
    <?php endif; ?>

    <h3>Add New Specialty</h3>
    <form action="<?= BASE_URL ?>admin/manage_specialties.php" method="post">
        <label for="name">Specialty Name:</label>
        <input type="text" id="name" name="name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required><br><br>
        <button type="submit" name="add_specialty">Add Specialty</button>
    </form>

    <h3>Existing Specialties</h3>
    <?php if (empty($specialties)): ?>
        <p style="text-align: center;">No specialties found.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Specialty Name</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($specialties as $specialty): ?>
                    <tr>
                        <td><?= htmlspecialchars($specialty['id']) ?></td>
                        <td><?= htmlspecialchars($specialty['name']) ?></td>
                        <td>
                            <a href="#" onclick="
                                document.getElementById('edit_id').value='<?= $specialty['id'] ?>';
                                document.getElementById('edit_name').value='<?= htmlspecialchars($specialty['name'], ENT_QUOTES) ?>';
                                document.getElementById('editSpecialtyForm').scrollIntoView({ behavior: 'smooth' });
                                return false;
                            ">Edit</a> |
                            <a href="<?= BASE_URL ?>admin/manage_specialties.php?delete=<?= $specialty['id'] ?>" onclick="return confirm('Are you sure you want to delete this specialty?');">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <h3>Edit Specialty</h3>
    <form id="editSpecialtyForm" action="<?= BASE_URL ?>admin/manage_specialties.php" method="post">
        <input type="hidden" id="edit_id" name="id">
        <label for="edit_name">Specialty Name:</label>
        <input type="text" id="edit_name" name="name" required><br><br>
        <button type="submit" name="edit_specialty">Update Specialty</button>
    </form>

    <p><a href="<?= BASE_URL ?>admin/index.php">Back to Dashboard</a></p>
<?php require_once '../includes/footer.php'; ?> 