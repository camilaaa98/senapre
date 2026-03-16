/**
 * report-utils.js — v1.0.0
 * SENAPRE — Estilos globales para PDFs y Excels
 * 
 * Uso:
 *   await ReportUtils.aplicarEstiloPDF(doc, { titulo, subtitulo, responsable });
 *   const excelData = ReportUtils.prepararExcel(data, { titulo, headers });
 */

'use strict';

const ReportUtils = (() => {

    /**
     * Aplica estilo profesional SENAPRE a cualquier PDF
     */
    async function aplicarEstiloPDF(doc, { 
        titulo = '', 
        subtitulo = '', 
        responsable = '', 
        orientacion = 'landscape' 
    } = {}) {
        
        const pw = doc.internal.pageSize.getWidth();
        const headerH = 45;

        // ── Fondo cabecera principal (verde sena)
        doc.setFillColor(0, 100, 0);
        doc.rect(0, 0, pw, headerH, 'F');

        // ── Franja inferior más oscura
        doc.setFillColor(0, 60, 0);
        doc.rect(0, headerH - 6, pw, 6, 'F');

        // ── Cargar logos (async)
        const [imgSena, imgSenapre] = await Promise.all([
            cargarImagen('assets/img/logosena.png'),
            cargarImagen('assets/img/asi.png')
        ]);

        // Logo SENA izquierda
        if (imgSena) doc.addImage(imgSena, 'PNG', 12, 6, 22, 22);

        // Logo SenApre derecha
        const logoSize = 24;
        const logoX = pw - 12 - logoSize;
        const logoY = 6;
        if (imgSenapre) {
            doc.addImage(imgSenapre, 'PNG', logoX, logoY, logoSize, logoSize);
        }

        // ── Texto del Encabezado
        doc.setTextColor(255, 255, 255);
        doc.setFont('helvetica', 'bold');
        
        // SENAPRE
        doc.setFontSize(20);
        doc.text('SENAPRE', pw / 2, 16, { align: 'center' });

        // REGIONAL CAQUETÁ
        doc.setFontSize(12);
        doc.text('REGIONAL CAQUETÁ', pw / 2, 23, { align: 'center' });

        // Detalle
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(10);
        const info = `${titulo}${subtitulo ? ' | ' + subtitulo : ''}`;
        doc.text(info, pw / 2, 32, { align: 'center' });

        // ── Franja de fecha
        const fechaY = headerH;
        doc.setFillColor(245, 248, 245);
        doc.rect(0, fechaY, pw, 8, 'F');

        const fechaStr = new Date().toLocaleDateString('es-CO', {
            weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
        });
        doc.setFontSize(8);
        doc.setTextColor(50, 50, 50);
        doc.text(`Fecha: ${fechaStr}`, 12, fechaY + 5);

        // Responsable si existe
        if (responsable) {
            doc.text(`Responsable: ${responsable}`, pw - 12, fechaY + 5, { align: 'right' });
        }

        // ── Pie de página
        const footerY = doc.internal.pageSize.getHeight() - 15;
        doc.setFillColor(245, 245, 245);
        doc.rect(0, footerY, pw, 15, 'F');

        doc.setFontSize(7);
        doc.setTextColor(100, 100, 100);
        doc.text('Sistema Nacional de Aprendices - SENAPRE', pw / 2, footerY + 5, { align: 'center' });
        doc.text('Regional Caquetá - Centro de Formación', pw / 2, footerY + 10, { align: 'center' });

        // ── Línea separadora
        doc.setDrawColor(200, 200, 200);
        doc.setLineWidth(0.5);
        doc.line(12, headerH + 8, pw - 12, headerH + 8);

        // Retornar Y de inicio de contenido
        return headerH + 16;
    }

    /**
     * Prepara datos para Excel con estilo SENAPRE
     */
    function prepararExcel(data, { 
        titulo = '', 
        headers = [], 
        filename = 'reporte' 
    } = {}) {
        
        return {
            data: data,
            headers: headers,
            filename: `SENAPRE_${titulo}_${new Date().toISOString().split('T')[0]}.xlsx`,
            styles: {
                header: {
                    fill: { fgColor: { rgb: ['FFFFFF', 'FFFFFF', 'FFFFFF'] } },
                    font: { bold: true, sz: 12 },
                    alignment: { horizontal: 'center', vertical: 'center' },
                    border: { top: { style: 'thin' }, bottom: { style: 'thin' }, left: { style: 'thin' }, right: { style: 'thin' } }
                },
                cell: {
                    font: { sz: 10 },
                    alignment: { horizontal: 'left', vertical: 'center' },
                    border: { top: { style: 'thin' }, bottom: { style: 'thin' }, left: { style: 'thin' }, right: { style: 'thin' } }
                }
            },
            worksheet: {
                name: `SENAPRE - ${titulo}`,
                views: [{ state: 'frozen', xSplit: 1, ySplit: 0 }]
            }
        };
    }

    /**
     * Carga una imagen desde una URL y la convierte a base64
     */
    async function cargarImagen(src) {
        return new Promise((resolve, reject) => {
            const img = new Image();
            img.crossOrigin = 'anonymous';
            img.onload = () => resolve(img);
            img.onerror = () => resolve(null);
            img.src = src;
        });
    }

    /**
     * Aplica estilo a tablas en PDF
     */
    function estiloTablaPDF(options = {}) {
        return {
            theme: 'grid',
            styles: {
                font: 'helvetica',
                fontSize: 9,
                cellPadding: 3,
                lineWidth: 0.5,
                lineColor: [200, 200, 200],
                fillColor: [255, 255, 255]
            },
            headStyles: {
                fillColor: [0, 100, 0],
                textColor: [255, 255, 255],
                fontStyle: 'bold',
                fontSize: 10,
                halign: 'center',
                valign: 'middle'
            },
            alternateRowStyles: {
                fillColor: [248, 250, 252]
            },
            margin: { top: 0, right: 12, bottom: 20, left: 12 },
            ...options
        };
    }

    // ── Exportar funciones públicas
    return {
        aplicarEstiloPDF,
        prepararExcel,
        estiloTablaPDF,
        cargarImagen
    };

})();

// Auto-inicialización para uso global
if (typeof window !== 'undefined') {
    window.ReportUtils = ReportUtils;
}
