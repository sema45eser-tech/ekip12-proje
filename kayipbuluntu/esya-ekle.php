<?php
require 'ayar.php';
giris_zorunlu();                       // bu sayfayı yalnızca giriş yapanlar görebilir
$sayfa_basligi = 'Eşya bildir';

$hatalar = [];
$kaydedildi = false;

// Form alanlarının başlangıç değerleri (hata olursa kullanıcının yazdıkları geri gelsin)
$ad = $aciklama = $yer = $bulunma_tarihi = $kategori_id = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ad             = trim($_POST['ad'] ?? '');
    $aciklama       = trim($_POST['aciklama'] ?? '');
    $yer            = trim($_POST['yer'] ?? '');
    $bulunma_tarihi = $_POST['bulunma_tarihi'] ?? '';
    $kategori_id    = $_POST['kategori_id'] ?? '';

    // --- Doğrulama ---
    if ($ad === '')             { $hatalar[] = 'Eşya adı boş olamaz.'; }
    if ($yer === '')            { $hatalar[] = 'Bulunduğu yer boş olamaz.'; }
    if ($kategori_id === '')    { $hatalar[] = 'Kategori seçilmeli.'; }
    if (uzunluk($ad) > 100) {
        $hatalar[] = 'Eşya adı en fazla 100 karakter olabilir.';
    }
    if (uzunluk($yer) > 60) {
        $hatalar[] = 'Yer en fazla 60 karakter olabilir.';
    }
    if (!gecerli_tarih($bulunma_tarihi)) {
        $hatalar[] = 'Bulunma tarihi seçilmeli.';
    } elseif ($bulunma_tarihi > date('Y-m-d')) {
        $hatalar[] = 'Bulunma tarihi gelecekte olamaz.';
    }

    // Seçim listesi tarayıcıda değiştirilebilir: seçilen numara tabloda var mı?
    if ($kategori_id !== '') {
        $var_mi = $db->prepare("SELECT COUNT(*) FROM kategori WHERE kategori_id = :id");
        $var_mi->execute(['id' => $kategori_id]);
        if ($var_mi->fetchColumn() == 0) {
            $hatalar[] = 'Seçilen kategori bulunamadı.';
        }
    }

    // Fotoğraf isteğe bağlı; seçilmişse doğrulanıp yuklemeler/ klasörüne taşınır.
    // Diğer alanlarda hata varsa dosyayı hiç kaydetmiyoruz (sahipsiz dosya kalmasın).
    $fotograf = null;
    if (!$hatalar) {
        $fotograf = dosya_yukle('fotograf', $hatalar);
    }

    if (!$hatalar) {
        $ekle = $db->prepare(
            "INSERT INTO esya (ad, aciklama, yer, bulunma_tarihi,
                               kategori_id, bulan_id, fotograf)
             VALUES (:ad, :aciklama, :yer, :bulunma_tarihi,
                     :kategori_id, :bulan_id, :fotograf)"
        );
        $ekle->execute([
            'ad'             => $ad,
            'aciklama'       => $aciklama,
            'yer'            => $yer,
            'bulunma_tarihi' => $bulunma_tarihi,
            'kategori_id'    => $kategori_id,
            'bulan_id'       => $_SESSION['kullanici_id'],
            'fotograf'       => $fotograf,
        ]);
        $kaydedildi = true;
        $ad = $aciklama = $yer = $bulunma_tarihi = $kategori_id = '';   // formu boşalt
    }
}

$kategoriler = $db->query("SELECT kategori_id, ad FROM kategori ORDER BY ad")->fetchAll();
require 'baslik.php';
?>

<section>
  <h2>Eşya bildir</h2>

  <?php if ($kaydedildi): ?>
    <p class="basarili">Kayıt eklendi. <a href="esyalar.php">Listeye git</a></p>
  <?php endif; ?>

  <?php foreach ($hatalar as $hata): ?>
    <p class="hata"><?= yaz($hata) ?></p>
  <?php endforeach; ?>

  <form method="post" enctype="multipart/form-data">
    <label for="ad">Eşya adı</label>
    <input type="text" name="ad" id="ad" maxlength="100"
           value="<?= yaz($ad) ?>" required>

    <label for="kategori_id">Kategori</label>
    <select name="kategori_id" id="kategori_id" required>
      <option value="">Seçiniz</option>
      <?php foreach ($kategoriler as $kategori): ?>
        <option value="<?= (int) $kategori['kategori_id'] ?>"
          <?= $kategori_id == $kategori['kategori_id'] ? 'selected' : '' ?>>
          <?= yaz($kategori['ad']) ?>
        </option>
      <?php endforeach; ?>
    </select>

    <label for="yer">Bulunduğu yer</label>
    <input type="text" name="yer" id="yer" maxlength="60"
           value="<?= yaz($yer) ?>" required>

    <label for="bulunma_tarihi">Bulunma tarihi</label>
    <input type="date" name="bulunma_tarihi" id="bulunma_tarihi"
           value="<?= yaz($bulunma_tarihi) ?>" max="<?= date('Y-m-d') ?>" required>

    <label for="aciklama">Açıklama</label>
    <textarea name="aciklama" id="aciklama" rows="3"><?= yaz($aciklama) ?></textarea>

    <label for="fotograf">Fotoğraf (isteğe bağlı, en fazla 2 MB)</label>
    <input type="file" name="fotograf" id="fotograf"
           accept="image/jpeg,image/png,image/webp">

    <button type="submit">Kaydet</button>
  </form>
</section>

<?php require 'altbilgi.php'; ?>
