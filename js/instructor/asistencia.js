// IMPORTANTE: Esta constante debe coincidir con MINUTOS_TOLERANCIA en api/asistencias.php
const MINUTOS_TOLERANCIA = 30; // Tiempo máximo para llegar tarde sin marcar retardo

let aprendicesActuales = [];
let asistenciaGuardada = false;
let modoVerificacionInstructor = false;
let modoGrupalActivo = false;
let fichasData = []; // Almacenar datos completos de fichas para validaciones

document.addEventListener('DOMContentLoaded', async () => {
    console.log('=== Iniciando Módulo de Asistencia ===');
    try {
        // Asegurar que la fecha se establezca primero para evitar campos vacíos visualmente
        establecerFechaActual();
        await cargarFichasInstructor();
    } catch (error) {
        console.error('Error crítico en inicialización:', error);
        mostrarNotificacion('Error al iniciar el módulo de asistencia', 'error');
    }
});

// ... (código sin cambios hasta cargarAprendices) ...

// Función auxiliar para calcular estado basado en hora
function calcularEstadoAsistencia(fichaId) {
    const ficha = fichasData.find(f => f.numero_ficha == fichaId);
    if (!ficha || !ficha.hora_inicio) return 'Presente'; // Si no hay horario, asumir a tiempo

    const ahora = new Date();
    const [horaInicio, minInicio] = ficha.hora_inicio.split(':').map(Number);

    // Crear fecha con hora de inicio de la clase hoy
    const fechaInicioClase = new Date();
    fechaInicioClase.setHours(horaInicio, minInicio, 0, 0);

    // Sumar tolerancia
    const fechaLimite = new Date(fechaInicioClase.getTime() + MINUTOS_TOLERANCIA * 60000);

    // Comparar
    if (ahora > fechaLimite) {
        return 'Retardo';
    }
    return 'Presente';
}


function esFechaHoy(fechaString) {
    const hoy = new Date().toISOString().split('T')[0];
    return fechaString === hoy;
}


function establecerFechaActual() {
    const ahora = new Date();
    const fechaHoy = new Date(ahora.getTime() - (ahora.getTimezoneOffset() * 60000)).toISOString().split('T')[0];
    const inputFecha = document.getElementById('fechaAsistencia');
    inputFecha.value = fechaHoy;
}

async function cargarFichasInstructor() {
    console.log('Cargando fichas simple...');
    try {
        const user = authSystem.getCurrentUser();
        const idUsuario = user ? user.id_usuario : '';
        // Lógica unificada de reportes.js
        const response = await fetch(`api/instructor-fichas.php?id_usuario=${idUsuario}`);
        const result = await response.json();

        if (result.success) {
            fichasData = result.data; // Mantener referencia global
            const select = document.getElementById('fichaSelect');

            const fichaOptions = result.data.map(f => {
                const tieneClase = f.tiene_clase_hoy == 1;
                const textoExtra = tieneClase ? ' (Clase Hoy)' : '';
                return `<option value="${f.numero_ficha}" ${tieneClase ? 'selected' : ''}>${f.numero_ficha} - ${f.nombre_programa || 'Sin programa'}${textoExtra}</option>`;
            }).join('');

            select.innerHTML = '<option value="">Seleccione una ficha...</option>' + fichaOptions;

            // Disparar evento si hay preselección
            if (select.value) {
                select.dispatchEvent(new Event('change'));
            }
        }
    } catch (error) {
        console.error('Error cargando fichas:', error);
    }
}

