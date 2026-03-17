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

// Funciones de Exportación
async function exportarReporteGeneral(formato) {
    try {
        // Mostrar loading
        const btn = event.target;
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Exportando...';
        btn.disabled = true;

        // Obtener datos del reporte
        const response = await fetch('api/reportes.php?export=true&format=' + formato);
        const result = await response.json();

        if (result.success) {
            // Generar archivo según formato
            if (formato === 'excel') {
                exportToExcel(result.data, 'reporte_general_senapre');
            } else if (formato === 'pdf') {
                exportToPDF(result.data, 'reporte_general_senapre');
            } else if (formato === 'csv') {
                exportToCSV(result.data, 'reporte_general_senapre');
            }
            
            // Mostrar éxito
            showNotification('Reporte exportado correctamente', 'success');
        } else {
            showNotification('Error al exportar reporte', 'error');
        }
    } catch (error) {
        console.error('Error exportando reporte:', error);
        showNotification('Error de conexión al exportar', 'error');
    } finally {
        // Restaurar botón
        const btn = event.target;
        btn.innerHTML = originalText;
        btn.disabled = false;
    }
}

// Función para exportar a Excel
function exportToExcel(data, filename) {
    // Implementación simplificada para Excel
    const ws = XLSX.utils.json_to_sheet(data);
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Reporte');
    XLSX.writeFile(wb, filename + '.xlsx');
}

// Función para exportar a PDF
function exportToPDF(data, filename) {
    // Implementación simplificada para PDF
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();
    
    // Agregar contenido
    doc.setFontSize(12);
    doc.text('Reporte General - SenApre', 20, 20);
    
    // Agregar tabla
    let y = 40;
    data.forEach((row, index) => {
        Object.entries(row).forEach(([key, value]) => {
            doc.text(`${key}: ${value}`, 20, y);
            y += 10;
        });
        y += 10;
    });
    
    doc.save(filename + '.pdf');
}

// Función para exportar a CSV
function exportToCSV(data, filename) {
    // Convertir a CSV
    const headers = Object.keys(data[0] || {});
    const csvContent = [
        headers.join(','),
        ...data.map(row => headers.map(header => row[header]).join(','))
    ].join('\n');
    
    // Descargar archivo
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = filename + '.csv';
    link.click();
}

// Función para mostrar notificaciones
function showNotification(message, type) {
    // Crear elemento de notificación
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'}"></i>
        <span>${message}</span>
    `;
    
    // Agregar al DOM
    document.body.appendChild(notification);
    
    // Remover después de 3 segundos
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

async function cargarReportes() {
    try {
        const response = await fetch('api/reportes.php');
        const result = await response.json();

        if (result.success) {
            const data = result.data;

            // Actualizar tarjetas
            document.getElementById('totalAprendices').textContent = data.resumen.aprendices || 0;
            document.getElementById('totalFichas').textContent = data.resumen.fichas || 0;
            document.getElementById('totalInstructores').textContent = data.resumen.instructores || 0;

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
