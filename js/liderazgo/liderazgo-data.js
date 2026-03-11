/**
 * Liderazgo Module - Data Services
 * Centralizes all API calls for the Liderazgo component
 */

const LiderazgoData = {
    cache: {
        principales: [],
        suplentes: [],
        enfoque: [],
        representantes: [],
        reuniones: []
    },
    
    pages: {
        principales: 1,
        suplentes: 1,
        enfoque: 1,
        representantes: 1
    },

    async fetchLideres(filtro) {
        try {
            const res = await fetch(`api/liderazgo.php?action=getLideres&filtro=${filtro}`);
            const data = await res.json();
            if (data.success) {
                this.cache[filtro] = data.data;
                return data.data;
            }
        } catch (e) {
            console.error(`Error fetching ${filtro}:`, e);
            return [];
        }
    },

    async fetchReuniones() {
        try {
            const res = await fetch('api/liderazgo.php?action=getReuniones');
            const data = await res.json();
            if (data.success) {
                this.cache.reuniones = data.data;
                return data.data;
            }
        } catch (e) {
            console.error('Error fetching reuniones:', e);
            return [];
        }
    },

    async updateLider(documento, datos) {
        try {
            const res = await fetch('api/liderazgo.php?action=updateLider', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ documento, ...datos })
            });
            return await res.json();
        } catch (e) {
            return { success: false, message: 'Error de conexión' };
        }
    },

    changePage(tipo, newPage) {
        const key = tipo.toLowerCase();
        this.pages[key] = newPage;
        
        // Determinar contenedor según tipo
        let containerId = 'container-voceros';
        if (key === 'representantes') containerId = 'container-representantes';
        if (key === 'reuniones') containerId = 'container-reuniones';
        if (key === 'suplentes') containerId = 'container-voceros-suplentes';
        
        LiderazgoUI.renderLideres(this.cache[key] || [], containerId, newPage, tipo);
        
        // Scroll to top of section
        const section = document.getElementById(`tab-btn-${key}`) || document.getElementById(containerId);
        if (section) section.scrollIntoView({ behavior: 'smooth' });
    }
};
