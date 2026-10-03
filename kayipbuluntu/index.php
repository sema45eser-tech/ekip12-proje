<?php
require 'ayar.php';
$sayfa_basligi = 'Ana sayfa · Kampüs Kayıp-Buluntu';

// Depoda bekleyen son 5 eşya
$sorgu = $db->query(
    "SELECT e.ad, e.yer, e.bulunma_tarihi, k.ad AS kategori
     FROM esya e
     JOIN kategori k ON k.kategori_id = e.kategori_id
     WHERE e.durum = 'depoda'
     ORDER BY e.bulunma_tarihi DESC
     LIMIT 5"
);
$son_esyalar = $sorgu->fetchAll();

// Toplam kaç eşya depoda bekliyor?
$bekleyen = $db->query("SELECT COUNT(*) FROM esya WHERE durum = 'depoda'")
                ->fetchColumn();

require 'baslik.php';
?>

<section>
  <h2>Kaybettiğin eşyayı bul</h2>
  <p>Kampüste bulunan eşyalar burada listelenir.
     Şu anda <strong><?= (int) $bekleyen ?></strong> eşya sahibini bekliyor.</p>
  <p><a href="esyalar.php">Tüm eşyaları gör</a></p>
</section>

<section>
  <h2>Son bulunanlar</h2>
  <table>
    <thead>
      <tr><th>Eşya</th><th>Kategori</th><th>Yer</th><th>Tarih</th></tr>
    </thead>
    <tbody>
      <?php foreach ($son_esyalar as $esya): ?>
      <tr>
        <td><?= yaz($esya['ad']) ?></td>
        <td><?= yaz($esya['kategori']) ?></td>
        <td><?= yaz($esya['yer']) ?></td>
        <td><?= date('d.m.Y', strtotime($esya['bulunma_tarihi'])) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</section>

<?php require 'altbilgi.php'; ?>
