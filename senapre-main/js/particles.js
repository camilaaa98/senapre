// Sistema de partículas de fondo para AsistNet
class ParticlesBackground {
  constructor(containerId) {
    this.container = document.getElementById(containerId);
    this.particles = [];
    this.particleCount = 40; // Aumentado de 30 a 40 partículas
    this.animationId = null;
    this.init();
  }

  init() {
    if (!this.container) {
      console.error('[v0] Contenedor de partículas no encontrado');
      return;
    }
    
    console.log('[v0] Inicializando sistema de partículas');
    
    // Crear todas las partículas
    for (let i = 0; i < this.particleCount; i++) {
      this.createParticle(i);
    }
    
    console.log(`[v0] ${this.particleCount} partículas creadas`);
  }

  createParticle(index) {
    const particle = document.createElement('div');
    particle.className = 'particle';
    
    const size = Math.random() * 80 + 40; // Entre 40 y 120px
    particle.style.width = `${size}px`;
    particle.style.height = `${size}px`;
    
    // Posición aleatoria inicial
    particle.style.left = `${Math.random() * 100}%`;
    particle.style.top = `${Math.random() * 100}%`;
    
    // Duración de animación más variada
    const duration = Math.random() * 15 + 10; // Entre 10 y 25 segundos
    particle.style.animationDuration = `${duration}s`;
    
    // Retraso escalonado para crear efecto más natural
    const delay = (index * 0.5) + (Math.random() * 3);
    particle.style.animationDelay = `${delay}s`;
    
    const opacity = Math.random() * 0.4 + 0.2; // Entre 0.2 y 0.6
    particle.style.opacity = opacity;
    
    this.container.appendChild(particle);
    this.particles.push(particle);
  }

  destroy() {
    console.log('[v0] Destruyendo partículas');
    this.particles.forEach(particle => particle.remove());
    this.particles = [];
  }
}

let particlesSystem = null;

document.addEventListener('DOMContentLoaded', () => {
  console.log('[v0] DOM cargado, inicializando partículas');
  particlesSystem = new ParticlesBackground('particles-bg');
});
