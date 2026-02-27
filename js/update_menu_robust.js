const fs = require('fs');
const path = require('path');

const directoryPath = __dirname;
const menuItem = `
                <li class="menu-item">
                    <a href="admin-asignaciones.html" class="menu-link">
                        <div class="menu-icon"><i class="fas fa-calendar-alt"></i></div>
                        <span>Asignar Instructores</span>
                    </a>
                </li>`;

// Regex para encontrar el bloque de Gestionar Fichas, permitiendo variaciones de espacios
const regex = /(<a href="admin-fichas\.html"[\s\S]*?<span>Gestionar Fichas<\/span>\s*<\/a>\s*<\/li>)/;

fs.readdir(directoryPath, (err, files) => {
    if (err) return console.log('Error: ' + err);

    files.forEach((file) => {
        if (file.startsWith('admin-') && file.endsWith('.html') && file !== 'admin-asignaciones.html') {
            const filePath = path.join(directoryPath, file);
            let content = fs.readFileSync(filePath, 'utf8');

            if (!content.includes('admin-asignaciones.html')) {
                if (regex.test(content)) {
                    const newContent = content.replace(regex, '$1' + menuItem);
                    fs.writeFileSync(filePath, newContent, 'utf8');
                    console.log(`Updated ${file}`);
                } else {
                    console.log(`Pattern not found in ${file}`);
                }
            }
        }
    });
});
