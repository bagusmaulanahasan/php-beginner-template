<?php
session_start();
require_once "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$role = $_SESSION['role'];
$nama = $_SESSION['nama'];

$db = new Database();
$conn = $db->getConnection();

$total_users = 0;
$total_courses = 0;
$total_nilai = 0;

if ($conn) {
    if ($role === 'kaprodi') {
        $total_users = $conn->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $total_courses = $conn->query("SELECT COUNT(*) FROM courses")->fetchColumn();
    } elseif ($role === 'dosen') {
        $total_nilai = $conn->query("SELECT COUNT(*) FROM nilai")->fetchColumn();
        $total_courses = $conn->query("SELECT COUNT(*) FROM courses")->fetchColumn();
    } elseif ($role === 'mahasiswa') {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM nilai WHERE mahasiswa_id = :id");
        $stmt->bindParam(":id", $_SESSION['user_id']);
        $stmt->execute();
        $total_nilai = $stmt->fetchColumn();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?= ucfirst($role) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
    <div class="container-fluid px-4">
        <a class="navbar-brand font-weight-bold" href="#">Dashboard Kampus</a>
        <div class="d-flex align-items-center text-white">
            <span class="me-3">Halo, <strong><?= htmlspecialchars($nama) ?></strong> (<?= strtoupper($role) ?>)</span>
            <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
        </div>
    </div>
</nav>

<div class="container-fluid px-4">
    <div class="row">
        <!-- Sidebar Navigation Konsisten -->
        <div class="col-md-3 col-lg-2 px-0 mb-4">
            <div class="list-group shadow-sm rounded-0">
                <a href="dashboard.php" class="list-group-item list-group-item-action active py-3">Dashboard Utama</a>
                <?php if ($role === 'kaprodi'): ?>
                    <a href="kaprodi_users.php" class="list-group-item list-group-item-action py-3">Kelola User (CRUD)</a>
                <?php elseif ($role === 'dosen'): ?>
                    <a href="dosen_nilai.php" class="list-group-item list-group-item-action py-3">Input Nilai (CRUD)</a>
                <?php elseif ($role === 'mahasiswa'): ?>
                    <a href="mahasiswa_krs.php" class="list-group-item list-group-item-action py-3">KRS Mahasiswa (CRUD)</a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="col-md-9 col-lg-10 px-md-4">
            <div class="card shadow-sm p-4 mb-4">
                <h4 class="mb-3">Selamat Datang, <?= htmlspecialchars($nama) ?>!</h4>
                <p class="text-muted">Anda login sebagai <span class="badge bg-info text-dark"><?= strtoupper($role) ?></span>. Gunakan menu di samping untuk mengelola data sistem akademik.</p>
            </div>

            <!-- Card Statistik -->
            <div class="row">
                <?php if ($role === 'kaprodi'): ?>
                    <div class="col-md-6 mb-3">
                        <div class="card text-white bg-primary shadow-sm p-3">
                            <h5>Total Pengguna</h5>
                            <h3><?= $total_users ?> Orang</h3>
                            <a href="kaprodi_users.php" class="text-white text-decoration-none small mt-2">Kelola User &rarr;</a>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="card text-white bg-success shadow-sm p-3">
                            <h5>Total Mata Kuliah</h5>
                            <h3><?= $total_courses ?> MK</h3>
                            <a href="kaprodi_users.php" class="text-white text-decoration-none small mt-2">Lihat Detail &rarr;</a>
                        </div>
                    </div>
                <?php elseif ($role === 'dosen'): ?>
                    <div class="col-md-6 mb-3">
                        <div class="card text-white bg-primary shadow-sm p-3">
                            <h5>Total Nilai Terinput</h5>
                            <h3><?= $total_nilai ?> Data</h3>
                            <a href="dosen_nilai.php" class="text-white text-decoration-none small mt-2">Kelola Nilai &rarr;</a>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="card text-white bg-success shadow-sm p-3">
                            <h5>Mata Kuliah Aktif</h5>
                            <h3><?= $total_courses ?> MK</h3>
                            <a href="dosen_nilai.php" class="text-white text-decoration-none small mt-2">Lihat Jadwal &rarr;</a>
                        </div>
                    </div>
                <?php elseif ($role === 'mahasiswa'): ?>
                    <div class="col-md-12 mb-3">
                        <div class="card text-white bg-primary shadow-sm p-3">
                            <h5>Mata Kuliah Diambil (KRS)</h5>
                            <h3><?= $total_nilai ?> MK</h3>
                            <a href="mahasiswa_krs.php" class="text-white text-decoration-none small mt-2">Kelola KRS &rarr;</a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
</body>
</html>
