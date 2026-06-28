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

                if ($currentAddress === '' | $currentAddress === NULL) {
                    $updateUserStmt = $db->prepare("
                        UPDATE [User]
                        SET address = :address
                        WHERE username = :username
                    ");

                    $result = $updateUserStmt->execute([
                        ':address' => $address,
                        ':username' => $clientUsername,
                    ]);

                    return $result;
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

        return $orderId;
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

function getOrderStatusLabel(int $status): string
{
    return match ($status) {
        0 => 'New',
        1 => 'Accepted / being prepared',
        2 => 'In the oven',
        3 => 'Ready for delivery',
        4 => 'Out for delivery',
        5 => 'Arrived',
        10 => 'Denied',
        default => 'Unknown',
    };
}

function getOrderOverview(PDO $db): array
{
    $stmt = $db->prepare("
        SELECT
            po.order_id,
            po.client_username,
            po.client_name,
            po.personnel_username,
            po.datetime,
            po.status,
            po.address,
            pop.product_name,
            pop.quantity
        FROM Pizza_Order po
        JOIN Pizza_Order_Product pop
            ON pop.order_id = po.order_id
        ORDER BY 
            CASE 
                WHEN po.status = 0 THEN 0
                WHEN po.status IN (1, 2, 3, 4) THEN 1
                WHEN po.status = 10 THEN 2
                ELSE 3
            END,
            po.status DESC,
            po.datetime ASC,
            po.order_id ASC
    ");

    $stmt->execute();

    return groupOrderOverviewRows($stmt->fetchAll());
}

function groupOrderOverviewRows(array $rows): array
{
    $orders = [];

    foreach ($rows as $row) {
        $orderId = (int) $row['order_id'];

        if (!isset($orders[$orderId])) {
            $orders[$orderId] = [
                'order_id' => $orderId,
                'client_username' => $row['client_username'],
                'client_name' => $row['client_name'],
                'personnel_username' => $row['personnel_username'],
                'datetime' => $row['datetime'],
                'status' => (int) $row['status'],
                'address' => $row['address'],
                'products' => [],
            ];
        }

        $orders[$orderId]['products'][] = [
            'name' => $row['product_name'],
            'quantity' => (int) $row['quantity'],
        ];
    }

    return array_values($orders);
}

function updateOrderStatus(PDO $db, int $orderId, int $status): bool
{
    $allowedStatuses = [0, 1, 2, 3, 4, 5, 10];

    if (!in_array($status, $allowedStatuses, true)) {
        return false;
    }

    $stmt = $db->prepare("
        UPDATE Pizza_Order
        SET status = :status
        WHERE order_id = :order_id
    ");

    return $stmt->execute([
        ':status' => $status,
        ':order_id' => $orderId,
    ]);
}

function getLatestOrderForCurrentVisitor(PDO $db): ?array
{
    if (isSignedIn()) {
        $stmt = $db->prepare("
            SELECT TOP 1
                order_id,
                client_username,
                client_name,
                personnel_username,
                datetime,
                status,
                address
            FROM Pizza_Order
            WHERE client_username = :client_username
            ORDER BY datetime DESC, order_id DESC
        ");

        $stmt->execute([
            ':client_username' => $_SESSION['user']['username'],
        ]);

        $order = $stmt->fetch();

        return $order ?: null;
    }

    $lastOrderId = $_SESSION['last_order_id'] ?? null;

    if ($lastOrderId === null) {
        return null;
    }

    $stmt = $db->prepare("
        SELECT TOP 1
            order_id,
            client_username,
            client_name,
            personnel_username,
            datetime,
            status,
            address
        FROM Pizza_Order
        WHERE order_id = :order_id
    ");

    $stmt->execute([
        ':order_id' => $lastOrderId,
    ]);

    $order = $stmt->fetch();

    return $order ?: null;
}

function statusClass(int $currentStatus, int $stepStatus): string
{
    if ($currentStatus === 10) {
        return '';
    }

    return $currentStatus >= $stepStatus ? ' processed' : '';
}

function getOrderById(PDO $db, int $orderId): ?array
{

    $stmt = $db->prepare("
        SELECT TOP 1
            order_id,
            client_username,
            client_name,
            personnel_username,
            datetime,
            status,
            address
        FROM Pizza_Order
        WHERE order_id = :order_id
    ");

    $stmt->execute([
        ':order_id' => $orderId,
    ]);

    $order = $stmt->fetch();

    return $order ?: null;
}

function formatOrderDateTime(mixed $datetime): string
{
    if ($datetime instanceof DateTimeInterface) {
        return $datetime->format('d.m.y') . '<br>' . $datetime->format('H:i');
    }

    $timestamp = strtotime((string) $datetime);

    if ($timestamp === false) {
        return e((string) $datetime);
    }

    return date('d.m.y', $timestamp) . '<br>' . date('H:i', $timestamp);
}

function getOrderCssClass(int $status): string
{
    return match ($status) {
        1, 2, 3, 4, 5 => 'accepted',
        10 => 'denied',
        default => '',
    };
}

function isStatusProcessed(int $currentStatus, int $stepStatus): string
{
    if ($currentStatus === 10) {
        return '';
    }

    return $currentStatus >= $stepStatus ? ' processed' : '';
}

function splitAddress(string $address): ?array
{
    $address = trim($address);

    $pattern = '/^(.+?)\s+(\d+\s*[a-zA-Z]?),\s*([^,]+),\s*(.+)$/';

    if (!preg_match($pattern, $address, $matches)) {
        return null;
    }

    return [
        'street' => trim($matches[1]),
        'house_number' => trim($matches[2]),
        'zip_code' => trim($matches[3]),
        'city' => trim($matches[4]),
    ];
}