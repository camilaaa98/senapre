/**
 * Gestión de Asignaciones - Admin
 * Sistema de asignación con calendario múltiple y horarios automáticos
 * Aplicando principios SOLID y mejores prácticas
 */

// Constants (Single Responsibility)
const ASSIGNMENT_CONSTANTS = {
    API_ENDPOINTS: {
        ASIGNACIONES: 'api/asignaciones.php',
        INSTRUCTORES: 'api/instructores.php',
        FICHAS: 'api/fichas.php'
    },
    HORARIOS_POR_JORNADA: {
        'Diurna': { inicio: '06:00', fin: '12:00' },
        'Diurna - Cerrado': { inicio: '06:00', fin: '12:00' },
        'Tarde': { inicio: '13:00', fin: '18:00' },
        'Tarde - Cerrado': { inicio: '13:00', fin: '18:00' },
        'Noche': { inicio: '18:00', fin: '00:00' },
        'Noche - Cerrado': { inicio: '18:00', fin: '00:00' },
        'Nocturna': { inicio: '18:00', fin: '00:00' },
        'Mixta': { inicio: '06:00', fin: '18:00' },
        'Fin de semana': { inicio: '08:00', fin: '16:00' }
    },
    MESSAGES: {
        SUCCESS: {
            CREATED: 'Asignación creada correctamente',
            UPDATED: 'Asignación actualizada correctamente',
            DELETED: 'Asignación eliminada correctamente',
            EXPORTED: 'Archivo exportado correctamente'
        },
        ERROR: {
            GENERIC: 'Error en la operación',
            NETWORK: 'Error de conexión',
            VALIDATION: 'Error de validación',
            NO_DATA: 'No hay datos disponibles'
        },
        WARNING: {
            NO_SELECTION: 'Debe seleccionar instructor y ficha',
            NO_DATES: 'Debe seleccionar al menos una fecha'
        }
    }
};

// State Management (Single Responsibility)
class AssignmentState {
    constructor() {
        this.fichasData = [];
        this.instructoresData = [];
        this.asignacionesData = [];
        this.calendarioFlatpickr = null;
        this.currentAssignment = null;
    }
}

