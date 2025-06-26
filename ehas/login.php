<?php
// DEBUG_LOGIN_FILE_VERSION_20240626
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'includes/db.php';
require_once 'includes/session.php';
require_once 'includes/config.php';
require_once 'includes/header.php'; // Include the header

$username = $password = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Username and password are required.';
    } else {
        $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows === 1) {
            $stmt->bind_result($id, $fetched_username, $hashed_password, $role);
            $stmt->fetch();
            if (password_verify($password, $hashed_password)) {
                $_SESSION['user_id'] = $id;
                $_SESSION['name'] = $fetched_username;
                $_SESSION['role'] = $role;
                // Redirect based on role
                if ($role === 'admin') {
                    header('Location: ' . BASE_URL . 'admin/index.php');
                } elseif ($role === 'doctor') {
                    header('Location: ' . BASE_URL . 'doctor/index.php');
                } else {
                    header('Location: ' . BASE_URL . 'patient/index.php');
                }
                exit();
            } else {
                $error = 'Invalid username or password.';
            }
        } else {
            $error = 'Invalid username or password.';
        }
        $stmt->close();
    }
}
?>
    <h2>Login</h2>
    <?php if (isset($_GET['registered'])): ?>
        <p class="success-message">Registration successful! Please log in.</p>
    <?php endif; ?>
    <?php if ($error): ?>
        <p class="error-message"> <?= htmlspecialchars($error) ?> </p>
    <?php endif; ?>
    <form method="post" action="<?= BASE_URL ?>login.php">
        <label>Username:</label><br>
        <input type="text" name="username" value="<?= htmlspecialchars($username) ?>" required><br>
        <label>Password:</label><br>
        <input type="password" name="password" required><br><br>
        <button type="submit">Login</button>
    </form>
    <p>New patient? <a href="<?= BASE_URL ?>patient/register.php">Register here</a>.</p>
<?php require_once 'includes/footer.php'; ?> 