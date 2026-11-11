
<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_admin();

/* Evita el error de redeclaración de h() */
if (!function_exists('h')) {
    function h($valor): string
    {
        return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
    }
}

function redirigir(string $seccion): void
{
    header('Location: dashboard.php?seccion=' . urlencode($seccion));
    exit;
}

function aviso(string $mensaje, string $tipo = 'ok'): void
{
    $_SESSION['admin_mensaje'] = $mensaje;
    $_SESSION['admin_tipo'] = $tipo;
}

if (empty($_SESSION['admin_csrf'])) {
    $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
}

$csrf = $_SESSION['admin_csrf'];
$secciones = ['inicio', 'mascotas', 'usuarios', 'solicitudes', 'mensajes'];
$seccion = $_GET['seccion'] ?? 'inicio';

if (!in_array($seccion, $secciones, true)) {
    $seccion = 'inicio';
}

$mensaje = $_SESSION['admin_mensaje'] ?? '';
$tipoMensaje = $_SESSION['admin_tipo'] ?? 'ok';
unset($_SESSION['admin_mensaje'], $_SESSION['admin_tipo']);

$carpetaFotos = __DIR__ . '/../assets/img/mascotas/';
$rutaFotos = '../assets/img/mascotas/';

if (!is_dir($carpetaFotos)) {
    mkdir($carpetaFotos, 0775, true);
}

