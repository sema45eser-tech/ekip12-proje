<?php /* Her sayfanın üst kısmı. $sayfa_basligi değişkeni sayfadan gelir. */ ?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= yaz($sayfa_basligi ?? 'Kampüs Kayıp-Buluntu') ?></title>
  <link rel="stylesheet" href="stil.css">
</head>
<body>
<header>
  <h1>Kampüs Kayıp-Buluntu</h1>
</header>

<nav>
  <a href="index.php">Ana sayfa</a>
  <a href="esyalar.php">Bulunan eşyalar</a>
  <?php if (giris_yapildi()): ?>
    <a href="esya-ekle.php">Eşya bildir</a>
    <?php if (($_SESSION['rol'] ?? '') === 'yonetici'): ?>
      <a href="yonetici.php">Yönetici paneli</a>
    <?php endif; ?>
    <a href="cikis.php">Çıkış (<?= yaz($_SESSION['ad_soyad']) ?>)</a>
  <?php else: ?>
    <a href="giris.php">Giriş</a>
    <a href="kayit.php">Kayıt ol</a>
  <?php endif; ?>
</nav>

<main>
