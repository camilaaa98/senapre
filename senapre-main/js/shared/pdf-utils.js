/**
 * pdf-utils.js — v2.2.0
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
    async function crearCabecera(doc, { titulo = '', subtitulo = '', responsable = '', orientacion = 'landscape' } = {}) {
        const pw = doc.internal.pageSize.getWidth();
        const isLandscape = orientacion === 'landscape';

        // Altura del encabezado (ajustada para el nuevo diseño)
        const headerH = isLandscape ? 60 : 64;
        const franjaH = isLandscape ? 52 : 56;

        // ── Fondo cabecera principal (verde sena)
        doc.setFillColor(0, 100, 0);
        doc.rect(0, 0, pw, headerH, 'F');

        // ── Franja inferior más oscura
        doc.setFillColor(0, 60, 0);
        doc.rect(0, franjaH, pw, 8, 'F');

        // ── Logos
        const [imgSena, imgSenapre] = await Promise.all([
            cargarImagen('assets/img/logosena.png'),
            cargarImagen('assets/img/asi.png')
        ]);

        // Logo SENA izquierda — 32x32mm
        const logoSenaSize = 32;
        const logoSenaX = 14;
        const logoSenaY = 10;
        if (imgSena) doc.addImage(imgSena, 'PNG', logoSenaX, logoSenaY, logoSenaSize, logoSenaSize);

        // Logo SenApre circular — radio 50px de pantalla ≈ 13.2mm radio (26.4mm diámetro)
        const logoSenapreD = 30; // diámetro aproximado para visibilidad premium
        const logoSenapreX = pw - 14 - logoSenapreD;
        const logoSenapreY = 11;
        
        if (imgSenapre) {
            // Clipping circular perfecto sin borde blanco
            doc.saveGraphicsState();
            doc.circle(logoSenapreX + logoSenapreD / 2, logoSenapreY + logoSenapreD / 2, logoSenapreD / 2); // Definir el path del círculo
            doc.clip(); // Aplicar el recorte basado en el path definido
            doc.addImage(imgSenapre, 'PNG', logoSenapreX, logoSenapreY, logoSenapreD, logoSenapreD);
            doc.restoreGraphicsState();
        }

        // ── Texto del Encabezado (Nueva Jerarquía)
        doc.setTextColor(255, 255, 255);
        doc.setFont('helvetica', 'bold');
        
        // Línea 1: SENAPRE
        doc.setFontSize(22);
        doc.text('SENAPRE', pw / 2, 18, { align: 'center' });

        // Línea 2: REGIONAL CAQUETÁ
        doc.setFontSize(14);
        doc.text('REGIONAL CAQUET\u00c1', pw / 2, 27, { align: 'center' });

        // Línea 3: Detalle del reporte (Título e información adicional)
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(11);
        const infoDetalle = `${titulo}${subtitulo ? ' | ' + subtitulo : ''}${responsable ? ' | ' + responsable : ''}`;
        doc.text(infoDetalle, pw / 2, 38, { align: 'center' });

        // ── Franja de fecha (fondo claro)
        const fechaY = headerH;
        doc.setFillColor(245, 248, 245);
        doc.rect(0, fechaY, pw, 10, 'F');

        // Fecha larga en español
        const fechaStr = new Date().toLocaleDateString('es-CO', {
            weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
        });
        doc.setFontSize(8);
        doc.setFont('helvetica', 'italic');
        doc.setTextColor(60, 60, 60);
        doc.text(`Fecha de generaci\u00f3n: ${fechaStr}`, pw / 2, fechaY + 6.5, { align: 'center' });

        // Retornar Y de inicio del contenido
        return fechaY + 14;
    }

    /**
     * Pie de página estándar en cada hoja del PDF
     */
    function pieDePagina(doc) {
        const pw = doc.internal.pageSize.getWidth();
        const ph = doc.internal.pageSize.getHeight();
        const pageNum   = doc.internal.getCurrentPageInfo().pageNumber;
        const totalPags = doc.internal.getNumberOfPages();

        doc.setFontSize(7);
        doc.setFont('helvetica', 'normal');
        doc.setTextColor(140, 140, 140);

        // Línea separadora
        doc.setDrawColor(200, 200, 200);
        doc.setLineWidth(0.3);
        doc.line(12, ph - 11, pw - 12, ph - 11);

        doc.text('Generado por SenApre \u2014 SENA Regional Caquet\u00e1', 14, ph - 6);
        doc.text(
            `Página ${pageNum} de ${totalPags}`,
            pw - 14, ph - 6,
            { align: 'right' }
        );
    }

    /**
     * Estilos estándar para autoTable
     */
    const ESTILOS_TABLA = {
        theme: 'grid',
        styles: {
            font: 'helvetica',
            fontSize: 8.5,
            cellPadding: { top: 4, right: 5, bottom: 4, left: 5 },
            halign: 'center',
            valign: 'middle',
            lineColor: [210, 210, 210],
            lineWidth: 0.3
        },
        headStyles: {
            fillColor: [0, 100, 0],
            textColor: [255, 255, 255],
            fontStyle: 'bold',
            fontSize: 9,
            halign: 'center',
            cellPadding: 5
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
