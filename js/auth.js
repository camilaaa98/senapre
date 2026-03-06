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
                    instructor_data: result.data.instructor_data
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
        if (rol === 'admin' || rol === 'administrador') {
            window.location.href = 'admin-dashboard.html';
        } else if (rol === 'instructor') {
            window.location.href = 'instructor-dashboard.html';
        }
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
