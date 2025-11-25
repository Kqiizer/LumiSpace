<?php

/**
 * Detecta la ruta base pública del proyecto considerando distintos entornos.
 *
 * Prioriza la variable de entorno BASE_URL. Si no está definida, intenta
 * calcular la ruta relativa entre el DOCUMENT_ROOT expuesto por el servidor
 * web y el directorio real del proyecto. Como último recurso devuelve "/".
 */
if (!function_exists('ls_detect_base_url')) {
    function ls_detect_base_url(): string
    {
        $envBase = getenv('BASE_URL');
        if ($envBase !== false && trim($envBase) !== '') {
            return rtrim($envBase, '/') . '/';
        }

        $projectRoot = dirname(__DIR__); // Directorio raíz del proyecto
        $docRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
        $docRootReal = $docRoot !== '' ? realpath($docRoot) : false;
        $projectReal = realpath($projectRoot);

        if ($docRootReal && $projectReal) {
            $docRootNorm = rtrim(str_replace('\\', '/', $docRootReal), '/');
            $projectNorm = rtrim(str_replace('\\', '/', $projectReal), '/');

            if (strpos($projectNorm, $docRootNorm) === 0) {
                $relative = trim(substr($projectNorm, strlen($docRootNorm)), '/');
                if ($relative !== '') {
                    return '/' . $relative . '/';
                }
                return '/';
            }
        }

        // Fallback para contextos CLI donde DOCUMENT_ROOT puede no existir.
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        if ($scriptName !== '') {
            $segments = explode('/', trim($scriptName, '/'));
            if (count($segments) > 1) {
                $firstSegment = $segments[0];
                if ($firstSegment !== '' && $firstSegment !== basename($scriptName)) {
                    return '/' . $firstSegment . '/';
                }
            }
        }

        return '/';
    }
}

if (!defined('BASE_URL')) {
    define('BASE_URL', ls_detect_base_url());
}

