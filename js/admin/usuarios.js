/**
 * Gestión de Usuarios - Admin
 */

let todosUsuarios = [];
let paginaActual = 1;
let totalPaginas = 1;
const ITEMS_POR_PAGINA = 10;

document.addEventListener('DOMContentLoaded', () => {
    cargarUsuarios();

    // Event listeners para filtros
    document.getElementById('filtroSearch')?.addEventListener('input', debounce(aplicarFiltros, 500));
    document.getElementById('filtroRol')?.addEventListener('change', aplicarFiltros);
    document.getElementById('filtroEstado')?.addEventListener('change', aplicarFiltros);
});

async function cargarUsuarios(pagina = 1) {
    try {
        const search = document.getElementById('filtroSearch')?.value || '';
        const rol = document.getElementById('filtroRol')?.value || '';
        const estado = document.getElementById('filtroEstado')?.value || '';

        let url = `api/usuarios.php?page=${pagina}&limit=${ITEMS_POR_PAGINA}`;
        if (search) url += `&search=${encodeURIComponent(search)}`;
        if (rol) url += `&rol=${encodeURIComponent(rol)}`;
        if (estado) url += `&estado=${encodeURIComponent(estado)}`;

        const response = await fetch(url);
        const result = await response.json();

        if (result.success) {
            todosUsuarios = result.data;
            paginaActual = result.pagination.page;
            totalPaginas = result.pagination.pages;

            mostrarUsuarios(todosUsuarios);
            actualizarPaginacion(result.pagination);
        }
    } catch (error) {
        console.error('Error cargando usuarios:', error);
        mostrarNotificacion('Error al cargar usuarios', 'error');
    }
}

function aplicarFiltros() {
    paginaActual = 1;
    cargarUsuarios(1);
}

function cambiarPagina(pagina) {
    if (pagina < 1 || pagina > totalPaginas) return;
    cargarUsuarios(pagina);
}

