document.addEventListener('DOMContentLoaded', function () {
    // Auth Check
    const userStr = localStorage.getItem('user');
    if (!userStr) {
        window.location.href = 'index.html';
        return;
    }
    const user = JSON.parse(userStr);
    if (user.rol !== 'instructor') {
        alert('Acceso no autorizado');
        window.location.href = 'index.html';
        return;
    }

    // UI Updates
    const userNameEl = document.getElementById('userName');
    if (userNameEl) {
        userNameEl.textContent = `${user.nombre} ${user.apellido}`;
    }

    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    const dateEl = document.getElementById('currentDate');
    if (dateEl) {
        dateEl.textContent = new Date().toLocaleDateString('es-ES', options);
    }

    // Logout
    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', () => {
            localStorage.removeItem('user');
            window.location.href = 'index.html';
        });
    }

    // Load Fichas
    loadFichas(user.id_usuario);
});

async function loadFichas(userId) {
    const tableBody = document.getElementById('fichasTableBody');

    try {
        const response = await fetch(`api/instructor-fichas.php?id_usuario=${userId}`);
        const data = await response.json();

        if (data.success) {
            if (data.data.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="5" style="text-align: center;">No tienes fichas asignadas.</td></tr>';
                return;
            }

            tableBody.innerHTML = '';
            data.data.forEach(ficha => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td><strong>${ficha.numero_ficha}</strong></td>
                    <td>${ficha.nombre_programa || 'No especificado'}</td>
                    <td><span class="badge badge-success">${ficha.jornada || 'N/A'}</span></td>
                    <td>${ficha.total_aprendices}</td>
                    <td>
                        <button class="btn-sm btn-primary" onclick="verDetalle('${ficha.numero_ficha}')">
                            <i class="fas fa-eye"></i> Ver
                        </button>
                    </td>
                `;
                tableBody.appendChild(row);
            });
        } else {
            tableBody.innerHTML = `<tr><td colspan="5" style="text-align: center; color: red;">Error: ${data.message}</td></tr>`;
        }
    } catch (error) {
        console.error('Error loading fichas:', error);
        tableBody.innerHTML = '<tr><td colspan="5" style="text-align: center; color: red;">Error al cargar las fichas.</td></tr>';
    }
}

function verDetalle(ficha) {
    alert(`Funcionalidad para ver detalle de ficha ${ficha} en desarrollo.`);
    // window.location.href = `instructor-ficha-detalle.html?ficha=${ficha}`;
}
