let todosAprendices = [];
let paginaActual = 1;
let totalPaginas = 1;
if (typeof ITEMS_POR_PAGINA === 'undefined') {
    var ITEMS_POR_PAGINA = 10;
}
let filtroPoblacionActivo = '';
let todosEstados = [];
let chartPoblacionInstance = null;

async function cargarEstados() {
    try {
        const response = await fetch('api/fichas.php?action=listStates');
        const result = await response.json();
        if (result.success) {
            todosEstados = result.data;
            const filtro = document.getElementById('filtroEstado');
            if (filtro) {
                filtro.innerHTML = '<option value="">TODOS LOS ESTADOS</option>';
                result.data.forEach(e => {
                    filtro.insertAdjacentHTML('beforeend', `<option value="${e.nombre}">${e.nombre}</option>`);
                });
            }
            // Actualizar filtro GP si existe
            const filtroGP = document.getElementById('filtroEstadoGP');
            if (filtroGP) {
                filtroGP.innerHTML = '<option value="">TODOS LOS ESTADOS</option>';
                result.data.forEach(e => {
                    filtroGP.insertAdjacentHTML('beforeend', `<option value="${e.nombre}">${e.nombre}</option>`);
                });
            }
        }
    } catch (error) {
        console.error('Error loading states:', error);
    }
}

// ========== INICIALIZACIÓN ==========
document.addEventListener('DOMContentLoaded', () => {
    aplicarRestriccionesDePagina();
    cargarFichas();
    cargarEstados(); // Cargar estados
    cargarAprendices();
    cargarEstadisticasPoblacion(); // Cargar estadísticas y gráfica de población

    // Filtros
    // Filtros Principales
    document.getElementById('filtroSearch')?.addEventListener('input', debounce(() => cargarAprendices(1), 500));
    document.getElementById('filtroFicha')?.addEventListener('change', () => cargarAprendices(1));
    document.getElementById('filtroEstado')?.addEventListener('change', () => cargarAprendices(1));

    // Filtros Gestión Población (GP)
    document.getElementById('filtroSearchGP')?.addEventListener('input', debounce(() => cargarAprendicesGP(1), 500));
    document.getElementById('filtroFichaGP')?.addEventListener('change', () => cargarAprendicesGP(1));
    document.getElementById('filtroEstadoGP')?.addEventListener('change', () => cargarAprendicesGP(1));

    // Manejo de Hash para navegación inicial e interna
    const procesarHash = () => {
        const hash = window.location.hash || '#lista';
        const seccion = hash.replace('#', '');

        // Mapeo selectivo por si el hash no coincide exactamente con el ID
        const validas = ['lista', 'poblacion', 'gestion-poblacion', 'excusas', 'planes', 'inasistencias', 'retardos'];
        if (validas.includes(seccion)) {
            mostrarSeccion(null, seccion);
        } else {
            mostrarSeccion(null, 'lista');
        }
    };

    // Procesar hash inicial con un pequeño delay para asegurar carga de otros componentes
    setTimeout(procesarHash, 100);
});


// Función de filtrado que faltaba y rompía los filtros
function aplicarFiltros() {
    cargarAprendices(1);
}

function mostrarSeccion(event, seccionNombre) {
    if (event) event.preventDefault();



    // Ocultar todas las secciones
    document.querySelectorAll('.content-section').forEach(s => {
        s.style.display = 'none';
        s.classList.remove('active');
    });

    // Mostrar sección seleccionada
    const seccion = document.getElementById(`seccion-${seccionNombre}`);
    if (seccion) {
        seccion.style.display = 'block';
        seccion.classList.add('active');
        // Asegurar que el scroll esté arriba
        window.scrollTo({ top: 0, behavior: 'smooth' });
    } else {
        console.warn(`Sección no encontrada: seccion-${seccionNombre}`);
    }

    // Actualizar menú activo en sidebar
    document.querySelectorAll('.sidebar-menu a, .submenu a').forEach(l => {
        l.classList.remove('active');
        // Si el href coincide con el hash o el seccionNombre
        const href = l.getAttribute('href');
        if (href && (href.includes(`#${seccionNombre}`) || (seccionNombre === 'lista' && href === 'admin-aprendices.html'))) {
            l.classList.add('active');
        }
    });

    // Lógica específica
    if (seccionNombre === 'lista') cargarAprendices(paginaActual || 1);
    if (seccionNombre === 'poblacion') cargarEstadisticasPoblacion();
    if (seccionNombre === 'gestion-poblacion') cargarAprendicesGP(1);
    if (seccionNombre === 'excusas' && typeof cargarExcusasPendientes === 'function') cargarExcusasPendientes();
}

// Escuchar cambios de hash para navegación fluida
window.addEventListener('hashchange', () => {
    const hash = window.location.hash.replace('#', '') || 'lista';
    mostrarSeccion(null, hash);
});


/**
 * Aplica restricciones visuales en la página de aprendices según el rol.
 */
function aplicarRestriccionesDePagina() {
    const user = authSystem.getCurrentUser();
    if (!user) return;

    const rol = (user.rol || '').toLowerCase();
    const bienestar = user.bienestar_data || [];
    const scopes = user.vocero_scopes || (user.vocero_scope ? [user.vocero_scope] : []);

    const esDirector = ['director', 'admin', 'administrador'].includes(rol);
    const esJefeBienestar = bienestar.includes('jefe_bienestar');
    const esRespLiderazgo = bienestar.includes('voceros_y_representantes');
    const esVocero = rol === 'vocero';

    const esVoceroEnfoque = scopes.some(s => s.tipo === 'enfoque');
    const esVoceroFicha = scopes.some(s => s.tipo === 'principal' || s.tipo === 'suplente');

    // Si es Vocero de Enfoque o Resp. Liderazgo, forzar vista de población
    if (esVoceroEnfoque || esRespLiderazgo) {
        if (!window.location.hash || window.location.hash === '#lista') {
            mostrarSeccion(null, 'poblacion');
        }
    }

    // Ocultar tabs de secciones no permitidas para roles de bienestar/voceros
    if (!esDirector) {
        const tabsNoPermitidos = [];

        if (esVocero) {
            // Voceros solo ven Lista y Población (si son de enfoque o ambos)
            tabsNoPermitidos.push('excusas', 'planes', 'inasistencias', 'retardos', 'gestion-poblacion');

            // Si es vocero de ficha (principal/suplente) Y NO es de enfoque, NO ve población global
            if (esVoceroFicha && !esVoceroEnfoque) {
                tabsNoPermitidos.push('poblacion');
            }
        } else if (esJefeBienestar || esRespLiderazgo) {
            // Jefe de Bienestar y Resp Liderazgo SÍ deben ver Población y Gestión de Población
            tabsNoPermitidos.push('excusas', 'planes', 'inasistencias', 'retardos');
        } else {
            // Otros roles restrictivos
            tabsNoPermitidos.push('excusas', 'planes', 'inasistencias', 'retardos', 'gestion-poblacion');
        }

        tabsNoPermitidos.forEach(tab => {
            const seccion = document.getElementById(`seccion-${tab}`);
            if (seccion) seccion.remove(); // Eliminar del DOM para que no sea accesible
        });
    }
}

window.mostrarGestionPoblacion = function () {
    mostrarSeccion(null, 'gestion-poblacion');
}

// ========== CARGA DATOS ==========
async function cargarFichas() {
    try {
        const res = await fetch('api/fichas.php?limit=-1');
        const result = await res.json();
        if (result.success) {
            const sel = document.getElementById('numeroFicha');
            const fil = document.getElementById('filtroFicha');
            const filGP = document.getElementById('filtroFichaGP');
            if (sel) {
                sel.innerHTML = '<option value="">Seleccione...</option>';
                result.data.forEach(f => sel.insertAdjacentHTML('beforeend', `<option value="${f.numero_ficha}">${f.numero_ficha} - ${f.nombre_programa || ''}</option>`));
            }
            if (fil) {
                fil.innerHTML = '<option value="">Todas las fichas</option>';
                result.data.forEach(f => fil.insertAdjacentHTML('beforeend', `<option value="${f.numero_ficha}">${f.numero_ficha} - ${f.nombre_programa || ''}</option>`));
            }
            if (filGP) {
                filGP.innerHTML = '<option value="">Todas las fichas</option>';
                result.data.forEach(f => filGP.insertAdjacentHTML('beforeend', `<option value="${f.numero_ficha}">${f.numero_ficha} - ${f.nombre_programa || ''}</option>`));
            }
        }
    } catch (e) { console.error(e); }
    cargarInstructoresParaLider();
}

async function cargarInstructoresParaLider() {
    try {
        const res = await fetch('api/usuarios.php?rol=instructor');
        const r = await res.json();
        const sel = document.getElementById('id_instructor_lider');
        if (sel && r.data) {
            sel.innerHTML = '<option value="">Seleccione un instructor...</option>';
            r.data.forEach(ins => {
                sel.insertAdjacentHTML('beforeend', `<option value="${ins.id_usuario}">${ins.nombre} ${ins.apellido}</option>`);
            });
        }
    } catch (e) { console.error(e); }
}

