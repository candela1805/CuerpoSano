<?php
header('Content-Type: application/json');
require_once '../db.php';

$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';

function respuesta($ok,$msg='',$data=null){
  echo json_encode(['ok'=>$ok,'msg'=>$msg,'data'=>$data]);
  exit;
}

switch($accion){
  case 'listar':
    try{
      $sql="SELECT p.dni,p.nombre,p.apellido,p.email,p.telefono,p.direccion,p.fechaNacimiento,
                   m.idTipoMembresia,tm.nombre AS tipo,m.foto, m.fechaInicio
            FROM persona p
            INNER JOIN miembro m ON p.dni=m.dni
            INNER JOIN tipomembresia tm ON m.idTipoMembresia=tm.idTipo";
      $res=$pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
      respuesta(true,'',$res);
    }catch(Exception $e){respuesta(false,$e->getMessage());}
    break;

  case 'buscarMiembro':
    try{
      $texto = $_GET['texto'] ?? '';
      $like = "%".$texto."%";
      $stmt = $pdo->prepare("SELECT p.dni, p.nombre, p.apellido, tm.nombre AS membresia
                               FROM persona p
                               INNER JOIN miembro m ON p.dni = m.dni
                               LEFT JOIN tipomembresia tm ON m.idTipoMembresia = tm.idTipo
                               WHERE p.dni LIKE ? OR p.nombre LIKE ? OR p.apellido LIKE ?
                               ORDER BY p.apellido, p.nombre
                               LIMIT 20");
      $stmt->execute([$like,$like,$like]);
      respuesta(true,'',$stmt->fetchAll(PDO::FETCH_ASSOC));
    }catch(Exception $e){respuesta(false,$e->getMessage());}
    break;

  case 'agregar':
    try{
      $pdo->beginTransaction();
      $dni=$_POST['dni'];
      $nombre=$_POST['nombre'];
      $apellido=$_POST['apellido'];
      $direccion=$_POST['direccion'];
      $telefono=$_POST['telefono'];
      $fecha=$_POST['fechaRegistro'] ?: null;
      $email=$_POST['email'];
      $tipo=$_POST['tipoMembresia'];
      $desc=$_POST['descuento'] ?: 0;
      // guardar foto
      $nombreFoto=null;
      if(!empty($_FILES['foto']['name'])){
        $ext=pathinfo($_FILES['foto']['name'],PATHINFO_EXTENSION);
        $nombreFoto="foto_".$dni.".".$ext;
        $dest = __DIR__ . '/../uploads/' . $nombreFoto;
        if (!is_dir(dirname($dest))) { @mkdir(dirname($dest), 0777, true); }
        move_uploaded_file($_FILES['foto']['tmp_name'],$dest);
      }

      $pdo->prepare("INSERT INTO persona VALUES (?,?,?,?,?,?,?)")
          ->execute([$dni,$nombre,$apellido,$direccion,$telefono,$fecha,$email]);

      $stmt=$pdo->prepare("SELECT idMembresia FROM membresia WHERE idTipo=? LIMIT 1");
      $stmt->execute([$tipo]);
      $idMemb=$stmt->fetchColumn();

      $pdo->prepare("INSERT INTO miembro (dni,foto,idTipoMembresia,idMembresia,fechaInicio,fechaFin,descuento)
                     VALUES (?,?,?,?,CURDATE(),DATE_ADD(CURDATE(),INTERVAL 30 DAY),?)")
          ->execute([$dni,$nombreFoto,$tipo,$idMemb,$desc]);

      $pdo->commit();
      respuesta(true,'Socio agregado correctamente.');
    }catch(Exception $e){
      $pdo->rollBack();
      respuesta(false,'Error al agregar: '.$e->getMessage());
    }
    break;

  case 'eliminar':
    try {
        $dni = $_POST['dni'] ?? '';
        if (!$dni) respuesta(false, 'Falta DNI.');

        // Inicia transacción
        $pdo->beginTransaction();

        // 1) Obtener idmiembro y nombre de archivo foto
        $stmt = $pdo->prepare("SELECT idMiembro, foto FROM miembro WHERE dni = ?");
        $stmt->execute([$dni]);
        $miembro = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$miembro) {
            // No existe -> nada que hacer
            $pdo->commit();
            respuesta(false, 'No se encontró el socio con DNI ' . $dni);
        }

        $idmiembro = $miembro['idMiembro'];
        $nombreFoto = $miembro['foto'];

        // 2) Eliminar registros dependientes EN EL ORDEN CORRECTO
        // 2.1) Si existe tabla descuentoinscripcion que referencia inscripcionclase,
        //      eliminar sus filas relacionadas con las inscripciones de este miembro
        $pdo->prepare("
            DELETE di FROM descuentoinscripcion di
            JOIN inscripcionclase ic ON di.idInscripcion = ic.idInscripcion
            WHERE ic.idmiembro = ?
        ")->execute([$idmiembro]);

        // 2.2) Eliminar descuentos de miembro (miembro-descuento)
        $pdo->prepare("DELETE FROM miembrodescuento WHERE idmiembro = ?")->execute([$idmiembro]);

        // 2.3) Eliminar inscripciones de clase del miembro
        $pdo->prepare("DELETE FROM inscripcionclase WHERE idmiembro = ?")->execute([$idmiembro]);

        // 2.4) Eliminar asistencias registradas del miembro
        $pdo->prepare("DELETE FROM asistencia WHERE idmiembro = ?")->execute([$idmiembro]);

        // 2.5) Eliminar pagos asociados al miembro
        $pdo->prepare("DELETE FROM pago WHERE idmiembro = ?")->execute([$idmiembro]);

        // 2.6) (Opcional) Si existe alguna otra tabla que referencie idMiembro, borrarla aquí:
        // $pdo->prepare("DELETE FROM OtraTabla WHERE idmiembro = ?")->execute([$idmiembro]);

        // 3) Eliminar la fila de Miembro
        $pdo->prepare("DELETE FROM miembro WHERE idmiembro = ?")->execute([$idmiembro]);

        // 4) Eliminar la fila de persona (usa DNI)
        $pdo->prepare("DELETE FROM persona WHERE dni = ?")->execute([$dni]);

        // 5) Borrar archivo de foto físico (si existe)
        if ($nombreFoto) {
            $path = __DIR__ . '/../uploads/' . $nombreFoto;
            if (file_exists($path)) {
                @unlink($path);
            }
        }

        $pdo->commit();
        respuesta(true, 'Socio eliminado correctamente (y dependencias borradas).');

    } catch (Exception $e) {
        $pdo->rollBack();
        respuesta(false, 'Error al eliminar: ' . $e->getMessage());
    }
    break;


    case 'actualizar':
        try{
        $pdo->beginTransaction();
        $dni = $_POST['dni'];
        $nombre = $_POST['nombre'];
        $apellido = $_POST['apellido'];
        $direccion = $_POST['direccion'];
        $telefono = $_POST['telefono'];
        $fecha = $_POST['fechaRegistro'] ?: null;
        $email = $_POST['email'];
        $tipo = $_POST['tipoMembresia'];
        $desc = $_POST['descuento'] ?: 0;

        // Manejo de nueva foto
        $nombreFoto = null;
        if(!empty($_FILES['foto']['name'])){
        $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $nombreFoto = "foto_".$dni.".".$ext;
        $dest2 = __DIR__ . '/../uploads/' . $nombreFoto;
        if (!is_dir(dirname($dest2))) { @mkdir(dirname($dest2), 0777, true); }
        move_uploaded_file($_FILES['foto']['tmp_name'], $dest2);
        // Actualiza el campo foto
        $pdo->prepare("UPDATE miembro SET foto=? WHERE dni=?")->execute([$nombreFoto, $dni]);
        }

        $pdo->prepare("UPDATE persona SET nombre=?,apellido=?,direccion=?,telefono=?,fechaNacimiento=?,email=? WHERE dni=?")
            ->execute([$nombre,$apellido,$direccion,$telefono,$fecha,$email,$dni]);

        $pdo->prepare("UPDATE miembro SET idTipoMembresia=?,descuento=? WHERE dni=?")
            ->execute([$tipo,$desc,$dni]);

        $pdo->commit();
        respuesta(true,'Socio actualizado correctamente.');
    }catch(Exception $e){
    $pdo->rollBack();
    respuesta(false,'Error al actualizar: '.$e->getMessage());
    }
    break;

  default:
    respuesta(false,'Acción inválida');
}
