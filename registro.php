<?php
session_start();

require_once __DIR__ . '/includes/db.php';

/* ==========================================
   CONFIGURACIÓN
========================================== */

$baseUrl = '/patitas-felices';
$error = '';
$nombre = '';
$correo = '';
$registroExitoso = false;

/* ==========================================
   FUNCIÓN DE SEGURIDAD
========================================== */

function limpiarRegistro($valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

/* ==========================================
   SI YA HAY UNA SESIÓN ACTIVA
========================================== */

if (
    isset($_SESSION['user_id'], $_SESSION['user_role']) &&
    (int) $_SESSION['user_id'] > 0
) {
    if ($_SESSION['user_role'] === 'admin') {
        header("Location: {$baseUrl}/admin/dashboard.php");
        exit;
    }

    header("Location: {$baseUrl}/index.php");
    exit;
}

/* ==========================================
   PROCESAR REGISTRO
========================================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $correo = strtolower(trim($_POST['correo'] ?? ''));
    $clave = $_POST['clave'] ?? '';
    $confirmarClave = $_POST['confirmar_clave'] ?? '';

    if ($nombre === '' || $correo === '' || $clave === '' || $confirmarClave === '') {
        $error = 'Por favor, completa todos los campos.';
    } elseif (mb_strlen($nombre) < 3 || mb_strlen($nombre) > 100) {
        $error = 'El nombre debe tener entre 3 y 100 caracteres.';
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL) || strlen($correo) > 150) {
        $error = 'Ingresa un correo electrónico válido.';
    } elseif (strlen($clave) < 8) {
        $error = 'La contraseña debe tener al menos 8 caracteres.';
    } elseif ($clave !== $confirmarClave) {
        $error = 'Las contraseñas no coinciden.';
    } else {
        try {
            $consulta = $pdo->prepare("
                SELECT id_usuario
                FROM usuarios
                WHERE correo = :correo
                LIMIT 1
            ");
            $consulta->execute([':correo' => $correo]);

            if ($consulta->fetch(PDO::FETCH_ASSOC)) {
                $error = 'Este correo electrónico ya está registrado.';
            } else {
                $hash = password_hash($clave, PASSWORD_DEFAULT);

                $insertar = $pdo->prepare("
                    INSERT INTO usuarios (nombre, correo, contrasena, rol, activo)
                    VALUES (:nombre, :correo, :contrasena, 'usuario', 1)
                ");

                $insertar->execute([
                    ':nombre' => $nombre,
                    ':correo' => $correo,
                    ':contrasena' => $hash
                ]);

                $registroExitoso = true;
                $nombre = '';
                $correo = '';
            }
        } catch (PDOException $e) {
            error_log('Error de registro: ' . $e->getMessage());
            $error = 'No fue posible crear la cuenta. Inténtalo nuevamente.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Crea tu cuenta en Patitas Felices.">

    <title>Crear cuenta | Patitas Felices</title>

    <link rel="icon" href="<?= $baseUrl ?>/assets/img/logo.png">
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/login.css?v=13">
</head>

<body class="login-body">

    <main class="login-page">

        <div class="login-wrapper">

            <section class="login-card">

                <!-- PANEL DE PRESENTACIÓN -->
                <div class="login-brand">

                    <div class="brand-content">

                        <a href="<?= $baseUrl ?>/index.php" class="login-logo-container">
                            <img
                                src="<?= $baseUrl ?>/assets/img/logo.png"
                                alt="Logo Patitas Felices"
                                class="login-logo"
                            >
                        </a>

                        <span class="brand-name">PATITAS FELICES</span>

                        <h1>¡Únete a nuestra familia!</h1>

                        <p>
                            Crea tu cuenta y comienza a conocer a nuestros
                            peluditos. Cada adopción responsable es una
                            oportunidad para cambiar una vida.
                        </p>

                        <div class="brand-decoration" aria-hidden="true">♥</div>

                    </div>

                    <div class="brand-circle brand-circle-one" aria-hidden="true"></div>
                    <div class="brand-circle brand-circle-two" aria-hidden="true"></div>

                </div>

                <!-- PANEL DEL FORMULARIO -->
                <div class="login-form-section">

                    <div class="login-heading">
                        <span class="eyebrow">CREA TU ESPACIO PERSONAL</span>

                        <h2>Crear cuenta</h2>

                        <p>Completa tus datos para registrarte en Patitas Felices.</p>
                    </div>

                    <?php if ($registroExitoso): ?>

                        <div class="login-alert" role="status">
                            <span>¡Tu cuenta fue creada correctamente!</span>
                        </div>

                        <p class="login-note">
                            Ya puedes iniciar sesión con tu correo y contraseña.
                        </p>

                        <a href="<?= $baseUrl ?>/login.php" class="login-back">
                            Ir a iniciar sesión →
                        </a>

                    <?php else: ?>

                        <?php if ($error !== ''): ?>
                            <div class="login-error" role="alert">
                                <?= limpiarRegistro($error) ?>
                            </div>
                        <?php endif; ?>

                        <form
                            method="POST"
                            action="<?= $baseUrl ?>/registro.php"
                            class="login-form"
                            autocomplete="on"
                        >

                            <div class="form-group">
                                <label for="nombre">Nombre completo</label>
                                <input
                                    type="text"
                                    id="nombre"
                                    name="nombre"
                                    placeholder="Escribe tu nombre"
                                    value="<?= limpiarRegistro($nombre) ?>"
                                    maxlength="100"
                                    autocomplete="name"
                                    required
                                >
                            </div>

                            <div class="form-group">
                                <label for="correo">Correo electrónico</label>
                                <input
                                    type="email"
                                    id="correo"
                                    name="correo"
                                    placeholder="ejemplo@correo.com"
                                    value="<?= limpiarRegistro($correo) ?>"
                                    maxlength="150"
                                    autocomplete="email"
                                    required
                                >
                            </div>

                            <div class="form-group">
                                <label for="clave">Contraseña</label>
                                <input
                                    type="password"
                                    id="clave"
                                    name="clave"
                                    placeholder="Mínimo 8 caracteres"
                                    minlength="8"
                                    autocomplete="new-password"
                                    required
                                >
                            </div>

                            <div class="form-group">
                                <label for="confirmar_clave">Confirmar contraseña</label>
                                <input
                                    type="password"
                                    id="confirmar_clave"
                                    name="confirmar_clave"
                                    placeholder="Repite tu contraseña"
                                    minlength="8"
                                    autocomplete="new-password"
                                    required
                                >
                            </div>

                            <button type="submit" class="login-btn">
                                Crear mi cuenta →
                            </button>

                        </form>

                        <p class="login-note">
                            Al registrarte podrás realizar y consultar tus solicitudes de adopción.
                        </p>

                        <a href="<?= $baseUrl ?>/login.php" class="login-back">
                            ← Ya tengo una cuenta
                        </a>

                    <?php endif; ?>

                    <div class="login-copy">
                        © <?= date('Y') ?> Patitas Felices. Todos los derechos reservados.
                    </div>

                </div>

            </section>

        </div>

    </main>

</body>
</html>
