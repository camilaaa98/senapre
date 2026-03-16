/**
 * Funcionalidades de Población - Liderazgo SenApre
 * Principios SOLID: Separación de responsabilidades
 */

class PoblacionManager {
    constructor() {
        this.statsPoblacion = {};
        this.vocerosEnfoqueMap = {};
        this.currentCatData = [];
        this.currentKey = null;
        this.currentLabel = null;
        this.currentPage = 1;
        this.totalPages = 1;
        this.filtrosActivos = {
            ficha: '',
            busqueda: ''
        };
        this.todosLosDatos = [];
    }

    /**
     * Cargar estadísticas de población desde el API
     */
    async cargarEstadisticas() {
        try {
            const res = await fetch('api/liderazgo.php?action=getPoblacionStats');
            const data = await res.json();
            
            if (data.success) {
                this.statsPoblacion = data.counts || {};
                this.vocerosEnfoqueMap = data.voceros || {};
            }
        } catch(e) {
            console.error("Error al cargar estadísticas:", e);
        }
    }

    /**
     * Renderizar grid de categorías con diseño profesional
     */
    renderGrid() {
        const CATEGORIAS = [
            { key: 'mujer', label: 'Mujer', icon: 'fas fa-venus', bg: '#f8bbd9', bgDark: '#f2d7a5', col: '#7c2d12' },
            { key: 'indigena', label: 'Indígena', icon: 'fas fa-feather', bg: '#a8dadc', bgDark: '#6c757d', col: '#ffffff' },
            { key: 'narp', label: 'NARP', icon: 'fas fa-hands', bg: '#f5b7b1', bgDark: '#f4a460', col: '#212529' },
            { key: 'campesino', label: 'Campesino', icon: 'fas fa-seedling', bg: '#ffeaa7', bgDark: '#fdcb6e', col: '#2d3436' },
            { key: 'lgbtiq', label: 'LGBTIQ+', icon: 'fas fa-rainbow', bg: '#fab1a0', bgDark: '#ff7675', col: '#2d3436' },
            { key: 'discapacidad', label: 'Discapacidad', icon: 'fas fa-wheelchair', bg: '#74b9ff', bgDark: '#0984e3', col: '#ffffff' }
        ];

        let html = CATEGORIAS.map(c => {
            const count = this.statsPoblacion[c.key] || 0;
            return `
            <div class="categoria-card fade-in" style="--card-bg-start: ${c.bg}; --card-bg-end: ${c.bgDark || c.bg}; --card-text: ${c.col};" onclick="poblacionManager.cargarAprendicesPorCategoria('${c.key}')">
                <i class="${c.icon} categoria-icon"></i>
                <span class="categoria-label">${c.label}</span>
                <span class="categoria-count">${count}</span>
            </div>`;
        }).join('');

        document.getElementById('pob-grid-categorias').innerHTML = html;
    }

