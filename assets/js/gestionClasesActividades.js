// Gestion de Clases y Actividades - JS limpio y conectado a API
// Base de API segun ubicacion de la pagina
const API_BASE = window.location.pathname.includes('/pages/') ? '../api' : 'api';

// Estado en memoria
let actividadesCache = [];
let clasesCache = [];
let entrenadoresCache = [];

// Helpers DOM
const $ = (s) => document.querySelector(s);
const $$ = (s) => Array.from(document.querySelectorAll(s));

async function apiJson(action, options = {}, params = null) { let qs = ction=; if (params && typeof params === 'object') { for (const [k,v] of Object.entries(params)) { qs += &=; } } const url = ${API_BASE}/clases_actividades.php?; const res = await fetch(url, options); const data = await res.json().catch(() => ({ success: false, message: 'Respuesta invalida' })); if (!res.ok || !data.success) throw new Error(data.message || HTTP ); return data.data || data; }
}

// Carga inicial
document.addEventListener('DOMContentLoaded', () => {
  cargarTodo();
  setupEventListeners();
});

function setupEventListeners() {
  const fa = $('#form-actividad');
  const fc = $('#form-clase');
  const fe = $('#form-entrenador');
  if (fa) fa.addEventListener('submit', guardarActividad);
  if (fc) fc.addEventListener('submit', guardarClase);
  if (fe) fe.addEventListener('submit', guardarEntrenador);

  const sa = $('#search-actividades');
  const sc = $('#search-clases');
  const se = $('#search-entrenadores');
  if (sa) sa.addEventListener('input', (e) => renderActividades(e.target.value));
  if (sc) sc.addEventListener('input', (e) => renderClases(e.target.value));
  if (se) se.addEventListener('input', (e) => renderEntrenadores(e.target.value));
}

async function cargarTodo() {
  try {
    await Promise.all([cargarActividades(), cargarClases(), cargarEntrenadores()]);
    cargarSelectores();
  } catch (e) {
    alert('Error al cargar datos: ' + e.message);
  }
}

async function cargarActividades() {
  const d = await apiJson('listar_actividades');
  actividadesCache = d;
  renderActividades('');
}

async function cargarClases() {
  const d = await apiJson('listar_clases');
  clasesCache = d;
  renderClases('');
}

async function cargarEntrenadores() {
  const d = await apiJson('listar_entrenadores');
  entrenadoresCache = d;
  renderEntrenadores('');
}

// Renders
function renderActividades(filtro) {
  const tb = $('#tabla-actividades tbody');
  if (!tb) return;
  const list = (filtro ? actividadesCache.filter(a => (a.nombre||'').toLowerCase().includes(filtro.toLowerCase()) || (a.descripcion||'').toLowerCase().includes(filtro.toLowerCase())) : actividadesCache);
  if (!list.length) { tb.innerHTML = '<tr><td colspan="6" class="empty-state">No hay actividades registradas</td></tr>'; return; }
  tb.innerHTML = list.map(a => `
    <tr>
      <td>${a.idActividad}</td>
      <td><strong>${a.nombre}</strong></td>
      <td>${a.descripcion||''}</td>
      <td>${a.duracion||0} min</td>
      <td><span class="badge ${a.activo==1?'badge-success':'badge-danger'}">${a.activo==1?'Activa':'Inactiva'}</span></td>
      <td class="action-buttons">
        <button class="btn-sm btn-edit" onclick="editarActividad(${a.idActividad})">Editar</button>
        <button class="btn-sm btn-delete" onclick="eliminarActividad(${a.idActividad})">Eliminar</button>
      </td>
    </tr>`).join('');
}

function renderClases(filtro) {
  const tb = $('#tabla-clases tbody');
  if (!tb) return;
  const list = (filtro ? clasesCache.filter(c => (c.nombre||'').toLowerCase().includes(filtro.toLowerCase())) : clasesCache);
  if (!list.length) { tb.innerHTML = '<tr><td colspan="7" class="empty-state">No hay clases registradas</td></tr>'; return; }
  tb.innerHTML = list.map(c => `
    <tr>
      <td>${c.idClase}</td>
      <td><strong>${c.nombre}</strong></td>
      <td>${c.actividad||''}</td>
      <td>${c.entrenador||''}</td>
      <td>${c.horaInicio||''} - ${c.horaFin||''}</td>
      <td><span class="badge ${c.activo==1?'badge-success':'badge-danger'}">${c.activo==1?'Activa':'Inactiva'}</span></td>
      <td class="action-buttons">
        <button class="btn-sm btn-edit" onclick="editarClase(${c.idClase})">Editar</button>
        <button class="btn-sm btn-delete" onclick="eliminarClase(${c.idClase})">Eliminar</button>
      </td>
    </tr>`).join('');
}

