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
                    id_usuario: result.data.id_usuario,
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

    redirectToDashboard() {
        if (!this.currentUser) return;
        
        const rol = this.currentUser.rol.toLowerCase();
        const areas = this.currentUser.bienestar_data || [];

        // PRIORIDAD ALTA: Si tiene área de Liderazgo (Jancy), redirigir directo sin importar el rol
        const esLiderazgo = areas.includes('voceros_y_representantes') || areas.includes('liderazgo') || areas.includes('vocero');
        if (esLiderazgo) {
            if (!window.location.pathname.includes('liderazgo.html')) {
                window.location.href = 'liderazgo.html';
                return;
            }
        }

        // REGLA DE SEGURIDAD: Administrativos solo acceden a su panel si tienen área asignada
        if (rol === 'administrativo') {
            if (areas.includes('jefe_bienestar')) {
                window.location.href = 'admin-bienestar-dashboard.html';
                return;
            } else if (areas.length > 0) {
                // Tiene áreas asignadas pero no es jefe - redirigir según área
                if (areas.includes('voceros_y_representantes') || areas.includes('liderazgo')) {
                    window.location.href = 'liderazgo.html';
                    return;
                }
            }
            // Si no tiene áreas asignadas, mostrar acceso denegado
            this.showAccessDenied();
            return;
        }

        if (rol === 'vocero') {
            window.location.href = 'vocero-dashboard.html';
        } else if (areas.includes('jefe_bienestar')) {
            window.location.href = 'admin-bienestar-dashboard.html';
        } else if (rol === 'instructor') {
            window.location.href = 'instructor-dashboard.html';
        } else if (rol === 'admin' || rol === 'administrador' || rol === 'director') {
            window.location.href = 'admin-dashboard.html';
        } else {
            window.location.href = 'admin-dashboard.html';
        }
    }

    showAccessDenied() {
        document.body.innerHTML = `
            <div style="display: flex; justify-content: center; align-items: center; height: 100vh; font-family: system-ui;">
                <div style="text-align: center; padding: 40px; background: white; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); max-width: 400px;">
                    <i class="fas fa-exclamation-triangle" style="font-size: 48px; color: #f59e0b; margin-bottom: 20px;"></i>
                    <h2 style="color: #1f2937; margin-bottom: 15px;">Acceso Restringido</h2>
                    <p style="color: #6b7280; margin-bottom: 20px;">Su cuenta de administrativo no tiene áreas asignadas. Contacte al administrador del sistema para solicitar los permisos necesarios.</p>
                    <button onclick="authSystem.logout()" style="background: #ef4444; color: white; border: none; padding: 12px 24px; border-radius: 6px; cursor: pointer; font-size: 14px;">
                        Cerrar Sesión
                    </button>
                </div>
            </div>
        `;
    }
}

// Global instance
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
