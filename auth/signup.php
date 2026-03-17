<?php
/**
 * Signup Controller & UI
 */
require_once '../config/db.php';
require_once '../config/functions.php';

if (is_logged_in()) {
    redirect('../index.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (!empty($username) && !empty($password) && !empty($confirm_password)) {
        if ($password !== $confirm_password) {
            $error = "Passwords do not match.";
        } else {
            // Check if username already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $error = "Username already exists.";
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);

                // Insert user
                $stmt = $pdo->prepare("INSERT INTO users (username, password_hash) VALUES (?, ?)");
                if ($stmt->execute([$username, $hashed_password])) {
                    $success = "Registration successful! <a href='login.php' class='alert-link'>Login here</a>";
                } else {
                    $error = "Something went wrong. Please try again.";
                }
            }
        }
    } else {
        $error = "Please fill in all fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Signup | Secure Mailer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            background: linear-gradient(135deg, #0f0c29, #302b63, #24243e);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .auth-card {
            width: 100%;
            max-width: 400px;
        }
    </style>
</head>
<body>
    <div class="auth-card">
        <h2 class="text-center mb-4">Signup</h2>
        <?php if ($error): ?>
            <div class="alert alert-danger py-2"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success py-2"><?php echo $success; ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <div class="mb-4">
                <label class="form-label">Confirm Password</label>
                <input type="password" name="confirm_password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary w-100 mb-3">Sign Up</button>
            <p class="text-center small mb-0">Already have an account? <a href="login.php" class="text-info">Login</a></p>
        </form>
    </div>
</body>
</html>
