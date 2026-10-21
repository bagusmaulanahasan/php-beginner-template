<?php
session_start();
require_once "db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'kaprodi') {
    header("Location: dashboard.php");
    exit();
}

$db = new Database();
$conn = $db->getConnection();

$success = "";
$error = "";
$action = isset($_GET['action']) ? $_GET['action'] : 'list';

// Tambah / Edit User
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama = trim($_POST['nama']);
    $username = trim($_POST['username']);
    $role = $_POST['role'];
    $id = isset($_POST['id']) ? $_POST['id'] : '';

    if (!empty($nama) && !empty($username) && !empty($role)) {
        if ($id) {
            if (!empty($_POST['password'])) {
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET nama = :nama, username = :username, password = :password, role = :role WHERE id = :id");
                $stmt->bindParam(":password", $password);
            } else {
                $stmt = $conn->prepare("UPDATE users SET nama = :nama, username = :username, role = :role WHERE id = :id");
            }
            $stmt->bindParam(":nama", $nama);
            $stmt->bindParam(":username", $username);
            $stmt->bindParam(":role", $role);
            $stmt->bindParam(":id", $id);
            if ($stmt->execute()) {
                $_SESSION['success'] = "User berhasil diupdate!";
                header("Location: kaprodi_users.php");
                exit();
            } else {
                $error = "Gagal update user!";
            }
        } else {
            $password = password_hash(!empty($_POST['password']) ? $_POST['password'] : 'password', PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (nama, username, password, role) VALUES (:nama, :username, :password, :role)");
            $stmt->bindParam(":nama", $nama);
            $stmt->bindParam(":username", $username);
            $stmt->bindParam(":password", $password);
            $stmt->bindParam(":role", $role);
            if ($stmt->execute()) {
                $_SESSION['success'] = "User berhasil ditambahkan!";
                header("Location: kaprodi_users.php");
                exit();
            } else {
                $error = "Gagal tambah user!";
            }
        }
    } else {
        $error = "Semua kolom wajib diisi!";
    }
}

// Hapus User
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    $stmt = $conn->prepare("DELETE FROM users WHERE id = :id");
    $stmt->bindParam(":id", $id);
    $stmt->execute();
    $_SESSION['success'] = "User berhasil dihapus!";
    header("Location: kaprodi_users.php");
    exit();
}

// Ambil Data User untuk Edit
$editData = null;
if ($action == 'edit' && isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = :id");
    $stmt->bindParam(":id", $id);
    $stmt->execute();
    $editData = $stmt->fetch(PDO::FETCH_ASSOC);
}

$users = $conn->query("SELECT * FROM users")->fetchAll(PDO::FETCH_ASSOC);

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
    <title>Kelola User - Kaprodi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <style>
        .sidebar { min-height: calc(100vh - 100px); }
    </style>
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
        <!-- Sidebar Tetap Konsisten Lebarnya -->
        <div class="col-md-3 col-lg-2 px-0 mb-4">
            <div class="list-group shadow-sm rounded-0">
                <a href="dashboard.php" class="list-group-item list-group-item-action py-3">Dashboard Utama</a>
                <a href="kaprodi_users.php" class="list-group-item list-group-item-action active py-3">Kelola User (CRUD)</a>
                <a href="profile.php" class="list-group-item list-group-item-action py-3">Profil Pengguna</a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-md-9 col-lg-10 px-md-4">
            <?php if ($action == 'add' || $action == 'edit'): ?>
                <!-- Form Halaman Terpisah untuk Add/Edit -->
                <div class="card shadow-sm p-4 mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="mb-0"><?= $action == 'edit' ? 'Edit User' : 'Tambah User Baru' ?></h4>
                        <a href="kaprodi_users.php" class="btn btn-secondary btn-sm">&larr; Kembali</a>
                    </div>

                    <?php if ($error): ?><div class="alert alert-danger py-2"><?= $error ?></div><?php endif; ?>

                    <form method="POST">
                        <?php if ($editData): ?>
                            <input type="hidden" name="id" value="<?= $editData['id'] ?>">
                        <?php endif; ?>
                        <div class="mb-3">
                            <label class="form-label">Nama Lengkap</label>
                            <input type="text" name="nama" class="form-control" value="<?= $editData['nama'] ?? '' ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" name="username" class="form-control" value="<?= $editData['username'] ?? '' ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password <?= $action == 'edit' ? '(Kosongkan jika tidak diubah)' : '' ?></label>
                            <input type="password" name="password" class="form-control" <?= $action == 'edit' ? '' : 'required' ?>>
                        </div>
                        <div class="mb-4">
                            <label class="form-label">Role</label>
                            <select name="role" class="form-control" required>
                                <option value="kaprodi" <?= (isset($editData) && $editData['role'] == 'kaprodi') ? 'selected' : '' ?>>Kaprodi</option>
                                <option value="dosen" <?= (isset($editData) && $editData['role'] == 'dosen') ? 'selected' : '' ?>>Dosen</option>
                                <option value="mahasiswa" <?= (isset($editData) && $editData['role'] == 'mahasiswa') ? 'selected' : '' ?>>Mahasiswa</option>
                            </select>
                        </div>
                        <!-- Tombol Batal & Simpan di Paling Bawah -->
                        <div class="d-flex justify-content-end gap-2">
                            <a href="kaprodi_users.php" class="btn btn-secondary">Batal</a>
                            <button type="submit" class="btn btn-primary">Simpan</button>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <!-- List / Table View -->
                <div class="card shadow-sm p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="mb-0">Daftar Pengguna Sistem</h4>
                        <a href="kaprodi_users.php?action=add" class="btn btn-primary btn-sm">+ Tambah User</a>
                    </div>

                    <?php if ($success): ?><div class="alert alert-success py-2"><?= $success ?></div><?php endif; ?>

                    <table id="dataTable" class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nama</th>
                                <th>Username</th>
                                <th>Role</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $u): ?>
                            <tr>
                                <td><?= $u['id'] ?></td>
                                <td><?= htmlspecialchars($u['nama']) ?></td>
                                <td><?= htmlspecialchars($u['username']) ?></td>
                                <td><span class="badge bg-secondary"><?= $u['role'] ?></span></td>
                                <td>
                                    <a href="kaprodi_users.php?action=edit&id=<?= $u['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                                    <a href="kaprodi_users.php?hapus=<?= $u['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin hapus user ini?')">Hapus</a>
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
