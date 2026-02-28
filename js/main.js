/**
 * AuthSystem - Unified Authentication for AsistNet/SENAPRE
 * Consolidates legacy and v0 authentication logic.
 */
class AuthSystem {
    constructor() {
        this.storageKey = 'user';
        this.currentUser = null;
        this.loadUserFromStorage();
    }

    loadUserFromStorage() {
        const savedUser = localStorage.getItem(this.storageKey);
        if (savedUser) {
            try {
                this.currentUser = JSON.parse(savedUser);
            } catch (e) {
                console.error('Error parsing user from storage', e);
                this.currentUser = null;
            }
        }
    }

    // Validation helpers
    validateEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    validatePassword(password) {
        return password.length >= 6;
    }

    // UI Helpers (Legacy support)
    showValidation(inputElement, isValid) {
        const validationIcon = inputElement.parentElement.querySelector('.validation-icon');
        if (!validationIcon) return;

        if (isValid === null) {
            inputElement.classList.remove('valid', 'invalid');
            validationIcon.classList.remove('show', 'valid', 'invalid');
            return;
        }

        inputElement.classList.remove('valid', 'invalid');
        validationIcon.classList.remove('valid', 'invalid');

        if (isValid) {
            inputElement.classList.add('valid');
            validationIcon.classList.add('show', 'valid');
        } else {
            inputElement.classList.add('invalid');
            validationIcon.classList.add('show', 'invalid');
        }
    }

    // Login logic
    async login(email, password) {
        try {
            const response = await fetch('api/auth.php?action=login', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ correo: email, password: password })
            });

            const result = await response.json();

            if (result.success && result.data) {
                // Normalize data structure if needed
                this.currentUser = {
                    id: result.data.id_usuario,
                    nombre: result.data.nombre,
                    apellido: result.data.apellido,
                    correo: result.data.correo,
                    rol: result.data.rol,
                    instructor_data: result.data.instructor_data,
                    bienestar_data: result.data.bienestar_data || []
                };

                localStorage.setItem(this.storageKey, JSON.stringify(this.currentUser));
                return this.currentUser;
            } else {
                throw new Error(result.message || 'Credenciales inválidas');
            }
        } catch (error) {
            throw new Error(error.message || 'Error al conectar con el servidor');
        }
    }

    async logout() {
        try {
            await fetch('api/logout.php');
        } catch (e) {
            console.error('Error in backend logout', e);
        } finally {
            this.currentUser = null;
            localStorage.removeItem(this.storageKey);
            sessionStorage.clear();
            window.location.href = 'index.html';
        }
    }

    isAuthenticated() {
        return this.currentUser !== null;
    }

    getCurrentUser() {
        return this.currentUser;
    }

    isAdmin() {
        if (!this.currentUser) return false;
        const rol = this.currentUser.rol.toLowerCase();
        return ['director', 'administrativo', 'coordinador', 'admin', 'administrador', 'vocero'].includes(rol);
    }

    redirectToDashboard() {
        if (!this.currentUser) return;

        const rol = this.currentUser.rol.toLowerCase();
        if (['director', 'administrativo', 'coordinador', 'admin', 'administrador', 'vocero'].includes(rol)) {
            window.location.href = 'admin-dashboard.html';
        } else if (rol === 'instructor') {
            window.location.href = 'instructor-dashboard.html';
        }
    }
}

// Initialize global navigation features
function initGlobalNavigation() {
    document.addEventListener('DOMContentLoaded', () => {
        const header = document.querySelector('.content-header') || document.querySelector('.page-header');
        const isDashboard = window.location.pathname.includes('dashboard.html');
        const isLogin = window.location.pathname.includes('index.html');

        if (header && !isDashboard && !isLogin) {
            // Verificar si ya existe un botón de volver para no duplicar
            if (header.querySelector('.btn-back-global')) return;

            const btnBack = document.createElement('button');
            btnBack.className = 'btn-back-global';
            btnBack.innerHTML = '<i class="fas fa-arrow-left"></i> Volver';
            btnBack.onclick = () => window.history.back();

            // Estilos rápidos para el botón (pueden moverse a CSS)
            btnBack.style.cssText = `
                padding: 8px 15px;
                background: #f1f5f9;
                border: 1px solid #e2e8f0;
                border-radius: 6px;
                color: #475569;
                cursor: pointer;
                font-weight: 600;
                font-size: 0.9rem;
                display: flex;
                align-items: center;
                gap: 8px;
                transition: all 0.2s;
                margin-right: 15px;
            `;

            btnBack.onmouseover = () => { btnBack.style.background = '#e2e8f0'; };
            btnBack.onmouseout = () => { btnBack.style.background = '#f1f5f9'; };

            // Insertar al principio del header
            header.prepend(btnBack);
            header.style.display = 'flex';
            header.style.alignItems = 'center';
            header.style.justifyContent = 'flex-start'; // Mantener alineación a la izquierda si hay botón

            // Ajustar el título para que no quede pegado si está centrado
            const title = header.querySelector('.content-title') || header.querySelector('.page-title');
            if (title) {
                title.style.margin = '0 auto'; // Centrar título respecto al espacio restante
                title.style.transform = 'translateX(-40px)'; // Compensa visualmente el botón a la izquierda (aprox)
            }
        }
    });
}

