<?php

require_once __DIR__ . '/includes/setup.php';

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $success = registerUser(
        $db,
        $_POST['username'] ?? '',
        $_POST['first_name'] ?? '',
        $_POST['last_name'] ?? '',
        $_POST['password'] ?? '',
        $_POST['password_repeated'] ?? ''
    );

    if ($success) {
        header('Location: index.php');
        exit;
    }

    $error = 'Registration failed.';
}