async function cargarAprendices() {
    const ficha = document.getElementById('fichaSelect').value;
    const fecha = document.getElementById('fechaAsistencia').value;

    if (!ficha) {
        mostrarNotificacion('Por favor seleccione una ficha', 'error');
        return;
    }

    // Validar que la ficha tenga clase HOY (Deshabilitado para permitir registro flexible)
    /*
    const fichaSeleccionada = fichasData.find(f => f.numero_ficha == ficha);
    if (fichaSeleccionada && fichaSeleccionada.tiene_clase_hoy == 0) {
        // Opcional: Mostrar advertencia pero permitir continuar
        console.warn('Advertencia: Esta ficha no tiene formación programada para hoy en el sistema.');
        // mostrarNotificacion('Esta ficha no tiene formación programada para hoy. No se pueden cargar aprendices.', 'error');
        // return; 
    }
    */

    try {
        const respAprendices = await fetch(`api/aprendices.php?ficha=${ficha}&limit=-1&estado=EN FORMACION`);
        const resAprendices = await respAprendices.json();

        if (!resAprendices.success) throw new Error('Error cargando aprendices');

        const respAsistencia = await fetch(`api/asistencias.php?ficha=${ficha}&fecha=${fecha}`);
        const resAsistencia = await respAsistencia.json();

        let asistenciaMap = {};
        if (resAsistencia.success && resAsistencia.data.length > 0) {
            asistenciaGuardada = true;
            resAsistencia.data.forEach(a => {
                asistenciaMap[a.documento_aprendiz] = {
                    estado: a.estado,
                    observaciones: a.observaciones
                };
            });
            mostrarNotificacion('Cargando asistencia previamente guardada', 'info');
        } else {
            asistenciaGuardada = false;
        }

        aprendicesActuales = resAprendices.data.map(ap => ({
            ...ap,
            estado: asistenciaMap[ap.documento] ? asistenciaMap[ap.documento].estado : 'Ausente', // Default Ausente hasta que se verifique
            observaciones: asistenciaMap[ap.documento] ? asistenciaMap[ap.documento].observaciones : ''
        }));

        mostrarAprendices(aprendicesActuales);
        document.getElementById('accionesRapidas').style.display = 'block';
        document.getElementById('listaAprendices').style.display = 'block';

    } catch (error) {
        console.error('Error:', error);
        mostrarNotificacion('Error al cargar datos', 'error');
    }
}

function mostrarAprendices(aprendices) {
    const tbody = document.getElementById('tablaAprendices');
    document.getElementById('contadorAprendices').textContent = `${aprendices.length} aprendices`;

    if (aprendices.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; padding: 20px;">No hay aprendices en esta ficha</td></tr>';
        return;
    }

    tbody.innerHTML = aprendices.map((ap, index) => {
        // Determinar clase de fila y badge según estado
        let filaClass = '';
        let badgeClass = '';
        let badgeStyle = '';

        switch (ap.estado) {
            case 'Presente':
                filaClass = 'table-success';
                badgeClass = 'badge';
                badgeStyle = 'background: #39A900; color: white;'; // Verde biche
                break;
            case 'Retardado':
            case 'Retardo':
                badgeClass = 'badge';
                badgeStyle = 'background: #fbbf24; color: #332;'; // Amarillo
                break;
            case 'Con Excusa':
            case 'Justificado':
                badgeClass = 'badge';
                badgeStyle = 'background: #d97706; color: white;'; // Naranja oscuro
                break;
            case 'Ausente':
            default:
                badgeClass = 'badge';
                badgeStyle = 'background: #ef4444; color: white;'; // Rojo
                break;
        }

        return `
        <tr data-documento="${ap.documento}" id="fila_${ap.documento}" class="${filaClass}">
            <td>${index + 1}</td>
            <td>${ap.documento}</td>
            <td>
                ${ap.apellido} ${ap.nombre}
                <i class="fas fa-check-circle text-success" id="check_${ap.documento}" 
                   style="display: ${ap.estado === 'Presente' ? 'inline-block' : 'none'}; margin-left: 10px; color: #39A900;"></i>
            </td>
            <td>
                <input type="text" class="form-control form-control-sm" 
                    placeholder="Observaciones..." 
                    value="${ap.observaciones || ''}"
                    id="obs_${ap.documento}">
            </td>
            <td style="text-align: center;">
                <span class="${badgeClass}" style="${badgeStyle}" id="estado_badge_${ap.documento}">
                    ${ap.estado || 'Ausente'}
                </span>
                <input type="hidden" id="estado_input_${ap.documento}" value="${ap.estado || 'Ausente'}">
            </td>
        </tr>
        `;
    }).join('');
}

