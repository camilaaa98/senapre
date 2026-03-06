# AsistNet - Documentación del Backend PHP

## Estructura del Proyecto

\`\`\`
api/
├── config/
│   └── Database.php          # Configuración de conexión SQLite
├── controllers/
│   ├── AuthController.php    # Controlador de autenticación
│   ├── UsuariosController.php
│   ├── AprendicesController.php
│   ├── ProgramasController.php
│   ├── FichasController.php
│   └── AsistenciasController.php
├── routes/
│   ├── auth.php
│   ├── usuarios.php
│   ├── aprendices.php
│   ├── programas.php
│   ├── fichas.php
│   └── asistencias.php
└── index.php                 # Punto de entrada principal
database/
└── asistnet.db              # Base de datos SQLite (se crea automáticamente)
\`\`\`

## Instalación

1. **Requisitos:**
   - PHP 7.4 o superior
   - Extensión PDO SQLite habilitada
   - Servidor web (Apache/Nginx) o PHP integrado

2. **Configuración:**
   - No requiere configuración adicional
   - La base de datos se crea automáticamente
   - Los usuarios de prueba se insertan automáticamente

3. **Usuarios por defecto:**
   - Administrador: `admin@sena.edu.co` / `admin123`
   - Instructor: `instructor@sena.edu.co` / `instructor123`

## Endpoints del API

### Autenticación

**POST** `/api/auth`
\`\`\`json
{
  "correo": "admin@sena.edu.co",
  "password": "admin123"
}
\`\`\`

### Usuarios

- **GET** `/api/usuarios` - Listar todos los usuarios
- **GET** `/api/usuarios/{id}` - Obtener usuario por ID
- **POST** `/api/usuarios` - Crear nuevo usuario
- **PUT** `/api/usuarios/{id}` - Actualizar usuario
- **DELETE** `/api/usuarios/{id}` - Eliminar usuario (soft delete)

### Aprendices

- **GET** `/api/aprendices` - Listar todos los aprendices
- **GET** `/api/aprendices/{id}` - Obtener aprendiz por ID
- **GET** `/api/aprendices?ficha={id}` - Listar aprendices por ficha
- **POST** `/api/aprendices` - Crear nuevo aprendiz
- **PUT** `/api/aprendices/{id}` - Actualizar aprendiz
- **DELETE** `/api/aprendices/{id}` - Eliminar aprendiz (soft delete)

### Programas

- **GET** `/api/programas` - Listar todos los programas
- **GET** `/api/programas/{id}` - Obtener programa por ID
- **POST** `/api/programas` - Crear nuevo programa
- **PUT** `/api/programas/{id}` - Actualizar programa
- **DELETE** `/api/programas/{id}` - Eliminar programa

### Fichas

- **GET** `/api/fichas` - Listar todas las fichas
- **GET** `/api/fichas/{id}` - Obtener ficha por ID
- **POST** `/api/fichas` - Crear nueva ficha
- **PUT** `/api/fichas/{id}` - Actualizar ficha
- **DELETE** `/api/fichas/{id}` - Eliminar ficha (soft delete)

### Asistencias

- **GET** `/api/asistencias` - Listar todas las asistencias
- **GET** `/api/asistencias?fecha={YYYY-MM-DD}` - Filtrar por fecha
- **GET** `/api/asistencias?id_ficha={id}` - Filtrar por ficha
- **GET** `/api/asistencias?reporte=1&fecha_inicio={date}&fecha_fin={date}&id_ficha={id}` - Generar reporte
- **POST** `/api/asistencias` - Registrar asistencia
- **PUT** `/api/asistencias/{id}` - Actualizar asistencia

## Ejemplos de Uso

### 1. Login
\`\`\`javascript
fetch('http://localhost/api/auth', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    correo: 'admin@sena.edu.co',
    password: 'admin123'
  })
})
.then(res => res.json())
.then(data => console.log(data));
\`\`\`

### 2. Crear Aprendiz
\`\`\`javascript
fetch('http://localhost/api/aprendices', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    documento: '1234567890',
    nombre: 'Juan',
    apellido: 'Pérez',
    correo: 'juan@ejemplo.com',
    id_ficha: 1,
    estado: 1
  })
})
.then(res => res.json())
.then(data => console.log(data));
\`\`\`

### 3. Registrar Asistencia
\`\`\`javascript
fetch('http://localhost/api/asistencias', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    id_aprendiz: 1,
    id_usuario: 1,
    fecha: '2025-01-15',
    hora_entrada: '07:30:00',
    hora_salida: '17:00:00',
    tipo: 'completa',
    observaciones: null
  })
})
.then(res => res.json())
.then(data => console.log(data));
\`\`\`

## Mejoras Sugeridas a la Base de Datos

Su modelo de base de datos es sólido. Aquí algunas sugerencias opcionales:

1. **Tabla de asistencias:** El CHECK para 'tipo' tiene sintaxis incorrecta en el SQL proporcionado. Debería ser:
   \`\`\`sql
   CHECK(tipo IN ('entrada', 'salida', 'completa'))
   \`\`\`

2. **Índices adicionales:** Para mejorar el rendimiento:
   \`\`\`sql
   CREATE INDEX idx_asistencias_fecha ON asistencias(fecha);
   CREATE INDEX idx_asistencias_aprendiz ON asistencias(id_aprendiz);
   CREATE INDEX idx_aprendices_ficha ON aprendices(id_ficha);
   \`\`\`

3. **Restricción única compuesta:** Para evitar registros duplicados de asistencia:
   \`\`\`sql
   CREATE UNIQUE INDEX idx_asistencia_unica 
   ON asistencias(id_aprendiz, fecha, tipo);
   \`\`\`

4. **Campo adicional en usuarios:** Podría agregar `ultimo_acceso` para tracking:
   \`\`\`sql
   ultimo_acceso TEXT
   \`\`\`

## Notas Importantes

- La base de datos se crea automáticamente al hacer la primera petición
- Todos los endpoints soportan CORS para desarrollo
- Los passwords se almacenan con hash usando `password_hash()`
- Las eliminaciones son soft delete (estado = 0) excepto en programas
- Los logs registran todas las acciones importantes del sistema

## Servidor de Desarrollo

Para probar el backend localmente:

\`\`\`bash
php -S localhost:8000
\`\`\`

Luego acceder a: `http://localhost:8000/api/`
