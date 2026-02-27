/**
 * Gestión de Asignaciones - Admin
 * Sistema de asignación con calendario múltiple y horarios automáticos
 */

let fichasData = [];
let instructoresData = [];
let horariosPorJornada = {
    'Diurna': { inicio: '06:00', fin: '12:00' },
    'Tarde': { inicio: '13:00', fin: '18:00' },
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
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

function mostrarAsignaciones(datos) {
    const tbody = document.getElementById('tablaAsignaciones');

    if (datos.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align: center;">No hay asignaciones activas</td></tr>';
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
        <tr>
            <td>${a.numero_ficha}</td>
            <td>${a.nombre_instructor || 'N/A'}</td>
            <td>${a.fechas.length} fecha(s) asignada(s)</td>
            <td>${a.hora_inicio} - ${a.hora_fin}</td>
            <td>
                <button onclick="verDetalles('${a.id_usuario}', '${a.numero_ficha}')" class="btn-primary" style="padding: 5px 10px; background: #3b82f6;" title="Ver detalles">
                    <i class="fas fa-eye"></i>
                </button>
                <button onclick="eliminarAsignaciones('${a.id_usuario}', '${a.numero_ficha}')" class="btn-primary" style="padding: 5px 10px; background: #ef4444;" title="Eliminar">
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
    document.getElementById('formAsignacion').reset();
    if (calendarioFlatpickr) {
        calendarioFlatpickr.clear();
    }
    document.getElementById('jornadaInfo').textContent = '-';
    document.getElementById('horaInfo').textContent = '';
    document.getElementById('modalAsignacion').style.display = 'flex';
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
        if (instructor.estado === 'inactivo' || instructor.estado === '0' || instructor.estado === 0) {
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
                    <tr style="background-color: #39A900; color: white;">
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

function verDetalles(idUsuario, numeroFicha) {
    // Implementar modal de detalles si es necesario
    alert(`Ver detalles de asignaciones:\nInstructor ID: ${idUsuario}\nFicha: ${numeroFicha}`);
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
