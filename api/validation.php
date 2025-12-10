<?php
// validation.php
// Funciones de validación y utilidades para el sistema de pagos

/**
 * Valida datos de tarjeta de crédito/débito
 */
function validateCard($cardNumber, $expiryDate, $cvv) {
    $errors = [];
    
    // Validar número de tarjeta (Algoritmo de Luhn)
    $cardNumber = preg_replace('/\s+/', '', $cardNumber);
    
    if (!preg_match('/^\d{13,19}$/', $cardNumber)) {
        $errors[] = 'Número de tarjeta inválido';
    } else {
        if (!luhnCheck($cardNumber)) {
            $errors[] = 'Número de tarjeta no válido';
        }
    }
    
    // Validar fecha de expiración
    if (!preg_match('/^(0[1-9]|1[0-2])\/\d{2}$/', $expiryDate)) {
        $errors[] = 'Formato de fecha inválido (MM/AA)';
    } else {
        list($month, $year) = explode('/', $expiryDate);
        $year = '20' . $year;
        $expiryTimestamp = strtotime("$year-$month-01");
        
        if ($expiryTimestamp < time()) {
            $errors[] = 'Tarjeta expirada';
        }
    }
    
    // Validar CVV
    if (!preg_match('/^\d{3,4}$/', $cvv)) {
        $errors[] = 'CVV inválido';
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * Algoritmo de Luhn para validar números de tarjeta
 */
function luhnCheck($number) {
    $sum = 0;
    $numDigits = strlen($number);
    $parity = $numDigits % 2;
    
    for ($i = 0; $i < $numDigits; $i++) {
        $digit = (int)$number[$i];
        
        if ($i % 2 == $parity) {
            $digit *= 2;
            if ($digit > 9) {
                $digit -= 9;
            }
        }
        
        $sum += $digit;
    }
    
    return ($sum % 10) == 0;
}

/**
 * Detecta el tipo de tarjeta
 */
function detectCardType($cardNumber) {
    $cardNumber = preg_replace('/\s+/', '', $cardNumber);
    
    $patterns = [
        'visa' => '/^4[0-9]{12}(?:[0-9]{3})?$/',
        'mastercard' => '/^5[1-5][0-9]{14}$/',
        'amex' => '/^3[47][0-9]{13}$/',
        'discover' => '/^6(?:011|5[0-9]{2})[0-9]{12}$/',
        'diners' => '/^3(?:0[0-5]|[68][0-9])[0-9]{11}$/',
        'jcb' => '/^(?:2131|1800|35\d{3})\d{11}$/'
    ];
    
    foreach ($patterns as $type => $pattern) {
        if (preg_match($pattern, $cardNumber)) {
            return strtoupper($type);
        }
    }
    
    return 'UNKNOWN';
}

/**
 * Valida monto de pago
 */
function validateAmount($amount, $minAmount = 0, $maxAmount = 100000) {
    if (!is_numeric($amount)) {
        return [
            'valid' => false,
            'error' => 'Monto inválido'
        ];
    }
    
    $amount = floatval($amount);
    
    if ($amount <= $minAmount) {
        return [
            'valid' => false,
            'error' => "El monto debe ser mayor a $minAmount"
        ];
    }
    
    if ($amount > $maxAmount) {
        return [
            'valid' => false,
            'error' => "El monto no puede exceder $maxAmount"
        ];
    }
    
    return [
        'valid' => true,
        'amount' => $amount
    ];
}

/**
 * Valida referencia de pago
 */
function validateReference($reference) {
    // Permitir alfanuméricos, guiones y guiones bajos
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $reference)) {
        return [
            'valid' => false,
            'error' => 'Referencia inválida'
        ];
    }
    
    if (strlen($reference) < 5 || strlen($reference) > 50) {
        return [
            'valid' => false,
            'error' => 'La referencia debe tener entre 5 y 50 caracteres'
        ];
    }
    
    return [
        'valid' => true,
        'reference' => strtoupper($reference)
    ];
}

