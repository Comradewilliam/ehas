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

$doctor_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$error = '';
$success = '';

if (!$doctor_id) {
    $error = 'Invalid doctor ID.';
} else {
    // Fetch doctor and user info
    $stmt = $conn->prepare("SELECT u.id as user_id, u.username, u.email, u.phone, u.specialty_id, u.hospital_id FROM users u WHERE u.id = ? AND u.role = 'doctor'");
    $stmt->bind_param('i', $doctor_id);
    $stmt->execute();
    $doctor = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$doctor) {
        $error = 'Doctor not found.';
    }
}

// Fetch all hospitals and specialties for dropdowns
$specialties = [];
$stmt = $conn->prepare("SELECT id, name FROM specialties ORDER BY name");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $specialties[] = $row;
}
$stmt->close();

$hospitals = [];
$stmt = $conn->prepare("SELECT id, name FROM hospitals ORDER BY name");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $hospitals[] = $row;
}
$stmt->close();

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? ''; // Hidden ID field
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $specialty_id = $_POST['specialty_id'] ?? '';
    $hospital_id = $_POST['hospital_id'] ?? '';

    if (empty($id) || empty($username) || empty($email) || empty($phone) || empty($specialty_id) || empty($hospital_id)) {
        $error = 'All fields are required for updating.';
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
                // Refresh doctor data after successful update
                $stmt = $conn->prepare("SELECT u.id as user_id, u.username, u.email, u.phone, u.specialty_id, u.hospital_id FROM users u WHERE u.id = ? AND u.role = 'doctor'");
                $stmt->bind_param('i', $doctor_id);
                $stmt->execute();
                $doctor = $stmt->get_result()->fetch_assoc();
                $stmt->close();
            } else {
                $error = 'Error updating doctor: ' . $stmt->error;
            }
        }
        $stmt->close();
    }
}

// Display the form only if doctor data is available and no error from fetching
if (!empty($doctor) && empty($error)) {
?>
    <h2>Edit Doctor: <?= htmlspecialchars($doctor['username']) ?></h2>
    <?php if ($error): ?>
        <p class="error-message"> <?= htmlspecialchars($error) ?> </p>
    <?php endif; ?>
    <?php if ($success): ?>
        <p class="success-message"> <?= htmlspecialchars($success) ?> </p>
    <?php endif; ?>
    <form method="post" action="<?= BASE_URL ?>admin/edit_doctor.php?id=<?= $doctor_id ?>">
        <input type="hidden" name="id" value="<?= htmlspecialchars($doctor['user_id']) ?>">
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" value="<?= htmlspecialchars($doctor['username']) ?>" required><br>
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" value="<?= htmlspecialchars($doctor['email']) ?>" required><br>
        <label for="phone">Phone:</label>
        <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($doctor['phone']) ?>" required><br>
        <label for="specialty_id">Specialty:</label>
        <select id="specialty_id" name="specialty_id" required>
            <option value="">Select Specialty</option>
            <?php foreach ($specialties as $specialty): ?>
                <option value="<?= $specialty['id'] ?>" <?= ($doctor['specialty_id'] == $specialty['id']) ? 'selected' : '' ?>><?= htmlspecialchars($specialty['name']) ?></option>
            <?php endforeach; ?>
        </select><br>
        <label for="hospital_id">Hospital:</label>
        <select id="hospital_id" name="hospital_id" required>
            <option value="">Select Hospital</option>
            <?php foreach ($hospitals as $hospital): ?>
                <option value="<?= $hospital['id'] ?>" <?= ($doctor['hospital_id'] == $hospital['id']) ? 'selected' : '' ?>><?= htmlspecialchars($hospital['name']) ?></option>
            <?php endforeach; ?>
        </select><br><br>
        <button type="submit">Save Changes</button>
    </form>
    <p><a href="<?= BASE_URL ?>admin/manage_doctors.php">Back to Manage Doctors</a></p>
<?php
} else {
    echo '<p class="error-message">' . htmlspecialchars($error) . '</p>';
    echo '<p style="text-align: center;"><a href="' . BASE_URL . 'admin/manage_doctors.php">Back to Manage Doctors</a></p>';
}
require_once '../includes/footer.php';
?>