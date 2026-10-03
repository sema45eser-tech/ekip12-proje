-- ============================================================
--  Kampüs Kayıp-Buluntu Sistemi — Veritabanı Şeması
--  Simav MYO · Bilgisayar Programcılığı
--  Kurulum: phpMyAdmin > İçe Aktar > bu dosyayı seç
-- ============================================================

-- Türkçe karakterlerin doğru kaydedilmesi için (her istemcide gerekir)
SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS kayipbuluntu
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_turkish_ci;

USE kayipbuluntu;

-- ---------- Kategori: eşya türleri ----------
CREATE TABLE kategori (
  kategori_id  INT AUTO_INCREMENT PRIMARY KEY,
  ad           VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;

-- ---------- Kullanıcı: sisteme giriş yapanlar ----------
CREATE TABLE kullanici (
  kullanici_id INT AUTO_INCREMENT PRIMARY KEY,
  ad_soyad     VARCHAR(80)  NOT NULL,
  eposta       VARCHAR(120) NOT NULL UNIQUE,
  parola_hash  VARCHAR(255) NOT NULL,   -- parola ASLA düz metin saklanmaz
  rol          ENUM('uye','yonetici') NOT NULL DEFAULT 'uye'
) ENGINE=InnoDB;

-- ---------- Eşya: bulunan eşya kayıtları ----------
CREATE TABLE esya (
  esya_id      INT AUTO_INCREMENT PRIMARY KEY,
  ad           VARCHAR(100) NOT NULL,
  aciklama     TEXT,
  yer          VARCHAR(60)  NOT NULL,
  bulunma_tarihi DATE       NOT NULL,
  durum        ENUM('depoda','teslim_edildi') NOT NULL DEFAULT 'depoda',
  fotograf     VARCHAR(255) NULL,          -- yuklemeler/ klasöründeki dosyanın adı
  kategori_id  INT NOT NULL,
  bulan_id     INT NOT NULL,
  CONSTRAINT fk_esya_kategori FOREIGN KEY (kategori_id) REFERENCES kategori(kategori_id),
  CONSTRAINT fk_esya_bulan    FOREIGN KEY (bulan_id)    REFERENCES kullanici(kullanici_id)
) ENGINE=InnoDB;

-- ---------- Talep: "bu eşya benim" başvuruları ----------
CREATE TABLE talep (
  talep_id     INT AUTO_INCREMENT PRIMARY KEY,
  esya_id      INT NOT NULL,
  kullanici_id INT NOT NULL,
  aciklama     TEXT NOT NULL,
  talep_tarihi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  durum        ENUM('bekliyor','onaylandi','reddedildi') NOT NULL DEFAULT 'bekliyor',
  CONSTRAINT fk_talep_esya     FOREIGN KEY (esya_id)      REFERENCES esya(esya_id),
  CONSTRAINT fk_talep_kullanici FOREIGN KEY (kullanici_id) REFERENCES kullanici(kullanici_id)
) ENGINE=InnoDB;

-- ============================================================
--  Örnek veri
-- ============================================================
INSERT INTO kategori (ad) VALUES
  ('Elektronik'), ('Kıyafet'), ('Kimlik ve kart'), ('Kitap ve defter'), ('Diğer');

-- Her iki parolanın düz hâli: 1234   (yalnızca ders ortamı içindir)
INSERT INTO kullanici (ad_soyad, eposta, parola_hash, rol) VALUES
  ('Sistem Yöneticisi', 'yonetici@simav.edu.tr', '$2y$12$DqO4SEDjzov.IV915yuIROVWfpTYa1CxPKKuAU8Hcet4yUNlLVkfS', 'yonetici'),
  ('Ayşe Yılmaz',       'ayse@simav.edu.tr',     '$2y$12$DqO4SEDjzov.IV915yuIROVWfpTYa1CxPKKuAU8Hcet4yUNlLVkfS', 'uye');

INSERT INTO esya (ad, aciklama, yer, bulunma_tarihi, kategori_id, bulan_id) VALUES
  ('Mavi şemsiye',       'Sapı kırık, katlanabilir',        'Kütüphane',  '2026-09-08', 5, 2),
  ('Kablosuz kulaklık',  'Beyaz kutusunda, marka yazmıyor', 'B Blok 204', '2026-09-09', 1, 2),
  ('Öğrenci kimliği',    'Ad: M. K., 2024 girişli',         'Kantin',     '2026-09-11', 3, 1),
  ('Powerbank',          'Siyah, 10000 mAh',                'Kütüphane',  '2026-09-12', 1, 2),
  ('Matematik defteri',  'Kareli, ilk sayfada isim var',    'A Blok 110', '2026-09-15', 4, 2);

INSERT INTO talep (esya_id, kullanici_id, aciklama) VALUES
  (2, 2, 'Kulaklığımı geçen hafta o sınıfta unutmuştum.');
