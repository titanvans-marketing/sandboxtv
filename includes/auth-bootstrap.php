<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/db.php';

/**
 * Adjust these keys if your login.php uses different session names.
 */
function titan_get_logged_in_user_id(): ?int
{
    if (!empty($_SESSION['user_id'])) {
        return (int) $_SESSION['user_id'];
    }

    if (!empty($_SESSION['account_user_id'])) {
        return (int) $_SESSION['account_user_id'];
    }

    if (!empty($_SESSION['user']['id'])) {
        return (int) $_SESSION['user']['id'];
    }

    return null;
}

function titan_get_logged_in_user(PDO $pdo): ?array
{
    $userId = titan_get_logged_in_user_id();

    if (!$userId) {
        return null;
    }

    /**
     * Change table / column names here if your schema differs.
     */
    $stmt = $pdo->prepare("
        SELECT
            id,
            first_name,
            last_name,
            email,
            phone
        FROM users
        WHERE id = :id
        LIMIT 1
    ");
    $stmt->execute(['id' => $userId]);

    $user = $stmt->fetch();

    return $user ?: null;
}

function titan_is_checkout_guest(): bool
{
    return !empty($_SESSION['checkout_guest']) && $_SESSION['checkout_guest'] === true;
}

function titan_set_checkout_guest(bool $value): void
{
    $_SESSION['checkout_guest'] = $value;
}

function titan_clear_checkout_guest(): void
{
    unset($_SESSION['checkout_guest']);
}