<?php
require_once '../includes/db.php';
$region_id = isset($_GET['region_id']) ? intval($_GET['region_id']) : 0;
if ($region_id) {
    $stmt = $conn->prepare("SELECT id, name FROM districts WHERE region_id = ? ORDER BY name");
    $stmt->bind_param('i', $region_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        echo '<option value="' . $row['id'] . '">' . htmlspecialchars($row['name']) . '</option>';
    }
    $stmt->close();
}
?> 