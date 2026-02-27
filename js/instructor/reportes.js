/**
 * Generar Reportes - Lógica de Negocio
 */

document.addEventListener('DOMContentLoaded', () => {
    cargarFichasReportes();
    establecerFechasDefecto();
});

// ... (funciones de carga y validación de fechas iguales a las anteriores) ...

async function cargarFichasReportes() {
    try {
        const user = authSystem.getCurrentUser();
        const response = await fetch(`api/instructor-fichas.php?id_usuario=${user.id_usuario}`);
        const result = await response.json();

        if (result.success) {
            const fichaOptions = result.data.map(f =>
                `<option value="${f.numero_ficha}">${f.numero_ficha} - ${f.nombre_programa || 'Sin programa'}</option>`
            ).join('');

            const selectReporte = document.getElementById('reporteFichaSelect');
            const selectAlerta = document.getElementById('reporteAlertaFicha');

            if (selectReporte) selectReporte.innerHTML = '<option value="">Seleccione una ficha...</option>' + fichaOptions;
            if (selectAlerta) selectAlerta.innerHTML = '<option value="">Todas las fichas</option>' + fichaOptions;
        }
    } catch (error) {
        console.error('Error cargando fichas:', error);
    }
}

function establecerFechasDefecto() {
    const hoy = new Date();
    const hace30Dias = new Date(hoy);
    hace30Dias.setDate(hace30Dias.getDate() - 30);

    const fechaInicio = hace30Dias.toISOString().split('T')[0];
    const fechaFin = hoy.toISOString().split('T')[0];

    const inputsInicio = ['reporteFechaInicio1', 'reporteFechaInicio2', 'reporteFechaInicio3'];
    const inputsFin = ['reporteFechaFin1', 'reporteFechaFin2', 'reporteFechaFin3'];

    inputsInicio.forEach(id => {
        const input = document.getElementById(id);
        if (input) {
            input.value = fechaInicio;
            input.max = fechaFin;
            input.addEventListener('change', function () {
                validarRangoFechas(this, document.getElementById(id.replace('Inicio', 'Fin')));
            });
        }
    });

    inputsFin.forEach(id => {
        const input = document.getElementById(id);
        if (input) {
            input.value = fechaFin;
            input.max = fechaFin;
            input.addEventListener('change', function () {
                validarRangoFechas(document.getElementById(id.replace('Fin', 'Inicio')), this);
            });
        }
    });
}

function validarRangoFechas(inputInicio, inputFin) {
    const fechaInicio = new Date(inputInicio.value);
    const fechaFin = new Date(inputFin.value);
    const hoy = new Date();
    hoy.setHours(0, 0, 0, 0);

    const fechaInicioAjustada = new Date(fechaInicio.getTime() + fechaInicio.getTimezoneOffset() * 60000);
    const fechaFinAjustada = new Date(fechaFin.getTime() + fechaFin.getTimezoneOffset() * 60000);

    if (fechaInicioAjustada > hoy) {
        mostrarNotificacion('La fecha de inicio no puede ser futura', 'error');
        inputInicio.value = hoy.toISOString().split('T')[0];
        return false;
    }

    if (fechaFinAjustada > hoy) {
        mostrarNotificacion('La fecha fin no puede ser futura', 'error');
        inputFin.value = hoy.toISOString().split('T')[0];
        return false;
    }

    if (fechaInicio > fechaFin) {
        mostrarNotificacion('La fecha de inicio no puede ser mayor que la fecha fin', 'error');
        inputInicio.value = inputFin.value;
        return false;
    }

    inputFin.min = inputInicio.value;
    inputInicio.max = inputFin.value;

    return true;
}

// --- Generación de Reportes con Múltiples Formatos ---

