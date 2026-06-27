<?php

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function renderProduct(array $product): string
{
    $name = e($product['name']);
    $ingredients = htmlspecialchars(implode(', ', $product['ingredients']), ENT_QUOTES, 'UTF-8');
    $price = number_format((float) $product['price'], 2, ',', '.');

    return '
        <div class="product">
            <div class="product-name">' . $name . '</div>
            <div class="product-ingredients">' . $ingredients . '</div>
            <div class="product-price">€' . $price . '</div>

            <form method="post" action="index.php" class="product-cart-form product-to-cart">
                <input type="hidden" name="action" value="add_to_cart">
                <input type="hidden" name="product_name" value="' . $name . '">

                <button class="button" type="submit">
                    Add to cart
                </button>
            </form>
        </div>
    ';
}

function renderProducts(array $products): string
{
    if ($products === []) {
        return '<p>No products found.</p>';
    }

    $html = '';

    foreach ($products as $product) {
        $html .= renderProduct($product);
    }

    return $html;
}

function renderType(array $type, ?bool $selected = false): string
{
    $name = e($type['name']);
    return '<a class="type ' . ($selected ? 'selected' : '') . '" href="index.php?type=' . $name . '">' . $name . '</a>';
}

function renderTypes(array $types, ?string $selectedType = null): string
{
    $html = '<a class="type" href="index.php?type=">Alles</a>';

    if ($types === []) {
        return $html;
    }

    foreach ($types as $type) {
        $html .= renderType($type, $selectedType === $type['name']);
    }

    return $html;
}

function renderCartButton(int $cartAmount): string
{
    $cart = $_SESSION['cart'] ?? [];
    $total = 0;

    $html = '
        <div class="expandable-button">
            <a class="expandable-button__label" href="order.php">
                Current order (' . $cartAmount . ')
            </a>

            <div class="expandable-panel">
                <div class="cart-items">
    ';

    if ($cart === []) {
        $html .= '
            <p class="cart-empty">
                Your cart is empty.
            </p>
        ';
    }

    foreach ($cart as $item) {
        $name = e($item['name']);
        $price = (float) $item['price'];
        $amount = (int) $item['amount'];

        $rowTotal = $price * $amount;
        $total += $rowTotal;

        $formattedPrice = number_format($rowTotal, 2, ',', '.');

        $html .= '
            <div class="cart-row">
                <span class="cart-product-name">' . $name . '</span>
                <span class="cart-product-price">€' . $formattedPrice . '</span>

                <div class="cart-amount">
                    <form method="post" action="index.php" class="cart-amount-form">
                        <input type="hidden" name="action" value="remove_from_cart">
                        <input type="hidden" name="product_name" value="' . $name . '">
                        <button type="submit">-</button>
                    </form>

                    <span>' . $amount . '</span>

                    <form method="post" action="index.php" class="cart-amount-form">
                        <input type="hidden" name="action" value="add_to_cart">
                        <input type="hidden" name="product_name" value="' . $name . '">
                        <button type="submit">+</button>
                    </form>
                </div>
            </div>
        ';
    }

    $formattedTotal = number_format($total, 2, ',', '.');

    $html .= '
                </div>

                <div class="cart-total">
                    <span>Total</span>
                    <span>€' . $formattedTotal . '</span>
                </div>

                <div class="expandable-panel-actions">
                    <form method="post" action="order.php" class="cart-empty-form">
                        <input type="hidden" name="action" value="empty_cart">
                        <button type="submit">Empty cart</button>
                    </form>

                    <a class="button" href="order.php">Place order</a>
                </div>
            </div>
        </div>
    ';

    return $html;
}

function renderAccountButton(bool $isSignedIn): string
{
    if ($isSignedIn) {
        $username = htmlspecialchars($_SESSION['user']['username'] ?? 'Account', ENT_QUOTES, 'UTF-8');
        $firstName = htmlspecialchars($_SESSION['user']['first_name'] ?? '', ENT_QUOTES, 'UTF-8');

        $label = $firstName !== '' ? $firstName : $username;

        return '
            <div class="expandable-button">
                <a class="expandable-button__label" href="#">
                    ' . $label . '
                </a>

                <div class="expandable-panel">
                    <div class="account-panel">
                        <p class="account-panel__name">
                            Signed in as ' . $username . '
                        </p>

                        <div class="expandable-panel-actions">
                            <a href="./profile.php">Profile & order history</a>

                            <form method="post" action="calls/logout.php" class="signout-form">
                                <button class="button" type="submit">Sign out</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        ';
    }

    return '
        <div class="expandable-button">
            <a class="expandable-button__label" href="#">
                Account
            </a>

            <div class="expandable-panel">
                <form method="post" action="calls/login.php" class="signin-form">
                    <div class="signin">
                        <label for="username">Username</label>
                        <input id="username" name="username" class="text-input" type="email" required>

                        <label for="password">Password</label>
                        <input id="password" name="password" class="text-input" type="password" required>
                    </div>

                    <div class="expandable-panel-actions">
                        <a href="./register.php">Register</a>
                        <button class="button" type="submit">Sign in</button>
                    </div>
                </form>
            </div>
        </div>
    ';
}