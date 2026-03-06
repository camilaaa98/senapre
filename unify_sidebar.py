import os
import re

base_dir = r"c:\wamp64\www\YanguasEjercicios\senapre"

target_files = [
    "admin-dashboard.html",
    "admin-aprendices.html",
    "admin-aprendices-crear.html",
    "admin-programas.html",
    "admin-fichas.html",
    "admin-usuarios.html",
    "admin-reportes.html",
    "admin-asignaciones.html",
    "admin-asistencias.html",
    "admin-bienestar-dashboard.html",
    "admin-bienestar-asignacion.html",
    "admin-poblacion-detalle.html",
    "jefe-bienestar-dashboard.html"
]

def get_sidebar_html(active_file):
    is_dash = "active" if active_file == "admin-dashboard.html" else ""
    is_bien = "active" if active_file in ["admin-bienestar-dashboard.html", "admin-bienestar-asignacion.html", "jefe-bienestar-dashboard.html"] else ""
    is_user = "active" if active_file == "admin-usuarios.html" else ""
    is_prog = "active" if active_file == "admin-programas.html" else ""
    is_fich = "active" if active_file == "admin-fichas.html" else ""
    is_asig = "active" if active_file == "admin-asignaciones.html" else ""
    is_asit = "active" if active_file == "admin-asistencias.html" else ""
    is_repo = "active" if active_file == "admin-reportes.html" else ""
    
    is_aprendices = active_file in ["admin-aprendices.html", "admin-poblacion-detalle.html", "admin-aprendices-crear.html"]
    active_apr = "active" if is_aprendices else ""
    open_apr = "open" if is_aprendices else ""
    show_apr = "show" if is_aprendices else ""
    
    sidebar = f"""        <aside class="sidebar">
            <div class="sidebar-header">
                <img src="assets/img/asi.png" alt="ASI Logo"
                    style="width: 100px; height: 100px; object-fit: cover; margin-bottom: 10px; border-radius: 50px;">
                <h3>SenApre</h3>
                <div class="sidebar-subtitle" id="user-role-display"
                    style="text-align: center; color: rgba(255,255,255,0.7); font-size: 0.8rem;">Director</div>
            </div>

            <nav class="sidebar-menu">
                <li class="menu-item">
                    <a href="admin-dashboard.html" class="menu-link {is_dash}">
                        <div class="menu-icon"><i class="fas fa-home"></i></div>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="admin-bienestar-dashboard.html" class="menu-link {is_bien}">
                        <div class="menu-icon"><i class="fas fa-handshake"></i></div>
                        <span>Bienestar del Aprendiz</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="admin-usuarios.html" class="menu-link {is_user}">
                        <div class="menu-icon"><i class="fas fa-users-cog"></i></div>
                        <span>Gestionar Usuarios</span>
                    </a>
                </li>
                <li class="menu-item has-submenu {open_apr}">
                    <a href="#" class="menu-link {active_apr}" onclick="toggleSubmenu(event, 'submenu-aprendices')">
                        <div class="menu-icon"><i class="fas fa-user-graduate"></i></div>
                        <span>Gestionar Aprendices</span>
                        <i class="fas fa-chevron-down submenu-arrow"></i>
                    </a>
                    <ul class="submenu {show_apr}" id="submenu-aprendices">
                        <li><a href="admin-aprendices.html"><i class="fas fa-list"></i> Lista de Aprendices</a></li>
                        <li><a href="admin-aprendices.html#poblacion"><i class="fas fa-users"></i> Tipo de Población</a></li>
                        <li><a href="admin-aprendices.html#excusas"><i class="fas fa-file-alt"></i> Excusas</a></li>
                        <li><a href="admin-aprendices.html#planes"><i class="fas fa-clipboard-list"></i> Planes de Mejoramiento</a></li>
                        <li><a href="admin-aprendices.html#inasistencias"><i class="fas fa-exclamation-triangle"></i> Inasistencias</a></li>
                        <li><a href="admin-aprendices.html#retardos"><i class="fas fa-clock"></i> Retardos</a></li>
                    </ul>
                </li>
                <li class="menu-item">
                    <a href="admin-programas.html" class="menu-link {is_prog}">
                        <div class="menu-icon"><i class="fas fa-book"></i></div>
                        <span>Gestionar Programas</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="admin-fichas.html" class="menu-link {is_fich}">
                        <div class="menu-icon"><i class="fas fa-chalkboard"></i></div>
                        <span>Gestionar Fichas</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="admin-asignaciones.html" class="menu-link {is_asig}">
                        <div class="menu-icon"><i class="fas fa-calendar-alt"></i></div>
                        <span>Asignar Instructores</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="admin-asistencias.html" class="menu-link {is_asit}">
                        <div class="menu-icon"><i class="fas fa-check-circle"></i></div>
                        <span>Consultar Asistencias</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="admin-reportes.html" class="menu-link {is_repo}">
                        <div class="menu-icon"><i class="fas fa-chart-bar"></i></div>
                        <span>Generar Reportes</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="#" onclick="authSystem.logout()" class="menu-link">
                        <div class="menu-icon"><i class="fas fa-sign-out-alt"></i></div>
                        <span>Cerrar Sesión</span>
                    </a>
                </li>
            </nav>
            <div class="sidebar-footer"
                style="margin-top: auto; padding: 20px; border-top: 1px solid rgba(255,255,255,0.1); text-align: center;">
                <img src="assets/img/logosena.png" alt="SENA Logo" style="height: 60px; width: auto; opacity: 0.9;">
            </div>
        </aside>"""
    return sidebar

for filename in target_files:
    path = os.path.join(base_dir, filename)
    if not os.path.exists(path): continue
    with open(path, 'r', encoding='utf-8') as f: content = f.read()
    new_sidebar = get_sidebar_html(filename)
    pattern = re.compile(r'<aside class="sidebar">.*?</aside>', re.DOTALL)
    if pattern.search(content):
        updated_content = pattern.sub(new_sidebar, content)
        with open(path, 'w', encoding='utf-8') as f: f.write(updated_content)
        print(f"Updated sidebar in {filename}")

# Limpieza de botón Responsable en todos los archivos posibles
for filename in target_files:
    path = os.path.join(base_dir, filename)
    if not os.path.exists(path): continue
    with open(path, 'r', encoding='utf-8') as f: content = f.read()
    btn_pattern = re.compile(r'<button onclick="window\.location\.href=\'admin-bienestar-dashboard\.html\'".*?>\s*<i class="fas fa-user-shield"></i> RESPONSABLE\s*</button>', re.DOTALL)
    if btn_pattern.search(content):
        updated_content = btn_pattern.sub('', content)
        with open(path, 'w', encoding='utf-8') as f: f.write(updated_content)
        print(f"Removed Responsable button from {filename}")
