<?php
// php/helpers/csrf.php


function generate_csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    // also set token creation time (optional expiry)
    if (empty($_SESSION['csrf_token_time'])) {
        $_SESSION['csrf_token_time'] = time();
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token(?string $token): bool {
    if (empty($token) || empty($_SESSION['csrf_token'])) return false;
    // optional expiry (e.g., 30 minutes)
    $max_age = 60 * 30;
    if (isset($_SESSION['csrf_token_time']) && (time() - $_SESSION['csrf_token_time']) > $max_age) {
        unset($_SESSION['csrf_token']);
        unset($_SESSION['csrf_token_time']);
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function csrf_input_field(): string {
    $token = generate_csrf_token();
    return '<input type="hidden" name="csrf_token" value="'.htmlspecialchars($token, ENT_QUOTES).'">';
}
