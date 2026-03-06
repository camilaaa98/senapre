

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
        tbody.innerHTML = '<tr><td colspan="7" class="text-center color-muted">No se encontraron programas</td></tr>';
        return;
    }

    // Calcular el índice inicial basado en la página actual
    const startIndex = (currentPage - 1) * itemsPerPage;

    tbody.innerHTML = programas.map((p, index) => `
        <tr class="table-row-divider">
            <td class="td-index">${startIndex + index + 1}</td>
            <td class="font-bold">${p.nombre_programa}</td>
            <td><span class="badge-nivel">${p.nivel_formacion || 'N/A'}</span></td>
            <td><span class="badge-tipo ${p.tipo_oferta === 'Cerrada' ? 'tipo-cerrada' : 'tipo-abierta'}">${p.tipo_oferta || 'Abierta'}</span></td>
            <td class="td-mono">${p.hora_entrada || '--:--'}</td>
            <td class="td-mono">${p.hora_salida || '--:--'}</td>
            <td class="text-center">
                <div style="display: flex; gap: 8px; justify-content: center;">
                    <button onclick='editarPrograma(${JSON.stringify(p)})' class="btn-icon btn-primary" title="Editar" style="background: #00324D;">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button onclick="eliminarPrograma('${p.nombre_programa}')" class="btn-icon btn-danger" title="Eliminar">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </div>
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

function cerrarModal() {
    document.getElementById('modalPrograma').style.display = 'none';
}

function setHorarioJornada(jornada) {
    const entrada = document.getElementById('horaEntrada');
    const salida = document.getElementById('horaSalida');

    switch (jornada) {
        case 'Diurna':
            entrada.value = '06:00';
            salida.value = '12:00';
            break;
        case 'Tarde':
            entrada.value = '12:00';
            salida.value = '18:00';
            break;
        case 'Noche':
            entrada.value = '18:00';
            salida.value = '23:00'; // Mariana: Noche (6 pm - 11 pm)
            break;
    }
}

let editMode = false;

function nuevoPrograma() {
    editMode = false;
    document.getElementById('modalTitle').textContent = 'Nuevo Programa';
    document.getElementById('formPrograma').reset();
    document.getElementById('nombrePrograma').disabled = false;
    document.getElementById('modalPrograma').style.display = 'flex';
}

function editarPrograma(p) {
    editMode = true;
    document.getElementById('modalTitle').textContent = 'Editar Programa';
    document.getElementById('nombrePrograma').value = p.nombre_programa;
    document.getElementById('nombrePrograma').disabled = true;
    document.getElementById('nivelFormacion').value = p.nivel_formacion;
    document.getElementById('tipoOferta').value = p.tipo_oferta || 'Abierta';
    document.getElementById('horaEntrada').value = p.hora_entrada || '';
    document.getElementById('horaSalida').value = p.hora_salida || '';
    document.getElementById('modalPrograma').style.display = 'flex';
}

async function guardarPrograma(event) {
    event.preventDefault();

    const formData = {
        nombre_programa: document.getElementById('nombrePrograma').value,
        nivel_formacion: document.getElementById('nivelFormacion').value,
        tipo_oferta: document.getElementById('tipoOferta').value,
        hora_entrada: document.getElementById('horaEntrada').value,
        hora_salida: document.getElementById('horaSalida').value,
        duracion_meses: 0,
        estado: 'Activo'
    };

    try {
        const url = 'api/programas.php';
        const method = editMode ? 'PUT' : 'POST';

        const response = await fetch(url, {
            method: method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(formData)
        });

        const result = await response.json();

        if (result.success) {
            mostrarNotificacion(editMode ? 'Programa actualizado exitosamente' : 'Programa creado exitosamente', 'success');
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
                    <tr class="export-header">
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
    toast.className = `toast-notification toast-${tipo === 'info' ? 'blue' : tipo}`;
    document.body.appendChild(toast);
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(100%)';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
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