/* ==================================================
   PROCESAR FORMULARIOS
================================================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (
            !isset($_POST['csrf']) ||
            !hash_equals($csrf, (string) $_POST['csrf'])
        ) {
            throw new Exception('Formulario vencido. Recarga la página.');
        }

        $accion = $_POST['accion'] ?? '';

        /* GUARDAR MASCOTA */
        if ($accion === 'guardar_mascota') {
            $id = (int) ($_POST['id_mascota'] ?? 0);
            $nombre = trim($_POST['nombre'] ?? '');
            $especie = $_POST['especie'] ?? 'Perro';
            $edad = (int) ($_POST['edad'] ?? 0);
            $sexo = $_POST['sexo'] ?? 'No especificado';
            $tamano = $_POST['tamano'] ?? 'Mediano';
            $descripcion = trim($_POST['descripcion'] ?? '');
            $estado = $_POST['estado'] ?? 'Disponible';
            $foto = trim($_POST['foto_actual'] ?? '');

            if ($nombre === '') {
                throw new Exception('Escribe el nombre de la mascota.');
            }

            if (!in_array($especie, ['Perro', 'Gato', 'Otro'], true)) {
                throw new Exception('Especie no válida.');
            }

            if (!in_array($sexo, ['Macho', 'Hembra', 'No especificado'], true)) {
                throw new Exception('Sexo no válido.');
            }

            if (!in_array($tamano, ['Pequeño', 'Mediano', 'Grande'], true)) {
                throw new Exception('Tamaño no válido.');
            }

            if (!in_array($estado, ['Disponible', 'En proceso', 'Adoptado'], true)) {
                throw new Exception('Estado no válido.');
            }

            if ($edad < 0 || $edad > 100) {
                throw new Exception('La edad no es válida.');
            }

            if (
                isset($_FILES['foto']) &&
                $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE
            ) {
                $archivo = $_FILES['foto'];

                if ($archivo['error'] !== UPLOAD_ERR_OK) {
                    throw new Exception('No se pudo subir la fotografía.');
                }

                if ($archivo['size'] > 5 * 1024 * 1024) {
                    throw new Exception('La imagen no puede superar los 5 MB.');
                }

                $info = getimagesize($archivo['tmp_name']);

                if ($info === false) {
                    throw new Exception('El archivo no es una imagen válida.');
                }

                $tipos = [
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/webp' => 'webp',
                    'image/gif' => 'gif'
                ];

                if (!isset($tipos[$info['mime']])) {
                    throw new Exception('Usa una imagen JPG, PNG, WEBP o GIF.');
                }

                $nombreArchivo = bin2hex(random_bytes(16))
                    . '.' . $tipos[$info['mime']];

                if (!move_uploaded_file(
                    $archivo['tmp_name'],
                    $carpetaFotos . $nombreArchivo
                )) {
                    throw new Exception('No se pudo guardar la fotografía.');
                }

                $foto = $rutaFotos . $nombreArchivo;
            }

            if ($id > 0) {
                $sql = "UPDATE mascotas
                        SET nombre=?, especie=?, edad=?, sexo=?,
                            tamano=?, descripcion=?, foto=?, estado=?
                        WHERE id_mascota=?";

                $pdo->prepare($sql)->execute([
                    $nombre, $especie, $edad, $sexo, $tamano,
                    $descripcion, $foto, $estado, $id
                ]);

                aviso('Mascota actualizada correctamente.');
            } else {
                $sql = "INSERT INTO mascotas
                        (nombre, especie, edad, sexo, tamano,
                         descripcion, foto, estado, fecha_registro)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";

                $pdo->prepare($sql)->execute([
                    $nombre, $especie, $edad, $sexo, $tamano,
                    $descripcion, $foto, $estado
                ]);

                aviso('Mascota registrada correctamente.');
            }

            redirigir('mascotas');
        }

        /* ELIMINAR MASCOTA */
        if ($accion === 'eliminar_mascota') {
            $id = (int) ($_POST['id'] ?? 0);

            $stmt = $pdo->prepare(
                "SELECT COUNT(*) FROM solicitudes_adopcion WHERE id_mascota=?"
            );
            $stmt->execute([$id]);

            if ((int) $stmt->fetchColumn() > 0) {
                throw new Exception(
                    'Esta mascota tiene solicitudes relacionadas y no se puede eliminar.'
                );
            }

            $pdo->prepare(
                "DELETE FROM mascotas WHERE id_mascota=?"
            )->execute([$id]);

            aviso('Mascota eliminada correctamente.');
            redirigir('mascotas');
        }

        /* GUARDAR USUARIO */
        if ($accion === 'guardar_usuario') {
            $id = (int) ($_POST['id_usuario'] ?? 0);
            $nombre = trim($_POST['nombre'] ?? '');
            $correo = trim($_POST['correo'] ?? '');
            $contrasena = $_POST['contrasena'] ?? '';
            $rol = $_POST['rol'] ?? 'usuario';
            $activo = isset($_POST['activo']) ? 1 : 0;

            if ($nombre === '' || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Ingresa un nombre y correo válidos.');
            }

            if (!in_array($rol, ['admin', 'usuario'], true)) {
                throw new Exception('El rol no es válido.');
            }

            if (
                $id === (int) ($_SESSION['id_usuario'] ?? 0) &&
                $activo === 0
            ) {
                throw new Exception('No puedes desactivar tu propia cuenta.');
            }

            if ($id > 0) {
                if ($contrasena !== '') {
                    if (strlen($contrasena) < 8) {
                        throw new Exception('La contraseña debe tener mínimo 8 caracteres.');
                    }

                    $hash = password_hash($contrasena, PASSWORD_DEFAULT);

                    $sql = "UPDATE usuarios
                            SET nombre=?, correo=?, contrasena=?, rol=?, activo=?
                            WHERE id_usuario=?";

                    $pdo->prepare($sql)->execute([
                        $nombre, $correo, $hash, $rol, $activo, $id
                    ]);
                } else {
                    $sql = "UPDATE usuarios
                            SET nombre=?, correo=?, rol=?, activo=?
                            WHERE id_usuario=?";

                    $pdo->prepare($sql)->execute([
                        $nombre, $correo, $rol, $activo, $id
                    ]);
                }

                aviso('Usuario actualizado correctamente.');
            } else {
                if (strlen($contrasena) < 8) {
                    throw new Exception('La contraseña debe tener mínimo 8 caracteres.');
                }

                $hash = password_hash($contrasena, PASSWORD_DEFAULT);

                $sql = "INSERT INTO usuarios
                        (nombre, correo, contrasena, rol, activo, fecha_registro)
                        VALUES (?, ?, ?, ?, ?, NOW())";

                $pdo->prepare($sql)->execute([
                    $nombre, $correo, $hash, $rol, $activo
                ]);

                aviso('Usuario creado correctamente.');
            }

            redirigir('usuarios');
        }

        /* ACTIVAR / DESACTIVAR USUARIO */
        if ($accion === 'cambiar_activo') {
            $id = (int) ($_POST['id'] ?? 0);
            $activo = (int) ($_POST['activo'] ?? 0);

            if (
                $id === (int) ($_SESSION['id_usuario'] ?? 0) &&
                $activo === 0
            ) {
                throw new Exception('No puedes desactivar tu propia cuenta.');
            }

            $pdo->prepare(
                "UPDATE usuarios SET activo=? WHERE id_usuario=?"
            )->execute([$activo, $id]);

            aviso('Estado del usuario actualizado.');
            redirigir('usuarios');
        }

        /* ELIMINAR USUARIO */
        if ($accion === 'eliminar_usuario') {
            $id = (int) ($_POST['id'] ?? 0);

            if ($id === (int) ($_SESSION['id_usuario'] ?? 0)) {
                throw new Exception('No puedes eliminar tu propia cuenta.');
            }

            $pdo->prepare(
                "DELETE FROM usuarios WHERE id_usuario=?"
            )->execute([$id]);

            aviso('Usuario eliminado.');
            redirigir('usuarios');
        }

        /* ACTUALIZAR SOLICITUD */
        if ($accion === 'actualizar_solicitud') {
            $id = (int) ($_POST['id_solicitud'] ?? 0);
            $estado = $_POST['estado'] ?? '';

            if (!in_array(
                $estado,
                ['Pendiente', 'En revisión', 'Aprobada', 'Rechazada'],
                true
            )) {
                throw new Exception('Estado de solicitud no válido.');
            }

            $pdo->beginTransaction();

            $stmt = $pdo->prepare(
                "SELECT id_mascota FROM solicitudes_adopcion
                 WHERE id_solicitud=? FOR UPDATE"
            );
            $stmt->execute([$id]);
            $idMascota = $stmt->fetchColumn();

            if (!$idMascota) {
                $pdo->rollBack();
                throw new Exception('No se encontró la solicitud.');
            }

            if ($estado === 'Aprobada') {
                $stmt = $pdo->prepare(
                    "SELECT COUNT(*) FROM solicitudes_adopcion
                     WHERE id_mascota=? AND estado='Aprobada'
                     AND id_solicitud<>?"
                );
                $stmt->execute([$idMascota, $id]);

                if ((int) $stmt->fetchColumn() > 0) {
                    $pdo->rollBack();
                    throw new Exception(
                        'Ya existe otra solicitud aprobada para esta mascota.'
                    );
                }
            }

            $pdo->prepare(
                "UPDATE solicitudes_adopcion SET estado=? WHERE id_solicitud=?"
            )->execute([$estado, $id]);

            if ($estado === 'Aprobada') {
                $pdo->prepare(
                    "UPDATE mascotas SET estado='Adoptado' WHERE id_mascota=?"
                )->execute([$idMascota]);
            } else {
                $stmt = $pdo->prepare(
                    "SELECT COUNT(*) FROM solicitudes_adopcion
                     WHERE id_mascota=? AND estado='Aprobada'"
                );
                $stmt->execute([$idMascota]);

                if ((int) $stmt->fetchColumn() === 0) {
                    $pdo->prepare(
                        "UPDATE mascotas SET estado='Disponible'
                         WHERE id_mascota=? AND estado='Adoptado'"
                    )->execute([$idMascota]);
                }
            }

            $pdo->commit();

            aviso('Solicitud actualizada correctamente.');
            redirigir('solicitudes');
        }

        /* ELIMINAR SOLICITUD */
        if ($accion === 'eliminar_solicitud') {
            $id = (int) ($_POST['id'] ?? 0);

            $pdo->prepare(
                "DELETE FROM solicitudes_adopcion WHERE id_solicitud=?"
            )->execute([$id]);

            aviso('Solicitud eliminada.');
            redirigir('solicitudes');
        }

        /* LEER / MARCAR MENSAJE */
        if ($accion === 'marcar_mensaje') {
            $id = (int) ($_POST['id'] ?? 0);
            $leido = (int) ($_POST['leido'] ?? 1);

            $pdo->prepare(
                "UPDATE mensajes_contacto SET leido=? WHERE id_mensaje=?"
            )->execute([$leido, $id]);

            aviso('Mensaje actualizado.');
            redirigir('mensajes');
        }

        /* ELIMINAR MENSAJE */
        if ($accion === 'eliminar_mensaje') {
            $id = (int) ($_POST['id'] ?? 0);

            $pdo->prepare(
                "DELETE FROM mensajes_contacto WHERE id_mensaje=?"
            )->execute([$id]);

            aviso('Mensaje eliminado.');
            redirigir('mensajes');
        }

        throw new Exception('Acción no reconocida.');

    } catch (Throwable $e) {
        if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
            $pdo->rollBack();
        }

        if ($e instanceof PDOException) {
            error_log($e->getMessage());

            if ($e->getCode() === '23000') {
                aviso('No se pudo completar la operación: hay datos duplicados o registros relacionados.', 'error');
            } else {
                aviso('Ocurrió un error de base de datos. Revisa la estructura de las tablas.', 'error');
            }
        } else {
            aviso($e->getMessage(), 'error');
        }

        redirigir($seccion);
    }
}

