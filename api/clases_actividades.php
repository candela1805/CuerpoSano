<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../db.php';

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        // ACTIVIDADES
        case 'listar_actividades':
            listarActividades($pdo);
            break;
        case 'crear_actividad':
            crearActividad($pdo);
            break;
        case 'actualizar_actividad':
            actualizarActividad($pdo);
            break;
        case 'eliminar_actividad':
            eliminarActividad($pdo);
            break;

        // CLASES
        case 'listar_clases':
            listarClases($pdo);
            break;
        case 'crear_clase':
            crearClase($pdo);
            break;
        case 'actualizar_clase':
            actualizarClase($pdo);
            break;
        case 'eliminar_clase':
            eliminarClase($pdo);
            break;

        // ENTRENADORES
        case 'listar_entrenadores':
            listarEntrenadores($pdo);
            break;
        case 'crear_entrenador':
            crearEntrenador($pdo);
            break;
        case 'actualizar_entrenador':
            actualizarEntrenador($pdo);
            break;
        case 'eliminar_entrenador':
            eliminarEntrenador($pdo);
            break;

        // HORARIOS
        case 'listar_todos_horarios':
            listarTodosHorarios($pdo);
            break;

        default:
            echo json_encode([
                'success' => false,
                'message' => 'Acción no válida'
            ]);
            break;
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

// ============================================
// FUNCIONES ACTIVIDADES
// ============================================

function listarActividades($pdo) {
    $stmt = $pdo->query("SELECT * FROM Actividad ORDER BY idActividad DESC");
    $actividades = $stmt->fetchAll();
    echo json_encode([
        'success' => true,
        'data' => $actividades
    ]);
}

function crearActividad($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);
    $nombre = $data['nombre'] ?? '';
    $descripcion = $data['descripcion'] ?? '';
    $duracion = $data['duracion'] ?? 0;
    $activo = $data['activo'] ?? 1;

    if (empty($nombre) || empty($descripcion) || $duracion <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Datos incompletos o inválidos'
        ]);
        return;
    }

    $stmt = $pdo->prepare("INSERT INTO Actividad (nombre, descripcion, duracion, activo) VALUES (?, ?, ?, ?)");
    $stmt->execute([$nombre, $descripcion, $duracion, $activo]);

    echo json_encode([
        'success' => true,
        'message' => 'Actividad creada correctamente',
        'id' => $pdo->lastInsertId()
    ]);
}

function actualizarActividad($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);
    $idActividad = $data['idActividad'] ?? 0;
    $nombre = $data['nombre'] ?? '';
    $descripcion = $data['descripcion'] ?? '';
    $duracion = $data['duracion'] ?? 0;
    $activo = $data['activo'] ?? 1;

    if ($idActividad <= 0 || empty($nombre) || empty($descripcion) || $duracion <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Datos incompletos o inválidos'
        ]);
        return;
    }

    $stmt = $pdo->prepare("UPDATE Actividad SET nombre = ?, descripcion = ?, duracion = ?, activo = ? WHERE idActividad = ?");
    $stmt->execute([$nombre, $descripcion, $duracion, $activo, $idActividad]);

    echo json_encode([
        'success' => true,
        'message' => 'Actividad actualizada correctamente'
    ]);
}

function eliminarActividad($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);
    $idActividad = $data['idActividad'] ?? 0;

    if ($idActividad <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'ID de actividad inválido'
        ]);
        return;
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM Clase WHERE idActividad = ?");
    $stmt->execute([$idActividad]);
    $result = $stmt->fetch();

    if ($result['total'] > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'No se puede eliminar. La actividad está siendo usada en ' . $result['total'] . ' clase(s)'
        ]);
        return;
    }

    $stmt = $pdo->prepare("DELETE FROM Actividad WHERE idActividad = ?");
    $stmt->execute([$idActividad]);

    echo json_encode([
        'success' => true,
        'message' => 'Actividad eliminada correctamente'
    ]);
}

// ============================================
// FUNCIONES CLASES
// ============================================

function listarClases($pdo) {
    $query = "
        SELECT 
            c.*,
            a.nombre as actividad,
            e.nombre as entrenador,
            a.idActividad,
            e.idEntrenador
        FROM Clase c
        INNER JOIN Actividad a ON c.idActividad = a.idActividad
        INNER JOIN Entrenador e ON c.idEntrenador = e.idEntrenador
        ORDER BY c.idClase DESC
    ";
    $stmt = $pdo->query($query);
    $clases = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'data' => $clases
    ]);
}

