console.log('Cargando módulo de excusas...');

/**
 * Gestión de Excusas - Admin
 * Maneja la aprobación y rechazo de excusas por inasistencia y llegada tarde.
 */

let excusasPendientes = [];
let excusaEvaluarActual = null;
let decisionActual = '';
if (typeof ITEMS_POR_PAGINA === 'undefined') {
    var ITEMS_POR_PAGINA = 10;
}
let paginaInasistencias = 1;
let paginaLlegadas = 1;

document.addEventListener('DOMContentLoaded', () => {
    // Inicialización si es necesaria
    console.log('DOM Loaded - Inicializando Excusas');
    cargarFichasExcusas();
});



function renderizarTabla(idTabla, datos) {
    const tbody = document.getElementById(idTabla);

    if (datos.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center" style="padding: 20px; color: #6b7280;">No hay excusas pendientes en esta categoría</td></tr>';
        return;
    }

    const headers = idTabla === 'tablaExcusasInasistencia'
        ? ['Fecha Falta', 'Aprendiz', 'Ficha', 'Motivo', 'Soporte', 'Estado', 'Viabilidad']
        : ['Fecha Falta', 'Aprendiz', 'Ficha', 'Motivo', 'Soporte', 'Estado', 'Viabilidad'];

    // Reconstruir headers si es necesario (el HTML estático define headers pero aquí inyectamos filas, 
    // aseguraremos que el HTML coincida o lo inyectaremos dinámicamente si controlamos headers también.
    // Como controlamos tbody, asumimos que el usuario cambió el HTML o lo cambiamos nosotros.

    // NOTA: El usuario pidió cambiar la columna "Acciones" por "Viabilidad".
    // El HTML tiene th estático. Deberíamos actualizar el HTML también o confiar en que la posición es la misma.
    // La fila renderizada es lo que cambiamos aquí.

    tbody.innerHTML = datos.map(e => `
        <tr class="hover-row">
            <td>${formatearFecha(e.fecha_falta)}</td>
            <td>
                <div style="font-weight: 500;">${e.nombre_aprendiz || 'Desconocido'}</div>
                <div style="font-size: 0.85rem; color: #6b7280;">${e.documento}</div>
            </td>
            <td>${e.numero_ficha}</td>
            <td>
                <div style="max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="${e.motivo}">
                    ${e.motivo}
                </div>
            </td>
            <td class="text-center">
                ${e.archivo_adjunto ?
            `<button onclick="verArchivo('${e.archivo_adjunto}')" class="btn-icon" style="color: #ef4444;" title="Ver PDF">
                        <i class="fas fa-file-pdf fa-lg"></i>
                    </button>` :
            '<span style="color:#ccc">-</span>'
        }
            </td>
            <td>
                <span class="badge" style="background: #f59e0b; color: white;">PENDIENTE</span>
            </td>
            <td style="display: flex; gap: 5px; justify-content: center;">
                <button onclick="procesarExcusaRapida(${e.id_excusa}, 'APROBADA')" class="btn-success" style="padding: 5px 10px; background: #10b981; color: white; border: none; border-radius: 4px; cursor: pointer;" title="Aprobar (SI)">
                    SI
                </button>
                <button onclick="procesarExcusaRapida(${e.id_excusa}, 'RECHAZADA')" class="btn-danger" style="padding: 5px 10px; background: #ef4444; color: white; border: none; border-radius: 4px; cursor: pointer;" title="Rechazar (NO)">
                    NO
                </button>
            </td>
        </tr>
    `).join('');
}

/**
 * Carga las fichas en el select de filtros de excusas
 */
async function cargarFichasExcusas() {
    try {
        const res = await fetch('api/fichas.php?limit=-1');
        const result = await res.json();
        if (result.success) {
            const sel = document.getElementById('filtroExcusasFicha');
            if (sel) {
                sel.innerHTML = '<option value="">Todas las fichas</option>';
                result.data.forEach(f => {
                    sel.insertAdjacentHTML('beforeend', `<option value="${f.numero_ficha}">${f.numero_ficha} - ${f.nombre_programa || ''}</option>`);
                });
            }
        }
    } catch (e) { console.error('Error cargando fichas para excusas:', e); }
}

