// reportes_estadisticas.js - VERSIÓN CORREGIDA

// ============================================
// UTILIDADES
// ============================================
const $ = (sel) => document.querySelector(sel);
const $$ = (sel) => Array.from(document.querySelectorAll(sel));

const API_BASE = window.location.pathname.includes('/pages/') ? '../api/' : 'api/';

const fmtCurrency = (n) => new Intl.NumberFormat('es-AR', { 
    style: 'currency', 
    currency: 'ARS', 
    maximumFractionDigits: 2 
}).format(Number(n || 0));

const fmtDate = (iso) => {
    const d = new Date(iso);
    if (Number.isNaN(d.getTime())) return iso;
    return d.toLocaleDateString('es-AR');
};

const toCSV = (rows) => {
    const esc = (v) => `"${String(v).replace(/"/g, '""')}"`;
    return rows.map((r) => r.map(esc).join(',')).join('\n');
};

// ============================================
// ESTADO GLOBAL
// ============================================
let chartDistribucion = null;
let chartTemporal = null;
let datosActuales = null;

// ============================================
// ELEMENTOS DEL DOM
// ============================================
const form = $('#reportForm');
const tipoReporteEl = $('#tipoReporte');
const periodoEl = $('#periodo');
const fechaInicioEl = $('#fechaInicio');
const fechaFinEl = $('#fechaFin');
const btnGenerar = $('#btnGenerar');
const btnExportar = $('#btnExportar');
const btnImprimir = $('#btnImprimir');
const btnLimpiar = $('#btnLimpiar');
const searchInput = $('#searchInput');
const metricTotal = $('#metricTotal');
const metricCount = $('#metricCount');
const metricAvg = $('#metricAvg');
const metricVariation = $('#metricVariation');
const tabla = $('#tablaResultados');
const tbody = tabla?.querySelector('tbody');
const overlay = $('#loadingOverlay');

// ============================================
// FUNCIONES DE UI
// ============================================
const showLoading = (on) => {
    if (!overlay) return;
    overlay.classList.toggle('active', !!on);
};

const safeJSON = async (res) => {
    if (!res.ok) throw new Error(`Error HTTP ${res.status}`);
    return res.json();
};

// ============================================
// FETCHERS - API CALLS
// ============================================
async function fetchReporte(tipo, from, to) {
    const url = `${API_BASE}reportes_api.php?tipo=${tipo}&from=${from}&to=${to}`;
    const res = await fetch(url, { cache: 'no-store' });
    const json = await safeJSON(res);
    if (!json.success) throw new Error(json.message || `Error API ${tipo}`);
    return json.data;
}

// ============================================
// HELPERS DE CÁLCULO
// ============================================
function variationPercent(series) {
    if (!series || series.length < 2) return 0;
    const prev = series[series.length - 2];
    const curr = series[series.length - 1];
    if (!prev) return 0;
    if (prev === 0) return curr > 0 ? 100 : 0;
    return ((curr - prev) / prev) * 100;
}

// ============================================
// RENDER - UI UPDATES
// ============================================
function resetUI() {
    metricTotal.textContent = fmtCurrency(0);
    metricCount.textContent = '0';
    metricAvg.textContent = fmtCurrency(0);
    metricVariation.textContent = '0%';
    
    if (chartDistribucion) { 
        chartDistribucion.destroy(); 
        chartDistribucion = null; 
    }
    if (chartTemporal) { 
        chartTemporal.destroy(); 
        chartTemporal = null; 
    }
    
    if (tbody) {
        tbody.innerHTML = `
            <tr>
                <td colspan="4" class="no-data">
                    <div class="empty-state">
                        <div class="empty-icon">📊</div>
                        <p>No hay datos generados</p>
                        <small>Selecciona un tipo de reporte y haz clic en "Generar Reporte".</small>
                    </div>
                </td>
            </tr>`;
    }
}

