
// payment.js - 

// API Base URL - Detecta automáticamente la ruta
// O ajusta manualmente según tu configuración
//const API_URL = window.location.origin + window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/'));

// Si la detección automática falla, descomenta y ajusta una de estas líneas:
const API_URL = (window.location.pathname.includes('/pages/')? '..' : '.') + '/api';  // Para archivos en htdocs/
// const API_URL = 'http://localhost/cuerposano';  // Para archivos en htdocs/cuerposano/

// Estado de la aplicación
let appState = {
    currentStep: 1,
    selectedMember: null,
    selectedMembership: null,
    selectedDiscount: null,
    paymentMethod: null,
    paymentData: {}
};

// Cargar datos al iniciar
document.addEventListener('DOMContentLoaded', async () => {
    console.log('Sistema iniciado');
    await loadMembers();
    await loadMembershipTypes();
    await loadDiscounts();
});

// ============================================
// FUNCIONES DE CARGA DE DATOS
// ============================================

async function loadMembers() {
    const container = document.getElementById('membersContainer');
    container.innerHTML = '<p>Cargando miembros...</p>';
    
    try {
        const response = await fetch(`${API_URL}/get_data.php?action=members`);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.success && data.data && data.data.length > 0) {
            container.innerHTML = data.data.map(member => `
                <div class="member-card" onclick="selectMember(${member.idMiembro}, '${escapeHtml(member.nombre)}', '${escapeHtml(member.dni)}', '${escapeHtml(member.tipoMembresia)}')">
                    <div class="member-info">
                        <div>
                            <div class="member-name">${escapeHtml(member.nombre)}</div>
                            <div class="member-detail">DNI: ${escapeHtml(member.dni)}</div>
                            <div class="member-detail">Email: ${escapeHtml(member.email || 'Sin email')}</div>
                        </div>
                        <span class="badge badge-${member.estado === 'activo' ? 'active' : 'inactive'}">
                            ${member.estado.toUpperCase()}
                        </span>
                    </div>
                </div>
            `).join('');
        } else {
            container.innerHTML = '<p>No hay miembros disponibles</p>';
        }
    } catch (error) {
        console.error('Error al cargar miembros:', error);
        container.innerHTML = `
            <div class="alert alert-info">
                <strong>Error de conexión</strong><br>
                No se pudo conectar con el servidor. Verifica:
                <ul style="margin-top: 10px; margin-left: 20px;">
                    <li>XAMPP está ejecutándose</li>
                    <li>La base de datos existe</li>
                    <li>La ruta es: ${API_URL}/get_data.php</li>
                </ul>
            </div>
        `;
    }
}

async function loadMembershipTypes() {
    try {
        const response = await fetch(`${API_URL}/get_data.php?action=membership_types`);
        const data = await response.json();
        
        if (data.success && data.data) {
            // Aseguramos que precioBase sea un número
            window.membershipTypes = data.data.map(m => ({
                ...m,
                precioBase: parseFloat(m.precioBase)
            }));
        } else {
            throw new Error('No se pudieron cargar los tipos de membresía');
        }
    } catch (error) {
        console.error('Error al cargar tipos de membresía:', error);
        // Datos de respaldo
        window.membershipTypes = [
            { idTipo: 1, nombre: 'Básica', descripcion: 'Acceso al gimnasio general', precioBase: 50.00, duracionDias: 30 },
            { idTipo: 2, nombre: 'Premium', descripcion: 'Acceso con clases grupales', precioBase: 80.00, duracionDias: 30 },
            { idTipo: 3, nombre: 'VIP', descripcion: 'Acceso con entrenador personal', precioBase: 150.00, duracionDias: 30 },
            { idTipo: 4, nombre: 'Anual', descripcion: 'Membresía anual con descuento', precioBase: 500.00, duracionDias: 365 }
        ];
    }
}

async function loadDiscounts() {
    try {
        const response = await fetch(`${API_URL}/get_data.php?action=discounts`);
        const data = await response.json();
        
        if (data.success && data.data) {
            // Aseguramos que porcentaje sea un número
            window.discounts = data.data.map(d => ({
                ...d,
                porcentaje: parseFloat(d.porcentaje)
            }));
        } else {
            window.discounts = [];
        }
    } catch (error) {
        console.error('Error al cargar descuentos:', error);
        // Datos de respaldo
        window.discounts = [
            { idDescuento: 1, descripcion: 'Descuento estudiante', porcentaje: 15.00, tipo: 'Estudiante' },
            { idDescuento: 2, descripcion: 'Descuento tercera edad', porcentaje: 20.00, tipo: 'Adulto Mayor' }
        ];
    }
}

