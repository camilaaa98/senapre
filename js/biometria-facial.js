/**
 * Módulo de Biometría Facial
 * Gestión de captura y verificación de rostros usando la cámara web
 */

let stream = null;
let usuarioActualBiometria = null;

async function registrarBiometria(idUsuario) {
    usuarioActualBiometria = idUsuario;
    document.getElementById('modalBiometria').style.display = 'flex';
    await iniciarCamara();
}

async function iniciarCamara() {
    try {
        const video = document.getElementById('videoBiometria');
        const estadoCaptura = document.getElementById('estadoCaptura');

        estadoCaptura.textContent = 'Solicitando acceso a la cámara...';
        estadoCaptura.style.background = '#fef3c7';

        stream = await navigator.mediaDevices.getUserMedia({
            video: {
                width: { ideal: 640 },
                height: { ideal: 480 },
                facingMode: 'user'
            }
        });

        video.srcObject = stream;

        estadoCaptura.textContent = '✓ Cámara activa - Posicione su rostro';
        estadoCaptura.style.background = '#d1fae5';
        estadoCaptura.style.color = '#065f46';

    } catch (error) {
        console.error('Error accediendo a la cámara:', error);
        document.getElementById('estadoCaptura').textContent = '✗ Error: No se pudo acceder a la cámara';
        document.getElementById('estadoCaptura').style.background = '#fee2e2';
        document.getElementById('estadoCaptura').style.color = '#991b1b';
        alert('No se pudo acceder a la cámara. Verifique los permisos del navegador.');
    }
}

async function capturarRostro() {
    const video = document.getElementById('videoBiometria');
    const canvas = document.getElementById('canvasBiometria');
    const ctx = canvas.getContext('2d');
    const estadoCaptura = document.getElementById('estadoCaptura');

    // Capturar frame actual
    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

    // Convertir a base64
    const imagenBase64 = canvas.toDataURL('image/jpeg', 0.8);

    estadoCaptura.textContent = 'Procesando rostro...';
    estadoCaptura.style.background = '#fef3c7';

    // Simular procesamiento (en producción aquí iría el análisis facial real)
    await new Promise(r => setTimeout(r, 1500));

    // Enviar al servidor
    try {
        const response = await fetch('api/biometria.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                accion: 'registrar_usuario',
                id_usuario: usuarioActualBiometria,
                datos_faciales: imagenBase64
            })
        });

        const result = await response.json();

        if (result.success) {
            estadoCaptura.textContent = '✓ Rostro registrado exitosamente';
            estadoCaptura.style.background = '#d1fae5';
            estadoCaptura.style.color = '#065f46';

            setTimeout(() => {
                cerrarModalBiometria();
                mostrarNotificacion('Biometría facial registrada correctamente', 'success');
                cargarUsuarios(); // Recargar tabla
            }, 1500);
        } else {
            throw new Error(result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        estadoCaptura.textContent = '✗ Error al guardar biometría';
        estadoCaptura.style.background = '#fee2e2';
        estadoCaptura.style.color = '#991b1b';
        alert('Error al registrar biometría: ' + error.message);
    }
}

function cerrarModalBiometria() {
    // Detener cámara
    if (stream) {
        stream.getTracks().forEach(track => track.stop());
        stream = null;
    }

    document.getElementById('modalBiometria').style.display = 'none';
    document.getElementById('estadoCaptura').textContent = 'Preparando cámara...';
    document.getElementById('estadoCaptura').style.background = '#f3f4f6';
    document.getElementById('estadoCaptura').style.color = '#000';
    usuarioActualBiometria = null;
}

// Eliminar registro biométrico
async function eliminarBiometria(idUsuario) {
    if (!confirm('¿Está seguro de eliminar el registro biométrico de este usuario?\n\nEsta acción no se puede deshacer.')) {
        return;
    }

    try {
        const response = await fetch('api/biometria.php', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                id_usuario: idUsuario
            })
        });

        const result = await response.json();

        if (result.success) {
            mostrarNotificacion('Registro biométrico eliminado', 'success');
            cargarUsuarios();
        } else {
            throw new Error(result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error al eliminar biometría: ' + error.message);
    }
}
