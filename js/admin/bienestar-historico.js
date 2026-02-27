/**
 * Lógica para el Historial de Asistencia de Bienestar
 */

let reuiniones = [];
let asistenciaActual = [];
let chartAsistencia = null;

document.addEventListener('DOMContentLoaded', () => {
    cargarReuniones();
    cargarGlobalStats();
});

async function cargarReuniones() {
    try {
        const res = await fetch('api/bienestar.php?action=getReuniones');
        const r = await res.json();
        if (r.success) {
            reuiniones = r.data;
            const sel = document.getElementById('selectReunion');
            sel.innerHTML = '<option value="">Seleccione una reunión...</option>';
            reuiniones.forEach(reu => {
                sel.insertAdjacentHTML('beforeend', `<option value="${reu.id}">${reu.titulo} (${reu.fecha})</option>`);
            });
        }
    } catch (e) { console.error(e); }
}

async function cargarDetalleAsistencia() {
    const id = document.getElementById('selectReunion').value;
    if (!id) return;

    try {
        // En api/bienestar.php no hay una acción directa para ver TODA la asistencia de una reunión
        // Deberíamos obtener los líderes y cruzarlos con la asistencia de esa reunión

        // 1. Obtener Líderes
        const resLid = await fetch('api/bienestar.php?action=getLideres&filtro=todos');
        const rLid = await resLid.json();

        // 2. Obtener Asistencias de la reunión
        const resAsis = await fetch(`api/bienestar.php?action=getReunionAsistencia&id=${id}`); // Necesitamos crear esta acción
        const rAsis = await resAsis.json();

        if (rLid.success) {
            const asistenciasMap = {};
            if (rAsis.success) {
                rAsis.data.forEach(as => { asistenciasMap[as.id_aprendiz] = as; });
            }

            asistenciaActual = rLid.data.map(lid => {
                const asis = asistenciasMap[lid.documento];
                return {
                    ...lid,
                    estado: asis ? asis.estado : 'ausente',
                    fecha_registro: asis ? asis.fecha_registro : '-',
                    nota: asis ? asis.nota : ''
                };
            });

            filtrarDetalle();
            actualizarGraficaLocal(asistenciaActual);
        }
    } catch (e) {
        console.error(e);
        // Fallback si la acción no existe todavía
        mostrarNotificacion('Error cargando detalles. Verifique la API.', 'error');
    }
}

function filtrarDetalle() {
    const rol = document.getElementById('filtroRol').value;
    const tbody = document.getElementById('tablaAsistenciaCuerpo');

    const filtrados = asistenciaActual.filter(a => rol === 'todos' || a.tipo === rol);

    if (filtrados.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 20px;">No hay registros</td></tr>';
        return;
    }

    tbody.innerHTML = filtrados.map(a => `
        <tr>
            <td>
                <div style="font-weight: 600;">${a.nombre} ${a.apellido}</div>
                <div style="font-size: 0.8rem; color: #666;">${a.documento}</div>
            </td>
            <td><span class="badge" style="background: #f1f5f9; color: #475569;">${a.tipo}</span></td>
            <td><span style="font-size: 0.9rem;">${a.detalle}</span></td>
            <td>${getEstadoBadge(a.estado)}</td>
            <td style="font-size: 0.85rem;">${a.fecha_registro}</td>
            <td style="font-size: 0.85rem; font-style: italic;">${a.nota || '-'}</td>
        </tr>
    `).join('');
}

function getEstadoBadge(estado) {
    const s = (estado || '').toLowerCase();
    if (s === 'asistio') return '<span class="badge" style="background: #dcfce7; color: #166534;">✓ ASISTIÓ</span>';
    if (s === 'ausente') return '<span class="badge" style="background: #fee2e2; color: #991b1b;">✗ AUSENTE</span>';
    if (s === 'justificado') return '<span class="badge" style="background: #fef9c3; color: #854d0e;">! EXCUSA</span>';
    return '<span class="badge" style="background: #f1f5f9; color: #475569;">PENDIENTE</span>';
}