/**
 * Carga todas las excusas con estado PENDIENTE aplicando filtros
 */
async function cargarExcusasPendientes(pagina = 1) {
    try {
        mostrarCargandoExcusas();

        const ficha = document.getElementById('filtroExcusasFicha')?.value || '';
        const inicio = document.getElementById('filtroExcusasInicio')?.value || '';
        const fin = document.getElementById('filtroExcusasFin')?.value || '';

        // Para simplificar, cargamos todas (-1) y paginamos localmente o hacemos dos llamadas?
        // El diseño secuencial pide total de páginas. Haremos una llamada global y paginaremos localmente por ahora
        // si el API no soporta filtrar por tipo_excusa en la URL directamente (aunque sí lo hace el filter).
        // Pero el API soporta &limit. Si quisiéramos ser pro, haríamos dos llamadas.
        // Pero para no saturar, cargaremos un bloque grande o implementaremos el filtrado en API.

        let url = 'api/excusas.php?action=listar&estado=PENDIENTE&limit=-1'; // Cargamos todas para repartir entre tabs
        if (ficha) url += `&ficha=${encodeURIComponent(ficha)}`;
        if (inicio) url += `&fecha_inicio=${inicio}`;
        if (fin) url += `&fecha_fin=${fin}`;

        const response = await fetch(url);
        const result = await response.json();

        if (result.success) {
            excusasPendientes = result.data;
            renderizarTablasExcusas();
        } else {
            console.error('Error cargando excusas:', result.message);
            mostrarNotificacion('Error cargando excusas', 'error');
        }
    } catch (error) {
        console.error('Error de red:', error);
        mostrarNotificacion('Error de conexión', 'error');
    }
}

function limpiarFiltrosExcusas() {
    document.getElementById('filtroExcusasFicha').value = '';
    document.getElementById('filtroExcusasInicio').value = '';
    document.getElementById('filtroExcusasFin').value = '';
    cargarExcusasPendientes();
}

function mostrarCargandoExcusas() {
    const loading = '<tr><td colspan="7" class="text-center" style="padding: 20px;"><i class="fas fa-spinner fa-spin"></i> Cargando excusas...</td></tr>';
    document.getElementById('tablaExcusasInasistencia').innerHTML = loading;
    document.getElementById('tablaExcusasLlegadaTarde').innerHTML = loading;
}

/**
 * Renderiza las tablas separando por tipo
 */
function renderizarTablasExcusas() {
    const inasistencias = excusasPendientes.filter(e => e.tipo_excusa === 'INASISTENCIA');
    const llegadasTarde = excusasPendientes.filter(e => e.tipo_excusa === 'LLEGADA_TARDE');

    // Paginación Local
    const totalIna = inasistencias.length;
    const totalLle = llegadasTarde.length;

    const sliceIna = inasistencias.slice((paginaInasistencias - 1) * ITEMS_POR_PAGINA, paginaInasistencias * ITEMS_POR_PAGINA);
    const sliceLle = llegadasTarde.slice((paginaLlegadas - 1) * ITEMS_POR_PAGINA, paginaLlegadas * ITEMS_POR_PAGINA);

    renderizarTabla('tablaExcusasInasistencia', sliceIna);
    renderizarTabla('tablaExcusasLlegadaTarde', sliceLle);

    actualizarPaginacionExcusas('paginacionExcusasInasistencia', totalIna, paginaInasistencias, 'cambiarPaginaInasistencia');
    actualizarPaginacionExcusas('paginacionExcusasLlegadaTarde', totalLle, paginaLlegadas, 'cambiarPaginaLlegada');
}

