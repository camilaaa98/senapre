/**
 * vocero-dashboard.js — v2.1.0 SOLID + Responsive
 * SENAPRE — Sistema de Asistencias SENA
 */
'use strict';

const VoceroDashboard = (() => {

    // --- 1. STATE MANAGEMENT ---
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

    // --- 2. API SERVICE ---
    const API = {
        async fetchDashboard(id) {
            const resp = await fetch(`api/vocero/dashboard.php?id_usuario=${encodeURIComponent(id)}`);
            if (!resp.ok) throw new Error(`HTTP ${resp.status}`);
            return await resp.json();
        },
        async updateAprendiz(data) {
            const resp = await fetch('api/vocero/dashboard.php', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            return await resp.json();
        }
    };

    // --- 3. UI RENDERER ---
    const UI = {
        $(id, val) { const e = document.getElementById(id); if (e) e.textContent = val; },
        
        showLoading(id) {
            const e = document.getElementById(id);
            if (e) e.innerHTML = '<div class="voc-loading"><i class="fas fa-spinner fa-spin"></i> Cargando datos...</div>';
        },

        showError(id, msg) {
            const e = document.getElementById(id);
            if (e) e.innerHTML = `<div class="voc-loading"><i class="fas fa-exclamation-triangle color-error"></i> ${msg}</div>`;
        },

        renderHeader() {
            this.$('voc-nombre-sidebar', State.vocNombre || 'Vocero');
            this.$('voc-ficha-sidebar', State.vocFicha);
            this.$('voc-ficha-header', State.vocFicha);
            this.$('voc-ficha-tabla', State.vocFicha);
            this.$('voc-tipo-label', `${State.vocTipo} — Ficha ${State.vocFicha}`);
            document.title = `Panel Vocería — Ficha ${State.vocFicha}`;
        },

        renderIndicadores(resumen, total) {
            this.$('st-total', total);
            this.$('st-lectiva', resumen['LECTIVA'] || 0);
            this.$('st-cancelado', resumen['CANCELADO'] || 0);
            this.$('st-retirado', resumen['RETIRADO'] || 0);
            this.$('st-traslado', resumen['TRASLADO'] || 0);
        },

        renderTablaEstados(resumen, total) {
            const orden = ['LECTIVA', 'CANCELADO', 'RETIRADO', 'APLAZADO', 'TRASLADO'];
            Object.keys(resumen).forEach(e => { if (!orden.includes(e)) orden.push(e); });

            let html = orden.filter(est => resumen[est]).map(est => {
                const cnt = resumen[est];
                const pct = ((cnt / total) * 100).toFixed(1);
                return `<tr>
                    <td><span class="voc-estado-dot dot-${est.toLowerCase()}"></span><strong>${est}</strong></td>
                    <td>${cnt}</td><td>${pct}%</td>
                </tr>`;
            }).join('');

            html += `<tr>
                <td><span class="voc-estado-dot dot-total"></span><strong>TOTAL</strong></td>
                <td><strong>${total} aprendices</strong></td><td><strong>100%</strong></td>
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
                plugins: { legend: { position: 'bottom', labels: { font: { size: 10 }, padding: 10 } } }
            };

            const elD = document.getElementById('chart-dona');
            if (elD) State.charts.dona = new Chart(elD, {
                type: 'doughnut',
                data: { labels, datasets: [{ data: values, backgroundColor: colores, borderWidth: 2, borderColor: '#fff' }] },
                options: { ...base, cutout: '70%' }
            });

            const elB = document.getElementById('chart-barras');
            if (elB) State.charts.barras = new Chart(elB, {
                type: 'bar',
                data: { labels, datasets: [{ label: 'Aprendices', data: values, backgroundColor: colores, borderRadius: 5 }] },
                options: { ...base, plugins: { ...base.plugins, legend: { display: false } }, scales: { y: { beginAtZero: true } } }
            });
        },

        renderMainTable() {
            const busq = (document.getElementById('filtro-search')?.value || '').toLowerCase();
            const estadoFilt = (document.getElementById('filtro-estado')?.value || '').toUpperCase();
            
            const filtered = State.aprendices.filter(a => {
                const est = (a.estado || '').toUpperCase();
                const pasaEstado = estadoFilt === 'TODOS' || (!estadoFilt && !State.INACTIVOS.has(est)) || (est === estadoFilt);
                const pasaBusq = !busq || [a.nombre, a.apellido, a.documento].some(v => (v || '').toLowerCase().includes(busq));
                return pasaEstado && pasaBusq;
            });

            const aviso = document.getElementById('aviso-filtro');
            if (aviso) {
                if (estadoFilt === 'TODOS' || estadoFilt) aviso.classList.add('hidden');
                else aviso.classList.remove('hidden');
            }

            if (!filtered.length) {
                document.getElementById('voc-tabla-body').innerHTML = '<div class="voc-loading">Sin resultados</div>';
                document.getElementById('voc-paginacion').innerHTML = '';
                return;
            }

            const totalPag = Math.ceil(filtered.length / State.POR_PAG);
            const inicio = (State.paginaActual - 1) * State.POR_PAG;
            const slice = filtered.slice(inicio, inicio + State.POR_PAG);

            const rows = slice.map((a, i) => {
                const est = (a.estado || '').toUpperCase();
                const badge = ({ LECTIVA: 'badge-lectiva', CANCELADO: 'badge-cancelado', RETIRADO: 'badge-retirado', APLAZADO: 'badge-aplazado', TRASLADO: 'badge-aplazado' })[est] || 'badge-default';
                
                // Generar iconos de población
                const pobIcons = [];
                if (a.mujer == 1) pobIcons.push('<i class="fas fa-venus" title="Mujer" style="color:#ec4899"></i>');
                if (a.indigena == 1) pobIcons.push('<i class="fas fa-leaf" title="Indígena" style="color:#16a34a"></i>');
                if (a.narp == 1) pobIcons.push('<i class="fas fa-users" title="NARP" style="color:#92400e"></i>');
                if (a.campesino == 1) pobIcons.push('<i class="fas fa-tractor" title="Campesino" style="color:#d97706"></i>');
                if (a.lgbtiq == 1) pobIcons.push('<i class="fas fa-rainbow" title="LGBTIQ+" style="color:#8b5cf6"></i>');
                if (a.discapacidad == 1) pobIcons.push('<i class="fas fa-wheelchair" title="Discapacidad" style="color:#3b82f6"></i>');

                return `<tr>
                    <td>${inicio + i + 1}</td>
                    <td><strong>${a.documento}</strong></td>
                    <td>${a.nombre} ${a.apellido}</td>
                    <td><div class="voc-pob-cell">${pobIcons.join(' ') || '<span style="color:#cbd5e1">—</span>'}</div></td>
                    <td><span id="correo-txt-${a.documento}">${a.correo || '—'}</span></td>
                    <td><span id="cel-txt-${a.documento}">${a.celular || '—'}</span></td>
                    <td><span class="badge ${badge}">${est}</span></td>
                    <td class="txt-center">
                        <button class="voc-btn-edit-circ" onclick="VoceroDashboard.editarFila('${a.documento}')" title="Editar">
                            <i class="fas fa-pencil-alt"></i>
                        </button>
                    </td>
                </tr>`;
            }).join('');

            document.getElementById('voc-tabla-body').innerHTML = `
                <div class="table-container-fixed">
                    <table class="voc-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Documento</th>
                                <th>Aprendiz</th>
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
            
            this.renderPagination(totalPag);
        },

        renderPagination(total) {
            const pg = document.getElementById('voc-paginacion');
            if (!pg || total <= 1) { if(pg) pg.innerHTML = ''; return; }
            
            let h = State.paginaActual > 1 ? `<button class="voc-pag-btn" onclick="VoceroDashboard.irPagina(${State.paginaActual-1})">‹ Ant.</button>` : '';
            for (let p = Math.max(1, State.paginaActual-2); p <= Math.min(total, State.paginaActual+2); p++) {
                h += `<button class="voc-pag-btn ${p === State.paginaActual ? 'active' : ''}" onclick="VoceroDashboard.irPagina(${p})">${p}</button>`;
            }
            if (State.paginaActual < total) h += `<button class="voc-pag-btn" onclick="VoceroDashboard.irPagina(${State.paginaActual+1})">Sig. ›</button>`;
            pg.innerHTML = h;
        },

        toast(msg, tipo = 'ok') {
            let t = document.getElementById('voc-toast') || document.createElement('div');
            t.id = 'voc-toast'; if(!t.parentNode) document.body.appendChild(t);
            t.textContent = msg;
            t.className = `show ${tipo === 'ok' ? 'bg-success' : 'bg-error'}`;
            setTimeout(() => t.classList.remove('show'), 3000);
        }
    };

    // --- 4. MODAL CONTROLLER ---
    const Modal = {
        getOrCreate() {
            let m = document.getElementById('modalEditVocero');
            if (m) return m;

            m = document.createElement('div');
            m.id = 'modalEditVocero';
            m.className = 'modal-overlay';
            m.style = 'z-index:999999; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.6); display:none; justify-content:center; align-items:center;';
            m.innerHTML = `
                <div class="modal-glass" style="max-width:500px; width:90%; padding:2rem; background:white; border-radius:12px; max-height:90vh; overflow-y:auto; box-shadow:0 20px 50px rgba(0,0,0,0.3);">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; border-bottom:2px solid #f1f5f9; padding-bottom:1rem;">
                        <h2 style="font-family:'Outfit'; font-size:1.4rem; margin:0; color:#1e293b;"><i class="fas fa-user-edit" style="color:#39A900;"></i> Editar Aprendiz</h2>
                        <button onclick="document.getElementById('modalEditVocero').style.display='none'" style="background:none; border:none; font-size:1.5rem; color:#94a3b8; cursor:pointer;">&times;</button>
                    </div>
                    <form id="formEditVocero" onsubmit="VoceroDashboard.guardarEdicion(event)">
                        <input type="hidden" id="edit-doc-modal">
                        <div style="margin-bottom:15px;">
                            <label style="display:block; font-size:0.8rem; font-weight:600; color:#64748b; margin-bottom:5px;">Aprendiz</label>
                            <input type="text" id="edit-nombre-display" class="voc-input" readonly style="background:#f8fafc; cursor:not-allowed;">
                        </div>
                        <div style="margin-bottom:15px;">
                            <label style="display:block; font-size:0.8rem; font-weight:600; color:#1e293b; margin-bottom:5px;">Correo Electrónico</label>
                            <input type="email" id="edit-correo-modal" class="voc-input">
                        </div>
                        <div style="margin-bottom:15px;">
                            <label style="display:block; font-size:0.8rem; font-weight:600; color:#1e293b; margin-bottom:5px;">Celular / Teléfono</label>
                            <input type="text" id="edit-cel-modal" class="voc-input">
                        </div>
                        <div style="margin-bottom:20px;">
                            <label style="display:block; font-size:0.8rem; font-weight:600; color:#1e293b; margin-bottom:10px;">Población Diferencial</label>
                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px;" id="edit-pob-container">
                                ${['mujer','indigena','narp','campesino','lgbtiq','discapacidad'].map(p => 
                                    `<label style="display:flex; align-items:center; gap:8px; font-size:0.85rem; color:#475569;"><input type="checkbox" id="edit-pob-${p}"> ${p.charAt(0).toUpperCase() + p.slice(1)}</label>`
                                ).join('')}
                            </div>
                        </div>
                        <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:25px; border-top:1px solid #f1f5f9; padding-top:20px;">
                            <button type="button" onclick="document.getElementById('modalEditVocero').style.display='none'" class="voc-btn" style="background:#fff; border:1px solid #cbd5e1; color:#475569;">Cancelar</button>
                            <button type="submit" class="voc-btn-save" id="btn-save-modal" style="display:flex; align-items:center; gap:8px; background:#39A900; color:#fff; border:none; padding:10px 20px; border-radius:8px; cursor:pointer; font-weight:600;">
                                <i class="fas fa-save"></i> Guardar Cambios
                            </button>
                        </div>
                    </form>
                </div>`;
            document.body.appendChild(m);
            return m;
        },

        open(doc) {
            const a = State.aprendices.find(x => String(x.documento) === String(doc));
            if (!a) return;
            const m = this.getOrCreate();
            document.getElementById('edit-doc-modal').value = a.documento;
            document.getElementById('edit-nombre-display').value = `${a.documento} - ${a.nombre} ${a.apellido}`;
            document.getElementById('edit-correo-modal').value = a.correo || '';
            document.getElementById('edit-cel-modal').value = a.celular || '';
            ['mujer','indigena','narp','campesino','lgbtiq','discapacidad'].forEach(p => {
                document.getElementById(`edit-pob-${p}`).checked = a[p] == 1;
            });
            m.style.display = 'flex';
        }
    };

    // --- 5. EXPORT SERVICE ---
    const Export = {
        async toPDF() {
            if (!State.aprendices.length) return alert('No hay datos');
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF('p', 'mm', 'a4');
            const pw = doc.internal.pageSize.getWidth();
            
            // Header
            doc.setFillColor(0, 50, 77); doc.rect(0, 0, pw, 35, 'F');
            doc.setTextColor(255, 255, 255); doc.setFontSize(14); doc.setFont('helvetica', 'bold');
            doc.text('SENA — Centro de Teleinformática y Producción Industrial', pw/2, 12, { align: 'center' });
            doc.setFontSize(11); doc.setFont('helvetica', 'normal');
            doc.text(`Informe Vocería — Ficha ${State.vocFicha}`, pw/2, 21, { align: 'center' });
            doc.setFontSize(8); doc.text(`Vocero: ${State.vocNombre} | Fecha: ${new Date().toLocaleDateString()}`, pw/2, 28, { align: 'center' });

            const activos = State.aprendices.filter(a => !State.INACTIVOS.has((a.estado||'').toUpperCase()));
            doc.autoTable({
                startY: 40,
                head: [['#', 'Documento', 'Nombre', 'Correo', 'Celular', 'Estado']],
                body: activos.map((a, i) => [i + 1, a.documento, `${a.nombre} ${a.apellido}`, a.correo||'—', a.celular||'—', a.estado]),
                theme: 'striped', headStyles: { fillColor: [0, 50, 77] }
            });
            doc.save(`informe-ficha-${State.vocFicha}.pdf`);
        }
    };

    // --- 6. PUBLIC CONTROLLER ---
    async function init() {
        const user = JSON.parse(localStorage.getItem('user') || 'null');
        if (!user || user.rol?.toLowerCase() !== 'vocero') { location.href = 'index.html'; return; }
        
        State.idUsuario = String(user.id_usuario || user.id || '').trim();
        State.vocNombre = `${user.nombre} ${user.apellido}`.trim();
        
        UI.showLoading('voc-tabla-body');
        await cargarDatos();
    }

    async function cargarDatos() {
        try {
            const json = await API.fetchDashboard(State.idUsuario);
            if (!json.success) { UI.showError('voc-tabla-body', json.message); return; }
            
            const d = json.data;
            State.aprendices = d.aprendices;
            State.vocFicha = d.ficha.numero_ficha;
            State.vocTipo = d.tipo_vocero === 'principal' ? 'Principal' : 'Suplente';
            
            UI.renderHeader();
            UI.renderIndicadores(d.resumen, d.total);
            UI.renderTablaEstados(d.resumen, d.total);
            UI.renderCharts(d.resumen);
            UI.renderMainTable();
        } catch (e) { UI.showError('voc-tabla-body', 'Error de conexión'); }
    }

    return { 
        init, 
        irPagina: (p) => { State.paginaActual = p; UI.renderMainTable(); window.scrollTo(0,0); },
        editarFila: (doc) => Modal.open(doc),
        guardarEdicion: async (e) => {
            e.preventDefault();
            const doc = document.getElementById('edit-doc-modal').value;
            const correo = document.getElementById('edit-correo-modal').value.trim();
            const celular = document.getElementById('edit-cel-modal').value.trim();
            const pob = {};
            ['mujer','indigena','narp','campesino','lgbtiq','discapacidad'].forEach(p => {
                pob[p] = document.getElementById(`edit-pob-${p}`).checked ? 1 : 0;
            });
            const btn = document.getElementById('btn-save-modal');
            btn.disabled = true;

            try {
                const res = await API.updateAprendiz({ documento: doc, numero_ficha: State.vocFicha, correo, celular, poblacion: pob });
                if (res.success) {
                    const a = State.aprendices.find(x => String(x.documento) === String(doc));
                    if (a) Object.assign(a, { correo, celular, ...pob });
                    UI.renderMainTable();
                    document.getElementById('modalEditVocero').style.display = 'none';
                    UI.toast('✅ Actualizado');
                } else UI.toast(res.message, 'error');
            } catch { UI.toast('Error', 'error'); } finally { btn.disabled = false; }
        },
        renderTabla: () => { State.paginaActual = 1; UI.renderMainTable(); },
        exportarPDF: () => Export.toPDF()
    };

})();

// Bridge functions
function filtrarTabla() { VoceroDashboard.renderTabla(); }
function exportarPDF() { VoceroDashboard.exportarPDF(); }
function irA(id) { document.getElementById(id)?.scrollIntoView({ behavior: 'smooth' }); }
function cerrarSesion(e) { e.preventDefault(); localStorage.removeItem('user'); location.href = 'index.html'; }

document.addEventListener('DOMContentLoaded', () => VoceroDashboard.init());