// ============================================
// FUNCIONES DE SELECCIÓN
// ============================================

function selectMember(id, name, dni, currentMembershipName) {
    document.querySelectorAll('#membersContainer .member-card').forEach(card => {
        card.classList.remove('selected');
    });
    
    event.currentTarget.classList.add('selected');
    appState.selectedMember = { id, name, dni, currentMembershipName };
    document.getElementById('btn-step1').disabled = false;
}

function selectMembership(id, name, price, duration) {
    document.querySelectorAll('.membership-card').forEach(card => {
        card.classList.remove('selected');
    });
    
    event.currentTarget.classList.add('selected');
    appState.selectedMembership = { id, name, price, duration };
    updateSummary();
    document.getElementById('btn-step2').disabled = false;
}

function handleDiscountChange(event) {
    // Desactivar botones de descuento rápido si se usa el select
    document.querySelectorAll('.btn-quick-discount').forEach(btn => btn.classList.remove('selected'));

    const selectedId = event.target.value;
    
    if (selectedId) {
        const discount = window.discounts.find(d => d.idDescuento == selectedId);
        if (discount) {
            appState.selectedDiscount = { id: discount.idDescuento, description: discount.descripcion, percentage: discount.porcentaje };
        } else {
            appState.selectedDiscount = null;
        }
    } else {
        appState.selectedDiscount = null;
    }
    updateSummary(); // Actualiza el resumen de precios
}

function applyQuickDiscount(event, description, percentage) {
    // Limpiar la selección del dropdown
    const discountSelect = document.getElementById('discountSelect');
    if (discountSelect) {
        discountSelect.value = '';
    }

    // Manejar el estado visual de los botones
    document.querySelectorAll('.btn-quick-discount').forEach(btn => btn.classList.remove('selected'));
    event.currentTarget.classList.add('selected');

    // Aplicar el descuento
    appState.selectedDiscount = {
        id: 'quick_student', // Un ID para identificarlo
        description: description,
        percentage: percentage
    };
    updateSummary();
}

function selectPaymentMethod(method) {
    document.querySelectorAll('.payment-method-card').forEach(card => {
        card.classList.remove('selected');
    });
    
    event.currentTarget.classList.add('selected');
    appState.paymentMethod = method;
    renderPaymentForm(method);
    document.getElementById('btn-step3').disabled = false;
}

// ============================================
// NAVEGACIÓN ENTRE PASOS
// ============================================

function goToStep(step) {
    if (step === 2 && !appState.selectedMember) {
        alert('Por favor selecciona un miembro');
        return;
    }
    
    if (step === 3 && !appState.selectedMembership) {
        alert('Por favor selecciona una membresía');
        return;
    }
    
    document.querySelectorAll('.card').forEach(card => {
        card.classList.remove('active');
    });
    
    document.getElementById(`step${step}`).classList.add('active');
    
    const progressBar = document.getElementById('progressBar');
    progressBar.className = `progress-bar step-${step}`;
    
    document.querySelectorAll('.step').forEach((stepEl, index) => {
        if (index < step) {
            stepEl.classList.add('active');
        } else {
            stepEl.classList.remove('active');
        }
    });
    
    appState.currentStep = step;
    
    if (step === 2) {
        renderMemberships();
        renderDiscounts();
    }
    
    if (step === 3) {
        updatePaymentSummary();
    }
}

// ============================================
// RENDERIZADO DE ELEMENTOS
// ============================================

function renderMemberships() { // Ahora toma en cuenta la membresía actual del socio
    const container = document.getElementById('membershipsContainer');
    const currentMembershipName = appState.selectedMember?.currentMembershipName;

    if (!window.membershipTypes || window.membershipTypes.length === 0) {
        container.innerHTML = '<p>No hay membresías disponibles</p>';
        return;
    }
    
    container.innerHTML = window.membershipTypes.map(membership => `
        <div class="membership-card ${membership.nombre === currentMembershipName ? 'selected' : ''}" 
             onclick="selectMembership(${membership.idTipo}, '${escapeHtml(membership.nombre)}', ${membership.precioBase}, ${membership.duracionDias})">
            <div class="membership-name">${escapeHtml(membership.nombre)}</div>
            <p>${escapeHtml(membership.descripcion)}</p>
            <div class="membership-price">$${membership.precioBase.toFixed(2)}</div>
            <div class="membership-duration">${membership.duracionDias} días</div>
            ${membership.nombre === currentMembershipName ? '<span class="badge badge-active" style="position:absolute;top:10px;right:10px;">Actual</span>' : ''}
        </div>
    `).join('');
}