/* ==================================================
   CONSULTAS
================================================== */

$totalMascotas = (int) $pdo->query(
    "SELECT COUNT(*) FROM mascotas"
)->fetchColumn();

$disponibles = (int) $pdo->query(
    "SELECT COUNT(*) FROM mascotas WHERE estado='Disponible'"
)->fetchColumn();

$totalUsuarios = (int) $pdo->query(
    "SELECT COUNT(*) FROM usuarios"
)->fetchColumn();

$totalSolicitudes = (int) $pdo->query(
    "SELECT COUNT(*) FROM solicitudes_adopcion"
)->fetchColumn();

$pendientes = (int) $pdo->query(
    "SELECT COUNT(*) FROM solicitudes_adopcion WHERE estado='Pendiente'"
)->fetchColumn();

$totalMensajes = (int) $pdo->query(
    "SELECT COUNT(*) FROM mensajes_contacto"
)->fetchColumn();

$noLeidos = (int) $pdo->query(
    "SELECT COUNT(*) FROM mensajes_contacto WHERE leido=0"
)->fetchColumn();

$mascotas = $pdo->query(
    "SELECT * FROM mascotas ORDER BY fecha_registro DESC"
)->fetchAll(PDO::FETCH_ASSOC);

$usuarios = $pdo->query(
    "SELECT id_usuario,nombre,correo,rol,activo,fecha_registro
     FROM usuarios ORDER BY id_usuario DESC"
)->fetchAll(PDO::FETCH_ASSOC);

$solicitudes = $pdo->query(
    "SELECT s.*,m.nombre AS nombre_mascota
     FROM solicitudes_adopcion s
     LEFT JOIN mascotas m ON s.id_mascota=m.id_mascota
     ORDER BY s.fecha_solicitud DESC"
)->fetchAll(PDO::FETCH_ASSOC);

$mensajes = $pdo->query(
    "SELECT * FROM mensajes_contacto ORDER BY fecha_envio DESC"
)->fetchAll(PDO::FETCH_ASSOC);

$editarMascota = null;
$editarUsuario = null;

