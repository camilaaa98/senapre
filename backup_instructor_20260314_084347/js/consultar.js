// Variables globales
let asistenciasActuales = [];
let logoBase64 = '';
let logoSenaBase64 = '';
let excusaActual = null;
const ITEMS_POR_PAGINA = 10;

// ... (In downloadFile)
function downloadFile(content, fileName, mimeType) {
    // Agregar BOM para soporte UTF-8 en Excel
    const bom = '\uFEFF';
    const blob = new Blob([bom + content], { type: mimeType });

    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = fileName;
    link.click();
}

document.addEventListener('DOMContentLoaded', async () => {
    cargarFichas();
    cargarLogos(); // Pre-cargar logos

    // Establecer fechas por defecto (último mes)
    const hoy = new Date();
    const haceUnMes = new Date(hoy);
    haceUnMes.setMonth(haceUnMes.getMonth() - 1);

    const fechaHoySt = hoy.toISOString().split('T')[0];
    const fechaInicioSt = fechaHoySt; // Default: Solo hoy (para evitar confusión con acumulados)

    const inputInicio = document.getElementById('filtroFechaInicio');
    const inputFin = document.getElementById('filtroFechaFin');

    if (inputInicio && inputFin) {
        // Establecer máximos (No futuro)
        inputInicio.max = fechaHoySt;
        inputFin.max = fechaHoySt;

        // Valores por defecto
        inputInicio.value = fechaHoySt;
        inputFin.value = fechaHoySt;

        // Listeners con validación cruzada
        inputInicio.addEventListener('change', () => validarFechas(inputInicio, inputFin));
        inputFin.addEventListener('change', () => validarFechas(inputInicio, inputFin));
    }

    document.getElementById('filtroBuscar')?.addEventListener('input', debounce(filtrarLocal, 300));

    // Configurar botón de exportar para menú desplegable si se desea, o botones individuales
    const container = document.querySelector('.card-header');
    if (container) {
        // Reemplazar botón simple con grupo de botones
        const actionDiv = container.querySelector('button')?.parentNode || container;
        const oldBtn = container.querySelector('button');
        if (oldBtn && oldBtn.textContent.includes('Exportar')) oldBtn.remove();

        const btnGroup = document.createElement('div');
        btnGroup.className = 'flex-gap-sm';
        btnGroup.innerHTML = `
            <button onclick="exportarExcel()" class="btn-primary btn-excel"><i class="fas fa-file-excel"></i> Excel</button>
            <button onclick="exportarPDF()" class="btn-primary btn-pdf"><i class="fas fa-file-pdf"></i> PDF</button>
            <button onclick="exportarCSV()" class="btn-primary btn-csv"><i class="fas fa-file-csv"></i> CSV</button>
        `;
        container.appendChild(btnGroup);
    }
});

// Agregar robustez a cargarFichas
window.addEventListener('load', () => {
    // Si por alguna razón DOMContentLoaded falló
    if (!document.getElementById('filtroFicha').options.length > 1) {
        console.log('Reintentando carga de fichas en window.load');
        cargarFichas();
    }
});

async function cargarLogos() {
    try {
        logoBase64 = await getBase64FromUrl('assets/img/asi.png');
        logoSenaBase64 = await getBase64FromUrl('assets/img/logosena.png');
    } catch (e) {
        console.warn('No se pudieron cargar los logos:', e);
    }
}

function getBase64FromUrl(url) {
    return new Promise((resolve) => {
        const img = new Image();
        img.crossOrigin = 'Anonymous';
        img.src = url;
        img.onload = () => {
            const canvas = document.createElement('canvas');
            canvas.width = img.width;
            canvas.height = img.height;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(img, 0, 0);
            resolve(canvas.toDataURL('image/png'));
        };
        img.onerror = () => resolve('');
    });
}

