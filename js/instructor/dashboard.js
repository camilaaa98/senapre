/**
 * Instructor Dashboard Logic
 * Handles metrics fetching and calendar rendering
 * Optimizado con utilidades compartidas y principios SOLID
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

    // Mostrar nombre y fecha usando utilidades
    document.getElementById('userName').textContent = `Bienvenido, ${user.nombre} ${user.apellido}`;
    document.getElementById('currentDate').textContent = DateUtils.formatearFecha(new Date());

    try {
        const result = await APIUtils.fetchWithErrorHandling(`api/instructor-dashboard.php?id_usuario=${user.id_usuario}`);
        
        if (result.success) {
            actualizarMetricas(result.data.metrics);
            inicializarCalendario(result.data.calendar);
            mostrarAlertas(result.data.metrics.alertas);
        }
    } catch (error) {
        console.error('Error en dashboard:', error);
        UIUtils.mostrarNotificacion('Error al cargar datos del dashboard', 'error');
    }
}

function actualizarMetricas(metrics) {
    document.getElementById('fichasCount').textContent = UIUtils.formatearNumero(metrics.fichas || 0);
    document.getElementById('aprendicesCount').textContent = UIUtils.formatearNumero(metrics.aprendices || 0);
    document.getElementById('promedioAsistencia').textContent = metrics.asistencia || '0%';
    document.getElementById('alertasCount').textContent = UIUtils.formatearNumero(metrics.alertas || 0);
}

function mostrarAlertas(alertasCount) {
    const container = document.getElementById('alertasContainer');

    if (alertasCount > 0) {
        container.innerHTML = `
            <div class="text-center p-4">
                <i class="fas fa-exclamation-triangle fa-3x color-danger alert-icon"></i>
                <p>Tiene <strong>${UIUtils.formatearNumero(alertasCount)}</strong> aprendices con baja asistencia.</p>
                <a href="instructor-reportes.html" class="btn-primary inline-block mt-2">Ver Reporte Detallado</a>
            </div>
        `;
    } else {
        container.innerHTML = `
            <div class="text-center p-4">
                <i class="fas fa-check-circle fa-3x color-success alert-icon"></i>
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
        eventColor: '#39A900',
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
