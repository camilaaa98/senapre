/**
 * Reportes - Admin
 */

document.addEventListener('DOMContentLoaded', () => {
    cargarReportes();
});

// Helper para obtener colores del CSS
function getStyleColor(variable) {
    return getComputedStyle(document.documentElement).getPropertyValue(variable).trim() || '#6b7280';
}

async function cargarReportes() {
    try {
        const response = await fetch('api/reportes.php');
        const result = await response.json();

        if (result.success) {
            const data = result.data;

            // Actualizar tarjetas
            document.getElementById('totalAprendices').textContent = data.resumen.aprendices;
            document.getElementById('totalFichas').textContent = data.resumen.fichas;
            document.getElementById('totalInstructores').textContent = data.resumen.instructores;

            // Gráfica de Estados
            renderChartEstados(data.aprendices_estado);

            // Gráfica de Programas
            renderChartProgramas(data.fichas_programa);
        }
    } catch (error) {
        console.error('Error cargando reportes:', error);
    }
}

function renderChartEstados(data) {
    const ctx = document.getElementById('chartEstados').getContext('2d');

    const labels = data.map(item => item.estado);
    const values = data.map(item => item.cantidad);

    // Asignar colores según el estado (Desde CSS)
    const colors = labels.map(estado => {
        const estadoUpper = estado.toUpperCase();
        if (estadoUpper === 'EN FORMACION') return getStyleColor('--chart-formacion');
        if (estadoUpper === 'APLAZADO') return getStyleColor('--chart-aplazado');
        if (['RETIRADO', 'RETIRO VOLUNTARIO', 'CANCELADO'].includes(estadoUpper)) return getStyleColor('--chart-negativo');
        if (estadoUpper === 'INDUCCION') return getStyleColor('--chart-induccion');
        if (estadoUpper === 'POR CERTIFICAR') return getStyleColor('--chart-certificar');
        return getStyleColor('--chart-otros');
    });

    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: values,
                backgroundColor: colors,
                borderWidth: 2,
                borderColor: getStyleColor('--white')
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 15,
                        font: { size: 12 }
                    }
                }
            }
        }
    });
}

function renderChartProgramas(data) {
    const ctx = document.getElementById('chartProgramas').getContext('2d');

    const labels = data.map(item => item.nombre_programa);
    const values = data.map(item => item.cantidad);

    // Asignar colores según posición (Desde CSS)
    const backgroundColors = values.map((_, index) => {
        if (index < 3) return getStyleColor('--chart-formacion');
        if (index < 6) return getStyleColor('--chart-aplazado');
        return getStyleColor('--chart-negativo');
    });

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Cantidad de Fichas',
                data: values,
                backgroundColor: backgroundColors,
                borderColor: backgroundColors,
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: { ticks: { display: false } },
                y: { beginAtZero: true, ticks: { stepSize: 1 } }
            },
            plugins: {
                tooltip: { enabled: true },
                legend: { display: false }
            }
        }
    });
}
