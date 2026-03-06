/**
 * Gestión de Asignaciones - Admin
 * Sistema de asignación con calendario múltiple y horarios automáticos
 */

let fichasData = [];
let instructoresData = [];
let horariosPorJornada = {
    'Diurna': { inicio: '06:00', fin: '12:00' },
    'Diurna - Cerrado': { inicio: '06:00', fin: '12:00' },
    'Tarde': { inicio: '13:00', fin: '18:00' },
    'Tarde - Cerrado': { inicio: '13:00', fin: '18:00' },
    'Noche': { inicio: '18:00', fin: '00:00' },
    'Noche - Cerrado': { inicio: '18:00', fin: '00:00' },
    'Nocturna': { inicio: '18:00', fin: '00:00' },
    'Mixta': { inicio: '06:00', fin: '18:00' },
    'Fin de semana': { inicio: '08:00', fin: '16:00' }
};
let calendarioFlatpickr = null;

document.addEventListener('DOMContentLoaded', () => {
    cargarAsignaciones();
    cargarSelects();
    inicializarCalendario();
});

function inicializarCalendario() {
    calendarioFlatpickr = flatpickr("#fechasAsignadas", {
        mode: "multiple",
        dateFormat: "Y-m-d",
        locale: "es",
        minDate: "today",
        inline: false,
        onChange: function (selectedDates, dateStr, instance) {
            console.log('Fechas seleccionadas:', selectedDates);
        }
    });
}

async function cargarAsignaciones() {
    try {
        const response = await fetch('api/asignaciones.php');
        const result = await response.json();

        if (result.success) {
            mostrarAsignaciones(result.data);
        } else {
            console.error('Error del servidor:', result.message);
            const tbody = document.getElementById('tablaAsignaciones');
            if (tbody) {
                tbody.innerHTML = `<tr><td colspan="5" style="text-align:center; padding: 30px; color: red;">Error: ${result.message || 'No se pudieron cargar las asignaciones'}</td></tr>`;
            }
        }
    } catch (error) {
        console.error('Error de red:', error);
        const tbody = document.getElementById('tablaAsignaciones');
        if (tbody) {
            tbody.innerHTML = `<tr><td colspan="5" style="text-align:center; padding: 30px; color: red;">Error de conexión con el servidor</td></tr>`;
        }
    }
}

function mostrarAsignaciones(datos) {
    const tbody = document.getElementById('tablaAsignaciones');

    if (datos.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4 color-muted">No hay asignaciones activas</td></tr>';
        return;
    }

    // Agrupar asignaciones por instructor y ficha
    const agrupadas = {};
    datos.forEach(a => {
        const key = `${a.id_usuario}_${a.numero_ficha}`;
        if (!agrupadas[key]) {
            agrupadas[key] = {
                ...a,
                fechas: []
            };
        }
        agrupadas[key].fechas.push(a.dias_formacion);
    });

    tbody.innerHTML = Object.values(agrupadas).map(a => `
        <tr class="table-row-divider">
            <td class="td-mono">${a.numero_ficha}</td>
            <td>${a.nombre_instructor || 'N/A'}</td>
            <td><span class="badge badge-info">${a.fechas.length} fecha(s)</span></td>
            <td><span class="td-mono">${a.hora_inicio} - ${a.hora_fin}</span></td>
            <td class="flex-center-gap">
                <button onclick="verDetalles('${a.id_usuario}', '${a.numero_ficha}')" class="btn-icon btn-blue" title="Ver detalles">
                    <i class="fas fa-eye"></i>
                </button>
                <button onclick="eliminarAsignaciones('${a.id_usuario}', '${a.numero_ficha}')" class="btn-icon btn-red" title="Eliminar">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>
    `).join('');
}

async function cargarSelects() {
    try {
        // Cargar Instructores
        const respInst = await fetch('api/usuarios.php?rol=instructor&limit=-1');
        const resInst = await respInst.json();

        const selectInst = document.getElementById('selectInstructor');
        if (resInst.success && resInst.data.length > 0) {
            instructoresData = resInst.data; // Guardar para validación
            selectInst.innerHTML = '<option value="">Seleccione...</option>' +
                resInst.data.map(u => `<option value="${u.id_usuario}">${u.nombre} ${u.apellido}</option>`).join('');
        } else {
            instructoresData = [];
            selectInst.innerHTML = '<option value="">No hay instructores disponibles</option>';
        }

        // Cargar Fichas (TODAS las del sistema)
        const respFicha = await fetch('api/fichas.php?limit=-1');
        const resFicha = await respFicha.json();

        const selectFicha = document.getElementById('selectFicha');
        if (resFicha.success && resFicha.data.length > 0) {
            fichasData = resFicha.data;
            selectFicha.innerHTML = '<option value="">Seleccione...</option>' +
                resFicha.data.map(f => `<option value="${f.numero_ficha}">${f.numero_ficha} - ${f.nombre_programa}</option>`).join('');
        } else {
            selectFicha.innerHTML = '<option value="">No hay fichas disponibles</option>';
        }
    } catch (error) {
        console.error('Error cargando selects:', error);
    }
}

