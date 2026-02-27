/**
 * Gestión de Fichas - Admin
 */

let todasFichas = [];
let paginaActual = 1;
let totalPaginas = 1;
const ITEMS_POR_PAGINA = 10;

document.addEventListener('DOMContentLoaded', async () => {
    await Promise.all([cargarProgramas(), cargarInstructores(), cargarTiposFormacion(), cargarEstados()]);
    cargarFichas();

    // Event listeners para filtros
    document.getElementById('filtroSearch')?.addEventListener('input', debounce(aplicarFiltros, 500));
    document.getElementById('filtroPrograma')?.addEventListener('change', aplicarFiltros);
    document.getElementById('filtroEstado')?.addEventListener('change', aplicarFiltros);
});

async function cargarProgramas() {
    try {
        const response = await fetch('api/programas.php');
        const result = await response.json();

        if (result.success) {
            const select = document.getElementById('programa');
            const filtroSelect = document.getElementById('filtroPrograma');

            select.innerHTML = '<option value="">Seleccione...</option>';
            if (filtroSelect) filtroSelect.innerHTML = '<option value="">Todos los Programas</option>';

            result.data.forEach(p => {
                const option = document.createElement('option');
                option.value = p.nombre_programa;
                option.textContent = p.nombre_programa;
                select.appendChild(option);

                if (filtroSelect) {
                    const filterOption = option.cloneNode(true);
                    filtroSelect.appendChild(filterOption);
                }
            });
        }
    } catch (error) {
        console.error('Error cargando programas:', error);
    }
}

let todosInstructores = []; // Variable global para almacenar instructores
let todosTiposFormacion = []; // Variable global para almacenar tipos de formación
let todosEstados = []; // Variable global para almacenar estados

async function cargarEstados() {
    try {
        const response = await fetch('api/fichas.php?action=listStates');
        const result = await response.json();
        if (result.success) {
            todosEstados = result.data;
            // Actualizar filtro si existe
            const filtro = document.getElementById('filtroEstado');
            if (filtro) {
                filtro.innerHTML = '<option value="">Todos los Estados</option>';
                result.data.forEach(e => {
                    filtro.insertAdjacentHTML('beforeend', `<option value="${e.nombre}">${e.nombre}</option>`);
                });
            }
            // Actualizar selector en modal si existe
            const selectModal = document.getElementById('estadoFicha');
            if (selectModal) {
                selectModal.innerHTML = '';
                result.data.forEach(e => {
                    selectModal.insertAdjacentHTML('beforeend', `<option value="${e.nombre}">${e.nombre}</option>`);
                });
            }
        }
    } catch (error) {
        console.error('Error loading states:', error);
    }
}

async function cargarTiposFormacion() {
    try {
        const response = await fetch('api/fichas.php?action=listTypes');
        const result = await response.json();
        if (result.success) {
            todosTiposFormacion = result.data;
            const select = document.getElementById('tipoFormacionSelect');
            if (select) {
                select.innerHTML = '<option value="">Seleccione...</option>';
                result.data.forEach(t => {
                    const option = document.createElement('option');
                    option.value = t.nombre;
                    option.textContent = t.nombre;
                    select.appendChild(option);
                });
            }
        }
    } catch (error) {
        console.error('Error loading training types:', error);
    }
}

