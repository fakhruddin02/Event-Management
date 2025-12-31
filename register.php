<?php
require_once __DIR__ . '/config.php';

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        $errors[] = 'Invalid CSRF token.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';
        $role = $_POST['role'] ?? 'participant';

        // Basic validation
        if ($name === '') $errors[] = 'Name is required.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
        if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
        if ($password !== $password_confirm) $errors[] = 'Passwords do not match.';
        if (!in_array($role, ['participant','organizer'])) $role = 'participant';

        if (empty($errors)) {
            if (emailExists($email)) {
                $errors[] = 'Email is already registered.';
            } else {
                $created = createUser($name, $email, $password, $role);
                if ($created) {
                    $success = true;
                } else {
                    $errors[] = 'Could not create user. Try again.';
                }
            }
        }
    }
} 
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Register</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <h1>Register</h1>

    <?php if ($success): ?>
        <p class="success">Registration successful. <a href="login.php">Log in</a>.</p>
    <?php else: ?>

        <?php if ($errors): ?>
            <div class="errors">
                <?php foreach ($errors as $err): ?>
                    <p><?php echo e($err); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="post" action="">
            <?php echo csrfInput(); ?>
            <label>Name:<br>
                <input type="text" name="name" value="<?php echo e($_POST['name'] ?? '') ?>">
            </label><br>

            <label>Email:<br>
                <input type="email" name="email" value="<?php echo e($_POST['email'] ?? '') ?>">
            </label><br>

            <label>Password:<br>
                <input type="password" name="password">
            </label><br>

            <label>Confirm Password:<br>
                <input type="password" name="password_confirm">
            </label><br>

            <label>Role:<br>
                <select name="role">
                    <option value="participant" <?php if (($_POST['role'] ?? '') === 'participant') echo 'selected'; ?>>Participant</option>
                    <option value="organizer" <?php if (($_POST['role'] ?? '') === 'organizer') echo 'selected'; ?>>Organizer</option>
                </select>
            </label><br>

            <button type="submit">Register</button>
        </form>

        <p>Already have an account? <a href="login.php">Log in</a></p>

    <?php endif; ?>
</div>
</body>
</html>