// Global initialization
initGlobalNavigation();
const authSystem = new AuthSystem();

// Generic helper for debouncing
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Global Sidebar Toggle Submenu
function toggleSubmenu(event, submenuId) {
    if (event) event.preventDefault();
    const submenu = document.getElementById(submenuId);
    if (!submenu) return;

    const parent = submenu.closest('.menu-item');
    if (!parent) return;

    if (submenu.classList.contains('show')) {
        submenu.classList.remove('show');
        parent.classList.remove('open');
    } else {
        submenu.classList.add('show');
        parent.classList.add('open');
    }
}

/**
 * Filtra dinámicamente el menú lateral y el dashboard según el rol y áreas de bienestar.
 */
function aplicarRestriccionesDeRol() {
    const user = authSystem.getCurrentUser();
    if (!user) return;

    // Actualizar visualización del rol en el sidebar
    const roleDisplay = document.getElementById('user-role-display');
    if (roleDisplay) {
        let displayRole = user.rol.charAt(0).toUpperCase() + user.rol.slice(1);
        if (user.bienestar_data && user.bienestar_data.length > 0) {
            const areaMap = {
                'jefe_bienestar': 'Jefe de Bienestar',
                'voceros_y_representantes': 'Liderazgo',
                'enfermeria': 'Promoción y Prevención de Enfermedades',
                'socioemocional': 'Socioemocional',
                'deporte': 'Bienestar (Deporte)',
                'arte': 'Bienestar (Cultura)',
                'apoyos': 'Bienestar (Apoyos)'
            };
            const displayAreas = user.bienestar_data.map(a => areaMap[a] || a);
            displayRole = displayAreas[0]; // Mostrar la primera area como rol
        }
        roleDisplay.textContent = displayRole;
    }

    const rol = (user.rol || '').toLowerCase();
    const bienestar = user.bienestar_data || [];
    const scopes = user.vocero_scopes || (user.vocero_scope ? [user.vocero_scope] : []);
    const scope = scopes.length > 0 ? scopes[0] : null;

    const esDirector = rol === 'director' || rol === 'admin' || rol === 'administrador';
    const esJefeBienestar = bienestar.includes('jefe_bienestar');
    const esRespLiderazgo = bienestar.includes('voceros_y_representantes');
    const esVocero = rol === 'vocero';

    // Áreas restringidas (en construcción)
    const subAreasBienestar = ['enfermeria', 'deporte', 'arte', 'apoyos'];
    const esSoloSubArea = bienestar.some(area => subAreasBienestar.includes(area)) && !esDirector && !esJefeBienestar && !esVocero && !esRespLiderazgo;

    // Actualizar visualización del rol en el sidebar si es vocero
    if (esVocero && scopes.length > 0 && roleDisplay) {
        const typeNames = {
            'principal': 'Vocero Principal',
            'suplente': 'Vocero Suplente',
            'enfoque': (s) => `Vocero ${s.poblacion || 'Enfoque'}`,
            'representante': (s) => `Representante ${s.jornada || ''}`
        };

        const displayLines = scopes.map(s => {
            const mapper = typeNames[s.tipo];
            return (typeof mapper === 'function') ? mapper(s) : mapper;
        });

        roleDisplay.innerHTML = displayLines.join('<br>');
    }

    // 1. RESTRICCIÓN PARA SUB-ÁREAS (Enfermería, Deporte, etc.)
    if (esSoloSubArea) {
        if (window.location.pathname.includes('admin-dashboard.html')) {
            const mainContent = document.querySelector('.main-content');
            if (mainContent) {
                mainContent.innerHTML = `
                    <div class="content-header" style="text-align: center; margin-top: 50px;">
                        <h1 class="content-title">Panel en Construcción</h1>
                        <p class="content-description">Hola ${user.nombre}, tu área asignada (${bienestar.join(', ')}) aún se encuentra en desarrollo.</p>
                        <i class="fas fa-tools" style="font-size: 5rem; color: #cbd5e1; margin-top: 30px;"></i>
                    </div>
                `;
            }
        }
        ocultarMenusRestringidos(true);
        return;
    }

    // 2. RESTRICCIÓN PARA VOCEROS (Estudiantes)
    if (esVocero) {
        filtrarDashboardParaVocero(scope);
        ocultarMenusVocero(scope);
        return;
    }

    // 3. RESTRICCIÓN PARA JEFE DE BIENESTAR Y RESP. LIDERAZGO
    if ((esJefeBienestar || esRespLiderazgo) && !esDirector) {
        filtrarDashboardParaBienestar(esRespLiderazgo);
        ocultarMenusRestringidos(false, esRespLiderazgo);
    }
}

