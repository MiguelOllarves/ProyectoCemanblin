<?php
/**
 * Entrypoint para despliegues Serverless en Vercel.
 * Redirige todas las peticiones al index.php principal del proyecto.
 */

// Se carga el index.php de la raíz, que probablemente inicie el MVC / Front Controller
require __DIR__ . '/../index.php';
