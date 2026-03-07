'use strict';

// ── Guarda de seguridad y configuración inicial ───────────────
(function guardarSeguridad() {
    const user = (typeof authSystem !== 'undefined' && authSystem.getCurrentUser)
        ? authSystem.getCurrentUser()
        : JSON.parse(localStorage.getItem('user') || 'null');
    const rol = (user?.rol || '').toLowerCase();

    if (!user || !(typeof authSystem !== 'undefined' && authSystem.isAuthenticated())) {
        window.location.href = 'index.html';
        return;
    }

    const bienestarData = user.bienestar_data || [];
    const esRespLiderazgo = bienestarData.includes('voceros_y_representantes');

    if (rol === 'vocero') {
        window.location.href = 'vocero-dashboard.html';
        return;
    }

    // Si tiene alcance de Liderazgo o Bienestar y NO es director, redirigir
    if ((esRespLiderazgo || bienestarData.length > 0) && !authSystem.isAdmin()) {
        if (esRespLiderazgo) {
            window.location.href = 'admin-bienestar-historico.html';
        } else {
            window.location.href = 'admin-bienestar-dashboard.html';
        }
        return;
    }

    // Permitir acceso a roles directivos y de apoyo administrativo
    const rolesDash = ['director', 'admin', 'administrativo', 'coordinador'];
    if (!rolesDash.includes(rol)) {
        window.location.href = 'index.html';
        return;
    }

    // Bienvenida personalizada
    const bienvenida = document.getElementById('admin-bienvenida');
    if (bienvenida && user) {
        bienvenida.textContent = `Bienvenido, ${user.nombre || ''} ${user.apellido || ''}`.trim();
    }

    // Mostrar rol en sidebar
    const rolDisplay = document.getElementById('user-role-display');
    if (rolDisplay) rolDisplay.textContent = rol;
})();


document.addEventListener('DOMContentLoaded', () => {
    cargarEstadisticas();
    cargarGraficaAprendices();
});

async function cargarEstadisticas() {
    try {
        const user = typeof authSystem !== 'undefined' ? authSystem.getCurrentUser() : JSON.parse(localStorage.getItem('user'));
        let params = '';
        if (user && (user.rol || '').toLowerCase() === 'vocero') {
            const scopes = user.vocero_scopes || (user.vocero_scope ? [user.vocero_scope] : []);
            const scopeFicha = scopes.find(s => s.tipo === 'principal' || s.tipo === 'suplente');
            const scopeEnfoque = scopes.find(s => s.tipo === 'enfoque');

            if (scopeFicha) params = `?ficha=${scopeFicha.ficha}`;
            else if (scopeEnfoque) params = `?tabla_poblacion=${scopeEnfoque.poblacion}`;
        }

        const url = `api/reportes.php${params}`;
        const response = await fetch(url);
        const result = await response.json();

        if (result.success) {
            const data = result.data?.resumen || {};

            // Totales Principales con Animación
            if (data.usuarios !== undefined) animarContador('dashTotalUsuarios', data.usuarios);
            if (data.aprendices !== undefined) animarContador('dashTotalAprendices', data.aprendices);
            if (data.programas !== undefined) animarContador('dashTotalProgramas', data.programas);
            if (data.fichas !== undefined) animarContador('dashTotalFichas', data.fichas);

            // Detalle Usuarios (Activos/Inactivos)
            if (document.getElementById('dashUsuariosActivos')) {
                document.getElementById('dashUsuariosActivos').textContent = data.usuarios_activos || 0;
                document.getElementById('dashUsuariosInactivos').textContent = data.usuarios_inactivos || 0;
            }

            // Detalle Voceros
            if (document.getElementById('dashTotalVoceros')) {
                const totalVoceros = (data.voceros_principales || 0) + (data.voceros_suplentes || 0) + (data.voceros_enfoque || 0);
                animarContador('dashTotalVoceros', totalVoceros);
                if (document.getElementById('dashVocerosPrincipales')) document.getElementById('dashVocerosPrincipales').textContent = data.voceros_principales || 0;
                if (document.getElementById('dashVocerosSuplentes')) document.getElementById('dashVocerosSuplentes').textContent = data.voceros_suplentes || 0;
                if (document.getElementById('dashVocerosEnfoque')) document.getElementById('dashVocerosEnfoque').textContent = data.voceros_enfoque || 0;
            }

            // Detalle Aprendices por Estado
            const containerEstados = document.getElementById('dashAprendicesEstados');
            if (containerEstados && data.aprendices_detalle) {
                containerEstados.innerHTML = data.aprendices_detalle.map(est => {
                    const colorClass = getEstadoColorClass(est.estado);
                    return `
                        <span class="detail-item">
                            <i class="fas fa-circle ${colorClass}" style="font-size: 0.6rem;"></i> 
                            ${est.estado}: <strong>${est.cantidad}</strong>
                        </span>
                    `;
                }).join('');
            }
        }
    } catch (error) {
        console.error('Error cargando estadísticas:', error);
    }
}

