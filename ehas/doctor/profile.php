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

$doctor_id = $_SESSION['user_id'];
$error = '';
$success = '';
$doctor_data = null;

// Fetch doctor's current profile data
$stmt = $conn->prepare("SELECT u.username, u.email, u.phone, u.address, u.gender, u.dob, u.specialty_id, u.hospital_id 
                        FROM users u 
                        WHERE u.id = ? AND u.role = 'doctor'");
$stmt->bind_param('i', $doctor_id);
$stmt->execute();
$doctor_data = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$doctor_data) {
    $error = 'Doctor profile not found.';
}

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

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $dob = $_POST['dob'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $specialty_id = $_POST['specialty_id'] ?? '';
    $hospital_id = $_POST['hospital_id'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($username) || empty($email) || empty($phone) || empty($address) || empty($dob) || empty($gender) || empty($specialty_id) || empty($hospital_id)) {
        $error = 'All fields except password are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } elseif (!empty($password) && $password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        // Check if username or email already exists for another user
        $stmt = $conn->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
        $stmt->bind_param('ssi', $username, $email, $doctor_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $error = 'Username or Email already exists for another account.';
        } else {
            $update_sql = "UPDATE users SET username = ?, email = ?, phone = ?, address = ?, dob = ?, gender = ?, specialty_id = ?, hospital_id = ?";
            $params = 'ssssssii';
            $param_values = [&$username, &$email, &$phone, &$address, &$dob, &$gender, &$specialty_id, &$hospital_id];

            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $update_sql .= ", password = ?";
                $params .= 's';
                $param_values[] = &$hashed_password;
            }
            $update_sql .= " WHERE id = ? AND role = 'doctor'";
            $params .= 'i';
            $param_values[] = &$doctor_id;

            $stmt = $conn->prepare($update_sql);
            if ($stmt) {
                $stmt->bind_param($params, ...$param_values);
                if ($stmt->execute()) {
                    $success = 'Profile updated successfully.';
                    // Re-fetch updated data
                    $stmt_re_fetch = $conn->prepare("SELECT u.username, u.email, u.phone, u.address, u.gender, u.dob, u.specialty_id, u.hospital_id 
                                                    FROM users u 
                                                    WHERE u.id = ? AND u.role = 'doctor'");
                    $stmt_re_fetch->bind_param('i', $doctor_id);
                    $stmt_re_fetch->execute();
                    $doctor_data = $stmt_re_fetch->get_result()->fetch_assoc();
                    $stmt_re_fetch->close();

                    // Update session name if username changed
                    $_SESSION['name'] = $doctor_data['username'];

                } else {
                    $error = 'Error updating profile: ' . $stmt->error;
                }
                $stmt->close();
            } else {
                $error = 'Error preparing update statement: ' . $conn->error;
            }
        }
    }
}

// Display form if doctor data is available
if ($doctor_data) {
?>
    <h2>My Profile</h2>

    <?php if ($error): ?>
        <p class="error-message"> <?= htmlspecialchars($error) ?> </p>
    <?php endif; ?>
    <?php if ($success): ?>
        <p class="success-message"> <?= htmlspecialchars($success) ?> </p>
    <?php endif; ?>

    <form method="post" action="<?= BASE_URL ?>doctor/profile.php">
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" value="<?= htmlspecialchars($doctor_data['username']) ?>" required><br>

        <label for="email">Email:</label>
        <input type="email" id="email" name="email" value="<?= htmlspecialchars($doctor_data['email']) ?>" required><br>

        <label for="phone">Phone:</label>
        <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($doctor_data['phone']) ?>" required><br>

        <label for="address">Address:</label>
        <textarea id="address" name="address" required><?= htmlspecialchars($doctor_data['address']) ?></textarea><br>

        <label for="dob">Date of Birth:</label>
        <input type="date" id="dob" name="dob" value="<?= htmlspecialchars($doctor_data['dob']) ?>" required><br>

        <label for="gender">Gender:</label>
        <select id="gender" name="gender" required>
            <option value="">Select Gender</option>
            <option value="Male" <?= ($doctor_data['gender'] === 'Male') ? 'selected' : '' ?>>Male</option>
            <option value="Female" <?= ($doctor_data['gender'] === 'Female') ? 'selected' : '' ?>>Female</option>
            <option value="Other" <?= ($doctor_data['gender'] === 'Other') ? 'selected' : '' ?>>Other</option>
        </select><br>

        <label for="specialty_id">Specialty:</label>
        <select id="specialty_id" name="specialty_id" required>
            <option value="">Select Specialty</option>
            <?php foreach ($specialties as $specialty): ?>
                <option value="<?= $specialty['id'] ?>" <?= ($doctor_data['specialty_id'] == $specialty['id']) ? 'selected' : '' ?>><?= htmlspecialchars($specialty['name']) ?></option>
            <?php endforeach; ?>
        </select><br>

        <label for="hospital_id">Hospital:</label>
        <select id="hospital_id" name="hospital_id" required>
            <option value="">Select Hospital</option>
            <?php foreach ($hospitals as $hospital): ?>
                <option value="<?= $hospital['id'] ?>" <?= ($doctor_data['hospital_id'] == $hospital['id']) ? 'selected' : '' ?>><?= htmlspecialchars($hospital['name']) ?></option>
            <?php endforeach; ?>
        </select><br><br>

        <label for="password">New Password (leave blank to keep current):</label>
        <input type="password" id="password" name="password"><br>

        <label for="confirm_password">Confirm New Password:</label>
        <input type="password" id="confirm_password" name="confirm_password"><br><br>

        <button type="submit">Update Profile</button>
    </form>
    <p><a href="<?= BASE_URL ?>doctor/index.php">Back to Dashboard</a></p>
<?php 
} else {
    echo '<p class="error-message">' . htmlspecialchars($error) . '</p>';
    echo '<p style="text-align: center;"><a href="' . BASE_URL . 'doctor/index.php">Back to Dashboard</a></p>';
}
require_once '../includes/footer.php';
?>