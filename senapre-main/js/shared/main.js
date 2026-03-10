// Script principal de AsistNet
document.addEventListener('DOMContentLoaded', () => {
  console.log('[v0] Inicializando AsistNet');

  const loadingScreen = document.getElementById('loading-screen');
  const loginScreen = document.getElementById('login-screen');
  const loginForm = document.getElementById('login-form');
  const emailInput = document.getElementById('email');
  const passwordInput = document.getElementById('password');

  if (authSystem.isAuthenticated()) {
    console.log('[v0] Usuario ya autenticado, redirigiendo');
    authSystem.redirectToDashboard();
    return;
  }

  setTimeout(() => {
    console.log('[v0] Ocultando pantalla de carga');
    loadingScreen.classList.add('hidden');

    setTimeout(() => {
      loadingScreen.style.display = 'none';
      loginScreen.style.display = 'flex';
      // Forzar un reflow antes de añadir la clase visible
      loginScreen.offsetHeight;
      loginScreen.classList.add('visible');
      console.log('[v0] Mostrando pantalla de login');
    }, 300);
  }, 500);

  // Validación en tiempo real del email
  emailInput.addEventListener('input', (e) => {
    const value = e.target.value;
    if (value) {
      const isValid = authSystem.validateEmail(value);
      authSystem.showValidation(emailInput, isValid);
    } else {
      authSystem.showValidation(emailInput, null);
    }
  });

  // Validación en tiempo real de la contraseña
  passwordInput.addEventListener('input', (e) => {
    const value = e.target.value;
    if (value) {
      const isValid = authSystem.validatePassword(value);
      authSystem.showValidation(passwordInput, isValid);
    } else {
      authSystem.showValidation(passwordInput, null);
    }
  });

  // Manejo del formulario de login
  loginForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    authSystem.hideError();

    const email = emailInput.value.trim();
    const password = passwordInput.value.trim();

    // Validar campos
    const emailValid = authSystem.validateEmail(email);
    const passwordValid = authSystem.validatePassword(password);

    authSystem.showValidation(emailInput, emailValid);
    authSystem.showValidation(passwordInput, passwordValid);

    if (!emailValid || !passwordValid) {
      authSystem.showError('Por favor, ingrese credenciales válidas');
      return;
    }

    loginScreen.classList.remove('visible');
    setTimeout(() => {
      loginScreen.style.display = 'none';
      loadingScreen.style.display = 'flex';
      loadingScreen.classList.remove('hidden');
    }, 300);

    try {
      const role = await authSystem.login(email, password);
      console.log('[v0] Login exitoso, rol:', role);
      authSystem.redirectToDashboard();
    } catch (error) {
      console.error('[v0] Error en login:', error);
      loadingScreen.classList.add('hidden');
      setTimeout(() => {
        loadingScreen.style.display = 'none';
        loginScreen.style.display = 'flex';
        loginScreen.offsetHeight;
        loginScreen.classList.add('visible');
        authSystem.showError(error.message || 'Credenciales inválidas. Verifique correo y contraseña.');
      }, 500);
    }
  });
});
