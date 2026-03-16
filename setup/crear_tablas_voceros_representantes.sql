-- Tabla para voceros de enfoque diferencial
CREATE TABLE IF NOT EXISTS voceros_enfoque (
    id SERIAL PRIMARY KEY,
    tipo_poblacion VARCHAR(50) NOT NULL UNIQUE,
    documento VARCHAR(20) NOT NULL,
    fecha_asignacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla para representantes (diurna/mixta)
CREATE TABLE IF NOT EXISTS representantes (
    id SERIAL PRIMARY KEY,
    tipo_jornada VARCHAR(20) NOT NULL UNIQUE, -- 'diurna' o 'mixta'
    documento VARCHAR(20) NOT NULL,
    fecha_asignacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Índices para mejor rendimiento
CREATE INDEX IF NOT EXISTS idx_voceros_poblacion ON voceros_enfoque(tipo_poblacion);
CREATE INDEX IF NOT EXISTS idx_voceros_documento ON voceros_enfoque(documento);
CREATE INDEX IF NOT EXISTS idx_representantes_jornada ON representantes(tipo_jornada);
CREATE INDEX IF NOT EXISTS idx_representantes_documento ON representantes(documento);

-- Trigger para actualizar timestamp de actualización en voceros_enfoque
CREATE OR REPLACE FUNCTION update_voceros_enfoque_timestamp()
RETURNS TRIGGER AS $$
BEGIN
    NEW.actualizado_en = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER trigger_voceros_enfoque_actualizado
    BEFORE UPDATE ON voceros_enfoque
    FOR EACH ROW
    EXECUTE FUNCTION update_voceros_enfoque_timestamp();

-- Trigger para actualizar timestamp de actualización en representantes
CREATE OR REPLACE FUNCTION update_representantes_timestamp()
RETURNS TRIGGER AS $$
BEGIN
    NEW.actualizado_en = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER trigger_representantes_actualizado
    BEFORE UPDATE ON representantes
    FOR EACH ROW
    EXECUTE FUNCTION update_representantes_timestamp();

-- Insertar datos de ejemplo (si no existen)
INSERT INTO voceros_enfoque (tipo_poblacion, documento) VALUES
    ('mujer', '1056930328'),
    ('indigena', '1117506963'),
    ('narp', '1234567890'),
    ('campesino', '0987654321'),
    ('lgbtiq', '1122334455'),
    ('discapacidad', '5566778899')
ON CONFLICT (tipo_poblacion) DO NOTHING;

INSERT INTO representantes (tipo_jornada, documento) VALUES
    ('diurna', '1056930328'),
    ('mixta', '1117506963')
ON CONFLICT (tipo_jornada) DO NOTHING;