function fillTable(rows) {
    if (!tbody) return;
    if (!rows.length) { 
        resetUI(); 
        return; 
    }
    
    const html = rows.map((r) => {
        const fecha = fmtDate(r.fecha || r.fechaPago || r.fechaInscripcion || r.fechaInicio || '');
        const detalle = r.detalle || r.miembro || r.clase || '-';
        const valor = r.valor !== undefined ? r.valor : (r.monto ? fmtCurrency(r.monto) : '-');
        const estado = (r.estado || '').toUpperCase();
        
        return `<tr>
            <td>${fecha}</td>
            <td>${detalle}</td>
            <td>${valor}</td>
            <td>${estado}</td>
        </tr>`;
    }).join('');
    
    tbody.innerHTML = html;
}

function renderDistribucionChart(ctx, dataPairs) {
    if (chartDistribucion) chartDistribucion.destroy();
    
    const labels = dataPairs.map((d) => d.label);
    const values = dataPairs.map((d) => d.total || d.count);
    
    chartDistribucion = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels,
            datasets: [{
                data: values,
                backgroundColor: ['#10b981','#06b6d4','#f59e0b','#6366f1','#ef4444','#22d3ee','#84cc16'],
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { 
                legend: { 
                    labels: { color: '#374151' } 
                } 
            }
        }
    });
}

