<?php
declare(strict_types=1);

/* =========================================
   INICIAR SESIÓN
========================================= */

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/* =========================================
   EVITAR CACHÉ DEL NAVEGADOR
========================================= */

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: 0');

/* =========================================
   ELIMINAR DATOS DE SESIÓN
========================================= */

$_SESSION = [];

/* =========================================
   ELIMINAR COOKIE DE SESIÓN
========================================= */

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        [
            'expires' => time() - 42000,
            'path' => $params['path'],
            'domain' => $params['domain'],
            'secure' => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => $params['samesite'] ?? 'Lax'
        ]
    );
}

/* =========================================
   DESTRUIR SESIÓN
========================================= */

session_destroy();

/* =========================================
   VOLVER AL LOGIN PRINCIPAL
========================================= */

header('Location: /patitas-felices/login.php', true, 303);
exit;
