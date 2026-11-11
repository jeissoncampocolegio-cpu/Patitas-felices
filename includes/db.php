<?php
declare(strict_types=1);

$host = 'localhost';
$dbname = 'patitas_felices';
$username = 'root';
$password = ''; // Configuración habitual de XAMPP local. Ajustar si tu MySQL tiene contraseña.

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO(
        "mysql:host={$host};dbname={$dbname};charset=utf8mb4",
        $username,
        $password,
        $options
    );
} catch (PDOException $e) {
    http_response_code(500);
    exit('No se pudo conectar con la base de datos. Revisa includes/db.php y que MySQL esté iniciado en XAMPP.');
}
