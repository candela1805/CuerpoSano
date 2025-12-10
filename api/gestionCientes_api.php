<?php
header('Content-Type: application/json');
require_once '../db.php';
session_start();

$accion = $_GET['accion'] ?? $_POST['accion'] ?? '';

function responder($ok, $msg = '', $data = null) {
    echo json_encode(['ok' => $ok, 'msg' => $msg, 'data' => $data]);
    exit;
}

try {
    switch ($accion) {

        // === LISTAR CLIENTES ===
        case 'listar':
            $sql = "SELECT 
                        m.idMiembro,
                        p.dni,
                        p.nombre,
                        p.apellido,
                        p.email,
                        tm.nombre AS membresia,
                        m.foto,
                        m.fechaInicio
                    FROM miembro m
                    INNER JOIN persona p ON m.dni = p.dni
                    LEFT JOIN tipomembresia tm ON m.idTipoMembresia = tm.idTipo
                    ORDER BY p.apellido, p.nombre";
            $stmt = $pdo->query($sql);
            responder(true, 'OK', $stmt->fetchAll());
            break;

        // === AGREGAR CLIENTE ===
        case 'agregar':
            // Validación de datos básicos
            $dni = $_POST['dni'] ?? null;
            $nombre = $_POST['nombre'] ?? null;
            $apellido = $_POST['apellido'] ?? null;
            if (!$dni || !$nombre || !$apellido) {
                responder(false, 'DNI, Nombre y Apellido son obligatorios.');
            }

            $direccion = $_POST['direccion'] ?? null;
            $telefono = $_POST['telefono'] ?? null;
            $fechaNacimiento = $_POST['fechaNacimiento'] ?? null;
            $email = $_POST['email'] ?? null;
            $idTipoMembresia = $_POST['tipoMembresia'] ?? 1;
            $descuento = $_POST['descuento'] ?? 0;
            $fotoNombre = null;

            // === Subida de imagen segura ===
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['foto'];
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
                $maxSize = 5 * 1024 * 1024; // 5 MB

                $fileType = mime_content_type($file['tmp_name']);
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

                if (!in_array($fileType, $allowedTypes) || !in_array($ext, $allowedExtensions)) {
                    responder(false, 'Tipo de archivo no permitido. Solo se aceptan JPG, PNG, GIF.');
                }
                if ($file['size'] > $maxSize) {
                    responder(false, 'El archivo es demasiado grande. El máximo es 5MB.');
                }

                $fotoNombre = 'foto_' . preg_replace('/[^a-zA-Z0-9]/', '', $dni) . '_' . time() . '.' . $ext;
                $destino = __DIR__ . '/fotos/' . $fotoNombre;

                if (!move_uploaded_file($file['tmp_name'], $destino)) {
                    responder(false, 'Error al mover el archivo subido.');
                }
            }

            $pdo->beginTransaction();

            // 1. Guardar o actualizar persona
            $stmt = $pdo->prepare("INSERT INTO Persona (dni,nombre,apellido,direccion,telefono,fechaNacimiento,email)
                                   VALUES (?,?,?,?,?,?,?)
                                   ON DUPLICATE KEY UPDATE nombre=VALUES(nombre), apellido=VALUES(apellido),
                                   direccion=VALUES(direccion), telefono=VALUES(telefono),
                                   fechaNacimiento=VALUES(fechaNacimiento), email=VALUES(email)");
            $stmt->execute([$dni, $nombre, $apellido, $direccion, $telefono, $fechaNacimiento, $email]);
            
            // 2. Obtener datos del tipo de membresía (más eficiente)
            $stmt = $pdo->prepare("SELECT precioBase, duracionDias FROM TipoMembresia WHERE idTipo = ?");
            $stmt->execute([$idTipoMembresia]);
            $tipoMembresia = $stmt->fetch();
            if (!$tipoMembresia) {
                $pdo->rollBack();
                responder(false, 'El tipo de membresía seleccionado no es válido.');
            }

            // 3. Crear membresía
            $stmt = $pdo->prepare("INSERT INTO Membresia (idTipo, costo, duracion, fechaRegistro) VALUES (?, ?, ?, CURDATE())");
            $stmt->execute([$idTipoMembresia, $tipoMembresia['precioBase'], $tipoMembresia['duracionDias']]);
            $idMembresia = $pdo->lastInsertId();

            // 4. Crear miembro
            $stmt = $pdo->prepare("INSERT INTO Miembro (dni, idTipoMembresia, idMembresia, fechaInicio, fechaFin, descuento, foto)
                                   VALUES (?, ?, ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL ? DAY), ?, ?)");
            $stmt->execute([$dni, $idTipoMembresia, $idMembresia, $tipoMembresia['duracionDias'], $descuento, $fotoNombre]);

            $pdo->commit();
            responder(true, 'Cliente agregado correctamente');
            break;

        // === ELIMINAR CLIENTE ===
        case 'eliminar':
            $dni = $_POST['dni'] ?? null;
            if (!$dni) {
                responder(false, 'No se especificó el DNI del cliente a eliminar.');
            }

            // Eliminar foto si existe
            $stmt = $pdo->prepare("SELECT foto FROM Miembro WHERE dni=?");
            $stmt->execute([$dni]);
            $foto = $stmt->fetchColumn();
            if ($foto && file_exists(__DIR__ . '/fotos/' . $foto)) {
                unlink(__DIR__ . '/fotos/' . $foto);
            }

            $pdo->beginTransaction();
            // Se asume que la BD tiene ON DELETE CASCADE para tablas como InscripcionClase, Asistencia, Pago, etc.
            $pdo->prepare("DELETE FROM Miembro WHERE dni=?")->execute([$dni]);
            $pdo->prepare("DELETE FROM Persona WHERE dni=?")->execute([$dni]);
            $pdo->commit();

            responder(true, 'Cliente eliminado');
            break;

        default:
            responder(false, 'Acción no válida');
    }
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    responder(false, 'Error: ' . $e->getMessage());
}
?>
