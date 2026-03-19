# Manual de Usuario - Sistema SenApre
## Sistema de Gestión de Asistencias SENA

**Versión**: 1.0  
**Última actualización**: Marzo 2026  
**Idioma**: Español

---

## 📋 Índice

1. [Introducción](#introducción)
2. [Acceso al Sistema](#acceso-al-sistema)
3. [Roles de Usuario](#roles-de-usuario)
4. [Panel de Administrador](#panel-de-administrador)
5. [Panel de Instructor](#panel-de-instructor)
6. [Panel de Liderazgo](#panel-de-liderazgo)
7. [Panel de Voceros](#panel-de-voceros)
8. [Sistema Biométrico Facial](#sistema-biométrico-facial)
9. [Solución de Problemas](#solución-de-problemas)

---

## 🎯 Introducción

SenApre es un sistema integral para la gestión de asistencias del SENA, diseñado para automatizar y modernizar el control de asistencias de aprendices mediante reconocimiento facial biométrico.

### ✨ Características Principales

- ✅ **Reconocimiento facial** para registro de asistencias
- ✅ **Múltiples paneles** según rol de usuario
- ✅ **Gestión de fichas** y aprendices
- ✅ **Reportes y estadísticas** en tiempo real
- ✅ **Acceso desde cualquier dispositivo** con cámara

---

## 🔑 Acceso al Sistema

### Requisitos Técnicos

- Navegador web moderno (Chrome, Firefox, Edge, Safari)
- Conexión a Internet
- Cámara web (para funciones biométricas)
- Permisos de cámara habilitados

### URL de Acceso

```
https://tu-render-url.onrender.com
```

### Credenciales Iniciales

Las credenciales son asignadas por el administrador del sistema. Por defecto:
- **Usuario**: Número de documento
- **Contraseña**: Número de documento (cambiar en primer inicio)

---

## 👥 Roles de Usuario

### 1. 🎓 Aprendiz (Vocero)
**Funciones**:
- Consultar historial de asistencias
- Registrar asistencia biométrica
- Actualizar datos personales
- Recibir notificaciones de convocatorias

**Acceso**: `index.html` → Panel de Vocero

### 2. 👨‍🏫 Instructor
**Funciones**:
- Gestionar asistencias de su ficha
- Ver reportes de aprendices
- Generar informes de asistencia
- Usar reconocimiento facial para tomar lista

**Acceso**: `instructor-dashboard.html`

### 3. 🏛️ Administrativo de Bienestar (Liderazgo)
**Funciones**:
- Gestionar reuniones de liderazgo
- Registrar asistencia biométrica de líderes
- Administrar voceros y representantes
- Ver estadísticas de participación

**Acceso**: `liderazgo.html`

### 4. 👔 Director
**Funciones**:
- Dashboard general del sistema
- Gestión de usuarios y permisos
- Reportes globales de asistencias
- Configuración del sistema

**Acceso**: `admin-dashboard.html`

### 5. ⚙️ Administrador del Sistema
**Funciones**:
- Gestión completa de usuarios
- Configuración de base de datos
- Importación de datos masiva
- Mantenimiento del sistema

---

## 🖥️ Panel de Administrador

### Dashboard Principal

Muestra métricas clave:
- **Total de fichas activas**
- **Aprendices registrados**
- **Asistencias del día**
- **Reportes pendientes**

### Gestión de Usuarios

1. Navegar a **"Usuarios"** en el menú lateral
2. Ver lista de usuarios con filtros por rol
3. Acciones disponibles:
   - Crear nuevo usuario
   - Editar información
   - Cambiar contraseña
   - Activar/Desactivar cuenta

### Importación de Datos

**Para importar aprendices desde Excel**:

1. Ir a **"Importar Datos"**
2. Subir archivos Excel de fichas a la carpeta `fichas/`
3. Ejecutar script de importación
4. Revisar reporte de resultados

⚠️ **Nota**: Los archivos deben seguir el formato establecido por el SENA.

---

## 📚 Panel de Instructor

### Dashboard del Instructor

Muestra:
- Fichas asignadas
- Total de aprendices por ficha
- Asistencias del día
- Alertas de aprendices con baja asistencia

### Tomar Asistencia

#### Opción A: Asistencia Biométrica (Recomendada)

1. Ir a **"Asistencia"** → **"Tomar Asistencia"**
2. Seleccionar ficha
3. Permitir acceso a la cámara
4. El sistema detectará automáticamente los rostros
5. Los aprendices reconocidos se marcarán como presentes
6. Para aprendices no reconocidos, usar opción manual

#### Opción B: Asistencia Manual

1. Seleccionar ficha
2. Marcar cada aprendiz como:
   - ✅ Presente
   - ❌ Ausente
   - 📝 Excusa (subir documento)

### Ver Reportes

1. Ir a **"Reportes"**
2. Seleccionar tipo de reporte:
   - **Diario**: Asistencias de un día específico
   - **Semanal**: Resumen semanal
   - **Mensual**: Análisis mensual
   - **Por Aprendiz**: Historial individual
3. Filtrar por ficha y fechas
4. Exportar a PDF o Excel

---

## 🏛️ Panel de Liderazgo

### Gestión de Reuniones

1. Ir a **"Reuniones"**
2. Crear nueva reunión:
   - Título
   - Fecha y hora
   - Tipo (ordinaria/extraordinaria)
   - Descripción
3. Sistema genera código QR para invitación

### Registro Biométrico de Líderes

**Antes de usar el sistema, los líderes deben registrarse**:

1. Ir a **"Biometría"** → **"Registro Facial"**
2. Seleccionar líder de la lista
3. Permitir acceso a cámara
4. Capturar 3 fotos del rostro (frente, lado izquierdo, lado derecho)
5. Guardar registro biométrico

### Asistencia a Reuniones

1. Ir a **"Asistencia"** → **"Reuniones"**
2. Seleccionar reunión activa
3. Activar cámara para reconocimiento facial
4. Los líderes se registran automáticamente al detectar su rostro
5. Ver estadísticas en tiempo real:
   - Total de invitados
   - Presentes
   - Ausentes

---

## 🎓 Panel de Voceros

### Mi Perfil

Ver y actualizar:
- Datos personales
- Ficha asignada
- Foto de perfil
- Información de contacto

### Mis Asistencias

Consultar:
- Historial de asistencias
- Días presentes/ausentes
- Excusas registradas
- Estadísticas personales

### Registro Biométrico

**Primer uso**:

1. Acceder a **"Registro Facial"**
2. Permitir cámara
3. Seguir instrucciones para captura de rostro
4. Completar las 3 capturas requeridas
5. Confirmar registro exitoso

**Marcar asistencia**:

1. Ir a **"Registrar Asistencia"**
2. Permitir cámara
3. Sistema reconocerá automáticamente tu rostro
4. Confirmar asistencia registrada

---

## 🔐 Sistema Biométrico Facial

### ¿Cómo funciona?

1. **Captura**: La cámara detecta rostros en tiempo real
2. **Extracción**: Sistema extrae características faciales únicas (embeddings)
3. **Comparación**: Busca coincidencias en base de datos
4. **Verificación**: Si similitud ≥ 85%, identifica al usuario
5. **Registro**: Marca asistencia con timestamp

### Recomendaciones para Mejor Reconocimiento

✅ **Buena iluminación** (preferiblemente natural)  
✅ **Rostro descubierto** (sin lentes oscuros, gorras, barbijos)  
✅ **Mirar a cámara** (frente, no de perfil)  
✅ **Fondo neutro** (evitar fondos muy movidos)  
✅ **Distancia 50-100cm** de la cámara  

❌ **Evitar**:
- Iluminación muy baja o muy brillante
- Múltiples personas en el cuadro
- Movimientos bruscos durante captura

---

## ⚠️ Solución de Problemas

### No carga la página

1. Verificar conexión a Internet
2. Limpiar caché del navegador (Ctrl + Shift + R)
3. Intentar en navegador de incógnito
4. Verificar que la URL sea correcta

### La cámara no funciona

1. **Permisos del navegador**:
   - Click en 🔒 icono de seguridad (barra de dirección)
   - Permitir cámara
   - Recargar página

2. **Permisos del sistema operativo**:
   - Windows: Configuración → Privacidad → Cámara → Permitir
   - Mac: Preferencias del Sistema → Seguridad → Cámara

3. **Verificar cámara**:
   - Probar en: https://webcamtests.com/
   - Asegurar que ninguna otra app esté usando la cámara

### "No se detectó rostro"

1. Verificar iluminación adecuada
2. Acercarse más a la cámara (50cm)
3. Mirar directamente a la cámara
4. Quitar accesorios que cubran el rostro
5. Intentar en lugar con fondo más neutro

### "Usuario no encontrado"

1. Verificar que estás registrado en el sistema
2. Contactar al administrador para verificar tus datos
3. Asegurar que tu registro biométrico esté completo

### Error al importar Excel

1. **Verificar formato**: Debe ser archivo `.xls` (no .xlsx)
2. **Columnas requeridas**: Documento, Nombres, Apellidos, Estado
3. **Codificación**: Guardar en UTF-8 si hay caracteres especiales
4. **Tamaño**: Archivos menores a 10MB

### No puedo iniciar sesión

1. **Verificar credenciales**:
   - Usuario = Número de documento (sin puntos ni espacios)
   - Contraseña = Asignada por administrador

2. **Restablecer contraseña**:
   - Contactar al administrador del sistema
   - Solicitar reset de contraseña

3. **Cuenta bloqueada**:
   - Esperar 30 minutos después de intentos fallidos
   - Contactar administrador si persiste

---

## 📞 Soporte Técnico

### Contacto

**Administrador del Sistema SenApre**  
📧 Email: soporte@senapre.com  
📱 Teléfono: [Número de contacto]  
🕐 Horario: Lunes a Viernes, 8:00 AM - 5:00 PM

### Reportar Problemas

Para reportar un error técnico, incluir:
1. Descripción detallada del problema
2. Captura de pantalla (si aplica)
3. Navegador y versión utilizada
4. Hora y fecha del incidente
5. Usuario afectado

---

## 🔒 Seguridad y Privacidad

### Protección de Datos Biométricos

- Los datos faciales se almacenan encriptados
- No se guardan fotos, solo características matemáticas (embeddings)
- Acceso restringido solo a usuarios autorizados
- Cumplimiento con políticas de privacidad del SENA

### Buenas Prácticas

- No compartir credenciales de acceso
- Cerrar sesión al terminar (especialmente en computadores públicos)
- Reportar inmediatamente actividad sospechosa
- Mantener actualizada información de contacto

---

## 📱 Compatibilidad de Dispositivos

### Navegadores Recomendados

| Navegador | Versión Mínima | Soporte Biométrico |
|-----------|---------------|-------------------|
| Chrome | 90+ | ✅ Completo |
| Firefox | 88+ | ✅ Completo |
| Edge | 90+ | ✅ Completo |
| Safari | 14+ | ✅ Completo |

### Dispositivos Móviles

- ✅ **Android**: Chrome actualizado, cámara frontal funcional
- ✅ **iOS**: Safari, iPhone 6s o superior
- ⚠️ **Tablets**: Funcional, pero recomendado para visualización

---

## 🎓 Glosario de Términos

| Término | Definición |
|---------|-----------|
| **Embedding** | Representación matemática única de un rostro |
| **Ficha** | Grupo de formación del SENA |
| **Lectiva** | Etapa de formación teórica/práctica |
| **Productiva** | Etapa de práctica empresarial |
| **Vocero** | Aprendiz representante de la ficha |
| **Biometría** | Identificación mediante características físicas |
| **Confianza** | Porcentaje de similitud en reconocimiento facial |

---

## 🚀 Actualizaciones y Novedades

### Versión 1.0 (Marzo 2026)

✅ Sistema biométrico facial integrado  
✅ Panel de instructor con toma de asistencia  
✅ Panel de liderazgo para reuniones  
✅ Importación masiva desde Excel  
✅ Reportes y estadísticas en tiempo real  

### Próximas Funcionalidades

🔜 Notificaciones push  
🔜 App móvil nativa  
🔜 Integración con sistemas SENA  
🔜 Reportes personalizados avanzados  

---

## ✍️ Créditos

**Desarrollado por**: Equipo SenApre  
**Institución**: Servicio Nacional de Aprendizaje (SENA)  
**Año**: 2026

---

**© 2026 SenApre - Sistema de Asistencias SENA. Todos los derechos reservados.**
