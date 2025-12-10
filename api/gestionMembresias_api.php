<?php
// api/gestionMembresias_api.php

// Deshabilitar output de errores PHP para no romper JSON
ini_set('display_errors', 0);
error_reporting(0);

// Headers primero, antes de cualquier output
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');

// Incluir archivo de conexión
$db_path = __DIR__ . '/../db.php';

if (!file_exists($db_path)) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: No se encuentra el archivo db.php',
        'path' => $db_path
    ]);
    exit;
}

require_once $db_path;

if (!isset($pdo)) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: No se pudo establecer conexión con la base de datos'
    ]);
    exit;
}

$accion = '';
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $accion = $_GET['accion'] ?? '';
} else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
}

try {
    switch ($accion) {
        
        // ============================================
        // OBTENER MEMBRESÍAS
        // ============================================
        case 'obtener_membresias':
            $stmt = $pdo->query("SELECT * FROM TipoMembresia WHERE activo = TRUE ORDER BY idTipo");
            $membresias = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode([
                'success' => true,
                'membresias' => $membresias,
                'count' => count($membresias)
            ]);
            break;

        // ============================================
        // AGREGAR MEMBRESÍA
        // ============================================
        case 'agregar_membresia':
            $clave = $_POST['clave'] ?? '';
            if ($clave !== '1234') {
                echo json_encode(['success' => false, 'message' => 'Clave incorrecta']);
                exit;
            }

            $nombre = $_POST['nombre'] ?? '';
            $descripcion = $_POST['descripcion'] ?? '';
            $precio = $_POST['precio'] ?? 0;
            $duracion = $_POST['duracion'] ?? 0;

            // Validaciones
            if (empty($nombre) || empty($descripcion)) {
                echo json_encode(['success' => false, 'message' => 'Complete todos los campos']);
                exit;
            }

            if ($precio < 0 || $duracion <= 0) {
                echo json_encode(['success' => false, 'message' => 'Precio y duración deben ser válidos']);
                exit;
            }

            // Verificar si ya existe una membresía con ese nombre
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM TipoMembresia WHERE nombre = ? AND activo = TRUE");
            $stmt->execute([$nombre]);
            
            if ($stmt->fetchColumn() > 0) {
                echo json_encode(['success' => false, 'message' => 'Ya existe una membresía con ese nombre']);
                exit;
            }

            // Insertar nueva membresía
            $stmt = $pdo->prepare("INSERT INTO TipoMembresia (nombre, descripcion, precioBase, duracionDias, activo) 
                                   VALUES (?, ?, ?, ?, TRUE)");
            
            if ($stmt->execute([$nombre, $descripcion, $precio, $duracion])) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Membresía agregada correctamente',
                    'idTipo' => $pdo->lastInsertId()
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al agregar la membresía']);
            }
            break;

        // ============================================
        // ELIMINAR MEMBRESÍA (DESACTIVAR)
        // ============================================
        case 'eliminar_membresia':
            $clave = $_POST['clave'] ?? '';
            if ($clave !== '1234') {
                echo json_encode(['success' => false, 'message' => 'Clave incorrecta']);
                exit;
            }

            $idTipo = $_POST['idTipo'] ?? 0;

            if ($idTipo <= 0) {
                echo json_encode(['success' => false, 'message' => 'ID de membresía inválido']);
                exit;
            }

            // Verificar si hay miembros activos con esta membresía
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM Miembro 
                                   WHERE idTipoMembresia = ? AND estado = 'activo'");
            $stmt->execute([$idTipo]);
            
            $miembrosActivos = $stmt->fetchColumn();
            
            if ($miembrosActivos > 0) {
                echo json_encode([
                    'success' => false, 
                    'message' => "No se puede eliminar. Hay {$miembrosActivos} miembro(s) activo(s) con esta membresía"
                ]);
                exit;
            }

            // Desactivar membresía en lugar de eliminarla
            $stmt = $pdo->prepare("UPDATE TipoMembresia SET activo = FALSE WHERE idTipo = ?");
            
            if ($stmt->execute([$idTipo])) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Membresía eliminada correctamente'
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al eliminar la membresía']);
            }
            break;

        // ============================================
        // OBTENER MIEMBROS ACTIVOS
        // ============================================
        case 'obtener_miembros':
            $sql = "SELECT m.idMiembro, m.dni, CONCAT(p.nombre, ' ', p.apellido) AS nombreCompleto,
                    tm.nombre AS tipoMembresia, m.fechaInicio, m.fechaFin, m.estado,
                    tm.idTipo
                    FROM Miembro m
                    INNER JOIN Persona p ON m.dni = p.dni
                    INNER JOIN TipoMembresia tm ON m.idTipoMembresia = tm.idTipo
                    WHERE m.estado = 'activo' 
                    AND m.idTipoMembresia IN (2, 3)
                    ORDER BY p.nombre, p.apellido";
            
            $stmt = $pdo->query($sql);
            $miembros = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'miembros' => $miembros,
                'count' => count($miembros)
            ]);
            break;

        // ============================================
        // BUSCAR MIEMBRO POR DNI O NOMBRE
        // ============================================
        case 'buscar_miembro':
            $busqueda = $_POST['busqueda'] ?? '';
            
            if (empty($busqueda)) {
                echo json_encode(['success' => false, 'message' => 'Ingrese un DNI o nombre']);
                exit;
            }

            $sql = "SELECT m.idMiembro, m.dni, CONCAT(p.nombre, ' ', p.apellido) AS nombreCompleto,
                    tm.nombre AS tipoMembresia, m.fechaInicio, m.fechaFin, m.estado,
                    m.foto
                    FROM Miembro m
                    INNER JOIN Persona p ON m.dni = p.dni
                    INNER JOIN TipoMembresia tm ON m.idTipoMembresia = tm.idTipo
                    WHERE m.estado = 'activo' 
                    AND (p.dni LIKE ? OR CONCAT(p.nombre, ' ', p.apellido) LIKE ?)
                    LIMIT 10";
            
            $searchTerm = "%{$busqueda}%";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$searchTerm, $searchTerm]);
            $miembros = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'miembros' => $miembros,
                'count' => count($miembros)
            ]);
            break;

        // ============================================
        // OBTENER CLASES
        // ============================================
        case 'obtener_clases':
            $sql = "SELECT c.idClase, c.nombre, a.nombre AS actividad,
                    e.nombre AS entrenador, c.horaInicio, c.horaFin, c.capacidad,
                    (SELECT COUNT(*) FROM InscripcionClase ic
                     WHERE ic.idClase = c.idClase AND ic.estado IN ('confirmada', 'asistio')) AS inscritos
                    FROM Clase c
                    INNER JOIN Actividad a ON c.idActividad = a.idActividad
                    INNER JOIN Entrenador e ON c.idEntrenador = e.idEntrenador
                    WHERE c.activo = TRUE
                    ORDER BY c.nombre";
            
            $stmt = $pdo->query($sql);
            $clases = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'clases' => $clases,
                'count' => count($clases)
            ]);
            break;

        // ============================================
        // OBTENER CLASES INSCRITAS DE UN MIEMBRO
        // ============================================
        case 'obtener_clases_miembro':
            $idMiembro = $_POST['idMiembro'] ?? 0;
            
            if ($idMiembro <= 0) {
                echo json_encode(['success' => false, 'message' => 'Miembro inválido']);
                exit;
            }

            $sql = "SELECT ic.idInscripcion, ic.estado, c.idClase, c.nombre AS nombreClase,
                    a.nombre AS actividad, e.nombre AS entrenador,
                    c.horaInicio, c.horaFin,
                    (SELECT COUNT(*) FROM Asistencia a 
                     WHERE a.idMiembro = ic.idMiembro AND a.idClase = ic.idClase 
                     AND DATE(a.fecha) = CURDATE()) AS asistioHoy,
                    (SELECT MAX(a.fecha) FROM Asistencia a 
                     WHERE a.idMiembro = ic.idMiembro AND a.idClase = ic.idClase) AS ultimaAsistencia
                    FROM InscripcionClase ic
                    INNER JOIN Clase c ON ic.idClase = c.idClase
                    INNER JOIN Actividad a ON c.idActividad = a.idActividad
                    INNER JOIN Entrenador e ON c.idEntrenador = e.idEntrenador
                    WHERE ic.idMiembro = ? 
                    AND ic.estado IN ('confirmada', 'asistio')
                    AND c.activo = TRUE
                    ORDER BY c.nombre";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$idMiembro]);
            $clases = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'clases' => $clases,
                'count' => count($clases)
            ]);
            break;

        // ============================================
        // ACTUALIZAR PRECIO DE MEMBRESÍA
        // ============================================
        case 'actualizar_precio':
            $clave = $_POST['clave'] ?? '';
            if ($clave !== '1234') {
                echo json_encode(['success' => false, 'message' => 'Clave incorrecta']);
                exit;
            }

            $idTipo = $_POST['idTipo'] ?? 0;
            $nuevoPrecio = $_POST['nuevoPrecio'] ?? 0;

            if ($idTipo <= 0 || $nuevoPrecio < 0) {
                echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
                exit;
            }

            $stmt = $pdo->prepare("UPDATE TipoMembresia SET precioBase = ? WHERE idTipo = ?");
            if ($stmt->execute([$nuevoPrecio, $idTipo])) {
                echo json_encode(['success' => true, 'message' => 'Precio actualizado correctamente']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al actualizar el precio']);
            }
            break;

        // ============================================
        // INSCRIBIR MIEMBRO A CLASE
        // ============================================
        case 'inscribir_clase':
            $idMiembro = $_POST['idMiembro'] ?? 0;
            $idClase = $_POST['idClase'] ?? 0;

            if ($idMiembro <= 0 || $idClase <= 0) {
                echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
                exit;
            }

            $fechaInscripcion = date('Y-m-d');

            // Verificar si ya está inscrito
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM InscripcionClase
                                   WHERE idMiembro = ? AND idClase = ?
                                   AND estado IN ('confirmada', 'pendiente')");
            $stmt->execute([$idMiembro, $idClase]);
            
            if ($stmt->fetchColumn() > 0) {
                echo json_encode(['success' => false, 'message' => 'El miembro ya está inscrito en esta clase']);
                exit;
            }

            // Verificar capacidad
            $stmt = $pdo->prepare("SELECT c.capacidad,
                                   (SELECT COUNT(*) FROM InscripcionClase ic
                                    WHERE ic.idClase = c.idClase AND ic.estado IN ('confirmada', 'asistio')) AS inscritos
                                   FROM Clase c WHERE c.idClase = ?");
            $stmt->execute([$idClase]);
            $clase = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($clase['inscritos'] >= $clase['capacidad']) {
                echo json_encode(['success' => false, 'message' => 'La clase está llena']);
                exit;
            }

            // Inscribir
            $stmt = $pdo->prepare("INSERT INTO InscripcionClase (idMiembro, idClase, fechaInscripcion, estado)
                                   VALUES (?, ?, ?, 'confirmada')");
            
            if ($stmt->execute([$idMiembro, $idClase, $fechaInscripcion])) {
                echo json_encode(['success' => true, 'message' => 'Inscripción realizada correctamente']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al inscribir']);
            }
            break;

        // ============================================
        // REGISTRAR ASISTENCIA
        // ============================================
        case 'registrar_asistencia':
            $idMiembro = $_POST['idMiembro'] ?? 0;
            $idClase = $_POST['idClase'] ?? 0;

            if ($idMiembro <= 0 || $idClase <= 0) {
                echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
                exit;
            }

            $fecha = date('Y-m-d');
            $horaIngreso = date('H:i:s');

            // Verificar si ya registró asistencia hoy
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM Asistencia
                                   WHERE idMiembro = ? AND idClase = ? AND DATE(fecha) = ?");
            $stmt->execute([$idMiembro, $idClase, $fecha]);
            
            if ($stmt->fetchColumn() > 0) {
                echo json_encode(['success' => false, 'message' => 'La asistencia ya fue registrada hoy']);
                exit;
            }

            // Verificar que el miembro esté inscrito en la clase
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM InscripcionClase
                                   WHERE idMiembro = ? AND idClase = ? 
                                   AND estado IN ('confirmada', 'asistio')");
            $stmt->execute([$idMiembro, $idClase]);
            
            if ($stmt->fetchColumn() == 0) {
                echo json_encode(['success' => false, 'message' => 'El miembro no está inscrito en esta clase']);
                exit;
            }

            // Registrar asistencia
            $stmt = $pdo->prepare("INSERT INTO Asistencia (fecha, horaIngreso, idMiembro, idClase)
                                   VALUES (?, ?, ?, ?)");
            $stmt->execute([$fecha, $horaIngreso, $idMiembro, $idClase]);

            // Actualizar estado de inscripción
            $stmt = $pdo->prepare("UPDATE InscripcionClase
                                   SET estado = 'asistio'
                                   WHERE idMiembro = ? AND idClase = ? AND estado = 'confirmada'");
            $stmt->execute([$idMiembro, $idClase]);

            echo json_encode(['success' => true, 'message' => 'Asistencia registrada correctamente']);
            break;

        // ============================================
        // TEST DE CONEXIÓN
        // ============================================
        case 'test_conexion':
            $stmt = $pdo->query("SELECT DATABASE() as db_name");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'message' => 'Conexión exitosa',
                'database' => $result['db_name']
            ]);
            break;

        // ============================================
        // ACCIÓN NO VÁLIDA O VACÍA
        // ============================================
        case '':
            echo json_encode([
                'success' => false,
                'message' => 'No se especificó ninguna acción',
                'available_actions' => [
                    'obtener_membresias',
                    'agregar_membresia',
                    'eliminar_membresia',
                    'obtener_miembros',
                    'buscar_miembro',
                    'obtener_clases',
                    'obtener_clases_miembro',
                    'actualizar_precio',
                    'inscribir_clase',
                    'registrar_asistencia',
                    'test_conexion'
                ]
            ]);
            break;

        default:
            echo json_encode([
                'success' => false,
                'message' => 'Acción no válida: ' . $accion
            ]);
    }

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error de base de datos',
        'error' => $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error general',
        'error' => $e->getMessage()
    ]);
}
?>