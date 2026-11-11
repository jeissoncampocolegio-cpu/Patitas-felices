<?php
declare(strict_types=1);

/* =========================================
   INICIAR SESIÓN
========================================= */

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/* =========================================
   RUTA PRINCIPAL DEL PROYECTO
========================================= */

if (!defined('BASE_URL')) {
    define('BASE_URL', '/patitas-felices');
}

/* =========================================
   EXIGIR ACCESO DE ADMINISTRADOR
========================================= */

if (!function_exists('require_admin')) {
    function require_admin(): void
    {
        $adminId = $_SESSION['admin_id']
            ?? $_SESSION['user_id']
            ?? $_SESSION['id_usuario']
            ?? null;

        $adminRole = $_SESSION['admin_role']
            ?? $_SESSION['user_role']
            ?? $_SESSION['rol']
            ?? $_SESSION['usuario_rol']
            ?? '';

        $adminRole = strtolower(trim((string) $adminRole));

        if (empty($adminId) || $adminRole !== 'admin') {
            header('Location: ' . BASE_URL . '/login.php', true, 303);
            exit;
        }
    }
}

/* =========================================
   ESCAPAR TEXTO HTML
========================================= */

if (!function_exists('h')) {
    function h(?string $value): string
    {
        return htmlspecialchars(
            $value ?? '',
            ENT_QUOTES,
            'UTF-8'
        );
    }
}
