/**
 * Gestión de Programas - Admin
 */

let currentPage = 1;
const itemsPerPage = 10;

document.addEventListener('DOMContentLoaded', () => {
    cargarProgramas();

    // Event listeners para filtros
    document.getElementById('filtroSearch').addEventListener('input', debounce(() => {
        currentPage = 1;
        cargarProgramas();
    }, 300));

    document.getElementById('filtroNivel').addEventListener('change', () => {
        currentPage = 1;
        cargarProgramas();
    });
});

async function cargarProgramas() {
    try {
        const search = document.getElementById('filtroSearch').value;
        const nivel = document.getElementById('filtroNivel').value;

        let url = `api/programas.php?page=${currentPage}&limit=${itemsPerPage}`;
        if (search) url += `&search=${encodeURIComponent(search)}`;
        if (nivel) url += `&nivel=${encodeURIComponent(nivel)}`;

        const response = await fetch(url);
        const result = await response.json();

        if (result.success) {
            mostrarProgramas(result.data);
            renderPagination(result.pagination);
        }
    } catch (error) {
        console.error('Error cargando programas:', error);
        mostrarNotificacion('Error al cargar programas', 'error');
    }
}

function mostrarProgramas(programas) {
    const tbody = document.getElementById('tablaProgramas');

    if (!programas || programas.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" style="text-align: center;">No se encontraron programas</td></tr>';
        return;
    }

    // Calcular el índice inicial basado en la página actual
    const startIndex = (currentPage - 1) * itemsPerPage;

    tbody.innerHTML = programas.map((p, index) => `
        <tr>
            <td style="text-align: center; font-weight: 600; color: #666;">${startIndex + index + 1}</td>
            <td>${p.nombre_programa}</td>
            <td>${p.nivel_formacion || 'N/A'}</td>
            <td style="text-align: center;">
                <button onclick="eliminarPrograma('${p.nombre_programa}')" class="btn-icon btn-danger" title="Eliminar">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </button>
            </td>
        </tr>
    `).join('');
}

function renderPagination(pagination) {
    const container = document.getElementById('paginacion');
    if (!container || pagination.pages <= 1) {
        if (container) container.innerHTML = '';
        return;
    }

    let html = `<div class="pagination-wrapper">`;

    // Botón Anterior
    html += `<button onclick="cambiarPagina(${pagination.page - 1})" 
             class="btn-pagination ${pagination.page === 1 ? 'disabled' : ''}" 
             ${pagination.page === 1 ? 'disabled' : ''} title="Anterior"><i class="fas fa-chevron-left"></i></button>`;

    // Números de página
    const maxVisible = 5;
    let start = Math.max(1, pagination.page - 2);
    let end = Math.min(pagination.pages, start + maxVisible - 1);

    if (end - start < maxVisible - 1) {
        start = Math.max(1, end - maxVisible + 1);
    }

    if (start > 1) {
        html += `<button onclick="cambiarPagina(1)" class="btn-pagination">1</button>`;
        if (start > 2) html += `<span class="pagination-ellipsis">...</span>`;
    }

    for (let i = start; i <= end; i++) {
        html += `<button onclick="cambiarPagina(${i})" class="btn-pagination ${i === pagination.page ? 'active' : ''}">${i}</button>`;
    }

    if (end < pagination.pages) {
        if (end < pagination.pages - 1) html += `<span class="pagination-ellipsis">...</span>`;
        html += `<button onclick="cambiarPagina(${pagination.pages})" class="btn-pagination">${pagination.pages}</button>`;
    }

    // Botón Siguiente
    html += `<button onclick="cambiarPagina(${pagination.page + 1})" 
             class="btn-pagination ${pagination.page === pagination.pages ? 'disabled' : ''}" 
             ${pagination.page === pagination.pages ? 'disabled' : ''} title="Siguiente"><i class="fas fa-chevron-right"></i></button>`;

    html += `</div>`;
    container.innerHTML = html;
}

function cambiarPagina(page) {
    currentPage = page;
    cargarProgramas();
}

function nuevoPrograma() {
    document.getElementById('modalTitle').textContent = 'Nuevo Programa';
    document.getElementById('formPrograma').reset();
    document.getElementById('modalPrograma').style.display = 'flex';
}

function cerrarModal() {
    document.getElementById('modalPrograma').style.display = 'none';
}

async function guardarPrograma(event) {
    event.preventDefault();

    const formData = {
        nombre_programa: document.getElementById('nombrePrograma').value,
        nivel_formacion: document.getElementById('nivelFormacion').value,
        duracion_meses: 0, // Valor por defecto ya que se eliminó el campo
        estado: 'Activo'
    };

    try {
        const response = await fetch('api/programas.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(formData)
        });

        const result = await response.json();

        if (result.success) {
            mostrarNotificacion('Programa guardado exitosamente', 'success');
            cerrarModal();
            cargarProgramas();
        } else {
            mostrarNotificacion(result.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarNotificacion('Error al guardar programa', 'error');
    }
}

async function eliminarPrograma(nombre) {
    if (!confirm('¿Está seguro de eliminar este programa?')) return;

    try {
        const response = await fetch(`api/programas.php?nombre=${encodeURIComponent(nombre)}`, {
            method: 'DELETE'
        });

        const result = await response.json();

        if (result.success) {
            mostrarNotificacion('Programa eliminado', 'success');
            cargarProgramas();
        } else {
            mostrarNotificacion(result.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarNotificacion('Error al eliminar programa', 'error');
    }
}

async function exportarProgramas() {
    try {
        mostrarNotificacion('Generando reporte...', 'info');

        // Obtener filtros actuales
        const search = document.getElementById('filtroSearch')?.value || '';
        const nivel = document.getElementById('filtroNivel')?.value || '';

        // Solicitar TODOS los datos (limit=-1)
        let url = `api/programas.php?limit=-1`;
        if (search) url += `&search=${encodeURIComponent(search)}`;
        if (nivel) url += `&nivel=${encodeURIComponent(nivel)}`;

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
                        <th>Nombre del Programa</th>
                        <th>Nivel de Formación</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
        `;

        result.data.forEach(p => {
            table += `
                <tr>
                    <td>${p.nombre_programa}</td>
                    <td>${p.nivel_formacion}</td>
                    <td>${p.estado}</td>
                </tr>
            `;
        });

        table += '</tbody></table>';

        const blob = new Blob(['\uFEFF' + table], { type: 'application/vnd.ms-excel;charset=utf-8' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = `Programas_${new Date().toISOString().split('T')[0]}.xls`;
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

// Función debounce para optimizar búsqueda
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
