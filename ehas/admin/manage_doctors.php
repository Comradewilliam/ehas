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
$hospitals = [];
$doctors = [];

// Fetch specialties for dropdown
$stmt = $conn->prepare("SELECT id, name FROM specialties ORDER BY name");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $specialties[] = $row;
}
$stmt->close();

// Fetch hospitals for dropdown
$stmt = $conn->prepare("SELECT id, name FROM hospitals ORDER BY name");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $hospitals[] = $row;
}
$stmt->close();

// Handle Add Doctor
if (isset($_POST['add_doctor'])) {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $specialty_id = $_POST['specialty_id'] ?? '';
    $hospital_id = $_POST['hospital_id'] ?? '';

    if (empty($username) || empty($email) || empty($password) || empty($phone) || empty($specialty_id) || empty($hospital_id)) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } else {
        // Check if username or email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param('ss', $username, $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $error = 'Username or Email already exists. Please choose a different one.';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $role = 'doctor';

            $stmt = $conn->prepare("INSERT INTO users (username, email, password, role, phone, specialty_id, hospital_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('sssssii', $username, $email, $hashed_password, $role, $phone, $specialty_id, $hospital_id);

            if ($stmt->execute()) {
                $success = 'Doctor added successfully.';
            } else {
                $error = 'Error adding doctor: ' . $stmt->error;
            }
        }
        $stmt->close();
    }
}

// Handle Edit Doctor
if (isset($_POST['edit_doctor'])) {
    $id = $_POST['id'] ?? '';
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $specialty_id = $_POST['specialty_id'] ?? '';
    $hospital_id = $_POST['hospital_id'] ?? '';

    if (empty($id) || empty($username) || empty($email) || empty($phone) || empty($specialty_id) || empty($hospital_id)) {
        $error = 'All fields are required for editing.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } else {
        // Check if username or email already exists for another user
        $stmt = $conn->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
        $stmt->bind_param('ssi', $username, $email, $id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $error = 'Username or Email already exists for another user. Please choose a different one.';
        } else {
            $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, phone = ?, specialty_id = ?, hospital_id = ? WHERE id = ?");
            $stmt->bind_param('sssiii', $username, $email, $phone, $specialty_id, $hospital_id, $id);
            if ($stmt->execute()) {
                $success = 'Doctor updated successfully.';
            } else {
                $error = 'Error updating doctor: ' . $stmt->error;
            }
        }
        $stmt->close();
    }
}

// Handle Delete Doctor
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];

    // Check if the doctor has any associated appointments
    $stmt_check_appointments = $conn->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id = ?");
    $stmt_check_appointments->bind_param('i', $delete_id);
    $stmt_check_appointments->execute();
    $appointment_count = $stmt_check_appointments->get_result()->fetch_row()[0];
    $stmt_check_appointments->close();

    if ($appointment_count > 0) {
        $error = 'Cannot delete doctor with pending or approved appointments. Please manage their appointments first.';
    } else {
        // Delete the doctor's user record
        $stmt_delete = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'doctor'");
        $stmt_delete->bind_param('i', $delete_id);
        if ($stmt_delete->execute()) {
            if ($stmt_delete->affected_rows > 0) {
                $success = 'Doctor deleted successfully.';
            } else {
                $error = 'Doctor not found or already deleted.';
            }
        } else {
            $error = 'Error deleting doctor: ' . $stmt_delete->error;
        }
        $stmt_delete->close();
    }
}

// Fetch doctors for display
$query = "SELECT u.id, u.username, u.email, u.phone, s.name as specialty_name, h.name as hospital_name 
          FROM users u 
          LEFT JOIN specialties s ON u.specialty_id = s.id 
          LEFT JOIN hospitals h ON u.hospital_id = h.id 
          WHERE u.role = 'doctor' ORDER BY u.username";
$doctors_result = $conn->query($query);
while ($row = $doctors_result->fetch_assoc()) {
    $doctors[] = $row;
}

