# Manual Técnico - Sistema SenApre
## Documentación Técnica para Desarrolladores y Administradores

**Versión**: 1.0  
**Fecha**: Marzo 2026  
**Idioma**: Español  
**Audiencia**: Desarrolladores, DevOps, Administradores de Sistema

---

## 📋 Índice

1. [Arquitectura del Sistema](#arquitectura-del-sistema)
2. [Stack Tecnológico](#stack-tecnológico)
3. [Estructura de Directorios](#estructura-de-directorios)
4. [Base de Datos](#base-de-datos)
5. [API Reference](#api-reference)
6. [Sistema Biométrico](#sistema-biométrico)
7. [Configuración de Deploy](#configuración-de-deploy)
8. [Variables de Entorno](#variables-de-entorno)
9. [Seguridad](#seguridad)
10. [Mantenimiento](#mantenimiento)
11. [Troubleshooting Avanzado](#troubleshooting-avanzado)

---

## 🏗️ Arquitectura del Sistema

```
┌─────────────────────────────────────────────────────────────┐
│                        CLIENTE                              │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────────────┐  │
│  │  Navegador  │  │  Cámara     │  │  JavaScript         │  │
│  │  (HTML/CSS) │  │  (WebRTC)   │  │  (Human.js/API)     │  │
│  └──────┬──────┘  └──────┬──────┘  └──────────┬──────────┘  │
└─────────┼────────────────┼────────────────────┼──────────────┘
          │                │                    │
          └────────────────┴────────────────────┘
                             │ HTTPS
                             ▼
┌─────────────────────────────────────────────────────────────┐
│                    RENDER / SERVIDOR                        │
│  ┌──────────────────────────────────────────────────────┐   │
│  │              PHP Application                          │   │
│  │  ┌─────────────┐  ┌─────────────┐  ┌──────────────┐  │   │
│  │  │   API       │  │   Auth      │  │  Biometría   │  │   │
│  │  │   REST      │  │   JWT       │  │  Controller  │  │   │
│  │  └─────────────┘  └─────────────┘  └──────────────┘  │   │
│  └──────────────────────────────────────────────────────┘   │
└──────────────────────────┬──────────────────────────────────┘
                           │
                           │ PostgreSQL
                           ▼
┌─────────────────────────────────────────────────────────────┐
│                    BASE DE DATOS                              │
│                     SUPABASE / RENDER                         │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────────────┐  │
│  │  Aprendices │  │   Fichas    │  │  Biometría (BLOB)   │  │
│  │  Usuarios   │  │   Lideres   │  │  Asistencias        │  │
│  └─────────────┘  └─────────────┘  └─────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
```

### Componentes Principales

| Capa | Tecnología | Responsabilidad |
|------|-----------|-----------------|
| **Frontend** | HTML5, CSS3, Vanilla JS | UI/UX, Cámara, Canvas |
| **Biometría** | Human.js | Detección facial, embeddings |
| **Backend** | PHP 8.x | API REST, Auth, Lógica |
| **Base de Datos** | PostgreSQL | Persistencia, consultas |
| **Storage** | Supabase Storage | Archivos, fotos |

---

## 💻 Stack Tecnológico

### Frontend
- **HTML5**: Estructura semántica, WebRTC
- **CSS3**: Grid, Flexbox, Variables CSS, Animaciones
- **JavaScript ES6+**: Async/await, Fetch API, Canvas API
- **Human.js**: Reconocimiento facial, extracción de embeddings
- **Font Awesome**: Iconografía

### Backend
- **PHP 8.x**: Lenguaje principal
- **PDO**: Abstracción de base de datos
- **JWT**: Autenticación stateless
- **Composer**: Gestión de dependencias

### Base de Datos
- **PostgreSQL 14+**: Base de datos principal
- **SQLite**: Fallback para desarrollo local
- **Redis**: Cache (opcional)

### DevOps / Deploy
- **Git**: Control de versiones
- **Render**: Hosting principal (Web Service + PostgreSQL)
- **Vercel**: Alternativa para frontend
- **Supabase**: Base de datos alternativa
- **GitHub Actions**: CI/CD (opcional)

---

## 📁 Estructura de Directorios

```
senapre/
├── api/                          # Backend API
│   ├── config/
│   │   └── Database.php          # Configuración DB
│   ├── aprendices.php            # CRUD aprendices
│   ├── asistencias.php           # Gestión asistencias
│   ├── auth.php                  # Autenticación JWT
│   ├── biometria.php             # API biométrica
│   ├── fichas.php                # CRUD fichas
│   ├── importar-excel.php        # Importación masiva
│   ├── liderazgo.php             # API liderazgo
│   ├── notificaciones.php        # Sistema de notificaciones
│   └── setup-*.php               # Scripts de setup
│
├── css/                          # Estilos
│   ├── main.css                  # Estilos principales
│   ├── dashboard.css             # Panel admin
│   ├── instructor-*.css          # Paneles instructor
│   └── liderazgo-*.css           # Paneles liderazgo
│
├── js/                           # JavaScript
│   ├── auth.js                   # Autenticación
│   ├── main.js                   # Utilidades
│   ├── human.min.js              # Librería biometría
│   ├── instructor/
│   │   ├── dashboard.js
│   │   └── asistencia.js         # Asistencia instructor
│   └── liderazgo/
│       ├── biometria-lider.js    # Biometría liderazgo
│       └── registro-facial.js    # Registro facial
│
├── docs/                         # Documentación
│   ├── manual-usuario-es.md      # Manual usuario ES
│   ├── manual-usuario-en.md      # Manual usuario EN
│   ├── manual-tecnico-es.md      # Este documento
│   └── manual-tecnico-en.md      # Manual técnico EN
│
├── fichas/                       # Archivos Excel importación
│   └── *.xls                     # Reportes SENA
│
├── *.html                        # Vistas frontend
│   ├── index.html                # Login principal
│   ├── admin-dashboard.html      # Panel director
│   ├── instructor-*.html         # Paneles instructor
│   ├── liderazgo.html            # Panel liderazgo
│   └── asistencia-liderazgo.html # Asistencia biométrica
│
├── composer.json                 # Dependencias PHP
├── .env.example                  # Variables de entorno ejemplo
└── README.md                     # README principal
```

---

## 🗄️ Base de Datos

### Esquema Principal

#### Tabla: `aprendices`
```sql
CREATE TABLE aprendices (
    id SERIAL PRIMARY KEY,
    numero_documento VARCHAR(20) UNIQUE NOT NULL,
    nombres VARCHAR(100),
    apellidos VARCHAR(100),
    tipo_documento VARCHAR(10) DEFAULT 'CC',
    correo_electronico VARCHAR(100),
    celular VARCHAR(20),
    estado VARCHAR(20) DEFAULT 'LECTIVA',
    numero_ficha VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (numero_ficha) REFERENCES fichas(numero_ficha)
);
```

**Estados válidos**: `LECTIVA`, `PRODUCTIVA`, `CANCELADO`, `RETIRADO`, `FINALIZADO`

#### Tabla: `fichas`
```sql
CREATE TABLE fichas (
    numero_ficha VARCHAR(20) PRIMARY KEY,
    nombre_programa VARCHAR(100),
    nivel_formacion VARCHAR(50),
    estado VARCHAR(20) DEFAULT 'LECTIVA',
    fecha_inicio DATE,
    fecha_fin DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

#### Tabla: `biometria_aprendices` / `biometria_lideres`
```sql
CREATE TABLE biometria_aprendices (
    id SERIAL PRIMARY KEY,
    aprendiz_id INTEGER,
    embedding BYTEA,                    -- Vector facial (256 dims)
    confianza DECIMAL(3,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (aprendiz_id) REFERENCES aprendices(id)
);
```

#### Tabla: `asistencias`
```sql
CREATE TABLE asistencias (
    id SERIAL PRIMARY KEY,
    aprendiz_id INTEGER,
    fecha DATE,
    hora_entrada TIME,
    metodo VARCHAR(20),                 -- BIOMETRIA, MANUAL, QR
    confianza DECIMAL(5,2),             -- % confianza biométrica
    estado VARCHAR(20),                 -- PRESENTE, AUSENTE, EXCUSA
    observaciones TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(aprendiz_id, fecha)
);
```

### Diagrama ER (Simplificado)

```
┌─────────────┐       ┌─────────────┐       ┌──────────────┐
│   fichas    │       │  aprendices │       │   usuarios   │
├─────────────┤       ├─────────────┤       ├──────────────┤
│numero_ficha │◄──────│numero_ficha │       │     id       │
│   nombre    │       │  documento  │       │   documento  │
│   estado    │       │  nombres    │       │  password    │
└─────────────┘       │  apellidos  │       │    rol       │
                      │   email     │       │   areas      │
                      │   celular   │       └──────────────┘
                      │   estado    │
                      └──────┬──────┘
                             │
              ┌──────────────┼──────────────┐
              │              │              │
              ▼              ▼              ▼
      ┌──────────────┐ ┌──────────────┐ ┌──────────────┐
      │  biometria_  │ │ asistencias  │ │   reunion_   │
      │  aprendices  │ │              │ │  asistencias │
      ├──────────────┤ ├──────────────┤ ├──────────────┤
      │  aprendiz_id │ │  aprendiz_id │ │  reunion_id  │
      │  embedding   │ │    fecha     │ │   lider_id   │
      │  confianza   │ │    hora      │ │   estado     │
      └──────────────┘ └──────────────┘ └──────────────┘
```

---

## 🔌 API Reference

### Autenticación

Todos los endpoints requieren autenticación JWT excepto `/api/auth.php` (login).

**Header requerido**:
```http
Authorization: Bearer <token_jwt>
```

### Endpoints Principales

#### POST `/api/auth.php`
Login de usuario.

**Request**:
```json
{
  "documento": "1234567890",
  "password": "password123"
}
```

**Response**:
```json
{
  "success": true,
  "token": "eyJhbGciOiJIUzI1NiIs...",
  "user": {
    "id": 1,
    "documento": "1234567890",
    "nombre": "Juan Pérez",
    "rol": "instructor"
  }
}
```

#### GET `/api/aprendices.php`
Listar aprendices con filtros.

**Query Params**:
- `ficha`: Filtrar por número de ficha
- `estado`: LECTIVA, PRODUCTIVA, CANCELADO, etc.
- `page`: Paginación (default: 1)
- `limit`: Items por página (default: 20)

**Response**:
```json
{
  "success": true,
  "data": [...],
  "pagination": {
    "page": 1,
    "limit": 20,
    "total": 150
  }
}
```

#### POST `/api/biometria.php`
Operaciones biométricas.

**Actions**:
- `registrar`: Crear embedding
- `identificar`: 1:1 verificación
- `identificar_grupo`: 1:N identificación
- `identificar_lideres`: Para panel liderazgo

**Request Identificar Grupo**:
```json
{
  "action": "identificar_grupo",
  "embedding": [0.123, -0.456, ...],  // Array 256 floats
  "ficha": "2995479",
  "umbral": 0.85
}
```

**Response**:
```json
{
  "success": true,
  "identificado": true,
  "aprendiz": {
    "id": 123,
    "documento": "1234567890",
    "nombre": "Juan Pérez",
    "similaridad": 0.92
  }
}
```

#### POST `/api/asistencias.php`
CRUD de asistencias.

**Crear Asistencia**:
```json
{
  "action": "crear",
  "aprendiz_id": 123,
  "fecha": "2026-03-18",
  "hora": "08:30:00",
  "metodo": "BIOMETRIA",
  "confianza": 0.92
}
```

### Códigos de Error

| Código | Descripción | Solución |
|--------|-------------|----------|
| `401` | Token inválido/expirado | Re-login, refrescar token |
| `403` | Sin permisos | Verificar rol de usuario |
| `404` | Recurso no encontrado | Verificar ID/párametros |
| `409` | Conflicto (duplicado) | Asistencia ya registrada |
| `500` | Error interno | Revisar logs servidor |

---

## 🔐 Sistema Biométrico

### Flujo de Datos

```
┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
│   CAPTURA       │────▶│   PROCESAMIENTO │────▶│   COMPARACIÓN   │
│                 │     │                 │     │                 │
│ 1. Video webcam │     │ 1. Detección    │     │ 1. Query DB     │
│ 2. Frame canvas │     │ 2. Alineación   │     │ 2. Coseno       │
│ 3. Base64 img   │     │ 3. Embedding    │     │ 3. Umbral 0.85  │
└─────────────────┘     └─────────────────┘     └─────────────────┘
                                                        │
                                                        ▼
                                               ┌─────────────────┐
                                               │    RESULTADO    │
                                               │                 │
                                               │ • Match > 0.85  │
                                               │ • Identificado  │
                                               │ • Registro      │
                                               └─────────────────┘
```

### Algoritmo de Similaridad

**Similitud Coseno**:
```php
function cosineSimilarity($a, $b) {
    $dot = 0.0;
    $normA = 0.0;
    $normB = 0.0;
    
    for ($i = 0; $i < count($a); $i++) {
        $dot += $a[$i] * $b[$i];
        $normA += $a[$i] * $a[$i];
        $normB += $b[$i] * $b[$i];
    }
    
    return $dot / (sqrt($normA) * sqrt($normB));
}
```

**Umbrales**:
- **≥ 0.85**: Identificación confiable (match)
- **0.70 - 0.85**: Revisión manual sugerida
- **< 0.70**: Sin coincidencia

### Formatos de Datos

**Embedding (256 dimensiones)**:
```json
[
  -0.023456789, 0.123456789, 0.987654321,
  ... 256 valores float [-1, 1]
]
```

**Almacenamiento PostgreSQL**:
```sql
-- Como BYTEA (binario compacto)
embedding BYTEA

-- Inserción (PHP)
$embeddingBytes = pack('f*', ...$embeddingArray);
$stmt->bindParam(':embedding', $embeddingBytes, PDO::PARAM_LOB);
```

---

## 🚀 Configuración de Deploy

### Opción 1: Render (Recomendado)

**Ventajas**:
- Todo en uno (Web + PostgreSQL)
- Deploy automático desde GitHub
- HTTPS automático
- $0 para proyectos pequeños

**Pasos**:

1. **Crear PostgreSQL**:
   ```
   Dashboard → New → PostgreSQL
   Nombre: senapre
   Plan: Free
   Region: Oregon
   ```

2. **Crear Web Service**:
   ```
   Dashboard → New → Web Service
   GitHub: conectar repositorio
   Runtime: PHP
   Build Command: composer install
   Start Command: heroku-php-apache2
   ```

3. **Configurar Variables**:
   ```
   DATABASE_URL: postgresql://...
   JWT_SECRET: [generar random]
   APP_ENV: production
   ```

4. **Ejecutar Setup**:
   ```bash
   curl https://tu-app.render.com/api/setup-biometria-lideres.php
   curl https://tu-app.render.com/api/setup-notificaciones.php
   ```

### Opción 2: Vercel + Supabase

**Para frontend estático + Supabase**:

```json
// vercel.json
{
  "version": 2,
  "builds": [
    {
      "src": "api/**/*.php",
      "use": "vercel-php@0.6.0"
    }
  ],
  "routes": [
    {
      "src": "/api/(.*)",
      "dest": "/api/$1"
    },
    {
      "src": "/(.*)",
      "dest": "/$1.html"
    }
  ]
}
```

### Opción 3: Railway (Alternativa Premium)

```yaml
# railway.yml
services:
  web:
    build: .
    ports:
      - 80:80
    env:
      DATABASE_URL: ${{Postgres.DATABASE_URL}}
  
  postgres:
    image: postgres:14
    env:
      POSTGRES_DB: senapre
```

---

## ⚙️ Variables de Entorno

### Requeridas

```bash
# Base de datos
DATABASE_URL=postgresql://user:pass@host:5432/dbname

# Seguridad
JWT_SECRET=tu-clave-secreta-minimo-32-caracteres
JWT_EXPIRATION=86400  # 24 horas en segundos

# Aplicación
APP_ENV=production  # development | production
APP_DEBUG=false     # true solo en dev

# Supabase (opcional, para storage)
SUPABASE_URL=https://xxx.supabase.co
SUPABASE_KEY=eyJhbGci...
SUPABASE_BUCKET=fotos-aprendices
```

### Opcionales

```bash
# Email
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=notificaciones@senapre.com
SMTP_PASS=password

# Cache
REDIS_URL=redis://localhost:6379
CACHE_TTL=3600

# Logging
LOG_LEVEL=info  # debug | info | warning | error
LOG_FILE=/var/log/senapre.log
```

---

## 🔒 Seguridad

### Checklist de Seguridad Pre-Deploy

- [ ] JWT_SECRET generado aleatoriamente (≥32 chars)
- [ ] APP_DEBUG = false en producción
- [ ] Headers de seguridad configurados
- [ ] CORS restringido a dominios válidos
- [ ] Rate limiting en API
- [ ] Sanitización de inputs (XSS/SQL injection)
- [ ] Passwords hasheados (bcrypt)
- [ ] HTTPS forzado
- [ ] Variables sensibles no en código
- [ ] Logs de auditoría activados

### Headers de Seguridad (PHP)

```php
// api/config/headers.php
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' cdn.jsdelivr.net fonts.googleapis.com; font-src fonts.gstatic.com; img-src 'self' data: blob:; connect-src 'self' *.supabase.co;");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Permissions-Policy: camera=(self), microphone=(self)");
```

### Rate Limiting

```php
// Implementar en api/auth.php o middleware
$ip = $_SERVER['REMOTE_ADDR'];
$key = "rate_limit:$ip";
$attempts = $redis->incr($key);

if ($attempts === 1) {
    $redis->expire($key, 60); // 1 minuto
}

if ($attempts > 10) { // Máx 10 intentos/min
    http_response_code(429);
    exit(json_encode(['error' => 'Too many requests']));
}
```

---

## 🔧 Mantenimiento

### Tareas Diarias

- [ ] Revisar logs de errores
- [ ] Verificar asistencias registradas
- [ ] Confirmar backups automáticos

### Tareas Semanales

- [ ] Análisis de rendimiento API
- [ ] Revisión de intentos de login fallidos
- [ ] Limpieza de tokens expirados

### Tareas Mensuales

- [ ] Optimización de índices DB
- [ ] Actualización de dependencias
- [ ] Revisión de accesos no autorizados
- [ ] Backup manual de base de datos

### Comandos de Mantenimiento

```bash
# Backup PostgreSQL
pg_dump $DATABASE_URL > backup_$(date +%Y%m%d).sql

# Optimizar DB (VACUUM)
psql $DATABASE_URL -c "VACUUM ANALYZE;"

# Verificar tamaño de tablas
psql $DATABASE_URL -c "\dt+"
```

---

## 🐛 Troubleshooting Avanzado

### Problema: High CPU Usage

**Síntomas**: Servidor lento, timeouts

**Diagnóstico**:
```bash
# Ver procesos PHP
ps aux | grep php

# Ver queries lentas
psql $DATABASE_URL -c "SELECT * FROM pg_stat_statements ORDER BY total_time DESC LIMIT 10;"
```

**Soluciones**:
1. Agregar índices a `aprendices(numero_documento)`
2. Implementar cache Redis
3. Optimizar queries N+1
4. Limitar resultados de identificación biométrica

### Problema: Cámara No Funciona en iOS

**Síntomas**: Safari no solicita permisos

**Solución**:
```javascript
// Forzar HTTPS y contexto seguro
if (location.protocol !== 'https:') {
    location.replace('https:' + location.href.substring(5));
}

// Safari requiere getUserMedia en user gesture
button.addEventListener('click', async () => {
    const stream = await navigator.mediaDevices.getUserMedia({video: true});
});
```

### Problema: Embeddings No Coinciden

**Síntomas**: Falsos negativos en identificación

**Debugging**:
```php
// Log de embeddings para análisis
error_log("Embedding recibido: " . json_encode($embedding));
error_log("Embedding DB: " . base64_encode($embeddingDB));
error_log("Similitud: $similarity");
```

**Ajustes**:
1. Verificar normalización de vectores
2. Ajustar umbral de similitud
3. Reentrenar con mejores fotos

---

## 📊 Monitoreo y Métricas

### Métricas Clave

| Métrica | Target | Alerta si |
|---------|--------|-----------|
| **Tiempo respuesta API** | < 200ms | > 500ms |
| **Tasa de identificación** | > 95% | < 90% |
| **Asistencias/día** | > 1000 | < 100 |
| **Error rate** | < 1% | > 5% |
| **Uptime** | > 99.9% | < 99% |

### Dashboard de Monitoreo

Implementar endpoint `/api/health.php`:

```php
<?php
// Health check endpoint
$checks = [
    'database' => checkDatabase(),
    'camera' => checkCameraEndpoint(),
    'storage' => checkStorage()
];

http_response_code(array_product($checks) ? 200 : 503);
echo json_encode(['status' => $checks, 'timestamp' => date('c')]);
?>
```

---

## 📝 Changelog Técnico

### v1.0.0 (2026-03-18)

- ✅ Implementación sistema biométrico facial
- ✅ API REST completa
- ✅ Panel multi-rol (instructor, liderazgo, admin)
- ✅ Importación masiva Excel
- ✅ Deploy en Render
- ✅ PostgreSQL + Supabase compatible

---

## 📞 Contacto Soporte Técnico

**Equipo Desarrollo SenApre**
- 📧 dev@senapre.com
- 🐛 GitHub Issues: github.com/tu-repo/senapre/issues

---

**© 2026 SenApre - Documentación Técnica. Uso interno SENA.**
