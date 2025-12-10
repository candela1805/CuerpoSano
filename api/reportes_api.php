<?php
// reportes_api.php
// API de reportes unificada para Ingresos, Membresías, Asistencia e Inscripciones

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../db.php'; // expone $pdo (PDO MySQL)

function val_date($s) {
    return preg_match('/^\d{4}-\d{2}-\d{2}$/', $s) ? $s : null;
}

function range_or_default() {
    $from = isset($_GET['from']) ? val_date($_GET['from']) : null;
    $to   = isset($_GET['to'])   ? val_date($_GET['to'])   : null;

    if (!$from || !$to) {
        $to = date('Y-m-d');
        $from = date('Y-m-d', strtotime('-30 days'));
    }
    return [$from, $to];
}

function ok($data, $extra = []) {
    echo json_encode(array_merge(['success' => true, 'data' => $data], $extra));
    exit;
}

function fail($msg, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $msg]);
    exit;
}

try {
    $tipo = isset($_GET['tipo']) ? strtolower(trim($_GET['tipo'])) : '';
    if ($tipo === '') fail('Parámetro "tipo" requerido');

    [ $from, $to ] = range_or_default();
    $paramsInfo = [ 'tipo' => $tipo, 'from' => $from, 'to' => $to ];

    switch ($tipo) {
        // ==========================
        // INGRESOS (pagos)
        // ==========================
        case 'ingresos': {
            // Tabla
            $stmt = $pdo->prepare(
                "SELECT p.idPago, p.monto, p.fechaPago, p.metodoPago, p.referencia, p.estado,
                        CONCAT(pe.nombre, ' ', pe.apellido) AS miembro, m.idMiembro
                 FROM pago p
                 INNER JOIN miembro m ON p.idMiembro = m.idMiembro
                 INNER JOIN persona pe ON m.dni = pe.dni
                 WHERE p.estado = 'completado' AND p.fechaPago BETWEEN ? AND ?
                 ORDER BY p.fechaPago DESC, p.idPago DESC
                 LIMIT 1000"
            );
            $stmt->execute([$from, $to]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // General
            $stmt = $pdo->prepare(
                "SELECT COUNT(*) AS totalRegistros,
                        COALESCE(SUM(monto),0) AS totalMonto,
                        COALESCE(AVG(monto),0) AS promedio
                 FROM pago
                 WHERE estado = 'completado' AND fechaPago BETWEEN ? AND ?"
            );
            $stmt->execute([$from, $to]);
            $general = $stmt->fetch(PDO::FETCH_ASSOC);

            // Distribución por método
            $stmt = $pdo->prepare(
                "SELECT metodoPago AS label,
                        COUNT(*) AS count,
                        COALESCE(SUM(monto),0) AS total
                 FROM pago
                 WHERE estado = 'completado' AND fechaPago BETWEEN ? AND ?
                 GROUP BY metodoPago
                 ORDER BY total DESC"
            );
            $stmt->execute([$from, $to]);
            $dist = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Serie temporal diaria
            $stmt = $pdo->prepare(
                "SELECT fechaPago AS fecha, COALESCE(SUM(monto),0) AS total
                 FROM pago
                 WHERE estado = 'completado' AND fechaPago BETWEEN ? AND ?
                 GROUP BY fechaPago
                 ORDER BY fechaPago ASC"
            );
            $stmt->execute([$from, $to]);
            $serie = $stmt->fetchAll(PDO::FETCH_ASSOC);

            ok(['tabla' => $rows, 'general' => $general, 'distribucion' => $dist, 'temporal' => $serie], ['params' => $paramsInfo]);
        }

        // ==========================
        // MEMBRESÍAS
        // ==========================
        case 'membresias': {
            // Tabla: miembros y estado
            $stmt = $pdo->prepare(
                "SELECT m.idMiembro, m.estado, m.fechaInicio, m.fechaFin,
                        CONCAT(p.nombre, ' ', p.apellido) AS miembro,
                        tm.nombre AS tipoMembresia
                 FROM miembro m
                 INNER JOIN persona p ON m.dni = p.dni
                 INNER JOIN tipomembresia tm ON m.idTipoMembresia = tm.idTipo
                 WHERE (m.fechaInicio BETWEEN ? AND ?) OR (m.fechaFin BETWEEN ? AND ?)
                 ORDER BY m.fechaInicio DESC
                 LIMIT 1000"
            );
            $stmt->execute([$from, $to, $from, $to]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // General: conteos por estado y próximos a vencer (<= 7 días)
            $general = [];
            $qEstados = $pdo->query("SELECT estado, COUNT(*) AS c FROM miembro GROUP BY estado");
            foreach ($qEstados->fetchAll(PDO::FETCH_ASSOC) as $r) {
                $general['porEstado'][$r['estado']] = (int)$r['c'];
            }
            $qTotal = $pdo->query("SELECT COUNT(*) AS t FROM miembro");
            $general['totalMiembros'] = (int)$qTotal->fetch(PDO::FETCH_ASSOC)['t'];
            $qVencer = $pdo->prepare("SELECT COUNT(*) AS c FROM miembro WHERE DATEDIFF(fechaFin, CURDATE()) BETWEEN 0 AND 7");
            $qVencer->execute();
            $general['proximosAVencer'] = (int)$qVencer->fetch(PDO::FETCH_ASSOC)['c'];

            // Distribución por tipo de membresía (activos)
            $stmt = $pdo->prepare(
                "SELECT tm.nombre AS label, COUNT(*) AS count
                 FROM miembro m
                 INNER JOIN tipomembresia tm ON m.idTipoMembresia = tm.idTipo
                 WHERE m.estado = 'activo'
                 GROUP BY tm.nombre
                 ORDER BY count DESC"
            );
            $stmt->execute();
            $dist = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Serie temporal: altas por día (fechaInicio dentro del rango)
            $stmt = $pdo->prepare(
                "SELECT m.fechaInicio AS fecha, COUNT(*) AS total
                 FROM miembro m
                 WHERE m.fechaInicio BETWEEN ? AND ?
                 GROUP BY m.fechaInicio
                 ORDER BY m.fechaInicio ASC"
            );
            $stmt->execute([$from, $to]);
            $serie = $stmt->fetchAll(PDO::FETCH_ASSOC);

            ok(['tabla' => $rows, 'general' => $general, 'distribucion' => $dist, 'temporal' => $serie], ['params' => $paramsInfo]);
        }

        // ==========================
        // INSCRIPCIONES A CLASES
        // ==========================
        case 'inscripciones': {
            $stmt = $pdo->prepare(
                "SELECT ic.idInscripcion, ic.estado, ic.fechaInscripcion,
                        c.nombre AS clase,
                        CONCAT(pe.nombre, ' ', pe.apellido) AS miembro
                 FROM InscripcionClase ic
                 INNER JOIN miembro m ON ic.idMiembro = m.idMiembro
                 INNER JOIN persona pe ON m.dni = pe.dni
                 INNER JOIN clase c ON ic.idClase = c.idClase
                 WHERE ic.fechaInscripcion BETWEEN ? AND ?
                 ORDER BY ic.fechaInscripcion DESC, ic.idInscripcion DESC
                 LIMIT 1000"
            );
            $stmt->execute([$from, $to]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // General
            $stmt = $pdo->prepare(
                "SELECT COUNT(*) AS totalRegistros FROM InscripcionClase WHERE fechaInscripcion BETWEEN ? AND ?"
            );
            $stmt->execute([$from, $to]);
            $general = $stmt->fetch(PDO::FETCH_ASSOC);

            // Distribución por estado
            $stmt = $pdo->prepare(
                "SELECT estado AS label, COUNT(*) AS count
                 FROM InscripcionClase
                 WHERE fechaInscripcion BETWEEN ? AND ?
                 GROUP BY estado"
            );
            $stmt->execute([$from, $to]);
            $dist = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Serie temporal por día
            $stmt = $pdo->prepare(
                "SELECT fechaInscripcion AS fecha, COUNT(*) AS total
                 FROM InscripcionClase
                 WHERE fechaInscripcion BETWEEN ? AND ?
                 GROUP BY fechaInscripcion
                 ORDER BY fechaInscripcion ASC"
            );
            $stmt->execute([$from, $to]);
            $serie = $stmt->fetchAll(PDO::FETCH_ASSOC);

            ok(['tabla' => $rows, 'general' => $general, 'distribucion' => $dist, 'temporal' => $serie], ['params' => $paramsInfo]);
        }

        // ==========================
        // ASISTENCIA
        // ==========================
        case 'asistencia': {
            $stmt = $pdo->prepare(
                "SELECT a.idAsistencia, a.fecha, a.horaIngreso, a.horaEgreso,
                        CONCAT(pe.nombre, ' ', pe.apellido) AS miembro,
                        c.nombre AS clase
                 FROM Asistencia a
                 INNER JOIN miembro m ON a.idMiembro = m.idMiembro
                 INNER JOIN persona pe ON m.dni = pe.dni
                 LEFT JOIN clase c ON a.idClase = c.idClase
                 WHERE a.fecha BETWEEN ? AND ?
                 ORDER BY a.fecha DESC, a.idAsistencia DESC
                 LIMIT 1000"
            );
            $stmt->execute([$from, $to]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // General: total, con egreso registrado, sin egreso
            $stmt = $pdo->prepare(
                "SELECT
                    COUNT(*) AS total,
                    SUM(CASE WHEN a.horaEgreso IS NOT NULL THEN 1 ELSE 0 END) AS conEgreso,
                    SUM(CASE WHEN a.horaEgreso IS NULL THEN 1 ELSE 0 END) AS sinEgreso
                 FROM Asistencia a
                 WHERE a.fecha BETWEEN ? AND ?"
            );
            $stmt->execute([$from, $to]);
            $general = $stmt->fetch(PDO::FETCH_ASSOC);

            // Distribución por clase (si existe)
            $stmt = $pdo->prepare(
                "SELECT COALESCE(c.nombre, 'Sin clase') AS label, COUNT(*) AS count
                 FROM Asistencia a
                 LEFT JOIN clase c ON a.idClase = c.idClase
                 WHERE a.fecha BETWEEN ? AND ?
                 GROUP BY COALESCE(c.nombre, 'Sin clase')
                 ORDER BY count DESC"
            );
            $stmt->execute([$from, $to]);
            $dist = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Serie temporal: asistencias por día
            $stmt = $pdo->prepare(
                "SELECT a.fecha AS fecha, COUNT(*) AS total
                 FROM Asistencia a
                 WHERE a.fecha BETWEEN ? AND ?
                 GROUP BY a.fecha
                 ORDER BY a.fecha ASC"
            );
            $stmt->execute([$from, $to]);
            $serie = $stmt->fetchAll(PDO::FETCH_ASSOC);

            ok(['tabla' => $rows, 'general' => $general, 'distribucion' => $dist, 'temporal' => $serie], ['params' => $paramsInfo]);
        }

        default:
            fail('Tipo de reporte no soportado: ' . $tipo, 400);
    }
} catch (PDOException $e) {
    error_log('DB error en reportes_api.php: ' . $e->getMessage());
    fail('Error de base de datos: ' . $e->getMessage(), 500);
} catch (Exception $e) {
    error_log('Error en reportes_api.php: ' . $e->getMessage());
    fail($e->getMessage(), 400);
}



