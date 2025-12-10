<?php
while (ob_get_level() > 0) { ob_end_clean(); }
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
session_start();
require_once '../db.php';

function jexit($code, $payload) {
    http_response_code($code);
    echo json_encode($payload);
    exit;
}

if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    jexit(405, ['error' => 'Metodo no permitido']);
}

$usuario = isset($_POST['usuario']) ? trim((string)$_POST['usuario']) : '';
$password_form = isset($_POST['password']) ? (string)$_POST['password'] : '';

if ($usuario === '' || $password_form === '') {
    jexit(400, ['error' => 'Usuario y contrasena son requeridos.']);
}

try {
    $stmt = $pdo->prepare('SELECT idEmpleado, usuario, password FROM empleado WHERE usuario = ? LIMIT 1');
    $stmt->execute([$usuario]);
    $empleado = $stmt->fetch(PDO::FETCH_ASSOC);

    $passOk = false;
    if ($empleado) {
        $hash = (string)($empleado['password'] ?? '');
        if ($hash !== '' && (strpos($hash, '$2y$') === 0 || strpos($hash, '$argon2') === 0)) {
            $passOk = password_verify($password_form, $hash);
        } else {
            $passOk = hash_equals($hash, $password_form);
        }
    }

    if ($empleado && $passOk) {
        session_regenerate_id(true);
        $_SESSION['usuario'] = $empleado['usuario'];
        $_SESSION['idEmpleado'] = $empleado['idEmpleado'];
        jexit(200, ['success' => true, 'message' => 'Login OK', 'redirect' => '/pages/panelPrincipal.php']);
    }

    jexit(401, ['error' => 'Usuario o contrasena incorrectos.']);

} catch (Throwable $e) {
    error_log('login.php error: ' . $e->getMessage());
    jexit(500, ['error' => 'Error interno del servidor.']);
}
?>

