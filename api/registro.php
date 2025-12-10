<?php
session_start();
require_once '../db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'] ?? '';
    $usuario = $_POST['usuario'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirmar = $_POST['confirmar'] ?? '';
    $dni = $_POST['dni'] ?? '';
    $cargo = $_POST['cargo'] ?? '';

    if (!$nombre || !$usuario || !$email || !$password || !$confirmar || !$dni || !$cargo) {
        http_response_code(400);
        echo json_encode(['error' => 'Todos los campos son obligatorios.']);
        exit;
    } elseif ($password !== $confirmar) {
        http_response_code(400);
        echo json_encode(['error' => 'Las contraseñas no coinciden.']);
        exit;
    } else {
        try {
            $pdo->beginTransaction();

            $stmtPersona = $pdo->prepare(
                "INSERT INTO Persona (dni, nombre, email) VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), email = VALUES(email)"
            );
            $stmtPersona->execute([$dni, $nombre, $email]);

            $stmtUsuario = $pdo->prepare("SELECT idEmpleado FROM Empleado WHERE usuario = ?");
            $stmtUsuario->execute([$usuario]);

            if ($stmtUsuario->fetch()) {
                $pdo->rollBack();
                http_response_code(409); // Conflict
                echo json_encode(['error' => 'El nombre de usuario ya existe, elige otro.']);
                exit;
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $fechaIngreso = date('Y-m-d');
                $stmtEmpleado = $pdo->prepare(
                    "INSERT INTO Empleado (dni, cargo, fechaIngreso, usuario, password) VALUES (?, ?, ?, ?, ?)"
                );
                $stmtEmpleado->execute([$dni, $cargo, $fechaIngreso, $usuario, $hashed_password]);

                $pdo->commit();
                echo json_encode([
                    'success' => true,
                    'message' => "Registro exitoso. <a href='login.html'>Iniciá sesión</a>"
                ]);
                exit;
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            http_response_code(500);
            echo json_encode(['error' => 'Error al registrar en la base de datos.']);
            exit;
        }
    }
}

// Si el método no es POST
http_response_code(405); // Method Not Allowed
echo json_encode(['error' => 'Método no permitido.']);
?>
