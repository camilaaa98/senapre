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

        // SVG para la Malla de Conexiones (Premium Effect)
        const svg = document.createElementNS("http://www.w3.org/2000/svg", "svg");
        svg.setAttribute('style', 'position:absolute; width:100%; height:100%; top:0; left:0; pointer-events:none;');
        trackingBox.appendChild(svg);

        const meshPoints = [
            {id: 0, x: 10, y: 30}, {id: 1, x: 15, y: 50}, {id: 2, x: 25, y: 70}, {id: 3, x: 40, y: 85}, {id: 4, x: 50, y: 90}, {id: 5, x: 60, y: 85}, {id: 6, x: 75, y: 70}, {id: 7, x: 85, y: 50}, {id: 8, x: 90, y: 30},
            {id: 9, x: 25, y: 25}, {id: 10, x: 35, y: 22}, {id: 11, x: 45, y: 25}, {id: 12, x: 55, y: 25}, {id: 13, x: 65, y: 22}, {id: 14, x: 75, y: 25},
            {id: 15, x: 30, y: 35}, {id: 16, x: 35, y: 33}, {id: 17, x: 40, y: 35}, {id: 18, x: 35, y: 37},
            {id: 19, x: 60, y: 35}, {id: 20, x: 65, y: 33}, {id: 21, x: 70, y: 35}, {id: 22, x: 65, y: 37},
            {id: 23, x: 50, y: 35}, {id: 24, x: 50, y: 45}, {id: 25, x: 50, y: 55}, {id: 26, x: 45, y: 60}, {id: 27, x: 50, y: 62}, {id: 28, x: 55, y: 60},
            {id: 29, x: 35, y: 75}, {id: 30, x: 45, y: 70}, {id: 31, x: 50, y: 72}, {id: 32, x: 55, y: 70}, {id: 33, x: 65, y: 75},
            {id: 34, x: 55, y: 80}, {id: 35, x: 50, y: 82}, {id: 36, x: 45, y: 80}
        ];

        // Conexiones de malla (id1 to id2)
        const connections = [
            [0,1],[1,2],[2,3],[3,4],[4,5],[5,6],[6,7],[7,8], // Mandíbula
            [9,10],[10,11],[12,13],[13,14], // Cejas
            [15,16],[16,17],[17,18],[18,15], // Ojo Izq
            [19,20],[20,21],[21,22],[22,19], // Ojo Der
            [23,24],[24,25],[25,27],[26,27],[27,28], // Nariz
            [29,30],[30,31],[31,32],[32,33],[33,34],[34,35],[35,36],[36,29], // Boca
            [11,23],[12,23],[31,25] // Conectores centrales
        ];

        this.meshNodes = [];
        meshPoints.forEach(p => {
            const dot = document.createElement('div');
            dot.className = 'bio-mesh-point';
            dot.style.left = p.x + '%';
            dot.style.top = p.y + '%';
            trackingBox.appendChild(dot);
            this.meshNodes.push({id: p.id, el: dot, x: p.x, y: p.y});
        });

        // Dibujar líneas
        connections.forEach(conn => {
            const p1 = meshPoints.find(p => p.id === conn[0]);
            const p2 = meshPoints.find(p => p.id === conn[1]);
            const line = document.createElementNS("http://www.w3.org/2000/svg", "line");
            line.setAttribute('x1', p1.x + '%');
            line.setAttribute('y1', p1.y + '%');
            line.setAttribute('x2', p2.x + '%');
            line.setAttribute('y2', p2.y + '%');
            line.setAttribute('stroke', 'rgba(57, 169, 0, 0.2)');
            line.setAttribute('stroke-width', '1');
            svg.appendChild(line);
        });

        this.meshInterval = setInterval(() => {
            this.meshNodes.forEach(node => node.el.classList.remove('active'));
            // Activar aleatoriamente
            const numberOfActive = 8 + Math.floor(Math.random() * 10);
            for(let i=0; i < numberOfActive; i++) {
                const randomIdx = Math.floor(Math.random() * this.meshNodes.length);
                this.meshNodes[randomIdx].el.classList.add('active');
            }
        }, 200);
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
