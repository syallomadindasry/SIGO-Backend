-- ============================================================
-- SIGO - Sistem Informasi Gudang Obat
-- Cara import: buka phpMyAdmin → buat database "sigo_db" → Import file ini
-- ============================================================

CREATE DATABASE IF NOT EXISTS sigo_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sigo_db;

CREATE TABLE gudang (
  id_gudang   INT AUTO_INCREMENT PRIMARY KEY,
  nama_gudang VARCHAR(100) NOT NULL
);

CREATE TABLE user (
  id_admin  INT AUTO_INCREMENT PRIMARY KEY,
  nama      VARCHAR(100) NOT NULL,
  password  VARCHAR(255) NOT NULL,
  role      ENUM('dinkes','puskesmas') NOT NULL,
  id_gudang INT NOT NULL,
  FOREIGN KEY (id_gudang) REFERENCES gudang(id_gudang)
);

CREATE TABLE data_obat (
  id_obat INT AUTO_INCREMENT PRIMARY KEY,
  nama    VARCHAR(150) NOT NULL,
  satuan  VARCHAR(50)  NOT NULL,
  jenis   VARCHAR(100)
);

CREATE TABLE data_batch (
  id_batch INT AUTO_INCREMENT PRIMARY KEY,
  batch    VARCHAR(100) NOT NULL,
  id_obat  INT NOT NULL,
  exp_date DATE NOT NULL,
  FOREIGN KEY (id_obat) REFERENCES data_obat(id_obat)
);

CREATE TABLE stok_batch (
  id_gudang INT NOT NULL,
  id_batch  INT NOT NULL,
  stok      INT NOT NULL DEFAULT 0,
  PRIMARY KEY (id_gudang, id_batch),
  FOREIGN KEY (id_gudang) REFERENCES gudang(id_gudang),
  FOREIGN KEY (id_batch)  REFERENCES data_batch(id_batch)
);

CREATE TABLE pembelian (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  no_nota      VARCHAR(50)  NOT NULL,
  tanggal      DATE         NOT NULL,
  supplier     VARCHAR(150) NOT NULL,
  alamat       VARCHAR(200),
  kota         VARCHAR(100),
  telepon      VARCHAR(50),
  metode_bayar VARCHAR(50)  DEFAULT 'Transfer Bank',
  diskon       DECIMAL(5,2) DEFAULT 0,
  catatan      TEXT,
  id_admin     INT NOT NULL,
  id_gudang    INT NOT NULL,
  created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_admin)  REFERENCES user(id_admin),
  FOREIGN KEY (id_gudang) REFERENCES gudang(id_gudang)
);

CREATE TABLE pembelian_detail (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  id_pembelian INT NOT NULL,
  id_batch     INT NOT NULL,
  jumlah       INT           NOT NULL,
  harga        DECIMAL(15,2) NOT NULL,
  FOREIGN KEY (id_pembelian) REFERENCES pembelian(id),
  FOREIGN KEY (id_batch)     REFERENCES data_batch(id_batch)
);

CREATE TABLE mutasi (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  no_mutasi  VARCHAR(50) NOT NULL,
  tanggal    DATE        NOT NULL,
  sumber     INT NOT NULL,
  tujuan     INT NOT NULL,
  penyerah   VARCHAR(100),
  penerima   VARCHAR(100),
  catatan    TEXT,
  id_admin   INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (sumber)   REFERENCES gudang(id_gudang),
  FOREIGN KEY (tujuan)   REFERENCES gudang(id_gudang),
  FOREIGN KEY (id_admin) REFERENCES user(id_admin)
);

CREATE TABLE mutasi_detail (
  id        INT AUTO_INCREMENT PRIMARY KEY,
  id_mutasi INT NOT NULL,
  id_batch  INT NOT NULL,
  jumlah    INT NOT NULL,
  FOREIGN KEY (id_mutasi) REFERENCES mutasi(id),
  FOREIGN KEY (id_batch)  REFERENCES data_batch(id_batch)
);

CREATE TABLE pemakaian (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  no_pemakaian VARCHAR(50) NOT NULL,
  tanggal      DATE        NOT NULL,
  keterangan   TEXT,
  id_admin     INT NOT NULL,
  id_gudang    INT NOT NULL,
  created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_admin)  REFERENCES user(id_admin),
  FOREIGN KEY (id_gudang) REFERENCES gudang(id_gudang)
);