function validarFechas(inputInicio, inputFin) {
    if (!inputInicio || !inputFin) return;

    // Parsear fechas manejando zona horaria local
    const inicioParts = inputInicio.value.split('-');
    const finParts = inputFin.value.split('-');

    if (inicioParts.length !== 3 || finParts.length !== 3) return;

    const inicio = new Date(inicioParts[0], inicioParts[1] - 1, inicioParts[2]);
    const fin = new Date(finParts[0], finParts[1] - 1, finParts[2]);
    const hoy = new Date();
    hoy.setHours(0, 0, 0, 0);

    // Validación 1: No fechas futuras
    if (inicio > hoy) {
        mostrarNotificacion('La fecha de inicio no puede ser futura', 'error');
        inputInicio.value = hoy.toISOString().split('T')[0];
        return;
    }
    if (fin > hoy) {
        mostrarNotificacion('La fecha fin no puede ser futura', 'error');
        inputFin.value = hoy.toISOString().split('T')[0];
        return;
    }

    // Validación 2: Inicio <= Fin
    if (inicio > fin) {
        mostrarNotificacion('La fecha de inicio no puede ser mayor a la fecha fin', 'error');
        inputInicio.value = inputFin.value;
    }
}

async function cargarFichas() {
    try {
        let idUsuario = '';

        // 1. Intentar obtener de authSystem
        if (typeof authSystem !== 'undefined' && authSystem.getCurrentUser()) {
            idUsuario = authSystem.getCurrentUser().id_usuario;
        }

        // 2. Fallback: LocalStorage
        if (!idUsuario) {
            const session = localStorage.getItem('user_session') || localStorage.getItem('usuario_actual');
            if (session) {
                try {
                    const data = JSON.parse(session);
                    idUsuario = data.id_usuario || data.id;
                } catch (e) { console.warn('Error parsing session', e); }
            }
        }

        if (!idUsuario) {
            console.error('No se pudo identificar al usuario instructo para cargar fichas via API.');
            // No retornamos inmediatamente para ver si la API maneja sesión PHP
        }

        const url = `api/instructor-fichas.php?id_usuario=${idUsuario || ''}`;
        const response = await fetch(url);
        const result = await response.json();

        if (result.success) {
            const select = document.getElementById('filtroFicha');
            if (select) {
                const fichaOptions = result.data.map(f =>
                    `<option value="${f.numero_ficha}">${f.numero_ficha} - ${f.nombre_programa || 'Sin programa'}</option>`
                ).join('');

                select.innerHTML = '<option value="">Todas las fichas</option>' + fichaOptions;
            }
        } else {
            console.warn('API Fichas error:', result.message);
        }
    } catch (error) {
        console.error('Error cargando fichas:', error);
    }
}