function renderEntrenadores(filtro) {
  const tb = $('#tabla-entrenadores tbody');
  if (!tb) return;
  const list = (filtro ? entrenadoresCache.filter(e => (e.nombre||'').toLowerCase().includes(filtro.toLowerCase())) : entrenadoresCache);
  if (!list.length) { tb.innerHTML = '<tr><td colspan="6" class="empty-state">No hay entrenadores</td></tr>'; return; }
  tb.innerHTML = list.map(e => `
    <tr>
      <td>${e.idEntrenador}</td>
      <td><strong>${e.nombre}</strong></td>
      <td>${e.especialidad||''}</td>
      <td><span class="badge ${e.certificacion==1?'badge-success':'badge-warning'}">${e.certificacion==1?'Certificado':'Sin cert.'}</span></td>
      <td><span class="badge ${e.activo==1?'badge-success':'badge-danger'}">${e.activo==1?'Activo':'Inactivo'}</span></td>
      <td class="action-buttons">
        <button class="btn-sm btn-edit" onclick="editarEntrenador(${e.idEntrenador})">Editar</button>
        <button class="btn-sm btn-delete" onclick="eliminarEntrenador(${e.idEntrenador})">Eliminar</button>
      </td>
    </tr>`).join('');
}

// Actividades
async function guardarActividad(ev) {
  ev.preventDefault();
  const datos = {
    nombre: $('#act-nombre')?.value.trim(),
    descripcion: $('#act-descripcion')?.value.trim(),
    duracion: parseInt($('#act-duracion')?.value || '0', 10),
    activo: $('#act-activo')?.checked ? 1 : 0
  };
  const id = $('#act-id')?.value;
  const action = id ? 'actualizar_actividad' : 'crear_actividad';
  if (id) datos.idActividad = id;
  await apiJson(action, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(datos) });
  limpiarFormActividad();
  await cargarActividades();
  cargarSelectores();
}
function editarActividad(id) {
  const a = actividadesCache.find(x => x.idActividad == id); if (!a) return;
  $('#act-id').value = a.idActividad; $('#act-nombre').value = a.nombre || '';
  $('#act-descripcion').value = a.descripcion || ''; $('#act-duracion').value = a.duracion || 0;
  $('#act-activo').checked = a.activo == 1; const t = document.getElementById('form-actividad-title'); if (t) t.textContent='Editar Actividad';
}
async function eliminarActividad(id) {
  if (!confirm('Eliminar actividad?')) return;
  await apiJson('eliminar_actividad', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ idActividad: id }) });
  await cargarActividades(); cargarSelectores();
}
function limpiarFormActividad() { const f = $('#form-actividad'); if (f) f.reset(); if($('#act-id')) $('#act-id').value=''; if($('#form-actividad-title')) $('#form-actividad-title').textContent='Nueva Actividad'; }