function filtrarAprendices() {
    const busqueda = document.getElementById('buscarAprendiz').value.toLowerCase();
    const filas = document.querySelectorAll('#tablaAprendices tr');

    filas.forEach(fila => {
        const texto = fila.innerText.toLowerCase();
        fila.style.display = texto.includes(busqueda) ? '' : 'none';
    });
}

// ==================== LÓGICA DE BIOMETRÍA ====================

// 1. Reconocimiento Grupal (Continuo)
async function iniciarReconocimientoGrupal() {
    modoVerificacionInstructor = false;
    modoGrupalActivo = true;

    const modal = document.getElementById('modalBiometria');
    const titulo = document.getElementById('tituloModalBio');
    const instruccion = document.getElementById('instruccionModalBio');
    const mensaje = document.getElementById('mensajeCaptura');
    const btn = document.getElementById('btnCapturar');

    modal.style.display = 'flex';
    titulo.textContent = `Reconocimiento Grupal`;
    instruccion.textContent = `Escaneando rostros continuamente...`;
    mensaje.textContent = 'Buscando rostros...';
    mensaje.style.color = '#3b82f6';

    // Ocultar botón de captura manual en modo grupal, es automático
    btn.style.display = 'none';

    try {
        const stream = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: 'user', width: 640, height: 480 }
        });
        const video = document.getElementById('videoBiometria');
        video.srcObject = stream;
        window.streamBiometria = stream;

        // Iniciar bucle de detección
        detectarBucle(video);

    } catch (error) {
        console.error('Error cámara:', error);
        mostrarNotificacion('Error al acceder a la cámara', 'error');
        modal.style.display = 'none';
    }
}

// 2. Tomar Asistencia Individual (Automático)
async function tomarAsistenciaIndividual() {
    modoVerificacionInstructor = false;
    modoGrupalActivo = true; // Reutilizamos el flag para activar el bucle, pero lo controlaremos para que se detenga al encontrar

    const modal = document.getElementById('modalBiometria');
    const titulo = document.getElementById('tituloModalBio');
    const instruccion = document.getElementById('instruccionModalBio');
    const mensaje = document.getElementById('mensajeCaptura');
    const btn = document.getElementById('btnCapturar');

    modal.style.display = 'flex';
    titulo.textContent = `Asistencia Individual Automática`;
    instruccion.textContent = `Mire a la cámara para registrar su asistencia`;
    mensaje.textContent = 'Buscando rostro...';
    mensaje.style.color = '#3b82f6';

    // Ocultar botón, ahora es automático
    if (btn) btn.style.display = 'none';

    try {
        const stream = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: 'user', width: 640, height: 480 }
        });
        const video = document.getElementById('videoBiometria');
        video.srcObject = stream;
        window.streamBiometria = stream;

        // Iniciar bucle de detección (reutilizamos la lógica del bucle pero adaptada)
        detectarBucle(video, true); // true indica modo individual (detenerse al éxito)

    } catch (error) {
        console.error('Error cámara:', error);
        mostrarNotificacion('Error al acceder a la cámara', 'error');
        modal.style.display = 'none';
    }
}