async function consultarAsistencias(pagina = 1) {
    try {
        const ficha = document.getElementById('filtroFicha').value;
        const fechaInicio = document.getElementById('filtroFechaInicio').value;
        const fechaFin = document.getElementById('filtroFechaFin').value;

        let url = `api/asistencias.php?page=${pagina}&limit=${ITEMS_POR_PAGINA}`;
        if (ficha) url += `&ficha=${ficha}`;
        if (fechaInicio) url += `&fecha_inicio=${fechaInicio}`;
        if (fechaFin) url += `&fecha_fin=${fechaFin}`;

        const response = await fetch(url);
        const result = await response.json();

        if (result.success) {
            asistenciasActuales = result.data;
            mostrarResultados(asistenciasActuales);
            actualizarPaginacion(result.pagination);
            mostrarResumen(); // Resumen histórico o filtrado? El API -1 total ayudaría pero resumen lo hace con lo actual
            mostrarNotificacion(`${result.pagination.total} registros encontrados`, result.pagination.total > 0 ? 'success' : 'warning');
        } else {
            mostrarNotificacion('Error: ' + (result.message || 'Desconocido'), 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarNotificacion('Error al consultar asistencias', 'error');
    }
}

function actualizarPaginacion(p) {
    const div = document.getElementById('paginacion');
    if (!div || p.pages <= 1) {
        if (div) div.innerHTML = '';
        return;
    }

    let html = `<div class="pagination-wrapper">`;

    // Anterior
    html += `<button onclick="consultarAsistencias(${p.page - 1})" 
             class="btn-pagination ${p.page === 1 ? 'disabled' : ''}" 
             ${p.page === 1 ? 'disabled' : ''} title="Anterior"><i class="fas fa-chevron-left"></i></button>`;

    // Números
    const max = 5;
    let start = Math.max(1, p.page - 2);
    let end = Math.min(p.pages, start + max - 1);

    if (end - start < max - 1) {
        start = Math.max(1, end - max + 1);
    }

    if (start > 1) {
        html += `<button onclick="consultarAsistencias(1)" class="btn-pagination">1</button>`;
        if (start > 2) html += `<span class="pagination-ellipsis">...</span>`;
    }

    for (let i = start; i <= end; i++) {
        html += `<button onclick="consultarAsistencias(${i})" class="btn-pagination ${i === p.page ? 'active' : ''}">${i}</button>`;
    }

    if (end < p.pages) {
        if (end < p.pages - 1) html += `<span class="pagination-ellipsis">...</span>`;
        html += `<button onclick="consultarAsistencias(${p.pages})" class="btn-pagination">${p.pages}</button>`;
    }

    // Siguiente
    html += `<button onclick="consultarAsistencias(${p.page + 1})" 
             class="btn-pagination ${p.page === p.pages ? 'disabled' : ''}" 
             ${p.page === p.pages ? 'disabled' : ''} title="Siguiente"><i class="fas fa-chevron-right"></i></button>`;

    html += `</div>`;
    div.innerHTML = html;
}

function filtrarLocal() {
    const busqueda = document.getElementById('filtroBuscar')?.value.toLowerCase() || '';
    let datos = asistenciasActuales;

    if (busqueda) {
        datos = asistenciasActuales.filter(a =>
            (a.nombre && a.nombre.toLowerCase().includes(busqueda)) ||
            (a.apellido && a.apellido.toLowerCase().includes(busqueda)) ||
            (a.documento_aprendiz && a.documento_aprendiz.toString().includes(busqueda))
        );
    }
    mostrarResultados(datos);
}

function mostrarResultados(datos) {
    const tbody = document.getElementById('tablaResultados');
    if (!tbody) return;

    if (!datos || datos.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4 color-muted">No se encontraron resultados</td></tr>';
        const resumen = document.getElementById('resumenContainer');
        if (resumen) resumen.classList.add('hidden');
        return;
    }

    tbody.innerHTML = datos.map(a => {
        const estadoBadge = getEstadoBadge(a.estado);
        const horaLlegada = formatearHora(a.creado_en);
        const botonExcusa = generarBotonExcusa(a);

        return `
            <tr>
                <td>${formatearFecha(a.fecha)}</td>
                <td>${a.numero_ficha || '-'}</td>
                <td>${a.documento_aprendiz || '-'}</td>
                <td>${a.apellido || ''} ${a.nombre || ''}</td>
                <td>${estadoBadge}</td>
                <td class="text-center font-bold color-dark">${horaLlegada}</td>
                <td>${botonExcusa}</td>
            </tr>
        `;
    }).join('');
}

function mostrarResumen() {
    if (asistenciasActuales.length === 0) {
        const resumen = document.getElementById('resumenContainer');
        if (resumen) resumen.classList.add('hidden');
        return;
    }

    const presentes = asistenciasActuales.filter(a => a.estado === 'Presente').length;
    const ausentes = asistenciasActuales.filter(a => a.estado === 'Ausente').length;
    const justificados = asistenciasActuales.filter(a => a.estado === 'Justificado' || a.estado === 'Excusa').length;
    const total = asistenciasActuales.length;
    const porcentaje = total > 0 ? ((presentes / total) * 100).toFixed(1) : 0;

    const elPresentes = document.getElementById('totalPresentes');
    const elAusentes = document.getElementById('totalAusentes');
    const elJustificados = document.getElementById('totalJustificados');
    const elPorcentaje = document.getElementById('porcentajeAsistencia');
    const elResumen = document.getElementById('resumenContainer');

    if (elPresentes) elPresentes.textContent = presentes;
    if (elAusentes) elAusentes.textContent = ausentes;
    if (elJustificados) elJustificados.textContent = justificados;
    if (elPorcentaje) elPorcentaje.textContent = porcentaje + '%';
    if (elResumen) elResumen.classList.remove('hidden');
}

function getEstadoBadge(estado) {
    if (!estado) return '-';
    const s = estado.toLowerCase();
    let cls = 'badge-status ';
    let icon = '';

    if (s.includes('presente') || s.includes('temprano')) {
        cls += 'badge-status-presente';
        icon = '<i class="fas fa-check-circle"></i>';
    } else if (s.includes('ausente')) {
        cls += 'badge-status-ausente';
        icon = '<i class="fas fa-times-circle"></i>';
    } else if (s.includes('retardo') || s.includes('retardado')) {
        cls += 'badge-status-retardo';
        icon = '<i class="fas fa-clock"></i>';
    } else if (s.includes('excusa')) {
        cls += 'badge-status-excusa';
        icon = '<i class="fas fa-file-medical"></i>';
    } else if (s.includes('justificado')) {
        cls += 'badge-status-justificado';
        icon = '<i class="fas fa-exclamation-circle"></i>';
    } else {
        cls += 'badge-status-default';
    }
    return `<span class="${cls}">${icon} ${estado}</span>`;
}

function formatearFecha(fecha) {
    if (!fecha) return '-';
    // Manejar fecha con o sin zona horaria
    const d = new Date(fecha.includes('T') ? fecha : fecha + 'T00:00:00');
    return d.toLocaleDateString('es-CO', { year: 'numeric', month: '2-digit', day: '2-digit' });
}

function formatearHora(fechaHora) {
    if (!fechaHora) return '-';
    try {
        const d = new Date(fechaHora);
        return d.toLocaleTimeString('es-CO', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true });
    } catch (e) {
        return '-';
    }
}

function limpiarFiltros() {
    document.getElementById('filtroFicha').value = '';
    document.getElementById('filtroFechaInicio').value = '';
    document.getElementById('filtroFechaFin').value = '';
    document.getElementById('filtroBuscar').value = '';
    asistenciasActuales = [];

    document.getElementById('tablaResultados').innerHTML = '<tr><td colspan="7" class="text-center py-4 color-muted">Seleccione filtros y haga clic en Buscar</td></tr>';
    document.getElementById('resumenContainer').classList.add('hidden');
}

// ========== GESTIÓN DE EXCUSAS ==========

/**
 * Genera el botón/badge de excusa según el estado y validación temporal
 */
function generarBotonExcusa(registro) {
    const estado = registro.estado;
    const fecha = registro.fecha;
    const documento = registro.documento_aprendiz;
    const nombre = `${registro.apellido} ${registro.nombre}`;
    const ficha = registro.numero_ficha;

    // Calcular días transcurridos
    const fechaFalta = new Date(fecha.includes('T') ? fecha : fecha + 'T00:00:00');
    const hoy = new Date();
    const diferenciaDias = Math.floor((hoy - fechaFalta) / (1000 * 60 * 60 * 24));

    // Solo mostrar botón para Ausente o Retardo
    if (estado !== 'Ausente' && estado !== 'Retardo' && estado !== 'Retardado') {
        return '-';
    }

    // Validar ventana de 3 días
    const puedeSubir = diferenciaDias <= 3;

    if (puedeSubir) {
        const tipoExcusa = estado === 'Ausente' ? 'INASISTENCIA' : 'LLEGADA_TARDE';
        const colorBoton = estado === 'Ausente' ? '#ef4444' : '#f59e0b';
        const diasRestantes = 3 - diferenciaDias;

        return `
            <button 
                onclick="abrirModalExcusa('${documento}', '${nombre}', '${estado}', '${fecha}', '${ficha}')" 
                class="btn btn-sm btn-excusa-upload" 
                title="Quedan ${diasRestantes} día(s) para subir excusa">
                <i class="fas fa-file-upload"></i> Subir Excusa
            </button>
            <small class="block-text mt-1 color-muted">Quedan ${diasRestantes} día(s)</small>
        `;
    } else {
        return `
            <span class="italic-muted font-small">
                <i class="fas fa-times-circle"></i> Plazo vencido
            </span>
        `;
    }
}

/**
 * Abre el modal para subir excusa
 */
function abrirModalExcusa(documento, nombre, estado, fecha, ficha) {
    // Determinar tipo de excusa
    const tipoExcusa = estado === 'Ausente' ? 'INASISTENCIA' : 'LLEGADA_TARDE';
    const tipoTexto = tipoExcusa === 'INASISTENCIA' ? 'Excusa por Inasistencia' : 'Excusa por Llegada Tarde';
    const colorTipo = tipoExcusa === 'INASISTENCIA' ? '#ef4444' : '#f59e0b';

    // Guardar datos actuales
    excusaActual = {
        documento: documento,
        nombre: nombre,
        estado: estado,
        fecha: fecha,
        ficha: ficha,
        tipo_excusa: tipoExcusa
    };

    // Actualizar modal
    document.getElementById('infoAprendizExcusa').textContent = `${nombre} (${documento})`;
    document.getElementById('tipoExcusaInfo').innerHTML = `
        <span class="font-large ${tipoExcusa === 'INASISTENCIA' ? 'color-error' : 'color-warning'}">
            <i class="fas fa-${tipoExcusa === 'INASISTENCIA' ? 'user-times' : 'clock'}"></i> 
            ${tipoTexto}
        </span>
    `;

    // Validar días restantes
    const fechaFalta = new Date(fecha.includes('T') ? fecha : fecha + 'T00:00:00');
    const hoy = new Date();
    const diferenciaDias = Math.floor((hoy - fechaFalta) / (1000 * 60 * 60 * 24));
    const diasRestantes = 3 - diferenciaDias;

    const mensajeValidacion = document.getElementById('mensajeValidacion');
    if (diasRestantes >= 0) {
        mensajeValidacion.innerHTML = `<span class="color-success">✓ Puede subir excusa (quedan ${diasRestantes} día(s))</span>`;
        document.getElementById('btnSubirExcusa').disabled = false;
    } else {
        mensajeValidacion.innerHTML = `<span class="color-error">✗ Plazo vencido (han pasado ${diferenciaDias} días)</span>`;
        document.getElementById('btnSubirExcusa').disabled = true;
    }

    // Limpiar campos
    document.getElementById('motivoExcusa').value = '';
    document.getElementById('archivoExcusa').value = '';

    // Mostrar modal
    document.getElementById('modalExcusa').style.display = 'flex';
}

/**
 * Cierra el modal de excusas
 */
function cerrarModalExcusa() {
    document.getElementById('modalExcusa').style.display = 'none';
    excusaActual = null;
}

/**
 * Sube la excusa a la API
 */
async function subirExcusa() {
    if (!excusaActual) return;

    const motivo = document.getElementById('motivoExcusa').value.trim();
    const archivoInput = document.getElementById('archivoExcusa');

    if (!motivo) {
        mostrarNotificacion('El motivo es obligatorio', 'error');
        return;
    }

    if (archivoInput.files.length === 0) {
        mostrarNotificacion('Debe adjuntar un archivo PDF', 'error');
        return;
    }

    const archivo = archivoInput.files[0];
    if (archivo.size > 5 * 1024 * 1024) {
        mostrarNotificacion('El archivo no debe superar 5MB', 'error');
        return;
    }

    if (archivo.type !== 'application/pdf') {
        mostrarNotificacion('Solo se permiten archivos PDF', 'error');
        return;
    }

    try {
        // Convertir archivo a Base64
        const base64 = await convertirArchivoABase64(archivo);

        // Preparar payload
        const payload = {
            documento: excusaActual.documento,
            numero_ficha: excusaActual.ficha,
            fecha_falta: excusaActual.fecha,
            motivo: motivo,
            tipo_excusa: excusaActual.tipo_excusa,
            archivo_adjunto: base64
        };

        // Enviar a API
        const response = await fetch('api/excusas.php?action=subir', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        const result = await response.json();

        if (result.success) {
            mostrarNotificacion('✅ Excusa subida exitosamente. Será evaluada por un administrador.', 'success');
            cerrarModalExcusa();
            // Recargar asistencias para actualizar la tabla
            consultarAsistencias();
        } else {
            mostrarNotificacion('❌ Error: ' + (result.message || 'No se pudo subir la excusa'), 'error');
        }

    } catch (error) {
        console.error('Error subiendo excusa:', error);
        mostrarNotificacion('❌ Error técnico al subir excusa', 'error');
    }
}

/**
 * Convierte un archivo a Base64
 */
function convertirArchivoABase64(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.readAsDataURL(file);
        reader.onload = () => resolve(reader.result);
        reader.onerror = error => reject(error);
    });
}