async function cargarInstructores() {
    try {
        const response = await fetch('api/usuarios.php?rol=instructor&limit=-1');
        const result = await response.json();

        if (result.success) {
            todosInstructores = result.data; // Guardar globalmente

            const select = document.getElementById('instructorLider');
            select.innerHTML = '<option value="">Seleccione...</option>';

            result.data.forEach(i => {
                const option = document.createElement('option');
                option.value = i.id_usuario;
                option.textContent = `${i.nombre} ${i.apellido}`;
                select.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Error cargando instructores:', error);
    }
}

async function cargarFichas(pagina = 1) {
    try {
        const search = document.getElementById('filtroSearch')?.value || '';
        const programa = document.getElementById('filtroPrograma')?.value || '';
        const estado = document.getElementById('filtroEstado')?.value || '';
        const response = await fetch('api/fichas.php?limit=-1');
        const result = await response.json();

        if (result.success) {
            let fichas = result.data;

            // Filtrado en cliente
            if (search) {
                fichas = fichas.filter(f => f.numero_ficha.toString().includes(search));
            }
            if (programa) {
                fichas = fichas.filter(f => f.nombre_programa === programa);
            }
            if (estado) {
                fichas = fichas.filter(f => f.estado === estado);
            }

            // Paginación en cliente
            const total = fichas.length;
            totalPaginas = Math.ceil(total / ITEMS_POR_PAGINA);
            paginaActual = pagina;

            const start = (pagina - 1) * ITEMS_POR_PAGINA;
            const end = start + ITEMS_POR_PAGINA;
            const fichasPaginadas = fichas.slice(start, end);

            todasFichas = fichas; // Guardar para exportar
            mostrarFichas(fichasPaginadas);
            actualizarPaginacion(pagina, totalPaginas, total);
        }
    } catch (error) {
        console.error('Error cargando fichas:', error);
        mostrarNotificacion('Error al cargar fichas', 'error');
    }
}

function aplicarFiltros() {
    cargarFichas(1);
}

function cambiarPagina(pagina) {
    if (pagina < 1 || pagina > totalPaginas) return;
    cargarFichas(pagina);
}

function actualizarPaginacion(pagina, paginas, total) {
    const paginacionDiv = document.getElementById('paginacion');
    if (!paginacionDiv || paginas <= 1) {
        if (paginacionDiv) paginacionDiv.innerHTML = '';
        return;
    }

    let html = `<div class="pagination-wrapper">`;

    // Botón Anterior
    html += `<button onclick="cambiarPagina(${pagina - 1})" 
             class="btn-pagination ${pagina === 1 ? 'disabled' : ''}" 
             ${pagina === 1 ? 'disabled' : ''} title="Anterior"><i class="fas fa-chevron-left"></i></button>`;

    // Números de página
    const maxVisible = 5;
    let start = Math.max(1, pagina - 2);
    let end = Math.min(paginas, start + maxVisible - 1);

    if (end - start < maxVisible - 1) {
        start = Math.max(1, end - maxVisible + 1);
    }

    if (start > 1) {
        html += `<button onclick="cambiarPagina(1)" class="btn-pagination">1</button>`;
        if (start > 2) html += `<span class="pagination-ellipsis">...</span>`;
    }

    for (let i = start; i <= end; i++) {
        html += `<button onclick="cambiarPagina(${i})" class="btn-pagination ${i === pagina ? 'active' : ''}">${i}</button>`;
    }

    if (end < paginas) {
        if (end < paginas - 1) html += `<span class="pagination-ellipsis">...</span>`;
        html += `<button onclick="cambiarPagina(${paginas})" class="btn-pagination">${paginas}</button>`;
    }

    // Botón Siguiente
    html += `<button onclick="cambiarPagina(${pagina + 1})" 
             class="btn-pagination ${pagina === paginas ? 'disabled' : ''}" 
             ${pagina === paginas ? 'disabled' : ''} title="Siguiente"><i class="fas fa-chevron-right"></i></button>`;

    html += `</div>`;
    paginacionDiv.innerHTML = html;
}

function mostrarFichas(fichas) {
    const tbody = document.getElementById('tablaFichas');

    if (!fichas || fichas.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align: center;">No hay fichas registradas</td></tr>';
        return;
    }

    tbody.innerHTML = fichas.map(f => {
        const claseEstado = `status-${(f.estado || '').toLowerCase().replace(/ /g, '-')}`;
        return `
        <tr>
            <td style="font-weight: bold;">${f.numero_ficha}</td>
            <td>${f.nombre_programa || 'N/A'}</td>
            <td>
                <select onchange="cambiarTipoFormacion('${f.numero_ficha}', this.value)"
                        class="form-control"
                        style="padding: 5px; font-size: 0.85rem; width: 100%;">
                    <option value="">Sin asignar</option>
                    ${todosTiposFormacion.map(t => `
                        <option value="${t.nombre}" ${f.tipoFormacion == t.nombre ? 'selected' : ''}>
                            ${t.nombre}
                        </option>
                    `).join('')}
                </select>
            </td>
            <td>${f.jornada || 'N/A'}</td>
            <td>
                <select onchange="cambiarInstructorLider('${f.numero_ficha}', this.value)" 
                        class="form-control" 
                        style="padding: 5px; font-size: 0.85rem; width: 100%;">
                    <option value="">Sin asignar</option>
                    ${todosInstructores.map(i => `
                        <option value="${i.id_usuario}" ${f.instructor_lider == i.id_usuario ? 'selected' : ''}>
                            ${i.nombre} ${i.apellido}
                        </option>
                    `).join('')}
                </select>
            </td>
            <!-- Vocero Principal -->
            <td>
                <select id="vocero-principal-${f.numero_ficha}"
                        onfocus="cargarCandidatosVocero('${f.numero_ficha}', this)"
                        onchange="cambiarVocero('${f.numero_ficha}', 'vocero_principal', this.value)"
                        class="form-control"
                        style="padding: 5px; font-size: 0.85rem; width: 100%;">
                    <option value="${f.id_vocero_principal || ''}">${f.nombre_vocero_principal || 'Sin asignar'}</option>
                </select>
            </td>
             <!-- Vocero Suplente -->
            <td>
                 <select id="vocero-suplente-${f.numero_ficha}"
                        onfocus="cargarCandidatosVocero('${f.numero_ficha}', this)"
                        onchange="cambiarVocero('${f.numero_ficha}', 'vocero_suplente', this.value)"
                        class="form-control"
                        style="padding: 5px; font-size: 0.85rem; width: 100%;">
                    <option value="${f.id_vocero_suplente || ''}">${f.nombre_vocero_suplente || 'Sin asignar'}</option>
                </select>
            </td>
            <td>
                <select onchange="cambiarEstadoFicha('${f.numero_ficha}', this)" 
                        class="form-control status-select ${claseEstado}">
                    ${todosEstados.map(e => {
            const claseOpcion = `status-${e.nombre.toLowerCase().replace(/ /g, '-')}`;
            return `<option value="${e.nombre}" class="${claseOpcion}" ${f.estado === e.nombre ? 'selected' : ''}>${e.nombre}</option>`;
        }).join('')}
                </select>
            </td>
        </tr>
    `}).join('');
}

function esActivo(e) {
    if (!e) return false;
    // Normalizamos: 'activa', 'activo', 'lectiva' -> true
    const val = e.toString().trim().toLowerCase();
    return val === 'activa' || val === 'activo' || val === 'lectiva';
}

function getColorEstado(e) {
    if (!e) return '#ef4444';
    if (esActivo(e)) return '#39A900'; // SENA Green
    return '#ef4444'; // Red for everything else (Finalizada, Cancelada, etc)
}

// Variables caché para no recargar la lista de una ficha múltiples veces
const cacheAprendicesFicha = {};

async function cargarCandidatosVocero(ficha, selectElement) {
    // Si ya tiene más de 1 opción, asumimos que ya cargó
    if (selectElement.options.length > 1) return;

    // Mostrar estado de carga en el primer option
    const originalText = selectElement.options[0].text;
    const currentValue = selectElement.value;
    selectElement.options[0].text = "Cargando...";

    try {
        // Verificar caché
        if (!cacheAprendicesFicha[ficha]) {
            // Traer LECTIVA y otros estados activos
            const res = await fetch(`api/aprendices.php?ficha=${ficha}&limit=-1`); // Traer todos y filtrar aqui
            const data = await res.json();
            if (data.success) {
                // Filtrar solo activos para ser voceros
                const inactivos = ['RETIRO', 'CANCELADO', 'RETIRADO', 'FINALIZADO', 'TRASLADO', 'APLAZADO', 'CANCELADA', 'FINALIZADA'];
                cacheAprendicesFicha[ficha] = data.data.filter(a => !inactivos.includes((a.estado || '').toUpperCase()));
            } else {
                cacheAprendicesFicha[ficha] = [];
            }
        }

        const aprendices = cacheAprendicesFicha[ficha];

        // Limpiar y reconstruir
        selectElement.innerHTML = `<option value="">Sin asignar</option>`;

        aprendices.forEach(a => {
            const opt = document.createElement('option');
            opt.value = a.documento;
            opt.textContent = `${a.nombre} ${a.apellido}`;
            if (String(a.documento) === String(currentValue)) opt.selected = true;
            selectElement.appendChild(opt);
        });

        // Si el valor actual no está en la lista (ej. ya no está EN FORMACION), mantenerlo visualmente
        if (currentValue && !Array.from(selectElement.options).some(o => o.value == currentValue)) {
            const opt = document.createElement('option');
            opt.value = currentValue;
            opt.textContent = originalText; // Nombre original que venía del servidor
            opt.selected = true;
            opt.style.color = 'red'; // Indicar anomalía
            selectElement.appendChild(opt);
        }

    } catch (e) {
        console.error(e);
        selectElement.options[0].text = "Error al cargar";
    }
}

async function cambiarVocero(ficha, tipo, documento) {
    try {
        // Validar duplicidad localmente antes de enviar al servidor
        const otroTipo = tipo === 'vocero_principal' ? 'vocero_suplente' : 'vocero_principal';
        const idOtroSelect = tipo === 'vocero_principal' ? `vocero-suplente-${ficha}` : `vocero-principal-${ficha}`;
        const otroSelect = document.getElementById(idOtroSelect);

        if (documento && otroSelect && otroSelect.value === documento) {
            const rolActual = tipo === 'vocero_principal' ? 'Principal' : 'Suplente';
            const rolOtro = otroTipo === 'vocero_principal' ? 'Principal' : 'Suplente';
            mostrarNotificacion(`El aprendiz ya está asignado como Vocero ${rolOtro}. No puede ser ambos.`, 'error');
            cargarFichas(paginaActual); // Revertir visualmente
            return;
        }

        const body = { numero_ficha: ficha };
        body[tipo] = documento || null;

        const response = await fetch('api/fichas.php', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body)
        });

        const result = await response.json();

        if (result.success) {
            mostrarNotificacion('Vocero actualizado correctamente', 'success');
            // No recargamos toda la tabla para no perder el foco/estado, el select ya cambió
        } else {
            mostrarNotificacion(result.message, 'error');
            cargarFichas(paginaActual); // Revertir
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarNotificacion('Error al asignar vocero', 'error');
    }
}

function generarOpcionesInstructor(instructorActual, opcionesBase) {
    // Crear un elemento temporal para manipular las opciones
    const div = document.createElement('div');
    div.innerHTML = `<select>${opcionesBase}</select>`;
    const select = div.firstChild;

    // Marcar la opción seleccionada
    if (instructorActual) {
        const option = select.querySelector(`option[value="${instructorActual}"]`);
        if (option) option.selected = true;
    }

    return select.innerHTML;
}

async function cambiarInstructorLider(numeroFicha, idInstructor) {
    try {
        const response = await fetch('api/fichas.php', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                numero_ficha: numeroFicha,
                instructor_lider: idInstructor || null
            })
        });

        const result = await response.json();

        if (result.success) {
            mostrarNotificacion('Instructor líder actualizado', 'success');
            mostrarNotificacion(result.message, 'error');
            cargarFichas(paginaActual); // Revertir cambio visual
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarNotificacion('Error al asignar instructor', 'error');
        cargarFichas(paginaActual);
    }
}

