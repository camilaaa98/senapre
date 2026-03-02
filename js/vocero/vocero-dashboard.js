/**
 * vocero-dashboard.js — v1.2.0
 * Panel Profesional de Vocería con Gráficas e Informe de Estados
 * SENA SenApre — Principios SOLID aplicados
 */

'use strict';

const VoceroDashboard = (() => {
    // ── Estado del módulo ──────────────────────────────────────
    let vocFicha = '';
    let vocNombre = '';
    let vocTipo = '';
    let todosAprendices = [];
    let paginaActual = 1;
    const POR_PAGINA = 20;

    const ESTADOS_INACTIVOS = new Set(['CANCELADO', 'RETIRADO', 'APLAZADO', 'TRASLADO', 'FINALIZADO']);

    const COLORES_ESTADO = {
        LECTIVA: '#39A900',
        CANCELADO: '#dc2626',
        RETIRADO: '#f59e0b',
        APLAZADO: '#64748b',
        TRASLADO: '#94a3b8',
    };
    const COLOR_DEFECTO = '#cbd5e1';

    let chartDona = null;
    let chartBarras = null;

    // ── Inicialización (async — robusta) ───────────────────────
    async function init() {
        const user = _obtenerUsuario();
        if (!user) { window.location.href = 'index.html'; return; }
        if ((user.rol || '').toLowerCase() !== 'vocero') {
            localStorage.removeItem('user');
            window.location.href = 'index.html';
            return;
        }

        vocNombre = (`${user.nombre || ''} ${user.apellido || ''}`).trim();
        _setText('voc-nombre-sidebar', vocNombre || 'Vocero');

        // ─ Intentar obtener ficha desde los scopes guardados ──
        const scopes = user.vocero_scopes || (user.vocero_scope ? [user.vocero_scope] : []);
        const scope = scopes.find(s => s.tipo === 'principal' || s.tipo === 'suplente');

        if (scope && scope.ficha) {
            vocFicha = scope.ficha;
            vocTipo = scope.tipo === 'principal' ? 'Vocero/a Principal' : 'Vocero/a Suplente';
        } else {
            // ─ Fallback: Sesión vieja — renovar buscando la ficha via API de auth ─
            try {
                const idUsuario = user.id_usuario || user.id || '';
                const resp = await fetch(`api/fichas.php?vocero=${encodeURIComponent(idUsuario)}`);
                const data = await resp.json();

                if (data.success && data.data && data.data.length > 0) {
                    vocFicha = data.data[0].numero_ficha;
                    vocTipo = 'Vocero/a';
                    // Actualizar localStorage para próximas sesiones
                    user.vocero_scopes = [{ tipo: 'principal', ficha: vocFicha }];
                    localStorage.setItem('user', JSON.stringify(user));
                } else {
                    // No se encontró ficha asignada
                    _mostrarError('voc-tabla-body',
                        'No tiene una ficha asignada. Contacte al administrador del sistema.');
                    _resetIndicadores();
                    return;
                }
            } catch (e) {
                _mostrarError('voc-tabla-body', 'Error al obtener la ficha. Recargue la página.');
                _resetIndicadores();
                return;
            }
        }

        vocTipo = vocTipo || 'Vocero/a';
        _setText('voc-ficha-sidebar', vocFicha);
        _setText('voc-ficha-header', vocFicha);
        _setText('voc-ficha-tabla', vocFicha);
        _setText('voc-tipo-label', `${vocTipo} — Ficha ${vocFicha}`);
        document.title = `Panel Vocería — Ficha ${vocFicha}`;

        cargarAprendices();
    }

    // ── Carga de aprendices ────────────────────────────────────
    async function cargarAprendices() {
        _mostrarCargando('voc-tabla-body');
        try {
            const resp = await fetch(`api/aprendices.php?ficha=${encodeURIComponent(vocFicha)}&limit=500&page=1`);
            const data = await resp.json();

            if (data.success && data.data && data.data.length > 0) {
                todosAprendices = data.data;
                _actualizarIndicadores();
                _renderTablaEstados();
                _renderGraficas();
                paginaActual = 1;
                renderizarTabla();
            } else {
                _mostrarInfo('voc-tabla-body', 'No se encontraron aprendices para esta ficha.');
                _resetIndicadores();
                _vaciarTablaEstados();
            }
        } catch (err) {
            console.error('Error al cargar aprendices:', err);
            _mostrarError('voc-tabla-body', 'Error al cargar datos. Recargue la página.');
        }
    }

    // ── Indicadores de Resumen ─────────────────────────────────
    function _actualizarIndicadores() {
        const por = _agruparPorEstado();
        const total = todosAprendices.length;
        _setText('st-total', total);
        _setText('st-lectiva', por['LECTIVA'] || 0);
        _setText('st-cancelado', por['CANCELADO'] || 0);
        _setText('st-retirado', por['RETIRADO'] || 0);
        const otros = total - (por['LECTIVA'] || 0) - (por['CANCELADO'] || 0) - (por['RETIRADO'] || 0);
        _setText('st-otros', Math.max(0, otros));
    }

    function _resetIndicadores() {
        ['st-total', 'st-lectiva', 'st-cancelado', 'st-retirado', 'st-otros']
            .forEach(id => _setText(id, 0));
    }

    function _vaciarTablaEstados() {
        const el = document.getElementById('tabla-estados-body');
        if (el) el.innerHTML = '<tr><td colspan="3">Sin datos disponibles.</td></tr>';
    }

    // ── Tabla de Resumen de Estados ────────────────────────────
    function _renderTablaEstados() {
        const por = _agruparPorEstado();
        const total = todosAprendices.length;
        const orden = ['LECTIVA', 'CANCELADO', 'RETIRADO', 'APLAZADO', 'TRASLADO'];
        Object.keys(por).forEach(e => { if (!orden.includes(e)) orden.push(e); });

        let filas = '';
        orden.forEach(est => {
            if (!por[est]) return;
            const cnt = por[est];
            const pct = total > 0 ? ((cnt / total) * 100).toFixed(1) : '0.0';
            const dot = est.toLowerCase().replace(/[áé]/g, c => ({ á: 'a', é: 'e' }[c] || c));
            filas += `<tr>
                <td><span class="voc-estado-dot dot-${dot}"></span><strong>${est}</strong></td>
                <td>${cnt}</td>
                <td>${pct}%</td>
            </tr>`;
        });

        filas += `<tr>
            <td><span class="voc-estado-dot dot-total"></span><strong>TOTAL</strong></td>
            <td><strong>${total} aprendices</strong></td>
            <td><strong>100%</strong></td>
        </tr>`;

        const el = document.getElementById('tabla-estados-body');
        if (el) el.innerHTML = filas;
    }

    // ── Gráficas Chart.js ──────────────────────────────────────
    function _renderGraficas() {
        const por = _agruparPorEstado();
        const labels = Object.keys(por);
        const values = labels.map(e => por[e]);
        const colores = labels.map(e => COLORES_ESTADO[e] || COLOR_DEFECTO);

        if (chartDona) { chartDona.destroy(); chartDona = null; }
        if (chartBarras) { chartBarras.destroy(); chartBarras = null; }

        const baseOpts = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { font: { size: 11 }, padding: 12 } }
            }
        };

        const ctxDona = document.getElementById('chart-dona');
        if (ctxDona) {
            chartDona = new Chart(ctxDona, {
                type: 'doughnut',
                data: {
                    labels, datasets: [{
                        data: values, backgroundColor: colores,
                        borderWidth: 2, borderColor: '#fff', hoverOffset: 8
                    }]
                },
                options: {
                    ...baseOpts, cutout: '60%',
                    plugins: {
                        ...baseOpts.plugins,
                        tooltip: {
                            callbacks: {
                                label: ctx => ` ${ctx.label}: ${ctx.parsed} (${((ctx.parsed / todosAprendices.length) * 100).toFixed(1)}%)`
                            }
                        }
                    }
                }
            });
        }

        const ctxBar = document.getElementById('chart-barras');
        if (ctxBar) {
            chartBarras = new Chart(ctxBar, {
                type: 'bar',
                data: {
                    labels, datasets: [{
                        label: 'Aprendices', data: values,
                        backgroundColor: colores, borderRadius: 6, borderSkipped: false
                    }]
                },
                options: {
                    ...baseOpts,
                    plugins: { ...baseOpts.plugins, legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, ticks: { stepSize: 1, font: { size: 11 } }, grid: { color: '#f1f5f9' } },
                        x: { ticks: { font: { size: 11 } }, grid: { display: false } }
                    }
                }
            });
        }
    }

    // ── Tabla de Aprendices (con filtros) ──────────────────────
    function renderizarTabla() {
        const search = (_getVal('filtro-search')).toLowerCase();
        const estadoFilt = _getVal('filtro-estado').toUpperCase();
        const mostrarTodos = estadoFilt === 'TODOS';

        const lista = todosAprendices.filter(a => {
            const est = (a.estado || '').toUpperCase();
            const activo = mostrarTodos
                || (!estadoFilt && !ESTADOS_INACTIVOS.has(est))
                || (!mostrarTodos && estadoFilt && est === estadoFilt);
            const ms = !search
                || (a.nombre || '').toLowerCase().includes(search)
                || (a.apellido || '').toLowerCase().includes(search)
                || (a.documento || '').includes(search);
            return activo && ms;
        });

        const aviso = document.getElementById('aviso-filtro');
        if (aviso) aviso.style.display = (mostrarTodos || estadoFilt) ? 'none' : 'flex';

        if (!lista.length) {
            _mostrarInfo('voc-tabla-body', 'Sin resultados con los filtros aplicados.');
            const pag = document.getElementById('voc-paginacion');
            if (pag) pag.innerHTML = '';
            return;
        }

        const inicio = (paginaActual - 1) * POR_PAGINA;
        const paginaArr = lista.slice(inicio, inicio + POR_PAGINA);
        const totalPags = Math.ceil(lista.length / POR_PAGINA);

        const filas = paginaArr.map((a, i) => {
            const est = (a.estado || '').toUpperCase();
            const badge = _badgeEstado(est);
            return `<tr>
                <td>${inicio + i + 1}</td>
                <td><strong>${a.documento || '—'}</strong></td>
                <td>${(a.nombre || '')} ${(a.apellido || '')}</td>
                <td>${a.correo || '—'}</td>
                <td>${a.celular || '—'}</td>
                <td><span class="badge ${badge}">${a.estado || '—'}</span></td>
            </tr>`;
        }).join('');

        const tablaEl = document.getElementById('voc-tabla-body');
        if (tablaEl) tablaEl.innerHTML = `
            <table>
                <thead><tr>
                    <th>#</th><th>Documento</th><th>Nombre Completo</th>
                    <th>Correo</th><th>Celular</th><th>Estado</th>
                </tr></thead>
                <tbody>${filas}</tbody>
            </table>`;

        const pagEl = document.getElementById('voc-paginacion');
        if (pagEl) pagEl.innerHTML = _renderPaginacion(totalPags, paginaActual);
    }

    // ── Paginación ─────────────────────────────────────────────
    function irPagina(p) {
        paginaActual = p;
        renderizarTabla();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // ── Exportación PDF ────────────────────────────────────────
    function exportarPDF() {
        if (!todosAprendices.length) { alert('No hay aprendices para exportar.'); return; }
        if (!window.jspdf) { alert('Librería PDF no disponible. Verifique conexión a internet.'); return; }

        const { jsPDF } = window.jspdf;
        const doc = new jsPDF('p', 'mm', 'a4');
        const pageW = doc.internal.pageSize.getWidth();
        const hoy = new Date().toLocaleDateString('es-CO', { year: 'numeric', month: 'long', day: 'numeric' });

        doc.setFillColor(0, 50, 77);
        doc.rect(0, 0, pageW, 38, 'F');
        doc.setTextColor(255, 255, 255);
        doc.setFontSize(13); doc.setFont('helvetica', 'bold');
        doc.text('SENA — Centro de Teleinformática y Producción Industrial', pageW / 2, 12, { align: 'center' });
        doc.setFontSize(11);
        doc.text(`Informe Oficial de Aprendices — Ficha ${vocFicha}`, pageW / 2, 21, { align: 'center' });
        doc.setFontSize(8.5); doc.setFont('helvetica', 'normal');
        doc.text(`${vocTipo}: ${vocNombre}   |   Fecha: ${hoy}`, pageW / 2, 30, { align: 'center' });

        const por = _agruparPorEstado();
        const total = todosAprendices.length;
        let y = 46;
        doc.setDrawColor(200); doc.setFillColor(248, 250, 252);
        doc.roundedRect(14, y, pageW - 28, 8 + Object.keys(por).length * 7 + 7, 3, 3, 'FD');
        doc.setFont('helvetica', 'bold'); doc.setTextColor(0, 50, 77); doc.setFontSize(9);
        doc.text('RESUMEN POR ESTADO', 20, y + 6); y += 12;
        doc.setFont('helvetica', 'normal'); doc.setTextColor(55, 65, 81); doc.setFontSize(8.5);
        Object.entries(por).forEach(([estado, cnt]) => {
            doc.text(`${estado}:`, 22, y);
            doc.text(`${cnt}  (${((cnt / total) * 100).toFixed(1)}%)`, 80, y);
            y += 7;
        });
        doc.setFont('helvetica', 'bold'); doc.setTextColor(0, 50, 77);
        doc.text('TOTAL:', 22, y + 1); doc.text(`${total} aprendices`, 80, y + 1); y += 14;

        const activos = todosAprendices.filter(a => !ESTADOS_INACTIVOS.has((a.estado || '').toUpperCase()));
        doc.autoTable({
            startY: y,
            head: [['#', 'Documento', 'Nombre Completo', 'Correo', 'Celular', 'Estado']],
            body: activos.map((a, i) => [
                i + 1, a.documento || '—',
                `${a.nombre || ''} ${a.apellido || ''}`.trim(),
                a.correo || '—', a.celular || '—', a.estado || '—'
            ]),
            headStyles: { fillColor: [0, 50, 77], textColor: 255, fontStyle: 'bold', fontSize: 8.5 },
            bodyStyles: { fontSize: 8 },
            alternateRowStyles: { fillColor: [246, 248, 252] },
            columnStyles: { 0: { cellWidth: 8 }, 1: { cellWidth: 26 }, 2: { cellWidth: 55 }, 3: { cellWidth: 48 }, 4: { cellWidth: 22 }, 5: { cellWidth: 22 } }
        });

        const pages = doc.internal.getNumberOfPages();
        for (let p = 1; p <= pages; p++) {
            doc.setPage(p); doc.setFontSize(7.5); doc.setTextColor(150);
            doc.text(`Página ${p} de ${pages}  —  Documento generado por SenApre`, pageW / 2, 290, { align: 'center' });
        }
        doc.save(`informe-ficha-${vocFicha}.pdf`);
    }

    // ── Utilidades Privadas ────────────────────────────────────
    function _agruparPorEstado() {
        return todosAprendices.reduce((acc, a) => {
            const est = (a.estado || 'SIN ESTADO').toUpperCase();
            acc[est] = (acc[est] || 0) + 1;
            return acc;
        }, {});
    }

    function _obtenerUsuario() {
        try {
            return typeof authSystem !== 'undefined'
                ? authSystem.getCurrentUser()
                : JSON.parse(localStorage.getItem('user') || 'null');
        } catch { return null; }
    }

    function _setText(id, val) { const el = document.getElementById(id); if (el) el.textContent = val; }
    function _getVal(id) { return document.getElementById(id)?.value || ''; }

    function _badgeEstado(estado) {
        const map = { LECTIVA: 'badge-lectiva', CANCELADO: 'badge-cancelado', RETIRADO: 'badge-retirado', APLAZADO: 'badge-aplazado', TRASLADO: 'badge-aplazado' };
        return map[estado] || 'badge-default';
    }

    function _mostrarCargando(id) {
        const el = document.getElementById(id);
        if (el) el.innerHTML = '<div class="voc-loading"><i class="fas fa-spinner"></i> Cargando datos...</div>';
    }
    function _mostrarInfo(id, msg) {
        const el = document.getElementById(id);
        if (el) el.innerHTML = `<div class="voc-loading"><i class="fas fa-inbox"></i> ${msg}</div>`;
    }
    function _mostrarError(id, msg) {
        const el = document.getElementById(id);
        if (el) el.innerHTML = `<div class="voc-loading"><i class="fas fa-times-circle" style="color:#dc2626"></i> ${msg}</div>`;
    }

    function _renderPaginacion(totalPags, actual) {
        if (totalPags <= 1) return '';
        let html = '';
        if (actual > 1) html += `<button class="voc-pag-btn" onclick="VoceroDashboard.irPagina(${actual - 1})">‹ Ant.</button>`;
        for (let p = Math.max(1, actual - 2); p <= Math.min(totalPags, actual + 2); p++) {
            html += `<button class="voc-pag-btn ${p === actual ? 'active' : ''}" onclick="VoceroDashboard.irPagina(${p})">${p}</button>`;
        }
        if (actual < totalPags) html += `<button class="voc-pag-btn" onclick="VoceroDashboard.irPagina(${actual + 1})">Sig. ›</button>`;
        return html;
    }

    // ── API pública del módulo ─────────────────────────────────
    return { init, cargarAprendices, renderizarTabla, irPagina, exportarPDF };
})();

// ── Puente HTML → módulo ───────────────────────────────────────
function filtrarTabla() { VoceroDashboard.renderizarTabla(); }
function exportarPDF() { VoceroDashboard.exportarPDF(); }
function irA(id) { document.getElementById(id)?.scrollIntoView({ behavior: 'smooth' }); }
function cerrarSesion(e) { e.preventDefault(); localStorage.removeItem('user'); window.location.href = 'index.html'; }

document.addEventListener('DOMContentLoaded', () => VoceroDashboard.init());