function crearClase($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);
    $nombre = $data['nombre'] ?? '';
    $idActividad = $data['idActividad'] ?? 0;
    $idEntrenador = $data['idEntrenador'] ?? 0;
    $horaInicio = $data['horaInicio'] ?? '';
    $horaFin = $data['horaFin'] ?? '';
    $capacidad = $data['capacidad'] ?? 0;
    $activo = $data['activo'] ?? 1;

    if (empty($nombre) || $idActividad <= 0 || $idEntrenador <= 0 || $capacidad <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Datos incompletos o inválidos'
        ]);
        return;
    }

    // Validar rango horario
    if ($horaInicio < '07:00:00' || $horaInicio > '23:00:00') {
        echo json_encode([
            'success' => false,
            'message' => 'La hora de inicio debe estar entre las 07:00 y las 23:00'
        ]);
        return;
    }

    if ($horaFin > '23:00:00') {
        echo json_encode([
            'success' => false,
            'message' => 'La hora de fin no puede ser mayor a las 23:00'
        ]);
        return;
    }

    if ($horaFin <= $horaInicio) {
        echo json_encode([
            'success' => false,
            'message' => 'La hora de fin debe ser mayor que la hora de inicio'
        ]);
        return;
    }

    $stmt = $pdo->prepare("
        INSERT INTO Clase (nombre, horaInicio, horaFin, capacidad, idEntrenador, idActividad, activo)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$nombre, $horaInicio, $horaFin, $capacidad, $idEntrenador, $idActividad, $activo]);

    echo json_encode([
        'success' => true,
        'message' => 'Clase creada correctamente',
        'id' => $pdo->lastInsertId()
    ]);
}

function actualizarClase($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);
    $idClase = $data['idClase'] ?? 0;
    $nombre = $data['nombre'] ?? '';
    $idActividad = $data['idActividad'] ?? 0;
    $idEntrenador = $data['idEntrenador'] ?? 0;
    $horaInicio = $data['horaInicio'] ?? '';
    $horaFin = $data['horaFin'] ?? '';
    $capacidad = $data['capacidad'] ?? 0;
    $activo = $data['activo'] ?? 1;

    if ($idClase <= 0 || empty($nombre) || $idActividad <= 0 || $idEntrenador <= 0 || $capacidad <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Datos incompletos o inválidos'
        ]);
        return;
    }

    if ($horaInicio < '07:00:00' || $horaInicio > '23:00:00') {
        echo json_encode([
            'success' => false,
            'message' => 'La hora de inicio debe estar entre las 07:00 y las 23:00'
        ]);
        return;
    }

    if ($horaFin > '23:00:00') {
        echo json_encode([
            'success' => false,
            'message' => 'La hora de fin no puede ser mayor a las 23:00'
        ]);
        return;
    }

    if ($horaFin <= $horaInicio) {
        echo json_encode([
            'success' => false,
            'message' => 'La hora de fin debe ser mayor que la hora de inicio'
        ]);
        return;
    }

    $stmt = $pdo->prepare("
        UPDATE Clase 
        SET nombre = ?, horaInicio = ?, horaFin = ?, capacidad = ?, idEntrenador = ?, idActividad = ?, activo = ?
        WHERE idClase = ?
    ");
    $stmt->execute([$nombre, $horaInicio, $horaFin, $capacidad, $idEntrenador, $idActividad, $activo, $idClase]);

    echo json_encode([
        'success' => true,
        'message' => 'Clase actualizada correctamente'
    ]);
}

function eliminarClase($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);
    $idClase = $data['idClase'] ?? 0;

    if ($idClase <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'ID de clase inválido'
        ]);
        return;
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM InscripcionClase WHERE idClase = ? AND estado != 'cancelada'");
    $stmt->execute([$idClase]);
    $result = $stmt->fetch();

    if ($result['total'] > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'No se puede eliminar. Hay ' . $result['total'] . ' inscripción(es) activa(s)'
        ]);
        return;
    }

    $stmt = $pdo->prepare("DELETE FROM Clase WHERE idClase = ?");
    $stmt->execute([$idClase]);

    echo json_encode([
        'success' => true,
        'message' => 'Clase eliminada correctamente'
    ]);
}