function actualizarGraficaLocal(datos) {
    const ctx = document.getElementById('chartAsistenciaRoles').getContext('2d');
    if (chartAsistencia) chartAsistencia.destroy();

    const counts = { asistio: 0, ausente: 0, justificado: 0 };
    datos.forEach(d => {
        const s = (d.estado || '').toLowerCase();
        if (counts.hasOwnProperty(s)) counts[s]++;
        else counts.ausente++;
    });

    chartAsistencia = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: ['Asistieron', 'Ausentes', 'Justificados'],
            datasets: [{
                data: [counts.asistio, counts.ausente, counts.justificado],
                backgroundColor: ['#10b981', '#ef4444', '#f59e0b']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom' } }
        }
    });

    // Actualizar métricas
    const met = document.getElementById('metricas-resumen');
    const total = datos.length;
    const porAsis = total > 0 ? Math.round((counts.asistio / total) * 100) : 0;

    met.innerHTML = `
        <div style="background: #f8fafc; padding: 15px; border-radius: 8px;">
            <div style="font-size: 0.9rem; color: #64748b;">PORCENTAJE DE ASISTENCIA</div>
            <div style="font-size: 2rem; font-weight: 800; color: #10b981;">${porAsis}%</div>
        </div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
            <div style="background: #f8fafc; padding: 10px; border-radius: 8px; text-align: center;">
                <div style="font-size: 0.8rem; color: #64748b;">TOTAL LÍDERES</div>
                <div style="font-size: 1.2rem; font-weight: 700;">${total}</div>
            </div>
            <div style="background: #f8fafc; padding: 10px; border-radius: 8px; text-align: center;">
                <div style="font-size: 0.8rem; color: #64748b;">FALTANTES</div>
                <div style="font-size: 1.2rem; font-weight: 700; color: #ef4444;">${counts.ausente}</div>
            </div>
        </div>
    `;
}

async function cargarGlobalStats() {
    // Aquí se podrían cargar promedios históricos
}

async function descargarPDFAsistencia() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();

    const sel = document.getElementById('selectReunion');
    const textoReunion = sel.options[sel.selectedIndex].text;

    if (!asistenciaActual.length) {
        alert('No hay datos para exportar');
        return;
    }

    // Título y encabezado
    doc.setFontSize(18);
    doc.setTextColor(57, 169, 0); // Verde SENA
    doc.text('Reporte de Asistencia - Bienestar', 14, 22);

    doc.setFontSize(11);
    doc.setTextColor(100);
    doc.text(`Reunión: ${textoReunion}`, 14, 30);
    doc.text(`Fecha de Reporte: ${new Date().toLocaleString()}`, 14, 38);

    const columns = [
        { header: 'Aprendiz', dataKey: 'aprendiz' },
        { header: 'Rol', dataKey: 'rol' },
        { header: 'Ficha/Detalle', dataKey: 'detalle' },
        { header: 'Estado', dataKey: 'estado' },
        { header: 'Asistencia', dataKey: 'nota' }
    ];

    const data = asistenciaActual.map(a => ({
        aprendiz: `${a.nombre} ${a.apellido}\n(${a.documento})`,
        rol: a.tipo,
        detalle: a.detalle,
        estado: a.estado.toUpperCase(),
        nota: a.nota || '-'
    }));

    doc.autoTable({
        columns: columns,
        body: data,
        startY: 45,
        theme: 'grid',
        headStyles: { fillStyle: [57, 169, 0], textColor: [255, 255, 255] },
        styles: { fontSize: 9 },
        columnStyles: {
            aprendiz: { cellWidth: 50 },
            rol: { cellWidth: 35 },
            estado: { cellWidth: 25 }
        }
    });

    const fileName = `Asistencia_${textoReunion.replace(/ /g, '_')}.pdf`;
    doc.save(fileName);
}

function mostrarNotificacion(msg, tipo) {
    // Si existe una función global en main.js la usamos
    if (typeof window.mostrarNotificacion === 'function') {
        window.mostrarNotificacion(msg, tipo);
    } else {
        alert(msg);
    }
}
