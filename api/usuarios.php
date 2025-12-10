<?php
while (ob_get_level() > 0) { ob_end_clean(); }
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

session_start();

// Verificar que el usuario esté logueado
if (!isset($_SESSION['usuario']) || !isset($_SESSION['idEmpleado'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

// Ajustar la ruta según la estructura de carpetas
// Si usuarios.php está en /api/ y db.php está en la raíz de htdocs
$db_path = __DIR__ . '/../db.php';
if (!file_exists($db_path)) {
    // Intentar otra ruta posible
    $db_path = __DIR__ . '/db.php';
}

if (!file_exists($db_path)) {
    http_response_code(500);
    echo json_encode(['error' => 'No se encuentra el archivo de configuración de base de datos']);
    exit;
}

require_once $db_path;

function jexit($code, $payload) {
    http_response_code($code);
    echo json_encode($payload);
    exit;
}

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? '');
$action = $_GET['action'] ?? '';

try {
    // OBTENER PERFIL DEL USUARIO LOGUEADO
    if ($method === 'GET' && $action === 'perfil') {
        $stmt = $pdo->prepare('
            SELECT 
                e.idEmpleado,
                e.dni,
                e.cargo,
                e.fechaIngreso,
                e.usuario,
                e.activo,
                p.nombre,
                p.apellido,
                p.direccion,
                p.telefono,
                p.fechaNacimiento,
                p.email
            FROM Empleado e
            INNER JOIN Persona p ON e.dni = p.dni
            WHERE e.idEmpleado = ?
            LIMIT 1
        ');
        $stmt->execute([$_SESSION['idEmpleado']]);
        $perfil = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$perfil) {
            jexit(404, ['error' => 'Usuario no encontrado']);
        }
        
        // Agregar información de sesión
        $perfil['usuarioSesion'] = $_SESSION['usuario'];
        
        jexit(200, ['success' => true, 'data' => $perfil]);
    }

    // OBTENER TODOS LOS USUARIOS
    if ($method === 'GET' && $action === 'listar') {
        $stmt = $pdo->query('
            SELECT 
                e.idEmpleado,
                e.dni,
                e.cargo,
                e.fechaIngreso,
                e.usuario,
                e.activo,
                p.nombre,
                p.apellido,
                p.direccion,
                p.telefono,
                p.fechaNacimiento,
                p.email
            FROM Empleado e
            INNER JOIN Persona p ON e.dni = p.dni
            ORDER BY e.idEmpleado DESC
        ');
        $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        jexit(200, ['success' => true, 'data' => $usuarios]);
    }

    // ACTUALIZAR USUARIO
    if ($method === 'POST' && $action === 'actualizar') {
        $idEmpleado = $_POST['idEmpleado'] ?? '';
        $nombre = trim($_POST['nombre'] ?? '');
        $apellido = trim($_POST['apellido'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $direccion = trim($_POST['direccion'] ?? '');
        $cargo = trim($_POST['cargo'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!$idEmpleado || !$nombre || !$apellido || !$email || !$cargo) {
            jexit(400, ['error' => 'Datos incompletos']);
        }

        // Obtener DNI del empleado
        $stmt = $pdo->prepare('SELECT dni FROM Empleado WHERE idEmpleado = ?');
        $stmt->execute([$idEmpleado]);
        $empleado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$empleado) {
            jexit(404, ['error' => 'Empleado no encontrado']);
        }

        $pdo->beginTransaction();

        try {
            // Actualizar datos en la tabla Persona
            $stmt = $pdo->prepare('
                UPDATE Persona 
                SET nombre = ?, apellido = ?, email = ?, telefono = ?, direccion = ?
                WHERE dni = ?
            ');
            $stmt->execute([$nombre, $apellido, $email, $telefono, $direccion, $empleado['dni']]);

            // Actualizar cargo en la tabla Empleado
            if (!empty($password)) {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('
                    UPDATE Empleado 
                    SET cargo = ?, password = ?
                    WHERE idEmpleado = ?
                ');
                $stmt->execute([$cargo, $hashedPassword, $idEmpleado]);
            } else {
                $stmt = $pdo->prepare('
                    UPDATE Empleado 
                    SET cargo = ?
                    WHERE idEmpleado = ?
                ');
                $stmt->execute([$cargo, $idEmpleado]);
            }

            $pdo->commit();
            jexit(200, ['success' => true, 'message' => 'Usuario actualizado correctamente']);
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    // ELIMINAR USUARIO
    if ($method === 'POST' && $action === 'eliminar') {
        $idEmpleado = $_POST['idEmpleado'] ?? '';

        if (!$idEmpleado) {
            jexit(400, ['error' => 'ID de empleado requerido']);
        }

        // No permitir que el usuario se elimine a sí mismo
        if ($idEmpleado == $_SESSION['idEmpleado']) {
            jexit(400, ['error' => 'No puede eliminar su propia cuenta']);
        }

        // Obtener DNI del empleado
        $stmt = $pdo->prepare('SELECT dni FROM Empleado WHERE idEmpleado = ?');
        $stmt->execute([$idEmpleado]);
        $empleado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$empleado) {
            jexit(404, ['error' => 'Empleado no encontrado']);
        }

        $pdo->beginTransaction();

        try {
            // Eliminar de Empleado primero
            $stmt = $pdo->prepare('DELETE FROM Empleado WHERE idEmpleado = ?');
            $stmt->execute([$idEmpleado]);

            // Luego eliminar de Persona
            $stmt = $pdo->prepare('DELETE FROM Persona WHERE dni = ?');
            $stmt->execute([$empleado['dni']]);

            $pdo->commit();
            jexit(200, ['success' => true, 'message' => 'Usuario eliminado correctamente']);
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    // CAMBIAR ESTADO (ACTIVAR/DESACTIVAR)
    if ($method === 'POST' && $action === 'cambiarEstado') {
        $idEmpleado = $_POST['idEmpleado'] ?? '';
        $activo = $_POST['activo'] ?? '';

        if (!$idEmpleado || $activo === '') {
            jexit(400, ['error' => 'Datos incompletos']);
        }

        $stmt = $pdo->prepare('UPDATE Empleado SET activo = ? WHERE idEmpleado = ?');
        $stmt->execute([$activo, $idEmpleado]);

        jexit(200, ['success' => true, 'message' => 'Estado actualizado correctamente']);
    }

    // Si no coincide ninguna acción
    jexit(400, ['error' => 'Acción no válida']);

} catch (Throwable $e) {
    error_log('usuarios.php error: ' . $e->getMessage());
    jexit(500, ['error' => 'Error interno del servidor']);
}
?>