const api = '../api/usuario.php';
const $ = (s)=>document.querySelector(s);

async function apiCall(params, method='GET'){
  const opts = { method };
  if(method==='POST') opts.body = params instanceof FormData ? params : new URLSearchParams(params);
  const url = method==='GET' ? `${api}?${new URLSearchParams(params)}` : api;
  const res = await fetch(url, { credentials:'same-origin' });
  const json = await res.json().catch(()=>({success:false,message:'Respuesta inválida'}));
  if(!res.ok || !json.success) throw new Error(json.message || `HTTP ${res.status}`);
  return json.data;
}

function fillForm(d){
  $('#usuario').value = d.usuario || '';
  $('#cargo').value = d.cargo || '';
  $('#nombre').value = d.nombre || '';
  $('#apellido').value = d.apellido || '';
  $('#email').value = d.email || '';
  $('#telefono').value = d.telefono || '';
  $('#direccion').value = d.direccion || '';
}

function showMsg(text, ok=true){
  const el = $('#msg');
  el.textContent = text;
  el.style.display = 'block';
  el.style.background = ok ? '#d1e7dd' : '#f8d7da';
  el.style.color = ok ? '#0f5132' : '#842029';
  el.style.borderColor = ok ? '#badbcc' : '#f5c2c7';
}

async function cargar(){
  try { const me = await apiCall({action:'me'}); fillForm(me); }
  catch(e){ showMsg('No se pudo cargar el usuario: '+e.message, false); }
}

async function guardar(){
  const p1 = $('#password').value.trim();
  const p2 = $('#password2').value.trim();
  if(p1 || p2){ if(p1!==p2){ showMsg('Las contraseñas no coinciden', false); return; } }
  const fd = new FormData($('#formUser'));
  fd.append('action','update');
  try { await apiCall(fd,'POST'); showMsg('Cambios guardados'); $('#password').value=''; $('#password2').value=''; }
  catch(e){ showMsg('Error al guardar: '+e.message, false); }
}

async function eliminar(){
  if(!confirm('¿Seguro que deseas eliminar tu cuenta? Esta acción no se puede deshacer.')) return;
  try { await apiCall({action:'delete'}, 'POST'); showMsg('Cuenta eliminada. Cerrando sesión...'); setTimeout(()=>location.href='../login.html', 1200); }
  catch(e){ showMsg('Error al eliminar: '+e.message, false); }
}

document.addEventListener('DOMContentLoaded', ()=>{
  cargar();
  $('#btnGuardar').addEventListener('click', guardar);
  $('#btnImprimir').addEventListener('click', ()=>window.print());
  $('#btnEliminar').addEventListener('click', eliminar);
});

