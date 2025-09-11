<?php
// admin/helpers.php

function formatCurrency($amount) {
    return "$" . number_format($amount, 2, '.', ',');
}

function timeAgo($date) {
    $timestamp = strtotime($date);
    $diff = time() - $timestamp;
    if ($diff < 60) return "justo ahora";
    $minutes = floor($diff / 60);
    if ($minutes < 60) return "hace $minutes min";
    $hours = floor($minutes / 60);
    if ($hours < 24) return "hace $hours horas";
    $days = floor($hours / 24);
    return "hace $days días";
}
