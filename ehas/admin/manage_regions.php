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
$regions = [];

// Handle Add Region
if (isset($_POST['add_region'])) {
    $name = trim($_POST['name'] ?? '');

    if (empty($name)) {
        $error = 'Region name is required.';
    } else {
        $stmt = $conn->prepare("INSERT INTO regions (name) VALUES (?)");
        $stmt->bind_param('s', $name);
        if ($stmt->execute()) {
            $success = 'Region added successfully.';
            // Clear form fields after successful submission
            $_POST = [];
        } else {
            $error = 'Error adding region: ' . $stmt->error;
        }
        $stmt->close();
    }
}

// Handle Edit Region
if (isset($_POST['edit_region'])) {
    $id = $_POST['id'] ?? '';
    $name = trim($_POST['name'] ?? '');

    if (empty($id) || empty($name)) {
        $error = 'Region ID and name are required for editing.';
    } else {
        $stmt = $conn->prepare("UPDATE regions SET name = ? WHERE id = ?");
        $stmt->bind_param('si', $name, $id);
        if ($stmt->execute()) {
            $success = 'Region updated successfully.';
        } else {
            $error = 'Error updating region: ' . $stmt->error;
        }
        $stmt->close();
    }
}

// Handle Delete Region
if (isset($_GET['delete'])) {
    $id = $_GET['delete'] ?? '';

    if (empty($id)) {
        $error = 'Region ID is required for deletion.';
    } else {
        // Check if there are associated districts
        $stmt_check = $conn->prepare("SELECT COUNT(*) FROM districts WHERE region_id = ?");
        $stmt_check->bind_param('i', $id);
        $stmt_check->execute();
        $stmt_check->bind_result($district_count);
        $stmt_check->fetch();
        $stmt_check->close();

        if ($district_count > 0) {
            $error = 'Cannot delete region with associated districts. Please delete districts first.';
        } else {
            $stmt = $conn->prepare("DELETE FROM regions WHERE id = ?");
            $stmt->bind_param('i', $id);
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    $success = 'Region deleted successfully.';
                } else {
                    $error = 'Region not found or already deleted.';
                }
            } else {
                $error = 'Error deleting region: ' . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// Fetch regions for display (after any modifications)
$regions_result = $conn->query("SELECT id, name FROM regions ORDER BY name");
while ($row = $regions_result->fetch_assoc()) {
    $regions[] = $row;
}

?>
    <h2>Manage Regions</h2>

    <?php if ($error): ?>
        <p class="error-message"> <?= htmlspecialchars($error) ?> </p>
    <?php endif; ?>
    <?php if ($success): ?>
        <p class="success-message"> <?= htmlspecialchars($success) ?> </p>
    <?php endif; ?>

    <h3>Add New Region</h3>
    <form action="<?= BASE_URL ?>admin/manage_regions.php" method="post">
        <label for="name">Region Name:</label>
        <input type="text" id="name" name="name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required><br><br>
        <button type="submit" name="add_region">Add Region</button>
    </form>

    <h3>Existing Regions</h3>
    <?php if (empty($regions)): ?>
        <p style="text-align: center;">No regions found.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Region Name</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($regions as $region): ?>
                    <tr>
                        <td><?= htmlspecialchars($region['id']) ?></td>
                        <td><?= htmlspecialchars($region['name']) ?></td>
                        <td>
                            <a href="#" onclick="
                                document.getElementById('edit_id').value='<?= $region['id'] ?>';
                                document.getElementById('edit_name').value='<?= htmlspecialchars($region['name'], ENT_QUOTES) ?>';
                                document.getElementById('editRegionForm').scrollIntoView({ behavior: 'smooth' });
                                return false;
                            ">Edit</a> |
                            <a href="<?= BASE_URL ?>admin/manage_regions.php?delete=<?= $region['id'] ?>" onclick="return confirm('Are you sure you want to delete this region?');">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <h3>Edit Region</h3>
    <form id="editRegionForm" action="<?= BASE_URL ?>admin/manage_regions.php" method="post">
        <input type="hidden" id="edit_id" name="id">
        <label for="edit_name">Region Name:</label>
        <input type="text" id="edit_name" name="name" required><br><br>
        <button type="submit" name="edit_region">Update Region</button>
    </form>

    <p><a href="<?= BASE_URL ?>admin/index.php">Back to Dashboard</a></p>
<?php require_once '../includes/footer.php'; ?> 