/**
 * Reportes - Admin
 */

document.addEventListener('DOMContentLoaded', () => {
    cargarReportes();
});

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

    // Asignar colores según el estado
    const colors = labels.map(estado => {
        const estadoUpper = estado.toUpperCase();
        if (estadoUpper === 'EN FORMACION') {
            return '#39A900'; // Verde SENA vibrante para EN FORMACION
        } else if (estadoUpper === 'APLAZADO') {
            return '#fbbf24'; // Amarillo para APLAZADO
        } else if (estadoUpper === 'RETIRADO' || estadoUpper === 'RETIRO VOLUNTARIO' || estadoUpper === 'CANCELADO') {
            return '#ef4444'; // Rojo para estados negativos
        } else if (estadoUpper === 'INDUCCION') {
            return '#3b82f6'; // Azul para INDUCCION
        } else if (estadoUpper === 'POR CERTIFICAR') {
            return '#f59e0b'; // Naranja para POR CERTIFICAR
        } else {
            return '#6b7280'; // Gris para otros
        }
    });

    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: values,
                backgroundColor: colors,
                borderWidth: 2,
                borderColor: '#fff'
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
                        font: {
                            size: 12
                        }
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

    // Asignar colores según posición: primeras 3 verde, siguientes 3 amarillo, últimas 2 rojo
    const backgroundColors = values.map((_, index) => {
        if (index < 3) {
            return '#39A900'; // Verde biche para las primeras 3
        } else if (index < 6) {
            return '#fbbf24'; // Amarillo para las siguientes 3
        } else {
            return '#ef4444'; // Rojo para las últimas 2
        }
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
                x: {
                    ticks: {
                        display: false  // Ocultar etiquetas del eje X
                    }
                },
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            },
            plugins: {
                tooltip: {
                    enabled: true  // Mantener tooltips al pasar el cursor
                },
                legend: {
                    display: false  // Ocultar leyenda ya que los colores son por posición
                }
            }
        }
    });
}
