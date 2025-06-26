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
$districts = [];

// Fetch regions for dropdown and display
$stmt = $conn->prepare("SELECT id, name FROM regions ORDER BY name");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $regions[] = $row;
}
$stmt->close();

// Handle Add District
if (isset($_POST['add_district'])) {
    $name = trim($_POST['name'] ?? '');
    $region_id = $_POST['region_id'] ?? '';

    if (empty($name) || empty($region_id)) {
        $error = 'District name and region are required.';
    } else {
        $stmt = $conn->prepare("INSERT INTO districts (name, region_id) VALUES (?, ?)");
        $stmt->bind_param('si', $name, $region_id);
        if ($stmt->execute()) {
            $success = 'District added successfully.';
            // Clear form fields after successful submission
            $_POST = [];
        } else {
            $error = 'Error adding district: ' . $stmt->error;
        }
        $stmt->close();
    }
}

// Handle Edit District
if (isset($_POST['edit_district'])) {
    $id = $_POST['id'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $region_id = $_POST['region_id'] ?? '';

    if (empty($id) || empty($name) || empty($region_id)) {
        $error = 'District ID, name, and region are required for editing.';
    } else {
        $stmt = $conn->prepare("UPDATE districts SET name = ?, region_id = ? WHERE id = ?");
        $stmt->bind_param('sii', $name, $region_id, $id);
        if ($stmt->execute()) {
            $success = 'District updated successfully.';
        } else {
            $error = 'Error updating district: ' . $stmt->error;
        }
        $stmt->close();
    }
}

// Handle Delete District
if (isset($_GET['delete'])) {
    $id = $_GET['delete'] ?? '';

    if (empty($id)) {
        $error = 'District ID is required for deletion.';
    } else {
        $stmt = $conn->prepare("DELETE FROM districts WHERE id = ?");
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $success = 'District deleted successfully.';
            } else {
                $error = 'District not found or already deleted.';
            }
        } else {
            $error = 'Error deleting district: ' . $stmt->error;
        }
        $stmt->close();
    }
}

// Fetch districts for display (after any modifications)
$query = "SELECT d.id, d.name, r.name as region_name, r.id as region_id FROM districts d JOIN regions r ON d.region_id = r.id ORDER BY r.name, d.name";
$districts_result = $conn->query($query);
while ($row = $districts_result->fetch_assoc()) {
    $districts[] = $row;
}

?>
    <h2>Manage Districts</h2>

    <?php if ($error): ?>
        <p class="error-message"> <?= htmlspecialchars($error) ?> </p>
    <?php endif; ?>
    <?php if ($success): ?>
        <p class="success-message"> <?= htmlspecialchars($success) ?> </p>
    <?php endif; ?>

    <h3>Add New District</h3>
    <form action="<?= BASE_URL ?>admin/manage_districts.php" method="post">
        <label for="name">District Name:</label>
        <input type="text" id="name" name="name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required><br>
        <label for="region_id">Region:</label>
        <select id="region_id" name="region_id" required>
            <option value="">Select Region</option>
            <?php foreach ($regions as $region): ?>
                <option value="<?= $region['id'] ?>" <?= (isset($_POST['region_id']) && $_POST['region_id'] == $region['id']) ? 'selected' : '' ?>><?= htmlspecialchars($region['name']) ?></option>
            <?php endforeach; ?>
        </select><br><br>
        <button type="submit" name="add_district">Add District</button>
    </form>

    <h3>Existing Districts</h3>
    <?php if (empty($districts)): ?>
        <p style="text-align: center;">No districts found.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>District Name</th>
                    <th>Region</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($districts as $district): ?>
                    <tr>
                        <td><?= htmlspecialchars($district['id']) ?></td>
                        <td><?= htmlspecialchars($district['name']) ?></td>
                        <td><?= htmlspecialchars($district['region_name']) ?></td>
                        <td>
                            <a href="#" onclick="
                                document.getElementById('edit_id').value='<?= $district['id'] ?>';
                                document.getElementById('edit_name').value='<?= htmlspecialchars($district['name'], ENT_QUOTES) ?>';
                                document.getElementById('edit_region_id').value='<?= $district['region_id'] ?>';
                                document.getElementById('editDistrictForm').scrollIntoView({ behavior: 'smooth' });
                                return false;
                            ">Edit</a> |
                            <a href="<?= BASE_URL ?>admin/manage_districts.php?delete=<?= $district['id'] ?>" onclick="return confirm('Are you sure you want to delete this district?');">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <h3>Edit District</h3>
    <form id="editDistrictForm" action="<?= BASE_URL ?>admin/manage_districts.php" method="post">
        <input type="hidden" id="edit_id" name="id">
        <label for="edit_name">District Name:</label>
        <input type="text" id="edit_name" name="name" required><br>
        <label for="edit_region_id">Region:</label>
        <select id="edit_region_id" name="region_id" required>
            <option value="">Select Region</option>
            <?php foreach ($regions as $region): ?>
                <option value="<?= $region['id'] ?>"><?= htmlspecialchars($region['name']) ?></option>
            <?php endforeach; ?>
        </select><br><br>
        <button type="submit" name="edit_district">Update District</button>
    </form>

    <p><a href="<?= BASE_URL ?>admin/index.php">Back to Dashboard</a></p>
<?php require_once '../includes/footer.php'; ?>