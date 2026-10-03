<?php
require 'ayar.php';
giris_zorunlu();
$sayfa_basligi = 'Eşyayı düzenle';

$esya_id = (int) ($_GET['id'] ?? $_POST['esya_id'] ?? 0);

// --- Kaydı getir ---
$sorgu = $db->prepare("SELECT * FROM esya WHERE esya_id = :id");
$sorgu->execute(['id' => $esya_id]);
$esya = $sorgu->fetch();

if (!$esya) {
    http_response_code(404);
    $sayfa_basligi = 'Kayıt bulunamadı';
    require 'baslik.php';
    echo '<section><h2>Kayıt bulunamadı</h2><p>Aradığınız eşya silinmiş olabilir. '
       . '<a href="esyalar.php">Listeye dön</a></p></section>';
    require 'altbilgi.php';
    exit;
}

// --- Yetki: yalnızca kaydı ekleyen veya yönetici ---
if (!kayit_sahibi_mi($esya)) {
    http_response_code(403);
    require 'baslik.php';
    echo '<section><h2>Yetkiniz yok</h2>'
       . '<p>Yalnızca kaydı ekleyen kişi veya yönetici düzenleyebilir. '
       . '<a href="esyalar.php">Listeye dön</a></p></section>';
    require 'altbilgi.php';
    exit;
}

$hatalar = [];
$kaydedildi = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ad             = trim($_POST['ad'] ?? '');
    $aciklama       = trim($_POST['aciklama'] ?? '');
    $yer            = trim($_POST['yer'] ?? '');
    $bulunma_tarihi = $_POST['bulunma_tarihi'] ?? '';
    $kategori_id    = $_POST['kategori_id'] ?? '';
    $durum          = $_POST['durum'] ?? 'depoda';

    if ($ad === '')             { $hatalar[] = 'Eşya adı boş olamaz.'; }
    if ($yer === '')            { $hatalar[] = 'Bulunduğu yer boş olamaz.'; }
    if ($kategori_id === '')    { $hatalar[] = 'Kategori seçilmeli.'; }
    if (!in_array($durum, ['depoda', 'teslim_edildi'], true)) {
        $hatalar[] = 'Durum geçersiz.';
    }
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

    // Yeni fotoğraf seçildiyse yükle; seçilmediyse eskisi kalsın.
    // Diğer alanlarda hata varsa dosyayı hiç kaydetmiyoruz (sahipsiz dosya kalmasın).
    $fotograf = null;
    if (!$hatalar) {
        $fotograf = dosya_yukle('fotograf', $hatalar);
    }

    if (!$hatalar) {
        $guncelle = $db->prepare(
            "UPDATE esya
                SET ad = :ad,
                    aciklama = :aciklama,
                    yer = :yer,
                    bulunma_tarihi = :bulunma_tarihi,
                    kategori_id = :kategori_id,
                    durum = :durum"
            . ($fotograf ? ", fotograf = :fotograf" : "") .
            " WHERE esya_id = :id"
        );

        $degerler = [
            'ad'             => $ad,
            'aciklama'       => $aciklama,
            'yer'            => $yer,
            'bulunma_tarihi' => $bulunma_tarihi,
            'kategori_id'    => $kategori_id,
            'durum'          => $durum,
            'id'             => $esya_id,
        ];
        if ($fotograf) { $degerler['fotograf'] = $fotograf; }

        $guncelle->execute($degerler);

        if ($fotograf) { dosya_sil($esya['fotograf']); }   // eski dosyayı diskten kaldır

        $kaydedildi = true;
        $sorgu->execute(['id' => $esya_id]);               // güncel hâlini tekrar oku
        $esya = $sorgu->fetch();
    } else {
        // Hata var: formda kullanıcının yazdıkları görünsün (eski hâli değil).
        $esya = array_merge($esya, [
            'ad'             => $ad,
            'aciklama'       => $aciklama,
            'yer'            => $yer,
            'bulunma_tarihi' => $bulunma_tarihi,
            'kategori_id'    => $kategori_id,
            'durum'          => $durum,
        ]);
    }
}

$kategoriler = $db->query("SELECT kategori_id, ad FROM kategori ORDER BY ad")->fetchAll();
require 'baslik.php';
?>

<section>
  <h2>Eşyayı düzenle</h2>

  <?php if ($kaydedildi): ?>
    <p class="basarili">Değişiklikler kaydedildi.
      <a href="esyalar.php">Listeye git</a></p>
  <?php endif; ?>

  <?php foreach ($hatalar as $hata): ?>
    <p class="hata"><?= yaz($hata) ?></p>
  <?php endforeach; ?>

  <form method="post" enctype="multipart/form-data">
    <input type="hidden" name="esya_id" value="<?= (int) $esya['esya_id'] ?>">

    <label for="ad">Eşya adı</label>
    <input type="text" name="ad" id="ad" maxlength="100"
           value="<?= yaz($esya['ad']) ?>" required>

    <label for="kategori_id">Kategori</label>
    <select name="kategori_id" id="kategori_id" required>
      <?php foreach ($kategoriler as $kategori): ?>
        <option value="<?= (int) $kategori['kategori_id'] ?>"
          <?= $esya['kategori_id'] == $kategori['kategori_id'] ? 'selected' : '' ?>>
          <?= yaz($kategori['ad']) ?>
        </option>
      <?php endforeach; ?>
    </select>

    <label for="yer">Bulunduğu yer</label>
    <input type="text" name="yer" id="yer" maxlength="60"
           value="<?= yaz($esya['yer']) ?>" required>

    <label for="bulunma_tarihi">Bulunma tarihi</label>
    <input type="date" name="bulunma_tarihi" id="bulunma_tarihi"
           value="<?= yaz($esya['bulunma_tarihi']) ?>"
           max="<?= date('Y-m-d') ?>" required>

    <label for="durum">Durum</label>
    <select name="durum" id="durum">
      <option value="depoda"
        <?= $esya['durum'] === 'depoda' ? 'selected' : '' ?>>Depoda</option>
      <option value="teslim_edildi"
        <?= $esya['durum'] === 'teslim_edildi' ? 'selected' : '' ?>>Teslim edildi</option>
    </select>

    <label for="aciklama">Açıklama</label>
    <textarea name="aciklama" id="aciklama"
              rows="3"><?= yaz($esya['aciklama']) ?></textarea>

    <label for="fotograf">Fotoğraf (değiştirmek istemiyorsanız boş bırakın)</label>
    <input type="file" name="fotograf" id="fotograf"
           accept="image/jpeg,image/png,image/webp">
    <?php if ($esya['fotograf']): ?>
      <p><img src="yuklemeler/<?= yaz($esya['fotograf']) ?>"
              alt="Eşya fotoğrafı" class="onizleme"></p>
    <?php endif; ?>

    <button type="submit">Kaydet</button>
  </form>

  <p><a href="esya-sil.php?id=<?= (int) $esya['esya_id'] ?>">Bu kaydı sil</a></p>
</section>

<?php require 'altbilgi.php'; ?>
