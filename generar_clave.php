
<?php
require_once __DIR__ . '/includes/db.php';

$correo = 'admin@patitasfelices.com';
$clave = 'AdminPF2026*';

$stmt = $pdo->prepare(
    "SELECT contrasena FROM usuarios WHERE correo = ? LIMIT 1"
);
$stmt->execute([$correo]);

$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    exit('No se encontró el administrador en esta base de datos.');
}

if (password_verify($clave, $usuario['contrasena'])) {
    echo 'La contraseña temporal es correcta.';
} else {
    echo 'La contraseña temporal NO coincide con el hash guardado.';
}
