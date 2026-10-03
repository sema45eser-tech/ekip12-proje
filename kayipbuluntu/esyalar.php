<?php
require 'ayar.php';
$sayfa_basligi = 'Bulunan eşyalar';

// --- Filtre değerlerini adres satırından al ---
$secilen_kategori = $_GET['kategori_id'] ?? '';
$aranan           = trim($_GET['ara'] ?? '');
$siralama         = $_GET['sirala'] ?? 'yeni';

/* Sıralama seçenekleri. ORDER BY içine kullanıcıdan gelen değer ASLA doğrudan yazılmaz;
   hazırlıklı ifade de burada işe yaramaz. Bu yüzden izin verilen seçenekleri
   bir listede tutuyor, gelen değeri bu listeyle eşleştiriyoruz (beyaz liste). */
$siralamalar = [
    'yeni' => ['Yeniden eskiye', 'e.bulunma_tarihi DESC'],
    'eski' => ['Eskiden yeniye', 'e.bulunma_tarihi ASC'],
    'ad'   => ['Ada göre (A–Z)', 'e.ad ASC'],
];
if (!isset($siralamalar[$siralama])) {
    $siralama = 'yeni';
}

// --- Sorguyu parça parça kur ---
$sql = "SELECT e.esya_id, e.ad, e.yer, e.bulunma_tarihi, e.fotograf, e.bulan_id,
               k.ad AS kategori
        FROM esya e
        JOIN kategori k ON k.kategori_id = e.kategori_id
        WHERE e.durum = 'depoda'";
$parametreler = [];

if ($secilen_kategori !== '') {
    $sql .= " AND e.kategori_id = :kategori_id";
    $parametreler['kategori_id'] = $secilen_kategori;
}

if ($aranan !== '') {
    $sql .= " AND e.ad LIKE :aranan";
    $parametreler['aranan'] = '%' . $aranan . '%';
}

$sql .= " ORDER BY " . $siralamalar[$siralama][1];

// Hazırlıklı sorgu: kullanıcının yazdığı metin sorguya karışmaz
// (SQL enjeksiyonu koruması).
$sorgu = $db->prepare($sql);
$sorgu->execute($parametreler);
$esyalar = $sorgu->fetchAll();

$kategoriler = $db->query("SELECT kategori_id, ad FROM kategori ORDER BY ad")->fetchAll();

require 'baslik.php';
?>

<section>
  <h2>Bulunan eşyalar</h2>

  <?php if (isset($_GET['silindi'])): ?>
    <p class="basarili">Kayıt silindi.</p>
  <?php endif; ?>

  <form method="get" class="filtre">
    <label for="kategori_id">Kategori</label>
    <select name="kategori_id" id="kategori_id">
      <option value="">Hepsi</option>
      <?php foreach ($kategoriler as $kategori): ?>
        <option value="<?= (int) $kategori['kategori_id'] ?>"
          <?= $secilen_kategori == $kategori['kategori_id'] ? 'selected' : '' ?>>
          <?= yaz($kategori['ad']) ?>
        </option>
      <?php endforeach; ?>
    </select>

    <label for="ara">Eşya adı</label>
    <input type="text" name="ara" id="ara" value="<?= yaz($aranan) ?>">

    <label for="sirala">Sırala</label>
    <select name="sirala" id="sirala">
      <?php foreach ($siralamalar as $anahtar => [$etiket]): ?>
        <option value="<?= yaz($anahtar) ?>"
          <?= $siralama === $anahtar ? 'selected' : '' ?>><?= yaz($etiket) ?></option>
      <?php endforeach; ?>
    </select>

    <button type="submit">Filtrele</button>
  </form>

  <p><?= count($esyalar) ?> kayıt bulundu.</p>

  <table>
    <thead>
      <tr>
        <th>Fotoğraf</th><th>Eşya</th><th>Kategori</th><th>Yer</th><th>Tarih</th>
        <?php if (giris_yapildi()): ?><th>İşlem</th><?php endif; ?>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($esyalar as $esya): ?>
      <tr>
        <td>
          <?php if ($esya['fotograf']): ?>
            <img src="yuklemeler/<?= yaz($esya['fotograf']) ?>" alt="" class="kucuk">
          <?php else: ?>
            <span class="yok">—</span>
          <?php endif; ?>
        </td>
        <td><?= yaz($esya['ad']) ?></td>
        <td><?= yaz($esya['kategori']) ?></td>
        <td><?= yaz($esya['yer']) ?></td>
        <td><?= date('d.m.Y', strtotime($esya['bulunma_tarihi'])) ?></td>
        <?php if (giris_yapildi()): ?>
          <td>
            <?php if (kayit_sahibi_mi($esya)): ?>
              <a href="esya-duzenle.php?id=<?= (int) $esya['esya_id'] ?>">Düzenle</a> ·
              <a href="esya-sil.php?id=<?= (int) $esya['esya_id'] ?>">Sil</a>
            <?php else: ?>
              <span class="yok">—</span>
            <?php endif; ?>
          </td>
        <?php endif; ?>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</section>

<?php require 'altbilgi.php'; ?>
