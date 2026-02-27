/**
 * Dashboard Admin - AsistNet
 */

document.addEventListener('DOMContentLoaded', () => {
    cargarEstadisticas();
    cargarGraficaAprendices();
});

async function cargarEstadisticas() {
    try {
        const response = await fetch('api/reportes.php');
        const result = await response.json();

        if (result.success) {
            const data = result.data?.resumen || {};

            // Actualizar contadores con animación
            animarContador('dashTotalUsuarios', data.usuarios || 0);
            animarContador('dashTotalAprendices', data.aprendices || 0);
            animarContador('dashTotalProgramas', data.programas || 0);
            animarContador('dashTotalFichas', data.fichas || 0);
        }
    } catch (error) {
        console.error('Error cargando estadísticas:', error);
    }
}

async function cargarGraficaAprendices() {
    try {
        const response = await fetch('api/reportes.php');
        const result = await response.json();

        if (result.success && result.data?.aprendices_estado) {
            const ctx = document.getElementById('chartAprendices');
            if (!ctx) return;

            const data = result.data.aprendices_estado;
            const labels = data.map(item => item.estado);
            const values = data.map(item => item.cantidad);

            // Colores SENA:
            // EN FORMACION: Verde (#39A900)
            // CANCELADO: Azul Oscuro (#00324D)
            // RETIRADO: Rojo/Gris oscuro
            const backgroundColors = labels.map(label => {
                const l = label ? label.toUpperCase() : '';
                if (l === 'EN FORMACION') return '#39A900'; // Verde SENA
                if (l === 'CANCELADO' || l === 'CERTIFICADO') return '#00324D';
                return '#999999'; // Default gris
            });

            new Chart(ctx.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: values,
                        backgroundColor: backgroundColors,
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom' },
                        title: { display: true, text: 'Aprendices por Estado' }
                    }
                }
            });
        }
    } catch (error) {
        console.error('Error cargando gráfica de aprendices:', error);
    }
}

function animarContador(id, valorFinal) {
    const elemento = document.getElementById(id);
    if (!elemento) return;

    valorFinal = parseInt(valorFinal);
    if (isNaN(valorFinal)) valorFinal = 0;

    const duracion = 1000;
    const pasos = 20;
    const incremento = valorFinal / pasos;
    let valorActual = 0;
    let pasoActual = 0;

    const intervalo = setInterval(() => {
        pasoActual++;
        valorActual += incremento;

        if (pasoActual >= pasos) {
            valorActual = valorFinal;
            clearInterval(intervalo);
        }

        elemento.textContent = Math.round(valorActual);
    }, duracion / pasos);
}