function nuevaFicha() {
    document.getElementById('modalTitle').textContent = 'Nueva Ficha';
    document.getElementById('formFicha').reset();
    document.getElementById('modalFicha').style.display = 'flex';
}

function cerrarModal() {
    document.getElementById('modalFicha').style.display = 'none';
}

async function guardarFicha(event) {
    event.preventDefault();

    const formData = {
        numero_ficha: document.getElementById('numeroFicha').value,
        nombre_programa: document.getElementById('programa').value,
        jornada: document.getElementById('jornada').value,
        estado: document.getElementById('estadoFicha').value,
        instructor_lider: document.getElementById('instructorLider').value,
        tipoFormacion: document.getElementById('tipoFormacionSelect').value
    };

    try {
        const response = await fetch('api/fichas.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(formData)
        });

        const result = await response.json();

        if (result.success) {
            mostrarNotificacion('Ficha guardada exitosamente', 'success');
            cerrarModal();
            cargarFichas();
        } else {
            mostrarNotificacion(result.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarNotificacion('Error al guardar ficha', 'error');
    }
}

async function eliminarFicha(numero) {
    if (!confirm('¿Está seguro de eliminar esta ficha?')) return;

    try {
        const response = await fetch(`api/fichas.php?numero=${numero}`, {
            method: 'DELETE'
        });

        const result = await response.json();

        if (result.success) {
            mostrarNotificacion('Ficha eliminada', 'success');
            cargarFichas();
        } else {
            mostrarNotificacion(result.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarNotificacion('Error al eliminar ficha', 'error');
    }
}

async function cambiarEstadoFicha(numeroFicha, selectElement) {
    const nuevoEstado = selectElement.value;
    try {
        const response = await fetch('api/fichas.php', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                numero_ficha: numeroFicha,
                estado: nuevoEstado
            })
        });

        const result = await response.json();

        if (result.success) {
            mostrarNotificacion('Estado actualizado', 'success');
            // Actualizar clase visualmente
            const nuevaClase = `status-${nuevoEstado.toLowerCase().replace(/ /g, '-')}`;
            // Remover clases anteriores de status-
            const clases = selectElement.className.split(' ').filter(c => !c.startsWith('status-') || c === 'status-select');
            selectElement.className = clases.join(' ') + ' ' + nuevaClase;
        } else {
            mostrarNotificacion(result.message, 'error');
            cargarFichas(); // Recargar para revertir el cambio visual
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarNotificacion('Error al actualizar estado', 'error');
        cargarFichas();
    }
}


async function cambiarTipoFormacion(numeroFicha, nuevoTipo) {
    try {
        const response = await fetch('api/fichas.php', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                numero_ficha: numeroFicha,
                tipoFormacion: nuevoTipo
            })
        });

        const result = await response.json();

        if (result.success) {
            mostrarNotificacion('Tipo de formación actualizado', 'success');
        } else {
            mostrarNotificacion(result.message, 'error');
            cargarFichas(paginaActual); // Revertir
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarNotificacion('Error al actualizar tipo', 'error');
        cargarFichas(paginaActual);
    }
}

