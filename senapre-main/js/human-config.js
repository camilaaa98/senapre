/**
 * Configuración de Human.js para Reconocimiento Facial
 * Optimizado para rendimiento y precisión
 */

const humanConfig = {
    // Ruta base de modelos (CDN)
    modelBasePath: 'https://cdn.jsdelivr.net/npm/@vladmandic/human/models',

    // Backend: WebGL para GPU (más rápido)
    backend: 'webgl',

    // Configuración de detección facial
    face: {
        enabled: true,
        detector: {
            rotation: true,        // Detectar rostros rotados
            maxDetected: 10,       // Máximo 10 rostros (para reconocimiento grupal futuro)
            minConfidence: 0.7,    // Confianza mínima 70%
            return: true
        },
        mesh: {
            enabled: true          // Mesh facial para mejor precisión
        },
        iris: {
            enabled: false         // No necesario para reconocimiento
        },
        description: {
            enabled: true,         // CRÍTICO: Genera embeddings de 512 dimensiones
            minConfidence: 0.7
        },
        emotion: {
            enabled: false         // No necesario
        },
        age: {
            enabled: false
        },
        gender: {
            enabled: false
        }
    },

    // Desactivar detección de cuerpo y manos (no necesarias)
    body: { enabled: false },
    hand: { enabled: false },
    gesture: { enabled: false },

    // Configuración de video
    filter: {
        enabled: true,
        equalization: true,        // Mejora contraste
        brightness: 0.1
    }
};

// Instancia global de Human.js
let humanInstance = null;

/**
 * Inicializa Human.js (se llama una sola vez)
 */
async function inicializarHuman() {
    if (humanInstance) return humanInstance;

    try {
        console.log('Inicializando Human.js...');

        // Verificar que Human esté disponible globalmente
        if (typeof Human === 'undefined' || !window.Human) {
            throw new Error('La librería Human.js no está cargada. Verifique que el script CDN esté incluido.');
        }

        humanInstance = new Human.default(humanConfig);
        await humanInstance.load();
        await humanInstance.warmup();
        console.log('✓ Human.js inicializado correctamente');
        return humanInstance;
    } catch (error) {
        console.error('Error inicializando Human.js:', error);
        throw error;
    }
}

/**
 * Obtiene la instancia de Human.js (lazy loading)
 */
async function getHuman() {
    if (!humanInstance) {
        await inicializarHuman();
    }
    return humanInstance;
}