function cargarHorarioFicha() {
    const fichaSeleccionada = document.getElementById('selectFicha').value;
    const ficha = fichasData.find(f => f.numero_ficha == fichaSeleccionada);

    if (ficha && ficha.jornada) {
        const horario = horariosPorJornada[ficha.jornada];

        document.getElementById('jornadaInfo').textContent = ficha.jornada;

        if (horario) {
            document.getElementById('horaInfo').textContent = `${horario.inicio} - ${horario.fin}`;
        } else {
            document.getElementById('horaInfo').textContent = '';
        }
    } else {
        document.getElementById('jornadaInfo').textContent = '-';
        document.getElementById('horaInfo').textContent = '';
    }
}

function nuevaAsignacion() {
    console.log('Abriendo modal de nueva asignación...');
    const form = document.getElementById('formAsignacion');
    if (form) form.reset();

    if (calendarioFlatpickr) {
        calendarioFlatpickr.clear();
    }

    const jornadaInfo = document.getElementById('jornadaInfo');
    const horaInfo = document.getElementById('horaInfo');
    if (jornadaInfo) jornadaInfo.textContent = '-';
    if (horaInfo) horaInfo.textContent = '';

    const modal = document.getElementById('modalAsignacion');
    if (modal) {
        modal.style.display = 'flex';
        console.log('Modal abierto.');
    } else {
        console.error('No se encontró el modal con ID modalAsignacion');
        alert('Error: No se pudo abrir el formulario de asignación.');
    }
}

function cerrarModal() {
    document.getElementById('modalAsignacion').style.display = 'none';
}