/**
 * Genera referencia única de pago
 */
function generatePaymentReference($prefix = 'PAY') {
    $timestamp = date('YmdHis');
    $random = strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));
    return "{$prefix}-{$timestamp}-{$random}";
}

/**
 * Calcula el total con descuento aplicado
 */
function calculateTotalWithDiscount($baseAmount, $discountPercentage) {
    if ($discountPercentage < 0 || $discountPercentage > 100) {
        return $baseAmount;
    }
    
    $discount = ($baseAmount * $discountPercentage) / 100;
    $total = $baseAmount - $discount;
    
    return [
        'baseAmount' => number_format($baseAmount, 2),
        'discountPercentage' => $discountPercentage,
        'discountAmount' => number_format($discount, 2),
        'total' => number_format($total, 2),
        'totalRaw' => $total
    ];
}

/**
 * Valida disponibilidad de descuento
 */
function validateDiscount($idDescuento, $pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM descuento
            WHERE idDescuento = ? AND activo = 1
        ");
        $stmt->execute([$idDescuento]);
        $discount = $stmt->fetch();
        
        if (!$discount) {
            return [
                'valid' => false,
                'error' => 'Descuento no disponible o expirado'
            ];
        }
        
        return [
            'valid' => true,
            'discount' => $discount
        ];
        
    } catch (Exception $e) {
        return [
            'valid' => false,
            'error' => 'Error al validar descuento'
        ];
    }
}

/**
 * Verifica si un miembro tiene pagos pendientes
 */