// Bucle de detección unificado
async function detectarBucle(video, esIndividual = false) {
    if (!window.streamBiometria || !modoGrupalActivo) return;

    const mensaje = document.getElementById('mensajeCaptura');
    // Intentar obtener o crear canvas para dibujo
    const canvas = document.getElementById('canvasBiometria') || crearCanvasOverlay(video);

    try {
        const human = await getHuman();

        // 1. Detección completa
        const resultFull = await human.detect(video);

        // 2. Dibujar resultados si es necesario (opcional, aquí simplificado)
        // human.draw.all(canvas, resultFull);

        // 3. Filtrar y mapear rostros válidos
        const facesValidos = resultFull.face
            .filter(f => f.embedding && f.embedding.length > 0 && (f.faceScore || f.boxScore || 0) >= 0.55)
            .map(f => ({
                embedding: f.embedding,
                confianza: f.faceScore || f.boxScore || 0
            }));

        if (facesValidos.length > 0) {
            mensaje.textContent = `Procesando ${facesValidos.length} rostro(s)...`;
            mensaje.style.color = '#3b82f6';

            for (const face of facesValidos) {
                try {
                    // === MODIFICACIÓN: Manejar verificación de instructor dentro del bucle ===
                    if (modoVerificacionInstructor) {
                        const user = authSystem.getCurrentUser();
                        const resp = await fetch('api/biometria.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                action: 'verificar',
                                tipo: 'usuario',
                                id: user.id_usuario,
                                embedding: face.embedding
                            })
                        });
                        const verif = await resp.json();

                        if (verif.success && verif.match) {
                            mensaje.textContent = 'Instructor Verificado';
                            mensaje.style.color = '#10b981';

                            // Detener bucle y proceder
                            modoGrupalActivo = false;
                            setTimeout(() => {
                                cerrarModalBiometria();
                                procederGuardadoFinal();
                            }, 1000);
                            return; // Salir de la función
                        } else {
                            // Opcional: Feedback visual de "No eres tú" (cuidado con el spam visual en bucle)
                        }
                    } else {
                        // === Lógica Original: Identificación de Aprendices ===
                        const resp = await fetch('api/biometria.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                action: 'identificar_grupo',
                                embedding: face.embedding,
                                ficha: document.getElementById('fichaSelect').value
                            })
                        });
                        const ident = await resp.json();

                        if (ident.success && ident.match) {
                            const data = ident.data;
                            const nombreCompleto = `${data.nombre} ${data.apellido}`;

                            if (ident.pertenece_ficha) {
                                // === VALIDACIÓN: Verificar si ya tiene asistencia registrada ===
                                const aprendizExistente = aprendicesActuales.find(a => a.documento == data.documento);
                                if (aprendizExistente && aprendizExistente.estado && aprendizExistente.estado !== 'Ausente') {
                                    // Mostrar mensaje informativo en azul oscuro pero NO bloquear
                                    mensaje.textContent = `✓ ${nombreCompleto} - ${aprendizExistente.estado}`;
                                    mensaje.style.color = '#1e40af'; // Azul oscuro
                                    console.log(`ℹ️ ${nombreCompleto} ya tiene asistencia: ${aprendizExistente.estado}`);
                                    // NO RETURN - Continuar procesando otros rostros
                                    continue; // Saltar al siguiente rostro en el bucle
                                }

                                mensaje.textContent = `Identificado: ${nombreCompleto}`;
                                mensaje.style.color = '#10b981';
                                console.log(`Marcando asistencia automática para: ${nombreCompleto}`);

                                // Calcular estado basado en hora actual
                                const estadoCalculado = calcularEstadoAsistencia(document.getElementById('fichaSelect').value);
                                marcarAsistenciaAprendiz(data.documento, estadoCalculado);
                                mostrarNotificacion(`Asistencia marcada: ${nombreCompleto} - ${estadoCalculado}`, 'success');

                                // Si es modo individual, cerramos al encontrar
                                if (esIndividual) {
                                    setTimeout(() => cerrarModalBiometria(), 1500);
                                    modoGrupalActivo = false; // Detener bucle
                                    return; // Salir de la función
                                }

                            } else {
                                mensaje.textContent = `Aprendiz identificado (${nombreCompleto}) pero NO pertenece a esta ficha.`;
                                mensaje.style.color = '#10b981';
                            }
                        }
                    }
                } catch (errInner) {
                    console.error("Error procesando rostro individual:", errInner);
                }
            }
        } else {
            if (mensaje.textContent.includes('Procesando')) {
                mensaje.textContent = 'Buscando rostros...';
                mensaje.style.color = '#6b7280';
            }
        }

    } catch (error) {
        console.error('Error en ciclo de detección:', error);
    }

    // Continuar el bucle
    if (modoGrupalActivo) {
        requestAnimationFrame(() => detectarBucle(video, esIndividual));
    }
}


