-- =====================================================
-- SENAPRE - Esquema PostgreSQL
-- Migrado desde SQLite (Asistnet.db)
-- Supabase Project: senapre
-- =====================================================

-- Tablas de soporte (sin dependencias)
CREATE TABLE IF NOT EXISTS estados (
    id SERIAL PRIMARY KEY,
    nombre TEXT NOT NULL UNIQUE,
    color TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS programas_formacion (
    nombre_programa TEXT PRIMARY KEY,
    nivel_formacion TEXT
);

CREATE TABLE IF NOT EXISTS tipoFormacion (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE
);

-- Usuarios (instructores, admin, etc.)
CREATE TABLE IF NOT EXISTS usuarios (
    id_usuario SERIAL PRIMARY KEY,
    nombre TEXT NOT NULL,
    apellido TEXT NOT NULL,
    correo TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    rol TEXT NOT NULL CHECK(rol IN ('director', 'instructor', 'administrativo', 'coordinador')),
    estado TEXT DEFAULT 'activo',
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Fichas de formación
CREATE TABLE IF NOT EXISTS fichas (
    numero_ficha INTEGER PRIMARY KEY,
    nombre_programa TEXT NOT NULL,
    jornada TEXT NOT NULL,
    estado TEXT DEFAULT 'ACTIVO',
    instructor_lider TEXT,
    vocero_principal TEXT,
    vocero_suplente TEXT,
    tipoFormacion VARCHAR(50),
    FOREIGN KEY (nombre_programa) REFERENCES programas_formacion(nombre_programa)
);

-- Aprendices
CREATE TABLE IF NOT EXISTS aprendices (
    documento VARCHAR(20) PRIMARY KEY,
    tipo_identificacion TEXT NOT NULL,
    nombre TEXT NOT NULL,
    apellido TEXT NOT NULL,
    correo TEXT,
    celular TEXT,
    numero_ficha INTEGER,
    estado TEXT DEFAULT 'EN FORMACION',
    FOREIGN KEY (numero_ficha) REFERENCES fichas(numero_ficha)
);

-- Instructores (complementa usuarios)
CREATE TABLE IF NOT EXISTS instructores (
    id_usuario INTEGER PRIMARY KEY,
    nombres TEXT NOT NULL,
    apellidos TEXT NOT NULL,
    correo TEXT NOT NULL,
    telefono NUMERIC,
    estado TEXT DEFAULT 'activo',
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
);

-- Administración, coordinación
CREATE TABLE IF NOT EXISTS administracion (
    id_usuario INTEGER PRIMARY KEY,
    cargo TEXT,
    estado TEXT DEFAULT 'activo',
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
);

CREATE TABLE IF NOT EXISTS administrador (
    id_usuario INTEGER PRIMARY KEY,
    nombres TEXT NOT NULL,
    apellidos TEXT NOT NULL,
    correo TEXT NOT NULL,
    password_hash TEXT,
    rol TEXT,
    estado TEXT DEFAULT 'activo',
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
);

-- Asignaciones de instructores a fichas
CREATE TABLE IF NOT EXISTS asignacion_instructores (
    id_asignacion SERIAL PRIMARY KEY,
    id_usuario INTEGER NOT NULL,
    numero_ficha INTEGER NOT NULL,
    fecha_asignacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario),
    FOREIGN KEY (numero_ficha) REFERENCES fichas(numero_ficha)
);

-- Asistencias
CREATE TABLE IF NOT EXISTS asistencias (
    id_asistencia SERIAL PRIMARY KEY,
    documento_aprendiz TEXT NOT NULL,
    numero_ficha TEXT NOT NULL,
    fecha DATE NOT NULL,
    estado TEXT NOT NULL DEFAULT 'Presente',
    observaciones TEXT,
    id_instructor INTEGER,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS asistencias_backup (
    id_asistencia INTEGER,
    documento_aprendiz INTEGER NOT NULL,
    id_usuario INTEGER NOT NULL,
    fecha TEXT NOT NULL,
    hora_entrada TEXT,
    hora_salida TEXT,
    tipo TEXT DEFAULT 'presente',
    observaciones TEXT,
    archivo_soporte TEXT,
    origen_registro TEXT DEFAULT 'Manual',
    confianza_biometrica INTEGER DEFAULT 0,
    hora TIME
);

-- Horarios de formación
CREATE TABLE IF NOT EXISTS horarios_formacion (
    id SERIAL PRIMARY KEY,
    numero_ficha TEXT NOT NULL,
    id_instructor INTEGER NOT NULL,
    dia_semana INTEGER NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fin TIME NOT NULL,
    jornada TEXT,
    FOREIGN KEY (numero_ficha) REFERENCES fichas(numero_ficha),
    FOREIGN KEY (id_instructor) REFERENCES usuarios(id_usuario)
);

-- Biometría (BLOBs → BYTEA en PostgreSQL)
CREATE TABLE IF NOT EXISTS biometria_aprendices (
    id_biometria SERIAL PRIMARY KEY,
    documento TEXT NOT NULL UNIQUE,
    embedding_facial BYTEA NOT NULL,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ultima_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS biometria_usuarios (
    id_biometria SERIAL PRIMARY KEY,
    id_usuario TEXT NOT NULL UNIQUE,
    embedding_facial BYTEA NOT NULL,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ultima_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Excusas de asistencia
CREATE TABLE IF NOT EXISTS excusas_asistencia (
    id_excusa SERIAL PRIMARY KEY,
    documento TEXT NOT NULL,
    numero_ficha TEXT NOT NULL,
    fecha_falta DATE NOT NULL,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    motivo TEXT NOT NULL,
    archivo_adjunto TEXT,
    estado TEXT DEFAULT 'PENDIENTE',
    evaluado_por TEXT,
    fecha_evaluacion TIMESTAMP,
    observaciones_admin TEXT,
    tipo_excusa TEXT DEFAULT 'INASISTENCIA' CHECK(tipo_excusa IN ('INASISTENCIA', 'LLEGADA_TARDE')),
    UNIQUE(documento, numero_ficha, fecha_falta)
);

-- Bienestar
CREATE TABLE IF NOT EXISTS bienestar_reuniones (
    id SERIAL PRIMARY KEY,
    titulo TEXT NOT NULL,
    descripcion TEXT,
    fecha TIMESTAMP NOT NULL,
    lugar TEXT,
    tipo_convocatoria TEXT,
    creado_por INTEGER,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (creado_por) REFERENCES usuarios(id_usuario)
);

CREATE TABLE IF NOT EXISTS bienestar_asistencia (
    id SERIAL PRIMARY KEY,
    id_reunion INTEGER NOT NULL,
    id_aprendiz TEXT NOT NULL,
    estado TEXT DEFAULT 'ausente',
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_reunion) REFERENCES bienestar_reuniones(id)
);

CREATE TABLE IF NOT EXISTS bienestar_excusas (
    id SERIAL PRIMARY KEY,
    id_asistencia INTEGER NOT NULL,
    archivo_adjunto TEXT,
    motivo TEXT,
    estado_excusa TEXT DEFAULT 'pendiente',
    fecha_presentacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_asistencia) REFERENCES bienestar_asistencia(id)
);

-- Población diferencial
CREATE TABLE IF NOT EXISTS "Mujer" (
    documento VARCHAR(20) PRIMARY KEY,
    FOREIGN KEY (documento) REFERENCES aprendices(documento) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS campesino (
    documento VARCHAR(20) PRIMARY KEY,
    FOREIGN KEY (documento) REFERENCES aprendices(documento) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS discapacidad (
    documento VARCHAR(20) PRIMARY KEY,
    FOREIGN KEY (documento) REFERENCES aprendices(documento) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS "indígena" (
    documento VARCHAR(20) PRIMARY KEY,
    FOREIGN KEY (documento) REFERENCES aprendices(documento) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS lgbtiq (
    documento VARCHAR(20) PRIMARY KEY,
    FOREIGN KEY (documento) REFERENCES aprendices(documento) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS narp (
    documento VARCHAR(20) PRIMARY KEY,
    FOREIGN KEY (documento) REFERENCES aprendices(documento) ON DELETE CASCADE
);

-- Representantes y voceros
CREATE TABLE IF NOT EXISTS representantes_jornada (
    jornada VARCHAR(50) PRIMARY KEY,
    documento VARCHAR(20),
    FOREIGN KEY (documento) REFERENCES aprendices(documento) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS voceros_enfoque (
    tipo_poblacion VARCHAR(50) PRIMARY KEY,
    documento VARCHAR(20),
    FOREIGN KEY (documento) REFERENCES aprendices(documento) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS propuestas_enfoque (
    id SERIAL PRIMARY KEY,
    tipo_poblacion VARCHAR(50) NOT NULL,
    propuesta TEXT NOT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Área de responsables
CREATE TABLE IF NOT EXISTS area_responsables (
    id SERIAL PRIMARY KEY,
    id_usuario INTEGER NOT NULL,
    area TEXT NOT NULL,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
);

-- Logs
CREATE TABLE IF NOT EXISTS logs (
    id_log SERIAL PRIMARY KEY,
    id_usuario INTEGER,
    accion TEXT,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
);

-- Índices para rendimiento
CREATE INDEX IF NOT EXISTS idx_biometria_aprendices_doc ON biometria_aprendices(documento);
CREATE INDEX IF NOT EXISTS idx_biometria_usuarios_id ON biometria_usuarios(id_usuario);
CREATE INDEX IF NOT EXISTS idx_excusas_documento ON excusas_asistencia(documento);
CREATE INDEX IF NOT EXISTS idx_excusas_estado ON excusas_asistencia(estado);
CREATE INDEX IF NOT EXISTS idx_excusas_fecha ON excusas_asistencia(fecha_falta);
CREATE INDEX IF NOT EXISTS idx_asistencias_ficha_fecha ON asistencias(numero_ficha, fecha);
CREATE INDEX IF NOT EXISTS idx_aprendices_ficha ON aprendices(numero_ficha);
