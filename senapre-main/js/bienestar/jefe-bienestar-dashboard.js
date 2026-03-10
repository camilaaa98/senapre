/**
 * Jefe de Bienestar Dashboard Logic
 */

const areas = ['jefe_bienestar', 'liderazgo', 'enfermeria', 'socioemocional', 'deporte', 'arte', 'apoyos'];

document.addEventListener('DOMContentLoaded', () => {
    areas.forEach(area => cargarResponsable(area));
});

async function cargarResponsable(area) {
    const domMap = {
        'jefe_bienestar': 'info-jefe-bienestar',
        'liderazgo': 'info-liderazgo',
        'enfermeria': 'info-enfermeria',
        'socioemocional': 'info-socioemocional',
        'deporte': 'info-deporte',
        'arte': 'info-arte',
        'apoyos': 'info-apoyos'
    };

    const container = document.getElementById(domMap[area]);
    if (!container) return;

    const element = container.querySelector('.responsable-name');

    try {
        const response = await fetch(`api/bienestar.php?action=getResponsable&area=${area}`);
        const result = await response.json();

        if (result.success && result.data) {
            element.innerHTML = `<strong>${result.data.nombre} ${result.data.apellido}</strong><br><span style="font-size: 0.75rem; color: #64748b;">${result.data.correo}</span>`;

            const btnAssign = element.closest('.area-card').querySelector('.btn-assign');
            if (btnAssign) {
                btnAssign.innerHTML = '<i class="fas fa-user-edit"></i> Cambiar Responsable';
            }
        } else {
            element.textContent = 'Sin asignar';
        }
    } catch (error) {
        console.error(`Error cargando responsable de ${area}:`, error);
        element.textContent = 'Error al cargar';
    }
}

function irAAsignacion(area) {
    window.location.href = `admin-bienestar-asignacion.html?area=${area}`;
}
