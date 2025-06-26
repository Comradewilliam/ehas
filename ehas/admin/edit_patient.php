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

$patient_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$error = '';
$success = '';
$patient_data = null;

if (!$patient_id) {
    $error = 'Invalid patient ID.';
} else {
    // Fetch patient's current profile data
    $stmt = $conn->prepare("SELECT u.id as user_id, u.username, u.email, u.phone, u.address, u.dob, u.gender, u.region_id, u.district_id 
                            FROM users u 
                            WHERE u.id = ? AND u.role = 'patient'");
    $stmt->bind_param('i', $patient_id);
    $stmt->execute();
    $patient_data = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$patient_data) {
        $error = 'Patient not found.';
    }
}

// Fetch regions for dropdown
$regions = [];
$stmt = $conn->prepare("SELECT id, name FROM regions ORDER BY name");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $regions[] = $row;
}
$stmt->close();

$districts = [];
// This will be populated via AJAX, or initially if patient_data has a region_id
if ($patient_data && $patient_data['region_id']) {
    $stmt = $conn->prepare("SELECT id, name FROM districts WHERE region_id = ? ORDER BY name");
    $stmt->bind_param('i', $patient_data['region_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $districts[] = $row;
    }
    $stmt->close();
}

// Handle AJAX request for districts (if this script is called directly via AJAX for dropdowns)
if (isset($_GET['fetch_districts']) && isset($_GET['region_id']) && !empty($_GET['region_id'])) {
    $selected_region_id = $_GET['region_id'];
    $temp_districts = [];
    $stmt = $conn->prepare("SELECT id, name FROM districts WHERE region_id = ? ORDER BY name");
    $stmt->bind_param('i', $selected_region_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $temp_districts[] = $row;
    }
    $stmt->close();
    echo json_encode($temp_districts);
    exit();
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_to_update = $_POST['id'] ?? ''; // Hidden ID field
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $dob = $_POST['dob'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $region_id = $_POST['region_id'] ?? '';
    $district_id = $_POST['district_id'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($id_to_update) || empty($username) || empty($email) || empty($phone) || empty($address) || empty($dob) || empty($gender) || empty($region_id) || empty($district_id)) {
        $error = 'All fields except password are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } elseif (!empty($password) && $password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        // Check if username or email already exists for another user
        $stmt = $conn->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
        $stmt->bind_param('ssi', $username, $email, $id_to_update);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $error = 'Username or Email already exists for another account.';
        } else {
            $update_sql = "UPDATE users SET username = ?, email = ?, phone = ?, address = ?, dob = ?, gender = ?, region_id = ?, district_id = ?";
            $params = 'ssssssii';
            $param_values = [&$username, &$email, &$phone, &$address, &$dob, &$gender, &$region_id, &$district_id];

            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $update_sql .= ", password = ?";
                $params .= 's';
                $param_values[] = &$hashed_password;
            }
            $update_sql .= " WHERE id = ? AND role = 'patient'";
            $params .= 'i';
            $param_values[] = &$id_to_update;

            $stmt = $conn->prepare($update_sql);
            if ($stmt) {
                $stmt->bind_param($params, ...$param_values);
                if ($stmt->execute()) {
                    $success = 'Patient profile updated successfully.';
                    // Re-fetch updated data
                    $stmt_re_fetch = $conn->prepare("SELECT u.id as user_id, u.username, u.email, u.phone, u.address, u.dob, u.gender, u.region_id, u.district_id 
                                                    FROM users u 
                                                    WHERE u.id = ? AND u.role = 'patient'");
                    $stmt_re_fetch->bind_param('i', $id_to_update);
                    $stmt_re_fetch->execute();
                    $patient_data = $stmt_re_fetch->get_result()->fetch_assoc();
                    $stmt_re_fetch->close();
                } else {
                    $error = 'Error updating patient profile: ' . $stmt->error;
                }
                $stmt->close();
            } else {
                $error = 'Error preparing update statement: ' . $conn->error;
            }
        }
    }
}

// Display the form only if patient data is available and no error from fetching
if (!empty($patient_data) && empty($error)) {
?>
    <h2>Edit Patient: <?= htmlspecialchars($patient_data['username']) ?></h2>

    <?php if ($error): ?>
        <p class="error-message"> <?= htmlspecialchars($error) ?> </p>
    <?php endif; ?>
    <?php if ($success): ?>
        <p class="success-message"> <?= htmlspecialchars($success) ?> </p>
    <?php endif; ?>

    <form method="post" action="<?= BASE_URL ?>admin/edit_patient.php?id=<?= htmlspecialchars($patient_id) ?>">
        <input type="hidden" name="id" value="<?= htmlspecialchars($patient_data['user_id']) ?>">

        <label for="username">Username:</label>
        <input type="text" id="username" name="username" value="<?= htmlspecialchars($patient_data['username']) ?>" required><br>

        <label for="email">Email:</label>
        <input type="email" id="email" name="email" value="<?= htmlspecialchars($patient_data['email']) ?>" required><br>

        <label for="phone">Phone:</label>
        <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($patient_data['phone']) ?>" required><br>

        <label for="address">Address:</label>
        <textarea id="address" name="address" required><?= htmlspecialchars($patient_data['address']) ?></textarea><br>

        <label for="dob">Date of Birth:</label>
        <input type="date" id="dob" name="dob" value="<?= htmlspecialchars($patient_data['dob']) ?>" required><br>

        <label for="gender">Gender:</label>
        <select id="gender" name="gender" required>
            <option value="">Select Gender</option>
            <option value="Male" <?= ($patient_data['gender'] === 'Male') ? 'selected' : '' ?>>Male</option>
            <option value="Female" <?= ($patient_data['gender'] === 'Female') ? 'selected' : '' ?>>Female</option>
            <option value="Other" <?= ($patient_data['gender'] === 'Other') ? 'selected' : '' ?>>Other</option>
        </select><br>

        <label for="region_id">Region:</label>
        <select name="region_id" id="region_id" required>
            <option value="">Select Region</option>
            <?php foreach ($regions as $region): ?>
                <option value="<?= $region['id'] ?>" <?= ($patient_data['region_id'] == $region['id']) ? 'selected' : '' ?>><?= htmlspecialchars($region['name']) ?></option>
            <?php endforeach; ?>
        </select><br>

        <label for="district_id">District:</label>
        <select name="district_id" id="district_id" required>
            <option value="">Select District</option>
            <?php foreach ($districts as $district): ?>
                <option value="<?= $district['id'] ?>" <?= ($patient_data['district_id'] == $district['id']) ? 'selected' : '' ?>><?= htmlspecialchars($district['name']) ?></option>
            <?php endforeach; ?>
        </select><br><br>

        <label for="password">New Password (leave blank to keep current):</label>
        <input type="password" id="password" name="password"><br>

        <label for="confirm_password">Confirm New Password:</label>
        <input type="password" id="confirm_password" name="confirm_password"><br><br>

        <button type="submit">Update Patient</button>
    </form>

    <p><a href="<?= BASE_URL ?>admin/manage_patients.php">Back to Manage Patients</a></p>

    <script>
        document.getElementById('region_id').addEventListener('change', function() {
            var regionId = this.value;
            var districtSelect = document.getElementById('district_id');
            districtSelect.innerHTML = '<option value="">Select District</option>'; // Clear existing options

            if (regionId) {
                fetch('<?= BASE_URL ?>admin/edit_patient.php?fetch_districts=true&region_id=' + regionId)
                    .then(response => response.json())
                    .then(data => {
                        data.forEach(function(district) {
                            var option = document.createElement('option');
                            option.value = district.id;
                            option.textContent = district.name;
                            districtSelect.appendChild(option);
                        });
                    })
                    .catch(error => console.error('Error fetching districts:', error));
            }
        });

        // Pre-fill districts if region was previously selected (e.g., after form submission or initial load)
        document.addEventListener('DOMContentLoaded', function() {
            var selectedRegion = document.getElementById('region_id').value;
            if (selectedRegion) {
                // Create a new change event and dispatch it to load districts
                var event = new Event('change');
                document.getElementById('region_id').dispatchEvent(event);
            }
        });
    </script>
<?php 
} else {
    echo '<p class="error-message">' . htmlspecialchars($error) . '</p>';
    echo '<p style="text-align: center;"><a href="' . BASE_URL . 'admin/manage_patients.php">Back to Manage Patients</a></p>';
}
require_once '../includes/footer.php';
?>