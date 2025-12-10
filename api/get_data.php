<?php
// get_data.php 

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../db.php';
session_start();

$action = isset($_GET['action']) ? $_GET['action'] : '';

try {
    switch ($action) {
        // ====================================
        // OBTENER MIEMBROS
        // ====================================
        case 'members':
            $stmt = $pdo->query("
                SELECT 
                    m.idMiembro,
                    m.dni,
                    CONCAT(p.nombre, ' ', p.apellido) as nombre,
                    p.email,
                    p.telefono,
                    m.estado,
                    m.fechaInicio,
                    m.fechaFin,
                    tm.nombre as tipoMembresia,
                    DATEDIFF(m.fechaFin, CURDATE()) as diasRestantes
                FROM miembro m
                INNER JOIN persona p ON m.dni = p.dni
                INNER JOIN tipomembresia tm ON m.idTipoMembresia = tm.idTipo
                WHERE m.estado IN ('activo', 'inactivo')
                ORDER BY p.nombre, p.apellido
            ");
            
            $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $members,
                'count' => count($members)
            ]);
            break;
        
        // ====================================
        // OBTENER TIPOS DE MEMBRESÍA
        // ====================================
        case 'membership_types':
        try {
        $stmt = $pdo->query("
            SELECT 
                idTipo,
                nombre,
                descripcion,
                precioBase,
                duracionDias
            FROM tipomembresia
            WHERE activo = TRUE
            ORDER BY precioBase ASC
        ");
        
        $types = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($types)) {
            echo json_encode([
                'success' => false,
                'message' => 'No hay tipos de membresía disponibles',
                'data' => [],
                'count' => 0
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'data' => $types,
                'count' => count($types)
            ]);
        }
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error al obtener tipos de membresía',
            'error' => $e->getMessage()
        ]);
    }
    break;
        
        // ====================================
        // OBTENER DESCUENTOS VIGENTES
        // ====================================
        case 'discounts':
            $stmt = $pdo->query("
                SELECT 
                    idDescuento,
                    descripcion,
                    porcentaje,
                    tipo,
                    vigenteDesde,
                    vigenteHasta
                FROM Descuentoinscripcion
                WHERE activo = TRUE
                AND CURDATE() BETWEEN vigenteDesde AND vigenteHasta
                ORDER BY porcentaje DESC
            ");
            
            $discounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $discounts,
                'count' => count($discounts)
            ]);
            break;
        
        // ====================================
        // HISTORIAL DE PAGOS
        // ====================================
        case 'payment_history':
            $idMiembro = isset($_GET['idMiembro']) ? filter_var($_GET['idMiembro'], FILTER_VALIDATE_INT) : null;
            
            $sql = "
                SELECT 
                    p.idPago,
                    p.monto,
                    p.fechaPago,
                    p.metodoPago,
                    p.referencia,
                    p.estado,
                    CONCAT(pe.nombre, ' ', pe.apellido) as miembro,
                    m.idMiembro
                FROM pago p
                INNER JOIN miembro m ON p.idMiembro = m.idMiembro
                INNER JOIN persona pe ON m.dni = pe.dni
            ";
            
            if ($idMiembro) {
                $sql .= " WHERE p.idMiembro = ?";
                $stmt = $pdo->prepare($sql . " ORDER BY p.fechaPago DESC LIMIT 100");
                $stmt->execute([$idMiembro]);
            } else {
                $stmt = $pdo->prepare($sql . " ORDER BY p.fechaPago DESC LIMIT 100");
                $stmt->execute();
            }
            
            $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $payments,
                'count' => count($payments)
            ]);
            break;
        
        // ====================================
        // ESTADÍSTICAS DE PAGOS
        // ====================================
        case 'payment_stats':
            $stmt = $pdo->query("
                SELECT 
                    COUNT(*) as totalPagos,
                    COALESCE(SUM(monto), 0) as totalIngresos,
                    COALESCE(AVG(monto), 0) as promedioMonto
                FROM pago
                WHERE MONTH(fechaPago) = MONTH(CURDATE())
                AND YEAR(fechaPago) = YEAR(CURDATE())
                AND estado = 'completado'
            ");
            
            $general = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $stmt = $pdo->query("
                SELECT 
                    metodoPago,
                    COUNT(*) as cantidad,
                    COALESCE(SUM(monto), 0) as total
                FROM pago
                WHERE MONTH(fechaPago) = MONTH(CURDATE())
                AND YEAR(fechaPago) = YEAR(CURDATE())
                AND estado = 'completado'
                GROUP BY metodoPago
                ORDER BY total DESC
            ");
            
            $porMetodo = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'general' => $general,
                    'porMetodo' => $porMetodo
                ]
            ]);
            break;
        
        // ====================================
        // VALIDAR MIEMBRO
        // ====================================
        case 'validate_member':
            $idMiembro = isset($_GET['idMiembro']) ? filter_var($_GET['idMiembro'], FILTER_VALIDATE_INT) : null;
            
            if (!$idMiembro) {
                throw new Exception('ID de miembro requerido');
            }
            
            $stmt = $pdo->prepare("
                SELECT 
                    m.idMiembro,
                    m.estado,
                    m.fechaFin,
                    DATEDIFF(m.fechaFin, CURDATE()) as diasRestantes,
                    CONCAT(p.nombre, ' ', p.apellido) as nombre,
                    p.email,
                    tm.nombre as tipoMembresia
                FROM miembro m
                INNER JOIN persona p ON m.dni = p.dni
                INNER JOIN tipomembresia tm ON m.idTipoMembresia = tm.idTipo
                WHERE m.idMiembro = ?
            ");
            
            $stmt->execute([$idMiembro]);
            $member = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$member) {
                throw new Exception('Miembro no encontrado');
            }
            
            $canPay = true;
            $messages = [];
            
            if ($member['estado'] === 'suspendido') {
                $canPay = false;
                $messages[] = 'La membresía está suspendida';
            }
            
            if ($member['diasRestantes'] < 0) {
                $messages[] = 'La membresía está vencida';
            } elseif ($member['diasRestantes'] <= 7) {
                $messages[] = "La membresía vence en {$member['diasRestantes']} días";
            }
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'canPay' => $canPay,
                    'member' => $member,
                    'messages' => $messages
                ]
            ]);
            break;
        
        // ====================================
        // OBTENER DETALLE DE UN PAGO
        // ====================================
        case 'payment_detail':
            $idPago = isset($_GET['idPago']) ? filter_var($_GET['idPago'], FILTER_VALIDATE_INT) : null;
            
            if (!$idPago) {
                throw new Exception('ID de pago requerido');
            }
            
            $stmt = $pdo->prepare("
                SELECT 
                    p.*,
                    CONCAT(pe.nombre, ' ', pe.apellido) as nombreMiembro,
                    pe.email,
                    tm.nombre as tipoMembresia
                FROM pago p
                INNER JOIN miembro m ON p.idMiembro = m.idMiembro
                INNER JOIN persona pe ON m.dni = pe.dni
                INNER JOIN tipomembresia tm ON m.idTipoMembresia = tm.idTipo
                WHERE p.idPago = ?
            ");
            
            $stmt->execute([$idPago]);
            $payment = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$payment) {
                throw new Exception('Pago no encontrado');
            }
            
            echo json_encode([
                'success' => true,
                'data' => $payment
            ]);
            break;
        
        default:
            throw new Exception('Acción no válida: ' . $action);
    }
    
} catch (PDOException $e) {
    error_log("Error de BD en get_data.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error de base de datos: ' . $e->getMessage()
    ]);
    
} catch (Exception $e) {
    error_log("Error en get_data.php: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>