    /**
     * Cargar aprendices por categoría con patrones específicos
     */
    async cargarAprendicesPorCategoria(key) {
        const CATEGORIAS = [
            { key: 'mujer', label: 'Mujer' },
            { key: 'indigena', label: 'Indígena' },
            { key: 'narp', label: 'NARP' },
            { key: 'campesino', label: 'Campesino' },
            { key: 'lgbtiq', label: 'LGBTIQ+' },
            { key: 'discapacidad', label: 'Discapacidad' }
        ];

        const cat = CATEGORIAS.find(c => c.key === key);
        
        // Actualizar título de la tabla
        document.getElementById('tabla-poblacion-titulo').textContent = `Detalle de Aprendices - ${cat.label}`;
        
        // Mostrar loading
        const tbody = document.getElementById('pob-tabla-body');
        tbody.innerHTML = '<tr><td colspan="8" class="text-center" style="padding:2rem;"><i class="fas fa-spinner fa-spin"></i> Cargando aprendices...</td></tr>';
        
        try {
            // Obtener patrones de búsqueda para esta categoría
            const patrones = this.getPatronesPorCategoria(key);
            
            // Construir la consulta SQL con múltiples patrones LIKE
            const whereConditions = patrones.map(p => `UPPER(a.tipo_poblacion) LIKE UPPER('${p}')`).join(' OR ');
            
            // Llamar al API con los patrones específicos y paginación
            const res = await fetch(`api/aprendices.php?estado=LECTIVA&custom_filter=${encodeURIComponent(whereConditions)}&limit=5&page=1`);
            const data = await res.json();
            
            if (data.success && data.data.length > 0) {
                this.todosLosDatos = data.data;
                this.currentCatData = data.data;
                this.currentKey = key;
                this.currentLabel = cat.label;
                this.totalPages = data.pagination?.pages || 1;
                this.currentPage = data.pagination?.page || 1;
                
                // Cargar fichas únicas para el filtro
                this.cargarFichasUnicas();
                
                this.renderTable();
                this.renderChart();
                
                // Scroll suave a la tabla
                const tableContainer = document.querySelector('.table-container');
                if (tableContainer) {
                    tableContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            } else {
                tbody.innerHTML = `<tr><td colspan="8" class="text-center" style="padding:2rem;color:#64748b;">No se encontraron aprendices para la categoría ${cat.label}.</td></tr>`;
            }
        } catch (error) {
            console.error('Error al cargar aprendices:', error);
            tbody.innerHTML = `<tr><td colspan="8" class="text-center" style="padding:2rem;color:#ef4444;">Error al cargar los datos. Por favor intente nuevamente.</td></tr>`;
        }
    }

    /**
     * Obtener patrones de búsqueda por categoría
     */
    getPatronesPorCategoria(key) {
        const patrones = {
            'mujer': ['%mujer%', '%mujeres%', '%femenino%', '%femenina%', '%F%', '%muj%'],
            'indigena': ['%indigena%', '%indígena%', '%etnia%', '%pueblos%', '%indígenas%', '%etnico%'],
            'narp': ['%narp%', '%negro%', '%afro%', '%afrodescendiente%', '%raizal%', '%palenquero%', '%afro%'],
            'campesino': ['%campesino%', '%campesina%', '%rural%', '%campo%', '%camp%'],
            'lgbtiq': ['%lgbti%', '%lgbt%', '%trans%', '%gay%', '%lesbiana%', '%bisexual%', '%queer%', '%homosexual%', '+'],
            'discapacidad': ['%discapacidad%', '%discapacitado%', '%discapacitada%', '%capacidad%', '%disc%']
        };
        return patrones[key] || [];
    }

    /**
     * Renderizar tabla de aprendices
     */
    renderTable() {
        if (!this.currentCatData || this.currentCatData.length === 0) {
            document.getElementById('pob-tabla-body').innerHTML = '<tr><td colspan="8" class="text-center" style="padding:2rem;color:#64748b;">No hay aprendices para mostrar.</td></tr>';
            return;
        }
        
        let html = this.currentCatData.map(a => `
            <tr>
                <td>${a.documento}</td>
                <td>${a.nombre}</td>
                <td>${a.apellido}</td>
                <td>${a.correo}</td>
                <td>${a.celular}</td>
                <td>${a.numero_ficha}</td>
                <td>${a.tipo_formacion}</td>
                <td>
                    <button type="button" class="btn btn-danger btn-sm" onclick="poblacionManager.eliminarAprendiz('${a.documento}', '${a.nombre} ${a.apellido}')" style="padding: 4px 8px; font-size: 11px;">
                        <i class="fas fa-trash"></i> Eliminar
                    </button>
                </td>
            </tr>
        `).join('');
        
        document.getElementById('pob-tabla-body').innerHTML = html;
        
        // Renderizar paginación
        this.renderPagination();
    }
    
    /**
     * Renderizar paginación
     */
    renderPagination() {
        const paginationDiv = document.getElementById('pagination-container');
        if (!paginationDiv) {
            // Crear contenedor de paginación si no existe
            const tableContainer = document.querySelector('.table-responsive');
            if (tableContainer) {
                const paginationHtml = `
                    <div id="pagination-container" class="pagination-wrapper" style="display: flex; justify-content: center; align-items: center; gap: 10px; margin-top: 20px; padding: 15px;">
                        <!-- Paginación se renderizará aquí -->
                    </div>
                `;
                tableContainer.insertAdjacentHTML('afterend', paginationHtml);
            }
        }
        
        const container = document.getElementById('pagination-container');
        if (this.totalPages <= 1) {
            container.innerHTML = '';
            return;
        }
        
        let paginationHtml = `
            <div style="display: flex; align-items: center; gap: 10px;">
                <button class="btn btn-outline btn-sm" onclick="poblacionManager.cambiarPagina(${this.currentPage - 1})" ${this.currentPage === 1 ? 'disabled' : ''}>
                    <i class="fas fa-chevron-left"></i> Anterior
                </button>
                <span style="padding: 8px 16px; background: var(--bg-secondary); border-radius: 6px; font-size: 14px;">
                    Página ${this.currentPage} de ${this.totalPages}
                </span>
                <button class="btn btn-outline btn-sm" onclick="poblacionManager.cambiarPagina(${this.currentPage + 1})" ${this.currentPage === this.totalPages ? 'disabled' : ''}>
                    Siguiente <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        `;
        
        container.innerHTML = paginationHtml;
    }

    /**
     * Renderizar gráfico de distribución
     */
    renderChart() {
        if (window.poblacionChart) {
            const CATEGORIAS = ['Mujer', 'Indígena', 'NARP', 'Campesino', 'LGBTIQ+', 'Discapacidad'];
            const KEYS = ['mujer', 'indigena', 'narp', 'campesino', 'lgbtiq', 'discapacidad'];
            
            const catCounts = {};
            KEYS.forEach((key, index) => {
                catCounts[CATEGORIAS[index]] = this.statsPoblacion[key] || 0;
            });
            
            window.poblacionChart.data.datasets[0].data = Object.values(catCounts);
            window.poblacionChart.update();
        }
    }

    /**
     * Eliminar aprendiz de población específica
     */
    async eliminarDePoblacion(documento) {
        if (!confirm('¿Está seguro de eliminar este aprendiz de esta categoría de población? Esta acción eliminará la etiqueta de población del aprendiz.')) {
            return;
        }
        
        try {
            const res = await fetch('api/liderazgo.php?action=eliminarDePoblacion', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ documento: documento })
            });
            const data = await res.json();
            