if ($seccion === 'mascotas' && isset($_GET['editar'])) {
    $stmt = $pdo->prepare(
        "SELECT * FROM mascotas WHERE id_mascota=?"
    );
    $stmt->execute([(int) $_GET['editar']]);
    $editarMascota = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

if ($seccion === 'usuarios' && isset($_GET['editar'])) {
    $stmt = $pdo->prepare(
        "SELECT * FROM usuarios WHERE id_usuario=?"
    );
    $stmt->execute([(int) $_GET['editar']]);
    $editarUsuario = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

$titulos = [
    'inicio' => 'Panel principal',
    'mascotas' => 'Administrar mascotas',
    'usuarios' => 'Administrar usuarios',
    'solicitudes' => 'Solicitudes de adopción',
    'mensajes' => 'Mensajes de contacto'
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($titulos[$seccion]) ?> | Patitas Felices</title>

<style>
:root {
    --fondo:#09111f;
    --fondo2:#0e1a2e;
    --tarjeta:#111f35;
    --campo:#0b1729;
    --borde:#2b4262;
    --texto:#f5f8ff;
    --suave:#b8c7dc;
    --azul:#2a86f5;
    --azul2:#66adff;
    --verde:#238653;
    --amarillo:#f4bd50;
    --rojo:#d14f5b;
}
*{box-sizing:border-box}
body{margin:0;background:var(--fondo);color:var(--texto);font-family:Arial,sans-serif}
a{color:inherit;text-decoration:none}
button,input,select,textarea{font:inherit}
.barra{background:#0b1628;border-bottom:1px solid var(--borde);padding:16px 24px;display:flex;align-items:center;justify-content:space-between;gap:15px;flex-wrap:wrap}
.marca{display:flex;align-items:center;gap:12px}
.logo{width:44px;height:44px;display:grid;place-items:center;border-radius:13px;background:var(--azul);font-size:24px}
.marca h1{margin:0;font-size:20px}
.marca p{margin:4px 0 0;color:var(--suave);font-size:12px}
.cerrar{background:#233751;padding:10px 15px;border-radius:8px}
.cerrar:hover{background:var(--rojo)}
.layout{display:grid;grid-template-columns:235px minmax(0,1fr);min-height:calc(100vh - 77px)}
.menu{padding:24px 14px;background:var(--fondo2);border-right:1px solid var(--borde)}
.menu-titulo{font-size:11px;letter-spacing:1.5px;color:var(--suave);padding:0 12px}
.menu a{display:flex;align-items:center;gap:10px;padding:13px 12px;margin:5px 0;border-radius:9px;color:var(--suave)}
.menu a:hover,.menu a.activo{background:#19345a;color:#fff}
.numero{margin-left:auto;padding:3px 7px;background:#263f61;border-radius:20px;font-size:11px}
main{padding:28px;min-width:0}
.encabezado{display:flex;align-items:center;justify-content:space-between;gap:15px;flex-wrap:wrap;margin-bottom:25px}
.encabezado h2{margin:0;font-size:27px}
.encabezado p{color:var(--suave);margin:8px 0 0}
.estadisticas{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px;margin-bottom:24px}
.estadistica,.panel{background:var(--tarjeta);border:1px solid var(--borde);border-radius:14px;padding:20px;margin-bottom:20px}
.estadistica p{color:var(--suave);margin:0 0 12px}
.estadistica strong{font-size:30px}
.estadistica small{display:block;margin-top:8px;color:var(--azul2)}
.panel h3{margin:0 0 18px}
.boton{border:0;display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:10px 13px;border-radius:8px;background:var(--azul);color:#fff;cursor:pointer;font-weight:bold;font-size:13px}
.boton:hover{filter:brightness(1.12)}
.boton-sec{background:#263d5c}
.boton-rojo{background:#8c303b}
.boton-amarillo{background:#775b22}
.formulario{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:15px}
.campo{display:flex;flex-direction:column;gap:7px;min-width:0}
.campo.completo{grid-column:1/-1}
.campo label{font-size:13px;color:var(--suave)}
.campo input,.campo select,.campo textarea{width:100%;background:var(--campo);color:var(--texto);border:1px solid var(--borde);border-radius:8px;padding:11px}
.campo textarea{min-height:95px;resize:vertical}
.campo input[type=file]{padding:9px}
.ayuda{font-size:12px;color:var(--suave)}
.acciones-formulario{display:flex;gap:10px;flex-wrap:wrap;grid-column:1/-1}
.tabla-contenedor{overflow-x:auto;width:100%}
table{border-collapse:collapse;width:100%;min-width:760px}
th,td{padding:13px 12px;text-align:left;border-bottom:1px solid var(--borde);vertical-align:middle;font-size:13px}
th{color:var(--suave);background:#0e1a2c}
td{overflow-wrap:anywhere}
tbody tr:hover{background:#142640}
.foto-mini{width:58px;height:58px;object-fit:cover;border-radius:9px;background:var(--campo);border:1px solid var(--borde)}
.sin-foto{display:grid;place-items:center;width:58px;height:58px;border-radius:9px;background:#1b304d;font-size:24px}
.etiqueta{display:inline-block;padding:5px 8px;border-radius:20px;font-size:11px;background:#233751}
.verde{background:#174b37;color:#a8f1ce}
.amarillo{background:#58461e;color:#ffe4a1}
.rojo{background:#542933;color:#ffc1c8}
.acciones{display:flex;flex-wrap:wrap;gap:6px;align-items:center}
.acciones form{display:inline;margin:0}
.alerta{padding:13px 16px;border-radius:9px;margin-bottom:20px;background:#174b37;border:1px solid #238653;color:#c7ffe2}
.alerta.error{background:#542933;border-color:var(--rojo);color:#ffd5da}
.bienvenida{padding:26px;border-radius:14px;background:linear-gradient(120deg,#15365d,#12233b);border:1px solid #315b89;margin-bottom:22px}
.bienvenida h3{margin:0 0 10px;font-size:24px}
.bienvenida p{color:#d0e1f8;line-height:1.6;margin:0}
.atajos{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px}
.atajo{display:block;padding:18px;border-radius:12px;background:#142640;border:1px solid var(--borde)}
.atajo:hover{border-color:var(--azul2);background:#193252}
.atajo strong{display:block;margin-bottom:7px}
.atajo span{color:var(--suave);font-size:13px}
.detalle{max-width:230px;white-space:pre-wrap;line-height:1.5}
.sin-resultados{text-align:center;color:var(--suave);padding:25px}
.pie{text-align:center;color:#879ab5;font-size:12px;padding:20px;border-top:1px solid var(--borde)}
@media(max-width:1100px){.estadisticas{grid-template-columns:repeat(2,minmax(0,1fr))}.atajos{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:760px){
.layout{grid-template-columns:1fr}.menu{border-right:0;border-bottom:1px solid var(--borde);padding:12px}
.menu-enlaces{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:4px}
.menu a{font-size:13px;padding:11px 8px}main{padding:18px 12px}
.encabezado h2{font-size:23px}.formulario{grid-template-columns:1fr}
.campo.completo,.acciones-formulario{grid-column:auto}
.estadisticas{gap:10px}.estadistica{padding:15px}.estadistica strong{font-size:25px}
.atajos{grid-template-columns:1fr}.barra{padding:13px}
}
</style>
</head>

<body>
<header class="barra">
    <div class="marca">
        <div class="logo">🐾</div>
        <div>
            <h1>Patitas Felices</h1>
            <p>Panel de administración</p>
        </div>
    </div>
    <a class="cerrar" href="logout.php">Cerrar sesión ↗</a>
</header>

<div class="layout">
<aside class="menu">
    <p class="menu-titulo">MENÚ PRINCIPAL</p>
    <nav class="menu-enlaces">
        <a class="<?= $seccion==='inicio'?'activo':'' ?>" href="dashboard.php?seccion=inicio">⌂ Inicio</a>
        <a class="<?= $seccion==='mascotas'?'activo':'' ?>" href="dashboard.php?seccion=mascotas">🐾 Mascotas</a>
        <a class="<?= $seccion==='usuarios'?'activo':'' ?>" href="dashboard.php?seccion=usuarios">♙ Usuarios</a>
        <a class="<?= $seccion==='solicitudes'?'activo':'' ?>" href="dashboard.php?seccion=solicitudes">📋 Solicitudes <span class="numero"><?= $pendientes ?></span></a>
        <a class="<?= $seccion==='mensajes'?'activo':'' ?>" href="dashboard.php?seccion=mensajes">✉ Mensajes <span class="numero"><?= $noLeidos ?></span></a>
    </nav>
</aside>

<main>
<div class="encabezado">
    <div>
        <h2><?= h($titulos[$seccion]) ?></h2>
        <p>Gestiona la información de Patitas Felices.</p>
    </div>
</div>

<?php if ($mensaje !== ''): ?>
<div class="alerta <?= $tipoMensaje==='error'?'error':'' ?>"><?= h($mensaje) ?></div>
<?php endif; ?>

<?php if ($seccion === 'inicio'): ?>

<section class="bienvenida">
    <h3>¡Bienvenido al administrador! 🐾</h3>
    <p>Administra las mascotas, revisa las solicitudes de adopción, gestiona los usuarios y consulta los mensajes recibidos.</p>
</section>

<section class="estadisticas">
    <div class="estadistica"><p>Total de mascotas</p><strong><?= $totalMascotas ?></strong><small>Registradas</small></div>
    <div class="estadistica"><p>Mascotas disponibles</p><strong><?= $disponibles ?></strong><small>Esperando un hogar</small></div>
    <div class="estadistica"><p>Usuarios registrados</p><strong><?= $totalUsuarios ?></strong><small>Cuentas del sistema</small></div>
    <div class="estadistica"><p>Solicitudes pendientes</p><strong><?= $pendientes ?></strong><small>Por revisar</small></div>
    <div class="estadistica"><p>Total de solicitudes</p><strong><?= $totalSolicitudes ?></strong><small>Recibidas</small></div>
    <div class="estadistica"><p>Total de mensajes</p><strong><?= $totalMensajes ?></strong><small>De contacto</small></div>
</section>

<section class="panel">
    <h3>Accesos rápidos</h3>
    <div class="atajos">
        <a class="atajo" href="dashboard.php?seccion=mascotas"><strong>🐶 Administrar mascotas</strong><span>Agregar, editar y eliminar mascotas.</span></a>
        <a class="atajo" href="dashboard.php?seccion=usuarios"><strong>👤 Administrar usuarios</strong><span>Gestionar las cuentas registradas.</span></a>
        <a class="atajo" href="dashboard.php?seccion=solicitudes"><strong>📋 Solicitudes</strong><span>Revisar solicitudes de adopción.</span></a>
        <a class="atajo" href="dashboard.php?seccion=mensajes"><strong>✉ Mensajes</strong><span>Leer mensajes de contacto.</span></a>
        <a class="atajo" href="../index.php" target="_blank"><strong>🌐 Ver página pública</strong><span>Abrir la página principal.</span></a>
    </div>
</section>

<?php elseif ($seccion === 'mascotas'): ?>

<section class="panel">
<h3><?= $editarMascota?'Editar mascota':'Registrar nueva mascota' ?></h3>
<form class="formulario" method="POST" enctype="multipart/form-data" action="dashboard.php?seccion=mascotas">
    <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
    <input type="hidden" name="accion" value="guardar_mascota">
    <input type="hidden" name="id_mascota" value="<?= h($editarMascota['id_mascota']??0) ?>">
    <input type="hidden" name="foto_actual" value="<?= h($editarMascota['foto']??'') ?>">

    <div class="campo"><label>Nombre *</label><input name="nombre" required value="<?= h($editarMascota['nombre']??'') ?>"></div>
    <div class="campo"><label>Especie *</label><select name="especie"><?php foreach(['Perro','Gato','Otro'] as $v): ?><option <?= ($editarMascota['especie']??'Perro')===$v?'selected':'' ?>><?= h($v) ?></option><?php endforeach; ?></select></div>
    <div class="campo"><label>Edad en años *</label><input type="number" name="edad" min="0" max="100" required value="<?= h($editarMascota['edad']??0) ?>"></div>
    <div class="campo"><label>Sexo *</label><select name="sexo"><?php foreach(['Macho','Hembra','No especificado'] as $v): ?><option <?= ($editarMascota['sexo']??'No especificado')===$v?'selected':'' ?>><?= h($v) ?></option><?php endforeach; ?></select></div>
    <div class="campo"><label>Tamaño *</label><select name="tamano"><?php foreach(['Pequeño','Mediano','Grande'] as $v): ?><option <?= ($editarMascota['tamano']??'Mediano')===$v?'selected':'' ?>><?= h($v) ?></option><?php endforeach; ?></select></div>
    <div class="campo"><label>Estado *</label><select name="estado"><?php foreach(['Disponible','En proceso','Adoptado'] as $v): ?><option <?= ($editarMascota['estado']??'Disponible')===$v?'selected':'' ?>><?= h($v) ?></option><?php endforeach; ?></select></div>

    <div class="campo completo">
        <label>Fotografía</label>
        <input id="foto" type="file" name="foto" accept="image/jpeg,image/png,image/webp,image/gif">
        <span class="ayuda">Formatos JPG, PNG, WEBP o GIF. Máximo 5 MB.</span>
        <?php if (!empty($editarMascota['foto'])): ?><p>Fotografía actual:</p><img class="foto-mini" src="<?= h($editarMascota['foto']) ?>" alt="Foto actual"><?php endif; ?>
        <div id="vistaFoto" style="margin-top:10px"></div>
    </div>

    <div class="campo completo"><label>Descripción</label><textarea name="descripcion"><?= h($editarMascota['descripcion']??'') ?></textarea></div>

    <div class="acciones-formulario">
        <button class="boton" type="submit"><?= $editarMascota?'Guardar cambios':'Registrar mascota' ?></button>
        <label class="boton boton-sec" for="foto" style="cursor:pointer">📷 Adjuntar imágenes</label>
        <?php if ($editarMascota): ?><a class="boton boton-sec" href="dashboard.php?seccion=mascotas">Cancelar</a><?php endif; ?>
    </div>
</form>
</section>

<section class="panel">
<h3>Mascotas registradas (<?= count($mascotas) ?>)</h3>
<div class="tabla-contenedor"><table>
<thead><tr><th>Foto</th><th>Nombre</th><th>Especie</th><th>Edad</th><th>Sexo</th><th>Tamaño</th><th>Estado</th><th>Acciones</th></tr></thead>
<tbody>
<?php if (!$mascotas): ?><tr><td colspan="8" class="sin-resultados">No hay mascotas registradas.</td></tr><?php endif; ?>
<?php foreach ($mascotas as $m): ?>
<tr>
<td><?php if (!empty($m['foto'])): ?><img class="foto-mini" src="<?= h($m['foto']) ?>" alt="<?= h($m['nombre']) ?>"><?php else: ?><div class="sin-foto">🐾</div><?php endif; ?></td>
<td><?= h($m['nombre']) ?></td><td><?= h($m['especie']) ?></td><td><?= h($m['edad']) ?></td><td><?= h($m['sexo']) ?></td><td><?= h($m['tamano']) ?></td>
<td><span class="etiqueta <?= $m['estado']==='Disponible'?'verde':($m['estado']==='Adoptado'?'rojo':'amarillo') ?>"><?= h($m['estado']) ?></span></td>
<td><div class="acciones">
<a class="boton boton-sec" href="dashboard.php?seccion=mascotas&editar=<?= (int)$m['id_mascota'] ?>">Editar</a>
<form method="POST" action="dashboard.php?seccion=mascotas" onsubmit="return confirm('¿Eliminar esta mascota?')">
<input type="hidden" name="csrf" value="<?= h($csrf) ?>"><input type="hidden" name="accion" value="eliminar_mascota"><input type="hidden" name="id" value="<?= (int)$m['id_mascota'] ?>">
<button class="boton boton-rojo">Eliminar</button>
</form>
</div></td>
</tr>
<?php endforeach; ?>
</tbody></table></div>
</section>

<?php elseif ($seccion === 'usuarios'): ?>

<section class="panel">
<h3><?= $editarUsuario?'Editar usuario':'Crear usuario' ?></h3>
<form class="formulario" method="POST" action="dashboard.php?seccion=usuarios">
<input type="hidden" name="csrf" value="<?= h($csrf) ?>">
<input type="hidden" name="accion" value="guardar_usuario">
<input type="hidden" name="id_usuario" value="<?= h($editarUsuario['id_usuario']??0) ?>">
<div class="campo"><label>Nombre completo *</label><input name="nombre" required value="<?= h($editarUsuario['nombre']??'') ?>"></div>
<div class="campo"><label>Correo *</label><input type="email" name="correo" required value="<?= h($editarUsuario['correo']??'') ?>"></div>
<div class="campo"><label>Contraseña <?= $editarUsuario?'(vacía para conservarla)':'*' ?></label><input type="password" name="contrasena" <?= $editarUsuario?'':'required' ?> minlength="8" autocomplete="new-password"></div>
<div class="campo"><label>Rol</label><select name="rol"><option value="usuario" <?= ($editarUsuario['rol']??'usuario')==='usuario'?'selected':'' ?>>Usuario</option><option value="admin" <?= ($editarUsuario['rol']??'')==='admin'?'selected':'' ?>>Administrador</option></select></div>
<div class="campo completo"><label><input type="checkbox" name="activo" value="1" <?= !isset($editarUsuario['activo'])||(int)$editarUsuario['activo']===1?'checked':'' ?>> Cuenta activa</label></div>
<div class="acciones-formulario"><button class="boton"><?= $editarUsuario?'Guardar cambios':'Crear usuario' ?></button><?php if ($editarUsuario): ?><a class="boton boton-sec" href="dashboard.php?seccion=usuarios">Cancelar</a><?php endif; ?></div>
</form>
</section>

<section class="panel">
<h3>Usuarios registrados (<?= count($usuarios) ?>)</h3>
<div class="tabla-contenedor"><table>
<thead><tr><th>Nombre</th><th>Correo</th><th>Rol</th><th>Estado</th><th>Registro</th><th>Acciones</th></tr></thead>
<tbody>
<?php if (!$usuarios): ?><tr><td colspan="6" class="sin-resultados">No hay usuarios.</td></tr><?php endif; ?>
<?php foreach ($usuarios as $u): ?>
<tr>
<td><?= h($u['nombre']) ?></td><td><?= h($u['correo']) ?></td><td><?= h($u['rol']) ?></td>
<td><span class="etiqueta <?= (int)$u['activo']===1?'verde':'rojo' ?>"><?= (int)$u['activo']===1?'Activo':'Inactivo' ?></span></td>
<td><?= h($u['fecha_registro']) ?></td>
<td><div class="acciones">
<a class="boton boton-sec" href="dashboard.php?seccion=usuarios&editar=<?= (int)$u['id_usuario'] ?>">Editar</a>
<form method="POST" action="dashboard.php?seccion=usuarios"><input type="hidden" name="csrf" value="<?= h($csrf) ?>"><input type="hidden" name="accion" value="cambiar_activo"><input type="hidden" name="id" value="<?= (int)$u['id_usuario'] ?>"><input type="hidden" name="activo" value="<?= (int)$u['activo']===1?0:1 ?>"><button class="boton boton-amarillo"><?= (int)$u['activo']===1?'Desactivar':'Activar' ?></button></form>
<form method="POST" action="dashboard.php?seccion=usuarios" onsubmit="return confirm('¿Eliminar este usuario?')"><input type="hidden" name="csrf" value="<?= h($csrf) ?>"><input type="hidden" name="accion" value="eliminar_usuario"><input type="hidden" name="id" value="<?= (int)$u['id_usuario'] ?>"><button class="boton boton-rojo">Eliminar</button></form>
</div></td>
</tr>
<?php endforeach; ?>
</tbody></table></div>
</section>

<?php elseif ($seccion === 'solicitudes'): ?>

<section class="panel">
<h3>Solicitudes recibidas (<?= count($solicitudes) ?>)</h3>
<div class="tabla-contenedor"><table>
<thead><tr><th>N.º</th><th>Solicitante</th><th>Contacto</th><th>Mascota</th><th>Ciudad</th><th>Motivo</th><th>Fecha</th><th>Estado</th></tr></thead>
<tbody>
<?php if (!$solicitudes): ?><tr><td colspan="8" class="sin-resultados">No hay solicitudes.</td></tr><?php endif; ?>
<?php foreach ($solicitudes as $s): ?>
<tr>
<td>#<?= (int)$s['id_solicitud'] ?></td>
<td><?= h($s['nombre_solicitante']) ?></td>
<td><?= h($s['correo']) ?><br><?= h($s['telefono']) ?></td>
<td><?= h($s['nombre_mascota']??'No disponible') ?></td>
<td><?= h($s['ciudad']) ?></td>
<td><div class="detalle"><?= h($s['motivo']) ?></div></td>
<td><?= h($s['fecha_solicitud']) ?></td>
<td>
<form method="POST" action="dashboard.php?seccion=solicitudes">
<input type="hidden" name="csrf" value="<?= h($csrf) ?>"><input type="hidden" name="accion" value="actualizar_solicitud"><input type="hidden" name="id_solicitud" value="<?= (int)$s['id_solicitud'] ?>">
<div class="campo"><select name="estado"><?php foreach(['Pendiente','En revisión','Aprobada','Rechazada'] as $v): ?><option value="<?= h($v) ?>" <?= $s['estado']===$v?'selected':'' ?>><?= h($v) ?></option><?php endforeach; ?></select><button class="boton" style="margin-top:7px">Guardar</button></div>
</form>
<form method="POST" action="dashboard.php?seccion=solicitudes" onsubmit="return confirm('¿Eliminar esta solicitud?')" style="margin-top:7px">
<input type="hidden" name="csrf" value="<?= h($csrf) ?>"><input type="hidden" name="accion" value="eliminar_solicitud"><input type="hidden" name="id" value="<?= (int)$s['id_solicitud'] ?>"><button class="boton boton-rojo">Eliminar</button>
</form>
</td>
</tr>
<?php endforeach; ?>
</tbody></table></div>
</section>

<?php elseif ($seccion === 'mensajes'): ?>

<section class="panel">
<h3>Mensajes recibidos (<?= count($mensajes) ?>)</h3>
<div class="tabla-contenedor"><table>
<thead><tr><th>Estado</th><th>Nombre</th><th>Correo</th><th>Asunto</th><th>Mensaje</th><th>Fecha</th><th>Acciones</th></tr></thead>
<tbody>
<?php if (!$mensajes): ?><tr><td colspan="7" class="sin-resultados">No hay mensajes recibidos.</td></tr><?php endif; ?>
<?php foreach ($mensajes as $m): ?>
<tr>
<td><span class="etiqueta <?= (int)$m['leido']===1?'verde':'amarillo' ?>"><?= (int)$m['leido']===1?'Leído':'No leído' ?></span></td>
<td><?= h($m['nombre']) ?></td><td><?= h($m['correo']) ?></td><td><?= h($m['asunto']) ?></td>
<td><div class="detalle"><?= h($m['mensaje']) ?></div></td><td><?= h($m['fecha_envio']) ?></td>
<td><div class="acciones">
<form method="POST" action="dashboard.php?seccion=mensajes"><input type="hidden" name="csrf" value="<?= h($csrf) ?>"><input type="hidden" name="accion" value="marcar_mensaje"><input type="hidden" name="id" value="<?= (int)$m['id_mensaje'] ?>"><input type="hidden" name="leido" value="<?= (int)$m['leido']===1?0:1 ?>"><button class="boton boton-sec"><?= (int)$m['leido']===1?'Marcar no leído':'Marcar leído' ?></button></form>
<form method="POST" action="dashboard.php?seccion=mensajes" onsubmit="return confirm('¿Eliminar este mensaje?')"><input type="hidden" name="csrf" value="<?= h($csrf) ?>"><input type="hidden" name="accion" value="eliminar_mensaje"><input type="hidden" name="id" value="<?= (int)$m['id_mensaje'] ?>"><button class="boton boton-rojo">Eliminar</button></form>
</div></td>
</tr>
<?php endforeach; ?>
</tbody></table></div>
</section>

<?php endif; ?>
</main>
</div>

<footer class="pie">Patitas Felices · Panel de administración · Proyecto escolar</footer>

<script>
const entradaFoto = document.getElementById('foto');
const vistaFoto = document.getElementById('vistaFoto');

if (entradaFoto && vistaFoto) {
    entradaFoto.addEventListener('change', function () {
        vistaFoto.innerHTML = '';

        const archivo = this.files && this.files[0];

        if (!archivo) return;

        if (!archivo.type.startsWith('image/')) {
            vistaFoto.textContent = 'Selecciona una imagen válida.';
            this.value = '';
            return;
        }

        if (archivo.size > 5 * 1024 * 1024) {
            vistaFoto.textContent = 'La imagen no puede superar los 5 MB.';
            this.value = '';
            return;
        }

        const lector = new FileReader();
        const imagen = document.createElement('img');

        imagen.alt = 'Vista previa';
        imagen.style.width = '150px';
        imagen.style.height = '150px';
        imagen.style.objectFit = 'cover';
        imagen.style.borderRadius = '12px';

        lector.onload = function (evento) {
            imagen.src = evento.target.result;
            vistaFoto.appendChild(imagen);
        };

        lector.readAsDataURL(archivo);
    });
}
</script>
</body>
</html>