CREATE TABLE pemakaian_detail (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  id_pemakaian INT NOT NULL,
  id_batch     INT NOT NULL,
  jumlah       INT NOT NULL,
  FOREIGN KEY (id_pemakaian) REFERENCES pemakaian(id),
  FOREIGN KEY (id_batch)     REFERENCES data_batch(id_batch)
);

CREATE TABLE retur (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  no_retur   VARCHAR(50) NOT NULL,
  tanggal    DATE        NOT NULL,
  alasan     TEXT,
  id_admin   INT NOT NULL,
  id_gudang  INT NOT NULL,
  tujuan     INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_admin)  REFERENCES user(id_admin),
  FOREIGN KEY (id_gudang) REFERENCES gudang(id_gudang),
  FOREIGN KEY (tujuan)    REFERENCES gudang(id_gudang)
);

CREATE TABLE retur_detail (
  id       INT AUTO_INCREMENT PRIMARY KEY,
  id_retur INT NOT NULL,
  id_batch INT NOT NULL,
  jumlah   INT NOT NULL,
  FOREIGN KEY (id_retur) REFERENCES retur(id),
  FOREIGN KEY (id_batch) REFERENCES data_batch(id_batch)
);

CREATE TABLE penghapusan (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  no_hapus   VARCHAR(50) NOT NULL,
  tanggal    DATE        NOT NULL,
  alasan     TEXT        NOT NULL,
  id_admin   INT NOT NULL,
  id_gudang  INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_admin)  REFERENCES user(id_admin),
  FOREIGN KEY (id_gudang) REFERENCES gudang(id_gudang)
);

CREATE TABLE penghapusan_detail (
  id       INT AUTO_INCREMENT PRIMARY KEY,
  id_hapus INT NOT NULL,
  id_batch INT NOT NULL,
  jumlah   INT NOT NULL,
  FOREIGN KEY (id_hapus) REFERENCES penghapusan(id),
  FOREIGN KEY (id_batch) REFERENCES data_batch(id_batch)
);

-- ─── DATA AWAL ───────────────────────────────────────────────

INSERT INTO gudang (nama_gudang) VALUES
  ('Gudang Dinas Kesehatan'), ('Gudang Puskesmas 1'),
  ('Gudang Puskesmas 2'),     ('Gudang Puskesmas 3');

INSERT INTO user (nama, password, role, id_gudang) VALUES
  ('Admin Dinkes','dinkes123','dinkes',   1),
  ('Admin PKM 1', 'pkm1123', 'puskesmas',2),
  ('Admin PKM 2', 'pkm2123', 'puskesmas',3),
  ('Admin PKM 3', 'pkm3123', 'puskesmas',4);

INSERT INTO data_obat (nama, satuan, jenis) VALUES
  ('Paracetamol 500mg','Tablet','Analgesik'),
  ('Amoxicillin 500mg','Kapsul','Antibiotik'),
  ('Antasida Doen',    'Tablet','Antasida'),
  ('Vitamin C 50mg',   'Tablet','Vitamin'),
  ('ORS / Oralit',     'Sachet','Elektrolit'),
  ('Metformin 500mg',  'Tablet','Antidiabetik'),
  ('Captopril 25mg',   'Tablet','Antihipertensi'),
  ('Salbutamol 2mg',   'Tablet','Bronkodilator');

INSERT INTO data_batch (batch, id_obat, exp_date) VALUES
  ('B2023001',1,'2025-12-31'),('B2023002',2,'2024-06-30'),
  ('B2023003',3,'2025-08-31'),('B2023004',4,'2026-01-31'),
  ('B2023005',5,'2024-03-31'),('B2024001',6,'2026-06-30'),
  ('B2024002',7,'2026-09-30'),('B2024003',8,'2025-11-30');

INSERT INTO stok_batch (id_gudang, id_batch, stok) VALUES
  (1,1,5000),(1,2,3000),(1,3,2000),(1,4,4500),
  (1,5,1500),(1,6,80),  (1,7,2200),(1,8,1800),
  (2,1,500), (2,3,200), (3,2,300), (4,4,150);
