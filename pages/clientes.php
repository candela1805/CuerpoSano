<?php
session_start();
require_once '../db.php';

// --- SOLUCIÓN: Mover el encabezado y la función al principio ---
header('Content-Type: application/json; charset=utf-8');

function getClientePorDNI($pdo, $dni) {
    $stmt = $pdo->prepare("SELECT c.*, tm.nombre as membresia FROM miembro LEFT JOIN TipoMembresia tm ON c.tipoMembresiaId = tm.idTipoMembresia WHERE c.dni = ?");
    $stmt->execute([$dni]);
    return $stmt->fetch();
}

if (!isset($_SESSION['idEmpleado'])) {
    http_response_code(403); // Forbidden
    echo json_encode(['error' => 'Acceso no autorizado.']);
    exit();
}

try {
    $method = $_SERVER['REQUEST_METHOD'];

    switch ($method) {
        case 'GET':
            if (isset($_GET['dni'])) {
                $cliente = getClientePorDNI($pdo, $_GET['dni']);
                if ($cliente) {
                    echo json_encode($cliente);
                } else {
                    http_response_code(404);
                    echo json_encode(['error' => 'Cliente no encontrado.']);
                }
            } else {
                $stmt = $pdo->query("SELECT c.*, tm.nombre as membresia FROM miembro LEFT JOIN TipoMembresia tm ON c.tipoMembresiaId = tm.idTipoMembresia ORDER BY c.apellido, c.nombre");
                $clientes = $stmt->fetchAll();
                echo json_encode($clientes);
            }
            break;

        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);

            // Validación básica
            if (empty($data['dni']) || empty($data['nombre']) || empty($data['apellido'])) {
                http_response_code(400);
                echo json_encode(['error' => 'DNI, Nombre y Apellido son obligatorios.']);
                exit;
            }

            // Verificar si el DNI ya existe
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM miembro WHERE dni = ?");
            $stmt->execute([$data['dni']]);
            if ($stmt->fetchColumn() > 0) {
                http_response_code(409); // Conflict
                echo json_encode(['error' => 'Ya existe un cliente con ese DNI.']);
                exit;
            }

            $sql = "INSERT INTO miembro (dni, nombre, apellido, fechaNacimiento, telefono, direccion, email, tipoMembresiaId, descuento, foto, codigo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            
            $stmt->execute([
                $data['dni'],
                $data['nombre'],
                $data['apellido'],
                empty($data['fechaNacimiento']) ? null : $data['fechaNacimiento'],
                $data['telefono'] ?? null,
                $data['direccion'] ?? null,
                $data['email'] ?? null,
                $data['tipoMembresiaId'] ?? 1,
                $data['descuento'] ?? 0,
                $data['foto'] ?? null,
                $data['codigo'] ?? 'CS' . $data['dni']
            ]);
            http_response_code(201); // Created
            echo json_encode(['message' => 'Cliente agregado con éxito.']);
            break;

        case 'PUT':
            $dni = $_GET['dni'] ?? null;
            if (!$dni) {
                http_response_code(400);
                echo json_encode(['error' => 'DNI no especificado para actualizar.']);
                exit;
            }

            $data = json_decode(file_get_contents('php://input'), true);

            // El DNI no se puede cambiar, así que lo tomamos del query param.
            // Si se permitiera cambiar, la lógica sería más compleja.

            $sql = "UPDATE miembro SET nombre = ?, apellido = ?, fechaNacimiento = ?, telefono = ?, direccion = ?, email = ?, tipoMembresiaId = ?, descuento = ?, foto = ?, codigo = ? WHERE dni = ?";
            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                $data['nombre'],
                $data['apellido'],
                empty($data['fechaNacimiento']) ? null : $data['fechaNacimiento'],
                $data['telefono'] ?? null,
                $data['direccion'] ?? null,
                $data['email'] ?? null,
                $data['tipoMembresiaId'] ?? 1,
                $data['descuento'] ?? 0,
                $data['foto'] ?? null,
                $data['codigo'] ?? 'CS' . $dni,
                $dni
            ]);
            echo json_encode(['message' => 'Cliente actualizado con éxito.']);
            break;

        case 'DELETE':
            $dni = $_GET['dni'] ?? null;
            if (!$dni) {
                http_response_code(400);
                echo json_encode(['error' => 'DNI no especificado para eliminar.']);
                exit;
            }

            $sql = "DELETE FROM miembro WHERE dni = ?";
            $stmt = $pdo->prepare($sql);

            $stmt->execute([$dni]);
            if ($stmt->rowCount() > 0) {
                echo json_encode(['message' => 'Cliente eliminado con éxito.']);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Cliente no encontrado para eliminar.']);
            }
            break;

        default:
            http_response_code(405); // Method Not Allowed
            echo json_encode(['error' => 'Método no permitido.']);
            break;
    }
} catch (PDOException $e) {
    // Captura cualquier error de la base de datos (incluida la conexión)
    // y devuelve una respuesta JSON controlada.
    http_response_code(500);
    // En un entorno real, registraríamos el error en un log.
    // error_log("Error en clientes.php: " . $e->getMessage());
    echo json_encode(['error' => 'Error en la base de datos. Por favor, revise la conexión y la consulta.']);
}
?>
