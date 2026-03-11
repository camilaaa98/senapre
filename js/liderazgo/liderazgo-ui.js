/**
 * Liderazgo Module - UI Logic (SOLID)
 * Handles Tabs, Modals, and General DOM interactions
 */

const LiderazgoUI = {
    currentTab: 'voceros',
    itemsPerPage: 6,
    
    init() {
        this.setupEventListeners();
        this.updateUserDisplay();
    },

    setupEventListeners() {
        document.addEventListener('DOMContentLoaded', () => {
            // Initial loads
        });
    },

    updateUserDisplay() {
        const user = authSystem.getCurrentUser();
        if (!user) return;

        const roleDisplay = document.getElementById('lid-role-display');
        if (roleDisplay) {
            roleDisplay.innerHTML = `
                <strong style="color:#6ee47b;display:block;">Liderazgo</strong>
                <span style="font-size:0.75rem;opacity:0.9;color:white;display:block;margin-top:2px;">${user.nombre} ${user.apellido}</span>
            `;
        }
        
        const topbarUser = document.getElementById('lid-topbar-user');
        if (topbarUser) topbarUser.textContent = `${user.nombre} ${user.apellido}`;
    },

    switchTab(tab) {
        this.currentTab = tab;
        document.querySelectorAll('.tab-panel').forEach(p => p.style.display = 'none');
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        
        const panel = document.getElementById('tab-' + tab);
        const btn = document.getElementById('tab-btn-' + tab);
        if (panel) panel.style.display = 'block';
        if (btn) btn.classList.add('active');

        // Logic for specific tab loads
        if (tab === 'reuniones') {
            const fab = document.getElementById('fab-reunion');
            if (fab) fab.style.display = 'flex';
        } else {
            const fab = document.getElementById('fab-reunion');
            if (fab) fab.style.display = 'none';
        }
    },

    renderLideres(lideres, containerId, page = 1, tipoFiltro) {
        const container = document.getElementById(containerId);
        if (!lideres || !lideres.length) {
            container.innerHTML = `<div class="empty-state"><i class="fas fa-users-slash"></i><p>No se encontraron registros.</p></div>`;
            return;
        }

        const start = (page - 1) * this.itemsPerPage;
        const end = start + this.itemsPerPage;
        const paginatedItems = lideres.slice(start, end);

        const badgeMap = {
            'vocero': 'badge-vocero',
            'representante': 'badge-representante',
            'enfoque': 'badge-enfoque'
        };

        let html = paginatedItems.map(l => {
            const tipo = (l.tipo || '').toLowerCase();
            const badgeClass = Object.keys(badgeMap).find(k => tipo.includes(k)) 
                ? badgeMap[Object.keys(badgeMap).find(k => tipo.includes(k))] 
                : 'badge-enfoque';
            
            // UI Request: Ficha instead of #
            return `
            <div class="glass-card lider-card">
                <div class="lider-card-top">
                    <div class="lider-avatar"><i class="fas fa-user-circle"></i></div>
                    <div class="lider-info">
                        <h3>${l.nombre} ${l.apellido}</h3>
                        <span class="badge-premium ${badgeClass}">${l.tipo}</span>
                    </div>
                </div>
                <div class="lider-details">
                    <p><i class="fas fa-chalkboard"></i> Ficha: ${l.detalle}</p>
                    <p><i class="fas fa-envelope"></i> ${l.correo || 'Correo no registrado'}</p>
                </div>
                <div class="lider-actions" style="margin-top:1rem; display:flex; gap:10px;">
                    <button class="btn-lid btn-lid-secondary" onclick="LiderazgoUI.editLider('${l.documento}')" title="Editar">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn-lid btn-lid-primary" style="flex:1" onclick="LiderazgoUI.verSeguimiento('${l.documento}')">
                        <i class="fas fa-list-check"></i> Consultar Trayectoria
                    </button>
                </div>
            </div>`;
        }).join('');

        const totalPages = Math.ceil(lideres.length / this.itemsPerPage);
        if (totalPages > 1) {
            html += this.renderPagination(page, totalPages, tipoFiltro);
        }

        container.innerHTML = html;
    },

    renderPagination(page, totalPages, tipo) {
        return `
        <div class="pagination-controls" style="grid-column: 1/-1;">
            <button class="btn-lid btn-lid-secondary" ${page === 1 ? 'disabled' : ''} onclick="LiderazgoData.changePage('${tipo}', ${page - 1})">
                <i class="fas fa-chevron-left"></i> Anterior
            </button>
            <span class="page-info">Página ${page} de ${totalPages}</span>
            <button class="btn-lid btn-lid-secondary" ${page === totalPages ? 'disabled' : ''} onclick="LiderazgoData.changePage('${tipo}', ${page + 1})">
                Siguiente <i class="fas fa-chevron-right"></i>
            </button>
        </div>`;
    },

    editLider(documento) {
        // Implementation for editing
        console.log('Edit', documento);
    },

    abrirModalReunion() {
        document.getElementById('modalEditLider').style.display = 'none';
        // Reusaremos un modal similar para reuniones o crearemos uno rápido
        const modalHtml = `
            <div class="modal-overlay" id="modalAddReunion" style="display:flex;">
                <div class="modal-glass">
                    <h2 class="modal-title">Programar Reunión</h2>
                    <form id="formAddReunion">
                        <div class="form-group"><label>Título</label><input type="text" id="new-titulo" class="form-input" required></div>
                        <div class="form-group"><label>Fecha</label><input type="date" id="new-fecha" class="form-input" required></div>
                        <div class="form-group"><label>Hora</label><input type="time" id="new-hora" class="form-input" value="08:00"></div>
                        <div class="form-group"><label>Lugar</label><input type="text" id="new-lugar" class="form-input" placeholder="Auditorio"></div>
                        <div class="form-actions">
                            <button type="button" onclick="this.closest('.modal-overlay').remove()" class="btn-lid btn-lid-cancel">Cancelar</button>
                            <button type="submit" class="btn-lid btn-lid-primary">Crear Encuentro</button>
                        </div>
                    </form>
                </div>
            </div>`;
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        
        document.getElementById('formAddReunion').onsubmit = async (e) => {
            e.preventDefault();
            const data = {
                titulo: document.getElementById('new-titulo').value,
                fecha: document.getElementById('new-fecha').value,
                hora: document.getElementById('new-hora').value,
                lugar: document.getElementById('new-lugar').value
            };
            const res = await fetch('api/liderazgo.php?action=saveReunion', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            }).then(r => r.json());
            
            if (res.success) {
                alert('Reunión programada con éxito');
                document.getElementById('modalAddReunion').remove();
                if (typeof cargarLiderazgo === 'function') cargarLiderazgo();
            }
        };
    },

    verSeguimiento(documento) {
        // Implementation for tracking
        window.location.href = `trayectoria-lider.html?doc=${documento}`;
    }
};
