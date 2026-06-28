<?php

require_once 'includes/setup.php';

$db = db();

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'register_user') {
        $success = registerPersonnelUser(
            $db,
            $_POST['username'] ?? '',
            $_POST['first_name'] ?? '',
            $_POST['last_name'] ?? '',
            $_POST['password'] ?? '',
            $_POST['password_repeated'] ?? ''
        );

        if ($success) {
            header('Location: index.php?registered=success');
            exit;
        }

        $error = 'Registration failed. Check if all fields are filled, passwords match, and the username is not already used.';
    }
}

$selectedTypeId = null;

if (isset($_GET['type']) && $_GET['type'] !== '') {
    $selectedTypeId = $_GET['type'];
}

$types = getProductTypes($db);
$products = getProducts($db, $selectedTypeId);
$cartAmount = getCartAmount();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu</title>
    <link rel="stylesheet" href="../css/main.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
</head>

<body>
    <?php require 'includes/header.php'; ?>
    <main>
        <div class="content">
            <div class="border-form">
                <form method="post" action="register.php">
                    <input type="hidden" name="action" value="register_user">

                    <h1 class="form-title">Register profile</h1>

                    <?php if ($error !== null): ?>
                        <p class="form-error">
                            <?= escapeHtml($error) ?>
                        </p>
                    <?php endif; ?>

                    <div class="form-input-fields">
                        <label for="register-username">Username</label>
                        <input id="register-username" name="username" class="text-input" required>

                        <label for="first-name">First name</label>
                        <input id="first-name" name="first_name" class="text-input" required>

                        <label for="last-name">Last name</label>
                        <input id="last-name" name="last_name" class="text-input" required>

                        <label for="register-password">Password</label>
                        <input id="register-password" name="password" class="text-input" type="password" required>

                        <label for="password-repeated">Password again</label>
                        <input id="password-repeated" name="password_repeated" class="text-input" type="password"
                            required>
                    </div>

                    <button class="button submit-button" type="submit">Register</button>
                </form>
            </div>
        </div>
    </main>

</body>

</html>