// Función auxiliar para overlay
function crearCanvasOverlay(video) {
    let canvas = document.getElementById('canvasBiometria');
    if (!canvas) {
        canvas = document.createElement('canvas');
        canvas.id = 'canvasBiometria';
        canvas.style.position = 'absolute';
        canvas.style.top = '0';
        canvas.style.left = '0';
        canvas.width = video.videoWidth || 640;
        canvas.height = video.videoHeight || 480;
        canvas.style.pointerEvents = 'none';

        if (getComputedStyle(video.parentNode).position === 'static') {
            video.parentNode.style.position = 'relative';
        }

        video.parentNode.appendChild(canvas);
    }
    return canvas;
}

async function capturarRostroAsistencia() {
    const video = document.getElementById('videoBiometria');
    const mensaje = document.getElementById('mensajeCaptura');
    const btn = document.getElementById('btnCapturar');

    if (btn) btn.disabled = true;
    mensaje.textContent = 'Procesando...';
    mensaje.style.color = '#3b82f6';

    try {
        const resultado = await capturarRostro(video);

        if (!resultado.success) {
            mensaje.textContent = resultado.mensaje;
            mensaje.style.color = '#ef4444';
            if (btn) btn.disabled = false;
            return;
        }

        // Si es verificación de instructor
        if (modoVerificacionInstructor) {
            const user = authSystem.getCurrentUser();
            const resp = await fetch('api/biometria.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'verificar',
                    tipo: 'usuario',
                    id: user.id_usuario,
                    embedding: resultado.embedding
                })
            });
            const verif = await resp.json();

            if (verif.success && verif.match) {
                mensaje.textContent = 'Instructor Verificado';
                mensaje.style.color = '#10b981';
                setTimeout(() => {
                    cerrarModalBiometria();
                    procederGuardadoFinal();
                }, 1000);
            } else {
                mensaje.textContent = 'No coincide con el instructor';
                mensaje.style.color = '#ef4444';
                if (btn) btn.disabled = false;
            }
            return;
        }

        // Si es toma individual (buscamos quién es)
        const fichaActual = document.getElementById('fichaSelect').value;

        const respIdent = await fetch('api/biometria.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'identificar_grupo',
                ficha: fichaActual,
                embedding: resultado.embedding
            })
        });

        const ident = await respIdent.json();

        if (ident.success && ident.data) {
            const data = ident.data;
            if (data.ficha == fichaActual) {
                // === VALIDACIÓN: Verificar si ya tiene asistencia registrada ===
                const aprendizExistente = aprendicesActuales.find(a => a.documento == data.documento);
                if (aprendizExistente && aprendizExistente.estado && aprendizExistente.estado !== 'Ausente') {
                    mensaje.textContent = `✓ ${data.nombre} ${data.apellido} - ${aprendizExistente.estado}`;
                    mensaje.style.color = '#1e40af'; // Azul oscuro
                    mostrarNotificacion(`${data.nombre} ${data.apellido} ya tiene asistencia registrada como "${aprendizExistente.estado}"`, 'info');
                    if (btn) btn.disabled = false;
                    return;
                }

                mostrarNotificacion(`Aprendiz Identificado: ${data.nombre} ${data.apellido}`, 'success');

                // Calcular estado basado en hora actual
                const estadoCalculado = calcularEstadoAsistencia(fichaActual);
                marcarAsistenciaAprendiz(data.documento, estadoCalculado);
                mensaje.textContent = `Identificado: ${data.nombre} ${data.apellido} - ${estadoCalculado}`;
                mensaje.style.color = '#10b981'; // Verde Biche
                setTimeout(() => cerrarModalBiometria(), 1500);
            } else {
                mensaje.textContent = `Aprendiz identificado (${data.nombre} ${data.apellido}) pero NO pertenece a esta ficha.`;
                mensaje.style.color = '#10b981';
                if (btn) btn.disabled = false;
            }
        }

    } catch (error) {
        console.error('Error verificación:', error);
        mensaje.textContent = 'Error técnico';
        if (btn) btn.disabled = false;
    }
}