// ========== EXPORTACIÓN PROFESIONAL ==========


function exportarExcel() {
    if (asistenciasActuales.length === 0) return mostrarNotificacion('No hay datos', 'error');

    // Base URL para imágenes
    const baseUrl = window.location.origin + window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/'));

    let table = `
        <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
        <head>
            <!--[if gte mso 9]>
            <xml>
                <x:ExcelWorkbook>
                    <x:ExcelWorksheets>
                        <x:ExcelWorksheet>
                            <x:Name>Asistencias</x:Name>
                            <x:WorksheetOptions>
                                <x:DisplayGridlines/>
                            </x:WorksheetOptions>
                        </x:ExcelWorksheet>
                    </x:ExcelWorksheets>
                </x:ExcelWorkbook>
            </xml>
            <![endif]-->
            <style>
                th { background-color: #39A900; color: white; font-weight: bold; border: 1px solid #ddd; text-align: center; vertical-align: middle; }
                td { border: 1px solid #ddd; padding: 5px; vertical-align: middle; }
            </style>
        </head>
        <body>
            <table border="1">
                <!-- Row 1: Merged Header (Spans 3 rows physically in Excel) -->
                <tr style="height: 100px;">
                    <!-- Cols A, B -->
                    <td colspan="2" rowspan="3" align="center" valign="middle" style="background-color: #000000;">
                        <img src="${baseUrl}/assets/img/asi.png" height="90" width="auto" alt="ASI">
                    </td>
                    <!-- Cols C, D, E, F, G -->
                    <td colspan="5" rowspan="3" align="center" valign="middle" style="background-color: #ffffff;">
                        <div style="font-size: 20px; font-weight: bold; color: #39A900;">REPORTE DE ASISTENCIAS - ASISTNET</div>
                        <div style="font-size: 14px; font-weight: bold; color: #39A900; margin-top: 5px;">Generado: ${new Date().toLocaleDateString()}</div>
                    </td>
                    <!-- Col H -->
                    <td colspan="1" rowspan="3" align="center" valign="middle" style="background-color: #000000;">
                        <img src="${baseUrl}/assets/img/logosena.png" height="90" width="auto" alt="SENA">
                    </td>
                </tr>
                <!-- Empty Rows 2, 3 to consume the rowspan -->
                <tr></tr>
                <tr></tr>

                <!-- Row 4: Headers -->
                <tr>
                    <th style="background-color: #39A900; color: #ffffff;">FECHA</th>
                    <th style="background-color: #39A900; color: #ffffff;">FICHA</th>
                    <th style="background-color: #39A900; color: #ffffff;">DOCUMENTO</th>
                    <th style="background-color: #39A900; color: #ffffff;">APELLIDOS</th>
                    <th style="background-color: #39A900; color: #ffffff;">NOMBRES</th>
                    <th style="background-color: #39A900; color: #ffffff;">ESTADO</th>
                    <th style="background-color: #39A900; color: #ffffff;">HORA LLEGADA</th>
                    <th style="background-color: #39A900; color: #ffffff;">OBSERVACIONES</th>
                </tr>
    `;

    asistenciasActuales.forEach(a => {
        let bgColor = '#ffffff';
        let color = '#000000';
        let fontWeight = 'normal';

        const est = a.estado.toLowerCase();
        if (est.includes('ausente')) {
            bgColor = '#ffe4e6'; color = '#d32f2f'; fontWeight = 'bold'; // Rojo Oscuro sobre Rosa
        }
        else if (est.includes('retardo') || est.includes('retardado')) {
            bgColor = '#FFFF00'; color = '#000000'; fontWeight = 'bold'; // Amarillo Puck
        }
        else if (est.includes('presente') || est.includes('temprano')) {
            bgColor = '#ecfccb'; color = '#2e7d32'; fontWeight = 'bold'; // Verde Oscuro sobre Verde Claro
        }
        else if (est.includes('excusa')) {
            bgColor = '#d1fae5'; color = '#39A900'; fontWeight = 'bold';
        }

        table += `
            <tr>
                <td>${formatearFecha(a.fecha)}</td>
                <td>${a.numero_ficha || ''}</td>
                <td>${a.documento_aprendiz || ''}</td>
                <td>${a.apellido || ''}</td>
                <td>${a.nombre || ''}</td>
                <td style="background-color: ${bgColor}; color: ${color}; font-weight: ${fontWeight}; text-align: center;">${a.estado}</td>
                <td style="text-align: center;">${formatearHora(a.creado_en)}</td>
                <td>${a.observaciones || ''}</td>
            </tr>
        `;
    });

    table += '</table></body></html>';

    // BOM para UTF-8 charset (\uFEFF)
    const blob = new Blob(['\uFEFF', table], { type: 'application/vnd.ms-excel;charset=utf-8' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = `Asistencias_SENA_${new Date().toISOString().split('T')[0]}.xls`;
    link.click();
}

function exportarPDF() {
    if (asistenciasActuales.length === 0) return mostrarNotificacion('No hay datos', 'error');

    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();

    // Logos
    if (logoBase64) doc.addImage(logoBase64, 'PNG', 15, 15, 30, 15);
    if (logoSenaBase64) doc.addImage(logoSenaBase64, 'PNG', 170, 10, 25, 25);

    // Título
    doc.setFont("helvetica", "bold");
    doc.setFontSize(16);
    doc.setTextColor(57, 169, 0); // SENA Green
    doc.text("REPORTE DE ASISTENCIAS", 105, 25, { align: "center" });

    doc.setFontSize(10);
    doc.setTextColor(100);
    doc.text(`Generado: ${new Date().toLocaleDateString()}`, 105, 32, { align: "center" });

    // Tabla
    const columns = ["Fecha", "Ficha", "Documento", "Apellidos", "Nombres", "Estado", "Hora", "Obs"];
    const rows = asistenciasActuales.map(a => [
        formatearFecha(a.fecha),
        a.numero_ficha || '',
        a.documento_aprendiz || '',
        a.apellido || '',
        a.nombre || '',
        a.estado || '',
        formatearHora(a.creado_en),
        a.observaciones || ''
    ]);

    doc.autoTable({
        head: [columns],
        body: rows,
        startY: 40,
        styles: { fontSize: 8, cellPadding: 2 },
        headStyles: { fillColor: [57, 169, 0], textColor: 255 },
        didParseCell: function (data) {
            if (data.section === 'body' && data.column.index === 5) {
                const est = data.cell.raw.toLowerCase();
                if (est.includes('ausente')) {
                    data.cell.styles.textColor = [229, 62, 62]; // Rojo
                    data.cell.styles.fontStyle = 'bold';
                } else if (est.includes('retardo') || est.includes('retardado')) {
                    data.cell.styles.textColor = [183, 149, 11]; // Amarillo Oscuro (Gold) para legibilidad
                    data.cell.styles.fontStyle = 'bold';
                } else if (est.includes('presente') || est.includes('temprano')) {
                    data.cell.styles.textColor = [77, 160, 10]; // Verde Biche Oscuro
                    data.cell.styles.fontStyle = 'bold';
                } else if (est.includes('excusa')) {
                    data.cell.styles.textColor = [57, 169, 0]; // Verde SENA
                    data.cell.styles.fontStyle = 'bold';
                }
            }
            // Resaltar hora de llegada
            if (data.section === 'body' && data.column.index === 6) {
                data.cell.styles.fontStyle = 'bold';
                data.cell.styles.halign = 'center';
            }
        }
    });

    doc.save(`Reporte_Asistencia_${new Date().toISOString().split('T')[0]}.pdf`);
}

function exportarCSV() {
    if (asistenciasActuales.length === 0) return mostrarNotificacion('No hay datos', 'error');

    let csv = "FECHA,FICHA,DOCUMENTO,APELLIDOS,NOMBRES,ESTADO,HORA_LLEGADA,OBSERVACIONES\n";

    asistenciasActuales.forEach(a => {
        csv += [
            formatearFecha(a.fecha),
            a.numero_ficha,
            a.documento_aprendiz,
            a.apellido,
            a.nombre,
            a.estado,
            formatearHora(a.creado_en),
            `"${a.observaciones || ''}"`
        ].join(",") + "\n";
    });

    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = `Asistencias_${new Date().toISOString().split('T')[0]}.csv`;
    link.click();
}

function mostrarNotificacion(mensaje, tipo = 'info') {
    const toast = document.createElement('div');
    toast.textContent = mensaje;
    toast.className = `toast-notification toast-${tipo === 'info' ? 'blue' : tipo}`;
    document.body.appendChild(toast);
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(100%)';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Utility debounce
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

