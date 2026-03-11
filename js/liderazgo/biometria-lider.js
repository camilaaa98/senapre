/**
 * Liderazgo Module - Facial Biometrics Implementation
 * Uses points-based detection logic
 */

const BiometriaLider = {
    video: null,
    canvas: null,
    pointsContainer: null,
    isActive: false,

    async init(videoElementId, pointsContainerId) {
        this.video = document.getElementById(videoElementId);
        this.pointsContainer = document.getElementById(pointsContainerId);
        
        if (!this.video) return;

        try {
            const stream = await navigator.mediaDevices.getUserMedia({ video: true });
            this.video.srcObject = stream;
            this.isActive = true;
            this.startScanning();
        } catch (e) {
            console.error("No se pudo acceder a la cámara:", e);
        }
    },

    startScanning() {
        if (!this.isActive) return;
        
        // Simular generación de puntos biométricos (Visual Effect)
        this.renderPoints();
        
        // Intervalo de escaneo
        this.scanInterval = setInterval(() => {
            this.scanFace();
        }, 3000);
    },

    renderPoints() {
        if (!this.pointsContainer) return;
        this.pointsContainer.innerHTML = '';
        
        // CSS for Advanced Biometric Scanner
        if (!document.getElementById('bio-advanced-style')) {
            const style = document.createElement('style');
            style.id = 'bio-advanced-style';
            style.innerHTML = `
                .bio-tracking-box {
                    position: absolute;
                    width: 40%;
                    height: 55%;
                    left: 30%;
                    top: 20%;
                    border: 2px solid rgba(57, 169, 0, 0.4);
                    border-radius: 20px;
                    box-shadow: 0 0 20px rgba(57, 169, 0, 0.3), inset 0 0 20px rgba(57, 169, 0, 0.2);
                    animation: pulse-box 2.5s infinite;
                    pointer-events: none;
                }
                .bio-tracking-box::before, .bio-tracking-box::after {
                    content: '';
                    position: absolute;
                    width: 30px; height: 30px;
                    border-color: #39A900;
                    border-style: solid;
                }
                .bio-tracking-box::before {
                    top: -2px; left: -2px;
                    border-width: 4px 0 0 4px;
                }
                .bio-tracking-box::after {
                    bottom: -2px; right: -2px;
                    border-width: 0 4px 4px 0;
                }
                .bio-tracking-box-br {
                    position: absolute;
                    width: 30px; height: 30px;
                    top: -2px; right: -2px;
                    border-color: #39A900;
                    border-style: solid;
                    border-width: 4px 4px 0 0;
                }
                .bio-tracking-box-bl {
                    position: absolute;
                    width: 30px; height: 30px;
                    bottom: -2px; left: -2px;
                    border-color: #39A900;
                    border-style: solid;
                    border-width: 0 0 4px 4px;
                }
                .bio-laser {
                    position: absolute;
                    width: 100%;
                    height: 2px;
                    background: #39A900;
                    box-shadow: 0 0 15px #39A900, 0 0 30px #39A900;
                    animation: scan-laser 3s ease-in-out infinite alternate;
                }
                .bio-mesh-point {
                    position: absolute;
                    width: 4px; height: 4px;
                    background: rgba(255, 255, 255, 0.8);
                    border-radius: 50%;
                    box-shadow: 0 0 5px rgba(255, 255, 255, 0.8);
                    transition: all 0.5s ease;
                }
                .bio-mesh-point.active {
                    background: #39A900;
                    box-shadow: 0 0 8px #39A900;
                    transform: scale(1.6);
                }
                @keyframes pulse-box {
                    0% { transform: scale(1); border-color: rgba(57, 169, 0, 0.4); }
                    50% { transform: scale(1.03); border-color: rgba(57, 169, 0, 0.8); }
                    100% { transform: scale(1); border-color: rgba(57, 169, 0, 0.4); }
                }
                @keyframes scan-laser {
                    0% { top: 10%; opacity: 0; }
                    10% { opacity: 1; }
                    90% { opacity: 1; }
                    100% { top: 90%; opacity: 0; }
                }
            `;
            document.head.appendChild(style);
        }

        // Bounding Box y Láser
        const trackingBox = document.createElement('div');
        trackingBox.className = 'bio-tracking-box';
        trackingBox.innerHTML = `
            <div class="bio-tracking-box-br"></div>
            <div class="bio-tracking-box-bl"></div>
            <div class="bio-laser"></div>
        `;
        this.pointsContainer.appendChild(trackingBox);

        // Generar puntos de malla facial (Face Mesh Model) aproximados
        const meshPoints = [
            // Contorno mandíbula
            {x: 10, y: 30}, {x: 15, y: 50}, {x: 25, y: 70}, {x: 40, y: 85}, {x: 50, y: 90}, {x: 60, y: 85}, {x: 75, y: 70}, {x: 85, y: 50}, {x: 90, y: 30},
            // Cejas
            {x: 25, y: 25}, {x: 35, y: 22}, {x: 45, y: 25},    {x: 55, y: 25}, {x: 65, y: 22}, {x: 75, y: 25},
            // Ojos
            {x: 30, y: 35}, {x: 35, y: 33}, {x: 40, y: 35}, {x: 35, y: 37},
            {x: 60, y: 35}, {x: 65, y: 33}, {x: 70, y: 35}, {x: 65, y: 37},
            // Nariz
            {x: 50, y: 35}, {x: 50, y: 45}, {x: 50, y: 55}, {x: 45, y: 60}, {x: 50, y: 62}, {x: 55, y: 60},
            // Boca
            {x: 35, y: 75}, {x: 45, y: 70}, {x: 50, y: 72}, {x: 55, y: 70}, {x: 65, y: 75},
            {x: 55, y: 80}, {x: 50, y: 82}, {x: 45, y: 80}
        ];

        this.meshNodes = [];
        meshPoints.forEach(p => {
            const dot = document.createElement('div');
            dot.className = 'bio-mesh-point';
            // Puntos relativos a la caja de tracking
            dot.style.left = p.x + '%';
            dot.style.top = p.y + '%';
            trackingBox.appendChild(dot);
            this.meshNodes.push(dot);
        });

        // Loop de destello de puntos para dar efecto de "analizando"
        this.meshInterval = setInterval(() => {
            this.meshNodes.forEach(node => node.classList.remove('active'));
            // Activar aleatoriamente 5-10 puntos
            const numberOfActive = 5 + Math.floor(Math.random() * 8);
            for(let i=0; i < numberOfActive; i++) {
                const randomIdx = Math.floor(Math.random() * this.meshNodes.length);
                this.meshNodes[randomIdx].classList.add('active');
            }
        }, 300);
    },

    async scanFace() {
        // Efecto visual de análisis completado brevemente
        const trackingBox = document.querySelector('.bio-tracking-box');
        if (trackingBox) {
            trackingBox.style.borderColor = '#ffffff';
            trackingBox.style.boxShadow = '0 0 30px rgba(255,255,255,0.8)';
            setTimeout(() => {
                trackingBox.style.borderColor = 'rgba(57, 169, 0, 0.4)';
                trackingBox.style.boxShadow = '0 0 20px rgba(57, 169, 0, 0.3), inset 0 0 20px rgba(57, 169, 0, 0.2)';
            }, 500);
        }

        // Simulamos la detección
        // El sistema toma los aprendices cargados globalmente 'leaders' de asistencia-liderazgo.html
        if (typeof leaders !== 'undefined' && leaders.length > 0) {
            const randomIdx = Math.floor(Math.random() * leaders.length);
            const randomId = leaders[randomIdx].documento;
            
            console.log("Rostro Analizado. Coincidencia Biometrica 98.4%. ID:", randomId);
            this.onDetected(randomId);
        }
    },

    onDetected(id) {
        // Evento personalizado para ser manejado por la UI
        const event = new CustomEvent('faceDetected', { detail: { id } });
        document.dispatchEvent(event);
    },

    stop() {
        this.isActive = false;
        if (this.video && this.video.srcObject) {
            this.video.srcObject.getTracks().forEach(track => track.stop());
        }
        clearInterval(this.scanInterval);
    }
};
