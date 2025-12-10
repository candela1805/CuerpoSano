<?php
session_start();

// Verificamos si hay sesión activa
if (!isset($_SESSION['idEmpleado'])) {
    header("Location: login.html");
    exit();
}

// Si existe sesión, cargamos el HTML
include __DIR__ . '/panelPrincipal.html';
?>

