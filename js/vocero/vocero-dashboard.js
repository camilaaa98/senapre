/**
 * vocero-dashboard.js — v2.2.0
 * SENAPRE — SOLID + Responsive
 * Módulo unificado para vocero-dashboard.html y vocero-aprendices.html
 */
'use strict';

const VoceroDash = (() => {

    // ── 1. STATE ─────────────────────────────────────────────
    const State = {
        idUsuario: '',
        vocNombre: '',
        vocFicha: '',
        vocTipo: '',
        aprendices: [],
        paginaActual: 1,
        POR_PAG: 10,
        INACTIVOS: new Set(['CANCELADO', 'RETIRADO', 'APLAZADO', 'TRASLADO', 'FINALIZADO']),
        COLORES: {
            LECTIVA: '#39A900', CANCELADO: '#dc2626', RETIRADO: '#f59e0b',
            APLAZADO: '#64748b', TRASLADO: '#94a3b8'
        },
        charts: { dona: null, barras: null }
    };

    // ── 2. API ────────────────────────────────────────────────
    const API = {
        async fetchDashboard(id) {
            const r = await fetch(`api/vocero/dashboard.php?id_usuario=${encodeURIComponent(id)}`);
            if (!r.ok) throw new Error(`HTTP ${r.status}`);
            return r.json();
        },
        async updateAprendiz(data) {
            const r = await fetch('api/vocero/dashboard.php', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            return r.json();
        }
    };

    // ── 3. UI — Partes comunes ────────────────────────────────
    const UI = {
        set(id, val) {
            const e = document.getElementById(id);
            if (e) e.textContent = val;
        },

        renderHeader() {
            this.set('voc-nombre-sidebar', State.vocNombre || 'Vocero');
            this.set('voc-ficha-sidebar', State.vocFicha);
            this.set('voc-ficha-header', State.vocFicha);
            if (document.getElementById('voc-ficha-tabla')) this.set('voc-ficha-tabla', State.vocFicha);
            if (document.getElementById('voc-titulo-header')) this.set('voc-titulo-header', `Panel — ${State.vocNombre}`);
            this.set('voc-tipo-label', `${State.vocTipo} • Ficha ${State.vocFicha}`);
            document.title = `Panel Vocería — Ficha ${State.vocFicha}`;
        },

        renderIndicadores(resumen, total) {
            this.set('st-total', total);
            this.set('st-lectiva', resumen['LECTIVA'] || 0);
            this.set('st-cancelado', resumen['CANCELADO'] || 0);
            this.set('st-retirado', resumen['RETIRADO'] || 0);
            this.set('st-traslado', resumen['TRASLADO'] || 0);
        },

        renderTablaEstados(resumen, total) {
            const orden = ['LECTIVA', 'CANCELADO', 'RETIRADO', 'APLAZADO', 'TRASLADO'];
            Object.keys(resumen).forEach(k => { if (!orden.includes(k)) orden.push(k); });
            let html = orden.filter(e => resumen[e]).map(est => {
                const cnt = resumen[est];
                const pct = ((cnt / total) * 100).toFixed(1);
                return `<tr>
                    <td><span class="voc-dot dot-${est.toLowerCase()}"></span><strong>${est}</strong></td>
                    <td>${cnt}</td>
                    <td>${pct}%</td>
                </tr>`;
            }).join('');
            html += `<tr class="row-total">
                <td><strong>TOTAL</strong></td>
                <td><strong>${total} aprendices</strong></td>
                <td><strong>100%</strong></td>
            </tr>`;
            const el = document.getElementById('tabla-estados-body');
            if (el) el.innerHTML = html;
        },

        renderCharts(resumen) {
            if (State.charts.dona) State.charts.dona.destroy();
            if (State.charts.barras) State.charts.barras.destroy();
            const labels = Object.keys(resumen);
            const values = labels.map(e => resumen[e]);
            const colores = labels.map(e => State.COLORES[e] || '#cbd5e1');
            const base = {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', labels: { font: { size: 11, family: 'Inter' }, padding: 12 } } }
            };
            const elD = document.getElementById('chart-dona');
            if (elD) State.charts.dona = new Chart(elD, {
                type: 'doughnut',
                data: { labels, datasets: [{ data: values, backgroundColor: colores, borderWidth: 2, borderColor: '#fff' }] },
                options: { ...base, cutout: '68%' }
            });
            const elB = document.getElementById('chart-barras');
            if (elB) State.charts.barras = new Chart(elB, {
                type: 'bar',
                data: { labels, datasets: [{ label: 'Aprendices', data: values, backgroundColor: colores, borderRadius: 6 }] },
                options: { ...base, plugins: { ...base.plugins, legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { font: { family: 'Inter' } } } } }
            });
        },

        toast(msg, tipo = 'ok') {
            let t = document.getElementById('voc-toast');
            if (!t) return;
            t.textContent = msg;
            t.className = `voc-toast show ${tipo === 'ok' ? 'toast-ok' : 'toast-error'}`;
            setTimeout(() => t.className = 'voc-toast', 3000);
        }
    };

    // ── 4. TABLA de Aprendices ────────────────────────────────
    const Tabla = {
        render() {
            const busq = (document.getElementById('filtro-search')?.value || '').toLowerCase();
            const estadoFilt = (document.getElementById('filtro-estado')?.value || '').toUpperCase();

            const filtered = State.aprendices.filter(a => {
                const est = (a.estado || '').toUpperCase();
                const pasaEstado = estadoFilt === 'TODOS' || (!estadoFilt && !State.INACTIVOS.has(est)) || est === estadoFilt;
                const pasaBusq = !busq || [a.nombre, a.apellido, a.documento].some(v => (v || '').toLowerCase().includes(busq));
                return pasaEstado && pasaBusq;
            });

            const aviso = document.getElementById('aviso-filtro');
            if (aviso) aviso.classList.toggle('hidden', estadoFilt === 'TODOS' || !!estadoFilt);

            const contenedor = document.getElementById('voc-tabla-body');
            if (!filtered.length) {
                if (contenedor) contenedor.innerHTML = '<div class="voc-empty"><i class="fas fa-search"></i><p>Sin resultados para el filtro actual.</p></div>';
                document.getElementById('voc-paginacion').innerHTML = '';
                return;
            }

            const totalPag = Math.ceil(filtered.length / State.POR_PAG);
            const inicio = (State.paginaActual - 1) * State.POR_PAG;
            const slice = filtered.slice(inicio, inicio + State.POR_PAG);

            const POB_ICONS = {
                mujer: `<i class="fas fa-venus" title="Mujer" style="color:#ec4899"></i>`,
                indigena: `<i class="fas fa-leaf" title="Indígena" style="color:#16a34a"></i>`,
                narp: `<i class="fas fa-users" title="NARP" style="color:#92400e"></i>`,
                campesino: `<i class="fas fa-tractor" title="Campesino" style="color:#d97706"></i>`,
                lgbtiq: `<i class="fas fa-rainbow" title="lgbti" style="color:#8b5cf6"></i>`,
                discapacidad: `<i class="fas fa-wheelchair" title="Discapacidad" style="color:#3b82f6"></i>`
            };

            const rows = slice.map((a, i) => {
                const est = (a.estado || '').toUpperCase();
                const badgeClass = { LECTIVA: 'badge-lectiva', CANCELADO: 'badge-cancelado', RETIRADO: 'badge-retirado', APLAZADO: 'badge-aplazado', TRASLADO: 'badge-aplazado' }[est] || 'badge-default';
                const iconsPob = Object.keys(POB_ICONS).filter(k => a[k] == 1).map(k => POB_ICONS[k]).join(' ');
                return `<tr>
                    <td class="td-num">${inicio + i + 1}</td>
                    <td><strong>${a.documento}</strong></td>
                    <td>${a.nombre || ''} ${a.apellido || ''}</td>
                    <td><div class="voc-pob-cell">${iconsPob || '<span class="sin-dato">—</span>'}</div></td>
                    <td class="td-correo">${a.correo || '<span class="sin-dato">—</span>'}</td>
                    <td>${a.celular || '<span class="sin-dato">—</span>'}</td>
                    <td><span class="badge ${badgeClass}">${est}</span></td>
                    <td class="txt-center">
                        <button class="voc-btn-edit" onclick="VoceroDash.editarFila('${a.documento}')" title="Editar aprendiz">
                            <i class="fas fa-pencil-alt"></i>
                        </button>
                    </td>
                </tr>`;
            }).join('');

            if (contenedor) contenedor.innerHTML = `
                <div class="table-container-fixed">
                    <table class="voc-table">
                        <thead>
                            <tr>
                                <th>N°</th>
                                <th>Documento</th>
                                <th>Nombre del Aprendiz</th>
                                <th>Población</th>
                                <th>Correo</th>
                                <th>Celular</th>
                                <th>Estado</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>${rows}</tbody>
                    </table>
                </div>`;

            this.renderPaginacion(totalPag);
        },

        renderPaginacion(total) {
            const pg = document.getElementById('voc-paginacion');
            if (!pg) return;
            if (total <= 1) { pg.innerHTML = ''; return; }

            const cur = State.paginaActual;
            let h = '';

            // Boton primera pagina
            h += `<button class="voc-pag-btn ${cur === 1 ? 'disabled' : ''}" onclick="VoceroDash.irPagina(1)" title="Primera página" ${cur === 1 ? 'disabled' : ''}>&#171;</button>`;
            // Anterior
            h += `<button class="voc-pag-btn ${cur === 1 ? 'disabled' : ''}" onclick="VoceroDash.irPagina(${cur - 1})" ${cur === 1 ? 'disabled' : ''}>&#8249;</button>`;

            // Rango de paginas
            const desde = Math.max(1, cur - 2);
            const hasta = Math.min(total, cur + 2);
            if (desde > 1) h += `<span class="voc-pag-ellipsis">&hellip;</span>`;
            for (let p = desde; p <= hasta; p++) {
                h += `<button class="voc-pag-btn ${p === cur ? 'active' : ''}" onclick="VoceroDash.irPagina(${p})">${p}</button>`;
            }
            if (hasta < total) h += `<span class="voc-pag-ellipsis">&hellip;</span>`;

            // Siguiente
            h += `<button class="voc-pag-btn ${cur === total ? 'disabled' : ''}" onclick="VoceroDash.irPagina(${cur + 1})" ${cur === total ? 'disabled' : ''}>&#8250;</button>`;
            // Ultima
            h += `<button class="voc-pag-btn ${cur === total ? 'disabled' : ''}" onclick="VoceroDash.irPagina(${total})" title="Última página" ${cur === total ? 'disabled' : ''}>&#187;</button>`;

            // Indicador
            const filtered = State.aprendices.length;
            const ini = (cur - 1) * State.POR_PAG + 1;
            const fin = Math.min(cur * State.POR_PAG, filtered);
            h += `<span class="voc-pag-info">Pág. ${cur}/${total} &mdash; ${ini}-${fin} de ${filtered}</span>`;

            pg.innerHTML = h;
        }
    };

    // ── 5. MODAL con validaciones ─────────────────────────────
    const Modal = {
        POBLACIONES: ['mujer', 'indigena', 'narp', 'campesino', 'lgbtiq', 'discapacidad'],

        open(doc) {
            const a = State.aprendices.find(x => String(x.documento) === String(doc));
            if (!a) return;
            const m = document.getElementById('modalEditVocero');
            if (!m) return;
            document.getElementById('edit-doc-modal').value = a.documento;
            document.getElementById('edit-nombre-display').value = `${a.documento} - ${a.nombre} ${a.apellido}`;
            document.getElementById('edit-correo-modal').value = a.correo || '';
            document.getElementById('edit-cel-modal').value = a.celular || '';
            this.POBLACIONES.forEach(p => {
                const cb = document.getElementById(`edit-pob-${p}`);
                if (cb) cb.checked = a[p] == 1;
            });
            // Limpiar errores previos
            ['err-correo', 'err-celular'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.textContent = '';
            });
            m.style.display = 'flex';
        },

        validar() {
            let ok = true;
            const correo = document.getElementById('edit-correo-modal').value.trim();
            const celular = document.getElementById('edit-cel-modal').value.trim();
            const errCorreo = document.getElementById('err-correo');
            const errCelular = document.getElementById('err-celular');

            if (errCorreo) errCorreo.textContent = '';
            if (errCelular) errCelular.textContent = '';

            // Validar correo: si tiene valor debe contener @
            if (correo && !correo.includes('@')) {
                if (errCorreo) errCorreo.textContent = '⚠ El correo debe contener "@"';
                document.getElementById('edit-correo-modal')?.classList.add('input-error');
                ok = false;
            } else {
                document.getElementById('edit-correo-modal')?.classList.remove('input-error');
            }

            // Validar celular: solo números, máximo 10
            if (celular && (!/^\d+$/.test(celular) || celular.length > 10)) {
                if (errCelular) errCelular.textContent = '⚠ Solo números, máximo 10 dígitos';
                document.getElementById('edit-cel-modal')?.classList.add('input-error');
                ok = false;
            } else {
                document.getElementById('edit-cel-modal')?.classList.remove('input-error');
            }

            return ok;
        }
    };

    // ── 6. EXPORT PDF ─────────────────────────────────────────
    const Export = {
        async toPDF() {
            if (!State.aprendices.length) return alert('No hay datos para exportar');
            const { jsPDF } = window.jspdf;

            // A4 Horizontal (landscape)
            const doc  = new jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' });
            const pw   = doc.internal.pageSize.getWidth();    // 297mm
            const ph   = doc.internal.pageSize.getHeight();   // 210mm

            // ── Cabecera profesional SENA + SenApre ──────────────
            const subtitulo = `Informe de Aprendices | Ficha: ${State.vocFicha} | Vocero/a: ${State.vocNombre}`;

            // Usar el módulo compartido si está disponible, o pintar manualmente
            let startY = 56;
            if (typeof SenaPrePDF !== 'undefined') {
                startY = await SenaPrePDF.crearCabecera(doc, {
                    titulo:      'REGIONAL CAQUET\u00c1',
                    subtitulo:   subtitulo,
                    orientacion: 'landscape'
                });
            } else {
                // Fallback manual si el script no se cargó
                doc.setFillColor(0, 100, 0);
                doc.rect(0, 0, pw, 42, 'F');
                doc.setTextColor(255, 255, 255);
                doc.setFont('helvetica', 'bold');
                doc.setFontSize(13);
                doc.text('REPORTE DE APRENDICES', pw / 2, 19, { align: 'center' });
                doc.setFontSize(9);
                doc.setFont('helvetica', 'normal');
                doc.text(subtitulo, pw / 2, 27, { align: 'center' });
                doc.setFillColor(245, 248, 245);
                doc.rect(0, 42, pw, 10, 'F');
                const fechaStr = new Date().toLocaleDateString('es-CO', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
                doc.setFontSize(7.5);
                doc.setFont('helvetica', 'italic');
                doc.setTextColor(60, 60, 60);
                doc.text(`Fecha de generación: ${fechaStr}`, pw / 2, 48.5, { align: 'center' });
                startY = 56;
            }

            // ── Tabla centrada ─────────────────────────────────────
            // Filtrar solo aprendices en estado LECTIVA
            const soloLectiva = State.aprendices.filter(a =>
                (a.estado || '').toUpperCase() === 'LECTIVA'
            );

            if (!soloLectiva.length) {
                doc.save(`informe-vocero-ficha-${State.vocFicha}.pdf`);
                return;
            }

            // Columnas: N°=14, Doc=32, Nombres=40, Apellidos=42, Correo=60, Cel=28, Estado=26, Ficha=22 → total 264
            const colW = { n: 14, doc: 32, nom: 40, ape: 42, cor: 60, cel: 28, est: 26, fic: 22 };
            const totalCols = Object.values(colW).reduce((a, b) => a + b, 0); // 264mm
            const margen = Math.max(8, (pw - totalCols) / 2);

            doc.autoTable({
                startY,
                head: [['N\u00b0', 'Documento', 'Nombres', 'Apellidos', 'Correo Electr\u00f3nico', 'Celular', 'Estado', 'Ficha']],
                body: soloLectiva.map((a, i) => [
                    i + 1,
                    a.documento || '\u2014',
                    a.nombre    || '\u2014',
                    a.apellido  || '\u2014',
                    a.correo    || '\u2014',
                    a.celular   || '\u2014',
                    'LECTIVA',
                    State.vocFicha
                ]),
                theme: 'grid',
                styles: {
                    font: 'helvetica',
                    fontSize: 8,
                    cellPadding: { top: 2.5, right: 4, bottom: 2.5, left: 4 },
                    halign: 'center',
                    valign: 'middle',
                    lineColor: [210, 210, 210],
                    lineWidth: 0.3
                },
                headStyles: {
                    fillColor: [0, 100, 0],
                    textColor: [255, 255, 255],
                    fontStyle: 'bold',
                    fontSize: 8.5,
                    halign: 'center',
                    cellPadding: { top: 3.5, right: 4, bottom: 3.5, left: 4 }
                },
                alternateRowStyles: { fillColor: [245, 252, 240] },
                columnStyles: {
                    0: { cellWidth: colW.n,   halign: 'center' },
                    1: { cellWidth: colW.doc,  halign: 'center' },
                    2: { cellWidth: colW.nom,  halign: 'center' },
                    3: { cellWidth: colW.ape,  halign: 'center' },
                    4: { cellWidth: colW.cor,  halign: 'center' },
                    5: { cellWidth: colW.cel,  halign: 'center' },
                    6: { cellWidth: colW.est,  halign: 'center', fontStyle: 'bold', textColor: [22, 163, 74] },
                    7: { cellWidth: colW.fic,  halign: 'center' }
                },
                margin: { left: margen, right: margen },
                didDrawPage() {
                    if (typeof SenaPrePDF !== 'undefined') {
                        SenaPrePDF.pieDePagina(doc);
                    } else {
                        const pn = doc.internal.getCurrentPageInfo().pageNumber;
                        const pt = doc.internal.getNumberOfPages();
                        doc.setFontSize(7);
                        doc.setFont('helvetica', 'normal');
                        doc.setTextColor(140, 140, 140);
                        doc.setDrawColor(200, 200, 200);
                        doc.line(12, ph - 11, pw - 12, ph - 11);
                        doc.text('Generado por SenApre \u2014 SENA CTPI', 14, ph - 6);
                        doc.text(`P\u00e1gina ${pn} de ${pt}`, pw - 14, ph - 6, { align: 'right' });
                    }
                }
            });

            doc.save(`informe-vocero-ficha-${State.vocFicha}.pdf`);
        }
    };

    // ── 7. CARGA DE DATOS ─────────────────────────────────────
    async function cargarDatos() {
        try {
            const json = await API.fetchDashboard(State.idUsuario);
            if (!json.success) {
                const el = document.getElementById('voc-tabla-body');
                if (el) el.innerHTML = `<div class="voc-empty"><i class="fas fa-exclamation-triangle"></i><p>${json.message}</p></div>`;
                return;
            }
            const d = json.data;
            State.aprendices = d.aprendices;
            State.vocFicha = d.ficha.numero_ficha;
            State.vocTipo = d.tipo_vocero === 'principal' ? 'Principal' : 'Suplente';

            UI.renderHeader();
            UI.renderIndicadores(d.resumen, d.total);

            // Solo en el dashboard principal
            if (document.getElementById('tabla-estados-body')) UI.renderTablaEstados(d.resumen, d.total);
            if (document.getElementById('chart-dona')) UI.renderCharts(d.resumen);

            // Solo en la página de aprendices
            if (document.getElementById('voc-tabla-body')) Tabla.render();

        } catch (e) {
            const el = document.getElementById('voc-tabla-body');
            if (el) el.innerHTML = '<div class="voc-empty"><i class="fas fa-wifi"></i><p>Error de conexión. Recargue la página.</p></div>';
        }
    }

    function _getUser() {
        return (typeof authSystem !== 'undefined' && authSystem.getCurrentUser)
            ? authSystem.getCurrentUser()
            : JSON.parse(localStorage.getItem('user') || 'null');
    }

    function _setupAuth() {
        const user = _getUser();
        if (!user || (user.rol || '').toLowerCase() !== 'vocero') {
            location.href = 'index.html'; return false;
        }
        State.idUsuario = String(user.id_usuario || user.id || '').trim();
        State.vocNombre = `${user.nombre} ${user.apellido}`.trim();
        return true;
    }

    // ── 8. API PÚBLICA ────────────────────────────────────────
    return {
        /** Inicializar en vocero-dashboard.html */
        async initDashboard() {
            if (!_setupAuth()) return;
            await cargarDatos();
        },

        /** Inicializar en vocero-aprendices.html */
        async initAprendices() {
            if (!_setupAuth()) return;
            const el = document.getElementById('voc-tabla-body');
            if (el) el.innerHTML = '<div class="voc-loading"><i class="fas fa-spinner fa-spin"></i> Cargando aprendices...</div>';
            await cargarDatos();
        },

        irPagina(p) {
            State.paginaActual = p;
            Tabla.render();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        editarFila(doc) { Modal.open(doc); },

        renderTabla() { State.paginaActual = 1; Tabla.render(); },

        exportarPDF() { Export.toPDF(); },

        async guardarEdicion(e) {
            e.preventDefault();
            if (!Modal.validar()) return;

            const doc = document.getElementById('edit-doc-modal').value;
            const correo = document.getElementById('edit-correo-modal').value.trim();
            const celular = document.getElementById('edit-cel-modal').value.trim();
            const pob = {};
            Modal.POBLACIONES.forEach(p => {
                pob[p] = document.getElementById(`edit-pob-${p}`)?.checked ? 1 : 0;
            });

            const btn = document.getElementById('btn-save-modal');
            if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...'; }

            try {
                const res = await API.updateAprendiz({ documento: doc, numero_ficha: State.vocFicha, correo, celular, poblacion: pob });
                if (res.success) {
                    const a = State.aprendices.find(x => String(x.documento) === String(doc));
                    if (a) Object.assign(a, { correo, celular, ...pob });
                    Tabla.render();
                    document.getElementById('modalEditVocero').style.display = 'none';
                    UI.toast('✅ Guardado correctamente');
                } else {
                    UI.toast(`❌ ${res.message}`, 'error');
                }
            } catch {
                UI.toast('❌ Error de conexión', 'error');
            } finally {
                if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-save"></i> Guardar Cambios'; }
            }
        }
    };

})();
