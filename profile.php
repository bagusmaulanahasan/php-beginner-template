<?php
session_start();
require_once "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$role = $_SESSION['role'];
$db = new Database();
$conn = $db->getConnection();
$user_id = $_SESSION['user_id'];
$success = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama = trim($_POST['nama']);
    $password_baru = $_POST['password_baru'];
    $konfirmasi_password = $_POST['konfirmasi_password'];

    if (!empty($nama)) {
        if (!empty($password_baru)) {
            if ($password_baru === $konfirmasi_password) {
                $hashed = password_hash($password_baru, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET nama = :nama, password = :pass WHERE id = :id");
                $stmt->bindParam(":pass", $hashed);
            } else {
                $error = "Konfirmasi password tidak cocok!";
            }
        } else {
            $stmt = $conn->prepare("UPDATE users SET nama = :nama WHERE id = :id");
        }

        if (empty($error)) {
            $stmt->bindParam(":nama", $nama);
            $stmt->bindParam(":id", $user_id);

            if ($stmt->execute()) {
                $_SESSION['nama'] = $nama;
                $success = "Profil berhasil diperbarui!";
            } else {
                $error = "Gagal memperbarui profil.";
            }
        }
    } else {
        $error = "Nama tidak boleh kosong!";
    }
}

$stmt = $conn->prepare("SELECT * FROM users WHERE id = :id");
$stmt->bindParam(":id", $user_id);
$stmt->execute();
$current_user = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Pengguna</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
    <div class="container-fluid px-4">
        <a class="navbar-brand" href="dashboard.php">Dashboard Kampus</a>
        <div class="d-flex align-items-center text-white">
            <span class="me-3">Halo, <strong><?= htmlspecialchars($_SESSION['nama']) ?></strong> (<?= strtoupper($role) ?>)</span>
            <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
        </div>
    </div>
</nav>

<div class="container-fluid px-4">
    <div class="row">
        <!-- Sidebar Navigation Konsisten -->
        <div class="col-md-3 col-lg-2 px-0 mb-4">
            <div class="list-group shadow-sm rounded-0">
                <a href="dashboard.php" class="list-group-item list-group-item-action py-3">Dashboard Utama</a>
                <?php if ($role === 'kaprodi'): ?>
                    <a href="kaprodi_users.php" class="list-group-item list-group-item-action py-3">Kelola User (CRUD)</a>
                <?php elseif ($role === 'dosen'): ?>
                    <a href="dosen_nilai.php" class="list-group-item list-group-item-action py-3">Input Nilai (CRUD)</a>
                <?php elseif ($role === 'mahasiswa'): ?>
                    <a href="mahasiswa_krs.php" class="list-group-item list-group-item-action py-3">KRS Mahasiswa (CRUD)</a>
                <?php endif; ?>
                <a href="profile.php" class="list-group-item list-group-item-action active py-3">Profil Pengguna</a>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="col-md-9 col-lg-10 px-md-4">
            <div class="card shadow-sm p-4 mb-4">
                <h4 class="mb-3">Profil Pengguna</h4>
                <p class="text-muted">Perbarui informasi akun Anda di sini.</p>
            </div>

            <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

            <div class="card shadow-sm p-4" style="max-width: 600px;">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Username (Tidak dapat diubah)</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($current_user['username']) ?>" disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <input type="text" class="form-control text-uppercase" value="<?= htmlspecialchars($current_user['role']) ?>" disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama Lengkap</label>
                        <input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($current_user['nama']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password Baru (Kosongkan jika tidak ingin diubah)</label>
                        <input type="password" name="password_baru" class="form-control" placeholder="******">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Konfirmasi Password Baru</label>
                        <input type="password" name="konfirmasi_password" class="form-control" placeholder="******">
                    </div>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </form>
            </div>
        </div>
    </div>
</div>

</body>
</html>
