# Sistema de Activistas Digitales

Este es un sistema completo de gestión de activistas digitales desarrollado en PHP puro (sin framework) que cumple con todos los requisitos especificados.

## ✅ Características Implementadas

### 🔐 Sistema de Autenticación
- Login seguro con hash de contraseñas
- Registro público para Líder y Activista
- Verificación de fortaleza de contraseñas
- Protección CSRF en todos los formularios
- Gestión de sesiones segura

### 👥 Gestión de Usuarios (4 Roles)
- **SuperAdmin**: Control total del sistema
- **Gestor**: Administración de usuarios y validación
- **Líder**: Gestión de equipo y actividades
- **Activista**: Registro de actividades propias

### 📊 Dashboards Diferenciados
- **SuperAdmin Dashboard**: Métricas globales, gráficas, ranking de equipos
- **Gestor Dashboard**: Similar a SuperAdmin sin configuración crítica
- **Líder Dashboard**: Actividades del equipo, métricas por miembro
- **Activista Dashboard**: Actividades personales, información del equipo

### 📝 Sistema de Actividades
- Registro completo de actividades
- Tipos configurables (Redes Sociales, Eventos, Capacitación, etc.)
- Estados de actividad (Programada, En Progreso, Completada, Cancelada)
- Sistema de evidencias (fotos, videos, comentarios)
- Métricas de alcance

### 🛡️ Seguridad Implementada
- Validación y sanitización de datos
- Tokens CSRF
- Hash seguro de contraseñas (password_hash)
- Validación de permisos por roles
- Logging de actividades del sistema

## 🗂️ Estructura del Proyecto

```
activistas_digitales/
├── config/
│   └── database.php           # Configuración de base de datos
├── controllers/
│   ├── userController.php     # Lógica de usuarios
│   ├── dashboardController.php # Lógica de dashboards
│   └── activityController.php  # Lógica de actividades
├── models/
│   ├── user.php              # Modelo de Usuario
│   └── activity.php          # Modelo de Actividades
├── views/
│   └── dashboards/           # Vistas de dashboards
│       ├── admin.php
│       ├── gestor.php
│       ├── lider.php
│       └── activista.php
├── public/
│   ├── index.php             # Punto de entrada principal
│   ├── login.php             # Formulario de login
│   ├── register.php          # Formulario de registro
│   └── assets/               # Recursos estáticos
├── includes/
│   ├── auth.php              # Sistema de autenticación
│   └── functions.php         # Funciones auxiliares
├── database.sql              # Schema de base de datos
└── README.md                 # Este archivo
```

## 🚀 Instalación y Configuración

### 1. Requisitos del Sistema
- PHP 8.2 o superior
- MySQL 5.7 o 8.0
- Servidor web (Apache/Nginx)

### 2. Configuración de Base de Datos
1. Crear base de datos MySQL
2. Ejecutar el script `database.sql`
3. Configurar credenciales en `config/database.php`

### 3. Configuración del Servidor
- Configurar DocumentRoot hacia la carpeta `public/`
- Habilitar mod_rewrite si usa Apache
- Configurar permisos de escritura en `public/assets/uploads/`

### 4. Usuario por Defecto
- **Email**: admin@activistas.com
- **Contraseña**: password
- **Rol**: SuperAdmin

## 🎯 Funcionalidades por Rol

### SuperAdmin
- ✅ Vista global de métricas del sistema
- ✅ Gestión completa de usuarios
- ✅ Aprobación/rechazo de registros
- ✅ Acceso a todas las actividades
- ✅ Exportación de datos (preparado)
- ✅ Configuración del sistema

### Gestor
- ✅ Gestión de usuarios activos
- ✅ Validación de registros
- ✅ Seguimiento de equipos
- ✅ Métricas y reportes

### Líder
- ✅ Gestión de su equipo de activistas
- ✅ Registro de actividades propias y supervisión del equipo
- ✅ Métricas de participación por miembro
- ✅ Carga de evidencias

### Activista
- ✅ Registro de actividades personales
- ✅ Visualización de historial y desempeño
- ✅ Carga de evidencias
- ✅ Vista de equipo y líder asignado

## 🔧 Tecnologías Utilizadas

- **Backend**: PHP 8.2 (puro, sin framework)
- **Base de Datos**: MySQL con claves foráneas y vistas
- **Frontend**: HTML5, CSS3, JavaScript
- **UI Framework**: Bootstrap 5
- **Gráficas**: Chart.js
- **Iconos**: Font Awesome 6
- **Seguridad**: password_hash(), CSRF tokens, sanitización

## 📈 Características Avanzadas

### Sistema de Métricas
- Conteo de actividades por tipo
- Métricas de alcance estimado
- Ranking de equipos más activos
- Estadísticas por usuario y rol

### Gestión de Archivos
- Subida segura de fotos de perfil
- Sistema de evidencias multimedia
- Validación de tipos y tamaños de archivo

### Sistema de Notificaciones
- Mensajes flash para feedback al usuario
- Logging de actividades del sistema
- Estructura preparada para notificaciones por email

## 🛠️ Próximas Mejoras

- [ ] Calendario interactivo con FullCalendar.js
- [ ] Exportación real a PDF y Excel
- [ ] Sistema completo de notificaciones por email
- [ ] Geolocalización de actividades
- [ ] Dashboard con mapas interactivos
- [ ] API REST para integración móvil

## 🔐 Notas de Seguridad

El sistema implementa múltiples capas de seguridad:
- Autenticación robusta con sesiones seguras
- Validación exhaustiva de entrada de datos
- Protección contra CSRF, XSS y SQL Injection
- Permisos granulares por rol
- Logging completo de actividades

## 📞 Soporte

Sistema desarrollado como MVP funcional que cumple con todos los requisitos especificados. Listo para producción con las configuraciones adecuadas de servidor y base de datos.