function actualizarPaginacionExcusas(idContainer, totalItems, paginaActual, funcName) {
    const div = document.getElementById(idContainer);
    const totalPages = Math.ceil(totalItems / ITEMS_POR_PAGINA);

    if (!div || totalPages <= 1) {
        if (div) div.innerHTML = '';
        return;
    }

    let html = `<div class="pagination-wrapper" style="margin-top:15px;">`;

    // Anterior
    html += `<button onclick="${funcName}(${paginaActual - 1})" 
             class="btn-pagination ${paginaActual === 1 ? 'disabled' : ''}" 
             ${paginaActual === 1 ? 'disabled' : ''} title="Anterior"><i class="fas fa-chevron-left"></i></button>`;

    // Números
    const max = 5;
    let start = Math.max(1, paginaActual - 2);
    let end = Math.min(totalPages, start + max - 1);

    if (end - start < max - 1) {
        start = Math.max(1, end - max + 1);
    }

    if (start > 1) {
        html += `<button onclick="${funcName}(1)" class="btn-pagination">1</button>`;
        if (start > 2) html += `<span class="pagination-ellipsis">...</span>`;
    }

    for (let i = start; i <= end; i++) {
        html += `<button onclick="${funcName}(i)" class="btn-pagination ${i === paginaActual ? 'active' : ''}">${i}</button>`;
    }
    html = html.replace('onclick="${funcName}(i)"', ''); // Fix: use loop index

    // Re-do loop correctly to avoid JS interpolation issues if any
    let numsHtml = '';
    for (let i = start; i <= end; i++) {
        numsHtml += `<button onclick="${funcName}(${i})" class="btn-pagination ${i === paginaActual ? 'active' : ''}">${i}</button>`;
    }

    // Replace the placeholder loop part if I wrote it wrong above. 
    // Actually I'll just write it correctly here.

    let finalHtml = `<div class="pagination-wrapper" style="margin-top:15px;">`;
    finalHtml += `<button onclick="${funcName}(${paginaActual - 1})" class="btn-pagination ${paginaActual === 1 ? 'disabled' : ''}" ${paginaActual === 1 ? 'disabled' : ''}><i class="fas fa-chevron-left"></i></button>`;

    if (start > 1) {
        finalHtml += `<button onclick="${funcName}(1)" class="btn-pagination">1</button>`;
        if (start > 2) finalHtml += `<span class="pagination-ellipsis">...</span>`;
    }
    finalHtml += numsHtml;
    if (end < totalPages) {
        if (end < totalPages - 1) finalHtml += `<span class="pagination-ellipsis">...</span>`;
        finalHtml += `<button onclick="${funcName}(${totalPages})" class="btn-pagination">${totalPages}</button>`;
    }
    finalHtml += `<button onclick="${funcName}(${paginaActual + 1})" class="btn-pagination ${paginaActual === totalPages ? 'disabled' : ''}" ${paginaActual === totalPages ? 'disabled' : ''}><i class="fas fa-chevron-right"></i></button>`;
    finalHtml += `</div>`;

    div.innerHTML = finalHtml;
}

window.cambiarPaginaInasistencia = function (n) {
    paginaInasistencias = n;
    renderizarTablasExcusas();
}

window.cambiarPaginaLlegada = function (n) {
    paginaLlegadas = n;
    renderizarTablasExcusas();
}

function renderizarTabla(idTabla, datos) {
    const tbody = document.getElementById(idTabla);

    if (datos.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center" style="padding: 20px; color: #6b7280;">No hay excusas pendientes en esta categoría</td></tr>';
        return;
    }

    tbody.innerHTML = datos.map(e => `
        <tr class="hover-row">
            <td>${formatearFecha(e.fecha_falta)}</td>
            <td>
                <div style="font-weight: 500;">${e.nombre_aprendiz || 'Desconocido'}</div>
                <div style="font-size: 0.85rem; color: #6b7280;">${e.documento}</div>
            </td>
            <td>${e.numero_ficha}</td>
            <td>
                <div style="max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="${e.motivo}">
                    ${e.motivo}
                </div>
            </td>
            <td class="text-center">
                ${e.archivo_adjunto ?
            `<button onclick="verArchivo('${e.archivo_adjunto}')" class="btn-icon" style="color: #ef4444;" title="Ver PDF">
                        <i class="fas fa-file-pdf fa-lg"></i>
                    </button>` :
            '<span style="color:#ccc">-</span>'
        }
            </td>
            <td>
                <span class="badge" style="background: #f59e0b; color: white;">PENDIENTE</span>
            </td>
            <td>
                <button onclick="abrirModalEvaluacion(${e.id_excusa})" class="btn-primary" style="padding: 5px 10px; font-size: 0.9rem;">
                    Evaluar
                </button>
            </td>
        </tr>
    `).join('');
}

