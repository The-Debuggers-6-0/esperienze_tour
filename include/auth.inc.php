<?php

/*
 * Funzioni di controllo accesso.
 * Incluso nelle pagine che richiedono autenticazione o privilegi specifici.
 */

function require_login(): void {
    global $config;
    if (empty($_SESSION['user'])) {
        header('Location: ' . $config['base'] . '/login.php');
        exit;
    }
}

function is_admin(): bool {
    if (empty($_SESSION['user']['id'])) {
        return false;
    }
    $stmt = db()->prepare(
        'SELECT 1 FROM users_has_groups ug
         JOIN groups g ON g.id = ug.groups_id
         WHERE ug.users_id = ? AND g.name = ?'
    );
    $stmt->execute([$_SESSION['user']['id'], 'admin']);
    return (bool)$stmt->fetch();
}

function require_admin(): void {
    global $config;
    require_login();

    if (!is_admin()) {
        http_response_code(403);
        die('Accesso negato.');
    }
}
function block_admin(): void {
    global $config;
    if (!empty($_SESSION['user']) && is_admin()) {
        header('Location: ' . $config['base'] . '/admin/index.php');
        exit;
    }
}
