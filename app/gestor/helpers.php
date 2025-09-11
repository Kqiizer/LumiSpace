<?php
function formatCurrency(float $num): string {
    return "$" . number_format($num, 2, ".", ",");
}

function timeAgo(string $fecha): string {
    $diff = time() - strtotime($fecha);
    if ($diff < 60) return "hace " . $diff . " seg";
    if ($diff < 3600) return "hace " . floor($diff/60) . " min";
    if ($diff < 86400) return "hace " . floor($diff/3600) . " hr";
    return date("d/m/Y H:i", strtotime($fecha));
}
