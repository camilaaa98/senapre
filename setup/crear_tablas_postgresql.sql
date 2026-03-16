-- Crear tablas de liderazgo para PostgreSQL/PostgREST
-- Ejecutar en base de datos Senapre

-- 1. Tabla de voceros de enfoque diferencial
CREATE TABLE IF NOT EXISTS voceros_enfoque (
    id SERIAL PRIMARY KEY,
    tipo_poblacion VARCHAR(50) NOT NULL,
    documento VARCHAR(20) NOT NULL,
    fecha_asignacion DATE DEFAULT CURRENT_DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT unique_tipo_poblacion UNIQUE (tipo_poblacion)
);

-- 2. Tabla de representantes por jornada
CREATE TABLE IF NOT EXISTS representantes (
    id SERIAL PRIMARY KEY,
    documento VARCHAR(20) NOT NULL,
    tipo_jornada VARCHAR(20) NOT NULL, -- 'diurna' o 'mixta'
    fecha_asignacion DATE DEFAULT CURRENT_DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT unique_tipo_jornada UNIQUE (tipo_jornada)
);

-- 3. Crear índices para mejor rendimiento
CREATE INDEX IF NOT EXISTS idx_voceros_documento ON voceros_enfoque(documento);
CREATE INDEX IF NOT EXISTS idx_voceros_tipo ON voceros_enfoque(tipo_poblacion);
CREATE INDEX IF NOT EXISTS idx_representantes_documento ON representantes(documento);
CREATE INDEX IF NOT EXISTS idx_representantes_jornada ON representantes(tipo_jornada);

-- 4. Crear función para actualizar timestamp
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

-- 5. Crear triggers para actualizar timestamps
CREATE TRIGGER update_voceros_enfoque_updated_at 
    BEFORE UPDATE ON voceros_enfoque 
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_representantes_updated_at 
    BEFORE UPDATE ON representantes 
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- 6. Insertar datos de ejemplo (opcional)
INSERT INTO voceros_enfoque (tipo_poblacion, documento) VALUES 
('mujer', '1056930328'),
('indigena', '1117506963')
ON CONFLICT (tipo_poblacion) DO NOTHING;

INSERT INTO representantes (documento, tipo_jornada) VALUES 
('1056930328', 'diurna'),
('1117506963', 'mixta')
ON CONFLICT (tipo_jornada) DO NOTHING;

-- 7. Configurar permisos para PostgREST (ajustar según tu usuario)
-- GRANT USAGE ON SCHEMA public TO web_anon;
-- GRANT SELECT ON ALL TABLES IN SCHEMA public TO web_anon;
-- GRANT INSERT, UPDATE, DELETE ON voceros_enfoque TO web_user;
-- GRANT INSERT, UPDATE, DELETE ON representantes TO web_user;
-- GRANT USAGE ON ALL SEQUENCES IN SCHEMA public TO web_user;

COMMIT;
