<?php

declare(strict_types=1);

function createCartIfMissing(): void
{
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
}

function addProductToCart(PDO $db, string $productName): bool
{
    $productName = trim($productName);

    if ($productName === '') {
        return false;
    }

    $stmt = $db->prepare("
        SELECT
            p.name,
            p.price,
            p.type_id,
            pi.ingredient_name
        FROM product p
        LEFT JOIN product_ingredient pi
            ON pi.product_name = p.name
        WHERE p.name = :name
    ");

    $stmt->execute([
        ':name' => $productName,
    ]);

    $rows = $stmt->fetchAll();

    if (!$rows) {
        return false;
    }

    createCartIfMissing();

    $name = $rows[0]['name'];

    if (!isset($_SESSION['cart'][$name])) {
        foreach ($rows as $row) {
            $name = $row['name'];
            if (!isset($_SESSION['cart'][$name])) {
                $_SESSION['cart'][$name] = [
                    'name' => $row['name'],
                    'price' => (float) $row['price'],
                    'amount' => 1,
                    'ingredients' => [],
                ];

                if ($row['ingredient_name'] !== null) {
                    $_SESSION['cart'][$name]['ingredients'][] = $row['ingredient_name'];
                }
            } else {
                if ($row['ingredient_name'] !== null) {
                    $_SESSION['cart'][$name]['ingredients'][] = $row['ingredient_name'];
                }
            }
        }
    } else {
        $_SESSION['cart'][$name]['amount']++;
    }

    return true;
}

function removeFromCart(string $productName): bool
{
    $productName = trim($productName);

    if ($productName === '') {
        return false;
    }

    if (!isset($_SESSION['cart'][$productName])) {
        return false;
    }

    $_SESSION['cart'][$productName]['amount']--;

    if ($_SESSION['cart'][$productName]['amount'] <= 0) {
        unset($_SESSION['cart'][$productName]);
    }

    return true;
}

function emptyCart(): void
{
    $_SESSION['cart'] = [];
}

function getCart(): array
{
    return $_SESSION['cart'] ?? [];
}

function getCartAmount(): int
{
    if (!isset($_SESSION['cart'])) {
        return 0;
    }

    $amount = 0;

    foreach ($_SESSION['cart'] as $item) {
        $amount += (int) $item['amount'];
    }

    return $amount;
}

function getCartProductCount(): int
{
    if (!isset($_SESSION['cart'])) {
        return 0;
    }

    return count($_SESSION['cart']);
}

function getCartTotal(): float
{
    if (!isset($_SESSION['cart'])) {
        return 0;
    }

    $total = 0;

    foreach ($_SESSION['cart'] as $item) {
        $price = (float) $item['price'];
        $amount = (int) $item['amount'];

        $total += $price * $amount;
    }

    return $total;
}