function checkPendingPayments($idMiembro, $pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as pendientes
            FROM Pago
            WHERE idMiembro = ? AND estado = 'pendiente'
        ");
        $stmt->execute([$idMiembro]);
        $result = $stmt->fetch();
        
        return [
            'hasPending' => $result['pendientes'] > 0,
            'count' => $result['pendientes']
        ];
        
    } catch (Exception $e) {
        return [
            'hasPending' => false,
            'count' => 0,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Registra log de transacción
 */
function logTransaction($type, $data, $pdo) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO Reporte (tipo, contenido, idEmpleado, fechaGeneracion)
            VALUES (?, ?, 1, NOW())
        ");
        
        $content = json_encode($data, JSON_UNESCAPED_UNICODE);
        $stmt->execute([$type, $content]);
        
        return [
            'success' => true,
            'logId' => $pdo->lastInsertId()
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Formatea datos de pago para respuesta
 */
function formatPaymentResponse($paymentData) {
    return [
        'idPago' => $paymentData['idPago'],
        'referencia' => $paymentData['referencia'],
        'monto' => number_format($paymentData['monto'], 2),
        'metodoPago' => $paymentData['metodoPago'],
        'fechaPago' => $paymentData['fechaPago'],
        'estado' => $paymentData['estado'],
        'timestamp' => time()
    ];
}

/**
 * Envía notificación de pago (email simulado)
 */
function sendPaymentNotification($memberEmail, $paymentData) {
    // En producción, implementar envío real de emails
    $subject = "Confirmación de Pago - CuerpoSano";
    $message = "
        Hola,
        
        Tu pago ha sido procesado exitosamente.
        
        Detalles:
        - Referencia: {$paymentData['referencia']}
        - Monto: \${$paymentData['monto']}
        - Método: {$paymentData['metodoPago']}
        - Fecha: {$paymentData['fechaPago']}
        
        Gracias por tu preferencia.
        
        CuerpoSano Gimnasio
    ";
    
    // Simulación
    return [
        'sent' => true,
        'to' => $memberEmail,
        'subject' => $subject
    ];
    
    // En producción usar:
    // return mail($memberEmail, $subject, $message);
}

/**
 * Valida método de pago
 */
function validatePaymentMethod($method) {
    $validMethods = [
        'Efectivo',
        'Tarjeta de Crédito',
        'Tarjeta de Débito',
        'Transferencia Bancaria',
        'QR'
    ];
    
    return in_array($method, $validMethods);
}

/**
 * Obtiene estadísticas de pagos
 */
function getPaymentStatistics($pdo, $startDate = null, $endDate = null) {
    try {
        $whereClause = '';
        $params = [];
        
        if ($startDate && $endDate) {
            $whereClause = 'WHERE fechaPago BETWEEN ? AND ?';
            $params = [$startDate, $endDate];
        } elseif ($startDate) {
            $whereClause = 'WHERE fechaPago >= ?';
            $params = [$startDate];
        }
        
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as totalPagos,
                SUM(monto) as totalIngresos,
                AVG(monto) as promedioMonto,
                MIN(monto) as montoMinimo,
                MAX(monto) as montoMaximo,
                metodoPago,
                COUNT(*) as cantidad
            FROM Pago
            $whereClause
            GROUP BY metodoPago
        ");
        $stmt->execute($params);
        $byMethod = $stmt->fetchAll();
        
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as totalPagos,
                SUM(monto) as totalIngresos,
                AVG(monto) as promedioMonto
            FROM Pago
            $whereClause
        ");
        $stmt->execute($params);
        $general = $stmt->fetch();
        
        return [
            'success' => true,
            'general' => $general,
            'byMethod' => $byMethod
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Verifica estado de membresía antes del pago
 */
function validateMembershipStatus($idMiembro, $pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                m.estado,
                m.fechaFin,
                DATEDIFF(m.fechaFin, CURDATE()) as diasRestantes,
                CONCAT(p.nombre, ' ', p.apellido) as nombre
            FROM Miembro m
            INNER JOIN Persona p ON m.dni = p.dni
            WHERE m.idMiembro = ?
        ");
        $stmt->execute([$idMiembro]);
        $member = $stmt->fetch();
        
        if (!$member) {
            return [
                'valid' => false,
                'error' => 'Miembro no encontrado'
            ];
        }
        
        $warnings = [];
        
        if ($member['estado'] === 'suspendido') {
            return [
                'valid' => false,
                'error' => 'Membresía suspendida. Contacte con administración.'
            ];
        }
        
        if ($member['diasRestantes'] < 0) {
            $warnings[] = 'La membresía está vencida';
        } elseif ($member['diasRestantes'] <= 7) {
            $warnings[] = "La membresía vence en {$member['diasRestantes']} días";
        }
        
        return [
            'valid' => true,
            'member' => $member,
            'warnings' => $warnings
        ];
        
    } catch (Exception $e) {
        return [
            'valid' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Procesa reembolso (función auxiliar)
 */
function processRefund($idPago, $monto, $motivo, $pdo) {
    try {
        $pdo->beginTransaction();
        
        // Verificar que el pago existe
        $stmt = $pdo->prepare("SELECT * FROM Pago WHERE idPago = ?");
        $stmt->execute([$idPago]);
        $pago = $stmt->fetch();
        
        if (!$pago) {
            throw new Exception('Pago no encontrado');
        }
        
        if ($monto > $pago['monto']) {
            throw new Exception('El monto del reembolso excede el pago original');
        }
        
        // Registrar reembolso
        $stmt = $pdo->prepare("
            INSERT INTO Pago (monto, fechaPago, metodoPago, referencia, idMiembro, estado)
            VALUES (?, CURDATE(), 'Reembolso', ?, ?, 'completado')
        ");
        $referencia = 'REFUND-' . $idPago . '-' . time();
        $stmt->execute([-$monto, $referencia, $pago['idMiembro']]);
        
        // Log
        logTransaction('Reembolso', [
            'idPagoOriginal' => $idPago,
            'monto' => $monto,
            'motivo' => $motivo,
            'referencia' => $referencia
        ], $pdo);
        
        $pdo->commit();
        
        return [
            'success' => true,
            'referencia' => $referencia,
            'message' => 'Reembolso procesado exitosamente'
        ];
        
    } catch (Exception $e) {
        $pdo->rollBack();
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}
?>
