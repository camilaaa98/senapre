-- Agregar columnas para gestión de credenciales de voceros
-- Ejecutar este script en PostgreSQL para actualizar la tabla usuarios

-- Columna para indicar si las credenciales de vocero están activas
ALTER TABLE usuarios 
ADD COLUMN IF NOT EXISTS credenciales_vocero_activas BOOLEAN DEFAULT FALSE;

-- Columna para registrar cuándo se actualizaron las credenciales por última vez
ALTER TABLE usuarios 
ADD COLUMN IF NOT EXISTS fecha_actualizacion_credenciales TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

-- Comentario para documentar las nuevas columnas
COMMENT ON COLUMN usuarios.credenciales_vocero_activas IS 'Indica si el vocero tiene credenciales activas para login';
COMMENT ON COLUMN usuarios.fecha_actualizacion_credenciales IS 'Fecha de última actualización de credenciales de vocero';

-- Crear índice para mejorar rendimiento en consultas de voceros
CREATE INDEX IF NOT EXISTS idx_usuarios_voceros_credenciales 
ON usuarios(rol, credenciales_vocero_activas, estado);

-- Actualizar voceros existentes para que tengan credenciales desactivadas por defecto
UPDATE usuarios 
SET credenciales_vocero_activas = FALSE, 
    estado = 0,
    fecha_actualizacion_credenciales = CURRENT_TIMESTAMP
WHERE rol = 'vocero' AND credenciales_vocero_activas IS NULL;

-- Mostrar resumen de cambios
SELECT 
    'Columnas agregadas exitosamente' as mensaje,
    COUNT(*) as total_voceros,
    COUNT(*) FILTER (WHERE credenciales_vocero_activas = TRUE) as con_credenciales_activas,
    COUNT(*) FILTER (WHERE credenciales_vocero_activas = FALSE) as con_credenciales_inactivas
FROM usuarios 
WHERE rol = 'vocero';