function marcarAsistenciaAprendiz(documento, estadoNuevo) {
    // Actualizar array local
    const aprendiz = aprendicesActuales.find(a => a.documento == documento);
    if (!aprendiz) return;

    // === VALIDACIÓN DE REGISTRO ÚNICO ===
    // Si el aprendiz ya tiene un estado diferente de 'Ausente', significa que ya fue registrado
    const estadoActual = aprendiz.estado;
    if (estadoActual && estadoActual !== 'Ausente') {
        console.log(`⚠️ Aprendiz ${aprendiz.nombre} ${aprendiz.apellido} ya tiene asistencia registrada: ${estadoActual}`);
        mostrarNotificacion(`${aprendiz.nombre} ${aprendiz.apellido} ya registró asistencia como "${estadoActual}"`, 'warning');
        return; // NO permitir cambio de estado
    }

    // Actualizar estado en el array
    aprendiz.estado = estadoNuevo;

    // Actualizar UI
    const fila = document.getElementById(`fila_${documento}`);
    const badge = document.getElementById(`estado_badge_${documento}`);
    const input = document.getElementById(`estado_input_${documento}`);

    if (fila && badge && input) {
        // Limpiar clases previas
        fila.classList.remove('table-success');

        // Aplicar estilos según estado
        switch (estadoNuevo) {
            case 'Presente':
                fila.classList.add('table-success');
                badge.className = 'badge';
                badge.style = 'background: #39A900; color: white;'; // Verde biche
                badge.textContent = 'Presente';
                break;
            case 'Retardado':
            case 'Retardo':
                badge.className = 'badge';
                badge.style = 'background: #fbbf24; color: #332;'; // Amarillo
                badge.textContent = estadoNuevo;
                break;
            case 'Con Excusa':
            case 'Justificado':
                badge.className = 'badge';
                badge.style = 'background: #d97706; color: white;'; // Naranja oscuro
                badge.textContent = estadoNuevo;
                break;
            case 'Ausente':
            default:
                badge.className = 'badge';
                badge.style = 'background: #ef4444; color: white;'; // Rojo
                badge.textContent = estadoNuevo || 'Ausente';
                break;
        }

        input.value = estadoNuevo;

        // Actualizar icono de check (solo verde para Presente)
        const checkIcon = document.getElementById(`check_${documento}`);
        if (checkIcon) {
            checkIcon.style.display = estadoNuevo === 'Presente' ? 'inline-block' : 'none';
            checkIcon.style.color = '#39A900'; // Verde biche
        }
    }
}

function cerrarModalBiometria() {
    modoGrupalActivo = false; // Detener bucle
    if (window.streamBiometria) {
        window.streamBiometria.getTracks().forEach(track => track.stop());
        window.streamBiometria = null;
    }
    document.getElementById('modalBiometria').style.display = 'none';
}

// Guardar Asistencia con Validación Biométrica del Instructor
async function guardarAsistencia() {
    const user = authSystem.getCurrentUser();

    if (!confirm('Para guardar, debe verificar su identidad biométrica.\n\n¿Desea proceder con el escaneo?')) return;

    modoVerificacionInstructor = true;
    modoGrupalActivo = true; // Activar modo loop

    const modal = document.getElementById('modalBiometria');
    const titulo = document.getElementById('tituloModalBio');
    const instruccion = document.getElementById('instruccionModalBio');
    const mensaje = document.getElementById('mensajeCaptura');
    const btn = document.getElementById('btnCapturar');

    modal.style.display = 'flex';
    titulo.textContent = `Verificación de Instructor`;
    instruccion.textContent = `${user.nombre} ${user.apellido}`;
    mensaje.textContent = 'Verificando identidad...';
    mensaje.style.color = '#3b82f6';

    // Ocultar botón para flujo automático
    if (btn) btn.style.display = 'none';

    try {
        const stream = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: 'user', width: 640, height: 480 }
        });
        const video = document.getElementById('videoBiometria');
        video.srcObject = stream;
        window.streamBiometria = stream;

        // Iniciar detección automática (modo individual = true para detenerse al éxito)
        detectarBucle(video, true);

    } catch (error) {
        console.error('Error cámara:', error);
        mostrarNotificacion('Error al acceder a la cámara', 'error');
        modal.style.display = 'none';
    }
}

