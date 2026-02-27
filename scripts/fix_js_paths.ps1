# Script para corregir rutas de archivos JS en todos los HTML
# Actualiza js/auth.js a js/shared/auth.js
# Actualiza js/main.js a js/shared/main.js

$archivos = @(
    "admin-aprendices.html",
    "admin-asistencias.html",
    "admin-fichas.html",
    "admin-programas.html",
    "admin-usuarios.html",
    "index.html",
    "instructor-dashboard.html",
    "instructor-registrar.html",
    "instructor-reportes.html",
    "instructor-asistencias.html"
)

foreach ($archivo in $archivos) {
    $ruta = "c:\wamp64\www\YanguasEjercicios\mockups-asist-net\$archivo"
    
    if (Test-Path $ruta) {
        $contenido = Get-Content $ruta -Raw -Encoding UTF8
        
        # Reemplazar rutas
        $contenido = $contenido -replace 'src="js/auth\.js"', 'src="js/shared/auth.js"'
        $contenido = $contenido -replace 'src="js/main\.js"', 'src="js/shared/main.js"'
        
        # Guardar
        Set-Content $ruta -Value $contenido -Encoding UTF8 -NoNewline
        
        Write-Host "✓ Actualizado: $archivo"
    } else {
        Write-Host "✗ No encontrado: $archivo"
    }
}

Write-Host "`n¡Listo! Todas las rutas han sido corregidas."
