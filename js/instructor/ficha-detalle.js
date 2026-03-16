/**
 * ficha-detalle.js - Lógica para vista detallada de ficha
 * SOLID: Single Responsibility, Error Handling
 */

let fichaActual = {};
let aprendicesOriginales = [];

document.addEventListener('DOMContentLoaded', async () => {
    // Obtener número de ficha de la URL
    const urlParams = new URLSearchParams(window.location.search);
    const numeroFicha = urlParams.get('ficha');
    
    if (!numeroFicha) {
        mostrarError('No se especificó el número de ficha');
        return;
    }
    
    await cargarDetalleFicha(numeroFicha);
});

async function cargarDetalleFicha(numeroFicha) {
    try {
        // Cargar información de la ficha
        const responseFicha = await fetch(`api/instructor-fichas.php?id_usuario=${authSystem.getCurrentUser().id_usuario}`);
        const dataFicha = await responseFicha.json();
        
        if (!dataFicha.success) {
            throw new Error(dataFicha.message);
        }
        
        // Encontrar la ficha específica
        const ficha = dataFicha.data.find(f => f.numero_ficha === numeroFicha);
        if (!ficha) {
            throw new Error('Ficha no encontrada o no asignada a este instructor');
        }
        
        fichaActual = ficha;
        mostrarInfoFicha(ficha);
        
        // Cargar aprendices de esta ficha
        await cargarAprendices(numeroFicha);
        
    } catch (error) {
        console.error('Error cargando detalle de ficha:', error);
        mostrarError(error.message);
    }
}

async function cargarAprendices(numeroFicha) {
    try {
        const response = await fetch(`api/aprendices.php?ficha=${numeroFicha}&limit=-1`);
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.message || 'Error cargando aprendices');
        }
        
        aprendicesOriginales = data.data;
        mostrarAprendices(data.data);
        
    } catch (error) {
        console.error('Error cargando aprendices:', error);
        mostrarError('Error cargando aprendices: ' + error.message);
    }
}

function mostrarInfoFicha(ficha) {
    document.getElementById('fichaNumero').textContent = ficha.numero_ficha;
    document.getElementById('fichaPrograma').textContent = ficha.nombre_programa || 'No especificado';
    document.getElementById('fichaJornada').innerHTML = `<span class="badge badge-success">${ficha.jornada || 'N/A'}</span>`;
    document.getElementById('fichaAprendices').textContent = ficha.total_aprendices || 0;
}

function mostrarAprendices(aprendices) {
    const tableBody = document.getElementById('aprendicesTableBody');
    
    if (aprendices.length === 0) {
        tableBody.innerHTML = '<tr><td colspan="8" class="empty-state-row">No hay aprendices en esta ficha.</td></tr>';
        return;
    }
    
    tableBody.innerHTML = '';
    aprendices.forEach((aprendiz, index) => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${index + 1}</td>
            <td>${aprendiz.documento}</td>
            <td>${aprendiz.nombre}</td>
            <td>${aprendiz.apellido}</td>
            <td>${aprendiz.correo || 'N/A'}</td>
            <td>${aprendiz.celular || 'N/A'}</td>
            <td>
                <span class="badge ${getEstadoClass(aprendiz.estado)}">
                    ${aprendiz.estado || 'N/A'}
                </span>
            </td>
            <td>
                <button class="btn-sm btn-primary" onclick="verAprendiz('${aprendiz.documento}')">
                    <i class="fas fa-eye"></i> Ver
                </button>
            </td>
        `;
        tableBody.appendChild(row);
    });
}

function getEstadoClass(estado) {
    switch (estado) {
        case 'LECTIVA': return 'badge-success';
        case 'EN FORMACION': return 'badge-info';
        case 'RETIRADO': return 'badge-danger';
        case 'CANCELADO': return 'badge-warning';
        default: return 'badge-secondary';
    }
}

function filtrarAprendices() {
    const termino = document.getElementById('buscarAprendiz').value.toLowerCase();
    const aprendicesFiltrados = aprendicesOriginales.filter(aprendiz => 
        aprendiz.documento.toLowerCase().includes(termino) ||
        aprendiz.nombre.toLowerCase().includes(termino) ||
        aprendiz.apellido.toLowerCase().includes(termino) ||
        (aprendiz.correo && aprendiz.correo.toLowerCase().includes(termino))
    );
    
    mostrarAprendices(aprendicesFiltrados);
}

function verAprendiz(documento) {
    // TODO: Implementar modal con detalles del aprendiz
    alert(`Funcionalidad para ver detalles del aprendiz ${documento} en desarrollo.`);
}

function irAAsistencia() {
    window.location.href = `instructor-asistencia.html?ficha=${fichaActual.numero_ficha}`;
}

function irAReportes() {
    window.location.href = `instructor-reportes.html?ficha=${fichaActual.numero_ficha}`;
}

function verAprendices() {
    // Scroll a la tabla de aprendices
    document.querySelector('.table-container-scroll').scrollIntoView({ behavior: 'smooth' });
}

function exportarAprendices() {
    if (aprendicesOriginales.length === 0) {
        alert('No hay aprendices para exportar');
        return;
    }
    
    // Crear CSV
    let csv = 'Documento,Nombres,Apellidos,Correo,Celular,Estado\n';
    aprendicesOriginales.forEach(aprendiz => {
        csv += `${aprendiz.documento},"${aprendiz.nombre}","${aprendiz.apellido}","${aprendiz.correo || ''}","${aprendiz.celular || ''}","${aprendiz.estado || ''}"\n`;
    });
    
    // Descargar archivo
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', `aprendices_ficha_${fichaActual.numero_ficha}.csv`);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

function mostrarError(mensaje) {
    const tableBody = document.getElementById('aprendicesTableBody');
    tableBody.innerHTML = `
        <tr>
            <td colspan="8" class="text-center color-error py-4">
                <i class="fas fa-exclamation-triangle"></i> ${mensaje}
            </td>
        </tr>
    `;
}
