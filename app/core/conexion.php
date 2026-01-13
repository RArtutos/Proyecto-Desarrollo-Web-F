<?php
require_once __DIR__ . '/../config/config.php';


$conexion = mysqli_connect($servidor, $usuario, $contrasena, $basededatos);

if (!$conexion) {
  die("Error conexión: " . mysqli_connect_error());
}

mysqli_set_charset($conexion, "utf8mb4");
