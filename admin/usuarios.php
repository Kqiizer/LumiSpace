<?php
// admin/usuarios.php

function getTotalUsuarios() {
    return 120; // ejemplo
}

function getTotalGestores() {
    return 5; // ejemplo
}

function getUsuariosRecientes($limit = 5) {
    return [
        ["nombre"=>"Carlos Pérez","email"=>"carlos@example.com","fecha_registro"=>"2025-09-08 10:30:00"],
        ["nombre"=>"Ana López","email"=>"ana@example.com","fecha_registro"=>"2025-09-07 15:10:00"],
    ];
}
