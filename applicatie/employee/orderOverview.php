<?php

require_once '../includes/setup.php';

$db = db();

verifyUser(['Personnel'], '../index.php');

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'set_status') {
        $orderId = (int) ($_POST['order_id'] ?? 0);
        $status = (int) ($_POST['status'] ?? -1);

        if ($orderId > 0) {
            $success = updateOrderStatus($db, $orderId, $status);

            if (!$success) {
                $error = 'Could not update order status.';
            }
        }

        header('Location: orderOverview.php');
        exit;
    }
}

$orders = getOrderOverview($db);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order overview</title>
    <link rel="stylesheet" href="/css/main.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
</head>

<body>

    <main>
        <div class="order-overview">
            <?php if ($error !== null): ?>
                <p class="form-error"><?= e($error) ?></p>
            <?php endif; ?>

            <?php if ($orders === []): ?>
                <p>No orders found.</p>
            <?php endif; ?>

            <?php foreach ($orders as $order): ?>
                <?php
                $orderId = (int) $order['order_id'];
                $status = (int) $order['status'];
                $orderClass = getOrderCssClass($status);
                $clientName = e((string) $order['client_name']);
                $address = nl2br(e(str_replace(', ', "\n", (string) $order['address'])));
                $datetime = formatOrderDateTime($order['datetime']);
                $statusLabel = e(getOrderStatusLabel($status));
                ?>

                <div class="order <?= e($orderClass) ?>">
                    <div class="order-summary">
                        <div class="order-summary-products">
                            <?php foreach ($order['products'] as $product): ?>
                                <div class="order-summary-product">
                                    <div class="order-summary-product__name">
                                        <?= e((string) $product['name']) ?>
                                    </div>

                                    <div class="order-summary-product__amount">
                                        <?= (int) $product['quantity'] ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="order-summary-details">
                            <div>
                                <div class="order-summary-details__name">
                                    <?= $clientName ?>
                                </div>

                                <div class="order-summary-details__address">
                                    <?= $address ?>
                                </div>
                            </div>

                            <div class="order-summary-details__time">
                                <?= $datetime ?>
                            </div>
                        </div>
                    </div>

                    <?php if ($status !== 0): ?>
                        <div class="order-actions-accepted">
                            <?= renderStatusButton($orderId, $status, 1, 'Accepted / being prepared') ?>
                            <?= renderStatusButton($orderId, $status, 2, 'In the oven') ?>
                            <?= renderStatusButton($orderId, $status, 3, 'Ready for delivery') ?>
                            <?= renderStatusButton($orderId, $status, 4, 'Out for delivery') ?>
                        </div>
                    <?php endif; ?>

                    <?= renderAcceptDenyActions($orderId, $status) ?>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

</body>

</html>