function getEstadoColorClass(estado) {
    const est = (estado || '').toUpperCase().trim();
    if (est === 'EN FORMACION' || est === 'LECTIVA' || est === 'PRODUCTIVA' || est === 'EN FORMACIÓN') return 'color-success';
    if (est === 'RETIRADO' || est === 'CANCELADO' || est === 'RETIRO VOLUNTARIO' || est === 'CANCELADA') return 'color-error';
    if (est === 'APLAZADO' || est === 'TRASLADO') return 'color-warning';
    return 'color-muted';
}

async function cargarGraficaAprendices() {
    try {
        const user = typeof authSystem !== 'undefined' ? authSystem.getCurrentUser() : JSON.parse(localStorage.getItem('user'));
        let params = '';
        if (user && user.rol.toLowerCase() === 'vocero') {
            const scopes = user.vocero_scopes || (user.vocero_scope ? [user.vocero_scope] : []);
            const scopeFicha = scopes.find(s => s.tipo === 'principal' || s.tipo === 'suplente');
            const scopeEnfoque = scopes.find(s => s.tipo === 'enfoque');

            if (scopeFicha) params = `?ficha=${scopeFicha.ficha}`;
            else if (scopeEnfoque) params = `?tabla_poblacion=${scopeEnfoque.poblacion}`;
        }

        const response = await fetch(`api/reportes.php${params}`);
        const result = await response.json();

        if (result.success && result.data?.aprendices_estado) {
            const ctx = document.getElementById('chartAprendices');
            if (!ctx) return;

            const data = result.data.aprendices_estado;
            const labels = data.map(item => item.estado);
            const values = data.map(item => item.cantidad);

            // Colores SENA:
            // EN FORMACION: Verde (#39A900)
            // CANCELADO: Azul Oscuro (#00324D)
            // RETIRADO: Rojo/Gris oscuro
            const backgroundColors = labels.map(label => {
                const l = label ? label.toUpperCase() : '';
                if (l === 'EN FORMACION') return '#39A900'; // Verde SENA
                if (l === 'CANCELADO' || l === 'CERTIFICADO') return '#00324D';
                return '#999999'; // Default gris
            });

            new Chart(ctx.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: values,
                        backgroundColor: backgroundColors,
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom' },
                        title: { display: true, text: 'Aprendices por Estado' }
                    }
                }
            });
        }
    } catch (error) {
        console.error('Error cargando gráfica de aprendices:', error);
    }
}

function animarContador(id, valorFinal) {
    const elemento = document.getElementById(id);
    if (!elemento) return;

    valorFinal = parseInt(valorFinal);
    if (isNaN(valorFinal)) valorFinal = 0;

    const duracion = 1000;
    const pasos = 20;
    const incremento = valorFinal / pasos;
    let valorActual = 0;
    let pasoActual = 0;

    const intervalo = setInterval(() => {
        pasoActual++;
        valorActual += incremento;

        if (pasoActual >= pasos) {
            valorActual = valorFinal;
            clearInterval(intervalo);
        }

        elemento.textContent = Math.round(valorActual);
    }, duracion / pasos);
}
