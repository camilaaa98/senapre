-- Crear tablas para gestión de liderazgo
-- Ejecutar este script en la base de datos para crear las tablas necesarias

-- Tabla de voceros de enfoque diferencial
CREATE TABLE IF NOT EXISTS voceros_enfoque (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    tipo_poblacion TEXT NOT NULL, -- mujer, indigena, narp, campesino, lgbtiq, discapacidad
    documento TEXT NOT NULL,
    fecha_asignacion DATE DEFAULT CURRENT_DATE,
    FOREIGN KEY (documento) REFERENCES aprendices(documento),
    UNIQUE(tipo_poblacion)
);

-- Tabla de representantes por jornada
CREATE TABLE IF NOT EXISTS representantes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    documento TEXT NOT NULL,
    tipo_jornada TEXT NOT NULL, -- diurna, mixta
    fecha_asignacion DATE DEFAULT CURRENT_DATE,
    FOREIGN KEY (documento) REFERENCES aprendices(documento),
    UNIQUE(tipo_jornada)
);

-- Insertar algunos datos de ejemplo (opcional)
-- Voceros de ejemplo
INSERT OR IGNORE INTO voceros_enfoque (tipo_poblacion, documento) VALUES 
('mujer', '1056930328'), -- Jancy (ejemplo)
('indigena', '1117506963'); -- Erik (ejemplo)

-- Representantes de ejemplo
INSERT OR IGNORE INTO representantes (documento, tipo_jornada) VALUES 
('1056930328', 'diurna'), -- Jancy como representante diurno
('1117506963', 'mixta'); -- Erik como representante mixto
