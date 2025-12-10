// assets/js/gestionMembresias.js

const API_URL = '../api/gestionMembresias_api.php';

// Variables globales
let miembroSeleccionado = null;

// Cargar datos iniciales
document.addEventListener('DOMContentLoaded', function() {
    console.log('Página cargada, iniciando...');
    cargarMembresias();
    cargarMiembros();
    cargarClases();
});

// ============================================
// FUNCIÓN PARA CAMBIAR TABS
// ============================================
function openTab(tabName) {
    console.log('Cambiando a tab:', tabName);
    
    const tabs = document.querySelectorAll('.tab');
    const contents = document.querySelectorAll('.tab-content');
    
    tabs.forEach(tab => tab.classList.remove('active'));
    contents.forEach(content => content.classList.remove('active'));
    
    const clickedTab = Array.from(tabs).find(tab => 
        tab.textContent.toLowerCase().includes(tabName.toLowerCase())
    );
    
    if (clickedTab) {
        clickedTab.classList.add('active');
    }
    
    const targetContent = document.getElementById(tabName);
    if (targetContent) {
        targetContent.classList.add('active');
    }
}

// ============================================
// MEMBRESÍAS
// ============================================

function cargarMembresias() {
    console.log('Cargando membresías...');
    fetch(API_URL + '?accion=obtener_membresias')
        .then(response => {
            if (!response.ok) {
                throw new Error('Error HTTP: ' + response.status);
            }
            return response.text();
        })
        .then(text => {
            try {
                const data = JSON.parse(text);
                if (data.success) {
                    mostrarMembresias(data.membresias);
                } else {
                    console.error('Error:', data.message);
                    document.getElementById('bodyMembresias').innerHTML = 
                        `<tr><td colspan="6" style="text-align:center; color:red;">Error: ${data.message}</td></tr>`;
                }
            } catch (e) {
                console.error('Error parsing JSON:', e);
                console.error('Response text:', text);
                document.getElementById('bodyMembresias').innerHTML = 
                    '<tr><td colspan="6" style="text-align:center; color:red;">Error: Respuesta inválida del servidor.</td></tr>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('bodyMembresias').innerHTML = 
                '<tr><td colspan="6" style="text-align:center; color:red;">Error de conexión.</td></tr>';
        });
}

function mostrarMembresias(membresias) {
    const tbody = document.getElementById('bodyMembresias');
    tbody.innerHTML = '';
    
    membresias.forEach(memb => {
        let duracionTexto = '';
        if (memb.duracionDias == 30) duracionTexto = "Mensual (30 días)";
        else if (memb.duracionDias == 90) duracionTexto = "Trimestral (90 días)";
        else if (memb.duracionDias == 365) duracionTexto = "Anual (365 días)";
        else duracionTexto = memb.duracionDias + " días";
        
        const row = `
            <tr>
                <td>${memb.idTipo}</td>
                <td><strong>${memb.nombre}</strong></td>
                <td>${memb.descripcion}</td>
                <td>$${parseFloat(memb.precioBase).toFixed(2)}</td>
                <td>${duracionTexto}</td>
                <td>
                    <button class="btn btn-warning" onclick="editarPrecio(${memb.idTipo}, '${memb.nombre}', ${memb.precioBase})">
                        💰 Editar Precio
                    </button>
                    <button class="btn btn-danger" onclick="eliminarMembresia(${memb.idTipo}, '${memb.nombre}')">
                        🗑️ Eliminar
                    </button>
                </td>
            </tr>
        `;
        tbody.innerHTML += row;
    });
}

// ============================================
// AGREGAR MEMBRESÍA
// ============================================

function abrirModalAgregar() {
    document.getElementById('modalAgregar').classList.add('active');
}

function cerrarModalAgregar() {
    document.getElementById('modalAgregar').classList.remove('active');
    document.getElementById('formAgregar').reset();
}

document.getElementById('formAgregar').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData();
    formData.append('accion', 'agregar_membresia');
    formData.append('nombre', document.getElementById('nombreNuevo').value);
    formData.append('descripcion', document.getElementById('descripcionNuevo').value);
    formData.append('precio', document.getElementById('precioNuevo').value);
    formData.append('duracion', document.getElementById('duracionNuevo').value);
    formData.append('clave', document.getElementById('claveAgregar').value);
    
    fetch(API_URL, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ ' + data.message);
            cerrarModalAgregar();
            cargarMembresias();
        } else {
            alert('❌ ' + data.message);
        }
    })
    .catch(error => alert('Error: ' + error));
});

