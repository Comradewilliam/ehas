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

// Fetch specialties for dropdown
$specialties = [];
$stmt = $conn->prepare("SELECT id, name FROM specialties ORDER BY name");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $specialties[] = $row;
}
$stmt->close();

// Fetch hospitals for dropdown
$hospitals = [];
$stmt = $conn->prepare("SELECT id, name FROM hospitals ORDER BY name");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $hospitals[] = $row;
}
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
            $error = 'Username or Email already exists.';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $role = 'doctor';

            $stmt = $conn->prepare("INSERT INTO users (username, email, password, phone, role, specialty_id, hospital_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('sssssii', $username, $email, $hashed_password, $phone, $role, $specialty_id, $hospital_id);

            if ($stmt->execute()) {
                $success = 'Doctor registered successfully.';
                // Clear form fields after successful submission
                $_POST = []; 
            } else {
                $error = 'Error registering doctor: ' . $stmt->error;
            }
        }
        $stmt->close();
    }
}
?>
    <h2>Add New Doctor</h2>

    <?php if ($error): ?>
        <p class="error-message"> <?= htmlspecialchars($error) ?> </p>
    <?php endif; ?>
    <?php if ($success): ?>
        <p class="success-message"> <?= htmlspecialchars($success) ?> </p>
    <?php endif; ?>

    <form method="post" action="<?= BASE_URL ?>admin/add_doctor.php">
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required><br>

        <label for="email">Email:</label>
        <input type="email" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required><br>

        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required><br>

        <label for="phone">Phone:</label>
        <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" required><br>

        <label for="specialty_id">Specialty:</label>
        <select id="specialty_id" name="specialty_id" required>
            <option value="">Select Specialty</option>
            <?php foreach ($specialties as $specialty): ?>
                <option value="<?= $specialty['id'] ?>" <?= (isset($_POST['specialty_id']) && $_POST['specialty_id'] == $specialty['id']) ? 'selected' : '' ?>><?= htmlspecialchars($specialty['name']) ?></option>
            <?php endforeach; ?>
        </select><br>

        <label for="hospital_id">Hospital:</label>
        <select id="hospital_id" name="hospital_id" required>
            <option value="">Select Hospital</option>
            <?php foreach ($hospitals as $hospital): ?>
                <option value="<?= $hospital['id'] ?>" <?= (isset($_POST['hospital_id']) && $_POST['hospital_id'] == $hospital['id']) ? 'selected' : '' ?>><?= htmlspecialchars($hospital['name']) ?></option>
            <?php endforeach; ?>
        </select><br><br>

        <button type="submit">Add Doctor</button>
    </form>

    <p><a href="<?= BASE_URL ?>admin/manage_doctors.php">Back to Manage Doctors</a></p>
<?php require_once '../includes/footer.php'; ?>