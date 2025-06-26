<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../includes/db.php';
require_once '../includes/session.php';
require_once '../includes/config.php';
require_once '../includes/header.php';

// Check if user is logged in and is a patient
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header('Location: ' . BASE_URL . 'login.php');
    exit();
}

$doctors = [];
$specialties = [];
$hospitals = [];
$search_query = trim($_GET['search'] ?? '');
$selected_specialty = $_GET['specialty_id'] ?? '';
$selected_hospital = $_GET['hospital_id'] ?? '';

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

// Build query for doctors based on filters
$query = "SELECT u.id, u.username, u.email, u.phone, s.name as specialty_name, h.name as hospital_name 
          FROM users u 
          JOIN specialties s ON u.specialty_id = s.id 
          JOIN hospitals h ON u.hospital_id = h.id 
          WHERE u.role = 'doctor'";

$params = '';
$param_values = [];

if (!empty($search_query)) {
    $query .= " AND (u.username LIKE ? OR u.email LIKE ? OR s.name LIKE ? OR h.name LIKE ?)";
    $search_param = '%' . $search_query . '%';
    $params .= 'ssss';
    $param_values[] = &$search_param;
    $param_values[] = &$search_param;
    $param_values[] = &$search_param;
    $param_values[] = &$search_param;
}

if (!empty($selected_specialty)) {
    $query .= " AND u.specialty_id = ?";
    $params .= 'i';
    $param_values[] = &$selected_specialty;
}

if (!empty($selected_hospital)) {
    $query .= " AND u.hospital_id = ?";
    $params .= 'i';
    $param_values[] = &$selected_hospital;
}

$query .= " ORDER BY u.username";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($params, ...$param_values);
}
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $doctors[] = $row;
}
$stmt->close();

?>
    <h2>Search for Doctors</h2>

    <form method="get" action="<?= BASE_URL ?>patient/search_doctors.php" style="margin-bottom: 20px;">
        <label for="search">Search Doctor:</label>
        <input type="text" id="search" name="search" value="<?= htmlspecialchars($search_query) ?>" placeholder="Name, email, specialty, hospital"><br>

        <label for="specialty_id">Specialty:</label>
        <select id="specialty_id" name="specialty_id">
            <option value="">All Specialties</option>
            <?php foreach ($specialties as $specialty): ?>
                <option value="<?= $specialty['id'] ?>" <?= ($selected_specialty == $specialty['id']) ? 'selected' : '' ?>><?= htmlspecialchars($specialty['name']) ?></option>
            <?php endforeach; ?>
        </select><br>

        <label for="hospital_id">Hospital:</label>
        <select id="hospital_id" name="hospital_id">
            <option value="">All Hospitals</option>
            <?php foreach ($hospitals as $hospital): ?>
                <option value="<?= $hospital['id'] ?>" <?= ($selected_hospital == $hospital['id']) ? 'selected' : '' ?>><?= htmlspecialchars($hospital['name']) ?></option>
            <?php endforeach; ?>
        </select><br><br>

        <button type="submit">Search</button>
    </form>

    <h3>Available Doctors</h3>
    <?php if (empty($doctors)): ?>
        <p style="text-align: center;">No doctors found matching your criteria.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Specialty</th>
                    <th>Hospital</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($doctors as $doctor): ?>
                    <tr>
                        <td><?= htmlspecialchars($doctor['username']) ?></td>
                        <td><?= htmlspecialchars($doctor['email']) ?></td>
                        <td><?= htmlspecialchars($doctor['phone']) ?></td>
                        <td><?= htmlspecialchars($doctor['specialty_name']) ?></td>
                        <td><?= htmlspecialchars($doctor['hospital_name']) ?></td>
                        <td>
                            <a href="<?= BASE_URL ?>patient/request_appointment.php?doctor_id=<?= $doctor['id'] ?>">Request Appointment</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <p><a href="<?= BASE_URL ?>patient/index.php">Back to Dashboard</a></p>
<?php require_once '../includes/footer.php'; ?> 