// ============================================
// ELIMINAR MEMBRESÍA
// ============================================

function eliminarMembresia(idTipo, nombre) {
    if (!confirm(`¿Está seguro de eliminar la membresía "${nombre}"?\n\nNOTA: Esta acción solo desactivará la membresía, no eliminará registros existentes.`)) {
        return;
    }
    
    const clave = prompt('Ingrese la clave de seguridad:');
    if (!clave) {
        return;
    }
    
    const formData = new FormData();
    formData.append('accion', 'eliminar_membresia');
    formData.append('idTipo', idTipo);
    formData.append('clave', clave);
    
    fetch(API_URL, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ ' + data.message);
            cargarMembresias();
        } else {
            alert('❌ ' + data.message);
        }
    })
    .catch(error => alert('Error: ' + error));
}

// ============================================
// IMPRIMIR MEMBRESÍAS
// ============================================

function imprimirMembresias() {
    fetch(API_URL + '?accion=obtener_membresias')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                generarImpresion(data.membresias);
            } else {
                alert('Error al cargar datos para imprimir');
            }
        })
        .catch(error => alert('Error: ' + error));
}

function generarImpresion(membresias) {
    const ventana = window.open('', '_blank');
    
    const fecha = new Date().toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
    
    let html = `
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Listado de Membresías - CuerpoSano</title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body {
                    font-family: Arial, sans-serif;
                    padding: 30px;
                    color: #333;
                }
                .header {
                    text-align: center;
                    margin-bottom: 30px;
                    border-bottom: 3px solid #667eea;
                    padding-bottom: 20px;
                }
                .header h1 {
                    color: #667eea;
                    font-size: 2em;
                    margin-bottom: 10px;
                }
                .header p {
                    color: #666;
                    font-size: 1.1em;
                }
                .info {
                    margin-bottom: 20px;
                    text-align: right;
                    color: #666;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 20px;
                }
                th {
                    background: #667eea;
                    color: white;
                    padding: 12px;
                    text-align: left;
                    font-weight: bold;
                }
                td {
                    padding: 10px;
                    border-bottom: 1px solid #ddd;
                }
                tr:nth-child(even) {
                    background: #f9f9f9;
                }
                .descuentos {
                    margin-top: 30px;
                    padding: 15px;
                    background: #f0f0f0;
                    border-left: 4px solid #667eea;
                }
                .descuentos h3 {
                    color: #667eea;
                    margin-bottom: 10px;
                }
                .descuentos ul {
                    margin-left: 20px;
                }
                .footer {
                    margin-top: 40px;
                    text-align: center;
                    color: #999;
                    font-size: 0.9em;
                    border-top: 2px solid #ddd;
                    padding-top: 15px;
                }
                @media print {
                    body { padding: 15px; }
                    .no-print { display: none; }
                }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>🏋️ CuerpoSano</h1>
                <p>Listado de Tipos de Membresía</p>
            </div>
            
            <div class="info">
                <strong>Fecha de impresión:</strong> ${fecha}
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th>Precio Base</th>
                        <th>Duración</th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    membresias.forEach(memb => {
        let duracionTexto = '';
        if (memb.duracionDias == 30) duracionTexto = "Mensual (30 días)";
        else if (memb.duracionDias == 90) duracionTexto = "Trimestral (90 días)";
        else if (memb.duracionDias == 365) duracionTexto = "Anual (365 días)";
        else duracionTexto = memb.duracionDias + " días";
        
        html += `
            <tr>
                <td>${memb.idTipo}</td>
                <td><strong>${memb.nombre}</strong></td>
                <td>${memb.descripcion}</td>
                <td>$${parseFloat(memb.precioBase).toFixed(2)}</td>
                <td>${duracionTexto}</td>
            </tr>
        `;
    });
    
    html += `
                </tbody>
            </table>
            
            <div class="descuentos">
                <h3>Descuentos Aplicables</h3>
                <ul>
                    <li><strong>Estudiantes primarios/secundarios:</strong> 20% de descuento</li>
                    <li><strong>Mayores de 60 años:</strong> 25% de descuento</li>
                </ul>
            </div>
            
            <div class="footer">
                <p>CuerpoSano - Sistema de Gestión de Membresías</p>
                <p>Documento generado automáticamente</p>
            </div>
            
            <script>
                window.onload = function() {
                    window.print();
                }
            </script>
        </body>
        </html>
    `;
    
    ventana.document.write(html);
    ventana.document.close();
}

// ============================================
// EDITAR PRECIO
// ============================================

function editarPrecio(idTipo, nombre, precio) {
    document.getElementById('idTipoEdit').value = idTipo;
    document.getElementById('nombreMembresiaEdit').value = nombre;
    document.getElementById('precioActual').value = '$' + precio;
    document.getElementById('nuevoPrecio').value = precio;
    document.getElementById('modalPrecio').classList.add('active');
}

function cerrarModal() {
    document.getElementById('modalPrecio').classList.remove('active');
    document.getElementById('formPrecio').reset();
}

document.getElementById('formPrecio').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData();
    formData.append('accion', 'actualizar_precio');
    formData.append('idTipo', document.getElementById('idTipoEdit').value);
    formData.append('nuevoPrecio', document.getElementById('nuevoPrecio').value);
    formData.append('clave', document.getElementById('claveSeguridad').value);
    
    fetch(API_URL, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ ' + data.message);
            cerrarModal();
            cargarMembresias();
        } else {
            alert('❌ ' + data.message);
        }
    })
    .catch(error => alert('Error: ' + error));
});

// ============================================
// INSCRIPCIONES
// ============================================

function cargarMiembros() {
    console.log('Cargando miembros...');
    fetch(API_URL + '?accion=obtener_miembros')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const select = document.getElementById('miembroInscripcion');
                select.innerHTML = '<option value="">-- Seleccione un miembro --</option>';
                
                data.miembros.forEach(miembro => {
                    const option = document.createElement('option');
                    option.value = miembro.idMiembro;
                    option.setAttribute('data-tipo', miembro.idTipo);
                    option.textContent = `${miembro.nombreCompleto} - ${miembro.tipoMembresia}`;
                    select.appendChild(option);
                });
            }
        })
        .catch(error => console.error('Error:', error));
}

function cargarClases() {
    console.log('Cargando clases...');
    fetch(API_URL + '?accion=obtener_clases')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const selectInscripcion = document.getElementById('claseInscripcion');
                selectInscripcion.innerHTML = '<option value="">-- Seleccione una clase --</option>';
                
                data.clases.forEach(clase => {
                    const option = document.createElement('option');
                    option.value = clase.idClase;
                    option.setAttribute('data-capacidad', clase.capacidad);
                    option.setAttribute('data-inscritos', clase.inscritos);
                    option.textContent = `${clase.nombre} - ${clase.actividad} (${clase.inscritos}/${clase.capacidad})`;
                    selectInscripcion.appendChild(option);
                });
                
                mostrarClases(data.clases);
            }
        })
        .catch(error => console.error('Error:', error));
}

function mostrarClases(clases) {
    const tbody = document.getElementById('bodyClases');
    tbody.innerHTML = '';
    
    clases.forEach(clase => {
        const badgeClass = clase.inscritos >= clase.capacidad ? 'badge-warning' : 'badge-success';
        const row = `
            <tr>
                <td><strong>${clase.nombre}</strong></td>
                <td>${clase.actividad}</td>
                <td>${clase.entrenador}</td>
                <td>${clase.horaInicio.substring(0,5)} - ${clase.horaFin.substring(0,5)}</td>
                <td>
                    <span class="badge ${badgeClass}">
                        ${clase.inscritos}/${clase.capacidad}
                    </span>
                </td>
            </tr>
        `;
        tbody.innerHTML += row;
    });
}

function mostrarDetallesClase() {
    const select = document.getElementById('claseInscripcion');
    const option = select.options[select.selectedIndex];
    
    if (select.value) {
        const capacidad = option.dataset.capacidad;
        const inscritos = option.dataset.inscritos;
        const disponibles = capacidad - inscritos;
        
        let html = `<strong>Cupos disponibles:</strong> ${disponibles} de ${capacidad}`;
        
        if (disponibles <= 0) {
            html += '<br><span style="color: red;">⚠️ Clase llena</span>';
        }
        
        document.getElementById('detallesClase').innerHTML = html;
        document.getElementById('detallesClase').style.display = 'block';
    } else {
        document.getElementById('detallesClase').style.display = 'none';
    }
}

document.getElementById('formInscripcion').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData();
    formData.append('accion', 'inscribir_clase');
    formData.append('idMiembro', document.getElementById('miembroInscripcion').value);
    formData.append('idClase', document.getElementById('claseInscripcion').value);
    
    fetch(API_URL, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ ' + data.message);
            this.reset();
            document.getElementById('detallesClase').style.display = 'none';
            cargarClases();
        } else {
            alert('❌ ' + data.message);
        }
    })
    .catch(error => alert('Error: ' + error));
});

// ============================================
// ASISTENCIAS
// ============================================

function buscarMiembro() {
    const busqueda = document.getElementById('busquedaMiembro').value.trim();
    
    if (busqueda.length < 2) {
        document.getElementById('resultadosBusqueda').style.display = 'none';
        return;
    }
    
    const formData = new FormData();
    formData.append('accion', 'buscar_miembro');
    formData.append('busqueda', busqueda);
    
    fetch(API_URL, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.miembros.length > 0) {
            mostrarResultadosBusqueda(data.miembros);
        } else {
            document.getElementById('resultadosBusqueda').innerHTML = 
                '<div style="padding: 10px; text-align: center; color: #999;">No se encontraron miembros</div>';
            document.getElementById('resultadosBusqueda').style.display = 'block';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al buscar miembro');
    });
}

function mostrarResultadosBusqueda(miembros) {
    const container = document.getElementById('resultadosBusqueda');
    container.innerHTML = '';
    
    miembros.forEach(miembro => {
        const item = document.createElement('div');
        item.className = 'resultado-item';
        item.innerHTML = `
            <strong>${miembro.nombreCompleto}</strong><br>
            <small>DNI: ${miembro.dni} | ${miembro.tipoMembresia}</small>
        `;
        item.onclick = () => seleccionarMiembro(miembro);
        container.appendChild(item);
    });
    
    container.style.display = 'block';
}

function seleccionarMiembro(miembro) {
    miembroSeleccionado = miembro;
    
    document.getElementById('busquedaMiembro').value = miembro.nombreCompleto;
    document.getElementById('resultadosBusqueda').style.display = 'none';
    
    document.getElementById('infoMiembroSeleccionado').innerHTML = `
        <div class="miembro-info">
            <h4>Miembro Seleccionado</h4>
            <p><strong>Nombre:</strong> ${miembro.nombreCompleto}</p>
            <p><strong>DNI:</strong> ${miembro.dni}</p>
            <p><strong>Membresía:</strong> ${miembro.tipoMembresia}</p>
            <p><strong>Estado:</strong> <span class="badge badge-success">${miembro.estado}</span></p>
        </div>
    `;
    document.getElementById('infoMiembroSeleccionado').style.display = 'block';
    
    cargarClasesMiembro(miembro.idMiembro);
}

function cargarClasesMiembro(idMiembro) {
    const formData = new FormData();
    formData.append('accion', 'obtener_clases_miembro');
    formData.append('idMiembro', idMiembro);
    
    fetch(API_URL, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            mostrarClasesMiembro(data.clases);
        } else {
            document.getElementById('listaClasesMiembro').innerHTML = 
                '<div style="padding: 15px; text-align: center; color: #999;">Este miembro no está inscrito en ninguna clase</div>';
            document.getElementById('listaClasesMiembro').style.display = 'block';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al cargar clases del miembro');
    });
}

function mostrarClasesMiembro(clases) {
    const container = document.getElementById('listaClasesMiembro');
    
    if (clases.length === 0) {
        container.innerHTML = '<div style="padding: 15px; text-align: center; color: #999;">Este miembro no está inscrito en ninguna clase</div>';
        container.style.display = 'block';
        return;
    }
    
    const tbody = document.getElementById('bodyClasesMiembro');
    tbody.innerHTML = '';
    
    clases.forEach(clase => {
        const asistioHoy = parseInt(clase.asistioHoy) > 0;
        
        const row = `
            <tr>
                <td><strong>${clase.nombreClase}</strong></td>
                <td>${clase.actividad}</td>
                <td>${clase.entrenador}</td>
                <td>${clase.horaInicio.substring(0,5)} - ${clase.horaFin.substring(0,5)}</td>
                <td>
                    ${asistioHoy 
                        ? '<span class="badge badge-success">✅ Presente hoy</span>' 
                        : '<span class="badge badge-warning">Pendiente</span>'}
                    ${clase.ultimaAsistencia ? `<br><small>Última: ${formatearFecha(clase.ultimaAsistencia)}</small>` : ''}
                </td>
                <td>
                    ${!asistioHoy 
                        ? `<button class="btn btn-success" onclick="marcarAsistencia(${miembroSeleccionado.idMiembro}, ${clase.idClase}, '${clase.nombreClase}')">
                            ✅ Marcar Presente
                           </button>`
                        : '<span style="color: #48bb78;">✔ Registrado</span>'}
                </td>
            </tr>
        `;
        tbody.innerHTML += row;
    });
    
    container.style.display = 'block';
}

function marcarAsistencia(idMiembro, idClase, nombreClase) {
    if (!confirm(`¿Marcar asistencia para la clase "${nombreClase}"?`)) {
        return;
    }
    
    const formData = new FormData();
    formData.append('accion', 'registrar_asistencia');
    formData.append('idMiembro', idMiembro);
    formData.append('idClase', idClase);
    
    fetch(API_URL, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ ' + data.message);
            cargarClasesMiembro(idMiembro);
        } else {
            alert('❌ ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al registrar asistencia');
    });
}

function limpiarBusqueda() {
    document.getElementById('busquedaMiembro').value = '';
    document.getElementById('resultadosBusqueda').style.display = 'none';
    document.getElementById('infoMiembroSeleccionado').style.display = 'none';
    document.getElementById('listaClasesMiembro').style.display = 'none';
    miembroSeleccionado = null;
}

function formatearFecha(fecha) {
    const f = new Date(fecha);
    return f.toLocaleDateString('es-ES');
}

// Cerrar modales al hacer clic fuera
window.onclick = function(event) {
    const modalPrecio = document.getElementById('modalPrecio');
    const modalAgregar = document.getElementById('modalAgregar');
    
    if (event.target === modalPrecio) {
        cerrarModal();
    }
    if (event.target === modalAgregar) {
        cerrarModalAgregar();
    }
}

// Hacer las funciones globales
window.openTab = openTab;
window.editarPrecio = editarPrecio;
window.cerrarModal = cerrarModal;
window.abrirModalAgregar = abrirModalAgregar;
window.cerrarModalAgregar = cerrarModalAgregar;
window.eliminarMembresia = eliminarMembresia;
window.imprimirMembresias = imprimirMembresias;
window.mostrarDetallesClase = mostrarDetallesClase;
window.buscarMiembro = buscarMiembro;
window.limpiarBusqueda = limpiarBusqueda;
window.marcarAsistencia = marcarAsistencia;