function filtrarDashboardParaVocero(scope) {
    if (!window.location.pathname.includes('admin-dashboard.html')) return;

    const cards = document.querySelectorAll('.main-content .card');
    cards.forEach(card => {
        const text = card.innerText.toLowerCase();

        // El vocero solo puede ver "Aprendices" (que incluye población)
        const permitido = text.includes('aprendices') || text.includes('resumen') || text.id === 'panel-estadisticas-poblacion';

        if (!permitido) {
            card.style.display = 'none';
        } else if (text.includes('aprendices')) {
            // Personalizar mensaje de la tarjeta
            const p = card.querySelector('p');
            if (p) {
                if (scope.tipo === 'principal' || scope.tipo === 'suplente') {
                    p.textContent = `Gestionar aprendices de la Ficha ${scope.ficha}`;
                } else if (scope.tipo === 'enfoque') {
                    p.textContent = `Gestionar población ${scope.poblacion}`;
                }
            }
        }
    });

    // Para vocero de enfoque, cargar estadísticas si están disponibles
    if (scope && scope.tipo === 'enfoque') {
        cargarEstadisticasPoblacionDashboard();
    }
}

function ocultarMenusVocero(scope) {
    const menuItems = document.querySelectorAll('.sidebar-menu .menu-item');
    menuItems.forEach(item => {
        const text = item.innerText.toLowerCase();

        // Menús permitidos para voceros
        const permitido = text.includes('dashboard') ||
            text.includes('aprendices') ||
            text.includes('cerrar sesión');

        if (!permitido) {
            item.style.display = 'none';
        }

        // Si es el menú de aprendices, filtrar submenú
        const submenuAprendices = item.querySelector('#submenu-aprendices');
        if (submenuAprendices) {
            const sublinks = submenuAprendices.querySelectorAll('li');
            sublinks.forEach(li => {
                const subtext = li.innerText.toLowerCase();

                if (scope.tipo === 'enfoque') {
                    // Vocero enfoque solo ve Tipo de Población y Lista
                    if (!subtext.includes('población') && !subtext.includes('lista')) {
                        li.style.display = 'none';
                    }
                } else {
                    // Vocero principal/suplente ve lista (que estará filtrada por ficha)
                    if (!subtext.includes('lista')) {
                        li.style.display = 'none';
                    }
                }
            });
        }
    });
}

function filtrarDashboardParaBienestar(esRespLiderazgo = false) {
    if (!window.location.pathname.includes('admin-dashboard.html')) return;

    // Mostrar botón Volver
    const btnVolver = document.getElementById('btn-volver-dashboard');
    if (btnVolver) btnVolver.style.display = 'block';

    // INTERCAMBIO DE CONTENEDORES DE TARJETAS
    const adminCards = document.getElementById('dashboard-admin-cards');
    const bienestarCards = document.getElementById('dashboard-bienestar-cards');

    if (adminCards) adminCards.style.display = 'none';
    if (bienestarCards) {
        bienestarCards.style.display = 'grid';
        bienestarCards.style.gridTemplateColumns = 'repeat(auto-fit, minmax(280px, 1fr))';
    }

    // Modificar enlaces de Accesos Rápidos para Bienestar
    const btnAsis = document.querySelector('#accesos-rapidos-container button:nth-child(1)');
    const btnRepo = document.querySelector('#accesos-rapidos-container button:nth-child(2)');

    if (btnAsis) {
        btnAsis.setAttribute('onclick', "window.location.href='admin-bienestar-historico.html'");
        btnAsis.innerHTML = '<i class="fas fa-history"></i> Consultar Asistencias (Líderes)';
    }
    if (btnRepo) {
        btnRepo.setAttribute('onclick', "window.location.href='admin-bienestar-historico.html#seccion-reportes'");
        btnRepo.innerHTML = '<i class="fas fa-chart-pie"></i> Ver Reportes (Líderes)';
        btnRepo.style.background = '#00324D';
    }

    // Mostrar panel de estadísticas para Jefe de Bienestar (o también resp liderazgo si se desea)
    const panelStats = document.getElementById('panel-estadisticas-poblacion');
    if (panelStats) {
        panelStats.style.display = 'block';
    }

    cargarEstadisticasPoblacionDashboard();
}

