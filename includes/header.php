<?php
/**
 * CEMABLN - Header común con navegación lateral
 * Incluye Tailwind CSS y Chart.js via CDN.
 * Estilo: Military/Tech con soporte Dark/Light Mode.
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';

$currentUser = getCurrentUser();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$currentDir = basename(dirname($_SERVER['PHP_SELF']));
?>
<!DOCTYPE html>
<html lang="es" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="CEMABLN - Sistema de Gestión de Inventario y Trazabilidad para Blindados">
    <title><?= APP_NAME ?> - <?= APP_FULL_NAME ?></title>
    
    <!-- Tailwind CSS via CDN -->
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
    
    <!-- Chart.js via CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        * { font-family: 'Inter', sans-serif; }
        body { 
            background-color: #f8fafc; /* Light bg */
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        .dark body { 
            background-color: #0a1a0a; /* Dark bg */
        }
        
        /* Scrollbar personalizada */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        
        .dark ::-webkit-scrollbar-track { background: #131720; }
        .dark ::-webkit-scrollbar-thumb { background: #2d6b2d; }
        .dark ::-webkit-scrollbar-thumb:hover { background: #4a8c4a; }
        
        /* Animaciones */
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        .fade-in { animation: fadeIn 0.3s ease-out; }
        
        @keyframes pulse-glow { 0%, 100% { box-shadow: 0 0 5px rgba(45,107,45,0.3); } 50% { box-shadow: 0 0 20px rgba(45,107,45,0.6); } }
        .dark .pulse-glow { animation: pulse-glow 2s infinite; }
        .pulse-glow-light { animation: pulse-glow-light 2s infinite; }
        @keyframes pulse-glow-light { 0%, 100% { box-shadow: 0 0 5px rgba(74,140,74,0.3); } 50% { box-shadow: 0 0 15px rgba(74,140,74,0.5); } }
        
        /* Sidebar active link */
        .nav-link { transition: all 0.2s ease; border-left: 3px solid transparent; }
        .nav-link:hover { background: rgba(0,0,0,0.05); border-left-color: #4a8c4a; color: #1f2937; }
        .nav-link.active { background: rgba(45,107,45,0.1); border-left-color: #2d6b2d; color: #2d6b2d; font-weight: 600; }
        
        .dark .nav-link:hover { background: rgba(45,107,45,0.2); border-left-color: #4a8c4a; color: #e5e7eb; }
        .dark .nav-link.active { background: rgba(45,107,45,0.3); border-left-color: #2d6b2d; color: #7aad7a; font-weight: normal; }
    </style>
</head>
<body class="text-slate-800 dark:text-gray-300 min-h-screen">

<?php if ($currentUser): ?>
<!-- ── Layout con Sidebar ─────────────────────────────────────────────── -->
<div class="flex min-h-screen">
    
    <!-- Sidebar -->
    <aside id="sidebar" class="w-64 bg-white dark:bg-steel-950 border-r border-slate-200 dark:border-steel-800 flex flex-col fixed h-full z-30 transition-all duration-300 lg:translate-x-0 -translate-x-full shadow-lg lg:shadow-none">
        
        <!-- Logo / Brand -->
        <div class="p-4 border-b border-slate-200 dark:border-steel-800">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-military-100 dark:bg-military-700 rounded-lg flex items-center justify-center pulse-glow-light dark:pulse-glow">
                    <span class="text-xl">🛡️</span>
                </div>
                <div>
                    <h1 class="text-sm font-bold text-military-700 dark:text-military-300 tracking-wider"><?= APP_NAME ?></h1>
                    <p class="text-[10px] text-slate-500 dark:text-steel-400 uppercase tracking-widest">Control de Blindados</p>
                </div>
            </div>
        </div>
        
        <!-- Navigation -->
        <nav class="flex-1 py-4 overflow-y-auto">
            <div class="px-3 mb-2"><span class="text-[10px] font-bold text-slate-400 dark:text-steel-500 uppercase tracking-widest">Principal</span></div>
            
            <a href="<?= BASE_URL ?>/modules/dashboard/index.php" class="nav-link flex items-center gap-3 px-4 py-2.5 text-sm text-slate-600 dark:text-gray-300 <?= $currentDir === 'dashboard' ? 'active' : '' ?>">
                <span>📊</span> Panel de control
            </a>
            
            <a href="<?= BASE_URL ?>/modules/inventario/index.php" class="nav-link flex items-center gap-3 px-4 py-2.5 text-sm text-slate-600 dark:text-gray-300 <?= $currentDir === 'inventario' ? 'active' : '' ?>">
                <span>📦</span> Inventario
            </a>
            
            <a href="<?= BASE_URL ?>/modules/movimientos/index.php" class="nav-link flex items-center gap-3 px-4 py-2.5 text-sm text-slate-600 dark:text-gray-300 <?= $currentDir === 'movimientos' ? 'active' : '' ?>">
                <span>🔄</span> Movimientos
            </a>
            
            <div class="px-3 mt-4 mb-2"><span class="text-[10px] font-bold text-slate-400 dark:text-steel-500 uppercase tracking-widest">Operaciones</span></div>
            
            <a href="<?= BASE_URL ?>/modules/movimientos/entrada.php" class="nav-link flex items-center gap-3 px-4 py-2.5 text-sm text-slate-600 dark:text-gray-300">
                <span>📥</span> Registrar Entrada
            </a>
            
            <a href="<?= BASE_URL ?>/modules/movimientos/salida.php" class="nav-link flex items-center gap-3 px-4 py-2.5 text-sm text-slate-600 dark:text-gray-300">
                <span>📤</span> Despachar Salida
            </a>
            
            <a href="<?= BASE_URL ?>/modules/reportes/index.php" class="nav-link flex items-center gap-3 px-4 py-2.5 text-sm text-slate-600 dark:text-gray-300 <?= $currentDir === 'reportes' ? 'active' : '' ?>">
                <span>📋</span> Reportes
            </a>

            <?php if (hasRole(ROLE_ADMINISTRADOR)): ?>
            <div class="px-3 mt-4 mb-2"><span class="text-[10px] font-bold text-slate-400 dark:text-steel-500 uppercase tracking-widest">Administración</span></div>
            
            <a href="<?= BASE_URL ?>/modules/usuarios/index.php" class="nav-link flex items-center gap-3 px-4 py-2.5 text-sm text-slate-600 dark:text-gray-300 <?= $currentDir === 'usuarios' ? 'active' : '' ?>">
                <span>👥</span> Usuarios
            </a>
            
            <a href="<?= BASE_URL ?>/modules/vehiculos/index.php" class="nav-link flex items-center gap-3 px-4 py-2.5 text-sm text-slate-600 dark:text-gray-300 <?= $currentDir === 'vehiculos' ? 'active' : '' ?>">
                <span>🚛</span> Vehículos
            </a>
            <?php endif; ?>
            
            <?php if (hasRole(ROLE_SUPERADMIN)): ?>
            <a href="<?= BASE_URL ?>/modules/auditoria/index.php" class="nav-link flex items-center gap-3 px-4 py-2.5 text-sm text-slate-600 dark:text-gray-300 <?= $currentDir === 'auditoria' ? 'active' : '' ?>">
                <span>🔍</span> Auditoría
            </a>
            <?php endif; ?>
        </nav>
        
        <!-- User Info -->
        <div class="p-4 border-t border-slate-200 dark:border-steel-800 bg-slate-50 dark:bg-transparent">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 bg-military-200 dark:bg-military-700 rounded-full flex items-center justify-center text-xs font-bold text-military-800 dark:text-military-300 shadow-inner">
                    <?= strtoupper(substr($currentUser['nombre'], 0, 2)) ?>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-bold text-slate-700 dark:text-gray-300 truncate"><?= sanitize($currentUser['grado'] . ' ' . $currentUser['nombre']) ?></p>
                    <p class="text-[10px] text-military-600 dark:text-military-400 font-medium"><?= sanitize($currentUser['rol_name']) ?></p>
                </div>
                <a href="<?= BASE_URL ?>/logout.php" class="text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 text-sm transition-colors" title="Cerrar Sesión">⏻</a>
            </div>
        </div>
    </aside>
    
    <!-- Main Content -->
    <main class="flex-1 lg:ml-64 flex flex-col min-h-screen transition-all duration-300">
        <!-- Top Bar -->
        <header class="bg-white/80 dark:bg-steel-950/80 backdrop-blur border-b border-slate-200 dark:border-steel-800 sticky top-0 z-20 transition-colors duration-300">
            <div class="flex items-center justify-between px-6 py-3">
                <div class="flex items-center gap-4">
                    <button onclick="document.getElementById('sidebar').classList.toggle('-translate-x-full')" class="lg:hidden text-slate-500 hover:text-slate-800 dark:text-gray-400 dark:hover:text-white transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                    <div class="hidden sm:block text-sm font-medium text-slate-500 dark:text-steel-400">
                        <?= date('d/m/Y H:i') ?> | <span class="text-military-600 dark:text-military-400 font-bold"><?= sanitize($currentUser['rol_name']) ?></span>
                    </div>
                </div>
                
                <div class="flex items-center gap-4">
                    <!-- Theme Toggle -->
                    <button id="theme-toggle-header" type="button" class="text-slate-500 dark:text-gray-400 hover:bg-slate-100 dark:hover:bg-steel-800 focus:outline-none rounded-lg text-sm p-2 transition-colors">
                        <svg id="theme-toggle-dark-icon-header" class="hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path></svg>
                        <svg id="theme-toggle-light-icon-header" class="hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" fill-rule="evenodd" clip-rule="evenodd"></path></svg>
                    </button>

                    <?php
                    $alertCount = count(getProductosStockBajo()) + count(getProductosProximosVencer());
                    if ($alertCount > 0):
                    ?>
                    <a href="<?= BASE_URL ?>/modules/inventario/index.php?filter=alerts" class="relative text-slate-600 dark:text-gray-300 hover:text-military-600 dark:hover:text-military-400 transition-colors">
                        <span class="text-xl">🔔</span>
                        <span class="absolute -top-1 -right-1 w-4 h-4 bg-red-600 text-white text-[9px] rounded-full flex items-center justify-center font-bold border-2 border-white dark:border-steel-950 shadow-sm"><?= $alertCount ?></span>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </header>
        
        <!-- Page Content -->
        <div class="p-6 fade-in flex-1">
            <?= renderFlash() ?>
<?php endif; ?>