function renderDiscounts() {
    const select = document.getElementById('discountSelect');
    
    // Asignar eventos a los botones de descuento rápido
    document.querySelectorAll('.btn-quick-discount').forEach(button => {
        const percentage = button.dataset.percentage;
        const description = button.dataset.description;
        if (percentage && description) {
            button.addEventListener('click', (event) => applyQuickDiscount(event, description, parseFloat(percentage)));
        }
    });

    // Si el select existe, lo poblamos y le asignamos su evento
    if (select) {
        // Limpiar opciones existentes (excepto la primera)
        select.options.length = 1; 

        // Poblar el select con los descuentos cargados
        if (window.discounts && window.discounts.length > 0) {
            window.discounts.forEach(discount => {
                const option = new Option(`${escapeHtml(discount.descripcion)} (-${discount.porcentaje}%)`, discount.idDescuento);
                select.add(option);
            });
        }
        
        select.addEventListener('change', handleDiscountChange);
    }
}

function renderPaymentForm(method) {
    const container = document.getElementById('paymentForm');
    
    switch(method) {
        case 'Efectivo':
            container.innerHTML = `
                <div class="alert alert-info">
                    <strong>Pago en Efectivo</strong><br>
                    El pago debe realizarse en la recepción del gimnasio.
                </div>
                <div class="form-group">
                    <label>Número de Referencia (Opcional)</label>
                    <input type="text" id="reference" placeholder="REF-XXXXXX">
                </div>
            `;
            break;
            
        case 'Tarjeta de Crédito':
        case 'Tarjeta de Débito':
            container.innerHTML = `
                <div class="form-group">
                    <label>Número de Tarjeta</label>
                    <input type="text" id="cardNumber" placeholder="1234 5678 9012 3456" maxlength="19" required>
                </div>
                <div class="form-group">
                    <label>Nombre en la Tarjeta</label>
                    <input type="text" id="cardName" placeholder="JUAN PEREZ" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Fecha de Expiración</label>
                        <input type="text" id="expiryDate" placeholder="MM/AA" maxlength="5" required>
                    </div>
                    <div class="form-group">
                        <label>CVV</label>
                        <input type="text" id="cvv" placeholder="123" maxlength="4" required>
                    </div>
                </div>
            `;
            break;
            
        case 'Transferencia Bancaria':
            container.innerHTML = `
                <div class="bank-info">
                    <h4>Datos Bancarios</h4>
                    <p><strong>Banco:</strong> Banco Nacional</p>
                    <p><strong>Titular:</strong> CuerpoSano S.A.</p>
                    <p><strong>Cuenta:</strong> 1234-5678-9012-3456</p>
                    <p><strong>CBU:</strong> 0123456789012345678901</p>
                    <p><strong>Alias:</strong> CUERPOSANO.GYM</p>
                </div>
                <div class="form-group">
                    <label>Número de Comprobante</label>
                    <input type="text" id="reference" placeholder="Número de comprobante" required>
                </div>
            `;
            break;
            
        case 'QR':
            const total = calculateTotal();
            const reference = 'QR-' + Date.now();
            container.innerHTML = `
                <div class="qr-container">
                    <h3>Escanea el código QR para pagar</h3>
                    <div class="qr-code">📱</div>
                    <p style="font-size: 12px; color: #666; margin-top: 10px;">Simulación de QR Code</p>
                    <p style="margin-top: 15px;"><strong>Monto:</strong> $${total.toFixed(2)}</p>
                    <p style="color: #666; font-size: 14px;">Ref: ${reference}</p>
                    <p style="color: #999; font-size: 12px; margin-top: 10px;">Código válido por 15 minutos</p>
                </div>
            `;
            appState.paymentData.reference = reference;
            break;
    }
}

// ============================================
// CÁLCULOS Y ACTUALIZACIONES
// ============================================

function calculateTotal() {
    if (!appState.selectedMembership) return 0;
    
    let total = appState.selectedMembership.price;
    
    if (appState.selectedDiscount) {
        total -= (total * appState.selectedDiscount.percentage / 100);
    }
    
    return total;
}