?>
    <h2>Manage Doctors</h2>

    <?php if ($error): ?>
        <p class="error-message"> <?= htmlspecialchars($error) ?> </p>
    <?php endif; ?>
    <?php if ($success): ?>
        <p class="success-message"> <?= htmlspecialchars($success) ?> </p>
    <?php endif; ?>

    <h3>Add New Doctor</h3>
    <form action="<?= BASE_URL ?>admin/manage_doctors.php" method="post">
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" required><br>
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required><br>
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required><br>
        <label for="phone">Phone:</label>
        <input type="text" id="phone" name="phone" required><br>
        <label for="specialty_id">Specialty:</label>
        <select id="specialty_id" name="specialty_id" required>
            <option value="">Select Specialty</option>
            <?php foreach ($specialties as $specialty): ?>
                <option value="<?= $specialty['id'] ?>"><?= htmlspecialchars($specialty['name']) ?></option>
            <?php endforeach; ?>
        </select><br>
        <label for="hospital_id">Hospital:</label>
        <select id="hospital_id" name="hospital_id" required>
            <option value="">Select Hospital</option>
            <?php foreach ($hospitals as $hospital): ?>
                <option value="<?= $hospital['id'] ?>"><?= htmlspecialchars($hospital['name']) ?></option>
            <?php endforeach; ?>
        </select><br><br>
        <button type="submit" name="add_doctor">Add Doctor</button>
    </form>

    <h3>Existing Doctors</h3>
    <?php if (empty($doctors)): ?>
        <p style="text-align: center;">No doctors found.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Specialty</th>
                    <th>Hospital</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($doctors as $doctor): ?>
                    <tr>
                        <td><?= htmlspecialchars($doctor['id']) ?></td>
                        <td><?= htmlspecialchars($doctor['username']) ?></td>
                        <td><?= htmlspecialchars($doctor['email']) ?></td>
                        <td><?= htmlspecialchars($doctor['phone']) ?></td>
                        <td><?= htmlspecialchars($doctor['specialty_name'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($doctor['hospital_name'] ?? 'N/A') ?></td>
                        <td>
                            <a href="<?= BASE_URL ?>admin/edit_doctor.php?id=<?= $doctor['id'] ?>">Edit</a> |
                            <a href="<?= BASE_URL ?>admin/manage_doctors.php?delete_id=<?= $doctor['id'] ?>" onclick="return confirm('Are you sure you want to delete this doctor? This action cannot be undone.');">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <h3>Edit Doctor</h3>
    <form id="editDoctorForm" action="<?= BASE_URL ?>admin/manage_doctors.php" method="post">
        <input type="hidden" id="edit_id" name="id">
        <label for="edit_username">Username:</label>
        <input type="text" id="edit_username" name="username" required><br>
        <label for="edit_email">Email:</label>
        <input type="email" id="edit_email" name="email" required><br>
        <label for="edit_phone">Phone:</label>
        <input type="text" id="edit_phone" name="phone" required><br>
        <label for="edit_specialty_id">Specialty:</label>
        <select id="edit_specialty_id" name="specialty_id" required>
            <option value="">Select Specialty</option>
            <?php foreach ($specialties as $specialty): ?>
                <option value="<?= $specialty['id'] ?>"><?= htmlspecialchars($specialty['name']) ?></option>
            <?php endforeach; ?>
        </select><br>
        <label for="edit_hospital_id">Hospital:</label>
        <select id="edit_hospital_id" name="hospital_id" required>
            <option value="">Select Hospital</option>
            <?php foreach ($hospitals as $hospital): ?>
                <option value="<?= $hospital['id'] ?>"><?= htmlspecialchars($hospital['name']) ?></option>
            <?php endforeach; ?>
        </select><br><br>
        <button type="submit" name="edit_doctor">Update Doctor</button>
    </form>

    <p><a href="<?= BASE_URL ?>admin/index.php">Back to Dashboard</a></p>
<?php require_once '../includes/footer.php'; ?> 