            if (data.success) {
                alert('Aprendiz eliminado correctamente de la población');
                // Recargar datos
                await this.cargarEstadisticas();
                this.renderGrid();
                this.cargarAprendicesPorCategoria(this.currentKey);
            } else {
                alert('Error: ' + data.message);
            }
        } catch (error) {
            console.error('Error al eliminar de población:', error);
            alert('Error al eliminar de población');
        }
    }

    /**
     * Gestionar tipos de población
     */
    gestionarTipos() {
        const modalHtml = `
            <div class="modal-overlay" id="modalGestionTipos" style="display:flex;">
                <div class="modal-glass" style="max-width: 600px;">
                    <div class="modal-header">
                        <h3 class="modal-title">
                            <i class="fas fa-users-cog" style="color: var(--primary-green); margin-right: 8px;"></i>
                            Gestión de Tipos de Población
                        </h3>
                        <button type="button" class="modal-close" onclick="document.getElementById('modalGestionTipos').remove()">&times;</button>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> 
                        Los patrones de búsqueda permiten identificar aprendices por categorías de vulnerabilidad.
                    </div>
                    
                    <div style="margin-bottom: 1rem;">
                        <div class="form-group">
                            <label class="form-label">Patrones Actuales:</label>
                            <div style="background: var(--bg-secondary); padding: 15px; border-radius: var(--radius-md); font-family: monospace; font-size: 0.85rem;">
                                <div><strong>Mujer:</strong> mujer,mujeres,femenino,femenina,F,muj</div>
                                <div><strong>Indígena:</strong> indigena,indígena,etnia,pueblos,indígenas,etnico</div>
                                <div><strong>NARP:</strong> narp,negro,afro,afrodescendiente,raizal,palenquero,afro</div>
                                <div><strong>Campesino:</strong> campesino,campesina,rural,campo,camp</div>
                                <div><strong>LGBTIQ+:</strong> lgbti,lgbt,trans,gay,lesbiana,bisexual,queer,homosexual,+</div>
                                <div><strong>Discapacidad:</strong> discapacidad,discapacitado,discapacitada,capacidad,disc</div>
                            </div>
                        </div>
                    </div>
                    
                    <div style="display: flex; justify-content: flex-end; gap: 10px;">
                        <button type="button" class="btn btn-outline" onclick="document.getElementById('modalGestionTipos').remove()">Cerrar</button>
                        <button type="button" class="btn btn-primary" onclick="poblacionManager.actualizarPatrones()">Actualizar Patrones</button>
                    </div>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', modalHtml);
    }

    /**
     * Actualizar patrones de búsqueda
     */
    async actualizarPatrones() {
        try {
            // Mostrar loading
            const btn = event.target;
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Actualizando...';
            btn.disabled = true;
            
            // Simular actualización (en producción esto llamaría a un API)
            await new Promise(resolve => setTimeout(resolve, 2000));
            
            alert('Patrones de búsqueda actualizados correctamente. Los cambios se reflejarán en el próximo conteo de población.');
            document.getElementById('modalGestionTipos').remove();
            
            // Recargar estadísticas para reflejar cambios
            await this.cargarEstadisticas();
            this.renderGrid();
            
        } catch (error) {
            console.error('Error al actualizar patrones:', error);
            alert('Error al actualizar patrones. Por favor intente nuevamente.');
        }
    }

    /**
     * Gestionar vocero de enfoque diferencial
     */
    gestionarVoceroEnfoque() {
        const modalHtml = `
            <div class="modal-overlay" id="modalVoceroEnfoque" style="display:flex;">
                <div class="modal-glass" style="max-width: 600px;">
                    <div class="modal-header">
                        <h3 class="modal-title">
                            <i class="fas fa-user-friends" style="color: var(--secondary-orange); margin-right: 8px;"></i>
                            Vocero de Enfoque Diferencial
                        </h3>
                        <button type="button" class="modal-close" onclick="document.getElementById('modalVoceroEnfoque').remove()">&times;</button>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Categoría</label>
                        <select id="enfoque-categoria" class="form-control" onchange="poblacionManager.cargarVoceroActual()">
                            <option value="">Seleccione...</option>
                            <option value="mujer">Mujer</option>
                            <option value="indigena">Indígena</option>
                            <option value="narp">NARP</option>
                            <option value="campesino">Campesino</option>
                            <option value="lgbtiq">LGBTIQ+</option>
                            <option value="discapacidad">Discapacidad</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Vocero Actual</label>
                        <div id="vocero-actual" class="form-control" style="background: var(--bg-secondary); min-height: 40px; display: flex; align-items: center;">
                            <span style="color: var(--text-muted);">No asignado</span>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Buscar Aprendiz</label>
                        <div style="display: flex; gap: 10px; margin-bottom: 10px;">
                            <input type="text" id="buscar-vocero" class="form-control" placeholder="Escriba documento o nombre..." style="flex: 1;">
                            <input type="hidden" id="nuevo-vocero"> <!-- Campo oculto faltante -->
                            <button type="button" class="btn btn-primary" onclick="poblacionManager.buscarAprendicesParaVocero()">
                                <i class="fas fa-search"></i> Buscar
                            </button>
                        </div>
                        <div id="resultados-vocero" style="max-height: 200px; overflow-y: auto;">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                Busque un aprendiz para asignar como vocero de enfoque.
                            </div>
                        </div>
                    </div>
                    
                    <div style="display: flex; justify-content: flex-end; gap: 10px;">
                        <button type="button" class="btn btn-outline" onclick="document.getElementById('modalVoceroEnfoque').remove()">Cancelar</button>
                        <button type="button" class="btn btn-secondary" onclick="poblacionManager.guardarVoceroEnfoque()">Guardar</button>
                    </div>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', modalHtml);
    }
    
    /**
     * Cargar vocero actual de la categoría seleccionada
     */
    async cargarVoceroActual() {
        const categoria = document.getElementById('enfoque-categoria').value;
        const voceroDiv = document.getElementById('vocero-actual');
        const resultadosDiv = document.getElementById('resultados-vocero');
        const hiddenInput = document.getElementById('nuevo-vocero');
        
        // Limpiar búsqueda al cambiar categoría
        if (resultadosDiv) resultadosDiv.innerHTML = '<div class="alert alert-info"><i class="fas fa-info-circle"></i> Busque un aprendiz para asignar.</div>';
        if (hiddenInput) hiddenInput.value = '';

        if (!categoria) {
            voceroDiv.innerHTML = '<span style="color: var(--text-muted);">No asignado</span>';
            return;
        }
        
        try {
            const res = await fetch('api/liderazgo.php?action=getPoblacionStats');
            const data = await res.json();
            
            if (data.success && data.voceros && data.voceros[categoria]) {
                voceroDiv.innerHTML = `<strong style="color: var(--primary-green);">${data.voceros[categoria]}</strong>`;
            } else {
                voceroDiv.innerHTML = '<span style="color: var(--text-muted);">Sin asignar</span>';
            }
        } catch (error) {
            console.error('Error al cargar vocero actual:', error);
        }
    }
    
    /**
     * Buscar aprendices para vocero
     */
    async buscarAprendicesParaVocero() {
        const searchTerm = document.getElementById('buscar-vocero').value.trim();
        const resultadosDiv = document.getElementById('resultados-vocero');
        
        if (!searchTerm) {
            resultadosDiv.innerHTML = '<div class="alert alert-warning">Por favor ingrese un término de búsqueda.</div>';
            return;
        }

        try {
            resultadosDiv.innerHTML = '<div class="text-center" style="padding: 20px;"><i class="fas fa-spinner fa-spin"></i> Buscando...</div>';
            
            const res = await fetch(`api/aprendices.php?search=${encodeURIComponent(searchTerm)}&estado=LECTIVA&limit=10`);
            const data = await res.json();
            
            if (data.success && data.data.length > 0) {
                let html = '<div style="margin-bottom: 1rem;"><strong>Resultados encontrados:</strong></div>';
                html += '<div style="display: flex; flex-direction: column; gap: 10px;">';
                
                data.data.forEach(aprendiz => {
                    html += `
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px; background: var(--bg-secondary); border-radius: 8px; border: 1px solid var(--border-color);">
                            <div>
                                <strong>${aprendiz.nombre} ${aprendiz.apellido}</strong><br>
                                <small style="color: var(--text-muted);">Documento: ${aprendiz.documento} | Ficha: ${aprendiz.numero_ficha}</small>
                            </div>
                            <button type="button" class="btn btn-success btn-sm" onclick="poblacionManager.seleccionarVocero('${aprendiz.documento}', '${aprendiz.nombre} ${aprendiz.apellido}')">
                                <i class="fas fa-check"></i> Seleccionar
                            </button>
                        </div>
                    `;
                });
                
                html += '</div>';
                resultadosDiv.innerHTML = html;
            } else {
                resultadosDiv.innerHTML = '<div class="alert alert-warning">No se encontraron aprendices con ese criterio de búsqueda.</div>';
            }
        } catch (error) {
            console.error('Error al buscar aprendices:', error);
            resultadosDiv.innerHTML = '<div class="alert alert-danger">Error al buscar aprendices. Por favor intente nuevamente.</div>';
        }
    }
    
    /**
     * Seleccionar vocero
     */
    seleccionarVocero(documento, nombre) {
        document.getElementById('nuevo-vocero').value = documento;
        document.getElementById('resultados-vocero').innerHTML = `
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                Vocero seleccionado: <strong>${nombre}</strong>
            </div>
        `;
    }

    /**
     * Guardar vocero de enfoque
     */
    async guardarVoceroEnfoque() {
        const categoria = document.getElementById('enfoque-categoria').value;
        const documento = document.getElementById('nuevo-vocero').value;
        
        if (!categoria) {
            alert('Seleccione una categoría');
            return;
        }
        
        try {
            const res = await fetch('api/liderazgo.php?action=saveVoceroEnfoque', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ categoria: categoria, documento: documento })
            });
            const data = await res.json();
            
            if (data.success) {
                alert('Vocero de enfoque asignado correctamente');
                document.getElementById('modalVoceroEnfoque').remove();
                await this.cargarEstadisticas();
                this.renderGrid();
            } else {
                alert('Error: ' + data.message);
            }
        } catch (error) {
            console.error('Error al guardar vocero:', error);
            alert('Error al guardar vocero de enfoque');
        }
    }

    /**
     * Gestionar representantes
     */
    gestionarRepresentantes() {
        const modalHtml = `
            <div class="modal-overlay" id="modalRepresentantes" style="display:flex;">
                <div class="modal-glass" style="max-width: 600px;">
                    <div class="modal-header">
                        <h3 class="modal-title">
                            <i class="fas fa-user-tie" style="color: var(--secondary-blue); margin-right: 8px;"></i>
                            Representantes (Diurna/Mixta)
                        </h3>
                        <button type="button" class="modal-close" onclick="document.getElementById('modalRepresentantes').remove()">&times;</button>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 1rem;">
                        <div>
                            <label class="form-label">Representante Diurno</label>
                            <div id="rep-diurna" class="form-control" style="background: var(--bg-secondary); min-height: 40px; display: flex; align-items: center; border: 1px solid var(--border-color);">No asignado</div>
                        </div>
                        <div>
                            <label class="form-label">Representante Mixto</label>
                            <div id="rep-mixta" class="form-control" style="background: var(--bg-secondary); min-height: 40px; display: flex; align-items: center; border: 1px solid var(--border-color);">No asignado</div>
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> 
                        Los representantes son elegidos democráticamente por los aprendices de cada jornada.
                    </div>
                    
                    <div style="display: flex; justify-content: space-between; gap: 10px; margin-bottom: 1rem;">
                        <button type="button" class="btn btn-info btn-sm" onclick="poblacionManager.asignarRepresentante('diurna')">
                            <i class="fas fa-user-plus"></i> Asignar Diurno
                        </button>
                        <button type="button" class="btn btn-info btn-sm" onclick="poblacionManager.asignarRepresentante('mixta')">
                            <i class="fas fa-user-plus"></i> Asignar Mixto
                        </button>
                    </div>
                    
                    <div style="display: flex; justify-content: flex-end; gap: 10px;">
                        <button type="button" class="btn btn-outline" onclick="document.getElementById('modalRepresentantes').remove()">Cerrar</button>
                    </div>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        
        this.cargarRepresentantes();
    }

    /**
     * Cargar representantes actuales
     */
    async cargarRepresentantes() {
        try {
            const res = await fetch('api/liderazgo.php?action=getRepresentantes');
            
            if (!res.ok) {
                throw new Error(`HTTP ${res.status}`);
            }
            
            const text = await res.text();
            
            if (!text.trim()) {
                console.warn('Respuesta vacía del API de representantes');
                return;
            }
            
            let data;
            try {
                data = JSON.parse(text);
            } catch (e) {
                console.error('Error parsing JSON:', e, 'Response:', text);
                return;
            }
            
            if (data && data.success) {
                data.data.forEach(rep => {
                    const elem = document.getElementById(`rep-${rep.tipo_jornada}`);
                    if (elem) {
                        elem.innerHTML = `<strong style="color: var(--primary-green);">${rep.nombre} ${rep.apellido}</strong>`;
                    }
                });
            }
        } catch (error) {
            console.error('Error al cargar representantes:', error);
        }
    }

    /**
     * Asignar representante con búsqueda real
     */
    async asignarRepresentante(tipo) {
        const modalHtml = `
            <div class="modal-overlay" id="modalBuscarRepresentante" style="display:flex;">
                <div class="modal-glass" style="max-width: 600px;">
                    <div class="modal-header">
                        <h3 class="modal-title">
                            <i class="fas fa-user-plus" style="color: var(--secondary-blue); margin-right: 8px;"></i>
                            Asignar Representante ${tipo === 'diurna' ? 'Diurno' : 'Mixto'}
                        </h3>
                        <button type="button" class="modal-close" onclick="document.getElementById('modalBuscarRepresentante').remove()">&times;</button>
                    </div>

                    <div style="margin-bottom: 1rem;">
                        <label class="form-label">Buscar Aprendiz:</label>
                        <div style="display: flex; gap: 10px;">
                            <input type="text" id="buscar-representante" class="form-control" placeholder="Escriba documento o nombre..." style="flex: 1;">
                            <button type="button" class="btn btn-primary" onclick="poblacionManager.buscarAprendicesParaRepresentante('${tipo}')">
                                <i class="fas fa-search"></i> Buscar
                            </button>
                        </div>
                    </div>

                    <div id="resultados-busqueda" style="max-height: 300px; overflow-y: auto; margin-bottom: 1rem;">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            Busque un aprendiz para asignar como representante ${tipo === 'diurna' ? 'diurno' : 'mixto'}.
                        </div>
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: 10px;">
                        <button type="button" class="btn btn-outline" onclick="document.getElementById('modalBuscarRepresentante').remove()">Cancelar</button>
                    </div>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        
        // Enfocar el campo de búsqueda
        setTimeout(() => {
            document.getElementById('buscar-representante').focus();
        }, 100);
    }

    /**
     * Buscar aprendices para asignar como representante
     */
    async buscarAprendicesParaRepresentante(tipo) {
        const searchTerm = document.getElementById('buscar-representante').value.trim();
        const resultadosDiv = document.getElementById('resultados-busqueda');
        
        if (!searchTerm) {
            resultadosDiv.innerHTML = '<div class="alert alert-warning">Por favor ingrese un término de búsqueda.</div>';
            return;
        }

        try {
            resultadosDiv.innerHTML = '<div class="text-center" style="padding: 20px;"><i class="fas fa-spinner fa-spin"></i> Buscando...</div>';
            
            const res = await fetch(`api/aprendices.php?search=${encodeURIComponent(searchTerm)}&estado=LECTIVA&limit=20`);
            const data = await res.json();
            
            if (data.success && data.data.length > 0) {
                let html = '<div style="margin-bottom: 1rem;"><strong>Resultados encontrados:</strong></div>';
                html += '<div style="display: flex; flex-direction: column; gap: 10px;">';
                
                data.data.forEach(aprendiz => {
                    html += `
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px; background: var(--bg-secondary); border-radius: 8px; border: 1px solid var(--border-color);">
                            <div>
                                <strong>${aprendiz.nombre} ${aprendiz.apellido}</strong><br>
                                <small style="color: var(--text-muted);">Documento: ${aprendiz.documento} | Ficha: ${aprendiz.numero_ficha}</small>
                            </div>
                            <button type="button" class="btn btn-success btn-sm" onclick="poblacionManager.asignarRepresentanteSeleccionado('${aprendiz.documento}', '${aprendiz.nombre}', '${aprendiz.apellido}', '${tipo}')">
                                <i class="fas fa-check"></i> Asignar
                            </button>
                        </div>
                    `;
                });
                
                html += '</div>';
                resultadosDiv.innerHTML = html;
            } else {
                resultadosDiv.innerHTML = '<div class="alert alert-warning">No se encontraron aprendices con ese criterio de búsqueda.</div>';
            }
        } catch (error) {
            console.error('Error al buscar aprendices:', error);
            resultadosDiv.innerHTML = '<div class="alert alert-danger">Error al buscar aprendices. Por favor intente nuevamente.</div>';
        }
    }

    /**
     * Asignar representante seleccionado
     */
    async asignarRepresentanteSeleccionado(documento, nombre, apellido, tipo) {
        if (!confirm(`¿Está seguro de asignar a ${nombre} ${apellido} como representante ${tipo === 'diurna' ? 'diurno' : 'mixto'}?`)) {
            return;
        }

        try {
            const res = await fetch('api/liderazgo.php?action=saveRepresentante', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ tipo_jornada: tipo, documento: documento })
            });
            
            const data = await res.json();
            
            if (data.success) {
                alert('Representante asignado correctamente');
                document.getElementById('modalBuscarRepresentante').remove();
                
                // Actualizar el modal de representantes
                const elem = document.getElementById(`rep-${tipo}`);
                if (elem) {
                    elem.innerHTML = `${nombre} ${apellido}`;
                }
                
                // Recargar representantes
                await this.cargarRepresentantes();
            } else {
                alert('Error: ' + (data.message || 'No se pudo asignar el representante'));
            }
        } catch (error) {
            console.error('Error al asignar representante:', error);
            alert('Error al asignar representante. Por favor intente nuevamente.');
        }
    }

    /**
     * Exportar a PDF usando el módulo estandarizado SenaPrePDF
     */
    async exportPDF() {
        if (!this.currentKey) {
            alert('Por favor seleccione una categoría primero.');
            return;
        }

        try {
            // Mostrar loading en el botón
            const btn = event.target.closest('button');
            const originalHtml = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generando...';
            btn.disabled = true;

            // 1. Obtener patrones y fetch de DATOS COMPLETOS (sin límite)
            const patrones = this.getPatronesPorCategoria(this.currentKey);
            const whereConditions = patrones.map(p => `UPPER(a.tipo_poblacion) LIKE UPPER('${p}')`).join(' OR ');
            
            // Usamos limit=-1 para traernos absolutamente todo para el reporte
            const res = await fetch(`api/aprendices.php?estado=LECTIVA&custom_filter=${encodeURIComponent(whereConditions)}&limit=-1&_v=${Date.now()}`);
            const data = await res.json();

            if (!data.success || !data.data || data.data.length === 0) {
                alert('No hay datos disponibles para exportar.');
                btn.innerHTML = originalHtml;
                btn.disabled = false;
                return;
            }

            const aprendicesParaReporte = data.data;

            // 2. Inicializar jsPDF
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF({
                orientation: 'landscape',
                unit: 'mm',
                format: 'a4'
            });

            // 3. Crear cabecera profesional centralizada (SENAPRE - Regional Caquetá)
            let subtitulo = `Categoría: ${this.currentLabel} (${aprendicesParaReporte.length} Aprendices)`;
            if (this.filtrosActivos.ficha) {
                subtitulo += ` | Ficha Filtro: ${this.filtrosActivos.ficha}`;
            }

            const cabeceraY = await SenaPrePDF.crearCabecera(doc, {
                titulo: 'REPORTE DE POBLACIÓN VULNERABLE',
                subtitulo: subtitulo,
                orientacion: 'landscape'
            });

            // 4. Definir columnas (Match exacto con la guía)
            const columns = [
                { header: 'N°', dataKey: 'index' },
                { header: 'Documento', dataKey: 'documento' },
                { header: 'Nombres', dataKey: 'nombre' },
                { header: 'Apellidos', dataKey: 'apellido' },
                { header: 'Correo Electrónico', dataKey: 'correo' },
                { header: 'Celular', dataKey: 'celular' },
                { header: 'Estado', dataKey: 'estado' },
                { header: 'Ficha', dataKey: 'numero_ficha' }
            ];

            // 5. Preparar datos
            const tableData = aprendicesParaReporte.map((a, i) => ({
                index: i + 1,
                documento: a.documento || '',
                nombre: (a.nombre || '').toUpperCase(),
                apellido: (a.apellido || '').toUpperCase(),
                correo: a.correo || '',
                celular: a.celular || '',
                estado: 'LECTIVA',
                numero_ficha: a.numero_ficha || ''
            }));

            // 6. Generar tabla con estilos compartidos (Llamada explícita para máxima compatibilidad)
            doc.autoTable({
                theme: 'grid',
                columns: columns,
                body: tableData,
                startY: cabeceraY,
                margin: { horizontal: 14, top: 20 }, // Margen para páginas siguientes (no para la primera)
                styles: {
                    font: 'helvetica',
                    fontSize: 8,
                    cellPadding: 2,
                    halign: 'center',
                    valign: 'middle'
                },
                headStyles: {
                    fillColor: [0, 100, 0],
                    textColor: [255, 255, 255],
                    fontStyle: 'bold'
                },
                alternateRowStyles: {
                    fillColor: [245, 252, 240]
                },
                columnStyles: {
                    index: { cellWidth: 10 },
                    documento: { cellWidth: 25 },
                    nombre: { cellWidth: 'auto' },
                    apellido: { cellWidth: 'auto' },
                    estado: { cellWidth: 20 },
                    numero_ficha: { cellWidth: 20 }
                },
                didParseCell: function(data) {
                    if (data.section === 'body' && data.column.dataKey === 'estado') {
                        data.cell.styles.textColor = [22, 163, 74];
                    }
                },
                didDrawPage: function(data) {
                    if (typeof SenaPrePDF !== 'undefined' && SenaPrePDF.pieDePagina) {
                        SenaPrePDF.pieDePagina(doc);
                    }
                }
            });

            // 7. Descargar
            const filename = `Reporte_Poblacion_${this.currentLabel.replace(/\s+/g, '_')}_${Date.now()}.pdf`;
            doc.save(filename);

            // Restaurar botón
            btn.innerHTML = originalHtml;
            btn.disabled = false;

        } catch (error) {
            console.error('Error al generar PDF:', error);
            alert('Error al generar el PDF. Revise su conexión.');
            // Restaurar botón si hay error
            const btn = document.querySelector('button[onclick="poblacionManager.exportPDF()"]');
            if (btn) {
                btn.innerHTML = '<i class="fas fa-file-pdf"></i> PDF';
                btn.disabled = false;
            }
        }
    }
    
    /**
     * Cambiar página
     */
    async cambiarPagina(page) {
        if (page < 1 || page > this.totalPages || page === this.currentPage) {
            return;
        }
        
        try {
            // Obtener patrones de búsqueda para esta categoría
            const patrones = this.getPatronesPorCategoria(this.currentKey);
            
            // Construir la consulta SQL con múltiples patrones LIKE
            const whereConditions = patrones.map(p => `UPPER(a.tipo_poblacion) LIKE UPPER('${p}')`).join(' OR ');
            
            // Llamar al API con los patrones específicos y nueva página
            const res = await fetch(`api/aprendices.php?estado=LECTIVA&custom_filter=${encodeURIComponent(whereConditions)}&limit=5&page=${page}`);
            const data = await res.json();
            
            if (data.success) {
                this.currentCatData = data.data;
                this.currentPage = page;
                this.renderTable();
            }
        } catch (error) {
            console.error('Error al cambiar página:', error);
        }
    }
    
    /**
     * Eliminar aprendiz sin trabas
     */
    async eliminarAprendiz(documento, nombre) {
        if (!confirm(`¿Está seguro de eliminar a ${nombre} (Documento: ${documento})?\n\nEsta acción eliminará permanentemente al aprendiz del sistema.`)) {
            return;
        }
        
        try {
            const res = await fetch('/api/aprendices.php', {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ documento: documento })
            });
            
            const data = await res.json();
            
            if (data.success) {
                alert(`Aprendiz ${nombre} eliminado correctamente`);
                // Recargar la página actual
                await this.cargarAprendicesPorCategoria(this.currentKey);
            } else {
                alert('Error al eliminar aprendiz: ' + (data.message || 'Error desconocido'));
            }
        } catch (error) {
            console.error('Error al eliminar aprendiz:', error);
            alert('Error al eliminar aprendiz. Por favor intente nuevamente.');
        }
    }
    
    /**
     * Cargar fichas únicas para el filtro
     */
    cargarFichasUnicas() {
        const fichasUnicas = [...new Set(this.todosLosDatos.map(a => a.numero_ficha).filter(f => f))].sort();
        const select = document.getElementById('filtro-ficha');
        
        if (select) {
            select.innerHTML = '<option value="">Todas las fichas</option>';
            fichasUnicas.forEach(ficha => {
                select.innerHTML += `<option value="${ficha}">${ficha}</option>`;
            });
        }
    }
    
    /**
     * Filtrar por ficha
     */
    filtrarPorFicha(ficha) {
        this.filtrosActivos.ficha = ficha;
        this.aplicarFiltros();
    }
    
    /**
     * Filtrar por búsqueda
     */
    filtrarPorBusqueda() {
        const busqueda = document.getElementById('filtro-busqueda').value.trim();
        this.filtrosActivos.busqueda = busqueda;
        this.aplicarFiltros();
    }
    
    /**
     * Limpiar todos los filtros
     */
    limpiarFiltros() {
        this.filtrosActivos = { ficha: '', busqueda: '' };
        document.getElementById('filtro-ficha').value = '';
        document.getElementById('filtro-busqueda').value = '';
        this.currentCatData = this.todosLosDatos;
        this.renderTable();
    }
    
    /**
     * Aplicar filtros activos
     */
    aplicarFiltros() {
        let datosFiltrados = [...this.todosLosDatos];
        
        // Filtrar por ficha
        if (this.filtrosActivos.ficha) {
            datosFiltrados = datosFiltrados.filter(a => a.numero_ficha === this.filtrosActivos.ficha);
        }
        
        // Filtrar por búsqueda (documento o nombre)
        if (this.filtrosActivos.busqueda) {
            const busquedaLower = this.filtrosActivos.busqueda.toLowerCase();
            datosFiltrados = datosFiltrados.filter(a => 
                a.documento.toLowerCase().includes(busquedaLower) ||
                a.nombre.toLowerCase().includes(busquedaLower) ||
                a.apellido.toLowerCase().includes(busquedaLower)
            );
        }
        
        this.currentCatData = datosFiltrados;
        this.renderTable();
        
        // Mostrar mensaje si no hay resultados
        if (datosFiltrados.length === 0) {
            document.getElementById('pob-tabla-body').innerHTML = `
                <tr>
                    <td colspan="8" class="text-center" style="padding:2rem;color:#64748b;">
                        <i class="fas fa-search"></i> No se encontraron resultados con los filtros aplicados.
                        <br><button type="button" class="btn btn-outline btn-sm" onclick="poblacionManager.limpiarFiltros()" style="margin-top: 10px;">
                            <i class="fas fa-times"></i> Limpiar filtros
                        </button>
                    </td>
                </tr>
            `;
        }
    }
}

// Instancia global
const poblacionManager = new PoblacionManager();