function updateSummary() {
    if (!appState.selectedMembership) return;
    
    const summaryBox = document.getElementById('summaryBox');
    summaryBox.style.display = 'block';
    
    document.getElementById('summaryMembership').textContent = appState.selectedMembership.name;
    document.getElementById('summaryBasePrice').textContent = `$${appState.selectedMembership.price.toFixed(2)}`;
    
    let total = appState.selectedMembership.price;
    
    if (appState.selectedDiscount) {
        document.getElementById('summaryDiscountRow').style.display = 'flex';
        document.getElementById('summaryDiscount').textContent = `-${appState.selectedDiscount.percentage}%`;
        total -= (total * appState.selectedDiscount.percentage / 100);
    } else {
        document.getElementById('summaryDiscountRow').style.display = 'none';
    }
    
    document.getElementById('summaryTotal').textContent = `$${total.toFixed(2)}`;
}

function updatePaymentSummary() {
    document.getElementById('paymentMember').textContent = appState.selectedMember.name;
    document.getElementById('paymentMembership').textContent = appState.selectedMembership.name;
    
    if (appState.selectedDiscount) {
        document.getElementById('paymentDiscountRow').style.display = 'flex';
        document.getElementById('paymentDiscount').textContent = `-${appState.selectedDiscount.percentage}%`;
    } else {
        document.getElementById('paymentDiscountRow').style.display = 'none';
    }
    
    document.getElementById('paymentTotal').textContent = `$${calculateTotal().toFixed(2)}`;
}

// ============================================
// PROCESAMIENTO DE PAGO
// ============================================

async function processPayment() {
    const btn = document.getElementById('btn-step3');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<div class="loading"><div class="spinner"></div> Procesando...</div>';
    btn.disabled = true;
    
    let reference = appState.paymentData.reference || 'REF-' + Date.now();
    
    if (document.getElementById('reference')) {
        const refInput = document.getElementById('reference').value;
        if (refInput) reference = refInput;
    }
    
    const paymentPayload = {
        idMiembro: appState.selectedMember.id,
        monto: calculateTotal(),
        metodoPago: appState.paymentMethod,
        referencia: reference,
        idMembresia: appState.selectedMembership.id,
        idDescuento: appState.selectedDiscount ? appState.selectedDiscount.id : null
    };
    
    try {
        const response = await fetch(`${API_URL}/process_payment.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(paymentPayload)
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        
        if (!result.success) {
            throw new Error(result.message || 'Error al procesar el pago');
        }
        
        showConfirmation(result.data);
        goToStep(4);
        
    } catch (error) {
        console.error('Error:', error);
        alert('Error al procesar el pago: ' + error.message);
        btn.innerHTML = originalText;
        btn.disabled = false;
    }
}

// ============================================
// CONFIRMACIÓN Y RESET
// ============================================

function showConfirmation(data) {
    document.getElementById('confirmReference').textContent = data.referencia;
    document.getElementById('confirmMember').textContent = appState.selectedMember.name;
    document.getElementById('confirmMembership').textContent = appState.selectedMembership.name;
    document.getElementById('confirmMethod').textContent = data.metodoPago;
    document.getElementById('confirmDate').textContent = data.fechaPago;
    document.getElementById('confirmAmount').textContent = `$${parseFloat(data.monto).toFixed(2)}`;
}

function resetPayment() {
    appState = {
        currentStep: 1,
        selectedMember: null,
        selectedMembership: null,
        selectedDiscount: null,
        paymentMethod: null,
        paymentData: {}
    };
    
    document.querySelectorAll('.member-card, .membership-card, .discount-card, .payment-method-card').forEach(card => {
        card.classList.remove('selected');
    });
    
    document.getElementById('summaryBox').style.display = 'none';
    document.getElementById('paymentForm').innerHTML = '';
    
    document.getElementById('btn-step1').disabled = true;
    document.getElementById('btn-step2').disabled = true;
    document.getElementById('btn-step3').disabled = true;
    
    goToStep(1);
}

// ============================================
// UTILIDADES
// ============================================

function escapeHtml(text) {
    if (!text) return '';
    
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    
    return String(text).replace(/[&<>"']/g, m => map[m]);
}

function formatCardNumber(input) {
    let value = input.value.replace(/\s/g, '');
    let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
    input.value = formattedValue;
}

function formatExpiryDate(input) {
    let value = input.value.replace(/\D/g, '');
    if (value.length >= 2) {
        value = value.substring(0, 2) + '/' + value.substring(2, 4);
    }
    input.value = value;
}

document.addEventListener('DOMContentLoaded', () => {
    document.addEventListener('input', (e) => {
        if (e.target.id === 'cardNumber') {
            formatCardNumber(e.target);
        }
        if (e.target.id === 'expiryDate') {
            formatExpiryDate(e.target);
        }
    });
});
