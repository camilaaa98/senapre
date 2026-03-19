-- ESQUEMA DE BASE DE DATOS PARA SISTEMA DE NOTIFICACIONES
-- Sistema de Convocatorias y Autorizaciones de Voceros

-- 1. TABLA DE CONVOCATORIAS DE REUNIÓN
CREATE TABLE IF NOT EXISTS convocatorias_reunion (
    id SERIAL PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    fecha DATE NOT NULL,
    hora TIME NOT NULL,
    lugar VARCHAR(255) NOT NULL,
    tipo VARCHAR(50) DEFAULT 'Ordinaria',
    agenda JSONB,
    descripcion TEXT,
    fichas_invitadas TEXT[], -- Array de fichas invitadas
    estado VARCHAR(20) DEFAULT 'activa', -- activa, cancelada, completada
    creado_por VARCHAR(100),
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. TABLA DE AUTORIZACIONES DE VOCERO
CREATE TABLE IF NOT EXISTS autorizaciones_vocero (
    id SERIAL PRIMARY KEY,
    convocatoria_id INTEGER REFERENCES convocatorias_reunion(id) ON DELETE CASCADE,
    instructor_id INTEGER NOT NULL, -- ID del usuario instructor
    vocero_autorizado_id INTEGER NOT NULL, -- ID del aprendiz vocero
    fecha_autorizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    estado VARCHAR(20) DEFAULT 'autorizado', -- autorizado, rechazado, pendiente
    observaciones TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    CONSTRAINT unique_autorizacion UNIQUE (convocatoria_id, instructor_id)
);

-- 3. TABLA DE NOTIFICACIONES
CREATE TABLE IF NOT EXISTS notificaciones (
    id SERIAL PRIMARY KEY,
    tipo VARCHAR(50) NOT NULL, -- whatsapp, correo, sms, push
    destinatario_id INTEGER NOT NULL,
    destinatario_tipo VARCHAR(20) NOT NULL, -- instructor, vocero, administrativo
    convocatoria_id INTEGER REFERENCES convocatorias_reunion(id) ON DELETE CASCADE,
    autorizacion_id INTEGER REFERENCES autorizaciones_vocero(id) ON DELETE CASCADE,
    mensaje TEXT NOT NULL,
    estado VARCHAR(20) DEFAULT 'pendiente', -- pendiente, enviada, fallida, leida
    fecha_envio TIMESTAMP,
    fecha_lectura TIMESTAMP,
    respuesta TEXT,
    error_message TEXT,
    intentos INTEGER DEFAULT 0,
    creado_por VARCHAR(100),
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 4. TABLA DE CONFIRMACIONES DE ASISTENCIA
CREATE TABLE IF NOT EXISTS confirmaciones_asistencia (
    id SERIAL PRIMARY KEY,
    convocatoria_id INTEGER REFERENCES convocatorias_reunion(id) ON DELETE CASCADE,
    vocero_id INTEGER NOT NULL, -- ID del aprendiz vocero
    autorizacion_id INTEGER REFERENCES autorizaciones_vocero(id) ON DELETE CASCADE,
    respuesta VARCHAR(20) NOT NULL, -- confirmado, cancelado, pendiente
    medio_respuesta VARCHAR(20), -- whatsapp, correo, telefono, presencial
    fecha_respuesta TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    observaciones TEXT,
    confirmado_por VARCHAR(100), -- Nombre de quien confirmó
    ip_address VARCHAR(45)
);

-- 5. TABLA DE CONFIGURACIÓN DE NOTIFICACIONES
CREATE TABLE IF NOT EXISTS configuracion_notificaciones (
    id SERIAL PRIMARY KEY,
    clave VARCHAR(100) UNIQUE NOT NULL,
    valor TEXT NOT NULL,
    descripcion TEXT,
    tipo VARCHAR(20) DEFAULT 'string', -- string, boolean, integer, json
    activo BOOLEAN DEFAULT true,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 8. TABLA DE FIRMAS DIGITALES
CREATE TABLE IF NOT EXISTS firmas_digitales (
    id SERIAL PRIMARY KEY,
    usuario_id INTEGER NOT NULL,
    nombre_completo VARCHAR(255) NOT NULL,
    cargo VARCHAR(100),
    firma_imagen_path VARCHAR(500), -- Ruta a la imagen de la firma
    firma_base64 TEXT, -- Firma en base64 como respaldo
    activa BOOLEAN DEFAULT true, -- Firma activa por defecto
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id_usuario) ON DELETE CASCADE
);

-- 9. TABLA DE CONFIGURACIÓN DE MEDIOS POR USUARIO
CREATE TABLE IF NOT EXISTS configuracion_medios_usuario (
    id SERIAL PRIMARY KEY,
    usuario_id INTEGER NOT NULL,
    medio VARCHAR(50) NOT NULL, -- whatsapp, correo, sms, push, telegram
    activo BOOLEAN DEFAULT true,
    configuracion JSONB, -- Configuración específica del medio
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
    UNIQUE(usuario_id, medio)
);

-- 10. TABLA DE PLANTILLAS DE MENSAJES
CREATE TABLE IF NOT EXISTS plantillas_mensajes (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(100) UNIQUE NOT NULL,
    tipo VARCHAR(50) NOT NULL, -- whatsapp_instructor, correo_vocero, sms_recordatorio, push_notificacion
    medio VARCHAR(50) NOT NULL, -- whatsapp, correo, sms, push, telegram
    asunto VARCHAR(255), -- Para correos
    contenido TEXT NOT NULL,
    variables JSONB, -- Variables que puede usar la plantilla
    activo BOOLEAN DEFAULT true,
    creado_por VARCHAR(100),
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 7. VISTA PARA CONSULTAS COMPLEJAS
CREATE OR REPLACE VIEW vista_convocatorias_completas AS
SELECT 
    cr.id,
    cr.titulo,
    cr.fecha,
    cr.hora,
    cr.lugar,
    cr.tipo,
    cr.estado,
    cr.fecha_creacion,
    COUNT(av.id) as total_autorizaciones,
    COUNT(DISTINCT av.instructor_id) as instructores_participantes,
    COUNT(DISTINCT av.vocero_autorizado_id) as voceros_autorizados,
    COUNT(CASE WHEN ca.respuesta = 'confirmado' THEN 1 END) as confirmaciones,
    ARRAY_AGG(DISTINCT u.nombre) as instructores_nombres
FROM convocatorias_reunion cr
LEFT JOIN autorizaciones_vocero av ON cr.id = av.convocatoria_id
LEFT JOIN confirmaciones_asistencia ca ON cr.id = ca.convocatoria_id
LEFT JOIN usuarios u ON av.instructor_id = u.id_usuario
GROUP BY cr.id, cr.titulo, cr.fecha, cr.hora, cr.lugar, cr.tipo, cr.estado, cr.fecha_creacion
ORDER BY cr.fecha_creacion DESC;

-- 8. ÍNDICES PARA OPTIMIZAR CONSULTAS
CREATE INDEX IF NOT EXISTS idx_convocatorias_fecha ON convocatorias_reunion(fecha);
CREATE INDEX IF NOT EXISTS idx_convocatorias_estado ON convocatorias_reunion(estado);
CREATE INDEX IF NOT EXISTS idx_autorizaciones_convocatoria ON autorizaciones_vocero(convocatoria_id);
CREATE INDEX IF NOT EXISTS idx_autorizaciones_instructor ON autorizaciones_vocero(instructor_id);
CREATE INDEX IF NOT EXISTS idx_autorizaciones_vocero ON autorizaciones_vocero(vocero_autorizado_id);
CREATE INDEX IF NOT EXISTS idx_notificaciones_destinatario ON notificaciones(destinatario_id, destinatario_tipo);
CREATE INDEX IF NOT EXISTS idx_notificaciones_estado ON notificaciones(estado);
CREATE INDEX IF NOT EXISTS idx_confirmaciones_convocatoria ON confirmaciones_asistencia(convocatoria_id);
CREATE INDEX IF NOT EXISTS idx_confirmaciones_vocero ON confirmaciones_asistencia(vocero_id);

-- 9. TRIGGER PARA ACTUALIZAR FECHA_DE_ACTUALIZACIÓN
CREATE OR REPLACE FUNCTION actualizar_timestamp()
RETURNS TRIGGER AS $$
BEGIN
    NEW.fecha_actualizacion = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trigger_convocatorias_actualizacion
    BEFORE UPDATE ON convocatorias_reunion
    FOR EACH ROW
    EXECUTE FUNCTION actualizar_timestamp();

CREATE TRIGGER trigger_plantillas_actualizacion
    BEFORE UPDATE ON plantillas_mensajes
    FOR EACH ROW
    EXECUTE FUNCTION actualizar_timestamp();

CREATE TRIGGER trigger_configuracion_actualizacion
    BEFORE UPDATE ON configuracion_notificaciones
    FOR EACH ROW
    EXECUTE FUNCTION actualizar_timestamp();

-- 10. DATOS INICIALES DE CONFIGURACIÓN
INSERT INTO configuracion_notificaciones (clave, valor, descripcion, tipo) VALUES
('whatsapp_api_key', '', 'Clave API para WhatsApp Business', 'string'),
('whatsapp_api_url', 'https://graph.facebook.com/v18.0/', 'URL API WhatsApp', 'string'),
('whatsapp_numero_sena', '', 'Número de WhatsApp del SENA', 'string'),
('smtp_host', 'localhost', 'Servidor SMTP para correos', 'string'),
('smtp_port', '587', 'Puerto SMTP', 'integer'),
('smtp_username', '', 'Usuario SMTP', 'string'),
('smtp_password', '', 'Contraseña SMTP', 'string'),
('smtp_from_name', 'SENA Teleinformática', 'Nombre remitente correos', 'string'),
('smtp_from_email', 'bienestar@sena.edu.co', 'Correo remitente', 'string'),
('max_intentos_notificacion', '3', 'Máximo de intentos de envío', 'integer'),
('dias_recordatorio', '1', 'Días antes para enviar recordatorio', 'integer'),
('notificaciones_activas', 'true', 'Activar envío de notificaciones', 'boolean')
ON CONFLICT (clave) DO NOTHING;

-- 11. PLANTILLAS DE MENSAJES POR DEFECTO
INSERT INTO plantillas_mensajes (nombre, tipo, asunto, contenido, variables) VALUES
('whatsapp_instructor', 'whatsapp_instructor', NULL, 
'👨‍🏫 Estimado Instructor {{instructor_nombre}}

📋 RECORDATORIO: Oficio de autorización pendiente

🎭 VOCEROS FICHA {{ficha_numero}}:
{{#voceros}}
• {{nombre}} ({{rol}})
{{/voceros}}

📅 Reunión: {{reunion_titulo}}
🗓️ Fecha: {{reunion_fecha}}
⏰ Hora: {{reunion_hora}}
📍 Lugar: {{reunion_lugar}}

⚠️ ACCIÓN REQUERIDA:
Complete el oficio de autorización lo antes posible.
Link: {{oficio_url}}

🏢 SENA - Coordinación de Bienestar',
'{"instructor_nombre": "string", "ficha_numero": "string", "voceros": "array", "reunion_titulo": "string", "reunion_fecha": "string", "reunion_hora": "string", "reunion_lugar": "string", "oficio_url": "string"}'),

('correo_vocero_autorizado', 'correo_vocero', 'OFICIO DE AUTORIZACIÓN - {{vocero_rol}} - {{vocero_nombre}}',
'Estimado/a {{vocero_nombre}},

Por medio de la presente se le notifica que ha sido autorizado/a para asistir a la {{reunion_titulo}} en su calidad de {{vocero_rol}}.

📅 Fecha: {{reunion_fecha}}
⏰ Hora: {{reunion_hora}}
📍 Lugar: {{reunion_lugar}}

Se adjunta copia del oficio de autorización firmado por el instructor.

Es requisito indispensable confirmar su asistencia.

Atentamente,
Coordinación de Bienestar
SENA - Centro de Teleinformática y Producción Industrial',
'{"vocero_nombre": "string", "vocero_rol": "string", "reunion_titulo": "string", "reunion_fecha": "string", "reunion_hora": "string", "reunion_lugar": "string"}'),

('whatsapp_vocero_autorizado', 'whatsapp_vocero', NULL,
'🎭 Estimado/a {{vocero_nombre}}

✅ ¡BUENA NOTICIA! Has sido autorizado/a para asistir a la reunión.

📋 CONVOCATORIA OFICIAL - SENA TELEINFORMÁTICA
🎭 Tu rol: {{vocero_rol}}
📚 Ficha: {{ficha_numero}}
📅 Reunión: {{reunion_titulo}}
🗓️ Fecha: {{reunion_fecha}}
⏰ Hora: {{reunion_hora}}
📍 Lugar: {{reunion_lugar}}

✅ Por favor confirma asistencia respondiendo: ASISTIRÉ

🏢 SENA - Centro de Teleinformática
🔖 ID: VOC-{{vocero_documento}}-{{reunion_fecha}}',
'{"vocero_nombre": "string", "vocero_rol": "string", "ficha_numero": "string", "reunion_titulo": "string", "reunion_fecha": "string", "reunion_hora": "string", "reunion_lugar": "string", "vocero_documento": "string"}'),

('sms_recordatorio', 'sms_recordatorio', NULL,
'SENA: Convocatoria reunión {{vocero_rol}} {{vocero_nombre}}. Fecha: {{reunion_fecha}} {{reunion_hora}}. Lugar: {{reunion_lugar}}. Confirmar: {{telefono_contacto}}. ID: VOC-{{vocero_documento}}',
'{"vocero_rol": "string", "vocero_nombre": "string", "reunion_fecha": "string", "reunion_hora": "string", "reunion_lugar": "string", "telefono_contacto": "string", "vocero_documento": "string"}')
ON CONFLICT (nombre) DO NOTHING;

-- 12. FUNCIONES ÚTILES
-- Función para obtener estadísticas de notificaciones
CREATE OR REPLACE FUNCTION obtener_estadisticas_notificaciones()
RETURNS TABLE(
    total_convocatorias BIGINT,
    convocatorias_ultimos_30_dias BIGINT,
    instructores_participantes BIGINT,
    voceros_autorizados BIGINT,
    notificaciones_enviadas BIGINT,
    confirmaciones_recibidas BIGINT
) AS $$
BEGIN
    RETURN QUERY
    SELECT 
        (SELECT COUNT(*) FROM convocatorias_reunion) as total_convocatorias,
        (SELECT COUNT(*) FROM convocatorias_reunion WHERE fecha >= CURRENT_DATE - INTERVAL '30 days') as convocatorias_ultimos_30_dias,
        (SELECT COUNT(DISTINCT instructor_id) FROM autorizaciones_vocero) as instructores_participantes,
        (SELECT COUNT(DISTINCT vocero_autorizado_id) FROM autorizaciones_vocero) as voceros_autorizados,
        (SELECT COUNT(*) FROM notificaciones WHERE estado = 'enviada') as notificaciones_enviadas,
        (SELECT COUNT(*) FROM confirmaciones_asistencia WHERE respuesta = 'confirmado') as confirmaciones_recibidas;
END;
$$ LANGUAGE plpgsql;

-- Función para verificar si un vocero ya tiene autorización para una convocatoria
CREATE OR REPLACE FUNCTION verificar_autorizacion_vocero(p_convocatoria_id INTEGER, p_instructor_id INTEGER)
RETURNS TABLE(
    ya_autorizado BOOLEAN,
    vocero_autorizado_id INTEGER,
    vocero_autorizado_nombre VARCHAR(255),
    fecha_autorizacion TIMESTAMP
) AS $$
BEGIN
    RETURN QUERY
    SELECT 
        true as ya_autorizado,
        av.vocero_autorizado_id,
        a.nombre as vocero_autorizado_nombre,
        av.fecha_autorizacion
    FROM autorizaciones_vocero av
    JOIN aprendices a ON av.vocero_autorizado_id = a.id_aprendiz
    WHERE av.convocatoria_id = p_convocatoria_id 
    AND av.instructor_id = p_instructor_id
    LIMIT 1;
    
    -- Si no hay resultados, devolver false
    IF NOT FOUND THEN
        RETURN QUERY SELECT false, NULL, NULL, NULL;
    END IF;
END;
$$ LANGUAGE plpgsql;

-- 13. COMENTARIOS EXPLICATIVOS
COMMENT ON TABLE convocatorias_reunion IS 'Tabla principal para almacenar todas las convocatorias a reuniones de voceros';
COMMENT ON TABLE autorizaciones_vocero IS 'Registra las autorizaciones firmadas por los instructores para cada convocatoria';
COMMENT ON TABLE notificaciones IS 'Controla todas las notificaciones enviadas (WhatsApp, correo, SMS)';
COMMENT ON TABLE confirmaciones_asistencia IS 'Registra las respuestas de los voceros a las convocatorias';
COMMENT ON TABLE configuracion_notificaciones IS 'Configuración general del sistema de notificaciones';
COMMENT ON TABLE plantillas_mensajes IS 'Plantillas predefinidas para los diferentes tipos de mensajes';

-- 14. PERMISOS RECOMENDADOS (ajustar según el usuario de base de datos)
-- GRANT SELECT, INSERT, UPDATE, DELETE ON ALL TABLES IN SCHEMA public TO senapre_user;
-- GRANT USAGE, SELECT ON ALL SEQUENCES IN SCHEMA public TO senapre_user;
-- GRANT EXECUTE ON ALL FUNCTIONS IN SCHEMA public TO senapre_user;

-- 15. VERIFICACIÓN DE INSTALACIÓN
SELECT 'Esquema de notificaciones instalado correctamente' as mensaje,
       NOW() as fecha_instalacion;
