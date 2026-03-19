# 🚀 PREPARACIÓN PARA NUEVO DEPLOY EN RENDER

## 📋 PASOS A SEGUIR

### 1. ✅ PROYECTO LOCAL LISTO
- **Ruta:** `C:\wamp64\www\YanguasEjercicios\senapre`
- **Estado:** Completo y funcional
- **Características:** Todas implementadas

### 2. 🌐 CREAR NUEVA CUENTA RENDER
1. Ir a https://render.com
2. Crear cuenta nueva (email diferente)
3. Verificar correo
4. Listo para deploy

### 3. 📤 SUBIR PROYECTO
**Opción A: GitHub (Recomendada)**
```bash
git init
git add .
git commit -m "SenApre listo para produccion"
git remote add origin [URL_REPO_NUEVA]
git push -u origin main
```

**Opción B: Direct upload**
- Comprimir carpeta `senapre`
- Subir directamente a Render

### 4. 🔧 CONFIGURACIÓN EN RENDER
1. **Service Type:** Web Service
2. **Build Command:** `npm install`
3. **Start Command:** `php -S localhost:3000`
4. **Environment Variables:**
   - `DATABASE_URL`: [pegar URL de Supabase]

### 5. 🗄️ CONFIGURAR SUPABASE
1. Usar tu cuenta: `https://blbbixrdmwhjywhlqjwx.supabase.co`
2. Obtener DATABASE_URL
3. Configurar en Render

---

## 🎯 ARCHIVOS CLAVE PARA DEPLOY

### ✅ ESTOS ARCHIVOS ESTÁN LISTOS:
- `index.html` - Login principal
- `admin-dashboard.html` - Panel admin
- `instructor-dashboard.html` - Panel instructor
- `api/` - Todos los endpoints PHP
- `js/auth.js` - Autenticación
- `css/` - Todos los estilos
- `render.yaml` - Configuración deploy

### 🔧 CONFIGURACIÓN BASE DE DATOS:
- Archivo: `.env.local`
- Contiene URL de Supabase
- Se configura en Environment Variables

---

## 🚀 DEPLOY AUTOMÁTICO

### Una vez configurado:
1. **Push a GitHub** → Deploy automático
2. **URL pública**: `https://tu-nombre.render.com`
3. **Sistema funcionando** en 2-3 minutos

---

## 📱 ACCESO AL SISTEMA

### Credenciales de prueba:
```
👨‍💼 Administrador:
   Correo: admin@sena.edu.co
   Contraseña: admin123

👨‍🏫 Instructor:
   Correo: instructor@sena.edu.co
   Contraseña: 123456
```

---

## ⚠️ NOTAS IMPORTANTES

1. **NO usar la cuenta vieja de Render**
2. **CREAR cuenta completamente nueva**
3. **Usar email diferente si es necesario**
4. **Configurar desde cero**

---

## 🎉 RESULTADO ESPERADO

### ✅ Cuando termine:
- Sistema funcionando en producción
- URL pública accesible
- Base de datos conectada
- Todos los paneles operativos
- Usuarios pueden ingresar

---

## 📞 SI NECESITAS AYUDA

1. **Crear cuenta Render**
2. **Subir proyecto**
3. **Configurar DATABASE_URL**
4. **¡LISTO!**

**El proyecto está 100% listo para producción** 🚀
