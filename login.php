<?php
require_once __DIR__ . '/config.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        $errors[] = 'Invalid CSRF token.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
        if ($password === '') $errors[] = 'Password is required.';

        if (empty($errors)) {
            if (loginUser($email, $password)) {
                redirect('dashboard.php');
            } else {
                $errors[] = 'Invalid credentials.';
            }
        }
    }
} 
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Login</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <h1>Login</h1>

    <?php if ($errors): ?>
        <div class="errors">
            <?php foreach ($errors as $err): ?>
                <p><?php echo e($err); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="post" action="">
        <?php echo csrfInput(); ?>
        <label>Email:<br>
            <input type="email" name="email" value="<?php echo e($_POST['email'] ?? '') ?>">
        </label><br>

        <label>Password:<br>
            <input type="password" name="password">
        </label><br>

        <button type="submit">Login</button>
    </form>

    <p>No account? <a href="register.php">Register</a></p>
</div>
</body>
</html>