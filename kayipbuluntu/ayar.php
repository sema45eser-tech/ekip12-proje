<?php
/**
 * Veritabanı bağlantısı ve ortak ayarlar.
 * Bütün sayfalar bu dosyayı çağırır.
 */

// --- Veritabanı bilgileri (XAMPP varsayılanı) ---
$sunucu   = 'localhost';
$veritabani = 'kayipbuluntu';
$kullanici_adi = 'root';
$parola   = '';

try {
    // PDO: veritabanına bağlanmanın güvenli yolu
    $db = new PDO(
        "mysql:host=$sunucu;dbname=$veritabani;charset=utf8mb4",
        $kullanici_adi,
        $parola,
        [
            // hata olursa susma, haber ver
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    die('Veritabanına bağlanılamadı: ' . $e->getMessage());
}

// Tarih kontrolleri (ör. "gelecek tarih olamaz") Türkiye saatine göre yapılsın.
date_default_timezone_set('Europe/Istanbul');

session_start();

/**
 * Ekrana yazdırılacak her değer buradan geçer.
 * Kullanıcının yazdığı metin HTML olarak çalışmasın diye (XSS koruması).
 */
function yaz(?string $metin): string
{
    return htmlspecialchars($metin ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Metnin karakter sayısı. Türkçe harfler (ğ, ş, İ) tek karakter sayılır.
 * Formdaki bir metin, veritabanındaki sütunun uzunluğunu aşmasın diye kullanılır.
 */
function uzunluk(string $metin): int
{
    return preg_match_all('/./us', $metin);
}

/**
 * Metin YYYY-AA-GG biçiminde gerçek bir tarih mi?
 * Örnek: 2026-09-28 geçerli; 2026-02-30, 2026-9-1 ve boş metin geçersiz.
 */
function gecerli_tarih(string $tarih): bool
{
    $t = DateTime::createFromFormat('Y-m-d', $tarih);
    return $t !== false && $t->format('Y-m-d') === $tarih;
}

/** Giriş yapılmış mı? */
function giris_yapildi(): bool
{
    return isset($_SESSION['kullanici_id']);
}

/** Giriş yapılmamışsa giriş sayfasına gönder. */
function giris_zorunlu(): void
{
    if (!giris_yapildi()) {
        header('Location: giris.php');
        exit;
    }
}

/** Yönetici değilse ana sayfaya gönder. */
function yonetici_zorunlu(): void
{
    giris_zorunlu();
    if (($_SESSION['rol'] ?? '') !== 'yonetici') {
        header('Location: index.php');
        exit;
    }
}

/* ------------------------------------------------------------------
   Yetki ve dosya yükleme yardımcıları
   ------------------------------------------------------------------ */

/** Oturumdaki kullanıcı yönetici mi? */
function yonetici_mi(): bool
{
    return ($_SESSION['rol'] ?? '') === 'yonetici';
}

/**
 * Bu kaydı düzenleme/silme yetkisi var mı?
 * Kuralı: kaydı ekleyen kişi veya yönetici.
 */
function kayit_sahibi_mi(array $kayit): bool
{
    return yonetici_mi()
        || (int) ($kayit['bulan_id'] ?? 0) === (int) ($_SESSION['kullanici_id'] ?? 0);
}

/** Yüklemelerin durduğu klasör */
const YUKLEME_KLASORU = __DIR__ . '/yuklemeler/';

/** İzin verilen dosya türleri: MIME türü => uzantı */
const IZINLI_TURLER = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
];

/** En büyük dosya boyutu: 2 MB */
const EN_BUYUK_BOYUT = 2 * 1024 * 1024;

/**
 * Yüklenen dosyayı doğrular ve yuklemeler/ klasörüne taşır.
 * Başarılıysa kaydedilen dosyanın adını, dosya seçilmemişse null döner.
 * Hata olursa $hatalar dizisine mesaj ekler ve null döner.
 */
function dosya_yukle(string $alan, array &$hatalar): ?string
{
    // Dosya seçilmemiş: bu bir hata değil, fotoğraf zorunlu değil.
    if (!isset($_FILES[$alan]) || $_FILES[$alan]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    $dosya = $_FILES[$alan];

    if ($dosya['error'] !== UPLOAD_ERR_OK) {
        // Hata kodunu anlaşılır bir mesaja çeviriyoruz.
        $mesajlar = [
            UPLOAD_ERR_INI_SIZE   =>
                'Dosya çok büyük (sunucu sınırı). En fazla 2 MB yükleyin.',
            UPLOAD_ERR_FORM_SIZE  => 'Dosya çok büyük. En fazla 2 MB yükleyin.',
            UPLOAD_ERR_PARTIAL    => 'Dosya yarım yüklendi. Tekrar deneyin.',
            UPLOAD_ERR_NO_TMP_DIR => 'Sunucuda geçici klasör yok (php.ini ayarı).',
            UPLOAD_ERR_CANT_WRITE => 'Dosya diske yazılamadı (klasör izinleri).',
            UPLOAD_ERR_EXTENSION  => 'Yükleme bir PHP eklentisi tarafından durduruldu.',
        ];
        $hatalar[] = $mesajlar[$dosya['error']] ?? 'Dosya yüklenemedi. Tekrar deneyin.';
        return null;
    }

    if ($dosya['size'] > EN_BUYUK_BOYUT) {
        $hatalar[] = 'Dosya çok büyük. En fazla 2 MB yükleyin.';
        return null;
    }

    // Uzantıya değil, dosyanın gerçek içeriğine bakıyoruz.
    // getimagesize() görsel olmayan her dosyada false döner (ek eklenti gerektirmez).
    $bilgi = @getimagesize($dosya['tmp_name']);
    $tur = $bilgi['mime'] ?? '';

    if (!isset(IZINLI_TURLER[$tur])) {
        $hatalar[] = 'Yalnızca JPG, PNG veya WEBP yükleyebilirsiniz.';
        return null;
    }

    if (!is_dir(YUKLEME_KLASORU)) {
        mkdir(YUKLEME_KLASORU, 0775, true);
    }

    // Dosya adını biz üretiyoruz: kullanıcının verdiği ad asla kullanılmaz.
    $ad = uniqid('esya_', true) . '.' . IZINLI_TURLER[$tur];

    if (!move_uploaded_file($dosya['tmp_name'], YUKLEME_KLASORU . $ad)) {
        $hatalar[] = 'Dosya kaydedilemedi.';
        return null;
    }

    return $ad;
}

/** Bir yüklemeyi diskten siler. */
function dosya_sil(?string $ad): void
{
    if ($ad && is_file(YUKLEME_KLASORU . $ad)) {
        unlink(YUKLEME_KLASORU . $ad);
    }
}
