/**
 * Cliente API para AsistNet
 * Maneja todas las llamadas HTTP al backend
 */

const API_BASE_URL = 'api';

class ApiClient {
    /**
     * Realizar petición HTTP
     */
    async request(endpoint, options = {}) {
        const url = `${API_BASE_URL}/${endpoint}`;
        const config = {
            headers: {
                'Content-Type': 'application/json',
                ...options.headers
            },
            ...options
        };

        try {
            const response = await fetch(url, config);
            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'Error en la petición');
            }

            return data;
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    }

    /**
     * GET request
     */
    async get(endpoint) {
        return this.request(endpoint, { method: 'GET' });
    }

    /**
     * POST request
     */
    async post(endpoint, data) {
        return this.request(endpoint, {
            method: 'POST',
            body: JSON.stringify(data)
        });
    }

    /**
     * PUT request
     */
    async put(endpoint, data) {
        return this.request(endpoint, {
            method: 'PUT',
            body: JSON.stringify(data)
        });
    }

    /**
     * DELETE request
     */
    async delete(endpoint) {
        return this.request(endpoint, { method: 'DELETE' });
    }

    // === DASHBOARD ===
    async getDashboardStats() {
        // Usar archivo directo que SÍ funciona
        const response = await fetch('api/test-dashboard.php');
        const result = await response.json();
        return result.data || result;
    }

    async getRecentActivity() {
        // Por ahora retornar array vacío
        return [];
    }

    // === APRENDICES ===
    async getAprendices() {
        return this.get('aprendices');
    }

    async getAprendiz(id) {
        return this.get(`aprendices/${id}`);
    }

    async createAprendiz(data) {
        return this.post('aprendices', data);
    }

    async updateAprendiz(id, data) {
        return this.put(`aprendices/${id}`, data);
    }

    async deleteAprendiz(id) {
        return this.delete(`aprendices/${id}`);
    }

    // === FICHAS ===
    async getFichas() {
        return this.get('fichas');
    }

    async getFicha(id) {
        return this.get(`fichas/${id}`);
    }

    async createFicha(data) {
        return this.post('fichas', data);
    }

    async updateFicha(id, data) {
        return this.put(`fichas/${id}`, data);
    }

    // === PROGRAMAS ===
    async getProgramas() {
        return this.get('programas');
    }

    async getPrograma(id) {
        return this.get(`programas/${id}`);
    }

    // === ASISTENCIAS ===
    async getAsistencias(filters = {}) {
        const params = new URLSearchParams(filters).toString();
        return this.get(`asistencias${params ? '?' + params : ''}`);
    }

    async registrarAsistencia(data) {
        // La fecha y hora se agregan automáticamente en el backend
        return this.post('asistencias', data);
    }

    async getReporte(filters = {}) {
        const params = new URLSearchParams(filters).toString();
        return this.get(`asistencias/reporte${params ? '?' + params : ''}`);
    }
}

// Instancia global del cliente API
const api = new ApiClient();
