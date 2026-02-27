/**
 * Consultar Asistencias - Admin
 */

let asistenciasActuales = [];
const ITEMS_POR_PAGINA = 10;

document.addEventListener('DOMContentLoaded', () => {
    cargarFichas();

    // Establecer fechas por defecto (último mes)
    const hoy = new Date();
    const haceUnMes = new Date(hoy);
    haceUnMes.setMonth(haceUnMes.getMonth() - 1);

    const fechaHoySt = hoy.toISOString().split('T')[0];
    const fechaInicioSt = haceUnMes.toISOString().split('T')[0];

    const inputInicio = document.getElementById('filtroFechaInicio');
    const inputFin = document.getElementById('filtroFechaFin');

    inputInicio.value = fechaInicioSt;
    inputFin.value = fechaHoySt;

    // Establecer límites
    inputInicio.max = fechaHoySt;
    inputFin.max = fechaHoySt;

    // Validaciones en tiempo real
    inputInicio.addEventListener('change', function () {
        const fechaInicio = new Date(this.value);
        const fechaFin = new Date(inputFin.value);
        const hoy = new Date();
        hoy.setHours(0, 0, 0, 0);

        // Ajustar zona horaria
        const fechaInicioAjustada = new Date(fechaInicio.getTime() + fechaInicio.getTimezoneOffset() * 60000);

        if (fechaInicioAjustada > hoy) {
            mostrarNotificacion('La fecha de inicio no puede ser futura', 'error');
            this.value = fechaHoySt;
            return;
        }

        if (inputFin.value && fechaInicio > fechaFin) {
            mostrarNotificacion('La fecha de inicio no puede ser mayor que la fecha fin', 'error');
            this.value = inputFin.value;
        }

        inputFin.min = this.value;
    });

    inputFin.addEventListener('change', function () {
        const fechaInicio = new Date(inputInicio.value);
        const fechaFin = new Date(this.value);
        const hoy = new Date();
        hoy.setHours(0, 0, 0, 0);

        // Ajustar zona horaria
        const fechaFinAjustada = new Date(fechaFin.getTime() + fechaFin.getTimezoneOffset() * 60000);

        if (fechaFinAjustada > hoy) {
            mostrarNotificacion('La fecha fin no puede ser futura', 'error');
            this.value = fechaHoySt;
            return;
        }

        if (inputInicio.value && fechaFin < fechaInicio) {
            mostrarNotificacion('La fecha fin no puede ser menor que la fecha inicio', 'error');
            this.value = inputInicio.value;
        }

        inputInicio.max = this.value;
    });
});

async function cargarFichas() {
    try {
        const response = await fetch('api/fichas.php?limit=-1');
        const result = await response.json();

        if (result.success) {
            const select = document.getElementById('filtroFicha');
            select.innerHTML = '<option value="">Todas las fichas</option>';

            result.data.forEach(f => {
                const option = document.createElement('option');
                option.value = f.numero_ficha;
                option.textContent = `${f.numero_ficha} - ${f.nombre_programa || 'Sin programa'}`;
                select.appendChild(option);
            });
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
            mostrarNotificacion(`${result.pagination.total} registros encontrados`, 'success');
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

function mostrarResultados(datos) {
    const tbody = document.getElementById('tablaResultados');

    if (!datos || datos.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 40px; color: #666;">No se encontraron resultados</td></tr>';
        return;
    }

    tbody.innerHTML = datos.map(a => `
        <tr>
            <td>${formatearFecha(a.fecha)}</td>
            <td>${a.numero_ficha || '-'}</td>
            <td>${a.documento_aprendiz || '-'}</td>
            <td>${a.apellido || ''} ${a.nombre || ''}</td>
            <td>${getEstadoBadge(a.estado)}</td>
            <td>${a.observaciones || '-'}</td>
        </tr>
    `).join('');
}

function getEstadoBadge(estado) {
    const badges = {
        'Presente': '<span class="badge badge-success">✓ Presente</span>',
        'Ausente': '<span class="badge badge-danger">✗ Ausente</span>',
        'Justificado': '<span class="badge badge-warning">! Justificado</span>'
    };
    return badges[estado] || estado;
}

function formatearFecha(fecha) {
    if (!fecha) return '-';
    const d = new Date(fecha);
    return d.toLocaleDateString('es-ES', { year: 'numeric', month: '2-digit', day: '2-digit' });
}

async function exportarExcel() {
    try {
        mostrarNotificacion('Generando reporte...', 'info');

        const ficha = document.getElementById('filtroFicha').value;
        const fechaInicio = document.getElementById('filtroFechaInicio').value;
        const fechaFin = document.getElementById('filtroFechaFin').value;

        // Solicitar TODOS los datos (limit=-1)
        let url = 'api/asistencias.php?limit=-1';
        if (ficha) url += `&ficha=${ficha}`;
        if (fechaInicio) url += `&fecha_inicio=${fechaInicio}`;
        if (fechaFin) url += `&fecha_fin=${fechaFin}`;

        const response = await fetch(url);
        const result = await response.json();

        if (!result.success || !result.data || result.data.length === 0) {
            mostrarNotificacion('No hay datos para exportar', 'warning');
            return;
        }

        let table = `
            <table border="1">
                <thead>
                    <tr style="background-color: #39A900; color: white;">
                        <th>Fecha</th>
                        <th>Ficha</th>
                        <th>Documento</th>
                        <th>Apellidos</th>
                        <th>Nombres</th>
                        <th>Estado</th>
                        <th>Observaciones</th>
                    </tr>
                </thead>
                <tbody>
        `;

        result.data.forEach(a => {
            table += `
                <tr>
                    <td>${formatearFecha(a.fecha)}</td>
                    <td>${a.numero_ficha || ''}</td>
                    <td>${a.documento_aprendiz || ''}</td>
                    <td>${a.apellido || ''}</td>
                    <td>${a.nombre || ''}</td>
                    <td>${a.estado || ''}</td>
                    <td>${a.observaciones || ''}</td>
                </tr>
            `;
        });

        table += '</tbody></table>';

        const blob = new Blob(['\uFEFF' + table], { type: 'application/vnd.ms-excel;charset=utf-8' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = `Asistencias_Admin_${new Date().toISOString().split('T')[0]}.xls`;
        link.click();

        mostrarNotificacion('Archivo exportado exitosamente', 'success');
    } catch (error) {
        console.error('Error exportando:', error);
        mostrarNotificacion('Error al exportar datos', 'error');
    }
}

function mostrarNotificacion(mensaje, tipo = 'info') {
    const toast = document.createElement('div');
    toast.textContent = mensaje;
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        background: ${tipo === 'success' ? '#10b981' : tipo === 'error' ? '#ef4444' : '#3b82f6'};
        color: white;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        z-index: 10000;
    `;

    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
}
