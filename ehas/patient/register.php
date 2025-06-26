<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../includes/db.php';
require_once '../includes/config.php';
require_once '../includes/header.php'; // Include the header

$username = $email = $password = $confirm_password = $phone = $address = $dob = $gender = $region_id = $district_id = $error = '';
$regions = [];
$districts = [];

// Fetch regions for the dropdown
$stmt = $conn->prepare("SELECT id, name FROM regions ORDER BY name");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $regions[] = $row;
}
$stmt->close();

// Handle AJAX request for districts
if (isset($_GET['region_id']) && !empty($_GET['region_id'])) {
    $selected_region_id = $_GET['region_id'];
    $stmt = $conn->prepare("SELECT id, name FROM districts WHERE region_id = ? ORDER BY name");
    $stmt->bind_param('i', $selected_region_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $districts[] = $row;
    }
    $stmt->close();
    echo json_encode($districts);
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $dob = $_POST['dob'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $region_id = $_POST['region_id'] ?? '';
    $district_id = $_POST['district_id'] ?? '';

    if (empty($username) || empty($email) || empty($password) || empty($confirm_password) || empty($phone) || empty($address) || empty($dob) || empty($gender) || empty($region_id) || empty($district_id)) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
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
            $role = 'patient';

            $stmt = $conn->prepare("INSERT INTO users (username, email, password, role, phone, address, dob, gender, region_id, district_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('sssssssiii', $username, $email, $hashed_password, $role, $phone, $address, $dob, $gender, $region_id, $district_id);

            if ($stmt->execute()) {
                header('Location: ' . BASE_URL . 'login.php?registered=true');
                exit();
            } else {
                $error = 'Error registering user. Please try again.';
            }
        }
        $stmt->close();
    }
}
?>
    <h2>Patient Registration</h2>
    <?php if ($error): ?>
        <p class="error-message"> <?= htmlspecialchars($error) ?> </p>
    <?php endif; ?>
    <form method="post" action="<?= BASE_URL ?>patient/register.php">
        <label>Username:</label><br>
        <input type="text" name="username" value="<?= htmlspecialchars($username) ?>" required><br>

        <label>Email:</label><br>
        <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" required><br>

        <label>Password:</label><br>
        <input type="password" name="password" required><br>

        <label>Confirm Password:</label><br>
        <input type="password" name="confirm_password" required><br>

        <label>Phone:</label><br>
        <input type="text" name="phone" value="<?= htmlspecialchars($phone) ?>" required><br>

        <label>Address:</label><br>
        <textarea name="address" required><?= htmlspecialchars($address) ?></textarea><br>

        <label>Date of Birth:</label><br>
        <input type="date" name="dob" value="<?= htmlspecialchars($dob) ?>" required><br>

        <label>Gender:</label><br>
        <select name="gender" required>
            <option value="">Select Gender</option>
            <option value="Male" <?= ($gender === 'Male') ? 'selected' : '' ?>>Male</option>
            <option value="Female" <?= ($gender === 'Female') ? 'selected' : '' ?>>Female</option>
            <option value="Other" <?= ($gender === 'Other') ? 'selected' : '' ?>>Other</option>
        </select><br>

        <label>Region:</label><br>
        <select name="region_id" id="region_id" required>
            <option value="">Select Region</option>
            <?php foreach ($regions as $region): ?>
                <option value="<?= $region['id'] ?>" <?= ($region_id == $region['id']) ? 'selected' : '' ?>><?= htmlspecialchars($region['name']) ?></option>
            <?php endforeach; ?>
        </select><br>

        <label>District:</label><br>
        <select name="district_id" id="district_id" required>
            <option value="">Select District</option>
            <?php foreach ($districts as $district): ?>
                <option value="<?= $district['id'] ?>" <?= ($district_id == $district['id']) ? 'selected' : '' ?>><?= htmlspecialchars($district['name']) ?></option>
            <?php endforeach; ?>
        </select><br><br>

        <button type="submit">Register</button>
    </form>
    <p>Already have an account? <a href="<?= BASE_URL ?>login.php">Login here</a>.</p>

    <script>
        document.getElementById('region_id').addEventListener('change', function() {
            var regionId = this.value;
            var districtSelect = document.getElementById('district_id');
            districtSelect.innerHTML = '<option value="">Select District</option>'; // Clear existing options

            if (regionId) {
                fetch('<?= BASE_URL ?>patient/register.php?region_id=' + regionId)
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

        // Pre-fill districts if region was previously selected (e.g., after form submission with errors)
        document.addEventListener('DOMContentLoaded', function() {
            var selectedRegion = document.getElementById('region_id').value;
            if (selectedRegion) {
                var event = new Event('change');
                document.getElementById('region_id').dispatchEvent(event);
            }
        });
    </script>
<?php require_once '../includes/footer.php'; ?> 