function actualizarPaginacion(pagination) {
    const paginacionDiv = document.getElementById('paginacion');
    if (!paginacionDiv || pagination.pages <= 1) {
        if (paginacionDiv) paginacionDiv.innerHTML = '';
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
    paginacionDiv.innerHTML = html;
}

function mostrarUsuarios(usuarios) {
    const tbody = document.getElementById('tablaUsuarios');

    if (!usuarios || usuarios.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" style="text-align: center;">No hay usuarios registrados</td></tr>';
        return;
    }

    // Cargar estado biométrico para cada usuario
    usuarios.forEach(async (u) => {
        const estadoBio = await consultarEstadoBiometria('usuario', u.id_usuario);
        u.tiene_biometria = estadoBio.tiene_biometria || false;
    });

    // Calcular el índice inicial basado en la página actual
    const startIndex = (paginaActual - 1) * ITEMS_POR_PAGINA;

    // Renderizar después de un pequeño delay para que carguen los estados
    setTimeout(() => {
        tbody.innerHTML = usuarios.map((u, index) => {
            const biometriaStatus = u.tiene_biometria 
                ? `<i class="fas fa-check-circle color-success" title="Biometría registrada" style="margin-right: 5px;"></i>` 
                : `<i class="fas fa-times-circle color-error" title="Sin biometría" style="margin-right: 5px;"></i>`;

            return `
                <tr class="table-row-divider">
                    <td class="td-index">${startIndex + index + 1}</td>
                    <td>${u.nombre || ''} ${u.apellido || ''}</td>
                    <td>${u.correo}</td>
                    <td>${u.telefono || ''}</td>
                    <td><span class="badge ${u.rol === 'director' ? 'badge-primary' : u.rol === 'instructor' ? 'badge-info' : u.rol === 'coordinador' ? 'badge-warning' : 'badge-success'}">${u.rol.toUpperCase()}</span></td>
                    <td>
                        <select onchange="cambiarEstadoUsuario('${u.id_usuario}', this.value)" 
                                class="status-select-user ${esActivo(u.estado) ? 'bg-success-user' : 'bg-error-user'}">
                            <option value="activo" ${esActivo(u.estado) ? 'selected' : ''}>Activo</option>
                            <option value="inactivo" ${!esActivo(u.estado) ? 'selected' : ''}>Inactivo</option>
                        </select>
                    </td>
                    <td>
                        <div class="btn-action-container">
                            ${biometriaStatus}
                            <button onclick="registrarBiometriaUsuario('${u.id_usuario}')" 
                                    class="btn-action btn-action-purple" 
                                    title="Registrar/Actualizar Biometría">
                                <i class="fas fa-camera"></i>
                            </button>
                            <button onclick="editarUsuario('${u.id_usuario}')" 
                                    class="btn-action btn-action-blue" 
                                    title="Editar Usuario">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="eliminarUsuario('${u.id_usuario}')" 
                                    class="btn-action btn-action-red" 
                                    title="Eliminar Usuario">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
    }, 300);
}

function esActivo(e) {
    if (!e && e !== 0) return false;
    const val = e.toString().trim().toLowerCase();
    return val === 'activo' || val === '1';
}

function nuevoUsuario() {
    document.getElementById('modalTitle').textContent = 'Nuevo Usuario';
    document.getElementById('formUsuario').reset();
    document.getElementById('idUsuarioEdicion').value = ''; // Limpiar ID
    document.getElementById('contrasenaUsuario').required = true; // Contraseña obligatoria al crear

    // Cambiar texto del botón a Guardar
    const btnSubmit = document.querySelector('#formUsuario button[type="submit"]');
    if (btnSubmit) btnSubmit.textContent = 'Guardar';

    document.getElementById('modalUsuario').style.display = 'flex';
}

function cerrarModal() {
    document.getElementById('modalUsuario').style.display = 'none';
}

function editarUsuario(idUsuario) {
    const usuario = todosUsuarios.find(u => u.id_usuario == idUsuario);
    if (!usuario) {
        mostrarNotificacion('Usuario no encontrado', 'error');
        return;
    }

    document.getElementById('modalTitle').textContent = 'Editar Usuario';
    document.getElementById('idUsuarioEdicion').value = usuario.id_usuario;

    const documentoInput = document.getElementById('documentoUsuario');
    documentoInput.value = usuario.id_usuario; // El id_usuario ES el documento
    documentoInput.setAttribute('data-original-documento', usuario.id_usuario); // Guardar valor original

    // Mostrar nota visual para el campo de documento
    let documentoNote = document.getElementById('documentoChangeNote');
    if (!documentoNote) {
        documentoNote = document.createElement('small');
        documentoNote.id = 'documentoChangeNote';
        documentoNote.className = 'document-change-note';
        documentoNote.textContent = '⚠ Cambiar el documento requiere confirmación.';
        documentoInput.parentNode.appendChild(documentoNote);
    } else {
        documentoNote.style.display = 'block';
    }

    document.getElementById('nombresUsuario').value = usuario.nombre;
    document.getElementById('apellidosUsuario').value = usuario.apellido;
    document.getElementById('correoUsuario').value = usuario.correo;
    document.getElementById('celularUsuario').value = usuario.telefono || '';
    document.getElementById('rolUsuario').value = usuario.rol;
    document.getElementById('estadoUsuario').value = usuario.estado;

    // Contraseña opcional al editar
    document.getElementById('contrasenaUsuario').value = '';
    document.getElementById('contrasenaUsuario').required = false;

    // Cambiar texto del botón a Actualizar
    const btnSubmit = document.querySelector('#formUsuario button[type="submit"]');
    if (btnSubmit) btnSubmit.textContent = 'Actualizar';

    document.getElementById('modalUsuario').style.display = 'flex';
}

async function guardarUsuario(event) {
    event.preventDefault();

    const idEdicion = document.getElementById('idUsuarioEdicion').value;
    const esEdicion = !!idEdicion;

    const formData = {
        documento: document.getElementById('documentoUsuario').value,
        nombre: document.getElementById('nombresUsuario').value,
        apellido: document.getElementById('apellidosUsuario').value,
        correo: document.getElementById('correoUsuario').value,
        celular: document.getElementById('celularUsuario').value,
        rol: document.getElementById('rolUsuario').value.toLowerCase(),
        estado: document.getElementById('estadoUsuario').value
    };

    // Solo enviar contraseña si se escribió algo (en edición) o es obligatoria (en creación)
    const password = document.getElementById('contrasenaUsuario').value;
    if (password) {
        formData.password = password;
    }

    if (!formData.documento) {
        mostrarNotificacion('El documento es obligatorio', 'error');
        return;
    }

    // Confirmación de seguridad si el documento cambia en edición
    if (esEdicion) {
        const originalDocumento = document.getElementById('documentoUsuario').getAttribute('data-original-documento');
        if (originalDocumento && originalDocumento !== formData.documento) {
            const confirmChange = confirm(
                `Ha cambiado el número de documento del usuario de "${originalDocumento}" a "${formData.documento}".\n\n` +
                `¿Está seguro de que desea realizar este cambio? Esto podría afectar la integridad de los datos relacionados.`
            );
            if (!confirmChange) {
                mostrarNotificacion('Cambio de documento cancelado', 'info');
                return;
            }
        }
    }

    try {
        let url = 'api/usuarios.php';
        let method = 'POST';

        if (esEdicion) {
            url += `?id=${idEdicion}`; // Para PUT, a veces es mejor pasar ID en URL o Body
            method = 'PUT';
            formData.id_usuario = idEdicion;
        }

        const response = await fetch(url, {
            method: method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(formData)
        });

        const result = await response.json();

        if (result.success) {
            mostrarNotificacion(esEdicion ? 'Usuario actualizado exitosamente' : 'Usuario creado exitosamente', 'success');
            cerrarModal();
            cargarUsuarios(paginaActual); // Recargar datos para ver cambios reflejados
        } else {
            mostrarNotificacion(result.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarNotificacion('Error al guardar usuario', 'error');
    }
}

async function cambiarEstadoUsuario(id, nuevoEstado) {
    try {
        // Mostrar loading
        const loadingHtml = '<i class="fas fa-spinner fa-spin"></i> Actualizando...';
        const selectElement = event.target;
        const originalHtml = selectElement.outerHTML;
        
        // Deshabilitar el select durante la actualización
        selectElement.disabled = true;
        selectElement.innerHTML = `<option>${loadingHtml}</option>`;
        
        const response = await fetch(`api/usuarios.php?id=${id}`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ estado: nuevoEstado })
        });

        const result = await response.json();

        if (result.success) {
            // Mostrar notificación de éxito
            mostrarNotificacion(`Estado ${nuevoEstado === 'activo' ? 'activado' : 'inactivado'} correctamente`, 'success');
            
            // Recargar la tabla para mostrar el cambio
            await cargarUsuarios(paginaActual);
        } else {
            // Restaurar el select original
            selectElement.outerHTML = originalHtml;
            mostrarNotificacion(result.message || 'Error al cambiar estado', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarNotificacion('Error de conexión al cambiar estado', 'error');
        
        // Recargar la página en caso de error grave
        setTimeout(() => {
            location.reload();
        }, 2000);
    }
}

async function eliminarUsuario(id) {
    if (!confirm('¿Está seguro de eliminar este usuario?')) return;

    try {
        const response = await fetch(`api/usuarios.php?id=${id}`, {
            method: 'DELETE'
        });

        const result = await response.json();

        if (result.success) {
            mostrarNotificacion('Usuario eliminado', 'success');
            cargarUsuarios();
        } else {
            mostrarNotificacion(result.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarNotificacion('Error al eliminar usuario', 'error');
    }
}

async function exportarUsuariosExcel() {
    try {
        mostrarNotificacion('Generando reporte Excel...', 'info');

        // Obtener filtros actuales
        const search = document.getElementById('filtroSearch')?.value || '';
        const rol = document.getElementById('filtroRol')?.value || '';
        const estado = document.getElementById('filtroEstado')?.value || '';

        // Solicitar TODOS los datos (limit=-1)
        let url = `api/usuarios.php?limit=-1`;
        if (search) url += `&search=${encodeURIComponent(search)}`;
        if (rol) url += `&rol=${encodeURIComponent(rol)}`;
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
                        <th>No.</th>
                        <th>Usuario</th>
                        <th>Correo</th>
                        <th>Celular</th>
                        <th>Rol</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
        `;

        result.data.forEach((u, index) => {
            table += `
                <tr>
                    <td>${index + 1}</td>
                    <td>${u.nombre} ${u.apellido}</td>
                    <td>${u.correo}</td>
                    <td>${u.telefono || 'N/A'}</td>
                    <td>${u.rol.toUpperCase()}</td>
                    <td>${u.estado.toUpperCase()}</td>
                </tr>
            `;
        });

        table += '</tbody></table>';

        const blob = new Blob(['\uFEFF' + table], { type: 'application/vnd.ms-excel;charset=utf-8' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = `Usuarios_${new Date().toISOString().split('T')[0]}.xls`;
        link.click();

        mostrarNotificacion('Excel exportado exitosamente', 'success');
    } catch (error) {
        console.error('Error exportando Excel:', error);
        mostrarNotificacion('Error al exportar datos', 'error');
    }
}

function mostrarNotificacion(mensaje, tipo = 'info') {
    const toast = document.createElement('div');
    toast.className = `toast-notification toast-${tipo === 'info' ? 'blue' : tipo}`;
    toast.textContent = mensaje;
    document.body.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(100%)';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}


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

// ==================== FUNCIONES DE BIOMETRÍA ====================

/**
 * Abre modal para registrar biometría facial del usuario (instructor/admin)
 */
async function registrarBiometriaUsuario(idUsuario) {
    // 1. Buscar usuario en el array local
    const usuario = todosUsuarios.find(u => u.id_usuario == idUsuario);

    if (!usuario) {
        mostrarNotificacion('Usuario no encontrado', 'error');
        return;
    }

    // 2. NO permitir registro biométrico para usuarios INACTIVOS
    if (usuario.estado === 'inactivo' || usuario.estado === '0' || usuario.estado === 0) {
        mostrarNotificacion(
            `No se puede registrar biometría para usuarios INACTIVOS. ${usuario.nombre} ${usuario.apellido} no está activo en el sistema.`,
            'error'
        );
        return;
    }

    // 3. Verificar si ya tiene biometría registrada
    try {
        const estadoBio = await consultarEstadoBiometria('usuario', idUsuario);

        if (estadoBio.success && estadoBio.tiene_biometria) {
            const confirmar = confirm(
                `${usuario.nombre} ${usuario.apellido} ya tiene biometría registrada.\n\n` +
                `¿Desea RE-REGISTRAR (actualizar) la biometría?\n\n` +
                `⚠ Esto reemplazará el registro anterior.`
            );

            if (!confirmar) {
                mostrarNotificacion('Registro biométrico cancelado', 'info');
                return;
            }
        }
    } catch (error) {
        console.error('Error consultando estado biométrico:', error);
    }

    // 4. Crear modal de captura facial
    const modal = document.createElement('div');
    modal.id = 'modalBiometriaUsuario';
    modal.className = 'modal-biometria-wrapper';

    modal.innerHTML = `
        <div class="biometria-container">
            <div class="modal-header-flex">
                <h3 class="modal-title">Registro Biométrico - ${usuario.nombre} ${usuario.apellido}</h3>
                <button onclick="cerrarModalBiometriaUsuario()" class="btn-close-modal">&times;</button>
            </div>
            
            <div class="biometria-status-wrap">
                <p class="biometria-hint">Coloque su rostro dentro del círculo guía</p>
                <p class="biometria-status-text" id="estadoDeteccion">Inicializando cámara...</p>
            </div>
            
            <div class="biometria-video-wrap">
                <video id="videoBiometriaUsuario" autoplay playsinline class="biometria-video"></video>
                <canvas id="canvasBiometriaUsuario" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none;"></canvas>
                <div id="overlayGuiaUsuario" class="biometria-guide"></div>
            </div>
            
            <div id="mensajeCapturaUsuario" class="biometria-message-area"></div>
            
            <div class="modal-footer-flex">
                <button id="btnCapturarUsuario" onclick="capturarBiometriaUsuario('${idUsuario}')" 
                        class="btn-primary btn-success-capture" style="display: none;" disabled>
                    <i class="fas fa-camera"></i> Capturar Rostro
                </button>
                <button onclick="cerrarModalBiometriaUsuario()" class="btn-primary btn-cancel-modal">
                    Cancelar
                </button>
            </div>
        </div>
    `;

    document.body.appendChild(modal);

    // Iniciar cámara
    try {
        const stream = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: 'user', width: 640, height: 480 }
        });
        const video = document.getElementById('videoBiometriaUsuario');
        video.srcObject = stream;
        window.streamBiometriaUsuario = stream;

        // Esperar a que el video esté listo
        await new Promise(resolve => {
            video.onloadedmetadata = () => {
                video.play();
                resolve();
            };
        });

        // Iniciar detección en tiempo real
        iniciarDeteccionTiempoReal(video);

    } catch (error) {
        console.error('Error accediendo a la cámara:', error);
        mostrarNotificacion('Error al acceder a la cámara', 'error');
        cerrarModalBiometriaUsuario();
    }
}

/**
 * Detecta rostros en tiempo real y actualiza la UI
 */
async function iniciarDeteccionTiempoReal(videoElement) {
    const overlayGuia = document.getElementById('overlayGuiaUsuario');
    const estadoDeteccion = document.getElementById('estadoDeteccion');
    const btnCapturar = document.getElementById('btnCapturarUsuario');

    if (!overlayGuia || !estadoDeteccion) return;

    window.deteccionBiometriaActiva = true;
    window.capturaEnProceso = false;
    let rostroBuenoDetectado = false;

    async function detectar() {
        if (!window.deteccionBiometriaActiva || !videoElement || window.capturaEnProceso) return;

        try {
            // Verificar si Human está disponible
            if (typeof Human === 'undefined') {
                setTimeout(detectar, 500);
                return;
            }

            const human = await getHuman();
            const result = await human.detect(videoElement);

            // Limpiar y preparar canvas para dibujar landmarks
            const canvas = document.getElementById('canvasBiometriaUsuario');
            if (canvas) {
                const ctx = canvas.getContext('2d');
                canvas.width = videoElement.videoWidth;
                canvas.height = videoElement.videoHeight;
                ctx.clearRect(0, 0, canvas.width, canvas.height);
            }

            if (result.face && result.face.length === 1) {
                const face = result.face[0];
                const confianza = face.faceScore || face.boxScore || 0;

                // Dibujar landmarks
                if (canvas && face.mesh && typeof dibujarLandmarksFaciales === 'function') {
                    dibujarLandmarksFaciales(canvas, face.mesh, confianza);
                }

                if (confianza >= 0.7 && !rostroBuenoDetectado) {
                    overlayGuia.style.borderColor = '#10b981';
                    overlayGuia.style.borderWidth = '4px';
                    overlayGuia.style.borderStyle = 'solid';
                    estadoDeteccion.textContent = '✓ Rostro detectado correctamente';
                    estadoDeteccion.style.color = '#10b981';
                    btnCapturar.disabled = false;
                    btnCapturar.style.opacity = '1';

                    // CAPTURA AUTOMÁTICA INMEDIATA
                    rostroBuenoDetectado = true;
                    window.capturaEnProceso = true;
                    estadoDeteccion.textContent = '📸 Capturando...';
                    estadoDeteccion.style.color = '#3b82f6';

                    // Obtener el ID del usuario desde el botón
                    const idUsuario = btnCapturar.getAttribute('onclick').match(/'([^']+)'/)[1];

                    // Capturar inmediatamente
                    capturarBiometriaUsuario(idUsuario);
                } else if (confianza < 0.7) {
                    overlayGuia.style.borderColor = '#f59e0b';
                    overlayGuia.style.borderStyle = 'solid';
                    estadoDeteccion.textContent = 'Mejore la iluminación';
                    estadoDeteccion.style.color = '#f59e0b';
                    btnCapturar.disabled = true;
                    btnCapturar.style.opacity = '0.5';
                }
            } else if (result.face && result.face.length > 1) {
                overlayGuia.style.borderColor = '#ef4444';
                overlayGuia.style.borderStyle = 'solid';
                estadoDeteccion.textContent = '⚠ Múltiples rostros detectados';
                estadoDeteccion.style.color = '#ef4444';
                btnCapturar.disabled = true;
                btnCapturar.style.opacity = '0.5';
            } else {
                overlayGuia.style.borderColor = '#6b7280';
                overlayGuia.style.borderStyle = 'dashed';
                estadoDeteccion.textContent = 'Buscando rostro...';
                estadoDeteccion.style.color = '#6b7280';
                btnCapturar.disabled = true;
                btnCapturar.style.opacity = '0.5';
            }

            if (window.deteccionBiometriaActiva && !window.capturaEnProceso) {
                requestAnimationFrame(detectar);
            }

        } catch (error) {
            console.error('Error en detección:', error);
            if (window.deteccionBiometriaActiva) setTimeout(detectar, 1000);
        }
    }

    // Iniciar detección inmediatamente
    detectar();
}

/**
 * Captura y registra el embedding facial del usuario
 */
async function capturarBiometriaUsuario(idUsuario) {
    const video = document.getElementById('videoBiometriaUsuario');
    const btnCapturar = document.getElementById('btnCapturarUsuario');
    const mensaje = document.getElementById('mensajeCapturaUsuario');

    btnCapturar.disabled = true;
    mensaje.textContent = 'Capturando rostro...';
    mensaje.style.color = '#3b82f6';

    try {
        const resultado = await capturarRostro(video);

        if (!resultado.success) {
            mensaje.textContent = resultado.mensaje;
            mensaje.style.color = '#ef4444';
            btnCapturar.disabled = false;
            return;
        }

        mensaje.textContent = `Rostro capturado (${Math.round(resultado.confianza * 100)}%). Guardando...`;

        const registroResult = await registrarBiometria('usuario', idUsuario, resultado.embedding);

        if (registroResult.success) {
            mostrarNotificacion('Biometría registrada exitosamente', 'success');
            cerrarModalBiometriaUsuario();
            cargarUsuarios(paginaActual);
        } else {
            mensaje.textContent = 'Error al guardar: ' + registroResult.message;
            mensaje.style.color = '#ef4444';
            btnCapturar.disabled = false;
        }
    } catch (error) {
        console.error('Error capturando biometría:', error);
        mensaje.textContent = 'Error técnico al capturar rostro';
        mensaje.style.color = '#ef4444';
        btnCapturar.disabled = false;
    }
}

/**
 * Cierra el modal de biometría y detiene la cámara
 */
function cerrarModalBiometriaUsuario() {
    window.deteccionBiometriaActiva = false;

    if (window.streamBiometriaUsuario) {
        window.streamBiometriaUsuario.getTracks().forEach(track => track.stop());
        window.streamBiometriaUsuario = null;
    }

    const modal = document.getElementById('modalBiometriaUsuario');
    if (modal) {
        modal.remove();
    }
}

/**
 * Exportar Usuarios a PDF con cabecera profesional (Referencia Imagen 3)
 */
async function exportarUsuariosPDF() {
    if (!todosUsuarios || todosUsuarios.length === 0) {
        mostrarNotificacion('No hay datos para exportar', 'warning');
        return;
    }

    const { jsPDF } = window.jspdf;
    const doc = new jsPDF('p', 'mm', 'a4');
    const fecha = new Date().toLocaleDateString('es-CO', {
        year: 'numeric', month: 'long', day: 'numeric'
    });

    // --- ENCABEZADO ---
    let startY = 70; // Mayor margen para portrait
    if (typeof SenaPrePDF !== 'undefined') {
        startY = await SenaPrePDF.crearCabecera(doc, {
            titulo:      'Reporte General de Usuarios',
            subtitulo:   `Generado el ${fecha}`,
            orientacion: 'portrait'
        });
    }

    // ── TABLA DE DATOS ──────────────────────
    const head = [['Documento', 'Nombres', 'Apellidos', 'Correo', 'Rol', 'Estado']];
    const body = todosUsuarios.map(u => [
        u.id_usuario,
        u.nombre,
        u.apellido,
        u.correo,
        u.rol.toUpperCase(),
        u.estado.toUpperCase()
    ]);

    doc.autoTable({
        startY: startY,
        head: head,
        body: body,
        theme: 'grid',
        headStyles: {
            fillColor: [57, 169, 0], // Verde SENA
            textColor: 255,
            fontStyle: 'bold',
            halign: 'center'
        },
        styles: {
            fontSize: 8,
            cellPadding: 3
        },
        alternateRowStyles: {
            fillColor: [245, 245, 245]
        },
        columnStyles: {
            0: { cellWidth: 25 },
            3: { cellWidth: 50 },
            4: { cellWidth: 25 },
            5: { cellWidth: 20 }
        },
        didDrawPage: () => {
            if (typeof SenaPrePDF !== 'undefined') SenaPrePDF.pieDePagina(doc);
        }
    });

    doc.save(`Reporte_Usuarios_${new Date().getTime()}.pdf`);
    mostrarNotificacion('Reporte PDF generado exitosamente', 'success');
}
