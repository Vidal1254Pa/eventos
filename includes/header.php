<?php
// includes/header.php
require_once __DIR__ . '/../config.php';
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8" />

  <!-- RESPONSIVE -->
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />

  <title><?= h(APP_NAME) ?></title>

  <!-- CSS -->
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css?v=<?= time() ?>" />
</head>

<body>

<header class="topbar">
  <div class="brand">
    <span class="logo">✓</span>
    <div>
      <div class="title"><?= h(APP_NAME) ?></div>
      <div class="subtitle">Club Tennis</div>
    </div>
  </div>

  <nav class="nav">
    <?php if(is_logged_in()): ?>
      <a href="<?= BASE_URL ?>dashboard.php">Panel</a>
      <a href="<?= BASE_URL ?>registro/asistencia.php">Asistencia</a>
      <a href="<?= BASE_URL ?>reports/reportes.php">Reportes</a>

      <?php if(($_SESSION['user']['rol'] ?? '') === 'admin'): ?>
        <a href="<?= BASE_URL ?>admin/usuarios.php">Usuarios</a>
        <a href="<?= BASE_URL ?>admin/eventos.php">Eventos</a>
        <a href="<?= BASE_URL ?>admin/asistentes.php">Asistentes</a>
      <?php endif; ?>

      <a class="danger" href="<?= BASE_URL ?>auth/logout.php">Salir</a>
    <?php else: ?>
      <a href="<?= BASE_URL ?>auth/login.php">Ingresar</a>
    <?php endif; ?>
  </nav>
</header>

<main class="container">

<!-- 🔥 MENSAJES CON TOAST (REEMPLAZA ALERT) -->
<?php if($m = flash_get('ok')): ?>
  <div class="toast ok show"><?= h($m) ?></div>
<?php endif; ?>

<?php if($m = flash_get('err')): ?>
  <div class="toast err show"><?= h($m) ?></div>
<?php endif; ?>