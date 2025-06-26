<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../includes/db.php';
require_once '../includes/session.php';
require_once '../includes/config.php';
require_once '../includes/header.php'; // Include the header

$username = $password = $confirm_password = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($username) || empty($password) || empty($confirm_password)) {
        $error = 'All fields are required.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        // Check if username already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $error = 'Username already exists. Please choose a different username.';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $role = 'admin'; // Default role for admin registration

            $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
            $stmt->bind_param('sss', $username, $hashed_password, $role);

            if ($stmt->execute()) {
                header('Location: ' . BASE_URL . 'admin/index.php?registered=true');
                exit();
            } else {
                $error = 'Error registering admin. Please try again.';
            }
        }
        $stmt->close();
    }
}
?>
    <h2>Admin Registration</h2>
    <?php if ($error): ?>
        <p class="error-message"> <?= htmlspecialchars($error) ?> </p>
    <?php endif; ?>
    <form method="post" action="<?= BASE_URL ?>admin/adminreg.php">
        <label>Username:</label><br>
        <input type="text" name="username" value="<?= htmlspecialchars($username) ?>" required><br>
        <label>Password:</label><br>
        <input type="password" name="password" required><br>
        <label>Confirm Password:</label><br>
        <input type="password" name="confirm_password" required><br><br>
        <button type="submit">Register Admin</button>
    </form>
    <p><a href="<?= BASE_URL ?>admin/index.php">Back to Admin Dashboard</a></p>
<?php require_once '../includes/footer.php'; ?> 