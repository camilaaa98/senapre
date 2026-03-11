/**
 * Liderazgo Module - UI Logic (SOLID)
 * Handles Tabs, Modals, and General DOM interactions
 */

const LiderazgoUI = {
    currentTab: 'voceros',
    itemsPerPage: 6,
    
    async init() {
        this.setupEventListeners();
        this.updateUserDisplay();
    },

    setupEventListeners() {
        document.addEventListener('DOMContentLoaded', () => {
            // Initial loads
            const formAsig = document.getElementById('formAsignarRol');
            if (formAsig) {
                formAsig.onsubmit = async (e) => {
                    e.preventDefault();
                    const tipo = document.getElementById('asig-tipo').value;
                    const doc = document.getElementById('asig-aprendiz').value;
                    const ficha = document.getElementById('asig-ficha').value;
                    const cat = document.getElementById('asig-cat').value;
                    const jor = document.getElementById('asig-jornada').value;

                    try {
                        const res = await fetch('api/liderazgo.php?action=asignarRol', {
                            method: 'POST',
                            headers: {'Content-Type': 'application/json'},
                            body: JSON.stringify({
                                tipo_rol: tipo,
                                documento: doc,
                                numero_ficha: ficha,
                                categoria: cat,
                                jornada: jor
                            })
                        }).then(r => r.json());

                        if (res.success) {
                            alert(res.message);
                            document.getElementById('modalAsignarRol').style.display = 'none';
                            // Reload data
                            if (typeof cargarLiderazgo === 'function') cargarLiderazgo();
                        } else {
                            alert('Error: ' + res.message);
                        }
                    } catch(err) {
                        alert('Error de conexión');
                    }
                };
            }

            const formEdit = document.getElementById('formEditLider');
            if (formEdit) {
                formEdit.onsubmit = async (e) => {
                    e.preventDefault();
                    const doc = document.getElementById('edit-doc').value;
                    const correo = document.getElementById('edit-correo').value;
                    const tel = document.getElementById('edit-tel').value;

                    const res = await LiderazgoData.updateLider(doc, { correo, telefono: tel });
                    if (res.success) {
                        alert('Datos actualizados correctamente');
                        document.getElementById('modalEditLider').style.display = 'none';
                        if (typeof cargarLiderazgo === 'function') cargarLiderazgo();
                    } else {
                        alert('Error al actualizar: ' + (res.message || 'Desconocido'));
                    }
                };
            }
        });
    },

    async updateUserDisplay() {
        const user = authSystem.getCurrentUser();
        if (!user) return;
        
        const topbarUser = document.getElementById('lid-topbar-user');
        if (topbarUser) topbarUser.textContent = `${user.nombre} ${user.apellido}`;

        try {
            const res = await fetch('api/liderazgo.php?action=getResponsable&area=voceros_y_representantes');
            const data = await res.json();
            if (data.success && data.data) {
                const roleDisplay = document.getElementById('lid-role-display');
                if (roleDisplay) {
                    roleDisplay.innerHTML = `
                        <strong style="color:#6ee47b;display:block;">Liderazgo</strong>
                        <span style="font-size:0.75rem;opacity:0.9;color:white;display:block;margin-top:2px;text-transform:uppercase;">${data.data.nombre} ${data.data.apellido}</span>
                        <span style="font-size:0.65rem;opacity:0.6;color:white;display:block;">${data.data.correo}</span>
                    `;
                }
            }
        } catch (e) {
            console.error('Error fetching responsable:', e);
        }
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

        let html = `
        <div class="table-responsive" style="border: 1px solid #f1f5f9; border-radius: 8px; overflow-x: auto; margin-top: 1rem;">
            <table class="lid-table" style="width: 100%; min-width: 900px; border-collapse: collapse; text-align: left; font-family: 'Inter', sans-serif;">
                <thead>
                    <tr style="background-color: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                        <th style="padding: 1rem; color: #64748b; font-weight: 600; font-size: 0.8rem;">FICHA</th>
                        <th style="padding: 1rem; color: #64748b; font-weight: 600; font-size: 0.8rem;">DOCUMENTO</th>
                        <th style="padding: 1rem; color: #64748b; font-weight: 600; font-size: 0.8rem;">NOMBRE COMPLETO</th>
                        <th style="padding: 1rem; color: #64748b; font-weight: 600; font-size: 0.8rem;">CORREO</th>
                        <th style="padding: 1rem; color: #64748b; font-weight: 600; font-size: 0.8rem;">CELULAR</th>
                        <th style="padding: 1rem; color: #64748b; font-weight: 600; font-size: 0.8rem; text-align:center;">ESTADO</th>
                        <th style="padding: 1rem; color: #64748b; font-weight: 600; font-size: 0.8rem; text-align:center;">EDITAR</th>
                    </tr>
                </thead>
                <tbody>
        `;
        
        paginatedItems.forEach((l, i) => {
            const index = start + i + 1;
            const isLectiva = l.estado === 'LECTIVA';
            const pillBg = isLectiva ? '#e6f4ea' : '#fef3c7';
            const pillColor = isLectiva ? '#1e8e3e' : '#b45309';
            
            html += `
                <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.2s;">
                    <td style="padding: 1rem; color: #64748b; font-weight: 700;">${l.numero_ficha || '—'}</td>
                    <td style="padding: 1rem; color: #1e293b; font-weight: 700;">${l.documento}</td>
                    <td style="padding: 1rem; color: #475569;">${l.nombre} ${l.apellido}</td>
                    <td style="padding: 1rem; color: #64748b;">${l.correo || 'No disponible'}</td>
                    <td style="padding: 1rem; color: #64748b;">${l.telefono || 'N/A'}</td>
                    <td style="padding: 1rem; text-align:center;">
                        <span style="background:${pillBg}; color:${pillColor}; padding: 4px 12px; border-radius: 20px; font-weight: 600; font-size: 0.75rem; display: inline-block;">${l.estado}</span>
                    </td>
                    <td style="padding: 1rem; text-align:center;">
                        <button style="background: #e0f2fe; color: #0284c7; border: none; width: 32px; height: 32px; border-radius: 50%; cursor: pointer; display: inline-flex; justify-content: center; align-items: center; transition: all 0.2s;" onmouseover="this.style.background='#bae6fd'" onmouseout="this.style.background='#e0f2fe'" onclick="LiderazgoUI.editLider('${l.documento}')" title="Editar">
                            <i class="fas fa-pen" style="font-size: 0.85rem;"></i>
                        </button>
                    </td>
                </tr>
            `;
        });
        
        html += `
                </tbody>
            </table>
        </div>
        `;

        const totalPages = Math.ceil(lideres.length / this.itemsPerPage);
        if (totalPages > 1) {
            html += this.renderPagination(page, totalPages, tipoFiltro);
        }

        container.innerHTML = html;
    },

    renderPagination(page, totalPages, tipo) {
        return `
        <div style="grid-column: 1/-1; display:flex; justify-content:center; align-items:center; gap: 15px; margin-top: 20px; padding: 10px;">
            <button style="padding: 8px 16px; border: 1px solid #e2e8f0; border-radius: 6px; background: ${page === 1 ? '#f8fafc' : 'white'}; color: ${page === 1 ? '#cbd5e1' : '#475569'}; font-weight: 500; cursor: ${page === 1 ? 'not-allowed' : 'pointer'}; transition: all 0.2s;" ${page === 1 ? 'disabled' : ''} onclick="LiderazgoData.changePage('${tipo}', ${page - 1})">
                <i class="fas fa-chevron-left" style="margin-right: 5px;"></i> Ant
            </button>
            
            <div style="background: #f1f5f9; padding: 6px 16px; border-radius: 20px; font-size: 0.85rem; color: #334155; font-weight: 600;">
                <span style="color:#39A900;">${page}</span> / ${totalPages}
            </div>
            
            <button style="padding: 8px 16px; border: 1px solid #e2e8f0; border-radius: 6px; background: ${page === totalPages ? '#f8fafc' : 'white'}; color: ${page === totalPages ? '#cbd5e1' : '#475569'}; font-weight: 500; cursor: ${page === totalPages ? 'not-allowed' : 'pointer'}; transition: all 0.2s;" ${page === totalPages ? 'disabled' : ''} onclick="LiderazgoData.changePage('${tipo}', ${page + 1})">
                Sig <i class="fas fa-chevron-right" style="margin-left: 5px;"></i>
            </button>
        </div>`;
    },

    editLider(documento) {
        let lider = null;
        const caches = [LiderazgoData.cache.principales, LiderazgoData.cache.suplentes, LiderazgoData.cache.enfoque, LiderazgoData.cache.representantes];
        for (const cache of caches) {
            if (cache) {
                const found = cache.find(l => String(l.documento) === String(documento));
                if (found) { lider = found; break; }
            }
        }

        if (!lider) {
            alert('Líder no encontrado en la memoria actual.');
            return;
        }

        document.getElementById('edit-doc').value = lider.documento;
        document.getElementById('edit-doc-display').value = lider.documento;
        document.getElementById('edit-nombre-display').value = `${lider.nombre} ${lider.apellido}`;
        document.getElementById('edit-correo').value = lider.correo || '';
        document.getElementById('edit-tel').value = lider.telefono || '';
        
        document.getElementById('modalEditLider').style.display = 'flex';
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

    async abrirModalAsignacion() {
        document.getElementById('formAsignarRol').reset();
        document.getElementById('grp-ficha').style.display = 'none';
        document.getElementById('grp-enfoque').style.display = 'none';
        document.getElementById('grp-jornada').style.display = 'none';
        document.getElementById('grp-aprendiz').style.display = 'none';
        document.getElementById('modalAsignarRol').style.display = 'flex';

        // Pre-load fichas
        const asigFicha = document.getElementById('asig-ficha');
        try {
            const res = await fetch('api/liderazgo.php?action=getFichasActivas').then(r => r.json());
            if (res.success) {
                asigFicha.innerHTML = '<option value="">Seleccione Ficha...</option>' + 
                    res.data.map(f => `<option value="${f.numero_ficha}">${f.numero_ficha} - ${f.nombre_programa}</option>`).join('');
            }
        } catch (e) {
            console.error(e);
        }
    },

    async cambioTipoRolAsignacion(tipo) {
        document.getElementById('grp-ficha').style.display = (tipo === 'principal' || tipo === 'suplente') ? 'block' : 'none';
        document.getElementById('grp-enfoque').style.display = (tipo === 'enfoque') ? 'block' : 'none';
        document.getElementById('grp-jornada').style.display = (tipo === 'representante') ? 'block' : 'none';
        document.getElementById('grp-aprendiz').style.display = 'none';

        if (tipo === 'enfoque' || tipo === 'representante') {
            await this.cargarAprendicesParaRol();
        } else {
            document.getElementById('asig-ficha').value = '';
        }
    },

    async cargarAprendicesParaRol(ficha = null) {
        document.getElementById('grp-aprendiz').style.display = 'block';
        const asigAprendiz = document.getElementById('asig-aprendiz');
        asigAprendiz.innerHTML = '<option value="">Cargando aprendices...</option>';
        
        try {
            const url = ficha ? `api/liderazgo.php?action=getAprendicesLectiva&ficha=${ficha}` : `api/liderazgo.php?action=getAprendicesLectiva`;
            const res = await fetch(url).then(r => r.json());
            if (res.success) {
                asigAprendiz.innerHTML = '<option value="">Seleccione Aprendiz...</option>' + 
                    res.data.map(a => `<option value="${a.documento}">${a.nombre} ${a.apellido} (${a.documento})</option>`).join('');
            }
        } catch (e) {
            asigAprendiz.innerHTML = '<option value="">Error al cargar.</option>';
        }
    },

    verSeguimiento(documento) {
        // Implementation for tracking
        window.location.href = `trayectoria-lider.html?doc=${documento}`;
    }
};
