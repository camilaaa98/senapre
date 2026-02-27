const fs = require('fs');
const path = require('path');

// Archivos a actualizar
const files = [
    'login.html',
    'admin-aprendices.html',
    'admin-asistencias.html',
    'admin-dashboard.html',
    'admin-fichas.html',
    'admin-programas.html',
    'admin-reportes.html',
    'admin-usuarios.html'
];

const baseDir = __dirname;

files.forEach(file => {
    const filePath = path.join(baseDir, file);

    if (!fs.existsSync(filePath)) {
        console.log(`⚠ Archivo no encontrado: ${file}`);
        return;
    }

    let content = fs.readFileSync(filePath, 'utf8');
    let modified = false;

    // Reemplazar en login.html
    if (file === 'login.html') {
        const oldPattern = /if \(data\.data\.rol === 'admin'\)/g;
        const newPattern = "if (data.data.rol === 'admin' || data.data.rol === 'administrador')";
        if (content.match(oldPattern)) {
            content = content.replace(oldPattern, newPattern);
            modified = true;
        }
    }

    // Reemplazar en archivos admin-*
    if (file.startsWith('admin-')) {
        const oldPattern = /authSystem\.getCurrentUser\(\)\.rol !== 'admin'/g;
        const newPattern = "(user.rol !== 'admin' && user.rol !== 'administrador')";

        // Primero agregar la variable user si no existe
        if (content.includes("authSystem.getCurrentUser().rol !== 'admin'")) {
            content = content.replace(
                /(\s+)if \(!authSystem\.isAuthenticated\(\) \|\| authSystem\.getCurrentUser\(\)\.rol !== 'admin'\)/g,
                "$1const user = authSystem.getCurrentUser();\n$1if (!authSystem.isAuthenticated() || (user.rol !== 'admin' && user.rol !== 'administrador'))"
            );
            modified = true;
        }
    }

    if (modified) {
        fs.writeFileSync(filePath, content, 'utf8');
        console.log(`✓ Actualizado: ${file}`);
    } else {
        console.log(`- Sin cambios: ${file}`);
    }
});

console.log('\n✓ Proceso completado');
