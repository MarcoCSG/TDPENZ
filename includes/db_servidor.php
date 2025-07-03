<?php
$host = 'localhost';
$dbname = 'u510842518_tdpenz';
$user = 'u510842518_grupopenz2025';
$pass = '&3+amdNV;9b';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
?>
