document.addEventListener('DOMContentLoaded', () => {
    const params = new URLSearchParams(window.location.search);
    const tipoRelativo = params.get('tipo'); // mujer, indigena, narp, campesino, lgbtiq, discapacidad

    if (!tipoRelativo) {
        window.location.href = 'admin-aprendices.html';
        return;
    }

    const mapaTitulos = {
        'mujer': 'MUJER',
        'indigena': 'INDÍGENA',
        'narp': 'NARP',
        'campesino': 'CAMPESINO',
        'lgbtiq': 'LGBTIQ+',
        'discapacidad': 'DISCAPACIDAD'
    };

    const titulo = mapaTitulos[tipoRelativo] || tipoRelativo.toUpperCase();
    document.getElementById('poblacion-label').textContent = titulo;
    document.getElementById('page-title').textContent = `VOCERO ENFOQUE DIFERENCIAL POBLACION ${titulo}`;

    cargarDatosEnfoque(tipoRelativo, titulo);
    cargarPropuestas(titulo);
});

let integrantesGlobal = [];
let chartInstance = null;
let voceroActual = null;
let logosBase64 = {
    sena: '',
    asi: ''
};

async function cargarLogos() {
    try {
        const [resSena, resAsi] = await Promise.all([
            fetch('sena_logo.txt').then(r => r.text()),
            fetch('asi_logo.txt').then(r => r.text())
        ]);
        logosBase64.sena = resSena.trim();
        logosBase64.asi = resAsi.trim();
    } catch (e) {
        console.error("Error cargando logos:", e);
    }
}
cargarLogos();

async function cargarDatosEnfoque(tipoRelativo, tituloOriginal) {
    try {
        // 1. Cargar Vocero (usando el nombre exacto de la tabla/categoria que espera la API)
        const resV = await fetch('api/voceros_enfoque.php');
        const rV = await resV.json();

        const vocero = rV.data.find(v => v.tipo_poblacion.toLowerCase().includes(tipoRelativo.toLowerCase()));
        voceroActual = vocero;

        const voceroContainer = document.getElementById('vocero-data');
        if (vocero && vocero.documento) {
            voceroContainer.innerHTML = `
                <div class="vocero-avatar"><i class="fas fa-user-check"></i></div>
                <div>
                    <h3 style="margin: 0; color: #333;">${vocero.nombre} ${vocero.apellido}</h3>
                    <p style="margin: 4px 0; color: #666; font-size: 0.95rem;">
                        <i class="fas fa-id-card"></i> ${vocero.documento} | 
                        <i class="fas fa-barcode"></i> Ficha: ${vocero.numero_ficha}
                    </p>
                    <span class="badge badge-lectiva" style="background: #39A900; color: white;">Vocero Oficial Activo</span>
                </div>
            `;
            // Aplicar estilo al contenedor padre si es necesario
            document.getElementById('vocero-container').style.display = 'flex';
        } else {
            voceroContainer.innerHTML = `
                <div class="vocero-avatar" style="background: #f1f5f9; color: #94a3b8;"><i class="fas fa-user-slash"></i></div>
                <div>
                    <h3 style="margin: 0; color: #94a3b8;">Sin Vocero Asignado</h3>
                    <p style="margin: 4px 0; color: #94a3b8; font-style: italic;">No se ha designado un representante para esta población aún.</p>
                </div>
            `;
        }

        // 2. Cargar Integrantes (Filtrando por tabla física)
        const tablaApi = tipoRelativo === 'indigena' ? 'indígena' : tipoRelativo;
        const resI = await fetch(`api/aprendices.php?limit=-1&tabla_poblacion=${tablaApi}`);
        const rI = await resI.json();

        const listBody = document.getElementById('lista-poblacion');
        if (rI.success && rI.data.length > 0) {
            integrantesGlobal = rI.data;
            listBody.innerHTML = rI.data.map(a => `
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 1rem;">${a.documento}</td>
                    <td style="padding: 1rem; font-weight: 500;">${a.nombre} ${a.apellido}</td>
                    <td style="padding: 1rem; color: #00324D; font-weight: bold;">${a.documento}</td>
                    <td style="padding: 1rem; font-family: monospace; color: #39A900;">${a.documento}</td>
                    <td style="padding: 1rem;">${a.numero_ficha}</td>
                    <td style="padding: 1rem;"><span class="badge ${getClaseEstado(a.estado)}">${a.estado}</span></td>
                </tr>
            `).join('');
        } else {
            listBody.innerHTML = `<tr><td colspan="6" class="text-center">No se encontraron integrantes en esta población.</td></tr>`;
        }
    } catch (e) {
        console.error(e);
        mostrarNotificacion('Error al cargar datos del detalle', 'error');
    }
}