async function cargarAprendices(pagina = 1) {
    try {
        const user = authSystem.getCurrentUser();
        const search = document.getElementById('filtroSearch')?.value || '';
        let ficha = document.getElementById('filtroFicha')?.value || '';
        const estado = document.getElementById('filtroEstado')?.value || '';
        let urlPoblacion = '';

        // Restricción automática para Voceros
        const scopes = user.vocero_scopes || (user.vocero_scope ? [user.vocero_scope] : []);
        if (user && user.rol === 'vocero' && scopes.length > 0) {
            const scopeFicha = scopes.find(s => s.tipo === 'principal' || s.tipo === 'suplente');
            const scopeEnfoque = scopes.find(s => s.tipo === 'enfoque');
            const esVistaPoblacion = document.getElementById('seccion-poblacion')?.style.display === 'block';

            if (esVistaPoblacion && scopeEnfoque) {
                urlPoblacion = `&tabla_poblacion=${scopeEnfoque.poblacion}`;
            } else if (scopeFicha) {
                ficha = scopeFicha.ficha;
                // Bloquear el filtro visualmente
                const selFicha = document.getElementById('filtroFicha');
                if (selFicha) {
                    selFicha.value = ficha;
                    selFicha.disabled = true;
                }
            } else if (scopeEnfoque) {
                urlPoblacion = `&tabla_poblacion=${scopeEnfoque.poblacion}`;
            }
        }

        let url = `api/aprendices.php?page=${pagina}&limit=${ITEMS_POR_PAGINA}${urlPoblacion}`;
        if (search) url += `&search=${encodeURIComponent(search)}`;
        if (ficha) url += `&ficha=${encodeURIComponent(ficha)}`;
        if (estado) url += `&estado=${encodeURIComponent(estado)}`;
        if (filtroPoblacionActivo) url += `&poblacion=${encodeURIComponent(filtroPoblacionActivo)}`;

        const response = await fetch(url);
        const result = await response.json();

        if (result.success) {
            todosAprendices = result.data;
            paginaActual = result.pagination.page;
            totalPaginas = result.pagination.pages;
            mostrarAprendices(todosAprendices);
            actualizarPaginacion(result.pagination);
        } else {
            document.getElementById('tablaAprendices').innerHTML = '<tr><td colspan="9" class="text-center">No se encontraron datos</td></tr>';
        }
    } catch (e) {
        console.error(e);
        mostrarNotificacion('Error cargando aprendices', 'error');
    }
}

function mostrarAprendices(aprendices) {
    const tbody = document.getElementById('tablaAprendices');
    if (!tbody) return;

    if (!aprendices || aprendices.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center">No se encontraron aprendices registrados</td></tr>';
        return;
    }

    tbody.innerHTML = aprendices.map(a => {
        // Selector de Estado (Dinámico desde tabla estados)
        const getClaseEstado = (estado) => {
            if (!estado) return 'badge-secondary';
            const normalized = estado.toLowerCase().replace(/ /g, '-');
            return `status-${normalized}`;
        };

        const currentClass = getClaseEstado(a.estado);

        let estadoSelect = `<select onchange="actualizarEstadoColor(this, '${a.documento}')" class="status-select ${currentClass}">`;

        // Estados fijos solicitados + tabla dinámica
        const estadosFijos = ['INDUCCION', 'LECTIVA', 'PRODUCTIVA', 'CANCELADO', 'RETIRADO', 'POR CERTIFICAR', 'FINALIZADO', 'TRASLADO', 'APLAZADO'];

        // Usar todosEstados si existen, sino usar fijos
        const listaEstados = (todosEstados && todosEstados.length > 0) ? todosEstados.map(e => e.nombre) : estadosFijos;

        // Asegurar que los fijos estén sí o sí
        estadosFijos.forEach(ef => {
            if (!listaEstados.includes(ef)) listaEstados.push(ef);
        });

        listaEstados.forEach(est => {
            const selected = (a.estado === est) ? 'selected' : '';
            const claseOpcion = getClaseEstado(est);
            estadoSelect += `<option value="${est}" class="${claseOpcion}" ${selected}>${est}</option>`;
        });

        estadoSelect += `</select>`;

        // Estados INACTIVOS para biometría y filtros
        const estadosInactivos = ['RETIRO', 'CANCELADO', 'RETIRADO', 'FINALIZADO', 'TRASLADO', 'APLAZADO'];
        const esInactivo = estadosInactivos.includes(a.estado);

        // Botón Editar: Bloqueado si es CANCELADO
        let btnEditar = '';
        if (a.estado === 'CANCELADO') {
            btnEditar = `
                <button onclick="mostrarNotificacion('No se puede editar un aprendiz CANCELADO', 'warning')" 
                        class="btn-icon-custom btn-gray" 
                        title="Edición bloqueda (Cancelado)" style="cursor: not-allowed; opacity: 0.6;">
                    <i class="fas fa-lock"></i>
                </button>`;
        } else {
            btnEditar = `
                <button onclick="editarAprendiz('${a.documento}')" 
                        class="btn-icon-custom btn-purple" 
                        title="Editar Datos">
                    <i class="fas fa-edit"></i>
                </button>`;
        }

        // Generar badges de población
        let poblacionBadges = '';
        if (a.mujer == 1) poblacionBadges += '<span class="badge" style="background:#a855f7; color:white; font-size:0.6rem; margin-right:2px; padding: 2px 5px;" title="Mujer">M</span>';
        if (a.indigena == 1) poblacionBadges += '<span class="badge" style="background:#84cc16; color:white; font-size:0.6rem; margin-right:2px; padding: 2px 5px;" title="Indígena">I</span>';
        if (a.narp == 1) poblacionBadges += '<span class="badge" style="background:#000; color:white; font-size:0.6rem; margin-right:2px; padding: 2px 5px;" title="NARP">N</span>';
        if (a.campesino == 1) poblacionBadges += '<span class="badge" style="background:#6B8E23; color:white; font-size:0.6rem; margin-right:2px; padding: 2px 5px;" title="Campesino">C</span>';
        if (a.lgbtiq == 1) poblacionBadges += '<span class="badge" style="background:#f97316; color:white; font-size:0.6rem; margin-right:2px; padding: 2px 5px;" title="LGBTIQ+">L</span>';
        if (a.discapacidad == 1) poblacionBadges += '<span class="badge" style="background:#4A90E2; color:white; font-size:0.6rem; margin-right:2px; padding: 2px 5px;" title="Discapacidad">D</span>';

        if (!poblacionBadges && a.tipo_poblacion) poblacionBadges = `<span style="font-size:0.75rem; color:#64748b;">${a.tipo_poblacion}</span>`;

        return `
            <tr class="hover-row">
                <td class="font-medium">${a.documento}</td>
                <td>${a.nombre || a.nombres}</td>
                <td>${a.apellido || a.apellidos}</td>
                <td>${poblacionBadges || '<span style="color:#cbd5e1;">-</span>'}</td>
                <td class="col-correo" title="${a.correo || ''}">${a.correo || ''}</td>
                <td>${a.celular || a.telefono || ''}</td>
                <td><span class="badge badge-ficha">${a.numero_ficha || a.ficha_id || 'N/A'}</span></td>
                <td>${estadoSelect}</td>
                <td class="text-center action-buttons-wrapper">
                    <!-- Botón Biometría (oculto para inactivos) -->
                    ${esInactivo ?
                `<span class="badge badge-secondary" style="font-size: 0.75rem; padding: 4px 8px;" title="Biometría deshabilitada">
                            <i class="fas fa-ban"></i> Inactivo
                        </span>` :
                (a.tiene_biometria ?
                    `<button onclick="registrarBiometriaAprendiz('${a.documento}')" 
                                    class="btn-icon-custom btn-green" 
                                    title="Biometría Registrada - Click para actualizar">
                                <i class="fas fa-user-check"></i>
                            </button>` :
                    `<button onclick="registrarBiometriaAprendiz('${a.documento}')" 
                                    class="btn-icon-custom btn-orange" 
                                    title="Registrar Biometría">
                                <i class="fas fa-camera"></i>
                            </button>`)
            }

                    ${btnEditar}

                    <!-- Botón Eliminar REMOVIDO por solicitud -->
                </td>
            </tr>
        `;
    }).join('');
}

