<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_login();

ini_set('zlib.output_compression', '0');
while (ob_get_level() > 0) { ob_end_clean(); }

require_once __DIR__ . '/../libs/SimpleXLSXGen.php';
use Shuchkin\SimpleXLSXGen;

// Traer asistentes
$res = $conexion->query("SELECT dni, nombres, apellidos, mesa, area, cargo, activo, creado_en
                         FROM asistentes
                         ORDER BY apellidos, nombres");

$data = [
  ['DNI','Nombres','Apellidos','Mesa','Área','Cargo','Activo','Creado']
];

while($r = $res->fetch_assoc()){
  $data[] = [
    $r['dni'],
    $r['nombres'],
    $r['apellidos'],
    $r['mesa'] ?? '',
    $r['area'] ?? '',
    $r['cargo'] ?? '',
    ((int)$r['activo']===1 ? 'SI' : 'NO'),
    $r['creado_en']
  ];
}

$xlsx = SimpleXLSXGen::fromArray($data, 'Asistentes');
$xlsx->downloadAs('Asistentes.xlsx');
exit;
