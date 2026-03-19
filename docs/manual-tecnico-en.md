# Technical Manual - SenApre System
## Technical Documentation for Developers and System Administrators

**Version**: 1.0  
**Date**: March 2026  
**Language**: English  
**Audience**: Developers, DevOps, System Administrators

---

## 📋 Table of Contents

1. [System Architecture](#system-architecture)
2. [Technology Stack](#technology-stack)
3. [Directory Structure](#directory-structure)
4. [Database Schema](#database-schema)
5. [API Reference](#api-reference)
6. [Biometric System](#biometric-system)
7. [Deployment Configuration](#deployment-configuration)
8. [Environment Variables](#environment-variables)
9. [Security](#security)
10. [Maintenance](#maintenance)
11. [Advanced Troubleshooting](#advanced-troubleshooting)

---

## 🏗️ System Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                        CLIENT                               │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────────────┐  │
│  │  Browser    │  │  Camera     │  │  JavaScript         │  │
│  │  (HTML/CSS) │  │  (WebRTC)   │  │  (Human.js/API)     │  │
│  └──────┬──────┘  └──────┬──────┘  └──────────┬──────────┘  │
└─────────┼────────────────┼────────────────────┼──────────────┘
          │                │                    │
          └────────────────┴────────────────────┘
                             │ HTTPS
                             ▼
┌─────────────────────────────────────────────────────────────┐
│                    RENDER / SERVER                          │
│  ┌──────────────────────────────────────────────────────┐   │
│  │              PHP Application                            │   │
│  │  ┌─────────────┐  ┌─────────────┐  ┌──────────────┐  │   │
│  │  │   API       │  │   Auth      │  │  Biometrics  │  │   │
│  │  │   REST      │  │   JWT       │  │  Controller  │  │   │
│  │  └─────────────┘  └─────────────┘  └──────────────┘  │   │
│  └──────────────────────────────────────────────────────┘   │
└──────────────────────────┬──────────────────────────────────┘
                           │
                           │ PostgreSQL
                           ▼
┌─────────────────────────────────────────────────────────────┐
│                    DATABASE                                   │
│                     SUPABASE / RENDER                         │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────────────┐  │
│  │  Students   │  │   Groups    │  │  Biometrics (BLOB)  │  │
│  │  Users      │  │   Leaders   │  │  Attendance         │  │
│  └─────────────┘  └─────────────┘  └─────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
```

### Main Components

| Layer | Technology | Responsibility |
|------|-----------|-----------------|
| **Frontend** | HTML5, CSS3, Vanilla JS | UI/UX, Camera, Canvas |
| **Biometrics** | Human.js | Face detection, embeddings |
| **Backend** | PHP 8.x | REST API, Auth, Logic |
| **Database** | PostgreSQL | Persistence, queries |
| **Storage** | Supabase Storage | Files, photos |

---

## 💻 Technology Stack

### Frontend
- **HTML5**: Semantic structure, WebRTC
- **CSS3**: Grid, Flexbox, CSS Variables, Animations
- **JavaScript ES6+**: Async/await, Fetch API, Canvas API
- **Human.js**: Facial recognition, embedding extraction
- **Font Awesome**: Icons

### Backend
- **PHP 8.x**: Main language
- **PDO**: Database abstraction
- **JWT**: Stateless authentication
- **Composer**: Dependency management

### Database
- **PostgreSQL 14+**: Primary database
- **SQLite**: Local development fallback
- **Redis**: Cache (optional)

### DevOps / Deploy
- **Git**: Version control
- **Render**: Primary hosting (Web Service + PostgreSQL)
- **Vercel**: Alternative for frontend
- **Supabase**: Alternative database
- **GitHub Actions**: CI/CD (optional)

---

## 📁 Directory Structure

```
senapre/
├── api/                          # Backend API
│   ├── config/
│   │   └── Database.php          # DB configuration
│   ├── aprendices.php            # Students CRUD
│   ├── asistencias.php           # Attendance management
│   ├── auth.php                  # JWT authentication
│   ├── biometria.php             # Biometric API
│   ├── fichas.php                # Groups CRUD
│   ├── importar-excel.php        # Mass import
│   ├── liderazgo.php             # Leadership API
│   ├── notificaciones.php        # Notifications system
│   └── setup-*.php               # Setup scripts
│
├── css/                          # Styles
│   ├── main.css                  # Main styles
│   ├── dashboard.css             # Admin panel
│   ├── instructor-*.css          # Instructor panels
│   └── liderazgo-*.css           # Leadership panels
│
├── js/                           # JavaScript
│   ├── auth.js                   # Authentication
│   ├── main.js                   # Utilities
│   ├── human.min.js              # Biometric library
│   ├── instructor/
│   │   ├── dashboard.js
│   │   └── asistencia.js         # Instructor attendance
│   └── liderazgo/
│       ├── biometria-lider.js    # Leadership biometrics
│       └── registro-facial.js    # Face registration
│
├── docs/                         # Documentation
│   ├── manual-usuario-es.md      # User manual ES
│   ├── manual-usuario-en.md      # User manual EN
│   ├── manual-tecnico-es.md      # This document
│   └── manual-tecnico-en.md      # Technical manual EN
│
├── fichas/                       # Excel import files
│   └── *.xls                     # SENA reports
│
├── *.html                        # Frontend views
│   ├── index.html                # Main login
│   ├── admin-dashboard.html      # Director panel
│   ├── instructor-*.html         # Instructor panels
│   ├── liderazgo.html            # Leadership panel
│   └── asistencia-liderazgo.html # Biometric attendance
│
├── composer.json                 # PHP dependencies
├── .env.example                  # Environment variables example
└── README.md                     # Main README
```

---

## 🗄️ Database Schema

### Main Tables

#### Table: `aprendices` (Students)
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

**Valid states**: `LECTIVA`, `PRODUCTIVA`, `CANCELADO`, `RETIRADO`, `FINALIZADO`

#### Table: `fichas` (Training Groups)
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

#### Table: `biometria_aprendices` / `biometria_lideres`
```sql
CREATE TABLE biometria_aprendices (
    id SERIAL PRIMARY KEY,
    aprendiz_id INTEGER,
    embedding BYTEA,                    -- Face vector (256 dims)
    confianza DECIMAL(3,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (aprendiz_id) REFERENCES aprendices(id)
);
```

#### Table: `asistencias` (Attendance)
```sql
CREATE TABLE asistencias (
    id SERIAL PRIMARY KEY,
    aprendiz_id INTEGER,
    fecha DATE,
    hora_entrada TIME,
    metodo VARCHAR(20),                 -- BIOMETRIA, MANUAL, QR
    confianza DECIMAL(5,2),             -- Biometric confidence %
    estado VARCHAR(20),                 -- PRESENTE, AUSENTE, EXCUSA
    observaciones TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(aprendiz_id, fecha)
);
```

---

## 🔌 API Reference

### Authentication

All endpoints require JWT authentication except `/api/auth.php` (login).

**Required Header**:
```http
Authorization: Bearer <jwt_token>
```

### Main Endpoints

#### POST `/api/auth.php`
User login.

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
List students with filters.

**Query Params**:
- `ficha`: Filter by group number
- `estado`: LECTIVA, PRODUCTIVA, CANCELADO, etc.
- `page`: Pagination (default: 1)
- `limit`: Items per page (default: 20)

#### POST `/api/biometria.php`
Biometric operations.

**Actions**:
- `registrar`: Create embedding
- `identificar`: 1:1 verification
- `identificar_grupo`: 1:N identification
- `identificar_lideres`: For leadership panel

**Request Identify Group**:
```json
{
  "action": "identificar_grupo",
  "embedding": [0.123, -0.456, ...],  // Array 256 floats
  "ficha": "2995479",
  "umbral": 0.85
}
```

### Error Codes

| Code | Description | Solution |
|--------|-------------|----------|
| `401` | Invalid/expired token | Re-login, refresh token |
| `403` | No permissions | Check user role |
| `404` | Resource not found | Check ID/parameters |
| `409` | Conflict (duplicate) | Attendance already registered |
| `500` | Internal error | Check server logs |

---

## 🔐 Biometric System

### Data Flow

```
┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
│   CAPTURE       │────▶│   PROCESSING    │────▶│   COMPARISON    │
│                 │     │                 │     │                 │
│ 1. Webcam video │     │ 1. Detection    │     │ 1. Query DB     │
│ 2. Canvas frame │     │ 2. Alignment    │     │ 2. Cosine       │
│ 3. Base64 img   │     │ 3. Embedding    │     │ 3. Threshold    │
└─────────────────┘     └─────────────────┘ └─────────────────┘
                                                        │
                                                        ▼
                                               ┌─────────────────┐
                                               │    RESULT       │
                                               │                 │
                                               │ • Match > 0.85  │
                                               │ • Identified    │
                                               │ • Register      │
                                               └─────────────────┘
```

### Similarity Algorithm

**Cosine Similarity**:
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

**Thresholds**:
- **≥ 0.85**: Reliable identification (match)
- **0.70 - 0.85**: Manual review suggested
- **< 0.70**: No match

---

## 🚀 Deployment Configuration

### Option 1: Render (Recommended)

**Advantages**:
- All-in-one (Web + PostgreSQL)
- Automatic deploy from GitHub
- Automatic HTTPS
- $0 for small projects

**Steps**:

1. **Create PostgreSQL**:
   ```
   Dashboard → New → PostgreSQL
   Name: senapre
   Plan: Free
   Region: Oregon
   ```

2. **Create Web Service**:
   ```
   Dashboard → New → Web Service
   GitHub: connect repository
   Runtime: PHP
   Build Command: composer install
   Start Command: heroku-php-apache2
   ```

3. **Configure Variables**:
   ```
   DATABASE_URL: postgresql://...
   JWT_SECRET: [generate random]
   APP_ENV: production
   ```

### Option 2: Vercel + Supabase

**For static frontend + Supabase**:

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

---

## ⚙️ Environment Variables

### Required

```bash
# Database
DATABASE_URL=postgresql://user:pass@host:5432/dbname

# Security
JWT_SECRET=your-secret-key-minimum-32-characters
JWT_EXPIRATION=86400  # 24 hours in seconds

# Application
APP_ENV=production  # development | production
APP_DEBUG=false     # true only in dev

# Supabase (optional, for storage)
SUPABASE_URL=https://xxx.supabase.co
SUPABASE_KEY=eyJhbGci...
SUPABASE_BUCKET=fotos-aprendices
```

---

## 🔒 Security

### Pre-Deploy Security Checklist

- [ ] JWT_SECRET randomly generated (≥32 chars)
- [ ] APP_DEBUG = false in production
- [ ] Security headers configured
- [ ] CORS restricted to valid domains
- [ ] Rate limiting on API
- [ ] Input sanitization (XSS/SQL injection)
- [ ] Passwords hashed (bcrypt)
- [ ] HTTPS enforced
- [ ] Sensitive variables not in code
- [ ] Audit logs enabled

### Security Headers (PHP)

```php
// api/config/headers.php
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' cdn.jsdelivr.net fonts.googleapis.com; font-src fonts.gstatic.com; img-src 'self' data: blob:; connect-src 'self' *.supabase.co;");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Permissions-Policy: camera=(self), microphone=(self)");
```

---

## 🔧 Maintenance

### Daily Tasks

- [ ] Check error logs
- [ ] Verify registered attendances
- [ ] Confirm automatic backups

### Weekly Tasks

- [ ] API performance analysis
- [ ] Review failed login attempts
- [ ] Clean expired tokens

### Monthly Tasks

- [ ] DB index optimization
- [ ] Dependency updates
- [ ] Review unauthorized access
- [ ] Manual database backup

---

## 🐛 Advanced Troubleshooting

### Problem: High CPU Usage

**Symptoms**: Slow server, timeouts

**Diagnosis**:
```bash
# Check PHP processes
ps aux | grep php

# Check slow queries
psql $DATABASE_URL -c "SELECT * FROM pg_stat_statements ORDER BY total_time DESC LIMIT 10;"
```

**Solutions**:
1. Add indexes to `aprendices(numero_documento)`
2. Implement Redis cache
3. Optimize N+1 queries
4. Limit biometric identification results

---

**© 2026 SenApre - Technical Documentation. SENA Internal Use.**