function actualizarPaginacion(p) {
    const div = document.getElementById('paginacion');
    if (!div || p.pages <= 1) {
        if (div) div.innerHTML = '';
        return;
    }

    let html = `<div class="pagination-wrapper">`;

    // Anterior
    html += `<button onclick="cargarAprendices(${p.page - 1})" 
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
        html += `<button onclick="cargarAprendices(1)" class="btn-pagination">1</button>`;
        if (start > 2) html += `<span class="pagination-ellipsis">...</span>`;
    }

    for (let i = start; i <= end; i++) {
        html += `<button onclick="cargarAprendices(${i})" class="btn-pagination ${i === p.page ? 'active' : ''}">${i}</button>`;
    }

    if (end < p.pages) {
        if (end < p.pages - 1) html += `<span class="pagination-ellipsis">...</span>`;
        html += `<button onclick="cargarAprendices(${p.pages})" class="btn-pagination">${p.pages}</button>`;
    }

    // Siguiente
    html += `<button onclick="cargarAprendices(${p.page + 1})" 
             class="btn-pagination ${p.page === p.pages ? 'disabled' : ''}" 
             ${p.page === p.pages ? 'disabled' : ''} title="Siguiente"><i class="fas fa-chevron-right"></i></button>`;

    html += `</div>`;
    div.innerHTML = html;
}

// ========== CRUD ==========
window.nuevoAprendiz = function () {

    document.getElementById('modalTitulo').textContent = 'Nuevo Aprendiz';
    document.getElementById('formAprendiz').reset();
    document.getElementById('aprendizId').value = '';
    document.querySelectorAll('input[name="poblacion[]"]').forEach(cb => cb.checked = false);
    document.getElementById('modalAprendiz').style.display = 'flex';
}

window.nuevoAprendizDesdePoblacion = function () {
    window.nuevoAprendiz();
    if (filtroPoblacionActivo) {
        // Mapear el texto del filtro al valor del checkbox
        const mapRev = {
            'Mujer': 'Mujer',
            'Indígena': 'Indígena',
            'Afro': 'Afro',
            'Víctima': 'Víctima',
            'Campesina': 'Campesina',
            'N.A.P': 'N.A.P',
            'LGBTI': 'LGBTI',
            'Discapacidad': 'Discapacidad'
        };
        const val = mapRev[filtroPoblacionActivo];
        if (val) {
            const cb = Array.from(document.querySelectorAll('input[name="poblacion[]"]')).find(c => c.value === val);
            if (cb) cb.checked = true;
        }
    }
}

function editarAprendiz(doc) {
    const a = todosAprendices.find(x => x.documento == doc);
    if (!a) return;
    document.getElementById('modalTitulo').textContent = 'Editar Aprendiz';
    document.getElementById('aprendizId').value = doc;
    document.getElementById('tipoIdentificacion').value = a.tipo_identificacion;
    document.getElementById('documento').value = a.documento;
    document.getElementById('nombres').value = a.nombre;
    document.getElementById('apellidos').value = a.apellido;
    document.getElementById('correo').value = a.correo;
    document.getElementById('celular').value = a.celular || '';
    document.getElementById('numeroFicha').value = a.numero_ficha;
    document.getElementById('estado').value = a.estado;

    // Establecer instructor líder si existe
    const selLider = document.getElementById('id_instructor_lider');
    if (selLider) selLider.value = a.id_instructor_lider || '';

    // Resetear y marcar checkboxes de población
    document.querySelectorAll('input[name="poblacion[]"]').forEach(cb => cb.checked = false);
    if (a.tipo_poblacion) {
        const poblaciones = a.tipo_poblacion.split(',').map(s => s.trim());
        poblaciones.forEach(p => {
            const cb = Array.from(document.querySelectorAll('input[name="poblacion[]"]')).find(c => c.value === p);
            if (cb) cb.checked = true;
        });
    }
    document.getElementById('modalAprendiz').style.display = 'flex';
}

function cerrarModal() { document.getElementById('modalAprendiz').style.display = 'none'; }

async function guardarAprendiz(e) {
    e.preventDefault();
    const docOrig = document.getElementById('aprendizId').value;
    const esEdicion = !!docOrig;

    const checkedPoblaciones = Array.from(document.querySelectorAll('input[name="poblacion[]"]:checked')).map(cb => cb.value);

    const body = {
        tipo_identificacion: document.getElementById('tipoIdentificacion').value,
        documento: document.getElementById('documento').value,
        nombre: document.getElementById('nombres').value,
        apellido: document.getElementById('apellidos').value,
        correo: document.getElementById('correo').value,
        celular: document.getElementById('celular').value,
        numero_ficha: document.getElementById('numeroFicha').value,
        estado: document.getElementById('estado').value,
        id_instructor_lider: document.getElementById('id_instructor_lider').value,
        tipo_poblacion: checkedPoblaciones.join(', ')
    };

    try {
        const res = await fetch(esEdicion ? `api/aprendices.php?documento=${docOrig}` : 'api/aprendices.php', {
            method: esEdicion ? 'PUT' : 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body)
        });
        const r = await res.json();
        if (r.success) {
            mostrarNotificacion(r.message, 'success');
            cerrarModal();
            cargarAprendices(paginaActual);
        } else {
            mostrarNotificacion(r.message, 'error');
        }
    } catch (er) { mostrarNotificacion('Error guardando', 'error'); }
}

async function eliminarAprendiz(doc) {
    if (!confirm('¿Eliminar aprendiz?')) return;
    try {
        await fetch(`api/aprendices.php?documento=${doc}`, { method: 'DELETE' });
        mostrarNotificacion('Eliminado', 'success');
        cargarAprendices(paginaActual);
    } catch (e) { mostrarNotificacion('Error', 'error'); }
}

async function cambiarEstadoAprendiz(doc, est) {
    try {
        await fetch(`api/aprendices.php?documento=${doc}`, { method: 'PUT', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ estado: est }) });
        mostrarNotificacion('Estado actualizado', 'success');
    } catch (e) { mostrarNotificacion('Error', 'error'); }
}

// ========== BIOMETRÍA ROBUSTA ==========
// COPIA EXACTA DE LA LÓGICA DE USUARIOS (js/admin/usuarios.js)

async function registrarBiometriaAprendiz(documento) {
    const aprendiz = todosAprendices.find(a => a.documento == documento);
    if (!aprendiz) { mostrarNotificacion('Aprendiz no encontrado', 'error'); return; }

    if (aprendiz.estado === 'CANCELADO' || aprendiz.estado === 'RETIRADO' ||
        aprendiz.estado === 'CANCELADA' || aprendiz.estado === 'RETIRO') {
        mostrarNotificacion('No se puede registrar biometría para aprendices en estado RETIRO o CANCELADA', 'error');
        return;
    }

    try {
        const estadoBio = await consultarEstadoBiometria('aprendiz', documento);
        if (estadoBio.success && estadoBio.tiene_biometria) {
            const confirmar = confirm(`${aprendiz.nombre} ${aprendiz.apellido} ya tiene biometría registrada.\n\n¿Desea RE-REGISTRAR (actualizar) la biometría?\n\n⚠ Esto reemplazará el registro anterior.`);
            if (!confirmar) return;
        }
    } catch (e) { }

    // Crear modal IDÉNTICO a usuarios.js
    const modal = document.createElement('div');
    modal.id = 'modalBiometriaAprendiz';

    // Estilos exactos de usuarios.js
    modal.style.cssText = `
        display: flex;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.7);
        z-index: 10000;
        align-items: center;
        justify-content: center;
    `;

    // HTML interno exacto de usuarios.js (adaptado solo nombre/ID variables)
    modal.innerHTML = `
        <div style="background: white; padding: 30px; border-radius: 12px; max-width: 600px; width: 90%;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 20px;">
                <h3 style="margin: 0;">Registro Biométrico - ${aprendiz.nombre} ${aprendiz.apellido}</h3>
                <button onclick="cerrarModalBiometriaAprendiz()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
            </div>
            
            <div style="text-align: center; margin-bottom: 15px;">
                <p style="color: #666; margin: 5px 0; font-size: 0.9rem;">Coloque su rostro dentro del círculo guía</p>
                <p style="color: #10b981; font-weight: 600; margin: 5px 0; font-size: 0.95rem;" id="estadoDeteccion">Inicializando cámara...</p>
            </div>
            
            <div style="position: relative; background: #000; border-radius: 8px; overflow: hidden; margin-bottom: 20px;">
                <video id="videoBiometriaAprendiz" autoplay playsinline style="width: 100%; height: 400px; object-fit: cover;"></video>
                <canvas id="canvasBiometriaAprendiz" style="position: absolute; top: 0; left: 0; width: 100%; height: 400px; pointer-events: none;"></canvas>
                <div id="overlayGuiaAprendiz" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); 
                     width: 250px; height: 300px; border: 4px solid #6b7280; border-radius: 50%; 
                     transition: border-color 0.3s ease;"></div>
            </div>
            
            <div id="mensajeCapturaAprendiz" style="text-align: center; margin-bottom: 15px; min-height: 24px; color: #666; font-size: 0.9rem;"></div>
            
            <div style="display: flex; gap: 10px; justify-content: center;">
                <button id="btnCapturarAprendiz" onclick="capturarBiometriaAprendiz('${documento}')" 
                        class="btn-primary" style="padding: 12px 24px; background: #10b981; display: none;" disabled>
                    <i class="fas fa-camera"></i> Capturar Rostro
                </button>
                <button onclick="cerrarModalBiometriaAprendiz()" class="btn-primary" 
                        style="padding: 12px 24px; background: #6b7280;">
                    Cancelar
                </button>
            </div>
        </div>
    `;

    document.body.appendChild(modal);

    try {
        const stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user', width: 640, height: 480 } });
        const video = document.getElementById('videoBiometriaAprendiz');
        video.srcObject = stream;
        window.streamBiometriaAprendiz = stream;

        await new Promise(resolve => {
            video.onloadedmetadata = () => { video.play(); resolve(); };
        });

        iniciarDeteccionTiempoReal(video);

    } catch (error) {
        console.error('Error cámara:', error);
        mostrarNotificacion('Error al acceder a la cámara', 'error');
        cerrarModalBiometriaAprendiz();
    }
}

async function iniciarDeteccionTiempoReal(videoElement) {
    const overlayGuia = document.getElementById('overlayGuiaAprendiz');
    const estadoDeteccion = document.getElementById('estadoDeteccion');
    const btnCapturar = document.getElementById('btnCapturarAprendiz');

    if (!overlayGuia || !estadoDeteccion) return;

    window.deteccionBiometriaActiva = true;
    window.capturaEnProceso = false;
    let rostroBuenoDetectado = false;

    async function detectar() {
        if (!window.deteccionBiometriaActiva || !videoElement || window.capturaEnProceso) return;

        try {
            if (typeof Human === 'undefined' || !window.getHuman) {
                setTimeout(detectar, 500);
                return;
            }

            const human = await getHuman();
            const result = await human.detect(videoElement);

            const canvas = document.getElementById('canvasBiometriaAprendiz');
            if (canvas) {
                const ctx = canvas.getContext('2d');
                canvas.width = videoElement.videoWidth;
                canvas.height = videoElement.videoHeight;
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                if (result.face && result.face.length === 1 && typeof dibujarLandmarksFaciales === 'function') {
                    dibujarLandmarksFaciales(canvas, result.face[0].mesh, result.face[0].faceScore);
                }
            }

            if (result.face && result.face.length === 1) {
                const face = result.face[0];
                const confianza = face.faceScore || face.boxScore || 0;

                if (confianza >= 0.7 && !rostroBuenoDetectado) {
                    overlayGuia.style.borderColor = '#10b981';
                    estadoDeteccion.textContent = '✓ Rostro detectado correctamente';
                    estadoDeteccion.style.color = '#10b981';
                    btnCapturar.disabled = false;
                    btnCapturar.style.display = 'inline-block';

                    // CAPTURA AUTOMÁTICA INMEDIATA
                    rostroBuenoDetectado = true;
                    window.capturaEnProceso = true;
                    estadoDeteccion.textContent = '📸 Capturando...';
                    estadoDeteccion.style.color = '#3b82f6';

                    const match = btnCapturar.getAttribute('onclick').match(/'([^']+)'/);
                    if (match) capturarBiometriaAprendiz(match[1]);
                } else if (confianza < 0.7) {
                    overlayGuia.style.borderColor = '#f59e0b';
                    estadoDeteccion.textContent = 'Mejore la iluminación/Posición';
                    estadoDeteccion.style.color = '#f59e0b';
                    btnCapturar.disabled = true;
                }
            } else {
                overlayGuia.style.borderColor = '#6b7280';
                estadoDeteccion.textContent = 'Buscando rostro...';
                estadoDeteccion.style.color = '#6b7280';
                btnCapturar.disabled = true;
            }

            if (window.deteccionBiometriaActiva && !window.capturaEnProceso) {
                requestAnimationFrame(detectar);
            }

        } catch (e) {
            console.error(e);
            if (window.deteccionBiometriaActiva) setTimeout(detectar, 1000);
        }
    }
    detectar();
}

async function capturarBiometriaAprendiz(documento) {
    const video = document.getElementById('videoBiometriaAprendiz');
    const btnCapturar = document.getElementById('btnCapturarAprendiz');
    const mensaje = document.getElementById('mensajeCapturaAprendiz');

    if (btnCapturar.disabled && btnCapturar.textContent !== 'Capturando automáticamente...') return;

    btnCapturar.disabled = true;
    mensaje.textContent = 'Procesando captura...';
    mensaje.style.color = '#3b82f6';

    try {
        // Usar función robusta de facial-recognition.js
        const resultado = await capturarRostro(video);

        if (!resultado.success) {
            mensaje.textContent = resultado.mensaje;
            mensaje.style.color = '#ef4444';
            btnCapturar.disabled = false;
            window.capturaEnProceso = false;
            return;
        }

        mensaje.textContent = `Guardando biometría (${Math.round(resultado.confianza * 100)}%)...`;

        const registroResult = await registrarBiometria('aprendiz', documento, resultado.embedding);

        if (registroResult.success) {
            mostrarNotificacion("Biometría registrada exitosamente", "success");
            cerrarModalBiometriaAprendiz();
            cargarAprendices(paginaActual);
        } else {
            mensaje.textContent = 'Error al guardar: ' + (registroResult.message || 'Error desconocido');
            mensaje.style.color = '#ef4444';
            btnCapturar.disabled = false;
            window.capturaEnProceso = false;
        }
    } catch (e) {
        console.error(e);
        mostrarNotificacion("Error procesando biometría", "error");
        btnCapturar.disabled = false;
        window.capturaEnProceso = false;
    }
}

function cerrarModalBiometriaAprendiz() {
    window.deteccionBiometriaActiva = false;
    if (window.streamBiometriaAprendiz) {
        window.streamBiometriaAprendiz.getTracks().forEach(track => track.stop());
    }
    const modal = document.getElementById('modalBiometriaAprendiz');
    if (modal) {
        modal.remove();
    }
}

// ========== STATS & UTILS ==========

function cargarEstadisticasPoblacion() {
    const user = authSystem.getCurrentUser();
    let url = 'api/aprendices.php?limit=-1';

    const scopes = user.vocero_scopes || (user.vocero_scope ? [user.vocero_scope] : []);
    const scopeEnfoque = scopes.find(s => s.tipo === 'enfoque');

    if (user && user.rol === 'vocero' && scopeEnfoque) {
        url += `&tabla_poblacion=${scopeEnfoque.poblacion}`;
    }

    fetch(url).then(r => r.json()).then(d => {
        if (!d.success) return;

        const data = d.data;

        // Si es vocero de enfoque, filtrar los datos base por su población para las estadísticas
        let filteredData = data;
        if (scopeEnfoque) {
            const miPob = scopeEnfoque.poblacion.toLowerCase();
            setTimeout(() => {
                filtrarPorPoblacion(miPob);
            }, 500);
        }

        // Conteos básicos
        const stats = {
            mujer: data.filter(a => a.mujer == 1).length,
            indigena: data.filter(a => a.indigena == 1).length,
            narp: data.filter(a => a.narp == 1).length,
            campesino: data.filter(a => a.campesino == 1).length,
            lgbtiq: data.filter(a => a.lgbtiq == 1).length,
            discapacidad: data.filter(a => a.discapacidad == 1).length
        };

        // Actualizar UI
        Object.keys(stats).forEach(k => {
            const el = document.getElementById(`count-${k}`);
            if (el) el.textContent = stats[k];
        });

        // Gráfica por Formaciones (Solicitud especial: mayor/menor población)
        const formacionesCont = {};
        data.forEach(a => {
            const f = a.nombre_programa || 'Sin Programa';
            formacionesCont[f] = (formacionesCont[f] || 0) + 1;
        });

        const labels = Object.keys(formacionesCont);
        const values = Object.values(formacionesCont);

        // Identificar mayor y menor
        if (labels.length > 0) {
            const sorted = Object.entries(formacionesCont).sort((a, b) => b[1] - a[1]);
            const mayor = sorted[0];
            const menor = sorted[sorted.length - 1];

            // Si hay un contenedor de info extra, mostrarlo
            let infoExtra = document.getElementById('info-extra-poblacion');
            if (!infoExtra) {
                const wrapper = document.querySelector('.chart-container-wrapper');
                if (wrapper) {
                    infoExtra = document.createElement('div');
                    infoExtra.id = 'info-extra-poblacion';
                    infoExtra.style.cssText = 'margin-top: 15px; padding: 15px; background: #f8fafc; border-radius: 8px; border-left: 4px solid #39A900; font-size: 0.9rem;';
                    wrapper.appendChild(infoExtra);
                }
            }

            if (infoExtra) {
                infoExtra.innerHTML = `
                    <div style="margin-bottom: 5px;"><strong>Resumen de Formaciones:</strong></div>
                    <div style="display: flex; justify-content: space-between; gap: 10px;">
                        <span><i class="fas fa-arrow-up" style="color:#39A900;"></i> Mayor: <b>${mayor[0]}</b> (${mayor[1]})</span>
                        <span><i class="fas fa-arrow-down" style="color:#ef4444;"></i> Menor: <b>${menor[0]}</b> (${menor[1]})</span>
                    </div>
                `;
            }
        }

        renderChartPoblacion(labels, values);
    });
}

function renderChartPoblacion(labels, values) {
    const ctx = document.getElementById('chartPoblacion')?.getContext('2d');
    if (!ctx) return;

    if (chartPoblacionInstance) chartPoblacionInstance.destroy();

    chartPoblacionInstance = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Aprendices',
                data: values,
                backgroundColor: '#39A900',
                borderRadius: 5
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            }
        }
    });
}
// si no están en la primera página de la lista principal.
todosAprendices = data;
    });
}

function renderizarGraficaPoblacion(c) {
    const canvas = document.getElementById('chartPoblacion');
    if (!canvas) return;

    const ctx = canvas.getContext('2d');
    if (chartPoblacionInstance) chartPoblacionInstance.destroy();

    // Sumar total para porcentajes
    const total = Object.values(c).reduce((a, b) => a + b, 0);

    // Gradientes (NARP usa Afro: Rojo, Negro, Verde)
    const gradMujer = ctx.createLinearGradient(0, 0, 0, 400);
    gradMujer.addColorStop(0, '#a855f7'); gradMujer.addColorStop(1, '#ec4899');

    const gradIndigena = ctx.createLinearGradient(0, 0, 0, 400);
    gradIndigena.addColorStop(0, '#84cc16'); gradIndigena.addColorStop(1, '#365314');

    const gradNarp = ctx.createLinearGradient(0, 0, 0, 400);
    gradNarp.addColorStop(0, '#dc2626'); gradNarp.addColorStop(0.5, '#000000'); gradNarp.addColorStop(1, '#16a34a');

    const gradCampesino = ctx.createLinearGradient(0, 0, 0, 400);
    gradCampesino.addColorStop(0, '#6B8E23'); gradCampesino.addColorStop(1, '#556B2F');

    const gradLgbtiq = ctx.createLinearGradient(0, 0, 400, 400);
    gradLgbtiq.addColorStop(0, '#ef4444'); gradLgbtiq.addColorStop(0.2, '#f97316'); gradLgbtiq.addColorStop(0.4, '#eab308');
    gradLgbtiq.addColorStop(0.6, '#22c55e'); gradLgbtiq.addColorStop(0.8, '#3b82f6'); gradLgbtiq.addColorStop(1, '#a855f7');

    const gradDiscapacidad = ctx.createLinearGradient(0, 0, 0, 400);
    gradDiscapacidad.addColorStop(0, '#4A90E2'); gradDiscapacidad.addColorStop(1, '#2980B9');

    const labels = ['Mujer', 'Indígena', 'NARP', 'Campesino', 'LGBTIQ+', 'Discapacidad'];
    const cssClasses = ['bg-mujer', 'bg-indigena', 'bg-afro', 'bg-campesina', 'bg-lgbti', 'bg-discapacidad'];
    const dataValues = [c.mujer, c.indigena, c.narp, c.campesino, c.lgbtiq, c.discapacidad];
    const colors = [gradMujer, gradIndigena, gradNarp, gradCampesino, gradLgbtiq, gradDiscapacidad];

    chartPoblacionInstance = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: dataValues,
                backgroundColor: colors,
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function (item) {
                            const val = item.raw;
                            const pct = total > 0 ? ((val / total) * 100).toFixed(1) : 0;
                            return `${item.label}: ${val} (${pct}%)`;
                        }
                    }
                },
                title: { display: true, text: 'Distribución de Población (En Formación)' }
            }
        }
    });

    generarLeyendaHTML(labels, cssClasses);
}

function generarLeyendaHTML(labels, cssClasses) {
    const container = document.getElementById('chartLegend');
    if (!container) return;
    container.innerHTML = ''; // Limpiar

    labels.forEach((label, index) => {
        const item = document.createElement('div');
        item.className = 'legend-item';

        // El cuadro de color usa la MISMA clase CSS que la tarjeta grande
        const colorBox = document.createElement('div');
        colorBox.className = `legend-color-box ${cssClasses[index]}`;

        const text = document.createElement('span');
        text.textContent = label;

        item.appendChild(colorBox);
        item.appendChild(text);
        container.appendChild(item);
    });
}

function filtrarPorPoblacion(tipo) {
    const map = {
        'mujer': 'mujer', 'indigena': 'indigena', 'INDIGENA': 'indigena',
        'NARP': 'narp', 'narp': 'narp', 'AFRODESCENDIENTE': 'narp', 'N.A.P': 'narp',
        'campesino': 'campesino', 'CAMPESINA': 'campesino',
        'lgbtiq': 'lgbtiq', 'LGBTIQ+': 'lgbtiq', 'LGBTI': 'lgbtiq',
        'discapacidad': 'discapacidad'
    };

    const key = map[tipo] || tipo.toLowerCase();
    filtroPoblacionActivo = key;

    const tituloDetalle = document.getElementById('poblacion-detalle-titulo');
    if (tituloDetalle) tituloDetalle.textContent = `Detalle de Aprendices: ${tipo}`;

    const tablaDetalle = document.getElementById('tablaDetallePoblacion');
    if (tablaDetalle) {
        const filtrados = todosAprendices.filter(a => {
            const estado = (a.estado || '').toUpperCase();
            return a[key] == 1 && (estado === 'LECTIVA');
        });

        if (filtrados.length === 0) {
            tablaDetalle.innerHTML = '<tr><td colspan="8" style="text-align: center; color: #999; padding: 20px;">No hay aprendices en esta categoría (Lectiva)</td></tr>';
        } else {
            tablaDetalle.innerHTML = filtrados.map(a => `
                <tr>
                    <td>${a.documento}</td>
                    <td>${a.nombre}</td>
                    <td>${a.apellido}</td>
                    <td>${a.correo || ''}</td>
                    <td>${a.celular || ''}</td>
                    <td>${a.numero_ficha || 'N/A'}</td>
                    <td><span class="badge ${getClaseEstado(a.estado)}">${a.estado || 'N/A'}</span></td>
                    <td class="text-center action-buttons-wrapper">
                        <button onclick="quitarAprendizDePoblacion('${a.documento}', '${key}')" class="btn-icon-custom btn-red" title="Quitar de categoría">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </td>
                </tr>
            `).join('');
        }
    }
    document.getElementById('tablaDetallePoblacion')?.closest('.card')?.scrollIntoView({ behavior: 'smooth' });
}

window.abrirModalAnexarPoblacion = function () {
    if (!filtroPoblacionActivo) {
        mostrarNotificacion('Seleccione una categoría primero', 'warning');
        return;
    }

    const modal = document.createElement('div');
    modal.id = 'modalAnexarPoblacion';
    modal.className = 'modal-custom';
    modal.style.cssText = `
        position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
        background: rgba(0,0,0,0.5); display: flex; align-items: center; 
        justify-content: center; z-index: 1000;
    `;

    const categoria = filtroPoblacionActivo.charAt(0).toUpperCase() + filtroPoblacionActivo.slice(1);

    modal.innerHTML = `
        <div style="background: white; padding: 30px; border-radius: 12px; width: 450px; box-shadow: 0 10px 25px rgba(0,0,0,0.2);">
            <h2 style="margin-top: 0; color: #1e293b; border-bottom: 2px solid #39A900; padding-bottom: 10px;">
                Anexar a ${categoria}
            </h2>
            <p style="color: #64748b; font-size: 0.9rem; margin: 15px 0;">
                Busque al aprendiz por documento para agregarlo a esta categoría.
            </p>
            
            <div class="form-group" style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 600;">Documento del Aprendiz</label>
                <input type="text" id="input-search-aprendiz" placeholder="Ingrese documento..." 
                       style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button onclick="document.getElementById('modalAnexarPoblacion').remove()" 
                        style="padding: 10px 20px; border-radius: 6px; border: 1px solid #ccc; background: white; cursor: pointer;">
                    Cancelar
                </button>
                <button onclick="guardarAnexoPoblacion()" 
                        style="padding: 10px 20px; border-radius: 6px; border: none; background: #39A900; color: white; font-weight: 600; cursor: pointer;">
                    Anexar
                </button>
            </div>
        </div>
    `;

    document.body.appendChild(modal);
};

window.guardarAnexoPoblacion = async function () {
    const doc = document.getElementById('input-search-aprendiz').value.trim();
    if (!doc) {
        mostrarNotificacion('Ingrese un documento', 'warning');
        return;
    }

    try {
        const res = await fetch('api/poblacion.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                documento: doc,
                poblacion: filtroPoblacionActivo
            })
        });

        const r = await res.json();
        if (r.success) {
            mostrarNotificacion('Aprendiz anexado correctamente', 'success');
            document.getElementById('modalAnexarPoblacion').remove();

            // Recargar datos
            cargarEstadisticasPoblacion();
            setTimeout(() => filtrarPorPoblacion(filtroPoblacionActivo), 500);
        } else {
            mostrarNotificacion(r.message || 'Error al anexar', 'error');
        }
    } catch (e) {
        console.error(e);
        mostrarNotificacion('Error de conexión', 'error');
    }
};

window.quitarAprendizDePoblacion = async function (doc, poblacion) {
    if (!confirm('¿Está seguro de quitar a este aprendiz de esta categoría de población?')) return;

    try {
        const res = await fetch(`api/poblacion.php?documento=${doc}&poblacion=${poblacion}`, {
            method: 'DELETE'
        });
        const r = await res.json();
        if (r.success) {
            mostrarNotificacion(r.message, 'success');
            // Recargar datos y estadísticas
            cargarEstadisticasPoblacion();
            setTimeout(() => filtrarPorPoblacion(poblacion), 500);
        } else {
            mostrarNotificacion(r.message, 'error');
        }
    } catch (e) {
        console.error(e);
        mostrarNotificacion('Error al quitar de la categoría', 'error');
    }
}

window.descargarPDFPoblacion = async function () {
    if (!filtroPoblacionActivo) {
        mostrarNotificacion('Seleccione una categoría primero', 'warning');
        return;
    }

    const { jsPDF } = window.jspdf;
    const doc = new jsPDF('p', 'mm', 'a4');
    const user = authSystem.getCurrentUser();
    const categoria = filtroPoblacionActivo.charAt(0).toUpperCase() + filtroPoblacionActivo.slice(1);
    const title = `TIPO DE POBLACIÓN ${categoria.toUpperCase()}`;
    const fecha = new Date().toLocaleDateString();

    // Filtrar aprendices activos de esta categoría
    const filtrados = todosAprendices.filter(a => {
        const estado = (a.estado || '').toUpperCase();
        return a[filtroPoblacionActivo] == 1 && (estado === 'LECTIVA');
    });

    if (filtrados.length === 0) {
        mostrarNotificacion('No hay datos para exportar', 'warning');
        return;
    }

    // --- ENCABEZADO ESTILO IMAGEN ---
    try {
        // Logo SENA (Izquierda)
        const imgSena = new Image();
        imgSena.src = 'assets/img/logosena.png';
        await new Promise(r => imgSena.onload = r);
        doc.addImage(imgSena, 'PNG', 15, 10, 25, 25);

        // Logo SENAPRE (Derecha, circular si es posible o normal)
        const imgSenapre = new Image();
        imgSenapre.src = 'assets/img/asi.png';
        await new Promise(r => imgSenapre.onload = r);
        doc.addImage(imgSenapre, 'PNG', 170, 10, 25, 25);
    } catch (e) { console.warn('No se pudieron cargar los logos', e); }

    // Títulos
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(22);
    doc.setTextColor(57, 169, 0); // Verde SENA
    doc.text(title, 105, 25, { align: 'center' });

    doc.setFontSize(14);
    doc.text(`VOCERO DE ENFOQUE DIFERENCIAL ${categoria.toUpperCase()}`, 105, 35, { align: 'center' });

    // Nombre del Vocero (si es vocero, usar su nombre. Si es admin, poner el del responsable si lo tenemos)
    let nombreVocero = `${user.nombre} ${user.apellido}`;
    if (user.rol !== 'vocero') {
        // Aquí podrías buscar el responsable de la tabla voceros_enfoque si quisieras ser más preciso
    }
    doc.setFontSize(18);
    doc.text(nombreVocero.toUpperCase(), 105, 45, { align: 'center' });

    doc.setFont('helvetica', 'italic');
    doc.setFontSize(10);
    doc.setTextColor(50);
    doc.text(`Fecha de reporte: ${fecha}`, 105, 55, { align: 'center' });

    // Tabla de Datos
    const rows = filtrados.map(a => [
        a.documento,
        a.nombre,
        a.apellido,
        a.numero_ficha || 'N/A',
        a.nombre_programa || 'N/A',
        a.estado || 'N/A'
    ]);

    doc.autoTable({
        startY: 65,
        head: [['DOCUMENTO', 'NOMBRES', 'APELLIDOS', 'FICHA', 'PROGRAMA', 'ESTADO']],
        body: rows,
        theme: 'grid',
        headStyles: {
            fillColor: [0, 100, 0], // Verde oscuro
            textColor: [255, 255, 255],
            fontSize: 10,
            halign: 'center'
        },
        styles: {
            fontSize: 9,
            cellPadding: 3
        },
        columnStyles: {
            0: { halign: 'center' },
            3: { halign: 'center' },
            5: { halign: 'center' }
        },
        alternateRowStyles: {
            fillColor: [245, 255, 245]
        }
    });

    doc.save(`Reporte_${categoria}_${new Date().getTime()}.pdf`);
}
function mostrarNotificacion(m, t = 'info') {
    const d = document.createElement('div');
    d.className = `notificacion ${t}`;
    d.textContent = m;
    d.style.cssText = `position:fixed;top:20px;right:20px;padding:15px;background:${t === 'success' ? '#10b981' : '#ef4444'};color:white;z-index:9999;border-radius:5px;`;
    document.body.appendChild(d);
    setTimeout(() => d.remove(), 3000);
}
function debounce(f, w) { let t; return (...a) => { clearTimeout(t); t = setTimeout(() => f(...a), w); } }

function exportarAprendices() {
    if (!todosAprendices || todosAprendices.length === 0) {
        mostrarNotificacion('No hay aprendices para exportar', 'warning');
        return;
    }

    let table = `
        <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
        <head><meta charset="UTF-8"></head>
        <body>
            <table border="1">
                <tr>
                    <th style="background-color: #39A900; color: white;">Documento</th>
                    <th style="background-color: #39A900; color: white;">Nombres</th>
                    <th style="background-color: #39A900; color: white;">Apellidos</th>
                    <th style="background-color: #39A900; color: white;">Correo</th>
                    <th style="background-color: #39A900; color: white;">Celular</th>
                    <th style="background-color: #39A900; color: white;">Ficha</th>
                    <th style="background-color: #39A900; color: white;">Estado</th>
                </tr>
    `;

    todosAprendices.forEach(a => {
        // Determinar color según estado
        let estadoColor = '#666666'; // Default gris
        const est = (a.estado || '').toUpperCase();
        if (est === 'LECTIVA') estadoColor = '#16a34a'; // Verde oscuro
        else if (est === 'INDUCCION') estadoColor = '#ca8a04'; // Amarillo oscuro
        else if (est === 'POR CERTIFICAR') estadoColor = '#2563eb'; // Azul oscuro
        else if (est === 'CANCELADO') estadoColor = '#dc2626'; // Rojo oscuro
        else if (est === 'RETIRADO') estadoColor = '#ea580c'; // Naranja oscuro

        table += `
            <tr>
                <td style="mso-number-format:'@'">${a.documento}</td>
                <td>${a.nombre}</td>
                <td>${a.apellido}</td>
                <td>${a.correo}</td>
                <td style="mso-number-format:'@'">${a.celular || ''}</td>
                <td>${a.numero_ficha || ''}</td>
                <td style="background-color: ${estadoColor}; color: white; font-weight: bold;">${a.estado}</td>
            </tr>
        `;
    });

    table += '</table></body></html>';

    const blob = new Blob(['\uFEFF', table], { type: 'application/vnd.ms-excel;charset=utf-8' });
    const link = document.createElement('a');
    link.href = window.URL.createObjectURL(blob);
    link.download = 'Reporte_Aprendices.xls';
    link.click();
}

// Función para actualizar estado directamente desde la tabla (Dropdown)
window.actualizarEstado = async function (doc, nuevoEstado) {
    try {
        const aprendiz = todosAprendices.find(a => a.documento == doc);
        if (!aprendiz) return;

        // Usar PUT para actualización parcial del estado
        const res = await fetch(`api/aprendices.php?documento=${doc}`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ estado: nuevoEstado })
        });

        const data = await res.json();

        if (data.success) {
            mostrarNotificacion('Estado actualizado correctamente', 'success');
            aprendiz.estado = nuevoEstado; // Actualizar localmente sin recargar todo
        } else {
            throw new Error(data.message || 'Error al actualizar');
        }
    } catch (e) {
        console.error(e);
        mostrarNotificacion('Error actualizando estado: ' + e.message, 'error');
        // Revertir cambio visual si falla (recargar datos)
        cargarAprendices(paginaActual);
    }
}

// Función global para actualizar color del select de estado
window.actualizarEstadoColor = function (selectElement, doc) {
    const nuevoEstado = selectElement.value;

    // Función helper para generar clase dinámica
    const getClaseEstado = (estado) => {
        if (!estado) return 'badge-secondary';
        const normalized = estado.toLowerCase().replace(/ /g, '-');
        return `status-${normalized}`;
    };

    // Remover todas las clases de estado anteriores
    selectElement.className = selectElement.className.replace(/status-[\w-]+/g, '').trim();

    // Agregar nueva clase
    const newClass = getClaseEstado(nuevoEstado);
    selectElement.classList.add('status-select', newClass);

    // Llamar a la función de actualización de backend
    actualizarEstado(doc, nuevoEstado);
}

// ========== GESTIÓN DE POBLACIÓN (VISTA AGREGAR) ==========

async function cargarAprendicesGP(pagina = 1) {
    try {
        const search = document.getElementById('filtroSearchGP')?.value || '';
        const ficha = document.getElementById('filtroFichaGP')?.value || '';
        const estado = document.getElementById('filtroEstadoGP')?.value || 'LECTIVA'; // Solo LECTIVA en Tipo de Población

        const res = await fetch(`api/aprendices.php?page=${pagina}&limit=${ITEMS_POR_PAGINA}&search=${encodeURIComponent(search)}&ficha=${ficha}&estado=${estado}`);
        const result = await res.json();

        const tbody = document.getElementById('tablaGP');
        if (!tbody) return;

        if (result.success && result.data.length > 0) {
            // Unir o reemplazar en todosAprendices para que el botón Guardar funcione
            result.data.forEach(a => {
                const index = todosAprendices.findIndex(x => x.documento == a.documento);
                if (index !== -1) todosAprendices[index] = a;
                else todosAprendices.push(a);
            });

            tbody.innerHTML = result.data.map((a, index) => renderizarFilaAprendizGP(a)).join('');
            renderPaginacionGP(result.pagination.pages, pagina);
        } else {
            tbody.innerHTML = `<tr><td colspan="10" style="text-align: center; color: #999; padding: 20px;">No se encontraron aprendices con los filtros seleccionados</td></tr>`;
            const pagContainer = document.getElementById('paginacionGP');
            if (pagContainer) pagContainer.innerHTML = '';
        }
    } catch (e) {
        console.error(e);
        const tbody = document.getElementById('tablaGP');
        if (tbody) tbody.innerHTML = '<tr><td colspan="10" style="text-align: center; color: #dc2626; padding: 20px;">Error al cargar datos</td></tr>';
    }
}

function renderizarFilaAprendizGP(a) {
    const cb = (prop, label) => {
        const checked = a[prop] == 1 ? 'checked' : '';
        return `<input type="checkbox" class="cb-poblacion" data-prop="${prop}" value="${label}" ${checked} style="display: block; margin: 0 auto; cursor: pointer;">`;
    };

    return `
        <tr class="hover-row">
            <td class="font-medium">${a.documento}</td>
            <td>${a.nombre}</td>
            <td>${a.apellido}</td>
            <td class="text-center">${cb('mujer', 'Mujer')}</td>
            <td class="text-center">${cb('indigena', 'Indígena')}</td>
            <td class="text-center">${cb('narp', 'NARP')}</td>
            <td class="text-center">${cb('campesino', 'Campesino')}</td>
            <td class="text-center">${cb('lgbtiq', 'LGBTIQ+')}</td>
            <td class="text-center">${cb('discapacidad', 'Discapacidad')}</td>
            <td class="text-center">
                <button onclick="guardarPoblacionAprendizGP('${a.documento}', this)" class="btn-primary" style="padding: 5px 10px; font-size: 0.8rem;">
                    Guardar
                </button>
            </td>
        </tr>
    `;
}

window.guardarPoblacionAprendizGP = async function (doc, btn) {
    try {
        const row = btn.closest('tr');
        const checkboxes = row.querySelectorAll('.cb-poblacion');

        // Obtener el aprendiz original
        const aprendiz = todosAprendices.find(x => x.documento == doc);
        if (!aprendiz) return;

        // Construir el objeto de actualización con los valores de los checkboxes
        const body = { ...aprendiz };
        const checklist = [];

        checkboxes.forEach(cb => {
            const prop = cb.getAttribute('data-prop');
            const valor = cb.checked ? 1 : 0;
            body[prop] = valor;
            if (cb.checked) checklist.push(cb.value);
        });

        // También actualizamos el string tipo_poblacion para compatibilidad heredada
        body.tipo_poblacion = checklist.join(',');

        btn.disabled = true;
        btn.textContent = '...';

        const res = await fetch(`api/aprendices.php?documento=${doc}`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body)
        });

        const r = await res.json();
        if (r.success) {
            mostrarNotificacion('Población actualizada', 'success');
            // Actualizar localmente el array global
            Object.assign(aprendiz, body);
            // Recargar estadísticas en background
            cargarEstadisticasPoblacion();
        } else {
            mostrarNotificacion(r.message, 'error');
        }
    } catch (e) {
        console.error(e);
        mostrarNotificacion('Error al guardar', 'error');
    } finally {
        btn.disabled = false;
        btn.textContent = 'Guardar';
    }
}

function renderPaginacionGP(total, actual) {
    const container = document.getElementById('paginacionGP');
    if (!container || total <= 1) {
        if (container) container.innerHTML = '';
        return;
    }

    let html = `<div class="pagination-wrapper">`;

    // Botón Anterior
    html += `<button onclick="cargarAprendicesGP(${actual - 1})" class="btn-pagination ${actual === 1 ? 'disabled' : ''}" ${actual === 1 ? 'disabled' : ''} title="Anterior"><i class="fas fa-chevron-left"></i></button>`;

    // Números de página
    const maxVisible = 5;
    let start = Math.max(1, actual - 2);
    let end = Math.min(total, start + maxVisible - 1);

    if (end - start < maxVisible - 1) {
        start = Math.max(1, end - maxVisible + 1);
    }

    if (start > 1) {
        html += `<button onclick="cargarAprendicesGP(1)" class="btn-pagination">1</button>`;
        if (start > 2) html += `<span class="pagination-ellipsis">...</span>`;
    }

    for (let i = start; i <= end; i++) {
        html += `<button onclick="cargarAprendicesGP(${i})" class="btn-pagination ${i === actual ? 'active' : ''}">${i}</button>`;
    }

    if (end < total) {
        if (end < total - 1) html += `<span class="pagination-ellipsis">...</span>`;
        html += `<button onclick="cargarAprendicesGP(${total})" class="btn-pagination">${total}</button>`;
    }

    // Botón Siguiente
    html += `<button onclick="cargarAprendicesGP(${actual + 1})" class="btn-pagination ${actual === total ? 'disabled' : ''}" ${actual === total ? 'disabled' : ''} title="Siguiente"><i class="fas fa-chevron-right"></i></button>`;

    html += `</div>`;
    container.innerHTML = html;
}

// ========== VOCEROS ENFOQUE DIFERENCIAL ==========

window.mostrarModalVoceroEnfoque = async function () {
    const modal = document.createElement('div');
    modal.id = 'modalVoceroEnfoque';
    modal.style.cssText = `
        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0,0,0,0.6); z-index: 10001;
        display: flex; align-items: center; justify-content: center;
    `;

    try {
        const res = await fetch('api/voceros_enfoque.php');
        const r = await res.json();
        if (!r.success) throw new Error(r.message);

        const voceros = r.data;

        // Preparar lista de aprendices para el buscador (usando todosAprendices si está disponible)
        let opcionesAprendices = '';
        if (typeof todosAprendices !== 'undefined' && todosAprendices.length > 0) {
            opcionesAprendices = todosAprendices.map(a =>
                `<option value="${a.documento}">${a.nombre} ${a.apellido} - ${a.numero_ficha}</option>`
            ).join('');
        }

        modal.innerHTML = `
            <div style="background: white; padding: 30px; border-radius: 12px; width: 600px; max-width: 95%; max-height: 90vh; overflow-y: auto;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 2px solid #39A900; padding-bottom: 10px;">
                    <h2 style="margin: 0; color: #39A900;"><i class="fas fa-user-tie"></i> Voceros Enfoque Diferencial</h2>
                    <button onclick="document.getElementById('modalVoceroEnfoque').remove()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
                </div>
                
                <p style="color: #666; margin-bottom: 20px;">Asigne un vocero representante para cada una de las 6 poblaciones del enfoque diferencial.</p>
                
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="text-align: left; border-bottom: 1px solid #ddd;">
                            <th style="padding: 10px;">Tipo de Población</th>
                            <th style="padding: 10px;">Vocero Asignado</th>
                            <th style="padding: 10px; text-align: center;">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${voceros.map(v => {
            const slug = v.tipo_poblacion.toLowerCase().includes('ind') ? 'indigena' :
                v.tipo_poblacion.toLowerCase().includes('mujer') ? 'mujer' :
                    v.tipo_poblacion.toLowerCase().includes('narp') ? 'narp' :
                        v.tipo_poblacion.toLowerCase().includes('camp') ? 'campesino' :
                            v.tipo_poblacion.toLowerCase().includes('lgbt') ? 'lgbtiq' : 'discapacidad';
            return `
                            <tr style="border-bottom: 1px solid #eee;">
                                <td style="padding: 15px 10px; font-weight: 600;">
                                    <span onclick="window.location.href='admin-poblacion-detalle.html?tipo=${slug}'"
                                          style="color: #39A900; cursor: pointer; text-decoration: underline;"
                                          title="Ver listado y propuestas">
                                        ${v.tipo_poblacion}
                                    </span>
                                </td>
                                <td style="padding: 10px;">
                                    <div id="info-vocero-${v.tipo_poblacion.replace('+', 'plus')}" style="font-size: 0.9rem;">
                                        ${v.documento ? `
                                            <div style="color: #333; font-weight: 500;">${v.nombre} ${v.apellido}</div>
                                            <div style="color: #666; font-size: 0.8rem;">Ficha: ${v.numero_ficha}</div>
                                        ` : '<span style="color: #999; font-style: italic;">No asignado</span>'}
                                    </div>
                                    <div id="edit-vocero-${v.tipo_poblacion.replace('+', 'plus')}" style="display: none;">
                                        <input list="list-aprendices-enfoque" id="input-vocero-${v.tipo_poblacion.replace('+', 'plus')}"
                                               placeholder="Buscar por documento..."
                                               style="width: 100%; padding: 5px; border: 1px solid #ccc; border-radius: 4px;">
                                    </div>
                                </td>
                                <td style="padding: 10px; text-align: center;">
                                    <button id="btn-edit-${v.tipo_poblacion.replace('+', 'plus')}"
                                            onclick="habilitarEdicionVocero('${v.tipo_poblacion}')"
                                            class="btn-primary" style="padding: 5px 10px; font-size: 0.75rem; background: #6b7280;">
                                        Cambiar
                                    </button>
                                    <button id="btn-save-${v.tipo_poblacion.replace('+', 'plus')}"
                                            onclick="guardarCambioVocero('${v.tipo_poblacion}')"
                                            class="btn-primary" style="padding: 5px 10px; font-size: 0.75rem; display: none;">
                                        Guardar
                                    </button>
                                </td>
                            </tr>
                            `;
        }).join('')}
                    </tbody>
                </table>

                <datalist id="list-aprendices-enfoque">
                    ${opcionesAprendices}
                </datalist>

                </div>
            </div>
        `;

        document.body.appendChild(modal);

    } catch (error) {
        console.error(error);
        mostrarNotificacion('Error al cargar voceros de enfoque', 'error');
    }
};

window.habilitarEdicionVocero = function (tipo) {
    const id = tipo.replace('+', 'plus');
    document.getElementById(`info-vocero-${id}`).style.display = 'none';
    document.getElementById(`edit-vocero-${id}`).style.display = 'block';
    document.getElementById(`btn-edit-${id}`).style.display = 'none';
    document.getElementById(`btn-save-${id}`).style.display = 'inline-block';
};

window.guardarCambioVocero = async function (tipo) {
    const id = tipo.replace('+', 'plus');
    const documento = document.getElementById(`input-vocero-${id}`).value;
    const btn = document.getElementById(`btn-save-${id}`);

    try {
        btn.disabled = true;
        btn.textContent = '...';

        const res = await fetch('api/voceros_enfoque.php', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ tipo_poblacion: tipo, documento: documento })
        });

        const r = await res.json();
        if (r.success) {
            mostrarNotificacion('Vocero actualizado', 'success');
            // Recargar modal para mostrar info actualizada
            document.getElementById('modalVoceroEnfoque').remove();
            mostrarModalVoceroEnfoque();
        } else {
            mostrarNotificacion(r.message, 'error');
        }
    } catch (e) {
        console.error(e);
        mostrarNotificacion('Error al guardar', 'error');
    } finally {
        btn.disabled = false;
        btn.textContent = 'Guardar';
    }
};

// ========== REPRESENTANTES DE JORNADA ==========

window.mostrarModalRepresentantes = async function () {
    const modal = document.createElement('div');
    modal.id = 'modalRepresentantes';
    modal.style.cssText = `
    position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0, 0, 0, 0.6); z-index: 10001;
    display: flex; align-items: center; justify-content: center;
    `;

    try {
        const res = await fetch('api/representantes_jornada.php');
        const r = await res.json();
        if (!r.success) throw new Error(r.message);

        const representantes = r.data;

        let opcionesAprendices = '';
        if (typeof todosAprendices !== 'undefined' && todosAprendices.length > 0) {
            opcionesAprendices = todosAprendices.map(a =>
                `<option value="${a.documento}">${a.nombre} ${a.apellido} - ${a.numero_ficha}</option>`
            ).join('');
        }

        modal.innerHTML = `
        <div style="background: white; padding: 30px; border-radius: 12px; width: 600px; max-width: 95%; max-height: 90vh; overflow-y: auto;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 2px solid #ea580c; padding-bottom: 10px;">
                    <h2 style="margin: 0; color: #ea580c;"><i class="fas fa-users"></i> Representantes de Jornada</h2>
                    <button onclick="document.getElementById('modalRepresentantes').remove()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
                </div>
                
                <p style="color: #666; margin-bottom: 20px;">Asigne un representante para las jornadas Diurna y Mixta.</p>
                
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="text-align: left; border-bottom: 1px solid #ddd;">
                            <th style="padding: 10px;">Jornada</th>
                            <th style="padding: 10px;">Representante Asignado</th>
                            <th style="padding: 10px; text-align: center;">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${representantes.map(rep => `
                            <tr style="border-bottom: 1px solid #eee;">
                                <td style="padding: 15px 10px; font-weight: 600;">${rep.jornada}</td>
                                <td style="padding: 10px;">
                                    <div id="info-rep-${rep.jornada}" style="font-size: 0.9rem;">
                                        ${rep.documento ? `
                                            <div style="color: #333; font-weight: 500;">${rep.nombre} ${rep.apellido}</div>
                                            <div style="color: #666; font-size: 0.8rem;">Ficha: ${rep.numero_ficha}</div>
                                        ` : '<span style="color: #999; font-style: italic;">No asignado</span>'}
                                    </div>
                                    <div id="edit-rep-${rep.jornada}" style="display: none;">
                                        <input list="list-aprendices-rep" id="input-rep-${rep.jornada}" 
                                               placeholder="Buscar por documento..." 
                                               style="width: 100%; padding: 5px; border: 1px solid #ccc; border-radius: 4px;">
                                    </div>
                                </td>
                                <td style="padding: 10px; text-align: center;">
                                    <button id="btn-edit-rep-${rep.jornada}" 
                                            onclick="habilitarEdicionRepresentante('${rep.jornada}')" 
                                            class="btn-primary" style="padding: 5px 10px; font-size: 0.75rem; background: #6b7280;">
                                        Cambiar
                                    </button>
                                    <button id="btn-save-rep-${rep.jornada}" 
                                            onclick="guardarCambioRepresentante('${rep.jornada}')" 
                                            class="btn-primary" style="padding: 5px 10px; font-size: 0.75rem; display: none;">
                                        Guardar
                                    </button>
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>

                <datalist id="list-aprendices-rep">
                    ${opcionesAprendices}
                </datalist>

                <div style="margin-top: 30px; text-align: right;">
                    <button onclick="document.getElementById('modalRepresentantes').remove()" class="btn-primary" style="background: #6b7280;">Cerrar</button>
                </div>
            </div>
        `;

        document.body.appendChild(modal);

    } catch (error) {
        console.error(error);
        mostrarNotificacion('Error al cargar representantes', 'error');
    }
};