function renderTemporalChart(ctx, series) {
    if (chartTemporal) chartTemporal.destroy();
    
    chartTemporal = new Chart(ctx, {
        type: 'line',
        data: {
            labels: series.labels,
            datasets: [{
                label: 'Total',
                data: series.values,
                borderColor: '#22d3ee',
                backgroundColor: 'rgba(34,211,238,.2)',
                fill: true,
                tension: .3,
                pointRadius: 3,
                pointHoverRadius: 5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: { 
                    ticks: { color: '#94a3b8' }, 
                    grid: { color: 'rgba(255,255,255,.06)' } 
                },
                y: { 
                    ticks: { color: '#94a3b8' }, 
                    grid: { color: 'rgba(255,255,255,.06)' } 
                }
            },
            plugins: { 
                legend: { 
                    labels: { color: '#374151' } 
                } 
            }
        }
    });
}

// ============================================
// PROCESAMIENTO DE REPORTES
// ============================================
async function procesarReporteIngresos(data) {
    const rows = Array.isArray(data.tabla) ? data.tabla : [];
    const general = data.general || {};
    
    // Métricas
    const total = Number(general.totalMonto || 0);
    const count = Number(general.totalRegistros || rows.length);
    const avg = Number(general.promedio || 0);
    
    // Serie temporal
    const serie = (data.temporal || []).reduce((acc, r) => {
        acc.labels.push(fmtDate(r.fecha));
        acc.values.push(Number(r.total || 0));
        return acc;
    }, { labels: [], values: [] });
    
    const varPct = variationPercent(serie.values);
    
    // Actualizar métricas
    metricTotal.textContent = fmtCurrency(total);
    metricCount.textContent = String(count);
    metricAvg.textContent = fmtCurrency(avg);
    metricVariation.textContent = `${varPct.toFixed(1)}%`;
    
    // Preparar datos para tabla
    const tablaDatos = rows.map(r => ({
        fecha: r.fechaPago,
        detalle: `${r.miembro} · ${r.metodoPago}${r.referencia && r.referencia !== 'null' ? ' · ' + r.referencia : ''}`,
        valor: fmtCurrency(r.monto),
        estado: (r.estado || '').toUpperCase()
    }));
    
    fillTable(tablaDatos);
    
    // Gráficos
    const distPairs = (data.distribucion || []).map(d => ({ 
        label: d.label, 
        total: Number(d.total || 0) 
    }));
    renderDistribucionChart($('#graficoDistribucion'), distPairs);
    renderTemporalChart($('#graficoTemporal'), serie);
    
    datosActuales = tablaDatos;
}

async function procesarReporteMembresias(data) {
    const rows = Array.isArray(data.tabla) ? data.tabla : [];
    const general = data.general || {};
    
    // Serie temporal
    const serie = (data.temporal || []).reduce((acc, r) => {
        acc.labels.push(fmtDate(r.fecha));
        acc.values.push(Number(r.total || 0));
        return acc;
    }, { labels: [], values: [] });
    
    const varPct = variationPercent(serie.values);
    
    // Métricas
    const totalMiembros = Number(general.totalMiembros || 0);
    const proximosVencer = Number(general.proximosAVencer || 0);
    
    metricTotal.textContent = String(totalMiembros);
    metricCount.textContent = String(rows.length);
    metricAvg.textContent = `${proximosVencer} por vencer`;
    metricVariation.textContent = `${varPct.toFixed(1)}%`;
    
    // Preparar datos para tabla
    const tablaDatos = rows.map(r => ({
        fecha: r.fechaInicio || r.fechaFin,
        detalle: `${r.miembro} · ${r.tipoMembresia}`,
        valor: '-',
        estado: (r.estado || '').toUpperCase()
    }));
    
    fillTable(tablaDatos);
    
    // Gráficos
    const distPairs = (data.distribucion || []).map(d => ({ 
        label: d.label, 
        total: Number(d.count || 0) 
    }));
    renderDistribucionChart($('#graficoDistribucion'), distPairs);
    renderTemporalChart($('#graficoTemporal'), serie);
    
    datosActuales = tablaDatos;
}

async function procesarReporteInscripciones(data) {
    const rows = Array.isArray(data.tabla) ? data.tabla : [];
    const general = data.general || {};
    
    // Serie temporal
    const serie = (data.temporal || []).reduce((acc, r) => {
        acc.labels.push(fmtDate(r.fecha));
        acc.values.push(Number(r.total || 0));
        return acc;
    }, { labels: [], values: [] });
    
    const varPct = variationPercent(serie.values);
    const count = rows.length;
    
    // Métricas
    metricTotal.textContent = String(general.totalRegistros || count);
    metricCount.textContent = String(count);
    metricAvg.textContent = String(Math.round((count / (serie.values.length || 1)) * 10) / 10);
    metricVariation.textContent = `${varPct.toFixed(1)}%`;
    
    // Preparar datos para tabla
    const tablaDatos = rows.map(r => ({
        fecha: r.fechaInscripcion,
        detalle: `${r.miembro} · ${r.clase}`,
        valor: '-',
        estado: (r.estado || '').toUpperCase()
    }));
    
    fillTable(tablaDatos);
    
    // Gráficos
    const distPairs = (data.distribucion || []).map(d => ({ 
        label: d.label, 
        total: Number(d.count || 0) 
    }));
    renderDistribucionChart($('#graficoDistribucion'), distPairs);
    renderTemporalChart($('#graficoTemporal'), serie);
    
    datosActuales = tablaDatos;
}

async function procesarReporteAsistencia(data) {
    const rows = Array.isArray(data.tabla) ? data.tabla : [];
    const general = data.general || {};
    
    // Serie temporal
    const serie = (data.temporal || []).reduce((acc, r) => {
        acc.labels.push(fmtDate(r.fecha));
        acc.values.push(Number(r.total || 0));
        return acc;
    }, { labels: [], values: [] });
    
    const varPct = variationPercent(serie.values);
    const total = Number(general.total || rows.length);
    
    // Métricas
    metricTotal.textContent = String(total);
    metricCount.textContent = String(rows.length);
    metricAvg.textContent = `${Number(general.conEgreso || 0)} con egreso`;
    metricVariation.textContent = `${varPct.toFixed(1)}%`;
    
    // Preparar datos para tabla
    const tablaDatos = rows.map(r => ({
        fecha: r.fecha,
        detalle: `${r.miembro} · ${r.clase || 'Sin clase'}`,
        valor: r.horaIngreso ? `${r.horaIngreso}${r.horaEgreso ? ' - ' + r.horaEgreso : ''}` : '-',
        estado: r.horaEgreso ? 'CON EGRESO' : 'SIN EGRESO'
    }));
    
    fillTable(tablaDatos);
    
    // Gráficos
    const distPairs = (data.distribucion || []).map(d => ({ 
        label: d.label, 
        total: Number(d.count || 0) 
    }));
    renderDistribucionChart($('#graficoDistribucion'), distPairs);
    renderTemporalChart($('#graficoTemporal'), serie);
    
    datosActuales = tablaDatos;
}

// ============================================
// LÓGICA PRINCIPAL
// ============================================
async function generarReporte() {
    const tipo = tipoReporteEl.value;
    const desde = fechaInicioEl.value;
    const hasta = fechaFinEl.value;
    
    if (!desde || !hasta) {
        alert('Por favor selecciona fechas válidas');
        return;
    }
    
    resetUI();
    showLoading(true);
    
    try {
        const data = await fetchReporte(tipo, desde, hasta);
        
        switch (tipo) {
            case 'ingresos':
                await procesarReporteIngresos(data);
                break;
            case 'membresias':
                await procesarReporteMembresias(data);
                break;
            case 'inscripciones':
                await procesarReporteInscripciones(data);
                break;
            case 'asistencia':
                await procesarReporteAsistencia(data);
                break;
            default:
                throw new Error(`Tipo de reporte no soportado: ${tipo}`);
        }
        
    } catch (err) {
        console.error('Error al generar reporte:', err);
        if (tbody) {
            tbody.innerHTML = `<tr><td colspan="4" class="no-data"><div class="empty-state"><div class="empty-icon">⚠️</div><p>Error al generar el reporte</p><small>${err.message || err}</small></div></td></tr>`;
        }
    } finally {
        showLoading(false);
    }
}

function exportarCSV() {
    if (!datosActuales || datosActuales.length === 0) {
        alert('No hay datos para exportar');
        return;
    }
    
    const rows = [['FECHA', 'DETALLE', 'VALOR', 'ESTADO']];
    datosActuales.forEach(r => {
        rows.push([
            fmtDate(r.fecha),
            r.detalle,
            r.valor,
            r.estado
        ]);
    });
    
    const blob = new Blob([toCSV(rows)], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `reporte_${tipoReporteEl.value}_${new Date().toISOString().slice(0,10)}.csv`;
    document.body.appendChild(a);
    a.click();
    a.remove();
    URL.revokeObjectURL(url);
}

function imprimir() {
    window.print();
}

function limpiar() {
    form.reset();
    setDefaultDates();
    resetUI();
    datosActuales = null;
}

function setDefaultDates() {
    const today = new Date();
    const monthAgo = new Date();
    monthAgo.setDate(today.getDate() - 30);
    
    fechaInicioEl.value = monthAgo.toISOString().slice(0,10);
    fechaFinEl.value = today.toISOString().slice(0,10);
}

function aplicarPeriodo() {
    const p = periodoEl.value;
    const today = new Date();
    const start = new Date();
    
    if (p === 'diario') start.setDate(today.getDate() - 1);
    else if (p === 'semanal') start.setDate(today.getDate() - 7);
    else if (p === 'mensual') start.setMonth(today.getMonth() - 1);
    else if (p === 'trimestral') start.setMonth(today.getMonth() - 3);
    else if (p === 'anual') start.setFullYear(today.getFullYear() - 1);
    
    fechaInicioEl.value = start.toISOString().slice(0,10);
    fechaFinEl.value = today.toISOString().slice(0,10);
}

function bindSearch() {
    if (!searchInput) return;
    searchInput.addEventListener('input', () => {
        const q = searchInput.value.toLowerCase();
        $$('#tablaResultados tbody tr').forEach((tr) => {
            const txt = tr.textContent.toLowerCase();
            tr.style.display = txt.includes(q) ? '' : 'none';
        });
    });
}

// ============================================
// INICIALIZACIÓN
// ============================================
document.addEventListener('DOMContentLoaded', () => {
    console.log('[Reportes] Sistema inicializado');
    
    setDefaultDates();
    resetUI();
    bindSearch();
    
    // Event listeners
    periodoEl && periodoEl.addEventListener('change', aplicarPeriodo);
    btnGenerar && btnGenerar.addEventListener('click', generarReporte);
    btnExportar && btnExportar.addEventListener('click', exportarCSV);
    btnImprimir && btnImprimir.addEventListener('click', imprimir);
    btnLimpiar && btnLimpiar.addEventListener('click', limpiar);
    
    // Generar reporte inicial
    generarReporte().catch(err => console.error('Error en reporte inicial:', err));
});