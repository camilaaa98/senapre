/**
 * Instructor Dashboard Logic
 * Handles metrics fetching and calendar rendering
 */

document.addEventListener('DOMContentLoaded', () => {
    inicializarDashboard();
});

async function inicializarDashboard() {
    const user = authSystem.getCurrentUser();

    if (!user || !user.id_usuario) {
        console.error('No user session found');
        return;
    }

    // Mostrar nombre y fecha
    document.getElementById('userName').textContent = `Bienvenido, ${user.nombre} ${user.apellido}`;

    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    document.getElementById('currentDate').textContent = new Date().toLocaleDateString('es-ES', options);

    try {
        const response = await fetch(`api/instructor-dashboard.php?id_usuario=${user.id_usuario}`);
        const result = await response.json();

        if (result.success) {
            actualizarMetricas(result.data.metrics);
            inicializarCalendario(result.data.calendar);
            mostrarAlertas(result.data.metrics.alertas); // Por ahora pasamos el conteo, idealmente sería una lista
        } else {
            console.error('Error fetching dashboard data:', result.message);
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

function actualizarMetricas(metrics) {
    document.getElementById('fichasCount').textContent = metrics.fichas || 0;
    document.getElementById('aprendicesCount').textContent = metrics.aprendices || 0;
    document.getElementById('promedioAsistencia').textContent = metrics.asistencia || '0%';
    document.getElementById('alertasCount').textContent = metrics.alertas || 0;
}

function mostrarAlertas(alertasCount) {
    const container = document.getElementById('alertasContainer');

    if (alertasCount > 0) {
        // En una implementación real, aquí iteraríamos sobre la lista de aprendices en riesgo
        // Como el endpoint actual solo devuelve el conteo para simplificar, mostraremos un mensaje genérico
        // O podríamos hacer otra llamada para obtener los detalles.
        container.innerHTML = `
            <div style="text-align: center; padding: 20px;">
                <i class="fas fa-exclamation-triangle fa-3x" style="color: #ef4444; margin-bottom: 15px;"></i>
                <p>Tiene <strong>${alertasCount}</strong> aprendices con baja asistencia.</p>
                <a href="instructor-reportes.html" class="btn-primary" style="display: inline-block; margin-top: 10px;">Ver Reporte Detallado</a>
            </div>
        `;
    } else {
        container.innerHTML = `
            <div style="text-align: center; padding: 20px;">
                <i class="fas fa-check-circle fa-3x" style="color: #10b981; margin-bottom: 15px;"></i>
                <p>No hay alertas de asistencia.</p>
            </div>
        `;
    }
}

function inicializarCalendario(eventos) {
    const calendarEl = document.getElementById('calendar');

    if (!calendarEl) {
        console.error('Calendar element not found');
        return;
    }

    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: 'es',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        buttonText: {
            today: 'Hoy',
            month: 'Mes',
            week: 'Semana',
            day: 'Día'
        },
        events: eventos,
        eventColor: '#39A900', // Verde SENA vibrante
        eventBackgroundColor: '#39A900',
        eventBorderColor: '#2d8600',
        eventTextColor: '#ffffff',
        eventClick: function (info) {
            const start = info.event.start ? info.event.start.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' }) : 'N/A';
            const end = info.event.end ? info.event.end.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' }) : 'N/A';

            alert('Ficha: ' + info.event.title + '\n' +
                'Horario: ' + start + ' - ' + end + '\n' +
                (info.event.extendedProps.description || ''));
        },
        height: 'auto',
        themeSystem: 'standard'
    });

    calendar.render();
}