// Clases
async function guardarClase(ev) {
  ev.preventDefault();
  const datos = {
    nombre: $('#clase-nombre')?.value.trim(),
    idActividad: $('#clase-actividad')?.value,
    idEntrenador: $('#clase-entrenador')?.value,
    horaInicio: $('#clase-hora-inicio')?.value,
    horaFin: $('#clase-hora-fin')?.value,
    capacidad: parseInt($('#clase-capacidad')?.value || '0', 10),
    activo: $('#clase-activo')?.checked ? 1 : 0
  };
  const id = $('#clase-id')?.value; const action = id ? 'actualizar_clase' : 'crear_clase'; if (id) datos.idClase = id;
  await apiJson(action, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(datos)});
  limpiarFormClase(); await cargarClases();
}
function editarClase(id) { const c = clasesCache.find(x=>x.idClase==id); if(!c) return; $('#clase-id').value=c.idClase; $('#clase-nombre').value=c.nombre||''; $('#clase-actividad').value=c.idActividad; $('#clase-entrenador').value=c.idEntrenador; $('#clase-hora-inicio').value=c.horaInicio||''; $('#clase-hora-fin').value=c.horaFin||''; $('#clase-capacidad').value=c.capacidad||0; $('#clase-activo').checked=c.activo==1; const t=document.getElementById('form-clase-title'); if(t) t.textContent='Editar Clase'; }
async function eliminarClase(id) { if(!confirm('Eliminar clase?')) return; await apiJson('eliminar_clase',{method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({idClase:id})}); await cargarClases(); }
function limpiarFormClase(){ const f=$('#form-clase'); if(f) f.reset(); if($('#clase-id')) $('#clase-id').value=''; if($('#form-clase-title')) $('#form-clase-title').textContent='Nueva Clase'; if($('#clase-activo')) $('#clase-activo').checked=true; }

// Entrenadores
async function guardarEntrenador(ev){ ev.preventDefault(); const datos={ nombre:$('#ent-nombre')?.value.trim(), especialidad:$('#ent-especialidad')?.value.trim(), fechaCertificacion:$('#ent-fecha-cert')?.value||null, certificacion: $('#ent-certificado')?.checked?1:0, activo: $('#ent-activo')?.checked?1:0}; const id=$('#ent-id')?.value; const action=id?'actualizar_entrenador':'crear_entrenador'; if(id) datos.idEntrenador=id; await apiJson(action,{method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(datos)}); limpiarFormEntrenador(); await cargarEntrenadores(); cargarSelectores(); }
function editarEntrenador(id){ const e = entrenadoresCache.find(x=>x.idEntrenador==id); if(!e) return; $('#ent-id').value=e.idEntrenador; $('#ent-nombre').value=e.nombre||''; $('#ent-especialidad').value=e.especialidad||''; $('#ent-fecha-cert').value=e.fechaCertificacion||''; $('#ent-certificado').checked=e.certificacion==1; $('#ent-activo').checked=e.activo==1; const t=document.getElementById('form-entrenador-title'); if(t) t.textContent='Editar Entrenador'; }
async function eliminarEntrenador(id){ if(!confirm('Eliminar entrenador?')) return; await apiJson('eliminar_entrenador',{method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({idEntrenador:id})}); await cargarEntrenadores(); cargarSelectores(); }
function limpiarFormEntrenador(){ const f=$('#form-entrenador'); if(f) f.reset(); if($('#ent-id')) $('#ent-id').value=''; if($('#ent-activo')) $('#ent-activo').checked=true; if($('#form-entrenador-title')) $('#form-entrenador-title').textContent='Nuevo Entrenador'; }

// Selectores auxiliares
function cargarSelectores(){ const sa=$('#clase-actividad'); if(sa){ sa.innerHTML='<option value="">Seleccione actividad...</option>'+ actividadesCache.filter(a=>a.activo==1).map(a=>`<option value="${a.idActividad}">${a.nombre}</option>`).join(''); } const se=$('#clase-entrenador'); if(se){ se.innerHTML='<option value="">Seleccione entrenador...</option>'+ entrenadoresCache.filter(e=>e.activo==1).map(e=>`<option value="${e.idEntrenador}">${e.nombre}${e.especialidad?(' - '+e.especialidad):''}</option>`).join(''); } }

// Horarios
async function verHorarios(idClase){ const d = await apiJson(`listar_horarios&idClase=${idClase}`); const cont = document.getElementById('modal-horarios-contenido'); if(!cont){ return;} if(Array.isArray(d) && d.length){ cont.innerHTML = d.map(h=>`<div class="horario-item"><p><strong>${h.diaSemana||''}</strong></p><p>${h.horaInicio||''} - ${h.horaFin||''}</p><p>Estado: <span class="badge ${h.estado==='activo'?'badge-success':(h.estado==='cancelado'?'badge-danger':'badge-warning')}">${h.estado}</span></p></div>`).join(''); } else { cont.innerHTML = '<p class="empty-state">No hay horarios configurados para esta clase</p>'; } document.getElementById('modal-horarios')?.classList.add('active'); }
function cerrarModal(id){ document.getElementById(id)?.classList.remove('active'); }


