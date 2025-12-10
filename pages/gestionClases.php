<?php
session_start();
if (!isset($_SESSION['idEmpleado'])) {
    header("Location: login.html");
    exit();
}
require_once '../db.php';

// Cargar actividades
try {
    $stmt = $pdo->query("SELECT idActividad, nombre FROM Actividad ORDER BY nombre");
    $actividades = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $actividades = [];
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Gestión de Clases - Cuerpo Sano</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
  <style>
    body{font-family:Inter,system-ui,Arial;background:#eefaf3;margin:0;padding:18px;color:#123}
    .wrap{display:flex;gap:20px;align-items:flex-start}
    .panel-form{width:360px;background:#fff;padding:16px;border-radius:10px;box-shadow:0 6px 18px rgba(0,0,0,0.06)}
    .panel-grid{flex:1;background:#fff;padding:16px;border-radius:10px;box-shadow:0 6px 18px rgba(0,0,0,0.06)}
    input,select,button{width:100%;padding:8px;margin:6px 0;border-radius:8px;border:1px solid #d6e9e2}
    button{cursor:pointer}
    table{width:100%;border-collapse:collapse;margin-top:8px}
    th,td{padding:8px;border-bottom:1px solid #eef7ef;text-align:left}
    .muted{color:#566; font-size:13px}
    .result-card{background:#f7fff9;padding:8px;border-radius:8px;border:1px solid #e6f7ee;margin-top:8px}
    .actions button{margin-right:6px;padding:6px 8px;border-radius:6px}
  </style>
</head>
<body>
  <div class="wrap">
    <!-- Panel de asignación -->
    <div class="panel-form">
      <h3>Asignar Miembro a Clase</h3>
      <div class="muted">Buscar miembro por DNI o nombre y asignarle una clase para una fecha.</div>

      <label for="buscarMiembro">Buscar Miembro (DNI o Nombre)</label>
      <input id="buscarMiembro" type="text" placeholder="Ej: 45633056 o Juan" autocomplete="off" oninput="buscarMiembroLive()">

      <div id="resultadoBusqueda"></div>

      <label for="actividad">Actividad</label>
      <select id="actividad">
        <?php foreach ($actividades as $a): ?>
          <option value="<?= htmlspecialchars($a['idActividad']) ?>"><?= htmlspecialchars($a['nombre']) ?></option>
        <?php endforeach; ?>
      </select>

      <label for="fechaClase">Fecha</label>
      <input id="fechaClase" type="date" value="<?= date('Y-m-d') ?>">

      <button onclick="asignarClase()">Asignar Clase</button>
    </div>

    <!-- Panel Asistencias -->
    <div class="panel-grid">
      <div style="display:flex;justify-content:space-between;align-items:center">
        <h3>Asistencias e Inscripciones</h3>
        <input id="buscarTabla" placeholder="Buscar por DNI, nombre o clase..." oninput="filtrarTabla()" style="width:320px">
      </div>

      <table>
        <thead>
          <tr>
            <th>Miembro</th>
            <th>Clase / Actividad</th>
            <th>Fecha Inscripción</th>
            <th>Estado</th>
            <th>Pago</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody id="tbodyClases">
          <tr><td colspan="6" class="muted">Cargando datos...</td></tr>
        </tbody>
      </table>
    </div>
  </div>

<script>
const el = id => document.getElementById(id);
let socios = [];      // cache from clientes_api
let inscripciones = []; // cache from clases_api

// Buscar miembros vía clientes_api.php (listar) y filtrar cliente coincidente
async function buscarMiembroLive(){
  const q = el('buscarMiembro').value.trim().toLowerCase();
  if(!q) { el('resultadoBusqueda').innerHTML = ''; return; }

  try {
    const res = await fetch('../api/clientes_api.php?accion=listar');
    const json = await res.json();
    if(!json.ok){ el('resultadoBusqueda').innerHTML = `<div class="muted">Error: ${json.msg}</div>`; return; }
    socios = json.data;
    const matches = socios.filter(s => 
      (s.dni && s.dni.toString().toLowerCase().includes(q)) ||
      ((s.nombre||'').toLowerCase().includes(q)) ||
      ((s.apellido||'').toLowerCase().includes(q)) ||
      ((s.email||'').toLowerCase().includes(q))
    );
    renderBusqueda(matches.slice(0,8));
  } catch(err){
    el('resultadoBusqueda').innerHTML = `<div class="muted">Error buscando miembros</div>`;
  }
}

function renderBusqueda(arr){
  const target = el('resultadoBusqueda');
  if(!arr.length){ target.innerHTML = '<div class="muted">Sin resultados</div>'; return; }
  target.innerHTML = arr.map(a => `
    <div class="result-card">
      <strong>${a.nombre} ${a.apellido}</strong><br>
      DNI: ${a.dni} • Membresía: ${a.tipo || '-'}<br>
      <div style="margin-top:6px">
        <button onclick="seleccionarMiembro('${a.dni}')">Seleccionar</button>
      </div>
    </div>
  `).join('');
}

function seleccionarMiembro(dni){
  el('buscarMiembro').value = dni;
  el('resultadoBusqueda').innerHTML = `<div class="muted">Miembro seleccionado: ${dni}</div>`;
}

// Asignar clase (llama a clases_api.php?action=asignar)
async function asignarClase(){
  const dni = el('buscarMiembro').value.trim();
  const actividad = el('actividad').value;
  const fecha = el('fechaClase').value;
  if(!dni || !actividad || !fecha){ alert('Complete DNI, actividad y fecha'); return; }

  const fd = new FormData();
  fd.append('accion','asignar');
  fd.append('dni',dni);
  fd.append('idActividad',actividad);
  fd.append('fecha',fecha);

  try {
    const res = await fetch('../api/clases_api.php', { method:'POST', body: fd });
    const json = await res.json();
    alert(json.msg);
    if(json.ok) cargarInscripciones();
  } catch(err){
    alert('Error al asignar clase');
  }
}

// Cargar inscripciones/asistencias desde clases_api
async function cargarInscripciones(){
  try {
    const res = await fetch('../api/clases_api.php?accion=listar');
    const json = await res.json();
    if(!json.ok){ el('tbodyClases').innerHTML = `<tr><td colspan="6">${json.msg}</td></tr>`; return; }
    inscripciones = json.data;
    renderTabla(inscripciones);
  } catch(err){
    el('tbodyClases').innerHTML = '<tr><td colspan="6">Error cargando inscripciones</td></tr>';
  }
}

function renderTabla(list){
  const tb = el('tbodyClases');
  tb.innerHTML = '';
  if(!list.length){ tb.innerHTML = '<tr><td colspan="6" class="muted">No hay inscripciones.</td></tr>'; return; }
  list.forEach(row => {
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td>${row.dni}<br><small>${row.nombre} ${row.apellido}</small></td>
      <td>${row.claseNombre || row.actividad}</td>
      <td>${row.fechaInscripcion}</td>
      <td>${row.estado || 'Inscripto'}</td>
      <td>${row.tienePago ? 'Sí' : 'No'}</td>
      <td class="actions">
        <button onclick="marcarAsistencia(${row.idInscripcion}, ${row.idMiembro})">Marcar Asistencia</button>
        <button onclick="registrarPago(${row.idMiembro})">Registrar Pago</button>
        <button onclick="eliminarInscripcion(${row.idInscripcion})" style="background:#ef4444;color:white">Eliminar</button>
      </td>
    `;
    tb.appendChild(tr);
  });
}

// Filtrado en la tabla (client-side)
function filtrarTabla(){
  const q = el('buscarTabla').value.trim().toLowerCase();
  if(!q){ renderTabla(inscripciones); return; }
  const filt = inscripciones.filter(r =>
    (r.dni && r.dni.toString().toLowerCase().includes(q)) ||
    (`${r.nombre} ${r.apellido}`.toLowerCase().includes(q)) ||
    ((r.claseNombre||r.actividad||'').toLowerCase().includes(q))
  );
  renderTabla(filt);
}

// Llamada para marcar asistencia
async function marcarAsistencia(idInscrip, idMiembro){
  if(!confirm('Confirmar marcar asistencia para este socio?')) return;
  const fd = new FormData(); fd.append('accion','marcarAsistencia'); fd.append('idInscripcion', idInscrip);
  try {
    const res = await fetch('../api/clases_api.php', { method:'POST', body:fd });
    const json = await res.json();
    alert(json.msg);
    if(json.ok) cargarInscripciones();
  } catch(e){ alert('Error al marcar asistencia'); }
}

// Registrar pago simple (monto fijo preguntado al user)
async function registrarPago(idMiembro){
  const monto = prompt('Ingrese monto de pago (ej: 100):', '0');
  if(monto === null) return;
  const fd = new FormData(); fd.append('accion','registrarPago'); fd.append('idMiembro', idMiembro); fd.append('monto', monto);
  try {
    const res = await fetch('../api/clases_api.php', { method:'POST', body:fd });
    const json = await res.json();
    alert(json.msg);
    if(json.ok) cargarInscripciones();
  } catch(e){ alert('Error al registrar pago'); }
}

// Eliminar inscripción
async function eliminarInscripcion(idInscripcion){
  if(!confirm('Eliminar inscripción?')) return;
  const fd = new FormData(); fd.append('accion','eliminarInscripcion'); fd.append('idInscripcion', idInscripcion);
  try {
    const res = await fetch('../api/clases_api.php', { method:'POST', body: fd });
    const json = await res.json();
    alert(json.msg);
    if(json.ok) cargarInscripciones();
  } catch(e){ alert('Error al eliminar inscripción'); }
}

// Inicializar
cargarInscripciones();
</script>
</body>
</html>


