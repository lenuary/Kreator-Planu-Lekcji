<?php
// Ustawienia dla domyślnego XAMPP
$host = 'localhost';
$db   = 'szkola_plan';
$user = 'root';
$pass = ''; // W XAMPP domyślnie hasło jest puste
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // W przypadku błędu zwróć JSON z błędem
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Błąd połączenia z bazą: ' . $e->getMessage()]);
    exit;
}
?>