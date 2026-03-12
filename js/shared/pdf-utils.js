/**
 * pdf-utils.js — v1.0.0
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

        // Altura del encabezado (más grande para logos y texto)
        const headerH = isLandscape ? 54 : 57;
        const franjaH = isLandscape ? 46 : 49;

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

        // Logo SENA izquierda — 30x30mm
        const logoSenaW = 30;
        const logoSenaH = 30;
        const logoSenaX = 14;
        const logoSenaY = 10;
        if (imgSena) doc.addImage(imgSena, 'PNG', logoSenaX, logoSenaY, logoSenaW, logoSenaH);

        // Logo SenApre circular — radio aprox 18mm = diámetro 36mm
        // jsPDF no soporta clipping nativo, dibujamos la imagen cuadrada y luego
        // superponemos un fondo circular para simular el círculo
        const logoSenapreD = 36; // diámetro en mm
        const logoSenapreX = pw - 14 - logoSenapreD;
        const logoSenapreY = 9;
        if (imgSenapre) {
            // Primero dibujar el fondo circular verde oscuro
            doc.setFillColor(0, 50, 0);
            doc.circle(pw - 14 - logoSenapreD / 2, logoSenapreY + logoSenapreD / 2, logoSenapreD / 2, 'F');
            // Luego la imagen encima (recortada visualmente por el círculo de fondo)
            doc.addImage(imgSenapre, 'PNG', logoSenapreX, logoSenapreY, logoSenapreD, logoSenapreD);
        }

        // ── Línea decorativa entre logos
        doc.setDrawColor(255, 255, 255);
        doc.setLineWidth(0.3);
        doc.line(logoSenaX + logoSenaW + 4, 12, logoSenapreX - 4, 12);
        doc.line(logoSenaX + logoSenaW + 4, headerH - 10, logoSenapreX - 4, headerH - 10);

        // ── Texto "SENA — SENAPRE" (más grande, bajado para no pisar la línea)
        doc.setTextColor(255, 255, 255);
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(12);
        doc.text('SENA \u2014 SENAPRE', pw / 2, isLandscape ? 19 : 20, { align: 'center' });

        // ── Título principal
        doc.setFontSize(isLandscape ? 15 : 13);
        doc.setFont('helvetica', 'bold');
        doc.text(titulo.toUpperCase(), pw / 2, isLandscape ? 29 : 30, { align: 'center' });

        // ── Subtítulo
        doc.setFontSize(isLandscape ? 9.5 : 9);
        doc.setFont('helvetica', 'normal');
        doc.text(subtitulo, pw / 2, isLandscape ? 38 : 40, { align: 'center' });

        // ── Responsable (si aplica)
        if (responsable) {
            doc.setFontSize(isLandscape ? 9 : 8);
            doc.setFont('helvetica', 'bold');
            doc.text(responsable.toUpperCase(), pw / 2, isLandscape ? 46 : 48, { align: 'center' });
        }

        // ── Franja de fecha (fondo claro)
        const fechaY = headerH;
        doc.setFillColor(245, 248, 245);
        doc.rect(0, fechaY, pw, 10, 'F');

        // Fecha larga en español
        const fechaStr = new Date().toLocaleDateString('es-CO', {
            weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
        });
        doc.setFontSize(7.5);
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
