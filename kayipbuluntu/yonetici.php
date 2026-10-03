<?php
require 'ayar.php';
yonetici_zorunlu();                    // yalnızca yönetici rolü
$sayfa_basligi = 'Yönetici paneli';

// Kategoriye göre kaç eşya var? (LEFT JOIN: hiç eşyası olmayan kategori de görünsün)
$ozet = $db->query(
    "SELECT k.ad AS kategori,
            COUNT(e.esya_id) AS adet
     FROM kategori k
     LEFT JOIN esya e ON e.kategori_id = k.kategori_id AND e.durum = 'depoda'
     GROUP BY k.kategori_id, k.ad
     ORDER BY adet DESC, k.ad"
)->fetchAll();

// Bekleyen talepler
$talepler = $db->query(
    "SELECT t.talep_id, t.aciklama, t.talep_tarihi,
            e.ad AS esya, u.ad_soyad AS kullanici
     FROM talep t
     JOIN esya e      ON e.esya_id = t.esya_id
     JOIN kullanici u ON u.kullanici_id = t.kullanici_id
     WHERE t.durum = 'bekliyor'
     ORDER BY t.talep_tarihi"
)->fetchAll();

require 'baslik.php';
?>

<section>
  <h2>Kategoriye göre depodaki eşyalar</h2>
  <table>
    <thead><tr><th>Kategori</th><th>Adet</th></tr></thead>
    <tbody>
      <?php foreach ($ozet as $satir): ?>
        <tr>
          <td><?= yaz($satir['kategori']) ?></td>
          <td><?= (int) $satir['adet'] ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</section>

<section>
  <h2>Bekleyen talepler (<?= count($talepler) ?>)</h2>
  <table>
    <thead>
      <tr><th>Eşya</th><th>Talep eden</th><th>Açıklama</th><th>Tarih</th></tr>
    </thead>
    <tbody>
      <?php foreach ($talepler as $talep): ?>
      <tr>
        <td><?= yaz($talep['esya']) ?></td>
        <td><?= yaz($talep['kullanici']) ?></td>
        <td><?= yaz($talep['aciklama']) ?></td>
        <td><?= date('d.m.Y', strtotime($talep['talep_tarihi'])) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</section>

<?php require 'altbilgi.php'; ?>