// ============================================
// FUNCIONES ENTRENADORES
// ============================================

function listarEntrenadores($pdo) {
    $stmt = $pdo->query("SELECT * FROM Entrenador ORDER BY idEntrenador DESC");
    $entrenadores = $stmt->fetchAll();
    echo json_encode([
        'success' => true,
        'data' => $entrenadores
    ]);
}

function crearEntrenador($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);
    $nombre = $data['nombre'] ?? '';
    $especialidad = $data['especialidad'] ?? null;
    $certificacion = $data['certificacion'] ?? 0;
    $fechaCertificacion = $data['fechaCertificacion'] ?? null;
    $activo = $data['activo'] ?? 1;

    if (empty($nombre)) {
        echo json_encode([
            'success' => false,
            'message' => 'El nombre es obligatorio'
        ]);
        return;
    }

    // Validar año de certificación
    if ($fechaCertificacion) {
        $año = (int)date('Y', strtotime($fechaCertificacion));
        if ($año < 2000) {
            echo json_encode([
                'success' => false,
                'message' => 'El año de certificación debe ser mayor o igual a 2000'
            ]);
            return;
        }
    }

    $stmt = $pdo->prepare("
        INSERT INTO Entrenador (nombre, especialidad, certificacion, fechaCertificacion, activo)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$nombre, $especialidad, $certificacion, $fechaCertificacion, $activo]);

    echo json_encode([
        'success' => true,
        'message' => 'Entrenador creado correctamente',
        'id' => $pdo->lastInsertId()
    ]);
}

function actualizarEntrenador($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);
    $idEntrenador = $data['idEntrenador'] ?? 0;
    $nombre = $data['nombre'] ?? '';
    $especialidad = $data['especialidad'] ?? null;
    $certificacion = $data['certificacion'] ?? 0;
    $fechaCertificacion = $data['fechaCertificacion'] ?? null;
    $activo = $data['activo'] ?? 1;

    if ($idEntrenador <= 0 || empty($nombre)) {
        echo json_encode([
            'success' => false,
            'message' => 'Datos incompletos o inválidos'
        ]);
        return;
    }

    $stmt = $pdo->prepare("
        UPDATE Entrenador 
        SET nombre = ?, especialidad = ?, certificacion = ?, fechaCertificacion = ?, activo = ?
        WHERE idEntrenador = ?
    ");
    $stmt->execute([$nombre, $especialidad, $certificacion, $fechaCertificacion, $activo, $idEntrenador]);

    echo json_encode([
        'success' => true,
        'message' => 'Entrenador actualizado correctamente'
    ]);
}

function eliminarEntrenador($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);
    $idEntrenador = $data['idEntrenador'] ?? 0;

    if ($idEntrenador <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'ID de entrenador inválido'
        ]);
        return;
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM Clase WHERE idEntrenador = ?");
    $stmt->execute([$idEntrenador]);
    $result = $stmt->fetch();

    if ($result['total'] > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'No se puede eliminar. El entrenador tiene ' . $result['total'] . ' clase(s) asignada(s)'
        ]);
        return;
    }

    $stmt = $pdo->prepare("DELETE FROM Entrenador WHERE idEntrenador = ?");
    $stmt->execute([$idEntrenador]);

    echo json_encode([
        'success' => true,
        'message' => 'Entrenador eliminado correctamente'
    ]);
}

// ============================================
// FUNCIONES HORARIOS
// ============================================

function listarTodosHorarios($pdo) {
    $query = "
        SELECT 
            hc.idHorario,
            hc.diaSemana,
            hc.horaInicio,
            hc.horaFin,
            hc.estado,
            c.nombre as nombreClase,
            e.nombre as entrenador,
            a.nombre as actividad
        FROM HorarioClase hc
        INNER JOIN Clase c ON hc.idClase = c.idClase
        INNER JOIN Entrenador e ON c.idEntrenador = e.idEntrenador
        INNER JOIN Actividad a ON c.idActividad = a.idActividad
        WHERE hc.estado = 'activo' AND c.activo = 1
        ORDER BY 
            FIELD(hc.diaSemana, 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'),
            hc.horaInicio
    ";
    
    $stmt = $pdo->query($query);
    $horarios = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'data' => $horarios
    ]);
}
?>