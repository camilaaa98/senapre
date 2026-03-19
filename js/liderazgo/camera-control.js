/**
 * CONTROL DE CÁMARA PARA ASISTENCIA BIOMÉTRICA
 * Detección de dispositivo y cambio de cámara
 */

const CameraControl = {
    currentStream: null,
    currentDeviceId: null,
    devices: [],
    isMobile: false,
    facingMode: 'user', // 'user' = frontal, 'environment' = trasera
    
    /**
     * Inicializar el sistema de control de cámara
     */
    async init() {
        this.detectDevice();
        await this.getAvailableDevices();
        this.setupUI();
    },
    
    /**
     * Detectar si el dispositivo es móvil
     */
    detectDevice() {
        const userAgent = navigator.userAgent.toLowerCase();
        const isMobileDevice = /android|webos|iphone|ipad|ipod|blackberry|iemobile|opera mini/i.test(userAgent);
        const isTouchDevice = 'ontouchstart' in window || navigator.maxTouchPoints > 0;
        const isSmallScreen = window.innerWidth <= 768;
        
        this.isMobile = isMobileDevice || (isTouchDevice && isSmallScreen);
        
        // Configurar facing mode según dispositivo
        this.facingMode = this.isMobile ? 'environment' : 'user';
        
        console.log(`Dispositivo detectado: ${this.isMobile ? 'Móvil/Tablet' : 'PC/TV'}`);
        console.log(`Modo de cámara inicial: ${this.facingMode === 'user' ? 'Frontal' : 'Trasera'}`);
    },
    
    /**
     * Obtener lista de dispositivos de video disponibles
     */
    async getAvailableDevices() {
        try {
            const devices = await navigator.mediaDevices.enumerateDevices();
            this.devices = devices.filter(device => device.kind === 'videoinput');
            
            console.log(`Cámaras encontradas: ${this.devices.length}`);
            this.devices.forEach((device, index) => {
                console.log(`Cámara ${index}: ${device.label || 'Sin etiqueta'}`);
            });
            
            return this.devices;
        } catch (error) {
            console.error('Error obteniendo dispositivos:', error);
            return [];
        }
    },
    
    /**
     * Configurar la interfaz de usuario
     */
    setupUI() {
        // Crear botón de cambio de cámara si es necesario
        if (this.shouldShowCameraSwitch()) {
            this.createCameraSwitchButton();
        }
        
        // Crear indicador de cámara activa
        this.createCameraIndicator();
        
        // Crear selector de cámaras si hay múltiples
        if (this.devices.length > 1) {
            this.createCameraSelector();
        }
    },
    
    /**
     * Determinar si mostrar el botón de cambio de cámara
     */
    shouldShowCameraSwitch() {
        // Mostrar en móviles o si hay múltiples cámaras
        return this.isMobile || this.devices.length > 1;
    },
    
    /**
     * Crear botón para cambiar de cámara
     */
    createCameraSwitchButton() {
        const container = document.querySelector('.biometric-scanner-container');
        if (!container) return;
        
        // Crear botón flotante
        const switchButton = document.createElement('button');
        switchButton.id = 'camera-switch-btn';
        switchButton.className = 'camera-switch-btn';
        switchButton.innerHTML = `
            <i class="fas fa-camera-rotate"></i>
            <span>Cambiar Cámara</span>
        `;
        switchButton.onclick = () => this.switchCamera();
        
        // Estilos del botón
        switchButton.style.cssText = `
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 8px 12px;
            cursor: pointer;
            z-index: 100;
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 12px;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        `;
        
        switchButton.onmouseover = () => {
            switchButton.style.background = 'rgba(0, 0, 0, 0.9)';
            switchButton.style.transform = 'scale(1.05)';
        };
        
        switchButton.onmouseout = () => {
            switchButton.style.background = 'rgba(0, 0, 0, 0.7)';
            switchButton.style.transform = 'scale(1)';
        };
        
        container.appendChild(switchButton);
    },
    
    /**
     * Crear indicador de cámara activa
     */
    createCameraIndicator() {
        const container = document.querySelector('.biometric-scanner-container');
        if (!container) return;
        
        const indicator = document.createElement('div');
        indicator.id = 'camera-indicator';
        indicator.innerHTML = `
            <i class="fas fa-video"></i>
            <span id="camera-status-text">Cámara Frontal</span>
        `;
        
        indicator.style.cssText = `
            position: absolute;
            top: 10px;
            left: 10px;
            background: rgba(57, 169, 0, 0.9);
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 11px;
            z-index: 100;
            display: flex;
            align-items: center;
            gap: 5px;
            backdrop-filter: blur(10px);
        `;
        
        container.appendChild(indicator);
        this.updateCameraIndicator();
    },
    
    /**
     * Crear selector de cámaras (para PC con múltiples cámaras)
     */
    createCameraSelector() {
        const container = document.querySelector('.biometric-scanner-container');
        if (!container || this.devices.length <= 1) return;
        
        const selector = document.createElement('select');
        selector.id = 'camera-selector';
        selector.style.cssText = `
            position: absolute;
            bottom: 60px;
            right: 10px;
            background: rgba(0, 0, 0, 0.8);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 5px;
            padding: 5px;
            font-size: 11px;
            z-index: 100;
            backdrop-filter: blur(10px);
        `;
        
        this.devices.forEach((device, index) => {
            const option = document.createElement('option');
            option.value = device.deviceId;
            option.textContent = device.label || `Cámara ${index + 1}`;
            selector.appendChild(option);
        });
        
        selector.onchange = (e) => {
            this.switchToDevice(e.target.value);
        };
        
        container.appendChild(selector);
    },
    
    /**
     * Cambiar entre cámara frontal y trasera
     */
    async switchCamera() {
        try {
            // Cambiar facing mode
            this.facingMode = this.facingMode === 'user' ? 'environment' : 'user';
            
            // Mostrar feedback
            this.showSwitchingFeedback();
            
            // Detener stream actual
            if (this.currentStream) {
                this.currentStream.getTracks().forEach(track => track.stop());
            }
            
            // Iniciar nuevo stream
            const video = document.getElementById('webcam');
            if (video) {
                const constraints = {
                    video: {
                        facingMode: this.facingMode,
                        width: { ideal: 1280 },
                        height: { ideal: 720 }
                    }
                };
                
                this.currentStream = await navigator.mediaDevices.getUserMedia(constraints);
                video.srcObject = this.currentStream;
                
                // Actualizar indicador
                this.updateCameraIndicator();
                
                console.log(`Cámara cambiada a: ${this.facingMode === 'user' ? 'Frontal' : 'Trasera'}`);
            }
            
        } catch (error) {
            console.error('Error cambiando cámara:', error);
            this.showError('No se pudo cambiar la cámara');
            
            // Revertir facing mode
            this.facingMode = this.facingMode === 'user' ? 'environment' : 'user';
        }
    },
    
    /**
     * Cambiar a un dispositivo específico
     */
    async switchToDevice(deviceId) {
        try {
            // Mostrar feedback
            this.showSwitchingFeedback();
            
            // Detener stream actual
            if (this.currentStream) {
                this.currentStream.getTracks().forEach(track => track.stop());
            }
            
            // Iniciar nuevo stream con dispositivo específico
            const video = document.getElementById('webcam');
            if (video) {
                const constraints = {
                    video: {
                        deviceId: { exact: deviceId },
                        width: { ideal: 1280 },
                        height: { ideal: 720 }
                    }
                };
                
                this.currentStream = await navigator.mediaDevices.getUserMedia(constraints);
                video.srcObject = this.currentStream;
                this.currentDeviceId = deviceId;
                
                this.updateCameraIndicator();
            }
            
        } catch (error) {
            console.error('Error cambiando dispositivo:', error);
            this.showError('No se pudo cambiar al dispositivo seleccionado');
        }
    },
    
    /**
     * Obtener constraints óptimos según dispositivo
     */
    getOptimalConstraints() {
        if (this.isMobile) {
            // Para móviles, priorizar cámara trasera
            return {
                video: {
                    facingMode: this.facingMode,
                    width: { ideal: 1920, max: 1920 },
                    height: { ideal: 1080, max: 1080 }
                }
            };
        } else {
            // Para PC, usar la mejor cámara disponible
            return {
                video: {
                    width: { ideal: 1280 },
                    height: { ideal: 720 }
                }
            };
        }
    },
    
    /**
     * Actualizar indicador de cámara
     */
    updateCameraIndicator() {
        const statusText = document.getElementById('camera-status-text');
        if (statusText) {
            const cameraType = this.facingMode === 'user' ? 'Frontal' : 'Trasera';
            const deviceType = this.isMobile ? 'Móvil' : 'PC';
            statusText.textContent = `${cameraType} (${deviceType})`;
        }
    },
    
    /**
     * Mostrar feedback durante el cambio
     */
    showSwitchingFeedback() {
        const feedback = document.getElementById('scan-feedback');
        if (feedback) {
            feedback.textContent = "CAMBIANDO CÁMARA...";
            feedback.style.color = "#f59e0b";
            
            setTimeout(() => {
                if (feedback.textContent === "CAMBIANDO CÁMARA...") {
                    feedback.textContent = "BUSCANDO LÍDER...";
                    feedback.style.color = "#39A900";
                }
            }, 2000);
        }
    },
    
    /**
     * Mostrar mensaje de error
     */
    showError(message) {
        const feedback = document.getElementById('scan-feedback');
        if (feedback) {
            feedback.textContent = message;
            feedback.style.color = "#ef4444";
            
            setTimeout(() => {
                feedback.textContent = "BUSCANDO LÍDER...";
                feedback.style.color = "#39A900";
            }, 3000);
        }
    },
    
    /**
     * Obtener stream inicial
     */
    async getInitialStream() {
        try {
            const constraints = this.getOptimalConstraints();
            this.currentStream = await navigator.mediaDevices.getUserMedia(constraints);
            return this.currentStream;
        } catch (error) {
            console.error('Error obteniendo stream inicial:', error);
            
            // Fallback a configuración básica
            try {
                this.currentStream = await navigator.mediaDevices.getUserMedia({ video: true });
                return this.currentStream;
            } catch (fallbackError) {
                console.error('Error en fallback:', fallbackError);
                throw new Error('No se pudo acceder a ninguna cámara');
            }
        }
    },
    
    /**
     * Limpiar recursos
     */
    cleanup() {
        if (this.currentStream) {
            this.currentStream.getTracks().forEach(track => track.stop());
            this.currentStream = null;
        }
        
        // Remover elementos UI
        const switchBtn = document.getElementById('camera-switch-btn');
        const indicator = document.getElementById('camera-indicator');
        const selector = document.getElementById('camera-selector');
        
        if (switchBtn) switchBtn.remove();
        if (indicator) indicator.remove();
        if (selector) selector.remove();
    }
};

// Exportar para uso global
window.CameraControl = CameraControl;
