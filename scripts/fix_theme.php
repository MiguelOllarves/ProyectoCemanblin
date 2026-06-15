<?php
$dir = '/home/maom/Proyectos/ProyectoNavas/modules';
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));

$replacements = [
    // Backgrounds
    'bg-steel-950' => 'bg-white dark:bg-steel-950',
    'bg-steel-900/80' => 'bg-slate-100 dark:bg-steel-900/80',
    'bg-steel-900/50' => 'bg-white dark:bg-steel-900/50',
    'bg-steel-900' => 'bg-slate-50 dark:bg-steel-900',
    'bg-steel-800/50' => 'bg-slate-50 dark:bg-steel-800/50',
    'bg-steel-800/30' => 'bg-slate-50/50 dark:bg-steel-800/30',
    'bg-steel-800' => 'bg-white dark:bg-steel-800',
    'bg-steel-700' => 'bg-slate-200 dark:bg-steel-700',
    'bg-steel-600' => 'bg-slate-300 dark:bg-steel-600',
    'bg-military-900/50' => 'bg-military-50 dark:bg-military-900/50',
    'bg-red-900/20' => 'bg-white dark:bg-red-900/20',
    'bg-orange-900/20' => 'bg-white dark:bg-orange-900/20',
    'bg-green-900/40' => 'bg-emerald-100 dark:bg-green-900/40',
    'bg-orange-900/40' => 'bg-orange-100 dark:bg-orange-900/40',
    'bg-blue-900/40' => 'bg-blue-100 dark:bg-blue-900/40',

    // Borders
    'border-steel-800/50' => 'border-slate-100 dark:border-steel-800/50',
    'border-steel-800' => 'border-slate-200 dark:border-steel-800',
    'border-steel-700/50' => 'border-slate-200 dark:border-steel-700/50',
    'border-steel-700' => 'border-slate-300 dark:border-steel-700',
    'border-military-600' => 'border-military-300 dark:border-military-600',
    'border-military-500' => 'border-military-500', // Usually for focus, keep it
    'border-red-500/50' => 'border-red-300 dark:border-red-500/50',

    // Texts
    'text-gray-100' => 'text-slate-800 dark:text-gray-100',
    'text-gray-200' => 'text-slate-800 dark:text-gray-200',
    'text-gray-300' => 'text-slate-700 dark:text-gray-300',
    'text-gray-400' => 'text-slate-500 dark:text-gray-400',
    'text-gray-500' => 'text-slate-500 dark:text-gray-500',
    'text-gray-600' => 'text-slate-400 dark:text-gray-600',
    'text-military-400' => 'text-military-600 dark:text-military-400',
    'text-red-400' => 'text-red-600 dark:text-red-400',
    'text-orange-400' => 'text-orange-600 dark:text-orange-400',
    'text-green-400' => 'text-emerald-600 dark:text-green-400',
    'text-blue-400' => 'text-blue-600 dark:text-blue-400',
];

// Helper to avoid double replacing (e.g. replacing 'bg-steel-800' when it's already 'dark:bg-steel-800')
foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php' && strpos($file->getPathname(), 'dashboard') === false) {
        $content = file_get_contents($file->getPathname());
        $original = $content;
        
        foreach ($replacements as $find => $replace) {
            // Use regex to replace only if it's not already prefixed with 'dark:'
            $content = preg_replace('/(?<!dark:)(?<!\w)' . preg_quote($find, '/') . '(?!\/\d+)(?!\w)/', $replace, $content);
        }

        // Fix potential double replacements for text-slate-800 dark:text-slate-800 etc (if they happened)
        // Add transition classes where bg-white is used
        $content = preg_replace('/(bg-white dark:bg-steel-[^\s"]+)/', '$1 transition-colors', $content);
        $content = preg_replace('/(text-slate-[^\s"]+ dark:text-gray-[^\s"]+)/', '$1 transition-colors', $content);

        if ($original !== $content) {
            file_put_contents($file->getPathname(), $content);
            echo "Updated " . $file->getPathname() . "\n";
        }
    }
}
echo "Done.\n";
