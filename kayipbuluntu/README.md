# Kampüs Kayıp-Buluntu Sistemi

Simav MYO · Bilgisayar Programcılığı · Referans proje

Bu proje dönem boyunca üzerinde çalışacağınız **çalışan** sistemdir.
Sıfırdan yazmayacaksınız; devralacak, onaracak ve geliştireceksiniz.

## Kurulum (15 dakika)

1. XAMPP'ı açın, **Apache** ve **MySQL**'i başlatın.
2. Bu klasörü `C:\xampp\htdocs` içine kopyalayın. Doğru yerdeyse şu dosya vardır: `C:\xampp\htdocs\kayipbuluntu\index.php`
3. Tarayıcıda `localhost/phpmyadmin` adresini açın.
4. Sol üstteki ev simgesine tıklayın (hiçbir veritabanı seçili olmasın) → **İçe aktar** sekmesi → `sema.sql` dosyasını seçin → sayfanın en altındaki **İçe aktar** düğmesine basın.
   Yeşil "İçe aktarma başarılı" mesajı yeterlidir; altındaki "#1046 No database selected" uyarıları önemsizdir.
5. Tarayıcıda `localhost/kayipbuluntu` adresini açın.

Bağlantı bilgilerini değiştirmeniz gerekirse: `ayar.php`

## Deneme hesapları

| E-posta | Parola | Rol |
| --- | --- | --- |
| yonetici@simav.edu.tr | 1234 | Yönetici |
| ayse@simav.edu.tr | 1234 | Üye |

Parolalar veritabanında `password_hash` ile saklanır, düz metin değildir.
Yeni hesap açmak için menüdeki **Kayıt ol** bağlantısını kullanın (parola en az 8 karakter).

## Dosyalar

| Dosya | Ne yapar |
| --- | --- |
| `sema.sql` | Veritabanı tabloları ve örnek veri |
| `ayar.php` | Veritabanı bağlantısı, oturum, ortak fonksiyonlar, dosya yükleme |
| `baslik.php` / `altbilgi.php` | Her sayfanın üst ve alt kısmı |
| `index.php` | Ana sayfa, son bulunan eşyalar |
| `esyalar.php` | Eşya listesi: kategori filtresi, arama, sıralama |
| `esya-ekle.php` | Eşya bildirme formu, fotoğraf yükleme (giriş ister) |
| `esya-duzenle.php` | Eşya güncelleme (yalnızca kaydı ekleyen veya yönetici) |
| `esya-sil.php` | Onaylı silme (yalnızca kaydı ekleyen veya yönetici) |
| `giris.php` / `kayit.php` / `cikis.php` | Oturum açma, yeni hesap, oturum kapatma |
| `yonetici.php` | Kategori özeti ve bekleyen talepler (yalnızca yönetici) |
| `stil.css` | Görünüm |
| `yuklemeler/` | Yüklenen fotoğraflar; içindeki `.htaccess` dosyası silinmemeli |
| `.gitignore` | Yüklenen fotoğrafların depoya gönderilmesini engeller |

## Veri modeli

    kategori 1 ─── N esya N ─── 1 kullanici
                     │
                     1
                     │
                     N
                   talep N ─── 1 kullanici

## Ekip

Bu bölümü kendi ekip bilgilerinizle doldurun:

- Ekip adı:Ali Gunduz
- Üyeler:
- Proje konusu:
