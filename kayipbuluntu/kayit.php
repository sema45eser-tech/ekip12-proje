<?php
require 'ayar.php';
$sayfa_basligi = 'Kayıt ol';

// Zaten giriş yapmış kullanıcının burada işi yok.
if (giris_yapildi()) {
    header('Location: index.php');
    exit;
}

$hatalar = [];
$ad_soyad = '';
$eposta = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ad_soyad = trim($_POST['ad_soyad'] ?? '');
    $eposta   = trim($_POST['eposta'] ?? '');
    $parola   = $_POST['parola'] ?? '';
    $parola2  = $_POST['parola2'] ?? '';

    // --- Doğrulama ---
    if ($ad_soyad === '') {
        $hatalar[] = 'Ad soyad boş olamaz.';
    }
    if (uzunluk($ad_soyad) > 80) {
        $hatalar[] = 'Ad soyad en fazla 80 karakter olabilir.';
    }
    if (!filter_var($eposta, FILTER_VALIDATE_EMAIL) || uzunluk($eposta) > 120) {
        $hatalar[] = 'Geçerli bir e-posta adresi yazın.';
    }
    if (uzunluk($parola) < 8) {
        $hatalar[] = 'Parola en az 8 karakter olmalı.';
    }
    if ($parola !== $parola2) {
        $hatalar[] = 'Parolalar birbiriyle aynı değil.';
    }

    // Bu e-posta ile kayıtlı biri var mı?
    if (!$hatalar) {
        $var_mi = $db->prepare("SELECT COUNT(*) FROM kullanici WHERE eposta = :eposta");
        $var_mi->execute(['eposta' => $eposta]);
        if ($var_mi->fetchColumn() > 0) {
            $hatalar[] = 'Bu e-posta ile zaten bir hesap var. Giriş yapmayı deneyin.';
        }
    }

    if (!$hatalar) {
        // Parola asla düz metin saklanmaz: password_hash geri çevrilemez bir özet üretir.
        $ekle = $db->prepare(
            "INSERT INTO kullanici (ad_soyad, eposta, parola_hash, rol)
             VALUES (:ad_soyad, :eposta, :parola_hash, 'uye')"
        );
        $ekle->execute([
            'ad_soyad'    => $ad_soyad,
            'eposta'      => $eposta,
            'parola_hash' => password_hash($parola, PASSWORD_DEFAULT),
        ]);

        // Kayıttan hemen sonra oturumu açıyoruz.
        session_regenerate_id(true);
        $_SESSION['kullanici_id'] = (int) $db->lastInsertId();
        $_SESSION['ad_soyad']     = $ad_soyad;
        $_SESSION['rol']          = 'uye';
        header('Location: index.php');
        exit;
    }
}

require 'baslik.php';
?>

<section>
  <h2>Kayıt ol</h2>

  <?php foreach ($hatalar as $hata): ?>
    <p class="hata"><?= yaz($hata) ?></p>
  <?php endforeach; ?>

  <form method="post">
    <label for="ad_soyad">Ad soyad</label>
    <input type="text" name="ad_soyad" id="ad_soyad" maxlength="80"
           value="<?= yaz($ad_soyad) ?>" required>

    <label for="eposta">E-posta</label>
    <input type="email" name="eposta" id="eposta" maxlength="120"
           value="<?= yaz($eposta) ?>" required>

    <label for="parola">Parola (en az 8 karakter)</label>
    <input type="password" name="parola" id="parola" minlength="8" required>

    <label for="parola2">Parola (tekrar)</label>
    <input type="password" name="parola2" id="parola2" minlength="8" required>

    <button type="submit">Kayıt ol</button>
  </form>

  <p>Hesabınız var mı? <a href="giris.php">Giriş yapın</a></p>
</section>

<?php require 'altbilgi.php'; ?>
