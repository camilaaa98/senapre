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
        // Validar que tenemos un botón válido
        if (!event || !event.target) {
            showNotification('Error: No se pudo identificar el botón', 'error');
            return;
        }
        
        const btn = event.target;
        const originalText = btn.innerHTML;
        
        // Mostrar loading
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Exportando...';
        btn.disabled = true;

        // Obtener datos del reporte
        const response = await fetch('api/reportes.php?export=true&format=' + formato);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();

        if (result.success) {
            // Validar que hay datos
            if (!result.data || !Array.isArray(result.data) || result.data.length === 0) {
                showNotification('No hay datos disponibles para exportar', 'warning');
                return;
            }
            
            // Generar archivo según formato
            if (formato === 'excel') {
                exportToExcel(result.data, 'reporte_general_senapre');
            } else if (formato === 'pdf') {
                exportToPDF(result.data, 'reporte_general_senapre');
            } else if (formato === 'csv') {
                exportToCSV(result.data, 'reporte_general_senapre');
            } else {
                showNotification('Formato no soportado', 'error');
                return;
            }
            
            // Mostrar éxito
            showNotification('Reporte exportado correctamente', 'success');
        } else {
            showNotification(result.message || 'Error al exportar reporte', 'error');
        }
    } catch (error) {
        console.error('Error exportando reporte:', error);
        
        // Mensajes de error específicos
        let errorMessage = 'Error al exportar reporte';
        if (error.name === 'TypeError') {
            errorMessage = 'Error de conexión con el servidor';
        } else if (error.name === 'NetworkError') {
            errorMessage = 'Error de red. Verifique su conexión';
        }
        
        showNotification(errorMessage, 'error');
    } finally {
        // Restaurar botón
        if (event && event.target) {
            const btn = event.target;
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    }
}

// Función para exportar a Excel
function exportToExcel(data, filename) {
    try {
        // Validar datos
        if (!data || !Array.isArray(data) || data.length === 0) {
            showNotification('No hay datos para exportar', 'warning');
            return;
        }

        // Verificar si XLSX está disponible
        if (typeof XLSX === 'undefined') {
            showNotification('Librería XLSX no disponible', 'error');
            return;
        }

        // Crear worksheet
        const ws = XLSX.utils.json_to_sheet(data);
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, 'Reporte General');
        XLSX.writeFile(wb, filename + '.xlsx');
        
        showNotification('Excel exportado correctamente', 'success');
    } catch (error) {
        console.error('Error exportando Excel:', error);
        showNotification('Error al exportar Excel', 'error');
    }
}

// Función para exportar a PDF
function exportToPDF(data, filename) {
    try {
        // Validar datos
        if (!data || !Array.isArray(data) || data.length === 0) {
            showNotification('No hay datos para exportar', 'warning');
            return;
        }

        // Verificar si jsPDF está disponible
        if (typeof jsPDF === 'undefined') {
            showNotification('Librería jsPDF no disponible', 'error');
            return;
        }

        const { jsPDF } = window.jspdf;
        const doc = new jsPDF();
        
        // Configuración del documento
        doc.setFontSize(16);
        doc.text('Reporte General - SenApre', 20, 20);
        
        // Fecha de generación
        doc.setFontSize(10);
        doc.text(`Generado: ${new Date().toLocaleString('es-CO')}`, 20, 30);
        
        // Agregar tabla
        doc.setFontSize(12);
        let y = 50;
        
        // Encabezados
        if (data.length > 0) {
            const headers = Object.keys(data[0]);
            headers.forEach((header, index) => {
                doc.text(`${header}:`, 20, y);
                y += 8;
            });
            
            // Datos
            data.slice(0, 10).forEach((row, index) => {
                if (y > 250) { // Nueva página si se excede
                    doc.addPage();
                    y = 20;
                }
                
                Object.entries(row).forEach(([key, value]) => {
                    const text = `${value || 'N/A'}`;
                    doc.text(text, 25, y);
                    y += 8;
                });
                y += 5;
            });
        }
        
        doc.save(filename + '.pdf');
        showNotification('PDF exportado correctamente', 'success');
    } catch (error) {
        console.error('Error exportando PDF:', error);
        showNotification('Error al exportar PDF', 'error');
    }
}

// Función para exportar a CSV
function exportToCSV(data, filename) {
    try {
        // Validar datos
        if (!data || !Array.isArray(data) || data.length === 0) {
            showNotification('No hay datos para exportar', 'warning');
            return;
        }

        // Convertir a CSV
        const headers = Object.keys(data[0] || {});
        const csvContent = [
            headers.join(','),
            ...data.map(row => 
                headers.map(header => {
                    const value = row[header] || '';
                    // Escapar comillas y comas
                    const escaped = String(value).replace(/"/g, '""');
                    return `"${escaped}"`;
                }).join(',')
            )
        ].join('\n');
        
        // Crear y descargar archivo
        const blob = new Blob(['\ufeff' + csvContent], { 
            type: 'text/csv;charset=utf-8;' 
        });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        
        link.setAttribute('href', url);
        link.setAttribute('download', filename + '.csv');
        link.style.visibility = 'hidden';
        
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        showNotification('CSV exportado correctamente', 'success');
    } catch (error) {
        console.error('Error exportando CSV:', error);
        showNotification('Error al exportar CSV', 'error');
    }
}

// Función para mostrar notificaciones
function showNotification(message, type) {
    try {
        // Remover notificaciones existentes
        const existingNotifications = document.querySelectorAll('.notification');
        existingNotifications.forEach(notif => notif.remove());
        
        // Crear elemento de notificación
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'warning' ? 'exclamation-triangle' : 'times-circle'}"></i>
            <span>${message}</span>
        `;
        
        // Agregar al DOM
        document.body.appendChild(notification);
        
        // Auto-remover después de 4 segundos
        setTimeout(() => {
            notification.style.animation = 'slideOutNotification 0.3s ease-in';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 300);
        }, 4000);
        
        // Click para cerrar manualmente
        notification.addEventListener('click', () => {
            notification.style.animation = 'slideOutNotification 0.3s ease-in';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 300);
        });
        
    } catch (error) {
        console.error('Error mostrando notificación:', error);
        // Fallback a alert
        alert(message);
    }
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
