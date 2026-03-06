/**
 * Utilidades de Reconocimiento Facial
 * Funciones reutilizables para captura, comparación y almacenamiento de embeddings
 */

// Umbrales de similitud (70-80% según especificación)
const UMBRAL_SIMILITUD_MIN = 0.70;
const UMBRAL_SIMILITUD_MAX = 0.80;
const UMBRAL_SIMILITUD_OPTIMO = 0.75; // Valor medio recomendado

/**
 * Convierte Float32Array a ArrayBuffer para almacenamiento BLOB
 */
function embeddingToBlob(embedding) {
    if (!embedding || !embedding.length) {
        throw new Error('Embedding inválido');
    }

    // Convertir Float32Array a ArrayBuffer
    const float32Array = new Float32Array(embedding);
    return float32Array.buffer;
}

/**
 * Convierte ArrayBuffer (BLOB) a Float32Array
 */
function blobToEmbedding(arrayBuffer) {
    return new Float32Array(arrayBuffer);
}

/**
 * Convierte ArrayBuffer a Base64 para enviar por HTTP
 */
function arrayBufferToBase64(buffer) {
    const bytes = new Uint8Array(buffer);
    let binary = '';
    for (let i = 0; i < bytes.byteLength; i++) {
        binary += String.fromCharCode(bytes[i]);
    }
    return btoa(binary);
}

/**
 * Convierte Base64 a ArrayBuffer
 */
function base64ToArrayBuffer(base64) {
    const binary = atob(base64);
    const bytes = new Uint8Array(binary.length);
    for (let i = 0; i < binary.length; i++) {
        bytes[i] = binary.charCodeAt(i);
    }
    return bytes.buffer;
}

/**
 * Calcula similitud coseno entre dos embeddings
 * Retorna valor entre 0 y 1 (1 = idénticos, 0 = completamente diferentes)
 */
function calcularSimilitudCoseno(embedding1, embedding2) {
    if (!embedding1 || !embedding2) {
        throw new Error('Embeddings inválidos para comparación');
    }

    if (embedding1.length !== embedding2.length) {
        throw new Error('Embeddings de diferentes dimensiones');
    }

    let dotProduct = 0;
    let norm1 = 0;
    let norm2 = 0;

    for (let i = 0; i < embedding1.length; i++) {
        dotProduct += embedding1[i] * embedding2[i];
        norm1 += embedding1[i] * embedding1[i];
        norm2 += embedding2[i] * embedding2[i];
    }

    const similarity = dotProduct / (Math.sqrt(norm1) * Math.sqrt(norm2));
    return similarity;
}

/**
 * Captura rostro desde elemento de video
 * Retorna: { success: boolean, embedding: Float32Array, mensaje: string, confianza: number }
 */
async function capturarRostro(videoElement) {
    try {
        const human = await getHuman();

        // Detectar rostros en el frame actual
        const result = await human.detect(videoElement);

        // Validar que se detectó exactamente un rostro
        if (!result.face || result.face.length === 0) {
            return {
                success: false,
                mensaje: 'No se detectó ningún rostro. Por favor, posicione su rostro frente a la cámara.',
                confianza: 0
            };
        }

        if (result.face.length > 1) {
            return {
                success: false,
                mensaje: 'Se detectaron múltiples rostros. Por favor, asegúrese de estar solo frente a la cámara.',
                confianza: 0
            };
        }

        const face = result.face[0];

        // Validar que se generó el embedding
        if (!face.embedding || face.embedding.length === 0) {
            return {
                success: false,
                mensaje: 'No se pudo generar el embedding facial. Intente con mejor iluminación.',
                confianza: 0
            };
        }

        // Validar confianza mínima
        const confianza = face.faceScore || face.boxScore || 0;
        if (confianza < UMBRAL_SIMILITUD_MIN) {
            return {
                success: false,
                mensaje: `Calidad de captura insuficiente (${Math.round(confianza * 100)}%). Mejore la iluminación y posición.`,
                confianza: confianza
            };
        }

        return {
            success: true,
            embedding: face.embedding,
            mensaje: 'Rostro capturado exitosamente',
            confianza: confianza
        };

    } catch (error) {
        console.error('Error capturando rostro:', error);
        return {
            success: false,
            mensaje: 'Error técnico al capturar rostro: ' + error.message,
            confianza: 0
        };
    }
}

/**
 * Captura MÚLTIPLES rostros (para asistencia grupal)
 * Retorna: { success: boolean, faces: Array<{embedding, confianza}>, mensaje: string }
 */
async function capturarRostros(videoElement) {
    try {
        const human = await getHuman();
        const result = await human.detect(videoElement);

        if (!result.face || result.face.length === 0) {
            return { success: false, faces: [], mensaje: 'Buscando rostros...' };
        }

        const facesValidos = result.face.filter(f => {
            const confianza = f.faceScore || f.boxScore || 0;
            return f.embedding && f.embedding.length > 0 && confianza >= UMBRAL_SIMILITUD_MIN;
        }).map(f => ({
            embedding: f.embedding,
            confianza: f.faceScore || f.boxScore || 0
        }));

        if (facesValidos.length === 0) {
            return { success: false, faces: [], mensaje: 'Rostros detectados con baja calidad' };
        }

        return {
            success: true,
            faces: facesValidos,
            mensaje: `Se detectaron ${facesValidos.length} rostros`
        };

    } catch (error) {
        console.error('Error captura grupal:', error);
        return { success: false, faces: [], mensaje: 'Error técnico' };
    }
}

/**
 * Compara dos embeddings y determina si son de la misma persona
 * Retorna: { esLaMismaPersona: boolean, similitud: number, mensaje: string }
 */
