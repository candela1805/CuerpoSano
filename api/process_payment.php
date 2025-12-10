<?php
// process_payment.php - 

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Solo permitir POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit();
}

// Incluir archivos necesarios
require_once '../db.php';

// Obtener datos del POST
$input = json_decode(file_get_contents('php://input'), true);

// Log de depuración (eliminar en producción)
error_log("Datos recibidos: " . print_r($input, true));

try {
    // ====================================
    // VALIDACIÓN DE DATOS
    // ====================================
    
    // Verificar que se recibieron datos
    if (!$input) {
        throw new Exception('No se recibieron datos válidos');
    }
    
    // Validar campos requeridos
    $requiredFields = ['idMiembro', 'monto', 'metodoPago'];
    foreach ($requiredFields as $field) {
        if (!isset($input[$field]) || $input[$field] === '' || $input[$field] === null) {
            throw new Exception("Campo requerido faltante: $field");
        }
    }
    
    // Validar y sanitizar datos
    $idMiembro = filter_var($input['idMiembro'], FILTER_VALIDATE_INT);
    if ($idMiembro === false || $idMiembro <= 0) {
        throw new Exception('ID de miembro inválido');
    }
    
    $monto = filter_var($input['monto'], FILTER_VALIDATE_FLOAT);
    if ($monto === false || $monto <= 0) {
        throw new Exception('Monto inválido');
    }
    
    $metodoPago = trim($input['metodoPago']);
    $metodosValidos = ['Efectivo', 'Tarjeta de Crédito', 'Tarjeta de Débito', 'Transferencia Bancaria', 'QR'];
    if (!in_array($metodoPago, $metodosValidos)) {
        throw new Exception('Método de pago no válido');
    }
    
    $referencia = isset($input['referencia']) ? trim($input['referencia']) : 'REF-' . time();
    $idMembresia = isset($input['idMembresia']) ? filter_var($input['idMembresia'], FILTER_VALIDATE_INT) : null;
    $idDescuento = isset($input['idDescuento']) ? filter_var($input['idDescuento'], FILTER_VALIDATE_INT) : null;
    
    // ====================================
    // VALIDAR MIEMBRO
    // ====================================
    
    $stmt = $pdo->prepare("SELECT * FROM miembro WHERE idMiembro = ?");
    $stmt->execute([$idMiembro]);
    $miembro = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$miembro) {
        throw new Exception('Miembro no encontrado');
    }
    
    if ($miembro['estado'] === 'suspendido') {
        throw new Exception('La membresía está suspendida. Contacte con administración.');
    }
    
    // ====================================
    // INICIAR TRANSACCIÓN
    // ====================================
    
    $pdo->beginTransaction();
    
    try {
        // Insertar pago
        $stmt = $pdo->prepare("
            INSERT INTO Pago (monto, fechaPago, metodoPago, referencia, idMiembro, estado)
            VALUES (?, CURDATE(), ?, ?, ?, 'completado')
        ");
        
        $stmt->execute([$monto, $metodoPago, $referencia, $idMiembro]);
        $idPago = $pdo->lastInsertId();
        
        if (!$idPago) {
            throw new Exception('Error al registrar el pago');
        }
        
        // ====================================
        // REGISTRAR DESCUENTO SI EXISTE
        // ====================================
        
        if ($idDescuento) {
            // Verificar que el descuento existe y está vigente
            $stmt = $pdo->prepare("
                SELECT * FROM descuento 
                WHERE idDescuento = ? AND activo = 1
            ");
            $stmt->execute([$idDescuento]);
            $descuento = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($descuento) {
                // Registrar relación miembro-descuento
                $stmt = $pdo->prepare("
                    INSERT INTO miembrodescuento (idMiembro, idDescuento, fechaAplicacion)
                    VALUES (?, ?, CURDATE())
                    ON DUPLICATE KEY UPDATE fechaAplicacion = CURDATE()
                ");
                $stmt->execute([$idMiembro, $idDescuento]);
            }
        }
        
        // ====================================
        // ACTUALIZAR MEMBRESÍA SI CORRESPONDE
        // ====================================
        
        if ($idMembresia) {
            // Obtener información del tipo de membresía
            $stmt = $pdo->prepare("SELECT duracionDias FROM tipomembresia WHERE idTipo = ?");
            $stmt->execute([$idMembresia]);
            $tipoMembresia = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($tipoMembresia) {
                // Verificar si tiene membresía activa
                $fechaActual = date('Y-m-d');
                
                if ($miembro['fechaFin'] >= $fechaActual && $miembro['estado'] === 'activo') {
                    // Extender membresía existente
                    $stmt = $pdo->prepare("
                        UPDATE miembro
                        SET fechaFin = DATE_ADD(fechaFin, INTERVAL ? DAY),
                            idTipoMembresia = ?
                        WHERE idMiembro = ?
                    ");
                    $stmt->execute([$tipoMembresia['duracionDias'], $idMembresia, $idMiembro]);
                } else {
                    // Crear nueva membresía
                    $stmt = $pdo->prepare("
                        UPDATE miembro
                        SET fechaInicio = CURDATE(),
                            fechaFin = DATE_ADD(CURDATE(), INTERVAL ? DAY),
                            estado = 'activo',
                            idTipoMembresia = ?
                        WHERE idMiembro = ?
                    ");
                    $stmt->execute([$tipoMembresia['duracionDias'], $idMembresia, $idMiembro]);
                }
            }
        }
        
        // ====================================
        // REGISTRAR EN LOG
        // ====================================
        
        $logContent = json_encode([
            'accion' => 'pago_procesado',
            'idPago' => $idPago,
            'idMiembro' => $idMiembro,
            'monto' => $monto,
            'metodoPago' => $metodoPago,
            'referencia' => $referencia,
            'timestamp' => date('Y-m-d H:i:s')
        ], JSON_UNESCAPED_UNICODE);
        
        $stmt = $pdo->prepare("
            INSERT INTO reporte (tipo, contenido, idEmpleado, fechaGeneracion)
            VALUES ('Pago', ?, 1, NOW())
        ");
        $stmt->execute([$logContent]);
        
        // ====================================
        // COMMIT
        // ====================================
        
        $pdo->commit();
        
        // ====================================
        // RESPUESTA EXITOSA
        // ====================================
        
        $response = [
            'success' => true,
            'message' => 'Pago procesado exitosamente',
            'data' => [
                'idPago' => $idPago,
                'idMiembro' => $idMiembro,
                'monto' => number_format($monto, 2, '.', ''),
                'fechaPago' => date('Y-m-d'),
                'metodoPago' => $metodoPago,
                'referencia' => $referencia,
                'estado' => 'completado'
            ]
        ];
        
        echo json_encode($response);
        
    } catch (Exception $e) {
        // Rollback en caso de error
        $pdo->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    // Asegurar rollback si la transacción está activa
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Log del error
    error_log("Error en process_payment.php: " . $e->getMessage());
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Error al procesar el pago: ' . $e->getMessage()
    ]);
}
?>
