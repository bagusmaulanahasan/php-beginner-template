<?php
session_start();
require_once "db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mahasiswa') {
    header("Location: dashboard.php");
    exit();
}

$db = new Database();
$conn = $db->getConnection();
$user_id = $_SESSION['user_id'];

$success = "";
$error = "";
$action = isset($_GET['action']) ? $_GET['action'] : 'list';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $course_id = $_POST['course_id'];

    if (!empty($course_id)) {
        $stmt = $conn->prepare("SELECT * FROM nilai WHERE mahasiswa_id = :m_id AND course_id = :c_id");
        $stmt->bindParam(":m_id", $user_id);
        $stmt->bindParam(":c_id", $course_id);
        $stmt->execute();

        if ($stmt->rowCount() == 0) {
            $stmt = $conn->prepare("INSERT INTO nilai (mahasiswa_id, course_id, nilai) VALUES (:m_id, :c_id, '-')");
            $stmt->bindParam(":m_id", $user_id);
            $stmt->bindParam(":c_id", $course_id);
            if ($stmt->execute()) {
                $_SESSION['success'] = "Matakuliah berhasil ditambahkan ke KRS!";
                header("Location: mahasiswa_krs.php");
                exit();
            } else {
                $error = "Gagal mengambil matakuliah!";
            }
        } else {
            $error = "Matakuliah sudah ada di KRS Anda!";
        }
    }
}

if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    $stmt = $conn->prepare("DELETE FROM nilai WHERE id = :id AND mahasiswa_id = :m_id");
    $stmt->bindParam(":id", $id);
    $stmt->bindParam(":m_id", $user_id);
    $stmt->execute();
    $_SESSION['success'] = "Matakuliah KRS berhasil dibatalkan!";
    header("Location: mahasiswa_krs.php");
    exit();
}

$all_courses = $conn->query("SELECT * FROM courses")->fetchAll(PDO::FETCH_ASSOC);

$stmt = $conn->prepare("SELECT n.id, c.kode_mk, c.nama_mk, c.sks, n.nilai 
                        FROM nilai n 
                        JOIN courses c ON n.course_id = c.id 
                        WHERE n.mahasiswa_id = :m_id");
$stmt->bindParam(":m_id", $user_id);
$stmt->execute();
$krs_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    <title>KRS Mahasiswa</title>
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
                <a href="mahasiswa_krs.php" class="list-group-item list-group-item-action active py-3">KRS Mahasiswa (CRUD)</a>
            </div>
        </div>

        <div class="col-md-9 col-lg-10 px-md-4">
            <?php if ($action == 'add'): ?>
                <div class="card shadow-sm p-4 mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="mb-0">Ambil Mata Kuliah Baru</h4>
                        <a href="mahasiswa_krs.php" class="btn btn-secondary btn-sm">&larr; Kembali</a>
                    </div>

                    <?php if ($error): ?><div class="alert alert-danger py-2"><?= $error ?></div><?php endif; ?>

                    <form method="POST">
                        <div class="mb-4">
                            <label class="form-label">Pilih Mata Kuliah</label>
                            <select name="course_id" class="form-control" required>
                                <option value="">-- Pilih Matakuliah --</option>
                                <?php foreach ($all_courses as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= $c['kode_mk'] ?> - <?= $c['nama_mk'] ?> (<?= $c['sks'] ?> SKS)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="d-flex justify-content-end gap-2">
                            <a href="mahasiswa_krs.php" class="btn btn-secondary">Batal</a>
                            <button type="submit" class="btn btn-primary">Simpan</button>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <div class="card shadow-sm p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="mb-0">Daftar KRS & Nilai Anda</h4>
                        <a href="mahasiswa_krs.php?action=add" class="btn btn-primary btn-sm">+ Ambil Matakuliah</a>
                    </div>

                    <?php if ($success): ?><div class="alert alert-success py-2"><?= $success ?></div><?php endif; ?>

                    <table id="dataTable" class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>Kode MK</th>
                                <th>Mata Kuliah</th>
                                <th>SKS</th>
                                <th>Nilai</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($krs_list as $k): ?>
                            <tr>
                                <td><?= htmlspecialchars($k['kode_mk']) ?></td>
                                <td><?= htmlspecialchars($k['nama_mk']) ?></td>
                                <td><?= $k['sks'] ?></td>
                                <td>
                                    <?php if($k['nilai'] == '-'): ?>
                                        <span class="badge bg-secondary">Belum ada</span>
                                    <?php else: ?>
                                        <span class="badge bg-success"><?= $k['nilai'] ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="mahasiswa_krs.php?hapus=<?= $k['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Batalkan matakuliah ini?')">Batalkan</a>
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