async function guardarAsignacion(event) {
    event.preventDefault();

    const fichaSeleccionada = document.getElementById('selectFicha').value;
    const instructorSeleccionado = document.getElementById('selectInstructor').value;

    if (!instructorSeleccionado || !fichaSeleccionada) {
        alert('Debe seleccionar instructor y ficha');
        return;
    }

    // Validar estado del instructor
    const instructor = instructoresData.find(i => i.id_usuario == instructorSeleccionado);
    if (instructor) {
        const estadoNorm = String(instructor.estado).toLowerCase().trim();
        if (estadoNorm === 'inactivo' || estadoNorm === '0' || estadoNorm === 'false') {
            mostrarNotificacion(`No se puede asignar fichas a un instructor INACTIVO (${instructor.nombre} ${instructor.apellido})`, 'error');
            return;
        }
    }

    const fechasSeleccionadas = calendarioFlatpickr.selectedDates;
    if (fechasSeleccionadas.length === 0) {
        alert('Debe seleccionar al menos una fecha');
        return;
    }

    // Obtener horario de la ficha o usar valores por defecto
    const ficha = fichasData.find(f => f.numero_ficha == fichaSeleccionada);
    let horario = { inicio: '06:00', fin: '18:00' }; // Valores por defecto

    if (ficha && ficha.jornada && horariosPorJornada[ficha.jornada]) {
        horario = horariosPorJornada[ficha.jornada];
    }

    const data = {
        id_usuario: instructorSeleccionado,
        numero_ficha: fichaSeleccionada,
        fechas: fechasSeleccionadas.map(fecha => {
            const year = fecha.getFullYear();
            const month = String(fecha.getMonth() + 1).padStart(2, '0');
            const day = String(fecha.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }),
        hora_inicio: horario.inicio,
        hora_fin: horario.fin
    };

    try {
        const response = await fetch('api/asignaciones.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            alert('Asignación creada con éxito');
            cerrarModal();
            cargarAsignaciones();
        } else {
            alert('Error: ' + (result.message || 'No se pudo crear la asignación'));
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error al guardar asignación');
    }
}

async function eliminarAsignaciones(idUsuario, numeroFicha) {
    if (!confirm('¿Está seguro de eliminar todas las asignaciones de este instructor en esta ficha?')) return;

    try {
        const response = await fetch(`api/asignaciones.php?id_usuario=${idUsuario}&numero_ficha=${numeroFicha}`, {
            method: 'DELETE'
        });
        const result = await response.json();

        if (result.success) {
            mostrarNotificacion('Asignaciones eliminadas', 'success');
            cargarAsignaciones();
        } else {
            mostrarNotificacion(result.message || 'Error al eliminar', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarNotificacion('Error al eliminar asignaciones', 'error');
    }
}

async function exportarAsignaciones() {
    try {
        mostrarNotificacion('Generando reporte...', 'info');

        // La API de asignaciones actualmente devuelve TODO, así que no necesitamos limit=-1
        // Pero si en el futuro se pagina, habría que ajustarlo.
        const response = await fetch('api/asignaciones.php');
        const result = await response.json();

        if (!result.success || !result.data || result.data.length === 0) {
            mostrarNotificacion('No hay datos para exportar', 'warning');
            return;
        }

        let table = `
            <table border="1">
                <thead>
                    <tr class="export-header">
                        <th>Ficha</th>
                        <th>Programa</th>
                        <th>Jornada</th>
                        <th>Instructor</th>
                        <th>Fecha</th>
                        <th>Horario</th>
                    </tr>
                </thead>
                <tbody>
        `;

        result.data.forEach(a => {
            table += `
                <tr>
                    <td>${a.numero_ficha}</td>
                    <td>${a.nombre_programa}</td>
                    <td>${a.jornada}</td>
                    <td>${a.nombre_instructor}</td>
                    <td>${a.dias_formacion}</td>
                    <td>${a.hora_inicio} - ${a.hora_fin}</td>
                </tr>
            `;
        });

        table += '</tbody></table>';

        const blob = new Blob(['\uFEFF' + table], { type: 'application/vnd.ms-excel;charset=utf-8' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = `Asignaciones_${new Date().toISOString().split('T')[0]}.xls`;
        link.click();

        mostrarNotificacion('Archivo exportado exitosamente', 'success');
    } catch (error) {
        console.error('Error exportando:', error);
        mostrarNotificacion('Error al exportar datos', 'error');
    }
}

async function exportarPDF() {
    try {
        mostrarNotificacion('Preparando PDF...', 'info');
        const response = await fetch('api/asignaciones.php');
        const result = await response.json();

        if (!result.success || !result.data || result.data.length === 0) {
            mostrarNotificacion('No hay datos para exportar', 'warning');
            return;
        }

        // Crear una ventana temporal para impresión/PDF
        const printWindow = window.open('', '_blank');
        const content = `
            <html>
            <head>
                <title>Reporte de Asignaciones - SenApre</title>
                <style>
                    body { font-family: Arial, sans-serif; padding: 20px; }
                    h1 { color: #00324D; text-align: center; }
                    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; font-size: 10px; }
                    th { background-color: #f2f2f2; color: #333; }
                    .header-logo { text-align: center; margin-bottom: 20px; }
                </style>
            </head>
            <body>
                <h1>SISTEMA SENAPRE</h1>
                <h3>Reporte de Asignación de Instructores</h3>
                <p>Fecha de generación: ${new Date().toLocaleString()}</p>
                <table>
                    <thead>
                        <tr>
                            <th>FICHA</th>
                            <th>PROGRAMA</th>
                            <th>INSTRUCTOR</th>
                            <th>FECHA</th>
                            <th>JORNADA</th>
                            <th>HORARIO</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${result.data.map(a => `
                            <tr>
                                <td>${a.numero_ficha}</td>
                                <td>${a.nombre_programa}</td>
                                <td>${a.nombre_instructor}</td>
                                <td>${a.dias_formacion}</td>
                                <td>${a.jornada}</td>
                                <td>${a.hora_inicio} - ${a.hora_fin}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
                <script>
                    window.onload = function() { window.print(); window.close(); }
                </script>
            </body>
            </html>
        `;
        printWindow.document.write(content);
        printWindow.document.close();
        mostrarNotificacion('PDF generado', 'success');
    } catch (error) {
        console.error('Error exportando PDF:', error);
        mostrarNotificacion('Error al generar PDF', 'error');
    }
}

function iniciarCapturaFacial() {
    const btn = event.target;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Inicializando IA...';

    mostrarNotificacion('Accediendo a la cámara...', 'info');

    // Simulación de inicialización de puntos biométricos (128 puntos)
    setTimeout(() => {
        mostrarNotificacion('Escaneando rostro...', 'info');
        const container = document.getElementById('camera-container');
        container.style.borderColor = '#39A900';
        container.innerHTML = `
            <div style="width:100%; height:100%; display:flex; align-items:center; justify-content:center; flex-direction:column; color:#39A900;">
                <i class="fas fa-user-check fa-4x mb-3"></i>
                <p>IDENTIDAD VERIFICADA</p>
                <small style="color:white;">Puntos biométricos: 128/128</small>
            </div>
        `;

        btn.innerHTML = '<i class="fas fa-check"></i> Registro Exitoso';
        btn.style.background = '#39A900';
        mostrarNotificacion('Identidad confirmada mediante biometría facial', 'success');
    }, 3000);
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
