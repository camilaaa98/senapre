/**
 * vocero-dashboard.js — v2.0.0 DEFINITIVO
 * Panel de Vocería — SenApre SENA
 * Usa api/vocero/dashboard.php directamente con id_usuario
 */
'use strict';

const VoceroDashboard = (() => {

    // Estado interno
    let vocFicha = '';
    let vocNombre = '';
    let vocTipo = '';
    let idUsuario = '';
    let aprendices = [];       // todos
    let paginaActual = 1;
    const POR_PAG = 20;

    const INACTIVOS = new Set(['CANCELADO', 'RETIRADO', 'APLAZADO', 'TRASLADO', 'FINALIZADO']);
    const COL = { LECTIVA: '#39A900', CANCELADO: '#dc2626', RETIRADO: '#f59e0b', APLAZADO: '#64748b', TRASLADO: '#94a3b8' };

    let chartDona = null, chartBar = null;

    // ────────────────────────────────────────────────────────────
    // INICIALIZACIÓN
    // ────────────────────────────────────────────────────────────
    async function init() {
        // 1. Leer usuario del localStorage
        let user = null;
        try {
            user = (typeof authSystem !== 'undefined' && authSystem.getCurrentUser)
                ? authSystem.getCurrentUser()
                : JSON.parse(localStorage.getItem('user') || 'null');
        } catch { }

        if (!user) { location.href = 'index.html'; return; }
        if ((user.rol || '').toLowerCase() !== 'vocero') {
            localStorage.removeItem('user');
            location.href = 'index.html';
            return;
        }

        idUsuario = String(user.id_usuario || user.id || '').trim();
        vocNombre = `${user.nombre || ''} ${user.apellido || ''}`.trim();

        $('voc-nombre-sidebar', vocNombre || 'Vocero');
        $('voc-tipo-label', 'Cargando ficha...');
        cargando('voc-tabla-body');

        // 2. Llamar al endpoint dedicado
        await cargarDatos();
    }

    // ────────────────────────────────────────────────────────────
    // CARGA DE DATOS DESDE ENDPOINT DEDICADO
    // ────────────────────────────────────────────────────────────
    async function cargarDatos() {
        if (!idUsuario) { err('voc-tabla-body', 'No se pudo identificar al usuario. Por favor cierre sesión y vuelva a ingresar.'); return; }

        try {
            const resp = await fetch(`api/vocero/dashboard.php?id_usuario=${encodeURIComponent(idUsuario)}`);
            if (!resp.ok) throw new Error(`HTTP ${resp.status}`);
            const json = await resp.json();

            if (!json.success) { err('voc-tabla-body', json.message || 'Error al cargar datos.'); return; }

            const d = json.data;
            aprendices = d.aprendices || [];
            vocFicha = d.ficha.numero_ficha;
            vocTipo = d.tipo_vocero === 'principal' ? 'Vocero/a Principal' : 'Vocero/a Suplente';

            // Actualizar UI de cabecera
            $('voc-ficha-sidebar', vocFicha);
            $('voc-ficha-header', vocFicha);
            $('voc-ficha-tabla', vocFicha);
            $('voc-tipo-label', `${vocTipo} — Ficha ${vocFicha}`);
            document.title = `Panel Vocería — Ficha ${vocFicha}`;

            // Renderizar
            renderIndicadores(d.resumen, d.total);
            renderTablaEstados(d.resumen, d.total);
            renderGraficas(d.resumen);
            paginaActual = 1;
            renderTabla();

        } catch (e) {
            console.error('VoceroDashboard error:', e);
            err('voc-tabla-body', `Error de conexión: ${e.message}. Recargue la página.`);
        }
    }

    // ────────────────────────────────────────────────────────────
    // INDICADORES (5 tarjetas)
    // ────────────────────────────────────────────────────────────
    function renderIndicadores(resumen, total) {
        $('st-total', total);
        $('st-lectiva', resumen['LECTIVA'] || 0);
        $('st-cancelado', resumen['CANCELADO'] || 0);
        $('st-retirado', resumen['RETIRADO'] || 0);
        // Mostrar conteo específico de Traslado en lugar de "otros" genérico
        $('st-traslado', resumen['TRASLADO'] || 0);
    }

    // ────────────────────────────────────────────────────────────
    // TABLA DE ESTADOS
    // ────────────────────────────────────────────────────────────
    function renderTablaEstados(resumen, total) {
        const orden = ['LECTIVA', 'CANCELADO', 'RETIRADO', 'APLAZADO', 'TRASLADO'];
        Object.keys(resumen).forEach(e => { if (!orden.includes(e)) orden.push(e); });

        let html = '';
        orden.forEach(est => {
            if (!resumen[est]) return;
            const cnt = resumen[est];
            const pct = ((cnt / total) * 100).toFixed(1);
            const dot = est.toLowerCase();
            html += `<tr>
                <td><span class="voc-estado-dot dot-${dot}"></span><strong>${est}</strong></td>
                <td>${cnt}</td><td>${pct}%</td>
            </tr>`;
        });
        html += `<tr>
            <td><span class="voc-estado-dot dot-total"></span><strong>TOTAL</strong></td>
            <td><strong>${total} aprendices</strong></td><td><strong>100%</strong></td>
        </tr>`;
        const el = document.getElementById('tabla-estados-body');
        if (el) el.innerHTML = html;
    }

    // ────────────────────────────────────────────────────────────
    // GRÁFICAS
    // ────────────────────────────────────────────────────────────
    function renderGraficas(resumen) {
        if (chartDona) { chartDona.destroy(); chartDona = null; }
        if (chartBar) { chartBar.destroy(); chartBar = null; }

        const labels = Object.keys(resumen);
        const values = labels.map(e => resumen[e]);
        const colores = labels.map(e => COL[e] || '#cbd5e1');

        const base = {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom', labels: { font: { size: 11 }, padding: 12 } } }
        };

        const elD = document.getElementById('chart-dona');
        if (elD) chartDona = new Chart(elD, {
            type: 'doughnut',
            data: { labels, datasets: [{ data: values, backgroundColor: colores, borderWidth: 2, borderColor: '#fff', hoverOffset: 8 }] },
            options: { ...base, cutout: '60%' }
        });

        const elB = document.getElementById('chart-barras');
        if (elB) chartBar = new Chart(elB, {
            type: 'bar',
            data: { labels, datasets: [{ label: 'Aprendices', data: values, backgroundColor: colores, borderRadius: 6, borderSkipped: false }] },
            options: {
                ...base,
                plugins: { ...base.plugins, legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: '#f1f5f9' } },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    // ────────────────────────────────────────────────────────────
    // TABLA DE APRENDICES CON EDICIÓN INLINE
    // ────────────────────────────────────────────────────────────
    function renderTabla() {
        const busq = (document.getElementById('filtro-search')?.value || '').toLowerCase();
        const estadoFilt = (document.getElementById('filtro-estado')?.value || '').toUpperCase();
        const verTodos = estadoFilt === 'TODOS';

        const lista = aprendices.filter(a => {
            const est = (a.estado || '').toUpperCase();
            const pasaEstado = verTodos
                || (!estadoFilt && !INACTIVOS.has(est))
                || (estadoFilt && estadoFilt !== 'TODOS' && est === estadoFilt);
            const pasaBusq = !busq
                || (a.nombre || '').toLowerCase().includes(busq)
                || (a.apellido || '').toLowerCase().includes(busq)
                || (a.documento || '').includes(busq);
            return pasaEstado && pasaBusq;
        });

        // Aviso de filtro activo
        const aviso = document.getElementById('aviso-filtro');
        if (aviso) {
            if (verTodos || estadoFilt) {
                aviso.classList.add('hidden');
            } else {
                aviso.classList.remove('hidden');
            }
        }

        if (!lista.length) {
            info('voc-tabla-body', 'Sin resultados con los filtros aplicados.');
            const p = document.getElementById('voc-paginacion');
            if (p) p.innerHTML = '';
            return;
        }

        const ini = (paginaActual - 1) * POR_PAG;
        const pagArr = lista.slice(ini, ini + POR_PAG);
        const total = Math.ceil(lista.length / POR_PAG);

        const filas = pagArr.map((a, i) => {
            const est = (a.estado || '').toUpperCase();
            const badge = ({ LECTIVA: 'badge-lectiva', CANCELADO: 'badge-cancelado', RETIRADO: 'badge-retirado', APLAZADO: 'badge-aplazado', TRASLADO: 'badge-aplazado' })[est] || 'badge-default';
            const doc = a.documento || '';

            return `<tr id="fila-${doc}" data-doc="${doc}" data-ficha="${vocFicha}">
                <td>${ini + i + 1}</td>
                <td><strong>${doc}</strong></td>
                <td>${a.nombre || ''} ${a.apellido || ''}</td>
                <td><span id="correo-txt-${doc}">${a.correo || '—'}</span></td>
                <td><span id="cel-txt-${doc}">${a.celular || '—'}</span></td>
                <td><span class="badge ${badge}">${a.estado || '—'}</span></td>
                <td>
                    <button class="voc-btn-edit-circ" onclick="VoceroDashboard.editarFila('${doc}')" title="Editar contacto">
                        <i class="fas fa-pencil-alt"></i>
                    </button>
                </td>
            </tr>`;
        }).join('');

        const el = document.getElementById('voc-tabla-body');
        if (el) el.innerHTML = `<table>
            <thead><tr>
                <th>N°</th><th>Documento</th><th>Nombre Completo</th>
                <th>Correo</th><th>Celular</th><th>Estado</th><th>Editar</th>
            </tr></thead>
            <tbody>${filas}</tbody>
        </table>`;

        const pg = document.getElementById('voc-paginacion');
        if (pg) pg.innerHTML = renderPag(total, paginaActual);
    }

    // ────────────────────────────────────────────────────────────
    // EDICIÓN EN MODAL
    // ────────────────────────────────────────────────────────────
    function editarFila(doc) {
        const a = aprendices.find(x => String(x.documento) === String(doc));
        if (!a) {
            console.error('Aprendiz no encontrado:', doc, aprendices);
            return;
        }
        
        // Crear modal si no existe en HTML
        let modal = document.getElementById('modalEditVocero');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'modalEditVocero';
            modal.className = 'modal-overlay';
            modal.style.zIndex = '999999';
            modal.style.position = 'fixed';
            modal.style.top = '0';
            modal.style.left = '0';
            modal.style.width = '100vw';
            modal.style.height = '100vh';
            modal.style.backgroundColor = 'rgba(0,0,0,0.6)';
            modal.style.display = 'flex';
            modal.style.justifyContent = 'center';
            modal.style.alignItems = 'center';
            
            modal.innerHTML = `
                <div class="modal-glass" style="max-width: 500px; width: 90%; padding: 2.5rem; background: white; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.2);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; border-bottom: 2px solid #f1f5f9; padding-bottom: 1rem;">
                        <h2 style="font-family: 'Outfit'; font-size: 1.5rem; color: #1e293b; margin: 0;">
                            <i class="fas fa-user-edit" style="color: #39A900; margin-right: 10px;"></i>Editar Contacto
                        </h2>
                        <button type="button" onclick="document.getElementById('modalEditVocero').style.display='none'" style="background: none; border: none; font-size: 1.5rem; color: #94a3b8; cursor: pointer;">&times;</button>
                    </div>
                    
                    <form id="formEditVocero" onsubmit="VoceroDashboard.guardarFormularioEdicion(event)">
                        <input type="hidden" id="edit-doc-modal">
                        <div style="margin-bottom: 15px;">
                            <label style="color: #64748b; font-size: 0.85rem; font-weight: 600; display:block; margin-bottom:5px;">Aprendiz</label>
                            <input type="text" id="edit-nombre-display" class="voc-input" readonly style="background-color: #f8fafc; color: #94a3b8; cursor: not-allowed; width:94%;">
                        </div>
                        <div style="margin-bottom: 15px;">
                            <label style="color: #1e293b; font-size: 0.85rem; font-weight: 600; display:block; margin-bottom:5px;">Correo Electrónico</label>
                            <input type="email" id="edit-correo-modal" class="voc-input" style="width:94%;">
                        </div>
                        <div style="margin-bottom: 25px;">
                            <label style="color: #1e293b; font-size: 0.85rem; font-weight: 600; display:block; margin-bottom:5px;">Celular / Teléfono</label>
                            <input type="text" id="edit-cel-modal" class="voc-input" style="width:94%;">
                        </div>
                        <div style="margin-bottom: 25px;">
                            <label style="color: #1e293b; font-size: 0.85rem; font-weight: 600; display:block; margin-bottom:10px;">Tipos de Población</label>
                            <div style="display:flex; flex-wrap:wrap; gap:10px;" id="edit-pob-container">
                                <label style="display:flex; align-items:center; gap:5px; font-size:0.85rem;"><input type="checkbox" id="edit-pob-mujer"> Mujer</label>
                                <label style="display:flex; align-items:center; gap:5px; font-size:0.85rem;"><input type="checkbox" id="edit-pob-indigena"> Indígena</label>
                                <label style="display:flex; align-items:center; gap:5px; font-size:0.85rem;"><input type="checkbox" id="edit-pob-narp"> NARP</label>
                                <label style="display:flex; align-items:center; gap:5px; font-size:0.85rem;"><input type="checkbox" id="edit-pob-campesino"> Campesino</label>
                                <label style="display:flex; align-items:center; gap:5px; font-size:0.85rem;"><input type="checkbox" id="edit-pob-lgbtiq"> LGBTIQ+</label>
                                <label style="display:flex; align-items:center; gap:5px; font-size:0.85rem;"><input type="checkbox" id="edit-pob-discapacidad"> Discapacidad</label>
                            </div>
                        </div>
                        <div style="display: flex; justify-content: flex-end; gap: 10px;">
                            <button type="button" onclick="document.getElementById('modalEditVocero').style.display='none'" class="voc-btn" style="background: white; border: 1px solid #cbd5e1; color: #475569;">Cancelar</button>
                            <button type="submit" class="voc-btn voc-btn-save" style="display:flex; align-items:center; gap:8px;" id="btn-save-modal">
                                <i class="fas fa-save"></i> Guardar Cambios
                            </button>
                        </div>
                    </form>
                </div>
            `;
            document.body.appendChild(modal);
        }

        document.getElementById('edit-doc-modal').value = a.documento;
        document.getElementById('edit-nombre-display').value = `${a.documento} - ${a.nombre || ''} ${a.apellido || ''}`;
        document.getElementById('edit-correo-modal').value = a.correo || '';
        document.getElementById('edit-cel-modal').value = a.celular || '';
        
        ['mujer', 'indigena', 'narp', 'campesino', 'lgbtiq', 'discapacidad'].forEach(pob => {
            document.getElementById(`edit-pob-${pob}`).checked = a[pob] == 1;
        });
        
        modal.style.display = 'flex';
    }

    async function guardarFormularioEdicion(e) {
        e.preventDefault();
        const doc = document.getElementById('edit-doc-modal').value;
        const correo = document.getElementById('edit-correo-modal').value.trim();
        const celular = document.getElementById('edit-cel-modal').value.trim();
        
        const poblacionData = {};
        ['mujer', 'indigena', 'narp', 'campesino', 'lgbtiq', 'discapacidad'].forEach(pob => {
            poblacionData[pob] = document.getElementById(`edit-pob-${pob}`).checked ? 1 : 0;
        });

        const btn = document.getElementById('btn-save-modal');
        if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...'; }

        try {
            const resp = await fetch('api/vocero/dashboard.php', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ documento: doc, numero_ficha: vocFicha, correo, celular, poblacion: poblacionData })
            });
            const json = await resp.json();

            if (json.success) {
                // Actualizar datos locales
                const a = aprendices.find(x => String(x.documento) === String(doc));
                if (a) { 
                    if (correo !== undefined) a.correo = correo; 
                    if (celular !== undefined) a.celular = celular;
                    Object.assign(a, poblacionData);
                }

                // Actualizar spans en pantalla (solo si usan el viejo ID)
                const cT = document.getElementById(`correo-txt-${doc}`);
                const cB = document.getElementById(`cel-txt-${doc}`);
                if (cT) cT.textContent = correo || '—';
                if (cB) cB.textContent = celular || '—';

                document.getElementById('modalEditVocero').style.display = 'none';
                _toast('✅ Información actualizada correctamente');
                
                // Refrescar para ver los checkboxes de poblacion en la tabla
                renderTabla();
            } else {
                _toast('❌ ' + (json.message || 'Error al guardar'), 'error');
            }
        } catch {
            _toast('❌ Error de conexión. Intente de nuevo.', 'error');
        } finally {
            if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-save"></i> Guardar Cambios'; }
        }
    }



    // ────────────────────────────────────────────────────────────
    // EXPORTAR PDF
    // ────────────────────────────────────────────────────────────
    function exportarPDF() {
        if (!aprendices.length) { alert('No hay aprendices para exportar.'); return; }
        if (!window.jspdf) { alert('Librería PDF no disponible.'); return; }

        const { jsPDF } = window.jspdf;
        const doc = new jsPDF('p', 'mm', 'a4');
        const pw = doc.internal.pageSize.getWidth();
        const hoy = new Date().toLocaleDateString('es-CO', { year: 'numeric', month: 'long', day: 'numeric' });

        doc.setFillColor(0, 50, 77); doc.rect(0, 0, pw, 38, 'F');
        doc.setTextColor(255, 255, 255);
        doc.setFontSize(13); doc.setFont('helvetica', 'bold');
        doc.text('SENA — Centro de Teleinformática y Producción Industrial', pw / 2, 12, { align: 'center' });
        doc.setFontSize(11);
        doc.text(`Informe Oficial de Aprendices — Ficha ${vocFicha}`, pw / 2, 21, { align: 'center' });
        doc.setFontSize(8.5); doc.setFont('helvetica', 'normal');
        doc.text(`${vocTipo}: ${vocNombre}   |   Fecha: ${hoy}`, pw / 2, 30, { align: 'center' });

        // Resumen
        const res = _resumen();
        const total = aprendices.length;
        let y = 46;
        doc.setDrawColor(200); doc.setFillColor(248, 250, 252);
        doc.roundedRect(14, y, pw - 28, 8 + Object.keys(res).length * 7 + 7, 3, 3, 'FD');
        doc.setFont('helvetica', 'bold'); doc.setTextColor(0, 50, 77); doc.setFontSize(9);
        doc.text('RESUMEN POR ESTADO', 20, y + 6); y += 12;
        doc.setFont('helvetica', 'normal'); doc.setTextColor(55, 65, 81); doc.setFontSize(8.5);
        Object.entries(res).forEach(([e, c]) => {
            doc.text(`${e}:`, 22, y); doc.text(`${c}  (${((c / total) * 100).toFixed(1)}%)`, 80, y); y += 7;
        });
        doc.setFont('helvetica', 'bold'); doc.setTextColor(0, 50, 77);
        doc.text('TOTAL:', 22, y + 1); doc.text(`${total} aprendices`, 80, y + 1); y += 14;

        const activos = aprendices.filter(a => !INACTIVOS.has((a.estado || '').toUpperCase()));
        doc.autoTable({
            startY: y,
            head: [['#', 'Documento', 'Nombre', 'Correo', 'Celular', 'Estado']],
            body: activos.map((a, i) => [i + 1, a.documento || '—', `${a.nombre || ''} ${a.apellido || ''}`.trim(), a.correo || '—', a.celular || '—', a.estado || '—']),
            headStyles: { fillColor: [0, 50, 77], textColor: 255, fontStyle: 'bold', fontSize: 8.5 },
            bodyStyles: { fontSize: 8 },
            alternateRowStyles: { fillColor: [246, 248, 252] },
            columnStyles: { 0: { cellWidth: 8 }, 1: { cellWidth: 25 }, 2: { cellWidth: 52 }, 3: { cellWidth: 48 }, 4: { cellWidth: 22 }, 5: { cellWidth: 22 } }
        });

        const pages = doc.internal.getNumberOfPages();
        for (let p = 1; p <= pages; p++) {
            doc.setPage(p); doc.setFontSize(7.5); doc.setTextColor(150);
            doc.text(`Página ${p} de ${pages} — SenApre`, pw / 2, 290, { align: 'center' });
        }
        doc.save(`informe-ficha-${vocFicha}.pdf`);
    }

    // ────────────────────────────────────────────────────────────
    // UTILIDADES PRIVADAS
    // ────────────────────────────────────────────────────────────
    function _resumen() {
        return aprendices.reduce((a, c) => {
            const e = (c.estado || 'SIN ESTADO').toUpperCase();
            a[e] = (a[e] || 0) + 1; return a;
        }, {});
    }
    function $(id, val) { const e = document.getElementById(id); if (e) e.textContent = val; }
    function cargando(id) { const e = document.getElementById(id); if (e) e.innerHTML = '<div class="voc-loading"><i class="fas fa-spinner"></i> Cargando datos...</div>'; }
    function info(id, msg) { const e = document.getElementById(id); if (e) e.innerHTML = `<div class="voc-loading"><i class="fas fa-inbox"></i> ${msg}</div>`; }
    function err(id, msg) { const e = document.getElementById(id); if (e) e.innerHTML = `<div class="voc-loading"><i class="fas fa-exclamation-triangle color-error"></i> ${msg}</div>`; }
    function renderPag(total, actual) {
        if (total <= 1) return '';
        let h = '';
        if (actual > 1) h += `<button class="voc-pag-btn" onclick="VoceroDashboard.irPagina(${actual - 1})">‹ Ant.</button>`;
        for (let p = Math.max(1, actual - 2); p <= Math.min(total, actual + 2); p++)
            h += `<button class="voc-pag-btn ${p === actual ? 'active' : ''}" onclick="VoceroDashboard.irPagina(${p})">${p}</button>`;
        if (actual < total) h += `<button class="voc-pag-btn" onclick="VoceroDashboard.irPagina(${actual + 1})">Sig. ›</button>`;
        return h;
    }
    function _toast(msg, tipo = 'ok') {
        let t = document.getElementById('voc-toast');
        if (!t) {
            t = document.createElement('div');
            t.id = 'voc-toast';
            document.body.appendChild(t);
        }
        t.textContent = msg;
        t.className = ''; // Limpiar clases
        t.style.background = ''; // Limpiar estilos heredados si existen
        t.style.color = '';
        t.style.opacity = '';

        t.classList.add('show');
        if (tipo !== 'ok') t.classList.add('bg-error'); // Asumo bg-error en admin.css
        else t.classList.add('bg-success');

        clearTimeout(t._timer);
        t._timer = setTimeout(() => {
            t.classList.remove('show');
        }, 3000);
    }

    async function togglePoblacion(documento, campo, valor) {
        try {
            const action = valor ? 'POST' : 'DELETE';
            const url = action === 'POST' ? 'api/poblacion.php' : `api/poblacion.php?documento=${documento}&poblacion=${campo}`;

            const options = {
                method: action,
                headers: { 'Content-Type': 'application/json' }
            };

            if (action === 'POST') {
                options.body = JSON.stringify({ documento, poblacion: campo });
            }

            const res = await fetch(url, options);
            const result = await res.json();

            if (result.success) {
                _toast(`✅ Categoría ${campo.toUpperCase()} actualizada`);
                // Actualizar el objeto local
                const a = aprendices.find(x => x.documento === documento);
                if (a) a[campo] = valor ? 1 : 0;
            } else {
                _toast('❌ ' + (result.message || 'Error'), 'error');
            }
        } catch (e) {
            console.error(e);
            _toast('❌ Error de conexión', 'error');
        }
    }

    // ────────────────────────────────────────────────────────────
    // PAGINACIÓN
    // ────────────────────────────────────────────────────────────
    function irPagina(p) {
        paginaActual = p;
        renderTabla();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // ── API PÚBLICA ────────────────────────────────────────────
    return { init, cargarDatos, renderTabla, irPagina, exportarPDF, editarFila, guardarFormularioEdicion, guardarFila, cancelarEdicion, togglePoblacion };

})();

// Puente HTML → módulo
function filtrarTabla() { VoceroDashboard.renderTabla(); }
function exportarPDF() { VoceroDashboard.exportarPDF(); }
function irA(id) { document.getElementById(id)?.scrollIntoView({ behavior: 'smooth' }); }
function cerrarSesion(e) { e.preventDefault(); localStorage.removeItem('user'); location.href = 'index.html'; }

document.addEventListener('DOMContentLoaded', () => VoceroDashboard.init());
