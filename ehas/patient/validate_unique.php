<?php
require_once '../includes/db.php';
$field = $_POST['field'] ?? '';
$value = $_POST['value'] ?? '';
$allowed = ['username', 'phone'];
$res = ['exists' => false];
if (in_array($field, $allowed) && $value) {
    $stmt = $conn->prepare("SELECT id FROM users WHERE $field = ? LIMIT 1");
    $stmt->bind_param('s', $value);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) $res['exists'] = true;
    $stmt->close();
}
header('Content-Type: application/json');
echo json_encode($res); 