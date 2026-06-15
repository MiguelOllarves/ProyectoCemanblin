<?php
/**
 * CEMANBLIND - Página de Login
 * Punto de entrada principal del sistema.
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

initSession();

// Si ya está autenticado, redirigir al dashboard
if (isLoggedIn()) {
    redirect(BASE_URL . '/modules/dashboard/index.php');
}

// Procesar formulario de login
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cedula = $_POST['cedula'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($cedula) || empty($password)) {
        $error = 'Debe ingresar cédula y contraseña.';
    } else {
        $result = loginUser($cedula, $password);
        if ($result['success']) {
            redirect(BASE_URL . '/modules/dashboard/index.php');
        } else {
            $error = $result['message'];
        }
    }
}

// Mensaje de sesión expirada
if (isset($_GET['error']) && $_GET['error'] === 'session_expired') {
    $error = 'Su sesión ha expirado. Por favor, inicie sesión nuevamente.';
}
?>
<!DOCTYPE html>
<html lang="es" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?> - Acceso al Sistema</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        military: {
                            50:  '#f0f5f0', 100: '#d9e8d9', 200: '#b3d1b3', 300: '#7aad7a',
                            400: '#4a8c4a', 500: '#2d6b2d', 600: '#1f4f1f', 700: '#163916',
                            800: '#0f280f', 900: '#0a1a0a', 950: '#050d05',
                        },
                        steel: {
                            50:  '#f5f6f8', 100: '#e1e4e9', 200: '#c3c9d3', 300: '#9da5b3',
                            400: '#7a8494', 500: '#5f6878', 600: '#4b5363', 700: '#3d4452',
                            800: '#2d3340', 900: '#1e2330', 950: '#131720',
                        }
                    }
                }
            }
        }
        
        // Theme initialization
        if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        
        /* Dynamic Backgrounds */
        body {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            transition: background-color 0.5s ease, color 0.5s ease;
        }
        .dark body {
            background: linear-gradient(135deg, #050d05 0%, #0a1a0a 30%, #131720 70%, #0f280f 100%);
        }
        
        /* Grid pattern */
        .bg-grid {
            background-image: radial-gradient(circle, #cbd5e1 1px, transparent 1px);
            background-size: 30px 30px;
        }
        .dark .bg-grid {
            background-image: radial-gradient(circle, #2d6b2d 1px, transparent 1px);
        }

        /* Animations */
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        .fade-up { animation: fadeInUp 0.6s ease-out forwards; }
        
        @keyframes pulse-border { 0%, 100% { border-color: rgba(45,107,45,0.3); } 50% { border-color: rgba(45,107,45,0.8); } }
        .dark .pulse-border { animation: pulse-border 3s infinite; }
        .pulse-border-light { animation: pulse-border-light 3s infinite; }
        @keyframes pulse-border-light { 0%, 100% { border-color: rgba(74,140,74,0.3); } 50% { border-color: rgba(74,140,74,0.8); } }
        
        @keyframes scanline { 0% { transform: translateY(-100%); } 100% { transform: translateY(100%); } }
        .dark .scanline::after {
            content: ''; position: absolute; top: 0; left: 0; right: 0; height: 2px;
            background: linear-gradient(90deg, transparent, rgba(45,107,45,0.4), transparent);
            animation: scanline 4s linear infinite; pointer-events: none;
        }
        
        /* Glassmorphism */
        .glass-card {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.05);
        }
        .dark .glass-card {
            background: rgba(19, 23, 32, 0.8);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
        }
        
        /* Autofill fixes */
        input:-webkit-autofill {
            -webkit-box-shadow: 0 0 0 30px #f8fafc inset !important;
            -webkit-text-fill-color: #1e293b !important;
        }
        .dark input:-webkit-autofill {
            -webkit-box-shadow: 0 0 0 30px #1e2330 inset !important;
            -webkit-text-fill-color: #e5e7eb !important;
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 text-slate-800 dark:text-gray-200">

    <!-- Background grid pattern -->
    <div class="fixed inset-0 opacity-40 dark:opacity-5 bg-grid transition-opacity duration-500"></div>

    <!-- Theme Toggle Button -->
    <div class="fixed top-6 right-6 z-50">
        <button id="theme-toggle" type="button" class="text-gray-500 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-steel-800 focus:outline-none focus:ring-2 focus:ring-military-500 rounded-full text-sm p-3 transition-colors glass-card">
            <svg id="theme-toggle-dark-icon" class="hidden w-6 h-6" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path></svg>
            <svg id="theme-toggle-light-icon" class="hidden w-6 h-6" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" fill-rule="evenodd" clip-rule="evenodd"></path></svg>
        </button>
    </div>

    <div class="w-full max-w-md fade-up relative z-10">
        
        <!-- Logo & Title -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-white dark:bg-military-900/50 border-2 border-military-400 dark:border-military-600 rounded-2xl mb-4 shadow-lg pulse-border-light dark:pulse-border transition-all duration-500">
                <span class="text-4xl">🛡️</span>
            </div>
            <h1 class="text-3xl font-extrabold text-slate-800 dark:text-military-300 tracking-tight transition-colors"><?= APP_NAME ?></h1>
            <p class="text-xs font-semibold text-slate-500 dark:text-gray-400 uppercase tracking-[0.25em] mt-2 transition-colors"><?= APP_FULL_NAME ?></p>
            <p class="text-[11px] text-slate-400 dark:text-gray-500 mt-2 font-medium transition-colors">Sistema de Gestión y Trazabilidad v<?= APP_VERSION ?></p>
        </div>
        
        <!-- Login Form -->
        <div class="glass-card rounded-2xl p-8 relative overflow-hidden scanline transition-all duration-500">
            
            <h2 class="text-xl font-bold text-slate-800 dark:text-gray-100 mb-6 text-center transition-colors">Acceso al Panel</h2>
            
            <?php if ($error): ?>
            <div class="bg-red-50 dark:bg-red-900/40 border border-red-200 dark:border-red-500/50 text-red-600 dark:text-red-300 rounded-lg p-3 mb-5 text-sm flex items-center gap-3 transition-colors">
                <span class="text-lg">⚠️</span> 
                <span class="font-medium"><?= sanitize($error) ?></span>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="" id="loginForm">
                <!-- Cédula -->
                <div class="mb-5">
                    <label for="cedula" class="block text-xs font-bold text-slate-600 dark:text-gray-400 uppercase tracking-wider mb-2 transition-colors">
                        Número de Cédula
                    </label>
                    <div class="relative group">
                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 dark:text-gray-500 group-focus-within:text-military-500 transition-colors">👤</span>
                        <input 
                            type="text" 
                            id="cedula" 
                            name="cedula" 
                            required
                            autocomplete="username"
                            placeholder="Ej: 12345678"
                            value="<?= sanitize($_POST['cedula'] ?? '') ?>"
                            class="w-full bg-white dark:bg-steel-900/50 border border-slate-200 dark:border-steel-700 rounded-xl pl-11 pr-4 py-3.5 text-slate-800 dark:text-gray-200 placeholder-slate-400 dark:placeholder-gray-600 focus:outline-none focus:border-military-500 focus:ring-2 focus:ring-military-500/20 transition-all duration-300 shadow-sm"
                        >
                    </div>
                </div>
                
                <!-- Contraseña -->
                <div class="mb-8">
                    <label for="password" class="block text-xs font-bold text-slate-600 dark:text-gray-400 uppercase tracking-wider mb-2 transition-colors">
                        Contraseña
                    </label>
                    <div class="relative group">
                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 dark:text-gray-500 group-focus-within:text-military-500 transition-colors">🔒</span>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            required
                            autocomplete="current-password"
                            placeholder="••••••••"
                            class="w-full bg-white dark:bg-steel-900/50 border border-slate-200 dark:border-steel-700 rounded-xl pl-11 pr-12 py-3.5 text-slate-800 dark:text-gray-200 placeholder-slate-400 dark:placeholder-gray-600 focus:outline-none focus:border-military-500 focus:ring-2 focus:ring-military-500/20 transition-all duration-300 shadow-sm"
                        >
                        <button type="button" id="togglePassword" class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:text-gray-500 dark:hover:text-gray-300 transition-colors focus:outline-none">
                            <svg id="eyeIcon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </button>
                    </div>
                </div>
                
                <!-- Submit -->
                <button 
                    type="submit" 
                    class="w-full bg-military-600 hover:bg-military-500 dark:bg-military-700 dark:hover:bg-military-600 text-white font-bold py-3.5 rounded-xl transition-all duration-300 uppercase tracking-widest text-sm shadow-lg shadow-military-600/20 dark:shadow-military-900/40 hover:shadow-military-600/40 active:scale-[0.98]"
                >
                    Ingresar al Sistema
                </button>
            </form>
        </div>
        
        <!-- Footer -->
        <div class="text-center mt-8">
            <p class="text-[11px] text-slate-500 dark:text-gray-600 font-medium transition-colors">
                Sistema clasificado — Acceso restringido a personal autorizado<br>
                <span class="mt-1 block">&copy; <?= date('Y') ?> <?= APP_NAME ?></span>
            </p>
        </div>
    </div>

    <script>
        // Theme toggle logic
        var themeToggleDarkIcon = document.getElementById('theme-toggle-dark-icon');
        var themeToggleLightIcon = document.getElementById('theme-toggle-light-icon');

        // Change the icons inside the button based on previous settings
        if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            themeToggleLightIcon.classList.remove('hidden');
        } else {
            themeToggleDarkIcon.classList.remove('hidden');
        }

        var themeToggleBtn = document.getElementById('theme-toggle');

        themeToggleBtn.addEventListener('click', function() {
            // toggle icons inside button
            themeToggleDarkIcon.classList.toggle('hidden');
            themeToggleLightIcon.classList.toggle('hidden');

            // if set via local storage previously
            if (localStorage.getItem('color-theme')) {
                if (localStorage.getItem('color-theme') === 'light') {
                    document.documentElement.classList.add('dark');
                    localStorage.setItem('color-theme', 'dark');
                } else {
                    document.documentElement.classList.remove('dark');
                    localStorage.setItem('color-theme', 'light');
                }
            // if NOT set via local storage previously
            } else {
                if (document.documentElement.classList.contains('dark')) {
                    document.documentElement.classList.remove('dark');
                    localStorage.setItem('color-theme', 'light');
                } else {
                    document.documentElement.classList.add('dark');
                    localStorage.setItem('color-theme', 'dark');
                }
            }
        });

        // Toggle Password Visibility
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        const eyeIcon = document.getElementById('eyeIcon');

        if (togglePassword && passwordInput) {
            togglePassword.addEventListener('click', function () {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                
                if (type === 'text') {
                    // Cambiar icono a ojo tachado
                    eyeIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />';
                } else {
                    // Cambiar icono a ojo normal
                    eyeIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />';
                }
            });
        }
    </script>
</body>
</html>
