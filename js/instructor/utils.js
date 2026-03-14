/**
 * Utilidades compartidas para el panel del instructor
 * Principios SOLID: Single Responsibility - utilidades reutilizables
 */

// Constantes compartidas
const INSTRUCTOR_CONSTANTS = {
    MINUTOS_TOLERANCIA: 30,
    ITEMS_POR_PAGINA: 10,
    COLORES_ESTADO: {
        'Presente': '#10b981',
        'Retardo': '#f59e0b', 
        'Ausente': '#ef4444',
        'Justificado': '#3b82f6',
        'Con Excusa': '#3b82f6'
    },
    BADGE_CLASSES: {
        'Presente': 'badge-status-presente',
        'Retardo': 'badge-status-retardo',
        'Ausente': 'badge-status-ausente', 
        'Justificado': 'badge-status-excusa',
        'Con Excusa': 'badge-status-excusa'
    }
};

/**
 * Funciones de fecha compartidas
 */
const DateUtils = {
    /**
     * Obtiene la fecha actual en formato YYYY-MM-DD
     */
    getFechaHoy() {
        const ahora = new Date();
        return new Date(ahora.getTime() - (ahora.getTimezoneOffset() * 60000)).toISOString().split('T')[0];
    },

    /**
     * Formatea fecha para visualización
     */
    formatearFecha(fechaString) {
        const opciones = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        return new Date(fechaString).toLocaleDateString('es-ES', opciones);
    },

    /**
     * Valida que fecha inicio no sea mayor a fecha fin
     */
    validarRangoFechas(fechaInicio, fechaFin) {
        return new Date(fechaInicio) <= new Date(fechaFin);
    },

    /**
     * Calcula estado de asistencia basado en hora de llegada
     */
    calcularEstadoAsistencia(horaInicioFicha) {
        if (!horaInicioFicha) return 'Presente';

        const ahora = new Date();
        const [horaInicio, minInicio] = horaInicioFicha.split(':').map(Number);
        const fechaInicioClase = new Date();
        fechaInicioClase.setHours(horaInicio, minInicio, 0, 0);
        const fechaLimite = new Date(fechaInicioClase.getTime() + INSTRUCTOR_CONSTANTS.MINUTOS_TOLERANCIA * 60000);

        return ahora > fechaLimite ? 'Retardo' : 'Presente';
    }
};

/**
 * Utilidades de UI compartidas
 */
const UIUtils = {
    /**
     * Muestra notificaciones estandarizadas
     */
    mostrarNotificacion(mensaje, tipo = 'info') {
        // Implementación genérica - puede ser sobreescrita por cada módulo
        console.log(`[${tipo.toUpperCase()}] ${mensaje}`);
        
        // Si existe una función global de notificación, usarla
        if (typeof mostrarNotificacion === 'function') {
            mostrarNotificacion(mensaje, tipo);
        }
    },

    /**
     * Obtiene badge HTML para estado de asistencia
     */
    getBadgeEstado(estado) {
        const colors = INSTRUCTOR_CONSTANTS.COLORES_ESTADO;
        const classes = INSTRUCTOR_CONSTANTS.BADGE_CLASSES;
        
        return `<span class="badge ${classes[estado] || 'badge-status-ausente'}" 
                     style="background-color: ${colors[estado] || '#ef4444'}">
                    ${estado}
                </span>`;
    },

    /**
     * Formatea número con separadores de miles
     */
    formatearNumero(numero) {
        return new Intl.NumberFormat('es-ES').format(numero);
    },

    /**
     * Debounce genérico para eventos
     */
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
};

/**
 * Utilidades de API compartidas
 */
const APIUtils = {
    /**
     * Realiza petición fetch con manejo de errores estándar
     */
    async fetchWithErrorHandling(url, options = {}) {
        try {
            const response = await fetch(url, options);
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.message || 'Error en la petición');
            }
            
            return data;
        } catch (error) {
            console.error('Error en API:', error);
            UIUtils.mostrarNotificacion(error.message, 'error');
            throw error;
        }
    },

    /**
     * Obtiene fichas del instructor actual
     */
    async getFichasInstructor(idUsuario) {
        return this.fetchWithErrorHandling(`api/instructor-fichas.php?id_usuario=${idUsuario}`);
    },

    /**
     * Obtiene aprendices de una ficha
     */
    async getAprendicesFicha(ficha, limit = -1) {
        return this.fetchWithErrorHandling(`api/aprendices.php?ficha=${ficha}&limit=${limit}`);
    }
};

/**
 * Utilidades de exportación compartidas
 */
const ExportUtils = {
    /**
     * Descarga archivo con BOM UTF-8 para Excel
     */
    downloadFile(content, fileName, mimeType = 'text/csv;charset=utf-8') {
        const bom = '\uFEFF';
        const blob = new Blob([bom + content], { type: mimeType });
        
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = fileName;
        link.click();
        
        URL.revokeObjectURL(link.href);
    },

    /**
     * Genera nombre de archivo con timestamp
     */
    generarNombreArchivo(baseName, extension) {
        const timestamp = new Date().toISOString().slice(0, 19).replace(/[:-]/g, '');
        return `${baseName}_${timestamp}.${extension}`;
    }
};

// Exportar para uso en módulos
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        INSTRUCTOR_CONSTANTS,
        DateUtils,
        UIUtils,
        APIUtils,
        ExportUtils
    };
}