async function generarReporteFicha(formato = 'excel') {
    const ficha = document.getElementById('reporteFichaSelect').value;
    const fechaInicio = document.getElementById('reporteFechaInicio1').value;
    const fechaFin = document.getElementById('reporteFechaFin1').value;

    if (!ficha || !fechaInicio || !fechaFin) {
        mostrarNotificacion('Complete todos los campos', 'error');
        return;
    }

    try {
        const url = `api/asistencias.php?ficha=${ficha}&fecha_inicio=${fechaInicio}&fecha_fin=${fechaFin}`;
        const response = await fetch(url);
        const result = await response.json();

        if (result.success && result.data.length > 0) {
            const estadisticas = calcularEstadisticasPorAprendiz(result.data);

            if (formato === 'excel') exportarExcelFicha(ficha, fechaInicio, fechaFin, estadisticas, result.data);
            else if (formato === 'csv') exportarCSVFicha(ficha, fechaInicio, fechaFin, estadisticas, result.data);
            else if (formato === 'pdf') exportarPDFFicha(ficha, fechaInicio, fechaFin, estadisticas, result.data);

            mostrarNotificacion(`Reporte generado en ${formato.toUpperCase()}`, 'success');
        } else {
            mostrarNotificacion('No hay datos para generar el reporte', 'warning');
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarNotificacion('Error al generar reporte', 'error');
    }
}

// Funciones de Exportación

// Funciones de Exportación

// Funciones de Exportación

function exportarExcelFicha(ficha, inicio, fin, datos, detalles) {
    if (!detalles || detalles.length === 0) return mostrarNotificacion('No hay datos', 'error');

    // Base URL para imágenes
    const baseUrl = window.location.origin + window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/'));

    let table = `
        <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
        <head>
            <!--[if gte mso 9]>
            <xml>
                <x:ExcelWorkbook>
                    <x:ExcelWorksheets>
                        <x:ExcelWorksheet>
                            <x:Name>Reporte Ficha ${ficha}</x:Name>
                            <x:WorksheetOptions>
                                <x:DisplayGridlines/>
                            </x:WorksheetOptions>
                        </x:ExcelWorksheet>
                    </x:ExcelWorksheets>
                </x:ExcelWorkbook>
            </xml>
            <![endif]-->
            <style>
                th { background-color: #39A900; color: white; font-weight: bold; border: 1px solid #ddd; text-align: center; vertical-align: middle; }
                td { border: 1px solid #ddd; padding: 5px; vertical-align: middle; }
            </style>
        </head>
        <body>
            <table border="1">
                <!-- Row 1: Merged Header (Spans 3 rows physically in Excel) -->
                <tr style="height: 100px;">
                    <!-- Cols A, B -->
                    <td colspan="2" rowspan="3" align="center" valign="middle" style="background-color: #000000;">
                        <img src="${baseUrl}/assets/img/asi.png" height="90" width="auto" alt="ASI">
                    </td>
                    <!-- Cols C, D, E, F, G -->
                    <td colspan="5" rowspan="3" align="center" valign="middle" style="background-color: #ffffff;">
                        <div style="font-size: 20px; font-weight: bold; color: #39A900;">REPORTE DE ASISTENCIAS - ASISTNET</div>
                        <div style="font-size: 14px; font-weight: bold; color: #39A900; margin-top: 5px;">Generado: ${new Date().toLocaleDateString()}</div>
                        <div style="font-size: 11px; color: #333; margin-top: 5px;">Ficha: ${ficha} | Del ${inicio} al ${fin}</div>
                    </td>
                    <!-- Col H -->
                    <td colspan="1" rowspan="3" align="center" valign="middle" style="background-color: #000000;">
                        <img src="${baseUrl}/assets/img/logosena.png" height="90" width="auto" alt="SENA">
                    </td>
                </tr>
                <!-- Empty Rows 2, 3 to consume the rowspan -->
                <tr></tr>
                <tr></tr>
                
                <!-- Row 4: Headers -->
                <tr>
                    <th style="background-color: #39A900; color: #ffffff;">FECHA</th>
                    <th style="background-color: #39A900; color: #ffffff;">FICHA</th>
                    <th style="background-color: #39A900; color: #ffffff;">DOCUMENTO</th>
                    <th style="background-color: #39A900; color: #ffffff;">APELLIDOS</th>
                    <th style="background-color: #39A900; color: #ffffff;">NOMBRES</th>
                    <th style="background-color: #39A900; color: #ffffff;">ESTADO</th>
                    <th style="background-color: #39A900; color: #ffffff;">HORA LLEGADA</th>
                    <th style="background-color: #39A900; color: #ffffff;">OBSERVACIONES</th>
                </tr>
    `;

    detalles.forEach(d => {
        let bgColor = '#ffffff';
        let color = '#000000';
        let fontWeight = 'normal';

        const est = (d.estado || '').toLowerCase();

        if (est.includes('ausente')) {
            bgColor = '#ffe4e6'; color = '#d32f2f'; fontWeight = 'bold';
        } else if (est.includes('retardo') || est.includes('retardado')) {
            bgColor = '#FFFF00'; color = '#000000'; fontWeight = 'bold';
        } else if (est.includes('presente') || est.includes('temprano')) {
            bgColor = '#ecfccb'; color = '#2e7d32'; fontWeight = 'bold';
        } else if (est.includes('excusa')) {
            bgColor = '#d1fae5'; color = '#39A900'; fontWeight = 'bold';
        }

        const fechaObj = new Date(d.creado_en || d.fecha);
        const hora = d.creado_en ? fechaObj.toLocaleTimeString() : 'N/A';

        table += `
            <tr>
                <td>${d.fecha}</td>
                <td>${ficha}</td>
                <td>${d.documento_aprendiz}</td>
                <td>${d.apellido}</td>
                <td>${d.nombre}</td>
                <td style="background-color: ${bgColor}; color: ${color}; font-weight: ${fontWeight}; text-align: center;">${d.estado}</td>
                <td style="text-align: center;">${hora}</td>
                <td>${d.observaciones || ''}</td>
            </tr>
        `;
    });

    table += '</table></body></html>';

    // BOM para UTF-8 charset (\uFEFF)
    const blob = new Blob(['\uFEFF', table], { type: 'application/vnd.ms-excel;charset=utf-8' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = `Reporte_Ficha_${ficha}_${inicio}_${fin}.xls`;
    link.click();
}

function exportarCSVFicha(ficha, inicio, fin, datos) {
    // ... (CSV function remains similar or can be simplified, keeping user's request focus on Excel/PDF)
    // For brevity, keeping it simple as user prioritized Excel
    let csv = `REPORTE DE ASISTENCIA - FICHA ${ficha}\nPeríodo: ${inicio} al ${fin}\n\n`;
    csv += "Documento,Apellidos,Nombres,Total,Presentes,Ausentes,Porcentaje\n";
    datos.forEach(d => {
        csv += `${d.documento},"${d.apellido}","${d.nombre}",${d.total},${d.presentes},${d.ausentes},${d.porcentaje}%\n`;
    });
    downloadFile(csv, `Reporte_Ficha_${ficha}.csv`, 'text/csv');
}

// ... (Logos loading remains same) ...

function exportarPDFFicha(ficha, inicio, fin, datos, detalles) {
    if (!detalles || detalles.length === 0) return mostrarNotificacion('No hay datos', 'error');

    if (!window.jspdf || !window.jspdf.jsPDF) {
        return mostrarNotificacion('Error: Librería PDF no cargada. Refresque la página.', 'error');
    }

    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();

    // Logos
    if (logoBase64) doc.addImage(logoBase64, 'PNG', 15, 15, 30, 15);
    if (logoSenaBase64) doc.addImage(logoSenaBase64, 'PNG', 170, 10, 25, 25);

    // Título
    doc.setFont("helvetica", "bold");
    doc.setFontSize(14);
    doc.setTextColor(57, 169, 0); // SENA Green
    doc.text(`REPORTE DE ASISTENCIA - FICHA ${ficha}`, 105, 25, { align: "center" });

    doc.setFontSize(10);
    doc.setTextColor(100);
    doc.text(`Periodo: ${inicio} al ${fin}`, 105, 32, { align: "center" });

    // Tabla Resumen
    const colResumen = ["Documento", "Aprendiz", "Total", "Presentes", "Ausentes", "%"];
    const rowResumen = datos.map(d => [
        d.documento,
        `${d.apellido} ${d.nombre}`,
        d.total,
        d.presentes,
        d.ausentes,
        `${d.porcentaje}%`
    ]);

    doc.autoTable({
        head: [colResumen],
        body: rowResumen,
        startY: 40,
        theme: 'grid',
        headStyles: { fillColor: [57, 169, 0], textColor: 255 },
        styles: { fontSize: 8 }
    });

    // Tabla Detallada
    doc.addPage();
    doc.text("Detalle de Asistencias", 14, 20);

    const colDetalle = ["Fecha", "Hora", "Aprendiz", "Estado", "Observaciones"];
    const rowDetalle = detalles.map(d => {
        const fechaObj = new Date(d.creado_en || d.fecha);
        return [
            d.fecha,
            d.creado_en ? fechaObj.toLocaleTimeString() : '-',
            `${d.apellido} ${d.nombre}`,
            d.estado,
            d.observaciones || ''
        ];
    });

    doc.autoTable({
        head: [colDetalle],
        body: rowDetalle,
        startY: 25,
        theme: 'grid',
        headStyles: { fillColor: [57, 169, 0], textColor: 255 },
        styles: { fontSize: 8 },
        didParseCell: function (data) {
            if (data.section === 'body' && data.column.index === 3) {
                const est = data.cell.raw.toLowerCase();
                if (est.includes('ausente')) {
                    data.cell.styles.textColor = [229, 62, 62]; // Rojo
                } else if (est.includes('retardo') || est.includes('retardado')) {
                    data.cell.styles.textColor = [183, 149, 11]; // Amarillo Oscuro
                } else if (est.includes('presente')) {
                    data.cell.styles.textColor = [77, 160, 10]; // Verde
                }
                data.cell.styles.fontStyle = 'bold';
            }
        }
    });

    doc.save(`Reporte_Ficha_${ficha}_${inicio}_${fin}.pdf`);
}

// Utilidades
function downloadFile(content, fileName, mimeType) {
    const blob = new Blob([content], { type: mimeType });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = fileName;
    link.click();
}

function calcularEstadisticasPorAprendiz(datos) {
    const porAprendiz = {};
    datos.forEach(d => {
        const key = d.documento_aprendiz;
        if (!porAprendiz[key]) {
            porAprendiz[key] = {
                documento: d.documento_aprendiz,
                nombre: d.nombre,
                apellido: d.apellido,
                total: 0, presentes: 0, ausentes: 0, justificados: 0
            };
        }
        porAprendiz[key].total++;
        if (d.estado === 'Presente') porAprendiz[key].presentes++;
        if (d.estado === 'Ausente') porAprendiz[key].ausentes++;
        if (d.estado === 'Justificado') porAprendiz[key].justificados++;
    });
    return Object.values(porAprendiz).map(a => ({
        ...a,
        porcentaje: a.total > 0 ? ((a.presentes / a.total) * 100).toFixed(1) : 0
    })).sort((a, b) => b.porcentaje - a.porcentaje);
}

function mostrarNotificacion(mensaje, tipo = 'info') {
    const toast = document.createElement('div');
    toast.textContent = mensaje;
    toast.style.cssText = `
        position: fixed; top: 20px; right: 20px; padding: 15px 20px;
        background: ${tipo === 'success' ? '#10b981' : tipo === 'error' ? '#ef4444' : '#3b82f6'};
        color: white; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); z-index: 10000;
    `;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
}

