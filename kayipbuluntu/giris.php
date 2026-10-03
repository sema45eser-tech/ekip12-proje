<?php
require 'ayar.php';
$sayfa_basligi = 'Giriş';
$hata = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $eposta = trim($_POST['eposta'] ?? '');
    $parola = $_POST['parola'] ?? '';

    $sorgu = $db->prepare("SELECT * FROM kullanici WHERE eposta = :eposta");
    $sorgu->execute(['eposta' => $eposta]);
    $kullanici = $sorgu->fetch();

    // password_verify: saklanan özet ile girilen parolayı karşılaştırır
    if ($kullanici && password_verify($parola, $kullanici['parola_hash'])) {
        session_regenerate_id(true);   // oturum sabitleme saldırısına karşı
        $_SESSION['kullanici_id'] = $kullanici['kullanici_id'];
        $_SESSION['ad_soyad']     = $kullanici['ad_soyad'];
        $_SESSION['rol']          = $kullanici['rol'];
        header('Location: index.php');
        exit;
    }
    $hata = 'E-posta veya parola hatalı.';
}

require 'baslik.php';
?>

<section>
  <h2>Giriş</h2>
  <?php if ($hata): ?><p class="hata"><?= yaz($hata) ?></p><?php endif; ?>

  <form method="post">
    <label for="eposta">E-posta</label>
    <input type="email" name="eposta" id="eposta" required>

    <label for="parola">Parola</label>
    <input type="password" name="parola" id="parola" required>

    <button type="submit">Giriş yap</button>
  </form>

  <p>Hesabınız yok mu? <a href="kayit.php">Kayıt olun</a></p>
</section>

<?php require 'altbilgi.php'; ?>
