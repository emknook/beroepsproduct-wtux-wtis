<?php

declare(strict_types=1);

function getUserByUsername(PDO $db, string $username): ?array
{
    $username = trim($username);

    $stmt = $db->prepare("
        SELECT username, first_name, last_name, password, role
        FROM [user]
        WHERE username = :username
    ");

    $stmt->execute([
        ':username' => $username,
    ]);

    $user = $stmt->fetch();

    return $user ?: null;
}

function registerUser(
    PDO $db,
    string $username,
    string $firstName,
    string $lastName,
    string $password,
    string $passwordRepeated
): bool {
    $username = trim($username);
    $firstName = trim($firstName);
    $lastName = trim($lastName);

    if ($username === '' || $firstName === '' || $lastName === '') {
        return false;
    }

    if ($password === '' || $passwordRepeated === '') {
        return false;
    }

    if ($password !== $passwordRepeated) {
        return false;
    }

    if (strlen($password) < 6) {
        return false;
    }

    if (getUserByUsername($db, $username) !== null) {
        return false;
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $db->prepare("
        INSERT INTO [user] (username, first_name, last_name, password, role)
        VALUES (:username, :first_name, :last_name, :password, :role)
    ");

    return $stmt->execute([
        ':username' => $username,
        ':first_name' => $firstName,
        ':last_name' => $lastName,
        ':password' => $hashedPassword,
        ':role' => 'Client',
    ]);
}

function signIn(PDO $db, string $username, string $password): bool
{
    $username = trim($username);

    if ($username === '' || $password === '') {
        return false;
    }

    $user = getUserByUsername($db, $username);

    if ($user === null) {
        return false;
    }

    if (!password_verify($password, $user['password'])) {
        return false;
    }

    session_regenerate_id(true);

    $_SESSION['user'] = [
        'username' => $user['username'],
        'first_name' => $user['first_name'],
        'last_name' => $user['last_name'],
        'role' => $user['role'],
    ];

    if ($user['role'] === 'Personnel') {
        header('Location: employee/orderoverview.php');
        exit;
    }

    header('Location: ../index.php');
    exit;
}

function signOut(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();

        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();

    header('Location: ../index.php');
    exit;
}

function isSignedIn(): bool
{
    return isset($_SESSION['user']);
}

function verifyUser(array $allowedRoles = [], string $redirectTo = 'index.php'): void
{
    if (!isset($_SESSION['user'])) {
        header('Location: ' . $redirectTo);
        exit;
    }

    if (!isset($_SESSION['user']['role'])) {
        header('Location: ' . $redirectTo);
        exit;
    }

    if ($allowedRoles !== [] && !in_array($_SESSION['user']['role'], $allowedRoles, true)) {
        header('Location: ' . $redirectTo);
        exit;
    }
}