// API Service (Single Responsibility)
class AssignmentAPI {
    static async fetch(endpoint, options = {}) {
        try {
            const response = await fetch(endpoint, {
                headers: {
                    'Content-Type': 'application/json',
                    ...options.headers
                },
                ...options
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            return await response.json();
        } catch (error) {
            console.error(`API Error (${endpoint}):`, error);
            throw error;
        }
    }
    
    static async getAsignaciones() {
        return this.fetch(ASSIGNMENT_CONSTANTS.API_ENDPOINTS.ASIGNACIONES);
    }
    
    static async getInstructores() {
        return this.fetch(ASSIGNMENT_CONSTANTS.API_ENDPOINTS.INSTRUCTORES);
    }
    
    static async getFichas() {
        return this.fetch(ASSIGNMENT_CONSTANTS.API_ENDPOINTS.FICHAS);
    }
    
    static async saveAssignment(data) {
        return this.fetch(ASSIGNMENT_CONSTANTS.API_ENDPOINTS.ASIGNACIONES, {
            method: 'POST',
            body: JSON.stringify(data)
        });
    }
    
    static async deleteAssignment(id) {
        return this.fetch(`${ASSIGNMENT_CONSTANTS.API_ENDPOINTS.ASIGNACIONES}?id=${id}`, {
            method: 'DELETE'
        });
    }
}

// Notification Service (Single Responsibility)
class NotificationService {
    static show(message, type = 'success') {
        try {
            // Remove existing notifications
            const existingNotifications = document.querySelectorAll('.notification');
            existingNotifications.forEach(notif => notif.remove());
            
            // Create notification element
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'warning' ? 'exclamation-triangle' : 'times-circle'}"></i>
                <span>${message}</span>
            `;
            
            // Add to DOM
            document.body.appendChild(notification);
            
            // Auto-remove after 4 seconds
            setTimeout(() => {
                notification.style.animation = 'slideOutNotification 0.3s ease-in';
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.remove();
                    }
                }, 300);
            }, 4000);
            
            // Click to close manually
            notification.addEventListener('click', () => {
                notification.style.animation = 'slideOutNotification 0.3s ease-in';
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.remove();
                    }
                }, 300);
            });
            
        } catch (error) {
            console.error('Error mostrando notificación:', error);
            alert(message); // Fallback
        }
    }
}

// Validation Service (Single Responsibility)
class ValidationService {
    static validateAssignment(data) {
        const errors = [];
        
        if (!data.id_instructor) {
            errors.push('Debe seleccionar un instructor');
        }
        
        if (!data.id_ficha) {
            errors.push('Debe seleccionar una ficha');
        }
        
        if (!data.fechas || data.fechas.length === 0) {
            errors.push('Debe seleccionar al menos una fecha');
        }
        
        return {
            isValid: errors.length === 0,
            errors
        };
    }
}

// Export Service (Single Responsibility)
class ExportService {
    static async exportToExcel(data, filename = 'asignaciones_senapre') {
        try {
            if (!data || data.length === 0) {
                NotificationService.show(ASSIGNMENT_CONSTANTS.MESSAGES.ERROR.NO_DATA, 'warning');
                return;
            }
            
            if (typeof XLSX === 'undefined') {
                throw new Error('Librería XLSX no disponible');
            }
            
            const ws = XLSX.utils.json_to_sheet(data);
            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, 'Asignaciones');
            XLSX.writeFile(wb, filename + '.xlsx');
            
            NotificationService.show(ASSIGNMENT_CONSTANTS.MESSAGES.SUCCESS.EXPORTED, 'success');
        } catch (error) {
            console.error('Error exportando Excel:', error);
            NotificationService.show(ASSIGNMENT_CONSTANTS.MESSAGES.ERROR.GENERIC, 'error');
        }
    }
    
    static async exportToPDF(data, filename = 'asignaciones_senapre') {
        try {
            if (!data || data.length === 0) {
                NotificationService.show(ASSIGNMENT_CONSTANTS.MESSAGES.ERROR.NO_DATA, 'warning');
                return;
            }
            
            if (typeof jsPDF === 'undefined') {
                throw new Error('Librería jsPDF no disponible');
            }
            
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            
            // Configuración del documento
            doc.setFontSize(16);
            doc.text('Reporte de Asignaciones - SenApre', 20, 20);
            
            // Fecha de generación
            doc.setFontSize(10);
            doc.text(`Generado: ${new Date().toLocaleString('es-CO')}`, 20, 30);
            
            // Tabla simplificada
            doc.setFontSize(12);
            let y = 50;
            
            data.slice(0, 10).forEach((assignment, index) => {
                if (y > 250) {
                    doc.addPage();
                    y = 20;
                }
                
                doc.text(`Ficha: ${assignment.ficha || 'N/A'}`, 20, y);
                doc.text(`Instructor: ${assignment.instructor || 'N/A'}`, 20, y + 8);
                doc.text(`Fechas: ${assignment.fechas || 'N/A'}`, 20, y + 16);
                y += 30;
            });
            
            doc.save(filename + '.pdf');
            NotificationService.show(ASSIGNMENT_CONSTANTS.MESSAGES.SUCCESS.EXPORTED, 'success');
        } catch (error) {
            console.error('Error exportando PDF:', error);
            NotificationService.show(ASSIGNMENT_CONSTANTS.MESSAGES.ERROR.GENERIC, 'error');
        }
    }
    
    static async exportToCSV(data, filename = 'asignaciones_senapre') {
        try {
            if (!data || data.length === 0) {
                NotificationService.show(ASSIGNMENT_CONSTANTS.MESSAGES.ERROR.NO_DATA, 'warning');
                return;
            }
            
            const headers = Object.keys(data[0] || {});
            const csvContent = [
                headers.join(','),
                ...data.map(row => 
                    headers.map(header => {
                        const value = row[header] || '';
                        const escaped = String(value).replace(/"/g, '""');
                        return `"${escaped}"`;
                    }).join(',')
                )
            ].join('\n');
            
            const blob = new Blob(['\ufeff' + csvContent], { 
                type: 'text/csv;charset=utf-8;' 
            });
            
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            
            link.setAttribute('href', url);
            link.setAttribute('download', filename + '.csv');
            link.style.visibility = 'hidden';
            
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            NotificationService.show(ASSIGNMENT_CONSTANTS.MESSAGES.SUCCESS.EXPORTED, 'success');
        } catch (error) {
            console.error('Error exportando CSV:', error);
            NotificationService.show(ASSIGNMENT_CONSTANTS.MESSAGES.ERROR.GENERIC, 'error');
        }
    }
}

// Main Assignment Controller (Single Responsibility)
class AssignmentController {
    constructor() {
        this.state = new AssignmentState();
        this.init();
    }
    
    async init() {
        try {
            await this.loadInitialData();
            this.initializeCalendar();
            this.setupEventListeners();
        } catch (error) {
            console.error('Error initializing AssignmentController:', error);
            NotificationService.show(ASSIGNMENT_CONSTANTS.MESSAGES.ERROR.GENERIC, 'error');
        }
    }
    
    async loadInitialData() {
        try {
            const [asignacionesResponse, instructoresResponse, fichasResponse] = await Promise.all([
                AssignmentAPI.getAsignaciones(),
                AssignmentAPI.getInstructores(),
                AssignmentAPI.getFichas()
            ]);
            
            if (asignacionesResponse.success) {
                this.state.asignacionesData = asignacionesResponse.data;
                this.renderAsignaciones();
            }
            
            if (instructoresResponse.success) {
                this.state.instructoresData = instructoresResponse.data;
                this.populateInstructorSelect();
            }
            
            if (fichasResponse.success) {
                this.state.fichasData = fichasResponse.data;
                this.populateFichaSelect();
            }
            
        } catch (error) {
            console.error('Error loading initial data:', error);
            NotificationService.show(ASSIGNMENT_CONSTANTS.MESSAGES.ERROR.NETWORK, 'error');
        }
    }
    
    initializeCalendar() {
        try {
            this.state.calendarioFlatpickr = flatpickr("#fechasAsignadas", {
                mode: "multiple",
                dateFormat: "Y-m-d",
                locale: "es",
                minDate: "today",
                inline: false,
                onChange: (selectedDates, dateStr, instance) => {
                    console.log('Fechas seleccionadas:', selectedDates);
                }
            });
        } catch (error) {
            console.error('Error initializing calendar:', error);
        }
    }
    
    setupEventListeners() {
        // Search filter
        const searchInput = document.getElementById('filtroSearch');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                this.filterAsignaciones(e.target.value);
            });
        }
        
        // Date filter
        const dateFilter = document.getElementById('filtroFecha');
        if (dateFilter) {
            dateFilter.addEventListener('change', (e) => {
                this.filterAsignacionesByDate(e.target.value);
            });
        }
    }
    
    populateInstructorSelect() {
        const select = document.getElementById('selectInstructor');
        if (!select) return;
        
        const options = this.state.instructoresData.map(instructor => 
            `<option value="${instructor.id_usuario}">${instructor.nombre} ${instructor.apellido}</option>`
        ).join('');
        
        select.innerHTML = '<option value="">Seleccione un instructor...</option>' + options;
    }
    
    populateFichaSelect() {
        const select = document.getElementById('selectFicha');
        if (!select) return;
        
        const options = this.state.fichasData.map(ficha => 
            `<option value="${ficha.numero_ficha}">${ficha.numero_ficha} - ${ficha.nombre_programa}</option>`
        ).join('');
        
        select.innerHTML = '<option value="">Seleccione una ficha...</option>' + options;
    }
    
    renderAsignaciones(asignaciones = this.state.asignacionesData) {
        const tbody = document.getElementById('tablaAsignaciones');
        if (!tbody) return;
        
        if (!asignaciones || asignaciones.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="5" style="text-align:center; padding: 30px;">
                        <i class="fas fa-calendar-times" style="font-size: 48px; color: #9ca3af; margin-bottom: 16px; display: block;"></i>
                        No hay asignaciones registradas
                    </td>
                </tr>
            `;
            return;
        }
        
        const rows = asignaciones.map(assignment => `
            <tr>
                <td>${assignment.ficha || 'N/A'}</td>
                <td>${assignment.instructor || 'N/A'}</td>
                <td>${assignment.fechas || 'N/A'}</td>
                <td>${assignment.horario || 'N/A'}</td>
                <td>
                    <button onclick="assignmentController.editAssignment(${assignment.id})" class="btn-action edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button onclick="assignmentController.deleteAssignment(${assignment.id})" class="btn-action delete">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `).join('');
        
        tbody.innerHTML = rows;
    }
    
    filterAsignaciones(searchTerm) {
        const filtered = this.state.asignacionesData.filter(assignment => 
            assignment.ficha?.toLowerCase().includes(searchTerm.toLowerCase()) ||
            assignment.instructor?.toLowerCase().includes(searchTerm.toLowerCase())
        );
        this.renderAsignaciones(filtered);
    }
    
    filterAsignacionesByDate(date) {
        if (!date) {
            this.renderAsignaciones();
            return;
        }
        
        const filtered = this.state.asignacionesData.filter(assignment => 
            assignment.fechas && assignment.fechas.includes(date)
        );
        this.renderAsignaciones(filtered);
    }
    
    async saveAssignment(event) {
        event.preventDefault();
        
        try {
            const formData = new FormData(event.target);
            const data = {
                id_instructor: formData.get('id_instructor') || document.getElementById('selectInstructor').value,
                id_ficha: formData.get('id_ficha') || document.getElementById('selectFicha').value,
                fechas: this.state.calendarioFlatpickr.selectedDates.map(date => 
                    date.toISOString().split('T')[0]
                )
            };
            
            // Validation
            const validation = ValidationService.validateAssignment(data);
            if (!validation.isValid) {
                validation.errors.forEach(error => {
                    NotificationService.show(error, 'warning');
                });
                return;
            }
            
            // Show loading
            const submitBtn = event.target.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
            submitBtn.disabled = true;
            
            // Save assignment
            const response = await AssignmentAPI.saveAssignment(data);
            
            if (response.success) {
                NotificationService.show(ASSIGNMENT_CONSTANTS.MESSAGES.SUCCESS.CREATED, 'success');
                this.loadInitialData(); // Reload data
                this.closeModal();
            } else {
                NotificationService.show(response.message || ASSIGNMENT_CONSTANTS.MESSAGES.ERROR.GENERIC, 'error');
            }
            
        } catch (error) {
            console.error('Error saving assignment:', error);
            NotificationService.show(ASSIGNMENT_CONSTANTS.MESSAGES.ERROR.NETWORK, 'error');
        } finally {
            // Restore button
            const submitBtn = event.target.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.innerHTML = '<i class="fas fa-save"></i> Guardar Asignación';
                submitBtn.disabled = false;
            }
        }
    }
    