async function exportarFichas() {
    try {
        mostrarNotificacion('Generando reporte...', 'info');

        // Obtener filtros actuales
        const search = document.getElementById('filtroSearch')?.value || '';
        const programa = document.getElementById('filtroPrograma')?.value || '';
        const estado = document.getElementById('filtroEstado')?.value || '';

        // Solicitar TODOS los datos (limit=-1)
        let url = `api/fichas.php?limit=-1`;
        if (search) url += `&search=${encodeURIComponent(search)}`;
        if (programa) url += `&programa=${encodeURIComponent(programa)}`;
        if (estado) url += `&estado=${encodeURIComponent(estado)}`;

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
                        <th>Número Ficha</th>
                        <th>Programa</th>
                        <th>Jornada</th>
                        <th>Instructor Líder</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
        `;

        result.data.forEach(f => {
            table += `
                <tr>
                    <td>${f.numero_ficha}</td>
                    <td>${f.nombre_programa}</td>
                    <td>${f.jornada}</td>
                    <td>${f.nombre_instructor || 'Sin asignar'}</td>
                    <td>${f.estado}</td>
                </tr>
            `;
        });

        table += '</tbody></table>';

        const blob = new Blob(['\uFEFF' + table], { type: 'application/vnd.ms-excel;charset=utf-8' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = `Fichas_${new Date().toISOString().split('T')[0]}.xls`;
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
