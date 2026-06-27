<?php

require_once 'includes/setup.php';

$db = db();

verifyUser([], 'index.php');

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_to_cart') {
        $productName = $_POST['product_name'] ?? '';

        if ($productName !== '') {
            addProductToCart($db, $productName);
        }

        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }

    if ($action === 'remove_from_cart') {
        $productName = $_POST['product_name'] ?? '';

        if ($productName !== '') {
            removeFromCart($productName);
        }

        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }
}

function formatPrice(float $price): string
{
    return '€' . number_format($price, 2, ',', '.');
}

$username = $_SESSION['user']['username'];

$user = getUserByUsername($db, $username);

if ($user === null) {
    signOut();
}

$orders = getOrdersForUser($db, $username);

$fullName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
$address = trim((string) ($user['address'] ?? ''));

if ($fullName === '') {
    $fullName = $username;
}

if ($address === '') {
    $address = 'No address saved yet';
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile</title>
    <link rel="stylesheet" href="../css/main.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
</head>

<body>

    <?php require 'includes/header.php'; ?>

    <main class="profile-page">
        <aside class="profile-sidebar">
            <p class="section-label">Account</p>
            <h1 class="section-title">Profile</h1>

            <div class="profile-details">
                <div class="profile-details__row">
                    <div>Name</div>
                    <div><?= e($fullName) ?></div>
                </div>

                <div class="profile-details__row">
                    <div>Email</div>
                    <div><?= e($username) ?></div>
                </div>

                <div class="profile-details__row">
                    <div>Address</div>
                    <div><?= e($address) ?></div>
                </div>
            </div>
        </aside>

        <section class="order-history">
            <div class="order-history__header">
                <div>
                    <p class="section-label">Previous orders</p>
                    <h2 class="section-title">Order history</h2>
                </div>

                <span class="order-history__count">
                    <?= count($orders) ?> <?= count($orders) === 1 ? 'order' : 'orders' ?>
                </span>
            </div>

            <div class="order-list">
                <?php if ($orders === []): ?>
                    <p>No previous orders found.</p>
                <?php endif; ?>

                <?php foreach ($orders as $order): ?>
                    <?php
                    $orderId = (int) $order['order_id'];
                    $datetime = e((string) $order['datetime']);
                    $total = formatPrice((float) $order['total']);
                    ?>

                    <article class="order-card">
                        <div class="order-card__main">
                            <div>
                                <h3 class="order-card__title">
                                    Order #<?= $orderId ?>
                                </h3>

                                <p class="order-card__date">
                                    <?= $datetime ?>
                                </p>
                            </div>
                        </div>

                        <ul class="order-card__items">
                            <?php foreach ($order['products'] as $product): ?>
                                <li>
                                    <?= (int) $product['quantity'] ?>x <?= e($product['name']) ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>

                        <div class="order-card__footer">
                            <strong class="order-card__price">
                                <?= $total ?>
                            </strong>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    </main>

</body>

</html>