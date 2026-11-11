
<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$pageTitle = $pageTitle ?? 'Patitas Felices';

/* =========================================
   RUTAS
========================================= */

$baseUrl = '/patitas-felices';

$logoPath = $baseUrl . '/assets/img/logo.png';

$archivoCss = __DIR__ . '/../assets/css/styles.css';

$versionCss = file_exists($archivoCss)
    ? filemtime($archivoCss)
    : time();

$cssPath = $baseUrl . '/assets/css/styles.css?v=' . $versionCss;

$archivoJs = __DIR__ . '/../assets/js/app.js';

$versionJs = file_exists($archivoJs)
    ? filemtime($archivoJs)
    : time();

$jsPath = $baseUrl . '/assets/js/app.js?v=' . $versionJs;

/* =========================================
   ENLACES
========================================= */

$inicioUrl = $baseUrl . '/index.php';
$mascotasUrl = $baseUrl . '/mascotas.php';
$nosotrosUrl = $baseUrl . '/nosotros.php';
$contactoUrl = $baseUrl . '/contacto.php';
$loginUrl = $baseUrl . '/login.php';
$perfilUrl = $baseUrl . '/perfil.php';
$logoutUrl = $baseUrl . '/admin/logout.php';
$dashboardUrl = $baseUrl . '/admin/dashboard.php';

/* =========================================
   SESIÓN
========================================= */

$usuarioId = $_SESSION['user_id']
    ?? $_SESSION['admin_id']
    ?? null;

$usuarioNombre = $_SESSION['user_name']
    ?? $_SESSION['admin_name']
    ?? $_SESSION['nombre']
    ?? $_SESSION['usuario_nombre']
    ?? null;

$usuarioRol = $_SESSION['user_role']
    ?? $_SESSION['admin_role']
    ?? $_SESSION['rol']
    ?? $_SESSION['usuario_rol']
    ?? '';

$haySesion = !empty($usuarioId);

$esAdmin = $haySesion &&
    strtolower((string)$usuarioRol) === 'admin';

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        <?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?>
        | Patitas Felices
    </title>

    <link rel="stylesheet" href="<?= htmlspecialchars($cssPath) ?>">

    <link rel="icon" href="<?= htmlspecialchars($logoPath) ?>">

</head>

<body>

<!-- =========================================
     HEADER PRINCIPAL
========================================= -->

<header class="header">

    <div class="container">

        <!-- LOGO -->
        <a href="<?= $inicioUrl ?>" class="logo">

            <img src="<?= $logoPath ?>" alt="Patitas Felices">

            <span>Patitas Felices</span>

        </a>

        <!-- NAVEGACIÓN -->
        <nav class="nav">

            <a href="<?= $inicioUrl ?>">Inicio</a>

            <a href="<?= $mascotasUrl ?>">Mascotas</a>

            <a href="<?= $nosotrosUrl ?>">Nosotros</a>

            <a href="<?= $contactoUrl ?>">Contacto</a>

            <?php if ($haySesion): ?>

                <a href="<?= $perfilUrl ?>">Mi perfil</a>

                <?php if ($esAdmin): ?>

                    <a href="<?= $dashboardUrl ?>">Administración</a>

                <?php endif; ?>

                <a href="<?= $logoutUrl ?>">Cerrar sesión</a>

            <?php else: ?>

                <a href="<?= $loginUrl ?>" class="btn-login">
                    Iniciar sesión
                </a>

            <?php endif; ?>

        </nav>

    </div>

</header>

 