function compararEmbeddings(embedding1, embedding2) {
    try {
        const similitud = calcularSimilitudCoseno(embedding1, embedding2);
        const porcentaje = Math.round(similitud * 100);

        // Validar contra umbral
        const esLaMismaPersona = similitud >= UMBRAL_SIMILITUD_MIN;

        let mensaje;
        if (similitud >= UMBRAL_SIMILITUD_MAX) {
            mensaje = `Coincidencia excelente (${porcentaje}%)`;
        } else if (similitud >= UMBRAL_SIMILITUD_OPTIMO) {
            mensaje = `Coincidencia buena (${porcentaje}%)`;
        } else if (similitud >= UMBRAL_SIMILITUD_MIN) {
            mensaje = `Coincidencia aceptable (${porcentaje}%)`;
        } else {
            mensaje = `No coincide (${porcentaje}% - mínimo requerido: 70%)`;
        }

        return {
            esLaMismaPersona,
            similitud,
            porcentaje,
            mensaje
        };

    } catch (error) {
        console.error('Error comparando embeddings:', error);
        return {
            esLaMismaPersona: false,
            similitud: 0,
            porcentaje: 0,
            mensaje: 'Error al comparar rostros'
        };
    }
}

/**
 * Registra o actualiza biometría en la base de datos
 * @param {string} tipo - 'usuario' o 'aprendiz'
 * @param {string} id - id_usuario o documento
 * @param {Float32Array} embedding - Vector facial
 */
async function registrarBiometria(tipo, id, embedding) {
    try {
        // Convertir embedding a Base64 para enviar por HTTP
        const arrayBuffer = embeddingToBlob(embedding);
        const base64Embedding = arrayBufferToBase64(arrayBuffer);

        const response = await fetch('api/biometria.php?action=registrar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                tipo: tipo,
                id: id,
                embedding: base64Embedding
            })
        });

        const result = await response.json();
        return result;

    } catch (error) {
        console.error('Error registrando biometría:', error);
        return {
            success: false,
            message: 'Error al guardar biometría: ' + error.message
        };
    }
}

/**
 * Verifica identidad comparando rostro capturado con embedding almacenado
 * @param {string} tipo - 'usuario' o 'aprendiz'
 * @param {string} id - id_usuario o documento
 * @param {Float32Array} embeddingCapturado - Vector facial capturado
 */
async function verificarBiometria(tipo, id, embeddingCapturado) {
    try {
        // Obtener embedding almacenado
        const response = await fetch(`api/biometria.php?action=obtener&tipo=${tipo}&id=${encodeURIComponent(id)}`);
        const result = await response.json();

        if (!result.success || !result.data || !result.data.embedding) {
            return {
                success: false,
                verificado: false,
                mensaje: 'No hay biometría registrada para este usuario'
            };
        }

        // Convertir Base64 a Float32Array
        const arrayBuffer = base64ToArrayBuffer(result.data.embedding);
        const embeddingAlmacenado = blobToEmbedding(arrayBuffer);

        // Comparar embeddings
        const comparacion = compararEmbeddings(embeddingCapturado, embeddingAlmacenado);

        return {
            success: true,
            verificado: comparacion.esLaMismaPersona,
            similitud: comparacion.similitud,
            porcentaje: comparacion.porcentaje,
            mensaje: comparacion.mensaje
        };

    } catch (error) {
        console.error('Error verificando biometría:', error);
        return {
            success: false,
            verificado: false,
            mensaje: 'Error al verificar identidad: ' + error.message
        };
    }
}

/**
 * Consulta si un usuario/aprendiz tiene biometría registrada
 * @param {string} tipo - 'usuario' o 'aprendiz'
 * @param {string} id - id_usuario o documento
 */
async function consultarEstadoBiometria(tipo, id) {
    try {
        const response = await fetch(`api/biometria.php?action=estado&tipo=${tipo}&id=${encodeURIComponent(id)}`);
        const result = await response.json();
        return result;
    } catch (error) {
        console.error('Error consultando estado biometría:', error);
        return {
            success: false,
            tiene_biometria: false
        };
    }
}

/**
 * Elimina registro biométrico
 * @param {string} tipo - 'usuario' o 'aprendiz'
 * @param {string} id - id_usuario o documento
 */
async function eliminarBiometria(tipo, id) {
    try {
        const response = await fetch(`api/biometria.php?action=eliminar&tipo=${tipo}&id=${encodeURIComponent(id)}`, {
            method: 'DELETE'
        });
        const result = await response.json();
        return result;
    } catch (error) {
        console.error('Error eliminando biometría:', error);
        return {
            success: false,
            message: 'Error al eliminar biometría'
        };
    }
}
/**
 * Identifica a una persona comparando su rostro con la base de datos (1:N)
 * @param {HTMLVideoElement} videoElement
 * @returns {Promise<{success: boolean, match: boolean, data?: any, score?: number, mensaje: string}>}
 */
async function identificarPersona(videoElement) {
    try {
        const captura = await capturarRostro(videoElement);
        if (!captura.success) return captura;

        const response = await fetch('api/biometria.php?action=identificar_grupo', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                embedding: Array.from(captura.embedding)
            })
        });

        const result = await response.json();
        if (result.success && result.match) {
            return {
                success: true,
                match: true,
                data: result.data,
                score: result.data.similitud,
                mensaje: result.mensaje
            };
        }

        return {
            success: true,
            match: false,
            mensaje: result.message || 'Persona no reconocida'
        };

    } catch (error) {
        console.error('Error en identificarPersona:', error);
        return {
            success: false,
            mensaje: 'Error técnico de reconocimiento: ' + error.message
        };
    }
}
