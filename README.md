# Centro de Mantenimiento de Blindados (CEMABLN)

Prototipo funcional, modular y profesional para la gestión de inventario y trazabilidad de repuestos para vehículos blindados.

## 🚀 Características Principales

*   **Arquitectura Ligera**: PHP 8.x puro sin frameworks pesados, ideal para servidores con recursos limitados o despliegues rápidos.
*   **Base de Datos Integrada**: Utiliza SQLite3 vía PDO, no requiere instalación de motores de base de datos externos (MySQL/PostgreSQL).
*   **Seguridad y Control de Acceso (RBAC)**: Roles definidos (Usuario, Supervisor, Administrador, Superadmin).
*   **Trazabilidad y Auditoría**: Registro inmutable de cada acción en el sistema (`logs_auditoria`) y trazabilidad obligatoria de repuestos (`movimientos` asociados a vehículos).
*   **Interfaz Moderna**: Diseño estilo "Military/Tech" utilizando Tailwind CSS (vía CDN) y gráficos interactivos con Chart.js.

## 📋 Requisitos del Sistema

*   PHP 8.0 o superior.
*   Extensión PDO de PHP (`pdo_sqlite`) habilitada.

## 🛠️ Instalación y Ejecución

### Opción 1: Usando el servidor integrado de PHP (Recomendado para pruebas)

1.  Abre una terminal y navega hasta la carpeta raíz del proyecto.
2.  Ejecuta el siguiente comando para iniciar el servidor local:
    ```bash
    php -S localhost:8080
    ```
3.  Abre tu navegador web y visita: `http://localhost:8080/index.php`

### Opción 2: Usando XAMPP, WAMP o LAMP

1.  Copia la carpeta completa del proyecto dentro del directorio de publicación web de tu servidor (`htdocs` en XAMPP, `/var/www/html` en Apache/Linux).
2.  Asegúrate de que el servidor web tenga permisos de escritura en la carpeta `/database` para que SQLite pueda crear y actualizar el archivo de la base de datos (`cemabln.sqlite`).
3.  Si la URL de tu proyecto no es la raíz (ej. `http://localhost/ProyectoNavas`), debes ajustar la constante `BASE_URL` en el archivo `/config/app.php` a la URL correspondiente.
    ```php
    // En config/app.php
    define('BASE_URL', 'http://localhost/ProyectoNavas');
    ```

## 🔐 Credenciales de Acceso Iniciales

Al ejecutar el sistema por primera vez, la base de datos se inicializará automáticamente y se creará un usuario Superadmin por defecto:

*   **Cédula (Login):** `00000000`
*   **Contraseña:** `admin123`

> ⚠️ **IMPORTANTE:** Por razones de seguridad, ingresa al sistema y cambia esta contraseña o crea un nuevo usuario Superadmin inmediatamente.

## 📂 Estructura de Directorios

```text
/
├── assets/             # Recursos estáticos (CSS, JS, Imágenes)
├── config/             # Configuración global de la aplicación (app.php)
├── database/           # Base de datos SQLite y scripts SQL
├── includes/           # Archivos comunes (auth.php, audit.php, header.php, etc.)
├── modules/            # Módulos del sistema
│   ├── auditoria/      # Visor de logs inmutables (Solo Superadmin)
│   ├── dashboard/      # Panel de control principal con gráficos y alertas
│   ├── inventario/     # CRUD de productos y alertas de stock
│   ├── movimientos/    # Trazabilidad: Entradas, salidas y vales
│   ├── reportes/       # Exportación de datos a CSV
│   ├── usuarios/       # Gestión de roles y accesos
│   └── vehiculos/      # Gestión de la flota de blindados
├── index.php           # Página de inicio de sesión
└── logout.php          # Script para cerrar sesión
```

## 🛡️ Detalles de Seguridad

*   **Contraseñas:** Almacenadas usando `password_hash()` con el algoritmo BCRYPT.
*   **Inyección SQL:** Prevenida mediante el uso exclusivo de sentencias preparadas (Prepared Statements) con PDO.
*   **XSS (Cross-Site Scripting):** Mitigado mediante la sanitización de salidas usando `htmlspecialchars()` (función `sanitize()` en `helpers.php`).
*   **CSRF (Cross-Site Request Forgery):** Los formularios críticos incluyen tokens CSRF validados en el backend.
*   **Sesiones:** Protección contra fijación de sesiones regenerando el ID en el login, y control de inactividad automática.