    async deleteAssignment(id) {
        if (!confirm('¿Está seguro de eliminar esta asignación?')) {
            return;
        }
        
        try {
            const response = await AssignmentAPI.deleteAssignment(id);
            
            if (response.success) {
                NotificationService.show(ASSIGNMENT_CONSTANTS.MESSAGES.SUCCESS.DELETED, 'success');
                this.loadInitialData(); // Reload data
            } else {
                NotificationService.show(response.message || ASSIGNMENT_CONSTANTS.MESSAGES.ERROR.GENERIC, 'error');
            }
        } catch (error) {
            console.error('Error deleting assignment:', error);
            NotificationService.show(ASSIGNMENT_CONSTANTS.MESSAGES.ERROR.NETWORK, 'error');
        }
    }
    
    editAssignment(id) {
        const assignment = this.state.asignacionesData.find(a => a.id === id);
        if (!assignment) return;
        
        this.state.currentAssignment = assignment;
        
        // Populate form
        document.getElementById('selectInstructor').value = assignment.id_instructor || '';
        document.getElementById('selectFicha').value = assignment.id_ficha || '';
        
        // Set dates
        if (assignment.fechas && this.state.calendarioFlatpickr) {
            const dates = assignment.fechas.split(',').map(date => new Date(date));
            this.state.calendarioFlatpickr.setDate(dates);
        }
        
        // Load schedule info
        this.cargarHorarioFicha();
        
        // Show modal
        this.openModal();
    }
    
