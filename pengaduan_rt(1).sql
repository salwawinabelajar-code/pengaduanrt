-- phpMyAdmin SQL Dump
-- version 4.8.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 31 Mar 2026 pada 10.12
-- Versi server: 10.1.36-MariaDB
-- Versi PHP: 7.0.32

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `pengaduan_rt`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `anggota_keluarga`
--

CREATE TABLE `anggota_keluarga` (
  `id` int(11) NOT NULL,
  `kk_id` int(11) NOT NULL,
  `nik` varchar(20) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `tempat_lahir` varchar(50) NOT NULL,
  `tanggal_lahir` date NOT NULL,
  `jenis_kelamin` enum('L','P') NOT NULL,
  `agama` varchar(20) NOT NULL,
  `pendidikan` varchar(50) NOT NULL,
  `pekerjaan` varchar(50) NOT NULL,
  `status_perkawinan` varchar(20) NOT NULL,
  `status_keluarga` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data untuk tabel `anggota_keluarga`
--

INSERT INTO `anggota_keluarga` (`id`, `kk_id`, `nik`, `nama`, `tempat_lahir`, `tanggal_lahir`, `jenis_kelamin`, `agama`, `pendidikan`, `pekerjaan`, `status_perkawinan`, `status_keluarga`, `created_at`, `updated_at`) VALUES
(1, 1, '124556798555553', 'CANDRA SUNARYA', 'JAKARTA', '1998-03-02', 'L', 'ISLAM', 'S1 HUKUM', 'SWASTA', 'SUDAH KAWIN', 'KEPALA KELUARGA', '2026-03-03 04:37:34', '2026-03-03 04:37:34'),
(3, 3, '02134569887978944', 'SALWA NUR ANNISA', 'BANDUNG', '2007-07-23', 'P', 'ISLAM', 'SMK', 'WIRASWASTA', 'SUDAH KAWIN', 'IBU RUMAH TANGGA', '2026-03-10 03:04:50', '2026-03-10 03:04:50'),
(4, 3, '0113148452887834585', 'REZA HARDIANSYAH', 'JAKARTA', '2005-06-07', 'L', 'ISLAM', 'S1 MANAGEMENT', 'WIRASWASTA', 'SUDAH KAWIN', 'KEPALA KELUARHA', '2026-03-10 03:04:50', '2026-03-10 03:04:50'),
(5, 3, '0126852163555650', 'SYAKILA NUR ANNISA', 'JAKARTA', '2025-01-29', 'P', 'ISLAM', 'BELUM SEKOLAH', '-', '-', 'ANAK KE 1', '2026-03-10 03:04:50', '2026-03-10 03:04:50'),
(7, 2, '0132248754458945', 'HERMANSYAH', 'JAKARTA', '1989-02-05', 'L', 'ISLAM', 'S1 HUKUM', 'SWASTA', 'SUDAH KAWIN', 'KEPALA KELUARGA', '2026-03-10 03:05:58', '2026-03-10 03:05:58'),
(8, 4, '02152141257841877', 'DUDI PIRMANTO', 'JAKARTA', '1978-09-03', 'L', 'ISLAM', 'SMK', 'BURUH LEPAS', 'SUDAH KAWIN', 'KEPALA KELUARGA', '2026-03-10 03:19:47', '2026-03-10 03:19:47');

-- --------------------------------------------------------

--
-- Struktur dari tabel `bantuan`
--

CREATE TABLE `bantuan` (
  `id` int(11) NOT NULL,
  `judul` varchar(255) NOT NULL,
  `konten` text NOT NULL,
  `kategori` varchar(50) DEFAULT 'umum',
  `urutan` int(11) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data untuk tabel `bantuan`
--

INSERT INTO `bantuan` (`id`, `judul`, `konten`, `kategori`, `urutan`, `created_at`) VALUES
(1, 'cvzdsfvcd', 'dfvadfda', 'umum', 10, '2026-02-27 08:03:40'),
(2, 'kerja bakti', 'agar bisa apa', 'umum', 3, '2026-02-27 08:31:10'),
(3, 'kerja bakti', 'kjhjhgffyrtiyfyiiyotultuilryifyukfhkguktiudthjhdgjth', 'umum', 0, '2026-03-10 05:46:18');

-- --------------------------------------------------------

--
-- Struktur dari tabel `faq`
--

CREATE TABLE `faq` (
  `id` int(11) NOT NULL,
  `judul` varchar(255) NOT NULL,
  `isi` text NOT NULL,
  `ikon` varchar(50) DEFAULT 'fas fa-question-circle',
  `urutan` int(11) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Struktur dari tabel `galeri`
--

CREATE TABLE `galeri` (
  `id` int(11) NOT NULL,
  `judul` varchar(100) NOT NULL,
  `deskripsi` text,
  `foto` varchar(255) NOT NULL,
  `tanggal` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data untuk tabel `galeri`
--

INSERT INTO `galeri` (`id`, `judul`, `deskripsi`, `foto`, `tanggal`, `created_at`) VALUES
(7, 'KEGIATAN KERJA BAKTI', 'Kerja bakti rutin warga RT 05 Desa Sukamaju ????????\r\n\r\nSetiap hari Sabtu, kami bersama-sama melaksanakan kegiatan kerja bakti sebagai bentuk kepedulian terhadap lingkungan dan kebersamaan antarwarga. Dengan semangat gotong royong, kami membersihkan lingkungan, merapikan fasilitas umum, serta mempererat tali silaturahmi.\r\n\r\nSemoga kegiatan ini terus berjalan secara konsisten dan menjadi contoh positif bagi lingkungan sekitar. Bersama, kita wujudkan Desa Sukamaju yang bersih, sehat, dan nyaman untuk semua ?????\r\n\r\n#KerjaBakti #GotongRoyong #DesaSukamaju #RT05 #LingkunganBersih\r\n', 'uploads/galeri/1774935446_5055.jpg', '2026-02-12', '2026-03-31 05:37:26'),
(8, 'PENYERAHAN BANTUAN UNTUK KORBAN GEMPA CIANJUR', 'Penyerahan Bantuan untuk Korban Gempa Cianjur ????????\r\n\r\nSebagai bentuk kepedulian dan solidaritas, warga RT 05 Desa Sukamaju melaksanakan kegiatan penyerahan bantuan bagi saudara-saudara kita yang terdampak gempa bumi di Cianjur. Bantuan ini merupakan hasil gotong royong dan partisipasi seluruh warga yang dengan tulus ingin meringankan beban para korban.\r\n\r\nSemoga bantuan yang diberikan dapat bermanfaat dan menjadi penyemangat bagi para korban untuk bangkit kembali. Terima kasih kepada seluruh pihak yang telah berkontribusi dalam kegiatan ini.\r\n\r\nBersama kita kuat, bersama kita bangkit ?????\r\n\r\n#PeduliCianjur #BantuanKemanusiaan #GotongRoyong #RT05 #DesaSukamaju\r\n', 'uploads/galeri/1774935547_8251.jpg', '2025-03-31', '2026-03-31 05:39:07'),
(9, 'PEMBERSIHAN GORONG GORONG', 'Kerja Bakti Membersihkan Gorong-Gorong ????????\r\n\r\nWarga RT 05 Desa Sukamaju melaksanakan kegiatan kerja bakti dengan membersihkan gorong-gorong guna menjaga kelancaran saluran air dan mencegah terjadinya banjir. Dengan semangat gotong royong, seluruh warga turut berpartisipasi demi menciptakan lingkungan yang bersih dan sehat.\r\n\r\nKegiatan ini menjadi wujud nyata kepedulian bersama terhadap kebersihan lingkungan sekaligus mempererat kebersamaan antarwarga.\r\n\r\nMari terus jaga lingkungan kita agar tetap nyaman dan bebas dari genangan air ?????\r\n\r\n#KerjaBakti #GotongRoyong #LingkunganBersih #RT05 #DesaSukamaju\r\n', 'uploads/galeri/1774935628_5790.jpg', '2026-03-01', '2026-03-31 05:40:28'),
(10, 'PEMBAGIAN TAKJIL DI SEKITAR JALAN RAYA SUKAMAJU', 'Berbagi Takjil di Bulan Penuh Berkah ?????\r\n\r\nWarga RT 05 Desa Sukamaju melaksanakan kegiatan berbagi takjil kepada masyarakat sebagai bentuk kepedulian dan kebersamaan di bulan suci Ramadan. Dengan penuh keikhlasan, takjil dibagikan kepada para pengguna jalan dan warga sekitar untuk membantu berbuka puasa.\r\n\r\nSemoga kegiatan ini membawa berkah, mempererat tali silaturahmi, serta menumbuhkan rasa saling berbagi antar sesama ????????\r\n\r\n#BerbagiTakjil #RamadanBerkah #GotongRoyong #RT05 #DesaSukamaju\r\n', 'uploads/galeri/1774935700_5477.jpg', '2026-03-10', '2026-03-31 05:41:40'),
(11, 'PENANAMAN TANAMAN HIAS', 'Penanaman Tanaman Hias di Sekitar Rumah Kosong ????????\r\n\r\nWarga RT 05 Desa Sukamaju melaksanakan kegiatan penanaman tanaman hias di area sekitar rumah yang kosong sebagai upaya memperindah lingkungan dan menciptakan suasana yang asri.\r\n\r\nDengan semangat kebersamaan, kegiatan ini tidak hanya membuat lingkungan menjadi lebih hijau dan nyaman, tetapi juga mengurangi kesan kumuh pada lahan yang tidak terpakai.\r\n\r\nMari bersama-sama menjaga dan merawat lingkungan agar tetap indah, bersih, dan menyejukkan ?????\r\n\r\n#Penghijauan #LingkunganAsri #GotongRoyong #RT05 #DesaSukamaju\r\n', 'uploads/galeri/1774935780_6717.jpg', '2026-01-12', '2026-03-31 05:43:00'),
(13, 'PEMBAGIAN BANTUAN', 'Pembagian Bantuan untuk Warga ????????\r\n\r\nWarga RT 05 Desa Sukamaju melaksanakan kegiatan pembagian bantuan sebagai bentuk kepedulian dan solidaritas terhadap sesama. Bantuan ini diberikan kepada warga yang membutuhkan, dengan harapan dapat meringankan beban dan memberikan manfaat bagi penerimanya.\r\n\r\nKegiatan ini menjadi wujud nyata semangat gotong royong dan kebersamaan yang terus dijaga di lingkungan kami. Terima kasih kepada seluruh pihak yang telah berpartisipasi dan mendukung terlaksananya kegiatan ini.\r\n\r\nSemoga kebersamaan ini membawa keberkahan bagi kita semua ?????\r\n\r\n#BantuanSosial #GotongRoyong #Kepedulian #RT05 #DesaSukamaju\r\n', 'uploads/galeri/1774935909_4980.jpg', '2026-02-18', '2026-03-31 05:45:09'),
(14, 'RAPAT BUKBER', 'Rapat Persiapan Buka Bersama (Bukber) ?????\r\n\r\nWarga RT 05 Desa Sukamaju mengadakan rapat untuk membahas persiapan kegiatan buka bersama (bukber). Dalam rapat ini dibahas berbagai hal mulai dari waktu pelaksanaan, tempat, konsumsi, hingga pembagian tugas demi kelancaran acara.\r\n\r\nMelalui musyawarah ini diharapkan kegiatan bukber dapat berjalan dengan lancar, meriah, dan penuh kebersamaan.\r\n\r\nTerima kasih kepada seluruh warga yang telah berpartisipasi dan memberikan ide serta dukungannya ????????\r\n\r\n#RapatWarga #PersiapanBukber #Kebersamaan #RT05 #DesaSukamaju\r\n', 'uploads/galeri/1774935988_3325.jpg', '2026-03-02', '2026-03-31 05:46:28'),
(15, 'SEMARAK BUKBER', 'Semarak Buka Bersama RT 05 Desa Sukamaju ?????\r\n\r\nDalam suasana penuh kebersamaan dan kehangatan, warga RT 05 Desa Sukamaju menggelar acara buka bersama (bukber) yang diikuti oleh seluruh warga. Kegiatan ini menjadi momen untuk mempererat silaturahmi, saling berbagi cerita, serta meningkatkan rasa kekeluargaan antarwarga.\r\n\r\nDengan penuh kebahagiaan, acara berlangsung meriah dan penuh keakraban. Semoga kebersamaan ini terus terjaga dan membawa keberkahan bagi kita semua ????????\r\n\r\n#Bukber #RamadanBerkah #Kebersamaan #RT05 #DesaSukamaju\r\n', 'uploads/galeri/1774936029_5562.jpg', '2026-03-31', '2026-03-31 05:47:09'),
(17, 'RAPAT PERSIAPAN 17 AGUSTU 2025', 'Rapat Persiapan Kegiatan 17 Agustus ?????????\r\n\r\nWarga RT 05 Desa Sukamaju mengadakan rapat untuk membahas persiapan peringatan Hari Kemerdekaan Republik Indonesia. Dalam rapat ini dibahas berbagai agenda kegiatan seperti perlombaan, upacara, hingga acara hiburan, serta pembagian tugas panitia.\r\n\r\nMelalui musyawarah ini diharapkan seluruh rangkaian kegiatan 17 Agustus dapat berjalan lancar, meriah, dan penuh semangat kebersamaan.\r\n\r\nMari bersama-sama kita sukseskan perayaan kemerdekaan dengan penuh semangat dan kekompakan ????????????\r\n\r\n#17Agustus #HUTRI #RapatWarga #GotongRoyong #RT05 #DesaSukamaju\r\n', 'uploads/galeri/1774936259_8647.jpg', '2025-08-25', '2026-03-31 05:50:59'),
(18, 'KEGIATAN SENAM BERSAMA', 'Senam Bersama Warga RT 05 Desa Sukamaju ????????\r\n\r\nWarga RT 05 Desa Sukamaju melaksanakan kegiatan senam bersama sebagai upaya menjaga kesehatan dan kebugaran tubuh. Kegiatan ini diikuti dengan penuh semangat dan keceriaan, sekaligus menjadi ajang mempererat kebersamaan antarwarga.\r\n\r\nDengan tubuh yang sehat, diharapkan kita semua dapat menjalani aktivitas sehari-hari dengan lebih baik dan penuh energi.\r\n\r\nAyo rutin berolahraga demi hidup yang lebih sehat dan bahagia ?????\r\n\r\n#SenamBersama #HidupSehat #Kebersamaan #RT05 #DesaSukamaju\r\n', 'uploads/galeri/1774936323_1429.jpg', '2026-02-01', '2026-03-31 05:52:03'),
(19, 'LOMBA 17 AGUTSU', 'Lomba Meriah 17 Agustus di RT 05 Desa Sukamaju ????????????\r\n\r\nDalam rangka memperingati Hari Kemerdekaan Republik Indonesia, warga RT 05 Desa Sukamaju mengadakan berbagai lomba seru dan meriah. Mulai dari balap karung, tarik tambang, hingga lomba kreatifitas anak-anak, seluruh warga ikut berpartisipasi dengan semangat dan antusiasme tinggi.\r\n\r\nKegiatan lomba ini tidak hanya menghibur, tetapi juga mempererat kebersamaan dan menumbuhkan rasa cinta tanah air. Semoga semangat gotong royong dan kemerdekaan terus hidup di hati kita semua ?????\r\n\r\n#Lomba17Agustus #HUTRI #Kebersamaan #GotongRoyong #RT05 #DesaSukamaju\r\n', 'uploads/galeri/1774936373_4747.jpg', '2025-08-18', '2026-03-31 05:52:53'),
(20, 'LOMBA 17 AGUTSU', 'LOMBA MAKAN KERUPUK', 'uploads/galeri/1774936413_3330.jpg', '2025-08-18', '2026-03-31 05:53:33'),
(21, 'RAPAT KEGIATAN MAULID NABI', 'Rapat Persiapan Maulid Nabi ? ?????\r\n\r\nWarga RT 05 Desa Sukamaju mengadakan rapat untuk membahas persiapan kegiatan peringatan Maulid Nabi Muhammad ?. Dalam rapat ini dibahas berbagai hal, mulai dari susunan acara, pembagian tugas, hingga persiapan konsumsi dan dekorasi.\r\n\r\nDengan musyawarah ini, diharapkan peringatan Maulid Nabi dapat berlangsung khidmat, meriah, dan mempererat tali silaturahmi antarwarga. Semoga kegiatan ini menjadi ladang berkah bagi kita semua ????????\r\n\r\n#MaulidNabi #RapatWarga #Kebersamaan #RT05 #DesaSukamaju\r\n', 'uploads/galeri/1774936540_9004.jpg', '2025-09-26', '2026-03-31 05:55:40'),
(22, 'KEGIATAN MAULID NABI', 'Kegiatan Peringatan Maulid Nabi ? di RT 05 Desa Sukamaju ?????\r\n\r\nWarga RT 05 Desa Sukamaju melaksanakan kegiatan peringatan Maulid Nabi Muhammad ? dengan penuh khidmat. Kegiatan meliputi pembacaan shalawat, ceramah agama, dan doa bersama untuk keselamatan dan kesejahteraan warga.\r\n\r\nAcara ini menjadi momen yang mempererat tali silaturahmi, menumbuhkan semangat keagamaan, serta menanamkan nilai-nilai teladan Nabi ? dalam kehidupan sehari-hari.\r\n\r\nSemoga kegiatan ini membawa keberkahan bagi kita semua dan memperkuat kebersamaan antarwarga ????????\r\n\r\n#MaulidNabi #PeringatanIslam #Kebersamaan #RT05 #DesaSukamaju\r\n', 'uploads/galeri/1774936619_8635.jpg', '2025-09-30', '2026-03-31 05:56:59'),
(23, 'CERAMAH USTAS. HENDRIK IRAWAN', '-', 'uploads/galeri/1774936659_6035.jpg', '2025-09-30', '2026-03-31 05:57:39');

-- --------------------------------------------------------

--
-- Struktur dari tabel `iuran`
--

CREATE TABLE `iuran` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `periode` date NOT NULL,
  `jumlah` int(11) NOT NULL,
  `status` enum('lunas','belum','diproses') DEFAULT 'belum',
  `tanggal_bayar` datetime DEFAULT NULL,
  `bukti_bayar` varchar(255) DEFAULT NULL,
  `metode_bayar` varchar(20) DEFAULT NULL,
  `keterangan` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Struktur dari tabel `iuran_kas`
--

CREATE TABLE `iuran_kas` (
  `id` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `keterangan` text NOT NULL,
  `pemasukan` int(11) DEFAULT '0',
  `pengeluaran` int(11) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data untuk tabel `iuran_kas`
--

INSERT INTO `iuran_kas` (`id`, `tanggal`, `keterangan`, `pemasukan`, `pengeluaran`, `created_at`) VALUES
(1, '2026-03-03', 'minggu ke 1', 150000, 0, '2026-03-03 04:47:19'),
(2, '2026-03-03', 'Iuran mingguan KK', 10000, 0, '2026-03-03 04:47:44'),
(3, '2026-03-10', 'Iuran mingguan KK', 10000, 0, '2026-03-10 03:43:54'),
(4, '2026-03-10', 'Iuran mingguan KK', 10000, 0, '2026-03-10 04:01:34'),
(5, '2026-03-10', 'Iuran mingguan KK', 10000, 0, '2026-03-10 04:01:37'),
(6, '2026-03-10', 'Iuran mingguan KK', 10000, 0, '2026-03-10 04:01:40'),
(7, '2026-03-10', 'Iuran mingguan KK', 10000, 0, '2026-03-10 04:23:02'),
(8, '2026-03-10', 'Iuran mingguan KK', 10000, 0, '2026-03-10 04:23:05'),
(9, '2026-03-10', 'Iuran mingguan KK', 10000, 0, '2026-03-10 04:23:10'),
(10, '2026-03-10', 'Iuran mingguan KK', 10000, 0, '2026-03-10 04:23:14'),
(11, '2026-03-10', 'Iuran mingguan KK', 10000, 0, '2026-03-10 04:28:26'),
(12, '2026-03-10', 'Iuran mingguan KK', 10000, 0, '2026-03-10 04:36:11'),
(13, '2026-03-10', 'Iuran mingguan KK', 10000, 0, '2026-03-10 04:36:44'),
(14, '2026-03-10', 'Iuran mingguan KK', 10000, 0, '2026-03-10 04:36:51'),
(15, '2026-03-31', 'asfssxx', 250000, 0, '2026-03-31 04:31:50'),
(16, '2026-03-31', 'ddfcdfc', 0, 250000, '2026-03-31 04:32:08');

-- --------------------------------------------------------

--
-- Struktur dari tabel `iuran_payments`
--

CREATE TABLE `iuran_payments` (
  `id` int(11) NOT NULL,
  `keluarga_id` int(11) NOT NULL,
  `week_start` date NOT NULL,
  `amount` int(11) NOT NULL DEFAULT '10000',
  `status` enum('lunas','belum') DEFAULT 'belum',
  `payment_date` date DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data untuk tabel `iuran_payments`
--

INSERT INTO `iuran_payments` (`id`, `keluarga_id`, `week_start`, `amount`, `status`, `payment_date`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, '2026-02-28', 10000, 'lunas', '2026-03-03', NULL, '2026-03-03 04:47:44', '2026-03-03 04:47:44'),
(2, 3, '2026-03-07', 10000, 'lunas', '2026-03-10', NULL, '2026-03-10 03:43:54', '2026-03-10 03:43:54'),
(3, 1, '2026-03-07', 10000, 'lunas', '2026-03-10', NULL, '2026-03-10 04:01:34', '2026-03-10 04:01:34'),
(4, 4, '2026-03-07', 10000, 'lunas', '2026-03-10', NULL, '2026-03-10 04:01:37', '2026-03-10 04:01:37'),
(5, 2, '2026-03-07', 10000, 'lunas', '2026-03-10', NULL, '2026-03-10 04:01:40', '2026-03-10 04:01:40'),
(6, 1, '2026-01-03', 10000, 'lunas', '2026-03-10', NULL, '2026-03-10 04:23:02', '2026-03-10 04:23:02'),
(7, 4, '2026-01-03', 10000, 'lunas', '2026-03-10', NULL, '2026-03-10 04:23:05', '2026-03-10 04:23:05'),
(8, 2, '2026-01-03', 10000, 'lunas', '2026-03-10', NULL, '2026-03-10 04:23:10', '2026-03-10 04:23:10'),
(9, 3, '2026-01-03', 10000, 'lunas', '2026-03-10', NULL, '2026-03-10 04:23:14', '2026-03-10 04:23:14'),
(10, 1, '2026-02-14', 10000, 'lunas', '2026-03-10', NULL, '2026-03-10 04:28:26', '2026-03-10 04:28:26'),
(11, 4, '2026-05-02', 10000, 'lunas', '2026-03-10', NULL, '2026-03-10 04:36:11', '2026-03-10 04:36:11'),
(12, 1, '2026-05-09', 10000, 'lunas', '2026-03-10', NULL, '2026-03-10 04:36:44', '2026-03-10 04:36:44'),
(13, 4, '2026-05-09', 10000, 'lunas', '2026-03-10', NULL, '2026-03-10 04:36:51', '2026-03-10 04:36:51');

-- --------------------------------------------------------

--
-- Struktur dari tabel `iuran_saldo`
--

CREATE TABLE `iuran_saldo` (
  `id` int(11) NOT NULL,
  `saldo` int(11) NOT NULL DEFAULT '0',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data untuk tabel `iuran_saldo`
--

INSERT INTO `iuran_saldo` (`id`, `saldo`, `updated_at`) VALUES
(1, 280000, '2026-03-31 04:32:08');

-- --------------------------------------------------------

--
-- Struktur dari tabel `kartu_keluarga`
--

CREATE TABLE `kartu_keluarga` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `no_kk` varchar(20) NOT NULL,
  `alamat` text NOT NULL,
  `rt_rw` varchar(15) NOT NULL,
  `desa_kelurahan` varchar(100) NOT NULL,
  `kecamatan` varchar(100) NOT NULL,
  `kabupaten` varchar(100) NOT NULL,
  `provinsi` varchar(100) NOT NULL,
  `kode_pos` varchar(10) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data untuk tabel `kartu_keluarga`
--

INSERT INTO `kartu_keluarga` (`id`, `user_id`, `no_kk`, `alamat`, `rt_rw`, `desa_kelurahan`, `kecamatan`, `kabupaten`, `provinsi`, `kode_pos`, `created_at`, `updated_at`) VALUES
(1, 15, '00163131984548779', 'JAKARTA', '05/09', 'SUKAMAJU', 'CIMANGGU', 'JAKARTA', 'JAWA BARAT', '04456', '2026-03-03 04:37:34', '2026-03-03 04:37:34'),
(2, 14, '11546445789364455', 'JAKARTA', '05/09', 'SUKAMAJU', 'CIMANGGU', 'JAKARTA', 'JAWA TIMUR', '04456', '2026-03-06 04:23:40', '2026-03-10 03:05:55'),
(3, 16, '01244668698895685562', 'JAKARTA', '05/09', 'SUKAMAJU', 'CIMANGGU', 'JAKARTA', 'JAWA BARAT', '04456', '2026-03-10 03:04:50', '2026-03-10 03:04:50'),
(4, 13, '012235543865752', 'JAKARTA', '05/09', 'SUKAMAJU', 'CIMANGGU', 'JAKARTA', 'JAWA BARAT', '04456', '2026-03-10 03:19:47', '2026-03-10 03:19:47');

-- --------------------------------------------------------

--
-- Struktur dari tabel `kategori_pengaduan`
--

CREATE TABLE `kategori_pengaduan` (
  `id` int(11) NOT NULL,
  `nama` varchar(50) NOT NULL,
  `deskripsi` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data untuk tabel `kategori_pengaduan`
--

INSERT INTO `kategori_pengaduan` (`id`, `nama`, `deskripsi`, `created_at`) VALUES
(1, 'Kebersihan', 'Laporan terkait kebersihan lingkungan, sampah, dll', '2026-02-26 05:36:05'),
(2, 'Keamanan', 'Laporan terkait keamanan, gangguan, dll', '2026-02-26 05:36:05'),
(3, 'Infrastruktur', 'Laporan terkait jalan, lampu, saluran air', '2026-02-26 05:36:05'),
(9, 'miaw', 'miaw', '2026-02-27 07:22:41');

-- --------------------------------------------------------

--
-- Struktur dari tabel `kegiatan`
--

CREATE TABLE `kegiatan` (
  `id` int(11) NOT NULL,
  `nama_kegiatan` varchar(255) NOT NULL,
  `deskripsi` text,
  `tanggal` date NOT NULL,
  `waktu` time DEFAULT NULL,
  `lokasi` varchar(255) DEFAULT NULL,
  `peserta` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Struktur dari tabel `pengaduan`
--

CREATE TABLE `pengaduan` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `no_tiket` varchar(20) NOT NULL,
  `judul` varchar(255) NOT NULL,
  `kategori` varchar(50) NOT NULL,
  `lokasi` varchar(255) DEFAULT NULL,
  `deskripsi` text NOT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `urgensi` enum('rendah','sedang','tinggi') DEFAULT 'sedang',
  `status` enum('baru','diproses','selesai','ditolak') DEFAULT 'baru',
  `tanggal` datetime DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data untuk tabel `pengaduan`
--

INSERT INTO `pengaduan` (`id`, `user_id`, `no_tiket`, `judul`, `kategori`, `lokasi`, `deskripsi`, `foto`, `urgensi`, `status`, `tanggal`, `created_at`) VALUES
(4, 13, 'TKT202602275583', 'saddasd', 'Infrastruktur', 'scfdasd', 'dwdseqdqwkfjhfnhfhfhehdebcdhdyghdjudnhcx', NULL, 'sedang', 'diproses', '2026-02-27 11:58:31', '2026-02-27 04:58:31'),
(5, 13, 'TKT202602273255', 'njkhjlkljjhjkjklkjl', 'administrasi', 'kpokjk;kl;kl;', 'liiho8uyhgiou8yhk,hbhgjuykjfgfthkhj', NULL, 'sedang', 'diproses', '2026-02-27 11:59:02', '2026-02-27 04:59:02'),
(6, 13, 'TKT202602277647', '.bd/.,bvc,l/cvb\\\'lcbv\\\'klgbl\\\';gbl;\\\'gb;\\\\\\\'', 'Keamanan', 'rt 05 ', 'rsfmsmfkkekejeimekreplokeeyigfhioeegppgioio-ofg9u0uydf', 'uploads/pengaduan/foto_1772175384_69a1401899a98.jpg', 'sedang', 'ditolak', '2026-02-27 13:56:24', '2026-02-27 06:56:24'),
(7, 13, 'TKT202602277416', 'kfkfdghyfk', 'miaw', 'rt 05 ', 'q,.emmdfnhaeoujhfkajdhgfiuqaegfk,jasdgvliuadghcmnxgc8ikydaqt', NULL, 'tinggi', 'selesai', '2026-02-27 14:23:15', '2026-02-27 07:23:15'),
(8, 16, 'TKT202603312145', 'kehilangan barang', 'Infrastruktur', 'rt 05 ', 'ini pos ronda yang di belakang makam itu dipake tempat pesta miras, dan pemerkosaan massal, tolong dicegah para remaja sialan itu\\r\\n', 'uploads/pengaduan/foto_1774930666_69cb4aeaa2f24.jpg', 'tinggi', 'baru', '2026-03-31 11:17:46', '2026-03-31 04:17:46');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pengajuan_surat`
--

CREATE TABLE `pengajuan_surat` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `nama_user` varchar(100) NOT NULL,
  `jenis_surat` varchar(100) NOT NULL,
  `keperluan` text NOT NULL,
  `keterangan` text,
  `file_pendukung` varchar(255) DEFAULT NULL,
  `status` enum('menunggu','diproses','selesai','ditolak') DEFAULT 'menunggu',
  `nomor_surat` varchar(50) DEFAULT NULL,
  `tanggal_pengajuan` datetime NOT NULL,
  `tanggal_selesai` datetime DEFAULT NULL,
  `catatan_admin` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `file_hasil` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data untuk tabel `pengajuan_surat`
--

INSERT INTO `pengajuan_surat` (`id`, `user_id`, `nama_user`, `jenis_surat`, `keperluan`, `keterangan`, `file_pendukung`, `status`, `nomor_surat`, `tanggal_pengajuan`, `tanggal_selesai`, `catatan_admin`, `created_at`, `file_hasil`) VALUES
(6, 13, 'Dudi Pirmanto', 'surat pengantar', 'mnvmcbnckcfbcjmdbhxdck', 'gsnjxvxux cjsgsihxnbbvfjhhzjh', NULL, 'selesai', NULL, '2026-02-27 11:59:31', NULL, NULL, '2026-02-27 04:59:31', 'uploads/surat_hasil/surat_1772425955_6.pdf'),
(7, 14, 'hermansyah', 'surat keterangan tidak mampu', 'pengantar untuk membuat surat sktm untuk mendaftar kuliah lewat beasiswa kip', 'di butuhkan segera', NULL, 'selesai', NULL, '2026-03-02 11:25:58', NULL, NULL, '2026-03-02 04:25:58', 'uploads/surat_hasil/surat_1774931421_7.pdf'),
(8, 14, 'hermansyah', 'surat pengantar', 'untuk membuat kk', '-', NULL, 'selesai', NULL, '2026-03-02 11:53:53', NULL, NULL, '2026-03-02 04:53:53', 'uploads/surat_hasil/surat_1772427440_8.pdf'),
(9, 14, 'hermansyah', 'surat pengantar', 'zxsdfszfbfnxncvdnghfdf', 'gssgegesagsdedgsdgds', 'surat_1772429992_69a522a8817df.jpg', 'selesai', NULL, '2026-03-02 12:39:52', NULL, NULL, '2026-03-02 05:39:52', 'uploads/surat_hasil/surat_1774931347_9.pdf'),
(10, 16, 'salwa nur annisa', 'surat keterangan tidak mampu', 'untuk keperluan kuliah', '-', 'surat_1774934603_69cb5a4bd81eb.jpg', 'selesai', NULL, '2026-03-31 12:23:23', NULL, NULL, '2026-03-31 05:23:23', NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `pengumuman`
--

CREATE TABLE `pengumuman` (
  `id` int(11) NOT NULL,
  `judul` varchar(255) NOT NULL,
  `isi` text NOT NULL,
  `tanggal` date NOT NULL,
  `penting` tinyint(4) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data untuk tabel `pengumuman`
--

INSERT INTO `pengumuman` (`id`, `judul`, `isi`, `tanggal`, `penting`, `created_at`, `updated_at`) VALUES
(6, 'KERJA BAKTI', '???? **Pengumuman Kerja Bakti Warga RT 05 Desa Sukamaju** ????????\r\n\r\nDihimbau kepada seluruh warga RT 05 Desa Sukamaju untuk ikut serta dalam kegiatan **kerja bakti rutin** yang akan dilaksanakan pada:\r\n\r\n???? **Hari/Tanggal:** Sabtu, 7 Februari\r\n⏰ **Waktu:** Pukul 07.00 WIB – selesai\r\n???? **Tempat:** Lingkungan RT 05 (sekitar jalan, selokan, dan fasilitas umum)\r\n\r\nKegiatan ini bertujuan untuk menjaga kebersihan lingkungan, mempererat kebersamaan, dan meningkatkan kepedulian terhadap lingkungan sekitar. Mohon hadir tepat waktu dan membawa peralatan kerja bakti seperti sapu, alat penggaruk, dan sarung tangan.\r\n\r\nSemangat gotong royong, bersama kita wujudkan lingkungan yang bersih dan nyaman! ????✨\r\n\r\n#KerjaBakti #GotongRoyong #RT05 #DesaSukamaju\r\n', '2026-02-07', 0, '2026-03-31 06:00:14', '2026-03-31 06:00:14');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nik` varchar(16) DEFAULT NULL,
  `nama` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `no_hp` varchar(15) DEFAULT NULL,
  `alamat` text,
  `role` enum('admin','warga') DEFAULT 'warga',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('aktif','nonaktif') DEFAULT 'aktif'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `nik`, `nama`, `email`, `username`, `password`, `no_hp`, `alamat`, `role`, `created_at`, `status`) VALUES
(5, NULL, 'agus', 'windut56@gmail.com', 'winga', '$2y$10$4MSbU.v1DaM8GnKXlBzGKOeqAadPNv/Gn9ZaR.dqhtd.yQruvtQum', NULL, NULL, 'warga', '2026-02-03 04:04:06', 'aktif'),
(7, NULL, 'windut', 'wina12@gmail.com', 'winaaa', '$2y$10$RK14OMp8nugWPSYajvfBSO8.1wYf7.pzPA71TnfygMcJXUSQH6dK6', NULL, NULL, 'warga', '2026-02-03 04:56:15', 'aktif'),
(8, NULL, 'agus guswara', 'agus@gmail.com', 'Agus', '$2y$10$69Oc3Wa/6U6ZjksYpnTXKeYQEir38fvYPNKsS9PgrBWZ9o.mISTSm', NULL, NULL, 'warga', '2026-02-03 07:08:11', 'aktif'),
(9, NULL, 'nando andiyansyah', 'nando321@gmail.com', 'nando', '$2y$10$ixYq1l1VYTGO6KmEP5Hm/emueKkdg/H3s.y628qRHnCv9.FFyAQN.', NULL, NULL, 'warga', '2026-02-04 00:24:10', 'aktif'),
(10, NULL, 'wina agunis', 'winawindut@gmail.com', 'wina', '$2y$10$mElqMJo8tqtv7W4On4ZoXu3upFWS.Y//yTBFiAZ4qtY9FYGQC.mdS', NULL, NULL, 'warga', '2026-02-04 02:40:28', 'aktif'),
(11, NULL, 'Administrator', 'admin@example.com', 'admin', '$2y$10$YourHashedPasswordHere', NULL, NULL, 'admin', '2026-02-26 05:53:21', 'aktif'),
(12, NULL, 'Admin', 'admin@rt05.id', 'admin', '$2a$12$jBFVZd1gaJRkgJEFw7LIy.jgADp9ghHoHpDjqB2rlhXd8x7jYTgRe', '088223319568', 'kp.sukamaju rt 05', 'admin', '2026-02-26 06:09:26', 'aktif'),
(13, NULL, 'Dudi Pirmanto', 'Dudi123@gmail.com', 'Dudi', '$2y$10$UWuZDRhkovknpdBW6V9S../ADVqXPE6kRnfincOUPHgKtji387a5.', NULL, NULL, 'warga', '2026-02-27 04:35:29', 'aktif'),
(14, NULL, 'hermansyah', 'herman23@gmail.com', 'Herman', '$2y$10$MAVL/hTaEaqTcPUdzTtboedfIU94hLi7rmdWPCzXwR9WrAC/dTHwC', NULL, NULL, 'warga', '2026-03-02 04:24:19', 'aktif'),
(15, NULL, 'candra sunarya', 'candra54@gmail.com', 'Candra', '$2y$10$ul8xKzPqqQ2GGj95e93cOuabMdAO.qCakmGxP21pM9WKntrqc5HlC', NULL, NULL, 'warga', '2026-03-03 03:10:40', 'aktif'),
(16, NULL, 'salwa nur annisa', 'salwa123@gmail.com', 'Salwa', '$2y$10$Kx12kS7KvdopCwylkHk2gORufiOtPNzQjmaqBfu.Pk/oO3EQw8MBO', NULL, NULL, 'warga', '2026-03-10 02:34:21', 'aktif');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `anggota_keluarga`
--
ALTER TABLE `anggota_keluarga`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nik` (`nik`),
  ADD KEY `kk_id` (`kk_id`);

--
-- Indeks untuk tabel `bantuan`
--
ALTER TABLE `bantuan`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `faq`
--
ALTER TABLE `faq`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `galeri`
--
ALTER TABLE `galeri`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `iuran`
--
ALTER TABLE `iuran`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `iuran_kas`
--
ALTER TABLE `iuran_kas`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `iuran_payments`
--
ALTER TABLE `iuran_payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_week` (`keluarga_id`,`week_start`);

--
-- Indeks untuk tabel `iuran_saldo`
--
ALTER TABLE `iuran_saldo`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `kartu_keluarga`
--
ALTER TABLE `kartu_keluarga`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `no_kk` (`no_kk`);

--
-- Indeks untuk tabel `kategori_pengaduan`
--
ALTER TABLE `kategori_pengaduan`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nama` (`nama`);

--
-- Indeks untuk tabel `kegiatan`
--
ALTER TABLE `kegiatan`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `pengaduan`
--
ALTER TABLE `pengaduan`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `no_tiket` (`no_tiket`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `pengajuan_surat`
--
ALTER TABLE `pengajuan_surat`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `pengumuman`
--
ALTER TABLE `pengumuman`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `nik` (`nik`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `anggota_keluarga`
--
ALTER TABLE `anggota_keluarga`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT untuk tabel `bantuan`
--
ALTER TABLE `bantuan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `faq`
--
ALTER TABLE `faq`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `galeri`
--
ALTER TABLE `galeri`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT untuk tabel `iuran`
--
ALTER TABLE `iuran`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `iuran_kas`
--
ALTER TABLE `iuran_kas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT untuk tabel `iuran_payments`
--
ALTER TABLE `iuran_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT untuk tabel `iuran_saldo`
--
ALTER TABLE `iuran_saldo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `kartu_keluarga`
--
ALTER TABLE `kartu_keluarga`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `kategori_pengaduan`
--
ALTER TABLE `kategori_pengaduan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT untuk tabel `kegiatan`
--
ALTER TABLE `kegiatan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `pengaduan`
--
ALTER TABLE `pengaduan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT untuk tabel `pengajuan_surat`
--
ALTER TABLE `pengajuan_surat`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT untuk tabel `pengumuman`
--
ALTER TABLE `pengumuman`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `anggota_keluarga`
--
ALTER TABLE `anggota_keluarga`
  ADD CONSTRAINT `anggota_keluarga_ibfk_1` FOREIGN KEY (`kk_id`) REFERENCES `kartu_keluarga` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `iuran`
--
ALTER TABLE `iuran`
  ADD CONSTRAINT `iuran_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `pengaduan`
--
ALTER TABLE `pengaduan`
  ADD CONSTRAINT `pengaduan_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `pengajuan_surat`
--
ALTER TABLE `pengajuan_surat`
  ADD CONSTRAINT `pengajuan_surat_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
