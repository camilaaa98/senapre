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
                // Guardar objeto completo para no perder scopes, instructor_data, etc.
                this.currentUser = result.data;
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
            window.location.href = 'index';
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
        const rol = (this.currentUser.rol || '').toLowerCase();
        // Solo Directores y Administradores generales son "Admins" reales con poder total
        return ['director', 'admin'].includes(rol);
    }

    redirectToDashboard() {
        if (!this.currentUser) return;
        const rol = this.currentUser.rol.toLowerCase();

        // Función interna para ofuscar (cifrado simple solicitado)
        const encrypt = (path) => btoa(path);

        if (rol === 'vocero') {
            window.location.href = 'vocero-dashboard';
        } else if (rol === 'bienestar') {
            const user = this.currentUser;
            const esRespLiderazgo = user.bienestar_data && user.bienestar_data.includes('voceros_y_representantes');
            window.location.href = esRespLiderazgo ? 'admin-bienestar-historico' : 'bienestar-aprendiz';
        } else if (rol === 'instructor') {
            window.location.href = 'instructor-dashboard';
        } else if (['director', 'administrativo', 'coordinador', 'admin', 'administrador'].includes(rol)) {
            window.location.href = 'admin-dashboard';
        }
    }
}

// Initialize global navigation features
function initGlobalNavigation() {
    document.addEventListener('DOMContentLoaded', () => {
        // Enforce Professional Responsiveness
        const head = document.head;
        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = 'css/responsive-professional.css?v=' + new Date().getTime();
        head.appendChild(link);

        const header = document.querySelector('.content-header') || document.querySelector('.page-header') || document.querySelector('.premium-header');
        const isDashboard = window.location.pathname.includes('dashboard');
        const isLogin = window.location.pathname.includes('index') || window.location.pathname === '/';

        // Sidebar Toggle for Mobile
        const dashboardContainer = document.querySelector('.dashboard-container');
        if (dashboardContainer) {
            // Add Overlay
            const overlay = document.createElement('div');
            overlay.className = 'sidebar-overlay';
            dashboardContainer.appendChild(overlay);

            // Add Hamburger
            const toggle = document.createElement('button');
            toggle.className = 'mobile-nav-toggle';
            toggle.innerHTML = '<i class="fas fa-bars"></i>';
            document.body.appendChild(toggle);

            const sidebar = document.querySelector('.sidebar');

            toggle.onclick = () => {
                sidebar.classList.toggle('active');
                overlay.classList.toggle('active');
                toggle.innerHTML = sidebar.classList.contains('active') ? '<i class="fas fa-times"></i>' : '<i class="fas fa-bars"></i>';
            };

            overlay.onclick = () => {
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
                toggle.innerHTML = '<i class="fas fa-bars"></i>';
            };
        }

        if (header && !isDashboard && !isLogin) {
            if (header.querySelector('.btn-back-professional')) return;

            // Asegurar que el header sea el contenedor de referencia
            header.style.position = 'relative';
            header.style.display = 'flex';
            header.style.flexDirection = 'column';
            header.style.alignItems = 'center'; // Centrado horizontal para el contenido base
            header.style.justifyContent = 'center';
            header.style.width = '100%';
            header.style.minHeight = '80px';
            header.style.padding = '10px 0';

            // Forzar centrado de títulos y subtítulos
            const titleElement = header.querySelector('h1, h2, .content-title, .page-title');
            if (titleElement) {
                titleElement.style.textAlign = 'center';
                titleElement.style.width = '100%';
                titleElement.style.margin = '0 auto';
                titleElement.style.display = 'block';
            }

            const descElement = header.querySelector('p, .content-description, .sidebar-subtitle');
            if (descElement) {
                descElement.style.textAlign = 'center';
                descElement.style.width = '100%';
                descElement.style.opacity = '0.8';
                descElement.style.margin = '5px auto 0';
            }

            const btnBack = document.createElement('a');
            btnBack.className = 'btn-back-professional';
            btnBack.href = '#';
            btnBack.innerHTML = '<i class="fas fa-arrow-left"></i> Volver';
            btnBack.onclick = (e) => {
                e.preventDefault();
                window.history.back();
            };

            // Estilo para posicionar el botón arriba a la derecha de forma absoluta
            btnBack.style.position = 'absolute';
            btnBack.style.right = '20px';
            btnBack.style.top = '50%';
            btnBack.style.transform = 'translateY(-50%)';
            btnBack.style.margin = '0';

            header.appendChild(btnBack);
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
    const submenu = document.getElementById(submenuId);
    if (!submenu) return;

    const parent = submenu.closest('.menu-item');
    if (!parent) return;

    const link = parent.querySelector('.menu-link');
    const href = link?.getAttribute('href');

    // Si clicamos el padre y ya está abierto, y el href no es '#', permitimos la navegación
    // Si no estamos en la página del href, permitimos la navegación
    const isCurrentPage = href && window.location.pathname.endsWith(href);

    if (event) {
        if (href && href !== '#' && !isCurrentPage) {
            // Permitir navegación natural si no es la página actual
            return;
        }
        event.preventDefault();
    }

    if (submenu.classList.contains('show')) {
        submenu.classList.remove('show');
        parent.classList.remove('open');
    } else {
        // Cerrar otros submenús abiertos (opcional, para limpieza)
        document.querySelectorAll('.submenu.show').forEach(s => {
            if (s.id !== submenuId) {
                s.classList.remove('show');
                s.closest('.menu-item')?.classList.remove('open');
            }
        });
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
        let displayRole = 'Usuario';
        const roleMap = {
            'director': 'Director de Centro',
            'administrativo': 'Personal Administrativo',
            'instructor': 'Instructor SENA',
            'vocero': 'Vocero Estudiantil',
            'admin': 'Administrador Sistema'
        };

        displayRole = roleMap[rol] || displayRole;

        if (user.bienestar_data && user.bienestar_data.length > 0) {
            const areaMap = {
                'jefe_bienestar': 'Jefe de Bienestar',
                'voceros_y_representantes': 'Liderazgo',
                'enfermeria': 'Bienestar (Salud)',
                'socioemocional': 'Bienestar (Socioemocional)',
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

    // Unificado con AuthSystem
    const esDirector = authSystem.isAdmin();
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
        if (window.location.pathname.includes('admin-dashboard')) {
            const mainContent = document.querySelector('.main-content');
            if (mainContent) {
                mainContent.innerHTML = `
                    <div class="content-header content-center">
                        <h1 class="content-title">Panel en Construcción</h1>
                        <p class="content-description">Hola ${user.nombre}, tu área asignada (${bienestar.join(', ')}) aún se encuentra en desarrollo.</p>
                        <i class="fas fa-tools icon-tools-large"></i>
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
        ocultarMenusRestringidos(false, esRespLiderazgo, esDirector);
    } else if (rol === 'administrativo') {
        // El administrativo es un cargo de apoyo, no tiene panel de director
        filtrarDashboardParaAdministrativo();
        ocultarMenusAdministrativo();
    } else if (esDirector) {
        // Asegurar que el director vea todo el menú
        ocultarMenusRestringidos(false, false, true);
    }
}

function ocultarMenusAdministrativo() {
    const menusSensibles = ['menu-usuarios', 'menu-programas', 'menu-asignaciones'];
    menusSensibles.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.style.display = 'none';
    });
}

function filtrarDashboardParaAdministrativo() {
    if (!window.location.pathname.includes('admin-dashboard')) return;

    // Ocultar tarjetas de alta gerencia o configuración
    const idsOcultar = ['dashTotalUsuarios']; // Por ejemplo, no debe gestionar usuarios
    idsOcultar.forEach(id => {
        const el = document.getElementById(id);
        const card = el?.closest('.stat-card');
        if (card) card.style.display = 'none';
    });

    const title = document.querySelector('.content-title');
    if (title) title.textContent = 'Panel de Apoyo Administrativo';
}

function filtrarDashboardParaVocero(scope) {
    if (!window.location.pathname.includes('admin-dashboard')) return;

    const cards = document.querySelectorAll('.main-content .card');
    const title = document.querySelector('.content-title');
    const desc = document.querySelector('.content-description');

    if (title && scope) {
        if (scope.tipo === 'principal' || scope.tipo === 'suplente') {
            title.textContent = `Panel Vocería - Ficha ${scope.ficha}`;
            if (desc) desc.textContent = 'Gestión y seguimiento de aprendices asignados';
        } else if (scope.tipo === 'enfoque') {
            title.textContent = `Panel Enfoque - ${scope.poblacion}`;
            if (desc) desc.textContent = `Gestión de aprendices en la población ${scope.poblacion}`;
        }
    }

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
        } else {
            item.style.display = 'block';
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
    if (!window.location.pathname.includes('admin-dashboard')) return;

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
        btnAsis.setAttribute('onclick', "window.location.href='admin-bienestar-historico'");
        btnAsis.innerHTML = '<i class="fas fa-history"></i> Consultar Asistencias (Líderes)';
    }
    if (btnRepo) {
        btnRepo.setAttribute('onclick', "window.location.href='admin-bienestar-historico.html#seccion-reportes'");
        btnRepo.innerHTML = '<i class="fas fa-chart-pie"></i> Ver Reportes (Líderes)';
        btnRepo.style.background = '#00324D';
    }

    // Mostrar panel de estadísticas para Jefe de Bienestar y Responsable de Liderazgo
    const panelStats = document.getElementById('panel-estadisticas-poblacion');
    if (panelStats) {
        panelStats.style.display = 'block';
    }

    cargarEstadisticasPoblacionDashboard();
}

function ocultarMenusRestringidos(ocultarTodo = false, esRespLiderazgo = false, esDirector = false) {
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

        // Los directores y administradores ven TODO
        if (esDirector) permitido = true;

        if (esRespLiderazgo) {
            const academicKeywords = ['aprendices', 'fichas', 'programas', 'asignar', 'asistencias', 'reportes', 'usuarios'];
            const isAcademic = academicKeywords.some(key => text.includes(key));

            // Permitir solo Liderazgo (antes Bienestar) y Cerrar Sesión
            permitido = text.includes('bienestar') || text.includes('liderazgo') || text.includes('cerrar sesión');

            // Rebranding dinámico de Bienestar a Liderazgo
            if (text.includes('bienestar')) {
                const span = item.querySelector('span');
                if (span) span.textContent = 'Liderazgo Estudiantil';
                const link = item.querySelector('a');
                if (link && !link.href.includes('bienestar-dashboard')) {
                    link.href = 'bienestar-dashboard';
                }
            }

            // Si es académico, se oculta explícitamente
            if (isAcademic && !text.includes('bienestar') && !text.includes('liderazgo')) permitido = false;

            // Ocultar el Dashboard Administrativo
            if (text === 'dashboard') permitido = false;
        }

        if (!permitido) {
            item.style.display = 'none';
        } else {
            item.style.display = 'block';
        }

        // Filtrar submenús de aprendices
        const submenuAprendices = item.querySelector('#submenu-aprendices');
        if (submenuAprendices) {
            const sublinks = submenuAprendices.querySelectorAll('li');
            sublinks.forEach(li => {
                const subtext = li.innerText.toLowerCase();
                if (esRespLiderazgo) {
                    // Resp Liderazgo ve todo el submenú de Aprendices
                    li.style.display = 'block';
                } else if (!esDirector) {
                    // Jefe de Bienestar (y otros) ve Lista y Población
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
    const user = authSystem.getCurrentUser();
    if (!user) return;

    const canvas = document.getElementById('chartPoblacionDash');
    const statTotalEl = document.getElementById('dashStatTotal');
    const scopes = user.vocero_scopes || (user.vocero_scope ? [user.vocero_scope] : []);
    const scope = scopes[0] || null;

    let url = 'api/aprendices.php?limit=-1';
    if (user.rol === 'vocero' && scope) {
        if (scope.tipo === 'principal' || scope.tipo === 'suplente') {
            url += `&ficha=${scope.ficha}`;
        } else if (scope.tipo === 'enfoque') {
            url += `&tabla_poblacion=${scope.poblacion}`;
        }
    }

    fetch(url)
        .then(r => r.json())
        .then(d => {
            if (!d.success) {
                console.error('Error in API response:', d.message);
                return;
            }

            const c = { mujer: 0, indigena: 0, narp: 0, campesino: 0, lgbtiq: 0, discapacidad: 0 };
            const estadosInactivos = ['RETIRO', 'CANCELADO', 'RETIRADO', 'FINALIZADO', 'TRASLADO', 'APLAZADO', 'CANCELADA', 'FINALIZADA'];

            // Si es vocero, solo contar los que NO están en estados inactivos
            const dataPura = d.data.filter(a => {
                const estado = (a.estado || '').toUpperCase().trim();
                return !estadosInactivos.includes(estado) && estado !== '';
            });

            dataPura.forEach(a => {
                if (a.mujer == 1) c.mujer++;
                if (a.indigena == 1) c.indigena++;
                if (a.narp == 1) c.narp++;
                if (a.campesino == 1) c.campesino++;
                if (a.lgbtiq == 1) c.lgbtiq++;
                if (a.discapacidad == 1) c.discapacidad++;
            });

            const totalConteo = dataPura.length;

            const dMujer = document.getElementById('dashStatMujer');
            const dIndi = document.getElementById('dashStatIndigena');
            const dNarp = document.getElementById('dashStatNarp');
            const dLgbt = document.getElementById('dashStatLgbtiq');
            const dTot = document.getElementById('dashStatTotal');

            if (dMujer) dMujer.textContent = c.mujer;
            if (dIndi) dIndi.textContent = c.indigena;
            if (dNarp) dNarp.textContent = c.narp;
            if (dLgbt) dLgbt.textContent = c.lgbtiq;
            if (dTot) dTot.textContent = totalConteo;

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
            updateText('stat-total-poblacion', totalConteo);

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

// Inicializar restricciones inmediatamente para evitar parpadeo si el DOM ya tiene algo útil
try {
    aplicarRestriccionesDeRol();
} catch (e) {
    // Es normal que falle si el DOM no está listo, se reintentará en DOMContentLoaded
}
document.addEventListener('DOMContentLoaded', aplicarRestriccionesDeRol);