    cargarHorarioFicha() {
        const fichaSelect = document.getElementById('selectFicha');
        const selectedFicha = this.state.fichasData.find(f => 
            f.numero_ficha === fichaSelect.value
        );
        
        if (selectedFicha) {
            const jornada = selectedFicha.jornada || 'Diurna';
            const horario = ASSIGNMENT_CONSTANTS.HORARIOS_POR_JORNADA[jornada] || 
                           ASSIGNMENT_CONSTANTS.HORARIOS_POR_JORNADA['Diurna'];
            
            document.getElementById('jornadaInfo').textContent = jornada;
            document.getElementById('horaInfo').textContent = `${horario.inicio} - ${horario.fin}`;
        } else {
            document.getElementById('jornadaInfo').textContent = '-';
            document.getElementById('horaInfo').textContent = '-';
        }
    }
    
    openModal() {
        const modal = document.getElementById('modalAsignacion');
        if (modal) {
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }
    }
    
    closeModal() {
        const modal = document.getElementById('modalAsignacion');
        if (modal) {
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
            
            // Reset form
            document.getElementById('formAsignacion').reset();
            if (this.state.calendarioFlatpickr) {
                this.state.calendarioFlatpickr.clear();
            }
            
            // Reset schedule info
            document.getElementById('jornadaInfo').textContent = '-';
            document.getElementById('horaInfo').textContent = '-';
            
            this.state.currentAssignment = null;
        }
    }
}

