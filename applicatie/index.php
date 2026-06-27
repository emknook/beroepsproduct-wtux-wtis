<?php

require_once 'includes/setup.php';

$db = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_to_cart') {
        $productName = $_POST['product_name'] ?? '';

        if ($productName !== '') {
            addProductToCart($db, $productName);
        }

        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    } else if ($action === 'remove_from_cart') {
        $productName = $_POST['product_name'] ?? '';

        if ($productName !== '') {
            removeFromCart($productName);
        }

        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }
}

$selectedTypeId = null;

if (isset($_GET['type']) && $_GET['type'] !== '') {
    $selectedTypeId = $_GET['type'];
}

$types = getProductTypes(db());
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
            <div class="types">
                <div class="carousel-left" aria-hidden="true">
                    <svg width="42px" height="42px" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd"
                            d="M10.0606 11.9999L15.5303 17.4696L14.4696 18.5303L7.93928 11.9999L14.4696 5.46961L15.5303 6.53027L10.0606 11.9999Z" />
                    </svg>
                </div>
                <div class="types__scroll">
                    <?= renderTypes($types, $selectedTypeId) ?>
                </div>
                <div class="carousel-right" aria-hidden="true">
                    <svg width="42px" height="42px" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd"
                            d="M13.9394 12.0001L8.46973 6.53039L9.53039 5.46973L16.0607 12.0001L9.53039 18.5304L8.46973 17.4697L13.9394 12.0001Z" />
                    </svg>
                </div>
            </div>
            <div class="products">
                <?= renderProducts($products) ?>
            </div>
        </div>
    </main>

</body>

</html>