'use strict';

const BienestarDashboard = (() => {
    let aprendices = [];
    let pagina = 1;
    const POR_PAGINA = 25;

    // ── Inicialización ─────────────────────────────────────────
    function init() {
        const user = _obtenerUsuario();
        if (!user) { window.location.href = 'index.html'; return; }

        const rolesPermitidos = ['administrativo', 'coordinador', 'director', 'admin', 'administrador', 'bienestar'];
        if (!rolesPermitidos.includes((user.rol || '').toLowerCase())) {
            localStorage.removeItem('user');
            window.location.href = 'index.html';
            return;
        }

        const nombre = (`${user.nombre || ''} ${user.apellido || ''}`).trim();
        _setText('bw-nombre', nombre || 'Bienestar');

        cargarEstadisticas();
        cargarAprendices();
    }

    // ── Cambio de Pestaña ──────────────────────────────────────
    function mostrarTab(tab, el) {
        document.querySelectorAll('.bw-tab').forEach(t => t.classList.remove('active'));
        if (el) el.classList.add('active');
        else document.getElementById(`tab-${tab}`)?.classList.add('active');

        ['aprendices', 'lideres', 'poblacion'].forEach(p => {
            const panel = document.getElementById(`panel-${p}`);
            if (panel) panel.classList.toggle('bw-panel-oculto', p !== tab);
        });

        if (tab === 'lideres') _cargarLideres();
        if (tab === 'poblacion') _cargarPoblacion();
    }

    // ── Estadísticas ───────────────────────────────────────────
    async function cargarEstadisticas() {
        try {
            const [rStats, rLideres] = await Promise.all([
                fetch('api/reportes.php').then(r => r.json()),
                fetch('api/lideres.php').then(r => r.json()).catch(() => ({ success: false }))
            ]);

            if (rStats.success) {
                const r = rStats.data?.resumen || {};
                _setText('bw-total', r.aprendices || 0);
                _setText('bw-fichas', r.fichas || 0);
                const activos = (rStats.data?.aprendices_estado || [])
                    .find(e => (e.estado || '').toUpperCase() === 'LECTIVA');
                _setText('bw-activos', activos?.cantidad || 0);
            }
            if (rLideres.success) {
                _setText('bw-lideres', (rLideres.data || []).length);
            }
        } catch { /* Estadísticas no críticas */ }
    }

    // ── Carga de Aprendices ────────────────────────────────────
    async function cargarAprendices() {
        _setCargando('bw-tabla-aprendices');
        try {
            const resp = await fetch('api/aprendices.php?limit=2000&page=1');
            const data = await resp.json();

            if (data.success && data.data?.length) {
                aprendices = data.data;
                _poblarFiltroFichas();
                pagina = 1;
                renderizarAprendices();
            } else {
                document.getElementById('bw-tabla-aprendices').innerHTML =
                    '<div class="bw-loading">No se encontraron aprendices.</div>';
            }
        } catch {
            document.getElementById('bw-tabla-aprendices').innerHTML =
                '<div class="bw-loading">Error al cargar datos.</div>';
        }
    }

    function _poblarFiltroFichas() {
        const fichas = [...new Set(aprendices.map(a => a.numero_ficha).filter(Boolean))].sort();
        const sel = document.getElementById('bw-ficha');
        if (!sel) return;
        fichas.forEach(f => {
            const opt = document.createElement('option');
            opt.value = f;
            opt.textContent = `Ficha ${f}`;
            sel.appendChild(opt);
        });
    }

    function filtrarAprendices() { pagina = 1; renderizarAprendices(); }

    function renderizarAprendices() {
        const search = (_getVal('bw-search')).toLowerCase();
        const estado = _getVal('bw-estado').toUpperCase();
        const ficha = _getVal('bw-ficha');

        const lista = aprendices.filter(a => {
            const ms = !search || (a.nombre || '').toLowerCase().includes(search)
                || (a.apellido || '').toLowerCase().includes(search)
                || (a.documento || '').includes(search);
            const me = !estado || (a.estado || '').toUpperCase() === estado;
            const mf = !ficha || (a.numero_ficha || '') === ficha;
            return ms && me && mf;
        });

        if (!lista.length) {
            document.getElementById('bw-tabla-aprendices').innerHTML =
                '<div class="bw-loading">Sin resultados con los filtros aplicados.</div>';
            document.getElementById('bw-pag-aprendices').innerHTML = '';
            return;
        }

        const inicio = (pagina - 1) * POR_PAGINA;
        const paginaData = lista.slice(inicio, inicio + POR_PAGINA);
        const totalPags = Math.ceil(lista.length / POR_PAGINA);

        const filas = paginaData.map((a, i) => {
            const est = (a.estado || '').toUpperCase();
            const bc = { LECTIVA: 'badge-lectiva', CANCELADO: 'badge-cancelado', RETIRADO: 'badge-retirado' }[est] || 'badge-default';
            return `<tr>
                <td>${inicio + i + 1}</td>
                <td>${a.documento || '—'}</td>
                <td>${a.nombre || ''} ${a.apellido || ''}</td>
                <td>${a.numero_ficha || '—'}</td>
                <td><span class="badge ${bc}">${a.estado || '—'}</span></td>
                <td>${a.tipo_poblacion || '—'}</td>
            </tr>`;
        }).join('');

        document.getElementById('bw-tabla-aprendices').innerHTML = `
            <table>
                <thead><tr>
                    <th>#</th><th>Documento</th><th>Nombre</th>
                    <th>Ficha</th><th>Estado</th><th>Tipo Población</th>
                </tr></thead>
                <tbody>${filas}</tbody>
            </table>`;

        // Paginación simple
        let pag = '';
        for (let p = Math.max(1, pagina - 3); p <= Math.min(totalPags, pagina + 3); p++) {
            pag += `<button class="bw-pag-btn ${p === pagina ? 'active' : ''}" onclick="BienestarDashboard.irPagina(${p})">${p}</button>`;
        }
        document.getElementById('bw-pag-aprendices').innerHTML = pag;
    }

    function irPagina(p) { pagina = p; renderizarAprendices(); }

    // ── Líderes ────────────────────────────────────────────────
    async function _cargarLideres() {
        const el = document.getElementById('bw-tabla-lideres');
        _setCargando('bw-tabla-lideres', 'Cargando líderes...');
        try {
            const resp = await fetch('api/lideres.php');
            const data = await resp.json();
            if (!data.success || !data.data?.length) {
                el.innerHTML = '<div class="bw-loading">No hay líderes registrados.</div>'; return;
            }
            const filas = data.data.map(l =>
                `<tr><td>${l.documento || '—'}</td><td>${l.nombre || ''} ${l.apellido || ''}</td><td>${l.tipo || '—'}</td><td>${l.numero_ficha || '—'}</td></tr>`
            ).join('');
            el.innerHTML = `<table>
                <thead><tr><th>Documento</th><th>Nombre</th><th>Tipo</th><th>Ficha</th></tr></thead>
                <tbody>${filas}</tbody>
            </table>`;
        } catch { el.innerHTML = '<div class="bw-loading">Error al cargar líderes.</div>'; }
    }

    // ── Población ──────────────────────────────────────────────
    async function _cargarPoblacion() {
        const el = document.getElementById('bw-tabla-poblacion');
        if (!aprendices.length) { el.innerHTML = '<div class="bw-loading">Sin datos de población.</div>'; return; }

        const grupos = [...new Set(aprendices.map(a => a.tipo_poblacion).filter(Boolean))].sort();
        const filas = grupos.map(g => {
            const count = aprendices.filter(a => a.tipo_poblacion === g).length;
            return `<tr><td>${g}</td><td><strong>${count}</strong></td></tr>`;
        }).join('');

        el.innerHTML = `<table>
            <thead><tr><th>Tipo de Población</th><th>Cantidad</th></tr></thead>
            <tbody>${filas}</tbody>
        </table>`;
    }

    // ── Utilidades privadas ────────────────────────────────────
    function _obtenerUsuario() {
        return typeof authSystem !== 'undefined'
            ? authSystem.getCurrentUser()
            : JSON.parse(localStorage.getItem('user') || 'null');
    }
    function _setText(id, val) { const el = document.getElementById(id); if (el) el.textContent = val; }
    function _getVal(id) { return document.getElementById(id)?.value || ''; }
    function _setCargando(id, msg = 'Cargando...') {
        const el = document.getElementById(id);
        if (el) el.innerHTML = `<div class="bw-loading"><i class="fas fa-spinner"></i>${msg}</div>`;
    }

    // API pública
    return { init, mostrarTab, filtrarAprendices, renderizarAprendices, irPagina };
})();

// Funciones globales requeridas por el HTML
function mostrarTab(tab, el) { BienestarDashboard.mostrarTab(tab, el); }
function filtrarAprendices() { BienestarDashboard.filtrarAprendices(); }
function cerrarSesion(e) { e.preventDefault(); localStorage.removeItem('user'); window.location.href = 'index.html'; }

document.addEventListener('DOMContentLoaded', () => BienestarDashboard.init());
