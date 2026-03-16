const areas = ['jefe_bienestar', 'voceros_y_representantes', 'enfermeria', 'socioemocional', 'deporte', 'arte', 'apoyos'];

document.addEventListener('DOMContentLoaded', () => {
    areas.forEach(area => cargarResponsable(area));
});

async function cargarResponsable(area) {
    const domMap = {
        'jefe_bienestar': 'info-jefe-bienestar',
        'voceros_y_representantes': 'info-voceros_y_representantes',
        'liderazgo': 'info-voceros_y_representantes',
        'enfermeria': 'info-enfermeria',
        'socioemocional': 'info-socioemocional',
        'deporte': 'info-deporte',
        'arte': 'info-arte',
        'apoyos': 'info-apoyos'
    };

    const container = document.getElementById(domMap[area]);
    if (!container) return;

    const card = container.closest('.area-card');
    const element = container.querySelector('.responsable-name');
    const btnAssign = card.querySelector('.btn-assign');

    try {
        const response = await fetch(`api/liderazgo.php?action=getResponsable&area=${area}`);
        const result = await response.json();
        const loggedUser = authSystem ? authSystem.getCurrentUser() : null;

        if (result.success && result.data) {
            if (element) {
                element.innerHTML = `<strong>${result.data.nombre} ${result.data.apellido}</strong><br><span class="meta-text">${result.data.correo}</span>`;
            }
            if (btnAssign) btnAssign.innerHTML = '<i class="fas fa-user-edit"></i> Cambiar Responsable';

            // REGLA: Jefe de bienestar NO puede auto-asignarse
            if (area === 'jefe_bienestar' && loggedUser && loggedUser.id_usuario == result.data.id_usuario) {
                if (btnAssign) btnAssign.style.display = 'none';
            }
        } else {
            if (element) element.textContent = 'Sin asignar';
        }
    } catch (error) {
        console.error(`Error cargando responsable de ${area}:`, error);
        if (element) element.textContent = 'Sin asignar'; // Silent fail to "Sin asignar" instead of Error
    }
}

function irAAsignacion(area) {
    window.location.href = `admin-bienestar-asignacion.html?area=${area}`;
}
