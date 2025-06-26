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

$patient_id = $_SESSION['user_id'];
$error = '';
$success = '';
$doctors = [];
$hospitals = [];
$specialties = [];

// Fetch data for dropdowns
$stmt = $conn->prepare("SELECT id, name FROM specialties ORDER BY name");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $specialties[] = $row;
}
$stmt->close();

$stmt = $conn->prepare("SELECT id, name FROM hospitals ORDER BY name");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $hospitals[] = $row;
}
$stmt->close();

// Handle AJAX for doctors based on specialty/hospital (if this script is called directly via AJAX)
if (isset($_GET['fetch_doctors'])) {
    $selected_specialty_id = $_GET['specialty_id'] ?? 0;
    $selected_hospital_id = $_GET['hospital_id'] ?? 0;

    $temp_doctors = [];
    $query = "SELECT u.id, u.username, s.name as specialty_name, h.name as hospital_name 
              FROM users u 
              JOIN specialties s ON u.specialty_id = s.id 
              JOIN hospitals h ON u.hospital_id = h.id 
              WHERE u.role = 'doctor'";
    $params = '';
    $param_values = [];

    if ($selected_specialty_id) {
        $query .= " AND u.specialty_id = ?";
        $params .= 'i';
        $param_values[] = &$selected_specialty_id;
    }
    if ($selected_hospital_id) {
        $query .= " AND u.hospital_id = ?";
        $params .= 'i';
        $param_values[] = &$selected_hospital_id;
    }
    $query .= " ORDER BY u.username";

    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($params, ...$param_values);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $temp_doctors[] = $row;
    }
    $stmt->close();
    echo json_encode($temp_doctors);
    exit();
}

// Handle Appointment Request Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $doctor_id = $_POST['doctor_id'] ?? '';
    $appointment_date = $_POST['appointment_time'] ?? '';
    $reason = trim($_POST['reason'] ?? '');
    $hospital_id_selected = $_POST['hospital_id_selected'] ?? '';
    $specialty_id_selected = $_POST['specialty_id_selected'] ?? '';

    if (empty($doctor_id) || empty($appointment_date) || empty($reason) || empty($hospital_id_selected) || empty($specialty_id_selected)) {
        $error = 'All fields are required.';
    } else {
        $status = 'pending';
        $stmt = $conn->prepare("INSERT INTO appointments (patient_id, doctor_id, appointment_time, reason, hospital_id, specialty_id, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('iisiiis', $patient_id, $doctor_id, $appointment_date, $reason, $hospital_id_selected, $specialty_id_selected, $status);

        if ($stmt->execute()) {
            $success = 'Appointment request sent successfully! You will be notified once the doctor approves.';
        } else {
            $error = 'Error requesting appointment: ' . $stmt->error;
        }
        $stmt->close();
    }
}
?>
    <h2>Request an Appointment</h2>

    <?php if ($error): ?>
        <p class="error-message"> <?= htmlspecialchars($error) ?> </p>
    <?php endif; ?>
    <?php if ($success): ?>
        <p class="success-message"> <?= htmlspecialchars($success) ?> </p>
    <?php endif; ?>

    <form method="post" action="<?= BASE_URL ?>patient/request_appointment.php">
        <label for="specialty_filter">Filter by Specialty:</label>
        <select id="specialty_filter" name="specialty_filter">
            <option value="">All Specialties</option>
            <?php foreach ($specialties as $specialty): ?>
                <option value="<?= $specialty['id'] ?>"><?= htmlspecialchars($specialty['name']) ?></option>
            <?php endforeach; ?>
        </select><br>

        <label for="hospital_filter">Filter by Hospital:</label>
        <select id="hospital_filter" name="hospital_filter">
            <option value="">All Hospitals</option>
            <?php foreach ($hospitals as $hospital): ?>
                <option value="<?= $hospital['id'] ?>"><?= htmlspecialchars($hospital['name']) ?></option>
            <?php endforeach; ?>
        </select><br>

        <label for="doctor_id">Select Doctor:</label>
        <select id="doctor_id" name="doctor_id" required>
            <option value="">Select Doctor</option>
            <!-- Doctors will be loaded dynamically here -->
        </select><br>

        <label for="appointment_time">Preferred Appointment Date & Time:</label>
        <input type="datetime-local" id="appointment_time" name="appointment_time" required><br>

        <label for="reason">Reason for Appointment:</label>
        <textarea id="reason" name="reason" rows="5" required></textarea><br>

        <!-- Hidden fields to pass selected specialty and hospital IDs from filters to form submission -->
        <input type="hidden" id="hospital_id_selected" name="hospital_id_selected">
        <input type="hidden" id="specialty_id_selected" name="specialty_id_selected">

        <button type="submit">Request Appointment</button>
    </form>

    <p><a href="<?= BASE_URL ?>patient/index.php">Back to Dashboard</a></p>

    <script>
        function fetchDoctors() {
            var specialtyId = document.getElementById('specialty_filter').value;
            var hospitalId = document.getElementById('hospital_filter').value;
            var doctorSelect = document.getElementById('doctor_id');
            var hospitalSelectedInput = document.getElementById('hospital_id_selected');
            var specialtySelectedInput = document.getElementById('specialty_id_selected');

            doctorSelect.innerHTML = '<option value="">Select Doctor</option>'; // Clear existing options

            // Update hidden fields for form submission
            hospitalSelectedInput.value = hospitalId;
            specialtySelectedInput.value = specialtyId;

            if (specialtyId || hospitalId) {
                var queryParams = '';
                if (specialtyId) queryParams += '&specialty_id=' + specialtyId;
                if (hospitalId) queryParams += '&hospital_id=' + hospitalId;

                fetch('<?= BASE_URL ?>patient/request_appointment.php?fetch_doctors=true' + queryParams)
                    .then(response => response.json())
                    .then(data => {
                        data.forEach(function(doctor) {
                            var option = document.createElement('option');
                            option.value = doctor.id;
                            option.textContent = doctor.username + ' (' + doctor.specialty_name + ' at ' + doctor.hospital_name + ')';
                            doctorSelect.appendChild(option);
                        });
                    })
                    .catch(error => console.error('Error fetching doctors:', error));
            }
        }

        document.getElementById('specialty_filter').addEventListener('change', fetchDoctors);
        document.getElementById('hospital_filter').addEventListener('change', fetchDoctors);

        // Initial fetch on page load
        document.addEventListener('DOMContentLoaded', fetchDoctors);
    </script>
<?php require_once '../includes/footer.php'; ?> 