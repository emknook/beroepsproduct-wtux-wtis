<?php

require_once '../includes/setup.php';

$error = null;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

$success = signIn($db, $username, $password);

if (!$success) {
    header('Location: ../index.php?login=failed');
    exit;
}