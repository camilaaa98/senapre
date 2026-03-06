/**
 * Dibuja landmarks faciales (puntos biométricos) en el canvas
 * @param {HTMLCanvasElement} canvas - Canvas donde dibujar
 * @param {Array} mesh - Array de puntos 3D del rostro
 * @param {number} confianza - Nivel de confianza de la detección
 */
function dibujarLandmarksFaciales(canvas, mesh, confianza) {
    const ctx = canvas.getContext('2d');


    // Validar canvas
    if (!canvas) return;

    // Color basado en confianza
    const color = confianza >= 0.7 ? '#10b981' : '#f59e0b'; // Verde o amarillo

    // Dibujar todos los puntos del mesh facial (468 puntos)
    ctx.fillStyle = color;
    mesh.forEach(point => {
        const x = point[0];
        const y = point[1];

        // Dibujar punto
        ctx.beginPath();
        ctx.arc(x, y, 1.5, 0, 2 * Math.PI);
        ctx.fill();
    });

    // Puntos clave específicos más destacados
    const puntosDestacados = [
        1,   // Nariz (punta)
        33,  // Ojo derecho (centro)
        263, // Ojo izquierdo (centro)
        61,  // Boca superior
        291, // Boca inferior
        234, // Contorno izquierdo
        454  // Contorno derecho
    ];

    ctx.fillStyle = confianza >= 0.7 ? '#22c55e' : '#fb923c';
    puntosDestacados.forEach(idx => {
        if (mesh[idx]) {
            const x = mesh[idx][0];
            const y = mesh[idx][1];

            // Punto más grande para landmarks clave
            ctx.beginPath();
            ctx.arc(x, y, 3, 0, 2 * Math.PI);
            ctx.fill();

            // Borde blanco
            ctx.strokeStyle = 'rgba(255,255,255,0.8)';
            ctx.lineWidth = 1;
            ctx.stroke();
        }
    });
}