function getClaseEstado(estado) {
    if (!estado) return 'badge-secondary';
    const normalized = estado.toLowerCase().replace(/ /g, '-');
    return `badge-${normalized}`;
}

// Helper to crop image to circle securely
async function getCircularLogo(base64) {
    return new Promise((resolve) => {
        const img = new Image();
        img.onload = () => {
            const size = Math.min(img.width, img.height);
            const canvas = document.createElement('canvas');
            canvas.width = size;
            canvas.height = size;
            const ctx = canvas.getContext('2d');

            ctx.beginPath();
            ctx.arc(size / 2, size / 2, size / 2, 0, Math.PI * 2);
            ctx.clip();
            ctx.drawImage(img, (img.width - size) / 2, (img.height - size) / 2, size, size, 0, 0, size, size);
            resolve(canvas.toDataURL('image/png'));
        };
        img.onerror = () => resolve(base64);
        img.src = base64;
    });
}

// ========== PDF GENERATION ==========
window.descargarPDF = async function () {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();
    const tituloPoblacion = document.getElementById('poblacion-label').textContent;
    const nombreVocero = voceroActual ? `${voceroActual.nombre} ${voceroActual.apellido}`.toUpperCase() : 'NO ASIGNADO';

    // Logos en el encabezado
    if (logosBase64.sena) {
        doc.addImage(logosBase64.sena, 'PNG', 14, 10, 25, 25);
    }

    if (logosBase64.asi) {
        const circLogo = await getCircularLogo(logosBase64.asi);
        // Radio 50px = 13.225mm. Diámetro = 26.45mm
        doc.addImage(circLogo, 'PNG', 170, 10, 26.5, 26.5);

        // Borde verde sutil
        doc.setDrawColor(33, 115, 70); // Verde más oscuro para legibilidad
        doc.setLineWidth(0.3);
        doc.circle(170 + 13.25, 10 + 13.25, 13.25, 'S');
    }

    // Títulos - Colores con mejor contraste
    const verdeContraste = [0, 100, 0]; // Verde oscuro real

    doc.setFont("helvetica", "bold");
    doc.setFontSize(16);
    doc.setTextColor(verdeContraste[0], verdeContraste[1], verdeContraste[2]);
    doc.text(`TIPO DE POBLACIÓN ${tituloPoblacion}`, 105, 25, { align: 'center' });

    doc.setFont("helvetica", "normal");
    doc.setFontSize(12);
    doc.setTextColor(verdeContraste[0], verdeContraste[1], verdeContraste[2]); // Gris oscuro
    doc.text(`VOCERO DE ENFOQUE DIFERENCIAL ${tituloPoblacion}`, 105, 33, { align: 'center' });

    doc.setFont("helvetica", "bold");
    doc.setFontSize(14);
    doc.setTextColor(verdeContraste[0], verdeContraste[1], verdeContraste[2]);
    doc.text(nombreVocero, 105, 41, { align: 'center' });

    doc.setFont("helvetica", "italic");
    doc.setFontSize(10);
    doc.setTextColor(60);
    const fecha = new Date().toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' });
    doc.text(`Fecha de reporte: ${fecha}`, 105, 48, { align: 'center' });

    // Tabla de integrantes
    const columns = [
        { header: 'DOCUMENTO', dataKey: 'doc' },
        { header: 'NOMBRES', dataKey: 'nom' },
        { header: 'APELLIDOS', dataKey: 'ape' },
        { header: 'FICHA', dataKey: 'fic' },
        { header: 'PROGRAMA', dataKey: 'pro' },
        { header: 'ESTADO', dataKey: 'est' }
    ];

    const rows = integrantesGlobal.map(a => ({
        doc: a.documento,
        nom: a.nombre.toUpperCase(),
        ape: a.apellido.toUpperCase(),
        fic: a.numero_ficha,
        pro: (a.nombre_programa || 'N/A').toUpperCase(),
        est: a.estado.toUpperCase()
    }));

    doc.autoTable({
        startY: 55,
        head: [columns.map(c => c.header)],
        body: rows.map(r => Object.values(r)),
        theme: 'grid', // Revertir a cuadrícula para máxima legibilidad si la cebra falla
        headStyles: {
            fillColor: verdeContraste,
            textColor: [255, 255, 255],
            fontSize: 10,
            fontStyle: 'bold',
            halign: 'center'
        },
        styles: {
            fontSize: 8,
            cellPadding: 3,
            valign: 'middle',
            overflow: 'linebreak'
        },
        columnStyles: {
            0: { halign: 'center', cellWidth: 25 },
            3: { halign: 'center', cellWidth: 20 },
            5: { halign: 'center', cellWidth: 25 }
        },
        alternateRowStyles: {
            fillColor: [240, 255, 240] // Verde muy pálido para cebra legible
        }
    });

    doc.save(`Reporte_SENAPRE_${tituloPoblacion.replace(' ', '_')}.pdf`);
};