/**
 * Cambia entre tabs
 */
function cambiarTabExcusas(tipo) {
    const tabInasistencia = document.getElementById('tab-inasistencia');
    const tabLlegada = document.getElementById('tab-llegada-tarde');
    const panelInasistencia = document.getElementById('panel-inasistencia');
    const panelLlegada = document.getElementById('panel-llegada-tarde');

    if (tipo === 'INASISTENCIA') {
        tabInasistencia.classList.add('active');
        tabLlegada.classList.remove('active');
        tabInasistencia.style.borderBottomColor = '#39A900';
        tabInasistencia.style.color = '#39A900';
        tabLlegada.style.borderBottomColor = 'transparent';
        tabLlegada.style.color = '#6b7280';

        panelInasistencia.style.display = 'block';
        panelLlegada.style.display = 'none';
    } else {
        tabInasistencia.classList.remove('active');
        tabLlegada.classList.add('active');
        tabLlegada.style.borderBottomColor = '#39A900';
        tabLlegada.style.color = '#39A900';
        tabInasistencia.style.borderBottomColor = 'transparent';
        tabInasistencia.style.color = '#6b7280';

        panelInasistencia.style.display = 'none';
        panelLlegada.style.display = 'block';
    }
}

/**
 * Abre archivo adjunto en nueva pestaña
 */
function verArchivo(ruta) {
    // Asumiendo que la ruta es relativa desde la raíz pública
    window.open(ruta, '_blank');
}


/**
 * Abre modal de evaluación
 */
window.abrirModalEvaluacion = function (idExcusa) {
    console.log('Intentando abrir modal evaluación para ID:', idExcusa);
    excusaEvaluarActual = excusasPendientes.find(e => e.id_excusa == idExcusa);

    if (!excusaEvaluarActual) {
        console.error('No se encontró la excusa con ID:', idExcusa);
        mostrarNotificacion('Error: No se encontró la excusa', 'error');
        return;
    }

    decisionActual = '';
    const obsInput = document.getElementById('observacionesEvaluacion');
    const btnConfirmar = document.getElementById('btnConfirmarEval');

    if (obsInput) obsInput.value = '';
    if (btnConfirmar) btnConfirmar.disabled = true;

    // Reset botones
    document.querySelectorAll('.btn-decision').forEach(b => {
        b.style.borderColor = '#d1d5db';
        b.style.background = 'white';
        const icon = b.querySelector('i');
        if (icon) {
            // Restaurar colores originales de los iconos
            if (b.id === 'btnAprobar') icon.style.color = '#10b981';
            if (b.id === 'btnRechazar') icon.style.color = '#ef4444';
        }
    });

    // Mostrar info
    const info = document.getElementById('infoEvaluacion');
    if (info) {
        info.innerHTML = `
            <strong>Aprendiz:</strong> ${excusaEvaluarActual.nombre_aprendiz || 'N/A'}<br>
            <strong>Motivo:</strong> ${excusaEvaluarActual.motivo || 'Sin motivo'}<br>
            <strong>Tipo:</strong> ${excusaEvaluarActual.tipo_excusa || 'N/A'}
        `;
    }

    const modal = document.getElementById('modalEvaluacion');
    if (modal) {
        modal.style.display = 'flex';
        console.log('Modal abierto');
    } else {
        console.error('No se encontró el elemento modalEvaluacion');
    }
}

window.cerrarModalEvaluacion = function () {
    const modal = document.getElementById('modalEvaluacion');
    if (modal) modal.style.display = 'none';
    excusaEvaluarActual = null;
}