// Global instance (Dependency Injection)
let assignmentController;

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    assignmentController = new AssignmentController();
});

// Global functions for backward compatibility
function nuevaAsignacion() {
    assignmentController.openModal();
}

function cerrarModal() {
    assignmentController.closeModal();
}

function guardarAsignacion(event) {
    assignmentController.saveAssignment(event);
}

function cargarHorarioFicha() {
    assignmentController.cargarHorarioFicha();
}

function exportarExcel() {
    ExportService.exportToExcel(assignmentController.state.asignacionesData);
}

function exportarPDF() {
    ExportService.exportToPDF(assignmentController.state.asignacionesData);
}

function exportarCSV() {
    ExportService.exportToCSV(assignmentController.state.asignacionesData);
}
                            <th>FECHA</th>
                            <th>JORNADA</th>
                            <th>HORARIO</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${result.data.map(a => `
                            <tr>
                                <td>${a.numero_ficha}</td>
                                <td>${a.nombre_programa}</td>
                                <td>${a.nombre_instructor}</td>
                                <td>${a.dias_formacion}</td>
                                <td>${a.jornada}</td>
                                <td>${a.hora_inicio} - ${a.hora_fin}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
                <script>
                    window.onload = function() { window.print(); window.close(); }
                </script>
            </body>
            </html>
        `;
        printWindow.document.write(content);
        printWindow.document.close();
        mostrarNotificacion('PDF generado', 'success');
    } catch (error) {
        console.error('Error exportando PDF:', error);
        mostrarNotificacion('Error al generar PDF', 'error');
    }
}

function iniciarCapturaFacial() {
    const btn = event.target;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Inicializando IA...';

    mostrarNotificacion('Accediendo a la cámara...', 'info');

    // Simulación de inicialización de puntos biométricos (128 puntos)
    setTimeout(() => {
        mostrarNotificacion('Escaneando rostro...', 'info');
        const container = document.getElementById('camera-container');
        container.style.borderColor = '#39A900';
        container.innerHTML = `
            <div style="width:100%; height:100%; display:flex; align-items:center; justify-content:center; flex-direction:column; color:#39A900;">
                <i class="fas fa-user-check fa-4x mb-3"></i>
                <p>IDENTIDAD VERIFICADA</p>
                <small style="color:white;">Puntos biométricos: 128/128</small>
            </div>
        `;

        btn.innerHTML = '<i class="fas fa-check"></i> Registro Exitoso';
        btn.style.background = '#39A900';
        mostrarNotificacion('Identidad confirmada mediante biometría facial', 'success');
    }, 3000);
}

function mostrarNotificacion(mensaje, tipo = 'info') {
    const toast = document.createElement('div');
    toast.textContent = mensaje;
    toast.className = `toast-notification toast-${tipo === 'info' ? 'blue' : tipo}`;
    document.body.appendChild(toast);
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(100%)';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}
