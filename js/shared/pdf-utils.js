/**
 * pdf-utils.js — v3.0.0
 * SENAPRE — Módulo compartido para generación de PDFs estandarizados
 * 
 * Uso:
 *   const cabeceraY = await SenaPrePDF.crearCabecera(doc, { titulo, subtitulo, responsable, orientacion });
 *   doc.autoTable({ startY: cabeceraY, ... });
 */

'use strict';

const SenaPrePDF = (() => {

    /**
     * Carga una imagen desde una URL y la convierte a base64
     */
    async function cargarImagen(src) {
        return new Promise((resolve, reject) => {
            const img = new Image();
            img.crossOrigin = 'anonymous';
            img.onload = () => resolve(img);
            img.onerror = () => resolve(null); // No bloquear si falla
            img.src = src;
        });
    }

    /**
     * Crea la cabecera profesional SENA — SenApre en el PDF
     * 
     * @param {jsPDF} doc - Instancia de jsPDF YA CREADA
     * @param {Object} opts
     *   @param {string} opts.titulo        - Título principal (ej: "REPORTE DE APRENDICES")
     *   @param {string} opts.subtitulo     - Subtítulo (ej: "Vocería de Formación — Ficha 2995479")
     *   @param {string} [opts.responsable] - Nombre completo del responsable (opcional)
     *   @param {string} [opts.orientacion] - 'landscape' | 'portrait' (default: 'landscape')
     * @returns {number} Y de inicio de contenido (para pasarlo en startY del autoTable)
     */
    /**
     * Crea la cabecera profesional SENA — SenApre en el PDF
     */
    async function crearCabecera(doc, { titulo = '', subtitulo = '', responsable = '', orientacion = 'landscape' } = {}) {
        const pw = doc.internal.pageSize.getWidth();
        const headerH = 50; // Altura fija optimizada

        // ── Fondo cabecera principal (verde sena)
        doc.setFillColor(0, 100, 0);
        doc.rect(0, 0, pw, headerH, 'F');

        // ── Franja inferior más oscura
        doc.setFillColor(0, 60, 0);
        doc.rect(0, headerH - 8, pw, 8, 'F');

        // ── Logos
        const [imgSena, imgSenapre] = await Promise.all([
            cargarImagen('assets/img/logosena.png'),
            cargarImagen('assets/img/asi.png')
        ]);

        // Logo SENA izquierda
        if (imgSena) doc.addImage(imgSena, 'PNG', 12, 8, 25, 25);

        // Logo SenApre circular (Radio 50px ≈ 13.2mm -> Diámetro 26.4mm)
        // Usamos un radio de 13mm para un corte limpio
        const logoRad = 14; 
        const logoX = pw - 15 - (logoRad * 2);
        const logoY = 6;
        
        if (imgSenapre) {
            doc.saveGraphicsState();
            // Definir círculo de recorte (x, y, radio, estilo)
            // Estilo null o 'S' define el path sin rellenar
            doc.circle(logoX + logoRad, logoY + logoRad, logoRad, 'S'); 
            doc.clip(); 
            doc.addImage(imgSenapre, 'PNG', logoX, logoY, logoRad * 2, logoRad * 2);
            doc.restoreGraphicsState();
        }

        // ── Texto del Encabezado
        doc.setTextColor(255, 255, 255);
        doc.setFont('helvetica', 'bold');
        
        // SENAPRE
        doc.setFontSize(22);
        doc.text('SENAPRE', pw / 2, 16, { align: 'center' });

        // REGIONAL CAQUETÁ
        doc.setFontSize(14);
        doc.text('REGIONAL CAQUET\u00c1', pw / 2, 24, { align: 'center' });

        // Detalle
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(10);
        const info = `${titulo}${subtitulo ? ' | ' + subtitulo : ''}`;
        doc.text(info, pw / 2, 34, { align: 'center' });

        // Franja de fecha
        const fechaY = headerH;
        doc.setFillColor(245, 248, 245);
        doc.rect(0, fechaY, pw, 8, 'F');

        const fechaStr = new Date().toLocaleDateString('es-CO', {
            weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
        });
        doc.setFontSize(8);
        doc.setFont('helvetica', 'italic');
        doc.setTextColor(60, 60, 60);
        doc.text(`Fecha de generaci\u00f3n: ${fechaStr}`, pw / 2, fechaY + 5.5, { align: 'center' });

        return fechaY + 12;
    }

    /**
     * Pie de página estándar
     */
    function pieDePagina(doc) {
        const pw = doc.internal.pageSize.getWidth();
        const ph = doc.internal.pageSize.getHeight();
        const pageNum = doc.internal.getCurrentPageInfo().pageNumber;
        const totalPags = doc.internal.getNumberOfPages();

        doc.setFontSize(7);
        doc.setFont('helvetica', 'normal');
        doc.setTextColor(140, 140, 140);
        doc.line(12, ph - 11, pw - 12, ph - 11);
        doc.text('Generado por SenApre \u2014 SENA Regional Caquet\u00e1', 14, ph - 6);
        doc.text(`Página ${pageNum} de ${totalPags}`, pw - 14, ph - 6, { align: 'right' });
    }

    /**
     * Estilos de tabla optimizados para espacio
     */
    const ESTILOS_TABLA = {
        theme: 'grid',
        styles: {
            font: 'helvetica',
            fontSize: 8.5,
            cellPadding: 2.5, // Reducido para que quepan más filas
            halign: 'center',
            valign: 'middle',
            lineColor: [210, 210, 210]
        },
        headStyles: {
            fillColor: [0, 100, 0],
            textColor: [255, 255, 255],
            fontStyle: 'bold'
        },
        alternateRowStyles: { fillColor: [245, 252, 240] }
    };

    /**
     * Colorear celdas de estado automáticamente
     */
    function colorearEstado(data, colIndex = null) {
        if (data.section !== 'body') return;
        const idx = colIndex !== null ? colIndex : data.column.index;
        if (data.column.index !== idx) return;
        const val = (data.cell.raw || '').toUpperCase();
        const map = {
            'LECTIVA':   [22, 163, 74],
            'CANCELADO': [220, 38, 38],
            'RETIRADO':  [217, 119, 6],
            'APLAZADO':  [107, 114, 128],
            'TRASLADO':  [107, 114, 128]
        };
        if (map[val]) data.cell.styles.textColor = map[val];
    }

    return { crearCabecera, pieDePagina, ESTILOS_TABLA, colorearEstado };
})();
