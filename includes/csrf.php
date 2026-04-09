<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/*
|--------------------------------------------------------------------------
| Get or create CSRF token
|--------------------------------------------------------------------------
*/
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

/*
|--------------------------------------------------------------------------
| Output hidden CSRF input field
|--------------------------------------------------------------------------
*/
function csrf_input(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

/*
|--------------------------------------------------------------------------
| Verify submitted CSRF token
|--------------------------------------------------------------------------
*/
function verify_csrf_token(): bool
{
    $submittedToken = $_POST['csrf_token'] ?? '';
    $sessionToken = $_SESSION['csrf_token'] ?? '';

    if ($submittedToken === '' || $sessionToken === '') {
        return false;
    }

    return hash_equals($sessionToken, $submittedToken);
}

/*
|--------------------------------------------------------------------------
| Enforce CSRF validation on POST
|--------------------------------------------------------------------------
*/
function require_valid_csrf(): void
{
    if (!verify_csrf_token()) {
        http_response_code(403);
        die('Invalid CSRF token.');
    }
}