async function procederGuardadoFinal() {
    const user = authSystem.getCurrentUser();
    const ficha = document.getElementById('fichaSelect').value;
    const fecha = document.getElementById('fechaAsistencia').value;

    console.log('=== INICIO GUARDADO ===');
    console.log('Usuario:', user);
    console.log('Ficha:', ficha);
    console.log('Fecha:', fecha);
    console.log('Aprendices actuales:', aprendicesActuales);

    if (!ficha || !fecha) {
        mostrarNotificacion('Faltan datos: ficha o fecha', 'error');
        console.error('Faltan datos básicos');
        return;
    }

    const datosAsistencia = aprendicesActuales.map(ap => ({
        documento_aprendiz: ap.documento,
        numero_ficha: ficha,
        fecha: fecha,
        estado: ap.estado || 'Ausente',
        observaciones: document.getElementById(`obs_${ap.documento}`)?.value || ''
    }));

    console.log('Datos de asistencia preparados:', datosAsistencia);

    const payload = {
        registros: datosAsistencia,
        id_usuario: user.id_usuario
    };

    console.log('Payload a enviar:', JSON.stringify(payload, null, 2));

    try {
        console.log('Enviando petición a api/asistencias.php...');

        const response = await fetch('api/asistencias.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        console.log('Respuesta recibida:', response.status, response.statusText);

        const result = await response.json();
        console.log('Resultado JSON:', result);

        if (result.success) {
            mostrarNotificacion('✅ Asistencia guardada exitosamente: ' + result.message, 'success');
            asistenciaGuardada = true;
            console.log('=== GUARDADO EXITOSO ===');
        } else {
            mostrarNotificacion('❌ Error al guardar: ' + result.message, 'error');
            console.error('Error del servidor:', result);
        }
    } catch (error) {
        console.error('Error de red o parsing:', error);
        mostrarNotificacion('❌ Error de conexión al guardar', 'error');
    }
}




// ==================== FUNCIONES DE EXCUSAS MOVIDAS A CONSULTAR.JS ====================
// Las funciones de gestión de excusas ahora están en instructor-consultar.html y consultar.js



// Función para mostrar notificaciones
function mostrarNotificacion(mensaje, tipo = 'info') {
    // Crear elemento de notificación
    const notif = document.createElement('div');
    notif.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        border-radius: 8px;
        color: white;
        font-weight: 500;
        z-index: 10001;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        animation: slideIn 0.3s ease-out;
        max-width: 400px;
    `;

    // Colores según el tipo
    const colores = {
        'success': '#10b981',
        'error': '#ef4444',
        'warning': '#f59e0b',
        'info': '#3b82f6'
    };

    notif.style.background = colores[tipo] || colores.info;
    notif.textContent = mensaje;

    document.body.appendChild(notif);

    // Auto-remover después de 4 segundos
    setTimeout(() => {
        notif.style.animation = 'slideOut 0.3s ease-out';
        setTimeout(() => notif.remove(), 300);
    }, 4000);
}

// Agregar estilos de animación
if (!document.getElementById('notif-styles')) {
    const style = document.createElement('style');
    style.id = 'notif-styles';
    style.textContent = `
        @keyframes slideIn {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(400px);
                opacity: 0;
            }
        }
    `;
}