// ========== PROPUESTAS ==========
async function cargarPropuestas(titulo) {
    try {
        const res = await fetch(`api/propuestas_enfoque.php?tipo=${titulo}`);
        const r = await res.json();
        const container = document.getElementById('lista-propuestas');

        if (r.success && r.data.length > 0) {
            container.innerHTML = r.data.map(p => `
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 15px; border-radius: 8px; margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center;">
                    <div style="flex: 1;">
                        <p style="margin: 0; color: #334155;">${p.propuesta}</p>
                        <small style="color: #94a3b8;">${new Date(p.fecha_creacion).toLocaleString()}</small>
                    </div>
                    <button onclick="eliminarPropuesta(${p.id})" style="color: #ef4444; background: none; border: none; cursor: pointer; padding: 5px;">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            `).join('');
        } else {
            container.innerHTML = `<p style="text-align: center; color: #94a3b8; padding: 20px;">No hay propuestas registradas aún.</p>`;
        }
    } catch (e) { console.error(e); }
}

window.guardarPropuesta = async function () {
    const text = document.getElementById('nueva-propuesta').value.trim();
    const titulo = document.getElementById('poblacion-label').textContent;
    if (!text) return;

    try {
        const res = await fetch('api/propuestas_enfoque.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ tipo_poblacion: titulo, propuesta: text })
        });
        const r = await res.json();
        if (r.success) {
            document.getElementById('nueva-propuesta').value = '';
            cargarPropuestas(titulo);
            mostrarNotificacion('Propuesta añadida', 'success');
        }
    } catch (e) { console.error(e); }
};

window.eliminarPropuesta = async function (id) {
    if (!confirm('¿Eliminar esta propuesta?')) return;
    try {
        const res = await fetch(`api/propuestas_enfoque.php?id=${id}`, { method: 'DELETE' });
        const r = await res.json();
        if (r.success) {
            cargarPropuestas(document.getElementById('poblacion-label').textContent);
            mostrarNotificacion('Propuesta eliminada', 'success');
        }
    } catch (e) { console.error(e); }
};

// ========== ESTADÍSTICAS ==========
window.mostrarEstadisticas = function () {
    document.getElementById('modalStats').style.display = 'flex';

    // Procesar datos para la gráfica (Agrupar por nombre_programa)
    const conteo = {};
    integrantesGlobal.forEach(a => {
        const prog = a.nombre_programa || 'Sin Programa';
        conteo[prog] = (conteo[prog] || 0) + 1;
    });

    const labels = Object.keys(conteo);
    const valores = Object.values(conteo);

    const ctx = document.getElementById('chartStats').getContext('2d');

    if (chartInstance) chartInstance.destroy();

    chartInstance = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Aprendices por Formación',
                data: valores,
                backgroundColor: '#ea580c',
                borderRadius: 5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 } }
            },
            plugins: {
                legend: { display: false }
            }
        }
    });
};

window.mostrarPropuestas = function () {
    document.getElementById('modalPropuestas').style.display = 'flex';
};

window.CerrarModal = function (id) {
    document.getElementById(id).style.display = 'none';
};

function mostrarNotificacion(msg, tipo = 'success') {
    // Implementación simple si no existe el global
    const toast = document.createElement('div');
    toast.textContent = msg;
    toast.style.cssText = `
        position: fixed; top: 20px; right: 20px; padding: 15px 25px;
        border-radius: 8px; color: white; z-index: 10001; font-weight: 500;
        background: ${tipo === 'success' ? '#39A900' : '#ef4444'};
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    `;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
}