function ocultarMenusRestringidos(ocultarTodo = false, esRespLiderazgo = false) {
    const menuItems = document.querySelectorAll('.sidebar-menu .menu-item');

    menuItems.forEach(item => {
        const text = item.innerText.toLowerCase();

        if (ocultarTodo) {
            if (!text.includes('cerrar sesión') && !text.includes('dashboard')) {
                item.style.display = 'none';
            }
            return;
        }

        // Permisos base
        let permitido = text.includes('dashboard') ||
            text.includes('bienestar') ||
            text.includes('aprendices') ||
            text.includes('cerrar sesión');

        if (esRespLiderazgo) {
            // Resp. Liderazgo es más restrictivo
            permitido = text.includes('dashboard') ||
                text.includes('aprendices') ||
                text.includes('cerrar sesión');
        }

        if (!permitido) {
            item.style.display = 'none';
        }

        // Filtrar submenús de aprendices para que solo vea población
        const submenuAprendices = item.querySelector('#submenu-aprendices');
        if (submenuAprendices) {
            const sublinks = submenuAprendices.querySelectorAll('li');
            sublinks.forEach(li => {
                const subtext = li.innerText.toLowerCase();
                if (esRespLiderazgo) {
                    // Resp Liderazgo solo Población y Lista
                    if (!subtext.includes('población') && !subtext.includes('lista')) {
                        li.style.display = 'none';
                    }
                } else {
                    // Jefe de Bienestar ve Lista y Población
                    if (!subtext.includes('población') && !subtext.includes('lista')) {
                        li.style.display = 'none';
                    }
                }
            });
        }
    });
}

/**
 * Carga las estadísticas de población para el panel del dashboard.
 */
function cargarEstadisticasPoblacionDashboard() {
    const canvas = document.getElementById('chartPoblacionDash');
    if (!canvas) return;

    fetch('api/aprendices.php?limit=-1')
        .then(r => r.json())
        .then(d => {
            if (!d.success) {
                console.error('Error in API response:', d.message);
                return;
            }

            const c = { mujer: 0, indigena: 0, narp: 0, campesino: 0, lgbtiq: 0, discapacidad: 0 };
            const estadosInactivos = ['RETIRO', 'CANCELADO', 'RETIRADO', 'FINALIZADO', 'TRASLADO', 'APLAZADO', 'CANCELADA', 'FINALIZADA'];

            d.data.forEach(a => {
                const estado = (a.estado || '').toUpperCase().trim();
                if (estadosInactivos.includes(estado)) return;
                if (!estado) return;

                if (a.mujer == 1) c.mujer++;
                if (a.indigena == 1) c.indigena++;
                if (a.narp == 1) c.narp++;
                if (a.campesino == 1) c.campesino++;
                if (a.lgbtiq == 1) c.lgbtiq++;
                if (a.discapacidad == 1) c.discapacidad++;
            });

            const totalPob = Object.values(c).reduce((a, b) => a + b, 0);

            const dMujer = document.getElementById('dashStatMujer');
            const dIndi = document.getElementById('dashStatIndigena');
            const dNarp = document.getElementById('dashStatNarp');
            const dLgbt = document.getElementById('dashStatLgbtiq');
            const dTot = document.getElementById('dashStatTotal');

            if (dMujer) dMujer.textContent = c.mujer;
            if (dIndi) dIndi.textContent = c.indigena;
            if (dNarp) dNarp.textContent = c.narp;
            if (dLgbt) dLgbt.textContent = c.lgbtiq;
            if (dTot) dTot.textContent = totalPob;

            // Actualizar números en panel detalle (si existen)
            const updateText = (id, val) => {
                const el = document.getElementById(id);
                if (el) el.textContent = val;
            };

            updateText('stat-mujer', c.mujer);
            updateText('stat-indigena', c.indigena);
            updateText('stat-narp', c.narp);
            updateText('stat-campesino', c.campesino);
            updateText('stat-lgbtiq', c.lgbtiq);
            updateText('stat-discapacidad', c.discapacidad);
            updateText('stat-total-poblacion', totalPob);

            // Renderizar gráfica
            const ctx = canvas.getContext('2d');
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Mujeres', 'Indígenas', 'NARP', 'Campesinos', 'LGBTIQ+', 'Discapacidad'],
                    datasets: [{
                        data: [c.mujer, c.indigena, c.narp, c.campesino, c.lgbtiq, c.discapacidad],
                        backgroundColor: ['#a855f7', '#84cc16', '#dc2626', '#6B8E23', '#ef4444', '#4A90E2'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom', labels: { boxWidth: 12, padding: 15 } }
                    }
                }
            });
        })
        .catch(error => {
            console.error('Error fetching population stats:', error);
            const dTot = document.getElementById('dashStatTotal');
            if (dTot) dTot.textContent = 'Error';
        });
}

// Inicializar restricciones
document.addEventListener('DOMContentLoaded', aplicarRestriccionesDeRol);

