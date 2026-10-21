<?php
session_start();
require_once "db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'dosen') {
    header("Location: dashboard.php");
    exit();
}

$db = new Database();
$conn = $db->getConnection();

$success = "";
$error = "";
$action = isset($_GET['action']) ? $_GET['action'] : 'list';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $mahasiswa_id = $_POST['mahasiswa_id'];
    $course_id = $_POST['course_id'];
    $nilai = trim($_POST['nilai']);
    $id = isset($_POST['id']) ? $_POST['id'] : '';

    if (!empty($mahasiswa_id) && !empty($course_id) && !empty($nilai)) {
        if ($id) {
            $stmt = $conn->prepare("UPDATE nilai SET mahasiswa_id = :m_id, course_id = :c_id, nilai = :nilai WHERE id = :id");
            $stmt->bindParam(":id", $id);
        } else {
            $stmt = $conn->prepare("INSERT INTO nilai (mahasiswa_id, course_id, nilai) VALUES (:m_id, :c_id, :nilai)");
        }
        $stmt->bindParam(":m_id", $mahasiswa_id);
        $stmt->bindParam(":c_id", $course_id);
        $stmt->bindParam(":nilai", $nilai);

        if ($stmt->execute()) {
            $_SESSION['success'] = "Nilai berhasil disimpan!";
            header("Location: dosen_nilai.php");
            exit();
        } else {
            $error = "Gagal menyimpan nilai!";
        }
    } else {
        $error = "Semua kolom wajib diisi!";
    }
}

if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    $stmt = $conn->prepare("DELETE FROM nilai WHERE id = :id");
    $stmt->bindParam(":id", $id);
    $stmt->execute();
    $_SESSION['success'] = "Nilai berhasil dihapus!";
    header("Location: dosen_nilai.php");
    exit();
}

$editData = null;
if ($action == 'edit' && isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM nilai WHERE id = :id");
    $stmt->bindParam(":id", $id);
    $stmt->execute();
    $editData = $stmt->fetch(PDO::FETCH_ASSOC);
}

$mahasiswa_list = $conn->query("SELECT id, nama FROM users WHERE role = 'mahasiswa'")->fetchAll(PDO::FETCH_ASSOC);
$courses_list = $conn->query("SELECT id, nama_mk FROM courses")->fetchAll(PDO::FETCH_ASSOC);

$query = "SELECT n.id, u.nama as mahasiswa_nama, c.nama_mk, n.nilai 
          FROM nilai n 
          JOIN users u ON n.mahasiswa_id = u.id 
          JOIN courses c ON n.course_id = c.id";
$nilai_list = $conn->query($query)->fetchAll(PDO::FETCH_ASSOC);

if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Nilai - Dosen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
    <div class="container-fluid px-4">
        <a class="navbar-brand" href="dashboard.php">Dashboard Kampus</a>
        <div class="d-flex text-white align-items-center">
            <span class="me-3">Halo, <strong><?= htmlspecialchars($_SESSION['nama']) ?></strong></span>
            <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
        </div>
    </div>
</nav>

<div class="container-fluid px-4">
    <div class="row">
        <div class="col-md-3 col-lg-2 px-0 mb-4">
            <div class="list-group shadow-sm rounded-0">
                <a href="dashboard.php" class="list-group-item list-group-item-action py-3">Dashboard Utama</a>
                <a href="dosen_nilai.php" class="list-group-item list-group-item-action active py-3">Input Nilai (CRUD)</a>
            </div>
        </div>

        <div class="col-md-9 col-lg-10 px-md-4">
            <?php if ($action == 'add' || $action == 'edit'): ?>
                <div class="card shadow-sm p-4 mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="mb-0"><?= $action == 'edit' ? 'Edit Nilai Mahasiswa' : 'Tambah Nilai Mahasiswa' ?></h4>
                        <a href="dosen_nilai.php" class="btn btn-secondary btn-sm">&larr; Kembali</a>
                    </div>

                    <?php if ($error): ?><div class="alert alert-danger py-2"><?= $error ?></div><?php endif; ?>

                    <form method="POST">
                        <?php if ($editData): ?>
                            <input type="hidden" name="id" value="<?= $editData['id'] ?>">
                        <?php endif; ?>
                        <div class="mb-3">
                            <label class="form-label">Mahasiswa</label>
                            <select name="mahasiswa_id" class="form-control" required>
                                <option value="">-- Pilih Mahasiswa --</option>
                                <?php foreach ($mahasiswa_list as $m): ?>
                                    <option value="<?= $m['id'] ?>" <?= (isset($editData) && $editData['mahasiswa_id'] == $m['id']) ? 'selected' : '' ?>><?= $m['nama'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Mata Kuliah</label>
                            <select name="course_id" class="form-control" required>
                                <option value="">-- Pilih Mata Kuliah --</option>
                                <?php foreach ($courses_list as $c): ?>
                                    <option value="<?= $c['id'] ?>" <?= (isset($editData) && $editData['course_id'] == $c['id']) ? 'selected' : '' ?>><?= $c['nama_mk'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="form-label">Nilai Huruf (A, B+, C, dll)</label>
                            <input type="text" name="nilai" class="form-control" value="<?= $editData['nilai'] ?? '' ?>" required>
                        </div>
                        <div class="d-flex justify-content-end gap-2">
                            <a href="dosen_nilai.php" class="btn btn-secondary">Batal</a>
                            <button type="submit" class="btn btn-primary">Simpan</button>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <div class="card shadow-sm p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="mb-0">Daftar Nilai Mahasiswa</h4>
                        <a href="dosen_nilai.php?action=add" class="btn btn-primary btn-sm">+ Input Nilai</a>
                    </div>

                    <?php if ($success): ?><div class="alert alert-success py-2"><?= $success ?></div><?php endif; ?>

                    <table id="dataTable" class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>Mahasiswa</th>
                                <th>Mata Kuliah</th>
                                <th>Nilai</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($nilai_list as $n): ?>
                            <tr>
                                <td><?= htmlspecialchars($n['mahasiswa_nama']) ?></td>
                                <td><?= htmlspecialchars($n['nama_mk']) ?></td>
                                <td><span class="badge bg-success"><?= $n['nilai'] ?></span></td>
                                <td>
                                    <a href="dosen_nilai.php?action=edit&id=<?= $n['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                                    <a href="dosen_nilai.php?hapus=<?= $n['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin hapus nilai ini?')">Hapus</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
    $(document).ready(function() {
        $('#dataTable').DataTable();
    });
</script>
</body>
</html>
