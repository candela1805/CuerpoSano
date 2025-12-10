<?php
session_start();
require_once '../db.php';

// Traer tipos de membresía
try {
  $tipos_membresia = $pdo->query("SELECT idTipo, nombre FROM TipoMembresia WHERE activo = 1")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
  $tipos_membresia = [];
}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Gestión de Socios - Cuerpo Sano</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&family=Libre+Barcode+128&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/qrcodejs2@0.0.2/qrcode.min.js"></script>
<style>
body{font-family:Inter;background:#e8fff3;margin:0;padding:20px;color:#123}
.wrap{display:flex;gap:24px;align-items:flex-start}
.panel{background:#fff;border-radius:14px;padding:18px;box-shadow:0 4px 18px rgba(0,0,0,.1)}
input,select{width:100%;padding:8px;border-radius:8px;border:1px solid #ccc;margin-bottom:8px}
button{padding:8px 12px;border:none;border-radius:8px;cursor:pointer;font-weight:600}
button.primary{background:#10b981;color:white}
button.secondary{background:#f0f0f0}
button.danger{background:#ef4444;color:white}
table{width:100%;border-collapse:collapse;margin-top:8px}
th,td{padding:8px;border-bottom:1px solid #e5e5e5;text-align:left}
input[type="date"][readonly]::-webkit-calendar-picker-indicator {
  display: none;
  -webkit-appearance: none;
}
.photo-preview{width:100%;height:120px;display:flex;justify-content:center;align-items:center;border:1px dashed #ccc;border-radius:8px;overflow:hidden;margin-bottom:8px;background:#fafafa}
.photo-preview img{max-width:100%;max-height:100%}
.codigo-barra{font-family:"Libre Barcode 128";font-size:34px;text-align:center;color:#222}
.modal{position:fixed;inset:0;background:rgba(0,0,0,.5);display:none;align-items:center;justify-content:center;z-index:10}
.modal .card{background:#fff;border-radius:12px;padding:20px;width:380px;text-align:center;box-shadow:0 12px 28px rgba(0,0,0,.25)}
.modal .card img{width:100px;height:100px;border-radius:10px;object-fit:cover;border:2px solid #ddd}
#qr canvas{margin:auto;margin-top:10px}
.search-box{margin-bottom:10px}
.search-box input{width:100%;padding:8px;border:1px solid #ccc;border-radius:8px}
</style>
</head>
<body>
<div class="wrap">
  <div class="panel" style="width:340px">
    <h2>Registro de Socio</h2>
    <form id="formCliente" enctype="multipart/form-data" onsubmit="return false;">
      <input id="dni" name="dni" placeholder="DNI" required>
      <input id="nombre" name="nombre" placeholder="Nombre" required>
      <input id="apellido" name="apellido" placeholder="Apellido" required>
      <input id="email" name="email" placeholder="Email">
      <input id="telefono" name="telefono" placeholder="Teléfono">
      <input id="direccion" name="direccion" placeholder="Dirección">
      <label for="fechaRegistro" style="text-align:left;font-weight:600;margin-bottom:4px;">Fecha de Registro</label>
      <input id="fechaRegistro" name="fechaRegistro" type="date" readonly>
      <select id="tipoMembresia" name="tipoMembresia">
        <?php foreach($tipos_membresia as $t): ?>
          <option value="<?= $t['idTipo']?>"><?= htmlspecialchars($t['nombre'])?></option>
        <?php endforeach;?>
      </select>
      <!--<input id="descuento" name="descuento" type="number" value="0" min="0" max="100">-->
      <div class="photo-preview" id="preview"><span>Sin foto</span></div>
      <input type="file" id="foto" name="foto" accept="image/*" onchange="previewFoto()">
      <div class="codigo-barra" id="codigoVista"></div>
      <button class="primary" id="btnPrincipal" onclick="agregarCliente()">Agregar</button>
    </form>
  </div>

  <div class="panel" style="flex:1">
    <h2>Listado de Socios</h2>
    <div class="search-box">
      <input type="text" id="buscar" placeholder="Buscar por nombre, apellido o DNI..." onkeyup="filtrarSocios()">
    </div>
    <table>
      <thead><tr><th>DNI</th><th>Nombre</th><th>Email</th><th>Membresía</th><th>Acciones</th></tr></thead>
      <tbody id="tbody"><tr><td colspan="5">Cargando...</td></tr></tbody>
    </table>
  </div>
</div>

<!-- MODAL CARNET -->
<div id="modal" class="modal" onclick="cerrarModal(event)">
  <div class="card" id="carnet">
    <h3>Cuerpo Sano</h3>
    <img id="fotoCarnet">
    <h2 id="nombreCarnet"></h2>
    <p id="dniCarnet"></p>
    <p id="tipoCarnet"></p>
    <svg id="codigoCarnet"></svg>
    <div id="qr"></div>
    <button class="primary" onclick="imprimirCarnet()">Imprimir Carnet</button>
    <button class="secondary" onclick="cerrarModal()">Cerrar</button>
  </div>
</div>

<script>
const el=id=>document.getElementById(id);
let clientes=[], editando=false;

document.addEventListener('DOMContentLoaded', () => {
  listarClientes();
  setFechaActual();
});

function previewFoto(){
 const f=el('foto').files[0];
 const p=el('preview');
 if(!f){p.innerHTML='<span>Sin foto</span>';return;}
 const r=new FileReader();
 r.onload=()=>{p.innerHTML=`<img src="${r.result}">`;};
 r.readAsDataURL(f);
}

function setFechaActual(){
  const hoy = new Date();
  const formatoISO = hoy.toISOString().split('T')[0];
  el('fechaRegistro').value = formatoISO;
}

function listarClientes(){
 fetch('../api/clientes_api.php?accion=listar')
 .then(r=>r.json()).then(res=>{
   if(res.ok){clientes=res.data;renderTabla();}
   else alert(res.msg);
 });
}

function renderTabla(){
 const tb=el('tbody');
 tb.innerHTML='';
 if(!clientes.length){tb.innerHTML='<tr><td colspan="5">Sin registros.</td></tr>';return;}
 clientes.forEach((c, index)=>{
   const tr=document.createElement('tr');
   tr.innerHTML=`
     <td>${c.dni}</td>
     <td>${c.nombre} ${c.apellido}</td>
     <td>${c.email||''}</td>
     <td>${c.tipo||''}</td>
     <td>
       <button onclick="verCarnet('${c.dni}')">Carnet</button>
       <button class="secondary" onclick="editarCliente(${index})">Editar</button>
       <button class="danger" onclick="eliminarCliente(${index})">Eliminar</button>
     </td>`;
   tb.appendChild(tr);
 });
}

function agregarCliente(){
 const fd=new FormData(el('formCliente'));
 fd.append('accion', editando ? 'actualizar' : 'agregar');
 fetch('../api/clientes_api.php',{method:'POST',body:fd})
 .then(r=>r.json()).then(res=>{
   alert(res.msg);
   if(res.ok){
     el('formCliente').reset();
     el('preview').innerHTML='<span>Sin foto</span>';
     editando=false;
     el('dni').readOnly=false;
     setFechaActual();
     el('btnPrincipal').textContent='Agregar';
     listarClientes();
   }
 });
}

function editarCliente(index){
 const c=clientes[index];
 if(!c)return;
 el('dni').value=c.dni;el('dni').readOnly=true;
 el('nombre').value=c.nombre;el('apellido').value=c.apellido;
 el('email').value=c.email;el('telefono').value=c.telefono;
 el('direccion').value=c.direccion;el('fechaRegistro').value = c.fechaInicio || '';
 el('tipoMembresia').value=c.idTipoMembresia;el('descuento').value=c.descuento || 0;
 el('preview').innerHTML=c.foto ? `<img src="../uploads/${c.foto}">` : '<span>Sin foto</span>';
 editando=true;
 el('btnPrincipal').textContent='Guardar Cambios';
}

function eliminarCliente(index){
 const cliente = clientes[index];
 if (!cliente) return;
 
 if(!confirm('¿Eliminar socio?'))return;
 const fd=new FormData();fd.append('accion','eliminar');fd.append('dni',cliente.dni);
 fetch('../api/clientes_api.php',{method:'POST',body:fd}).then(r=>r.json()).then(res=>{
   alert(res.msg);
   if(res.ok)listarClientes();
 });
}

function verCarnet(dni){
 const c=clientes.find(x=>x.dni===dni);
 if(!c)return;
 el('nombreCarnet').textContent=c.nombre+' '+c.apellido;
 el('dniCarnet').textContent='DNI: '+c.dni;
 el('tipoCarnet').textContent='Membresía: '+(c.tipo||'');
 el('fotoCarnet').src=c.foto?('../uploads/'+c.foto):'https://via.placeholder.com/100?text=Sin+Foto';
 JsBarcode("#codigoCarnet",'CS'+c.dni,{format:"CODE128",displayValue:false});
 el('qr').innerHTML='';
 new QRCode(el('qr'),{text:`${c.nombre} ${c.apellido} (${c.dni})`,width:80,height:80});
 el('modal').style.display='flex';
}

function cerrarModal(e){
 if(e && e.target!==el('modal'))return;
 el('modal').style.display='none';
 el('qr').innerHTML='';
}

function imprimirCarnet(){
 const contenido=el('carnet').outerHTML;
 const w=window.open('','_blank');
 w.document.write(`<html><head><title>Carnet</title></head><body>${contenido}</body></html>`);
 w.document.close();
 w.print();
}

function filtrarSocios(){
 const term=el('buscar').value.toLowerCase();
 const filtrados=clientes.filter(c=>(
   c.dni.toLowerCase().includes(term) ||
   (c.nombre+' '+c.apellido).toLowerCase().includes(term) ||
   (c.email||'').toLowerCase().includes(term)
 ));
 const tb=el('tbody');
 tb.innerHTML='';
 if(!filtrados.length){tb.innerHTML='<tr><td colspan="5">No se encontraron coincidencias.</td></tr>';return;}
 filtrados.forEach((c, index)=>{
   const tr=document.createElement('tr');
   tr.innerHTML=`
     <td>${c.dni}</td>
     <td>${c.nombre} ${c.apellido}</td>
     <td>${c.email||''}</td>
     <td>${c.tipo||''}</td>
     <td>
       <button onclick="verCarnet('${c.dni}')">Carnet</button>
       <button class="secondary" onclick="editarCliente(${index})">Editar</button>
       <button class="danger" onclick="eliminarCliente(${index})">Eliminar</button>
     </td>`;
   tb.appendChild(tr);
 });
}
</script>
</body>
</html>
