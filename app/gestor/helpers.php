<?php
/**
 * Formato de moneda
 */
function formatCurrency(float $monto): string {
    return "$" . number_format($monto, 2, ".", ",");
}

/**
 * Tiempo relativo (ej: "hace 5 min")
 */
function timeAgo(string $datetime): string {
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;

    if ($diff < 60) return "Hace " . $diff . "s";
    if ($diff < 3600) return "Hace " . floor($diff / 60) . "m";
    if ($diff < 86400) return "Hace " . floor($diff / 3600) . "h";
    return date("d M Y H:i", $timestamp);
}
