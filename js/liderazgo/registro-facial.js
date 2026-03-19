/**
 * MÓDULO DE REGISTRO FACIAL PARA LÍDERES
 * Captura y registro de rostros directamente desde liderazgo
 */

const RegistroFacialLider = {
    video: null,
    canvas: null,
    isProcessing: false,
    currentLider: null,
    stream: null,
    
    /**
     * Inicializar el módulo de registro facial
     */
    async init() {
        this.setupModal();
        this.setupEventListeners();
    },
    
    /**
     * Configurar el modal de registro
     */
    setupModal() {
        // Crear modal si no existe
        if (!document.getElementById('registro-facial-modal')) {
            const modal = document.createElement('div');
            modal.id = 'registro-facial-modal';
            modal.className = 'modal-overlay';
            modal.innerHTML = `
                <div class="modal-content" style="max-width: 600px;">
                    <div class="modal-header">
                        <h3><i class="fas fa-camera"></i> Registro Facial de Líder</h3>
                        <button class="modal-close" onclick="RegistroFacialLider.closeModal()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="leader-info" id="leader-info" style="margin-bottom: 20px;">
                            <!-- Información del líder -->
                        </div>
                        
                        <div class="camera-section">
                            <div class="camera-container" style="position: relative; background: #000; border-radius: 10px; overflow: hidden;">
                                <video id="registro-video" autoplay muted playsinline style="width: 100%; height: 300px; object-fit: cover; transform: scaleX(-1);"></video>
                                <canvas id="registro-canvas" style="display: none;"></canvas>
                                
                                <div class="camera-overlay" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none;">
                                    <div class="face-guide" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 200px; height: 250px; border: 2px dashed rgba(255,255,255,0.5); border-radius: 10px;">
                                        <div style="position: absolute; top: 10px; left: 50%; transform: translateX(-50%); color: white; font-size: 12px; text-align: center;">
                                            <i class="fas fa-user"></i><br>
                                            Centre su rostro aquí
                                        </div>
                                    </div>
                                </div>
                                
                                <div id="registro-feedback" style="position: absolute; bottom: 10px; left: 0; width: 100%; text-align: center; color: white; font-weight: bold; text-shadow: 0 0 5px rgba(0,0,0,0.8);">
                                    Preparando cámara...
                                </div>
                            </div>
                        </div>
                        
                        <div class="captured-preview" id="captured-preview" style="display: none; margin-top: 15px; text-align: center;">
                            <h4>Vista Previa del Registro</h4>
                            <img id="preview-image" style="max-width: 200px; border-radius: 10px; border: 3px solid #10b981;">
                        </div>
                        
                        <div class="modal-actions" style="margin-top: 20px; display: flex; gap: 10px; justify-content: center;">
                            <button id="btn-capturar" class="btn-lid btn-lid-primary" onclick="RegistroFacialLider.capturarRostro()">
                                <i class="fas fa-camera"></i> Capturar Rostro
                            </button>
                            <button id="btn-registrar" class="btn-lid btn-lid-success" onclick="RegistroFacialLider.registrarRostro()" style="display: none;">
                                <i class="fas fa-save"></i> Registrar Biometría
                            </button>
                            <button id="btn-reintentar" class="btn-lid btn-lid-secondary" onclick="RegistroFacialLider.reintentarCaptura()" style="display: none;">
                                <i class="fas fa-redo"></i> Reintentar
                            </button>
                            <button class="btn-lid btn-lid-danger" onclick="RegistroFacialLider.closeModal()">
                                <i class="fas fa-times"></i> Cancelar
                            </button>
                        </div>
                    </div>
                </div>
            `;
            
            // Estilos del modal
            modal.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.8);
                display: none;
                justify-content: center;
                align-items: center;
                z-index: 1000;
            `;
            
            document.body.appendChild(modal);
            
            // Estilos adicionales
            const style = document.createElement('style');
            style.textContent = `
                .modal-content {
                    background: white;
                    border-radius: 15px;
                    box-shadow: 0 20px 40px rgba(0,0,0,0.3);
                    max-height: 90vh;
                    overflow-y: auto;
                }
                .modal-header {
                    padding: 20px;
                    border-bottom: 1px solid #e5e7eb;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    background: linear-gradient(135deg, var(--lid-primary), var(--lid-secondary));
                    color: white;
                    border-radius: 15px 15px 0 0;
                }
                .modal-header h3 {
                    margin: 0;
                    font-size: 1.2rem;
                }
                .modal-close {
                    background: none;
                    border: none;
                    color: white;
                    font-size: 1.2rem;
                    cursor: pointer;
                    padding: 5px;
                    border-radius: 5px;
                    transition: background 0.3s;
                }
                .modal-close:hover {
                    background: rgba(255,255,255,0.2);
                }
                .modal-body {
                    padding: 20px;
                }
                .leader-info {
                    background: #f8f9fa;
                    padding: 15px;
                    border-radius: 10px;
                    border-left: 4px solid var(--lid-primary);
                }
                .face-guide {
                    animation: pulse 2s infinite;
                }
                @keyframes pulse {
                    0%, 100% { opacity: 0.5; }
                    50% { opacity: 1; }
                }
            `;
            document.head.appendChild(style);
        }
    },
    
    /**
     * Configurar event listeners
     */
    setupEventListeners() {
        // Event listeners se configuran dinámicamente
    },
    
    /**
     * Abrir modal de registro
     */
    async openModal(lider) {
        this.currentLider = lider;
        
        // Mostrar información del líder
        const leaderInfo = document.getElementById('leader-info');
        leaderInfo.innerHTML = `
            <div style="display: flex; align-items: center; gap: 15px;">
                <div style="width: 60px; height: 60px; border-radius: 50%; background: linear-gradient(135deg,var(--lid-primary),var(--lid-secondary)); display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 1.5rem;">
                    ${lider.foto_url ? `<img src="${lider.foto_url}" style="width:100%; height:100%; border-radius:50%; object-fit:cover;">` : lider.nombres.charAt(0)}
                </div>
                <div>
                    <h4 style="margin: 0; color: var(--lid-text-primary);">${lider.nombre_completo}</h4>
                    <p style="margin: 5px 0; color: var(--lid-text-muted);">${lider.tipo_liderazgo} | Ficha: ${lider.ficha || 'N/A'}</p>
                    <p style="margin: 0; color: var(--lid-text-muted); font-size: 0.9rem;">Documento: ${lider.documento}</p>
                </div>
            </div>
        `;
        
        // Mostrar modal
        document.getElementById('registro-facial-modal').style.display = 'flex';
        
        // Iniciar cámara
        await this.iniciarCamara();
    },
    
    /**
     * Cerrar modal
     */
    closeModal() {
        document.getElementById('registro-facial-modal').style.display = 'none';
        this.detenerCamara();
        this.resetEstado();
    },
    
    /**
     * Iniciar cámara para registro
     */
    async iniciarCamara() {
        try {
            this.video = document.getElementById('registro-video');
            this.canvas = document.getElementById('registro-canvas');
            
            const constraints = {
                video: {
                    facingMode: 'user',
                    width: { ideal: 640 },
                    height: { ideal: 480 }
                }
            };
            
            this.stream = await navigator.mediaDevices.getUserMedia(constraints);
            this.video.srcObject = this.stream;
            
            document.getElementById('registro-feedback').textContent = 'Listo para capturar';
            
        } catch (error) {
            console.error('Error iniciando cámara:', error);
            document.getElementById('registro-feedback').textContent = 'Error: No se pudo acceder a la cámara';
        }
    },
    
    /**
     * Detener cámara
     */
    detenerCamara() {
        if (this.stream) {
            this.stream.getTracks().forEach(track => track.stop());
            this.stream = null;
        }
    },
    
    /**
     * Capturar rostro
     */
    async capturarRostro() {
        if (this.isProcessing) return;
        
        this.isProcessing = true;
        const feedback = document.getElementById('registro-feedback');
        
        try {
            feedback.textContent = 'Capturando rostro...';
            feedback.style.color = '#f59e0b';
            
            // Capturar imagen del video
            const context = this.canvas.getContext('2d');
            this.canvas.width = this.video.videoWidth;
            this.canvas.height = this.video.videoHeight;
            context.drawImage(this.video, 0, 0);
            
            // Convertir a base64
            const imagenBase64 = this.canvas.toDataURL('image/jpeg', 0.8);
            
            // Mostrar vista previa
            document.getElementById('preview-image').src = imagenBase64;
            document.getElementById('captured-preview').style.display = 'block';
            
            // Generar descriptor facial (simulado)
            const descriptor = await this.generarDescriptorFacial(imagenBase64);
            
            // Guardar datos capturados
            this.capturedData = {
                imagen: imagenBase64,
                descriptor: descriptor
            };
            
            // Actualizar UI
            feedback.textContent = 'Rostro capturado correctamente';
            feedback.style.color = '#10b981';
            
            document.getElementById('btn-capturar').style.display = 'none';
            document.getElementById('btn-registrar').style.display = 'inline-flex';
            
        } catch (error) {
            console.error('Error capturando rostro:', error);
            feedback.textContent = 'Error capturando rostro';
            feedback.style.color = '#ef4444';
        } finally {
            this.isProcessing = false;
        }
    },
    
    /**
     * Generar descriptor facial (simulado)
     */
    async generarDescriptorFacial(imagenBase64) {
        // Simular generación de descriptor facial
        // En producción, esto usaría el motor real de reconocimiento facial
        const descriptor = [];
        for (let i = 0; i < 128; i++) {
            descriptor.push(Math.random());
        }
        return descriptor;
    },
    
    /**
     * Registrar rostro en la base de datos
     */
    async registrarRostro() {
        if (!this.capturedData || this.isProcessing) return;
        
        this.isProcessing = true;
        const feedback = document.getElementById('registro-feedback');
        
        try {
            feedback.textContent = 'Registrando biometría...';
            feedback.style.color = '#f59e0b';
            
            const response = await fetch('api/registro-biometrico-lider.php?action=registrarRostro', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    documento: this.currentLider.documento,
                    imagen: this.capturedData.imagen,
                    descriptor: this.capturedData.descriptor
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                feedback.textContent = '¡Biometría registrada exitosamente!';
                feedback.style.color = '#10b981';
                
                // Mostrar éxito
                showNotification('success', result.message);
                
                // Cerrar modal después de un momento
                setTimeout(() => {
                    this.closeModal();
                    // Recargar lista de líderes
                    if (typeof cargarLideresBiometricos === 'function') {
                        cargarLideresBiometricos();
                    }
                }, 2000);
                
            } else {
                throw new Error(result.error || 'Error registrando biometría');
            }
            
        } catch (error) {
            console.error('Error registrando rostro:', error);
            feedback.textContent = 'Error registrando biometría';
            feedback.style.color = '#ef4444';
            showNotification('error', error.message);
        } finally {
            this.isProcessing = false;
        }
    },
    
    /**
     * Reintentar captura
     */
    reintentarCaptura() {
        this.resetEstado();
        document.getElementById('captured-preview').style.display = 'none';
        document.getElementById('registro-feedback').textContent = 'Listo para capturar';
        document.getElementById('registro-feedback').style.color = 'white';
    },
    
    /**
     * Resetear estado
     */
    resetEstado() {
        this.capturedData = null;
        this.isProcessing = false;
        
        // Resetear botones
        document.getElementById('btn-capturar').style.display = 'inline-flex';
        document.getElementById('btn-registrar').style.display = 'none';
        document.getElementById('btn-reintentar').style.display = 'none';
        
        // Resetear feedback
        const feedback = document.getElementById('registro-feedback');
        feedback.textContent = 'Preparando cámara...';
        feedback.style.color = 'white';
    }
};

// Función global para mostrar notificaciones
function showNotification(type, message) {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        border-radius: 8px;
        color: white;
        font-weight: 600;
        z-index: 2000;
        animation: slideIn 0.3s ease;
        background: ${type === 'success' ? '#10b981' : '#ef4444'};
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    `;
    notification.innerHTML = `
        <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'}" style="margin-right:8px;"></i>
        ${message}
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Exportar para uso global
window.RegistroFacialLider = RegistroFacialLider;
