<?php

require_once 'includes/setup.php';

$db = db();

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $productName = $_POST['product_name'] ?? '';

    if ($action === 'add_to_cart') {
        if ($productName !== '') {
            addProductToCart($db, $productName);
        }

        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }

    if ($action === 'remove_from_cart') {
        if ($productName !== '') {
            removeFromCart($productName);
        }

        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }

    if ($action === 'empty_cart') {
        emptyCart();

        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }

    if ($action === 'place_order') {
        if (getCart() === []) {
            $error = 'Your cart is empty.';
        } else {
            $clientName = trim($_POST['client_name'] ?? '');

            $street = trim($_POST['street'] ?? '');
            $houseNumber = trim($_POST['housenumber'] ?? '');
            $zip = trim($_POST['zip'] ?? '');
            $city = trim($_POST['city'] ?? '');

            if ($clientName === '' || $street === '' || $houseNumber === '' || $zip === '' || $city === '') {
                $error = 'Please fill in your name and full address.';
            } else {
                $address = $street . ' ' . $houseNumber . ', ' . $zip . ', ' . $city;

                $clientUsername = null;

                if (isSignedIn()) {
                    $clientUsername = $_SESSION['user']['username'];
                }

                $result = createOrder($db, $clientUsername, $clientName, $address);

                if (is_numeric($result)) {
                    header('Location: orderStatus.php?order=' . $result);
                    exit;
                }

                if ($result instanceof Throwable) {
                    $error = 'Something went wrong: ' . $result->getMessage();
                } else {
                    $error = 'Something went wrong: ' . $result;
                }
            }
        }
    }
}

$cart = getCart();
$cartTotal = getCartTotal();

$defaultClientName = '';
$defaultAddress = '';
$defaultStreet = '';
$defaultHouse = '';
$defaultZip = '';
$defaultCity = '';

if (isSignedIn()) {
    $firstName = $_SESSION['user']['first_name'] ?? '';
    $lastName = $_SESSION['user']['last_name'] ?? '';
    $defaultAddress = $_SESSION['user']['address'] ?? '';

    if ($defaultAddress !== '') {
        $splitAddress = splitAddress($defaultAddress);
        $defaultStreet = $splitAddress['street'];
        $defaultHouse = $splitAddress['house_number'];
        $defaultZip = $splitAddress['zip_code'];
        $defaultCity = $splitAddress['city'];
    }

    $defaultClientName = trim($firstName . ' ' . $lastName);
}

function formatPrice(float $price): string
{
    return '€' . number_format($price, 2, ',', '.');
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Place order</title>
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
                <h1 class="form-title">Place order</h1>

                <?php if ($error !== null): ?>
                    <p class="form-error">
                        <?= escapeHtml($error) ?>
                    </p>
                <?php endif; ?>

                <div class="order-products">
                    <?php if ($cart === []): ?>
                        <p>Your cart is empty.</p>
                    <?php endif; ?>

                    <?php foreach ($cart as $item): ?>
                        <?php
                        $name = (string) $item['name'];
                        $price = (float) $item['price'];
                        $amount = (int) $item['amount'];
                        $ingredients = (array) $item['ingredients'];
                        $rowTotal = $price * $amount;
                        ?>

                        <div class="order-product">
                            <div class="product-name">
                                <?= escapeHtml($name) ?>
                            </div>

                            <div class="product-ingredients">
                                <?= escapeHtml(implode(', ', $ingredients)) ?>
                            </div>

                            <div class="product-price">
                                <?= formatPrice($rowTotal) ?>
                            </div>

                            <div class="product-price-per">
                                <?= formatPrice($price) ?> each
                            </div>

                            <div class="product-amount">
                                <form method="post" action="order.php" class="cart-amount-form">
                                    <input type="hidden" name="action" value="remove_from_cart">
                                    <input type="hidden" name="product_name" value="<?= escapeHtml($name) ?>">
                                    <button type="submit">-</button>
                                </form>

                                <span><?= $amount ?></span>

                                <form method="post" action="order.php" class="cart-amount-form">
                                    <input type="hidden" name="action" value="add_to_cart">
                                    <input type="hidden" name="product_name" value="<?= escapeHtml($name) ?>">
                                    <button type="submit">+</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <div class="order-total">
                        <div class="order-total__label">
                            Total
                        </div>

                        <div class="order-total__price">
                            <?= formatPrice($cartTotal) ?>
                        </div>
                    </div>
                </div>

                <?php if ($cart !== []): ?>
                    <form method="post" action="order.php" class="place-order-form">
                        <input type="hidden" name="action" value="place_order">

                        <div class="address-input-wrapper">
                            <div class="address-icon">
                                <svg viewBox="-4 0 32 32" version="1.1" xmlns="http://www.w3.org/2000/svg">
                                    <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                                        <g transform="translate(-106.000000, -413.000000)">
                                            <path
                                                d="M118,422 C116.343,422 115,423.343 115,425 C115,426.657 116.343,428 118,428 C119.657,428 121,426.657 121,425 C121,423.343 119.657,422 118,422 L118,422 Z M118,430 C115.239,430 113,427.762 113,425 C113,422.238 115.239,420 118,420 C120.761,420 123,422.238 123,425 C123,427.762 120.761,430 118,430 L118,430 Z M118,413 C111.373,413 106,418.373 106,425 C106,430.018 116.005,445.011 118,445 C119.964,445.011 130,429.95 130,425 C130,418.373 124.627,413 118,413 L118,413 Z"
                                                id="location">
                                            </path>
                                        </g>
                                    </g>
                                </svg>
                            </div>

                            <div class="address-input address-input-name">
                                <label for="client-name">Name</label>
                                <input id="client-name" class="text-input" name="client_name"
                                    value="<?= escapeHtml($defaultClientName) ?>" required>
                            </div>

                            <div class="address-input address-input-zip">
                                <label for="zip">Zipcode</label>
                                <input id="zip" class="text-input" name="zip" value="<?= escapeHtml($defaultZip) ?>"
                                    required>
                            </div>

                            <div class="address-input address-input-housenr">
                                <label for="housenumber">House number</label>
                                <input id="housenumber" class="text-input" value="<?= escapeHtml($defaultHouse) ?>"
                                    name="housenumber" required>
                            </div>

                            <div class="address-input address-input-street">
                                <label for="street">Street</label>
                                <input id="street" class="text-input" value="<?= escapeHtml($defaultStreet) ?>"
                                    name="street" required>
                            </div>

                            <div class="address-input address-input-city">
                                <label for="city">City</label>
                                <input id="city" class="text-input" value="<?= escapeHtml($defaultCity) ?>" name="city"
                                    required>
                            </div>
                        </div>
                        <button class="button submit-button" type="submit">
                            Place order
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </main>

</body>

</html>