<?php

function getProductTypes(PDO $db): array
{
    $sql = "
        SELECT name
        FROM productType
        ORDER BY name
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute();

    return $stmt->fetchAll();
}


function getProducts(PDO $db, ?string $typeId = null): array
{
    $sql = "
        SELECT
            p.name,
            p.price,
            p.type_id,
            pi.ingredient_name
        FROM product p
        LEFT JOIN product_ingredient pi
            ON pi.product_name = p.name
    ";

    $params = [];

    if ($typeId !== null) {
        $sql .= " WHERE p.type_id = :type_id";
        $params[':type_id'] = $typeId;
    }

    $sql .= " ORDER BY p.type_id, p.name, pi.ingredient_name";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    $rows = $stmt->fetchAll();

    $products = [];

    foreach ($rows as $row) {
        $name = $row['name'];

        if (!isset($products[$name])) {
            $products[$name] = [
                'name' => $row['name'],
                'price' => $row['price'],
                'type_id' => $row['type_id'],
                'ingredients' => [],
            ];
        }

        if ($row['ingredient_name'] !== null) {
            $products[$name]['ingredients'][] = $row['ingredient_name'];
        }
    }

    return array_values($products);
}