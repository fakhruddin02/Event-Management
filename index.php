<?php
require_once __DIR__ . '/config.php';

// Check DB connection
try {
    $pdo = dbConnect();
} catch (Exception $e) {
    die('Database unavailable. Please check your configuration.');
}

if (isLoggedIn()) {
    redirect('dashboard.php');
} else {
    redirect('login.php');
}
