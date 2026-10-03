<?php
require 'ayar.php';
giris_zorunlu();
$sayfa_basligi = 'Eşyayı sil';

$esya_id = (int) ($_GET['id'] ?? $_POST['esya_id'] ?? 0);

$sorgu = $db->prepare("SELECT * FROM esya WHERE esya_id = :id");
$sorgu->execute(['id' => $esya_id]);
$esya = $sorgu->fetch();

if (!$esya) {
    http_response_code(404);
    require 'baslik.php';
    echo '<section><h2>Kayıt bulunamadı</h2>'
       . '<p><a href="esyalar.php">Listeye dön</a></p></section>';
    require 'altbilgi.php';
    exit;
}

if (!kayit_sahibi_mi($esya)) {
    http_response_code(403);
    require 'baslik.php';
    echo '<section><h2>Yetkiniz yok</h2>'
       . '<p>Yalnızca kaydı ekleyen kişi veya yönetici silebilir. '
       . '<a href="esyalar.php">Listeye dön</a></p></section>';
    require 'altbilgi.php';
    exit;
}

// Silme yalnızca POST ile yapılır: bağlantıya tıklamak kaydı silmez, onay sayfası açar.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    /* Eşyanın talepleri var; önce onları silmemiz gerekiyor.
       İkisi tek işlemde olmalı: biri olup diğeri olmazsa veri bozulur. */
    $db->beginTransaction();
    try {
        $db->prepare("DELETE FROM talep WHERE esya_id = :id")
           ->execute(['id' => $esya_id]);
        $db->prepare("DELETE FROM esya  WHERE esya_id = :id")
           ->execute(['id' => $esya_id]);
        $db->commit();
    } catch (PDOException $e) {
        $db->rollBack();
        throw $e;
    }

    dosya_sil($esya['fotograf']);
    header('Location: esyalar.php?silindi=1');
    exit;
}

// Kaç talep silinecek? Kullanıcıya önceden söylüyoruz.
$talep_sayisi = $db->prepare("SELECT COUNT(*) FROM talep WHERE esya_id = :id");
$talep_sayisi->execute(['id' => $esya_id]);
$talep = (int) $talep_sayisi->fetchColumn();

require 'baslik.php';
?>

<section>
  <h2>Silmek istediğinize emin misiniz?</h2>

  <p><strong><?= yaz($esya['ad']) ?></strong> — <?= yaz($esya['yer']) ?>,
     <?= date('d.m.Y', strtotime($esya['bulunma_tarihi'])) ?></p>

  <?php if ($talep > 0): ?>
    <p class="hata">Bu eşyaya ait <?= $talep ?> talep de silinecek.</p>
  <?php endif; ?>

  <form method="post">
    <input type="hidden" name="esya_id" value="<?= (int) $esya['esya_id'] ?>">
    <button type="submit">Evet, sil</button>
  </form>

  <p><a href="esyalar.php">Vazgeç</a></p>
</section>

<?php require 'altbilgi.php'; ?>
