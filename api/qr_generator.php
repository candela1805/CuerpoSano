<?php
// qr_generator.php

/**
 * Genera un código QR para pagos
 * En producción, usar una librería real como phpqrcode o chillerlan/php-qr-code
 * Esta es una versión simplificada para demostración
 */
function generateQRCode($data) {
    // Datos del QR
    $qrData = [
        'version' => '1.0',
        'tipo' => $data['tipo'] ?? 'pago',
        'timestamp' => time(),
        'payload' => $data
    ];
    
    // Codificar en base64 para transmisión
    $encodedData = base64_encode(json_encode($qrData));
    
    return [
        'encoded' => $encodedData,
        'url' => generateQRImageURL($encodedData),
        'raw' => $qrData
    ];
}

/**
 * Genera URL de imagen QR usando servicio externo
 * En producción, generar QR localmente con librería PHP
 */
function generateQRImageURL($data) {
    // Usar API de QR Code Generator (ejemplo)
    $size = '300x300';
    $encodedData = urlencode($data);
    
    // API pública de ejemplo (en producción usar tu propia solución)
    return "https://api.qrserver.com/v1/create-qr-code/?size={$size}&data={$encodedData}";
}

/**
 * Valida un código QR escaneado
 */
function validateQRCode($qrCode) {
    try {
        // Decodificar el QR
        $decoded = json_decode(base64_decode($qrCode), true);
        
        if (!$decoded) {
            return [
                'valid' => false,
                'message' => 'Código QR inválido'
            ];
        }
        
        // Verificar timestamp (QR válido por 15 minutos)
        $timestamp = $decoded['timestamp'] ?? 0;
        $currentTime = time();
        $maxAge = 15 * 60; // 15 minutos
        
        if (($currentTime - $timestamp) > $maxAge) {
            return [
                'valid' => false,
                'message' => 'Código QR expirado'
            ];
        }
        
        return [
            'valid' => true,
            'data' => $decoded['payload'],
            'message' => 'Código QR válido'
        ];
        
    } catch (Exception $e) {
        return [
            'valid' => false,
            'message' => 'Error al validar QR: ' . $e->getMessage()
        ];
    }
}

/**
 * Genera QR para pago de membresía específico
 */
function generateMembershipPaymentQR($idMiembro, $idMembresia, $monto, $descuento = 0) {
    require_once '../db.php';
    global $pdo;
    
    try {
        // Obtener información del miembro
        $stmt = $pdo->prepare("
            SELECT 
                m.idMiembro,
                CONCAT(p.nombre, ' ', p.apellido) as nombre,
                p.email,
                tm.nombre as membresia
            FROM Miembro m
            INNER JOIN Persona p ON m.dni = p.dni
            INNER JOIN TipoMembresia tm ON m.idTipoMembresia = tm.idTipo
            WHERE m.idMiembro = ?
        ");
        $stmt->execute([$idMiembro]);
        $member = $stmt->fetch();
        
        if (!$member) {
            throw new Exception('Miembro no encontrado');
        }
        
        // Calcular monto final
        $montoFinal = $monto - ($monto * $descuento / 100);
        
        // Generar referencia única
        $referencia = 'QR-' . date('Ymd') . '-' . $idMiembro . '-' . uniqid();
        
        // Datos del QR
        $qrData = [
            'tipo' => 'pago_membresia',
            'referencia' => $referencia,
            'idMiembro' => $idMiembro,
            'nombreMiembro' => $member['nombre'],
            'email' => $member['email'],
            'membresia' => $member['membresia'],
            'montoOriginal' => number_format($monto, 2),
            'descuento' => number_format($descuento, 2),
            'montoFinal' => number_format($montoFinal, 2),
            'moneda' => 'ARS',
            'fechaGeneracion' => date('Y-m-d H:i:s'),
            'validoHasta' => date('Y-m-d H:i:s', strtotime('+15 minutes'))
        ];
        
        return generateQRCode($qrData);
        
    } catch (Exception $e) {
        return [
            'error' => true,
            'message' => $e->getMessage()
        ];
    }
}

/**
 * Procesa un pago mediante QR escaneado
 */
function processQRPayment($qrCode) {
    $validation = validateQRCode($qrCode);
    
    if (!$validation['valid']) {
        return $validation;
    }
    
    $data = $validation['data'];
    
    // Aquí iría la lógica para procesar el pago
    // Conectar con process_payment.php o procesar directamente
    
    return [
        'success' => true,
        'message' => 'Pago procesado exitosamente',
        'data' => $data
    ];
}

/**
 * Genera QR para transferencia bancaria
 */
function generateBankTransferQR($monto, $concepto, $referencia) {
    $qrData = [
        'tipo' => 'transferencia_bancaria',
        'monto' => number_format($monto, 2),
        'moneda' => 'ARS',
        'concepto' => $concepto,
        'referencia' => $referencia,
        'cbu' => '0123456789012345678901', // CBU del gimnasio
        'alias' => 'CUERPOSANO.GYM',
        'titular' => 'CuerpoSano S.A.',
        'fechaGeneracion' => date('Y-m-d H:i:s')
    ];
    
    return generateQRCode($qrData);
}

/**
 * Endpoint para generar QR
 */
if (basename($_SERVER['PHP_SELF']) === 'qr_generator.php') {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    
    $action = $_GET['action'] ?? '';
    
    try {
        switch ($action) {
            case 'generate_payment':
                $idMiembro = $_GET['idMiembro'] ?? null;
                $idMembresia = $_GET['idMembresia'] ?? null;
                $monto = $_GET['monto'] ?? 0;
                $descuento = $_GET['descuento'] ?? 0;
                
                if (!$idMiembro || !$monto) {
                    throw new Exception('Datos insuficientes');
                }
                
                $qr = generateMembershipPaymentQR($idMiembro, $idMembresia, $monto, $descuento);
                
                echo json_encode([
                    'success' => true,
                    'qr' => $qr
                ]);
                break;
                
            case 'validate':
                $qrCode = $_POST['qrCode'] ?? $_GET['qrCode'] ?? null;
                
                if (!$qrCode) {
                    throw new Exception('Código QR requerido');
                }
                
                $validation = validateQRCode($qrCode);
                
                echo json_encode([
                    'success' => $validation['valid'],
                    'validation' => $validation
                ]);
                break;
                
            case 'generate_transfer':
                $monto = $_GET['monto'] ?? 0;
                $concepto = $_GET['concepto'] ?? 'Pago Membresía';
                $referencia = $_GET['referencia'] ?? 'REF-' . time();
                
                if (!$monto) {
                    throw new Exception('Monto requerido');
                }
                
                $qr = generateBankTransferQR($monto, $concepto, $referencia);
                
                echo json_encode([
                    'success' => true,
                    'qr' => $qr
                ]);
                break;
                
            default:
                throw new Exception('Acción no válida');
        }
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}
?>
