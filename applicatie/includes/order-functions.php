<?php

declare(strict_types=1);

function createOrder(PDO $db, ?string $clientUsername, string $clientName, string $address)
{
    $cart = getCart();

    if ($cart === []) {
        return 'no cart';
    }

    $clientName = trim($clientName);
    $address = trim($address);

    if ($clientName === '') {
        return 'no client name';
    }

    if ($address === '') {
        return 'no address';
    }

    if ($clientUsername !== null) {
        $clientUsername = trim($clientUsername);

        if ($clientUsername === '') {
            $clientUsername = null;
        }
    }

    try {
        $db->beginTransaction();

        $personnelStmt = $db->prepare("
            SELECT TOP 1 username
            FROM [User]
            WHERE role = :role
            ORDER BY NEWID()
        ");

        $personnelStmt->execute([
            ':role' => 'Personnel',
        ]);

        $personnelUsername = $personnelStmt->fetchColumn();

        if ($personnelUsername === false) {
            throw new RuntimeException('No personnel found to assign to the order.');
        }

        if ($clientUsername !== null) {
            $userStmt = $db->prepare("
                SELECT TOP 1 address
                FROM [User]
                WHERE username = :username
            ");

            $userStmt->execute([
                ':username' => $clientUsername,
            ]);

            $user = $userStmt->fetch();

            if ($user !== false) {
                $currentAddress = trim((string) ($user['address'] ?? ''));

                if ($currentAddress === '') {
                    $updateUserStmt = $db->prepare("
                        UPDATE [User]
                        SET address = :address
                        WHERE username = :username
                    ");

                    $updateUserStmt->execute([
                        ':address' => $address,
                        ':username' => $clientUsername,
                    ]);
                }
            }
        }

        $orderStmt = $db->prepare("
            INSERT INTO Pizza_Order (
                client_username,
                client_name,
                personnel_username,
                datetime,
                status,
                address
            )
            OUTPUT INSERTED.order_id
            VALUES (
                :client_username,
                :client_name,
                :personnel_username,
                GETDATE(),
                :status,
                :address
            )
        ");

        $orderStmt->execute([
            ':client_username' => $clientUsername,
            ':client_name' => $clientName,
            ':personnel_username' => $personnelUsername,
            ':status' => 0,
            ':address' => $address,
        ]);

        $orderId = (int) $orderStmt->fetchColumn();

        foreach ($cart as $item) {
            $productStmt = $db->prepare("
                INSERT INTO Pizza_Order_Product (
                    order_id,
                    product_name,
                    quantity
                )
                VALUES (
                    :order_id,
                    :product_name,
                    :quantity
                )
            ");

            $productStmt->execute([
                ':order_id' => $orderId,
                ':product_name' => $item['name'],
                ':quantity' => $item['amount'],
            ]);
        }

        $db->commit();

        emptyCart();

        return true;
    } catch (Throwable $exception) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }

        return $exception;
    }
}

function getOrdersForUser(PDO $db, string $username): array
{
    $stmt = $db->prepare("
        SELECT
            po.order_id,
            po.client_username,
            po.personnel_username,
            po.datetime,
            po.status,
            po.address,
            pop.product_name,
            pop.quantity,
            p.price
        FROM Pizza_Order po
        JOIN Pizza_Order_Product pop
            ON pop.order_id = po.order_id
        JOIN Product p
            ON p.name = pop.product_name
        WHERE po.client_username = :username
        ORDER BY po.datetime DESC, po.order_id DESC
    ");

    $stmt->execute([
        ':username' => $username,
    ]);

    return groupOrders($stmt->fetchAll());
}

function getAllOrders(PDO $db): array
{
    $stmt = $db->prepare("
        SELECT
            po.order_id,
            po.client_username,
            po.personnel_username,
            po.datetime,
            po.status,
            po.address,
            pop.product_name,
            pop.quantity,
            p.price
        FROM Pizza_Order po
        JOIN Pizza_Order_Product pop
            ON pop.order_id = po.order_id
        JOIN Product p
            ON p.name = pop.product_name
        ORDER BY po.datetime DESC, po.order_id DESC
    ");

    $stmt->execute();

    return groupOrders($stmt->fetchAll());
}

function groupOrders(array $rows): array
{
    $orders = [];

    foreach ($rows as $row) {
        $orderId = (int) $row['order_id'];

        if (!isset($orders[$orderId])) {
            $orders[$orderId] = [
                'order_id' => $orderId,
                'client_username' => $row['client_username'],
                'personnel_username' => $row['personnel_username'],
                'datetime' => $row['datetime'],
                'status' => $row['status'],
                'address' => $row['address'],
                'products' => [],
                'total' => 0,
            ];
        }

        $price = (float) $row['price'];
        $quantity = (int) $row['quantity'];

        $orders[$orderId]['products'][] = [
            'name' => $row['product_name'],
            'quantity' => $quantity,
            'price' => $price,
            'total' => $price * $quantity,
        ];

        $orders[$orderId]['total'] += $price * $quantity;
    }

    return array_values($orders);
}