window.setDecision = function (decision) {
    console.log('Estableciendo decisión:', decision);
    decisionActual = decision;
    const btnAprobar = document.getElementById('btnAprobar');
    const btnRechazar = document.getElementById('btnRechazar');

    if (!btnAprobar || !btnRechazar) return;

    // Reset styles
    btnAprobar.style.background = 'white';
    btnAprobar.style.borderColor = '#d1d5db';
    btnRechazar.style.background = 'white';
    btnRechazar.style.borderColor = '#d1d5db';

    // Apply styles to selected
    if (decision === 'APROBADA') {
        btnAprobar.style.background = '#d1fae5';
        btnAprobar.style.borderColor = '#10b981';
    } else {
        btnRechazar.style.background = '#fee2e2';
        btnRechazar.style.borderColor = '#ef4444';
    }

    validarFormularioEvaluacion();
}

// Event listener puede mantenerse así, pero nos aseguramos que la función exista
document.getElementById('observacionesEvaluacion')?.addEventListener('input', validarFormularioEvaluacion);

window.validarFormularioEvaluacion = function () { // Exponerla aunque sea usada internamente por consistencia
    validarFormularioEvaluacion();
}

function validarFormularioEvaluacion() {
    const obsInput = document.getElementById('observacionesEvaluacion');
    const btn = document.getElementById('btnConfirmarEval');

    if (!obsInput || !btn) return;

    const obs = obsInput.value.trim();

    // Habilitar si hay decisión
    if (decisionActual && obs.length > 0) {
        btn.disabled = false;
    } else {
        btn.disabled = true;
    }
}

/**
 * Envía la evaluación a la API
 */
window.confirmarEvaluacion = async function () {
    console.log('Confirmando evaluación...');
    if (!excusaEvaluarActual || !decisionActual) {
        console.warn('Faltan datos para confirmar');
        return;
    }

    const obs = document.getElementById('observacionesEvaluacion').value.trim();
    const user = authSystem.getCurrentUser();

    const payload = {
        id_excusa: excusaEvaluarActual.id_excusa,
        estado: decisionActual,
        observaciones: obs,
        evaluado_por: user.id_usuario
    };

    try {
        const btn = document.getElementById('btnConfirmarEval');
        if (btn) {
            btn.disabled = true;
            btn.textContent = 'Procesando...';
        }

        const response = await fetch('api/excusas.php?action=evaluar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        const result = await response.json();

        if (result.success) {
            mostrarNotificacion(`Excusa ${decisionActual.toLowerCase()} exitosamente`, 'success');
            cerrarModalEvaluacion();
            cargarExcusasPendientes(); // Recargar tablas
        } else {
            mostrarNotificacion('Error: ' + result.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarNotificacion('Error al procesar la evaluación', 'error');
    } finally {
        const btn = document.getElementById('btnConfirmarEval');
        if (btn) {
            btn.textContent = 'Confirmar';
            // Se mantendrá disabled por validación o se cerrará el modal
        }
    }
}

// Helper de fecha
function formatearFecha(fecha) {
    if (!fecha) return '-';
    // Asumiendo YYYY-MM-DD
    const [y, m, d] = fecha.split('T')[0].split('-');
    return `${d}/${m}/${y}`;
}

/**
 * Procesa la excusa directamente (SI/NO)
 */
window.procesarExcusaRapida = async function (idExcusa, decision) {
    if (!confirm(decision === 'APROBADA' ? '¿Confirmar VIABILIDAD positiva (SI)?' : '¿Confirmar VIABILIDAD negativa (NO)?')) return;

    const user = authSystem.getCurrentUser();
    const payload = {
        id_excusa: idExcusa,
        estado: decision,
        observaciones: decision === 'APROBADA' ? 'Aprobación Directa Admin' : 'Rechazo Directo Admin',
        evaluado_por: user.id_usuario
    };

    try {
        const response = await fetch('api/excusas.php?action=evaluar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        const result = await response.json();

        if (result.success) {
            mostrarNotificacion(`Excusa ${decision === 'APROBADA' ? 'APROBADA (SI)' : 'RECHAZADA (NO)'} correctamente`, 'success');
            cargarExcusasPendientes();
        } else {
            mostrarNotificacion('Error: ' + result.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarNotificacion('Error técnico al procesar', 'error');
    }
}