window.habilitarEdicionRepresentante = function (jornada) {
    document.getElementById(`info-rep-${jornada}`).style.display = 'none';
    document.getElementById(`edit-rep-${jornada}`).style.display = 'block';
    document.getElementById(`btn-edit-rep-${jornada}`).style.display = 'none';
    document.getElementById(`btn-save-rep-${jornada}`).style.display = 'inline-block';
};

window.guardarCambioRepresentante = async function (jornada) {
    const documento = document.getElementById(`input-rep-${jornada}`).value;
    const btn = document.getElementById(`btn-save-rep-${jornada}`);

    try {
        btn.disabled = true;
        btn.textContent = '...';

        const res = await fetch('api/representantes_jornada.php', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ jornada: jornada, documento: documento })
        });

        const r = await res.json();
        if (r.success) {
            mostrarNotificacion('Representante actualizado', 'success');
            document.getElementById('modalRepresentantes').remove();
            mostrarModalRepresentantes();
        } else {
            mostrarNotificacion(r.message, 'error');
        }
    } catch (e) {
        console.error(e);
        mostrarNotificacion('Error al guardar', 'error');
    } finally {
        btn.disabled = false;
        btn.textContent = 'Guardar';
    }
};

function getClaseEstado(estado) {
    if (!estado) return 'badge-secondary';
    const normalized = estado.toLowerCase().replace(/ /g, '-');
    return `badge-${normalized}`;
}

function mostrarNotificacion(mensaje, tipo = 'success') {
    const toast = document.createElement('div');
    toast.style.cssText = `
        position: fixed; top: 20px; right: 20px; padding: 15px 20px;
        background: ${tipo === 'success' ? '#10b981' : tipo === 'error' ? '#ef4444' : '#3b82f6'};
        color: white; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        z-index: 11000; animation: slideIn 0.3s ease;
    `;
    toast.textContent = mensaje;
    document.body.appendChild(toast);
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transition = 'opacity 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}



