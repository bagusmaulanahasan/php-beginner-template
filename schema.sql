CREATE DATABASE IF NOT EXISTS `db_kampus`;
USE `db_kampus`;

CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nama` VARCHAR(100) NOT NULL,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('kaprodi', 'dosen', 'mahasiswa') NOT NULL
);

CREATE TABLE IF NOT EXISTS `courses` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `kode_mk` VARCHAR(20) NOT NULL,
  `nama_mk` VARCHAR(100) NOT NULL,
  `sks` INT NOT NULL
);

CREATE TABLE IF NOT EXISTS `nilai` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `mahasiswa_id` INT NOT NULL,
  `course_id` INT NOT NULL,
  `nilai` VARCHAR(5) NOT NULL
);

-- Default Users (Password: 123456)
INSERT IGNORE INTO `users` (`id`, `nama`, `username`, `password`, `role`) VALUES
(1, 'Dr. Kaprodi, M.T.', 'kaprodi', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'kaprodi'),
(2, 'Dosen Budi, M.Kom', 'dosen', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'dosen'),
(3, 'Mahasiswa Andi', 'mahasiswa', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'mahasiswa');

INSERT IGNORE INTO `courses` (`id`, `kode_mk`, `nama_mk`, `sks`) VALUES
(1, 'IF101', 'Pemrograman Web PHP', 3),
(2, 'IF102', 'Basis Data Lanjut', 3);

INSERT IGNORE INTO `nilai` (`id`, `mahasiswa_id`, `course_id`, `nilai`) VALUES
(1, 3, 1, 'A'),
(2, 3, 2, 'B+');
