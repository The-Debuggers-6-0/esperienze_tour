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

/*
 * Carica i servizi (pagine protette) a cui l'utente ha accesso, risolti
 * tramite la catena utente → gruppi → servizi. Restituisce una mappa
 * associativa: chiave = nome del servizio (= nome dello script), valore = true.
 */
function load_user_services(int $userId): array {
    $stmt = db()->prepare(
        'SELECT DISTINCT sg.services_username
         FROM users_has_groups ug
         JOIN services_has_groups sg ON sg.groups_id = ug.groups_id
         WHERE ug.users_id = ?'
    );
    $stmt->execute([$userId]);
    return array_fill_keys(array_column($stmt->fetchAll(), 'services_username'), true);
}

/*
 * Verifica se l'utente loggato ha accesso a un servizio.
 * I servizi vengono caricati in sessione al login (login.php).
 */
function has_service(string $service): bool {
    return isset($_SESSION['user']['services'][$service]);
}

/*
 * Autorizzazione basata sui Servizi: richiede che lo script corrente (o quello
 * indicato) sia tra i servizi concessi ai gruppi dell'utente.
 */
function require_service(?string $service = null): void {
    require_login();
    $service = $service ?? basename($_SERVER['SCRIPT_NAME']);
    if (!has_service($service)) {
        http_response_code(403);
        die('Accesso negato: servizio non autorizzato.');
    }
}

/*
 * Gate delle pagine del backoffice. L'accesso è autorizzato tramite il
 * meccanismo dei Servizi: lo script corrente deve essere un servizio
 * assegnato a un gruppo dell'utente (nel seed tutti i servizi del backoffice
 * sono concessi al gruppo "admin").
 */
function require_admin(): void {
    require_service();
}
function block_admin(): void {
    global $config;
    if (!empty($_SESSION['user']) && is_admin()) {
        header('Location: ' . $config['base'] . '/admin/index.php');
        exit;
    }
}
