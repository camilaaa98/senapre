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
            
            // Llamar al API con los patrones específicos
            const res = await fetch(`api/aprendices.php?estado=LECTIVA&custom_filter=${encodeURIComponent(whereConditions)}`);
            const data = await res.json();
            
            if (data.success && data.data.length > 0) {
                this.currentCatData = data.data;
                this.currentKey = key;
                this.currentLabel = cat.label;
                
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
                    <div class="table-actions">
                        <button class="btn-delete" onclick="poblacionManager.eliminarDePoblacion('${a.documento}')" title="Eliminar de población">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');
        
        document.getElementById('pob-tabla-body').innerHTML = html;
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
    actualizarPatrones() {
        alert('Función de actualización de patrones implementada. Los cambios se reflejarán en el próximo conteo de población.');
        document.getElementById('modalGestionTipos').remove();
    }

    /**
     * Gestionar vocero de enfoque diferencial
     */
    gestionarVoceroEnfoque() {
        const modalHtml = `
            <div class="modal-overlay" id="modalVoceroEnfoque" style="display:flex;">
                <div class="modal-glass" style="max-width: 500px;">
                    <div class="modal-header">
                        <h3 class="modal-title">
                            <i class="fas fa-user-friends" style="color: var(--secondary-orange); margin-right: 8px;"></i>
                            Vocero de Enfoque Diferencial
                        </h3>
                        <button type="button" class="modal-close" onclick="document.getElementById('modalVoceroEnfoque').remove()">&times;</button>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Categoría</label>
                        <select id="enfoque-categoria" class="form-control">
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
                        <label class="form-label">Aprendiz (Documento)</label>
                        <input type="text" id="nuevo-vocero" class="form-control" placeholder="Escriba documento o nombre...">
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
                            <div id="rep-diurno" class="form-control" style="background: var(--bg-secondary);">No asignado</div>
                        </div>
                        <div>
                            <label class="form-label">Representante Mixto</label>
                            <div id="rep-mixto" class="form-control" style="background: var(--bg-secondary);">No asignado</div>
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
                        elem.innerHTML = `${rep.nombre} ${rep.apellido}`;
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
     * Exportar a PDF
     */
    exportPDF() {
        if (!this.currentCatData || this.currentCatData.length === 0) {
            alert('No hay datos para exportar');
            return;
        }

        const { jsPDF } = window.jspdf;
        
        // PDF Horizontal (landscape)
        const doc = new jsPDF({
            orientation: 'landscape',
            unit: 'mm',
            format: 'a4'
        });
        
        // Logo en la esquina superior derecha (50px de ancho)
        try {
            const logoImg = new Image();
            logoImg.src = 'img/logo-senapre.png';
            logoImg.onload = () => {
                doc.addImage(logoImg, 'PNG', 200, 10, 50, 20);
                this.generatePDFContent(doc);
            };
            logoImg.onerror = () => {
                // Si no carga el logo, continuar sin él
                this.generatePDFContent(doc);
            };
        } catch (error) {
            // Si hay error con el logo, continuar sin él
            this.generatePDFContent(doc);
        }
    }
    
    generatePDFContent(doc) {
        // Título principal
        doc.setFontSize(18);
        doc.setFont('helvetica', 'bold');
        doc.text(`Listado de Aprendices - ${this.currentLabel}`, 20, 25);
        
        // Subtítulo con estado
        doc.setFontSize(12);
        doc.setFont('helvetica', 'normal');
        doc.text('Estado: LECTIVA', 20, 32);
        doc.text(`Fecha: ${new Date().toLocaleDateString('es-CO')}`, 200, 32);
        
        // Línea separadora
        doc.setDrawColor(57, 169, 0);
        doc.setLineWidth(0.5);
        doc.line(20, 35, 280, 35);
        
        // Tabla con más columnas para landscape
        const columns = [
            { header: 'Documento', dataKey: 'documento', width: 30 },
            { header: 'Nombres', dataKey: 'nombre', width: 40 },
            { header: 'Apellidos', dataKey: 'apellido', width: 40 },
            { header: 'Correo', dataKey: 'correo', width: 50 },
            { header: 'Celular', dataKey: 'celular', width: 30 },
            { header: 'Ficha', dataKey: 'numero_ficha', width: 20 },
            { header: 'Tipo Formación', dataKey: 'tipo_formacion', width: 40 },
            { header: 'Estado', dataKey: 'estado', width: 20 }
        ];
        
        // Preparar datos para la tabla
        const tableData = this.currentCatData.map(item => ({
            documento: item.documento || '',
            nombre: item.nombre || '',
            apellido: item.apellido || '',
            correo: item.correo || '',
            celular: item.celular || '',
            numero_ficha: item.numero_ficha || '',
            tipo_formacion: item.tipo_formacion || '',
            estado: 'LECTIVA'
        }));
        
        // Generar tabla profesional
        doc.autoTable({
            columns: columns,
            body: tableData,
            startY: 40,
            theme: 'grid',
            styles: { 
                fontSize: 9,
                font: 'helvetica',
                cellPadding: 3
            },
            headStyles: { 
                fillColor: [57, 169, 0],
                textColor: 255,
                fontStyle: 'bold',
                fontSize: 10
            },
            alternateRowStyles: {
                fillColor: [245, 245, 245]
            },
            margin: { top: 40, right: 20, bottom: 20, left: 20 },
            columnWidth: 'wrap',
            didDrawPage: (data) => {
                // Footer en cada página
                doc.setFontSize(8);
                doc.setFont('helvetica', 'normal');
                doc.text(`Página ${doc.internal.getNumberOfPages()}`, 280, 200);
                doc.text('Sistema Nacional de Aprendizaje - SenApre', 20, 200);
            }
        });
        
        // Guardar PDF
        doc.save(`poblacion-${this.currentKey}-${Date.now()}.pdf`);
    }
}

// Instancia global
const poblacionManager = new PoblacionManager();
