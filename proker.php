<?php
require_once 'config.php';
requireLogin();

$pdo = getConnection();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $nama_proker = sanitize($_POST['nama_proker']);
                $divisi = sanitize($_POST['divisi']);
                $tanggal_mulai = $_POST['tanggal_mulai'];
                $tanggal_selesai = $_POST['tanggal_selesai'];
                $lokasi = sanitize($_POST['lokasi']);
                $keterangan = sanitize($_POST['keterangan']);
                
                try {
                    $stmt = $pdo->prepare("INSERT INTO proker (nama_proker, divisi, tanggal_mulai, tanggal_selesai, lokasi, keterangan) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$nama_proker, $divisi, $tanggal_mulai, $tanggal_selesai, $lokasi, $keterangan]);
                    $success = "Program kerja berhasil ditambahkan!";
                } catch (PDOException $e) {
                    $error = "Error: " . $e->getMessage();
                }
                break;
                
            case 'edit':
                $id = (int)$_POST['id'];
                $nama_proker = sanitize($_POST['nama_proker']);
                $divisi = sanitize($_POST['divisi']);
                $tanggal_mulai = $_POST['tanggal_mulai'];
                $tanggal_selesai = $_POST['tanggal_selesai'];
                $lokasi = sanitize($_POST['lokasi']);
                $keterangan = sanitize($_POST['keterangan']);
                
                try {
                    $stmt = $pdo->prepare("UPDATE proker SET nama_proker = ?, divisi = ?, tanggal_mulai = ?, tanggal_selesai = ?, lokasi = ?, keterangan = ? WHERE id = ?");
                    $stmt->execute([$nama_proker, $divisi, $tanggal_mulai, $tanggal_selesai, $lokasi, $keterangan, $id]);
                    $success = "Program kerja berhasil diupdate!";
                } catch (PDOException $e) {
                    $error = "Error: " . $e->getMessage();
                }
                break;
                
            case 'delete':
                $id = (int)$_POST['id'];
                try {
                    $stmt = $pdo->prepare("DELETE FROM proker WHERE id = ?");
                    $stmt->execute([$id]);
                    $success = "Program kerja berhasil dihapus!";
                } catch (PDOException $e) {
                    $error = "Error: " . $e->getMessage();
                }
                break;
        }
    }
}

// Get all proker with related data
$stmt = $pdo->prepare("
    SELECT p.*, 
           COUNT(DISTINCT bp.barang_id) as jumlah_barang,
           GROUP_CONCAT(DISTINCT CONCAT(b.nama_barang, ' (', bp.jumlah_digunakan, ' unit)') SEPARATOR ', ') as barang_terkait
    FROM proker p
    LEFT JOIN barang_proker bp ON p.id = bp.proker_id
    LEFT JOIN barang b ON bp.barang_id = b.id
    GROUP BY p.id
    ORDER BY p.tanggal_mulai DESC
");
$stmt->execute();
$proker_list = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Program Kerja - Inventaris KKN</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/responsive.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-boxes"></i> Inventaris KKN
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <div class="navbar-nav ms-auto">
                <a class="nav-link" href="index.php">Dashboard</a>
                <a class="nav-link" href="barang.php">Barang</a>
                <a class="nav-link active" href="proker.php">Program Kerja</a>
                <a class="nav-link" href="vendor.php">Vendor</a>
                <a class="nav-link" href="pengadaan.php">Pengadaan</a>
                <a class="nav-link" href="laporan.php">Laporan</a>
                <a class="nav-link" href="logout.php">Logout</a>
            </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-project-diagram"></i> Manajemen Program Kerja</h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProkerModal">
                        <i class="fas fa-plus"></i> Tambah Program Kerja
                    </button>
                </div>

                <?php if (isset($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?= $success ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= $error ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>No</th>
                                        <th>Nama Program Kerja</th>
                                        <th>Divisi</th>
                                        <th>Tanggal Mulai</th>
                                        <th>Tanggal Selesai</th>
                                        <th>Lokasi</th>
                                        <th>Jumlah Barang</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($proker_list as $index => $proker): ?>
                                        <?php
                                        $today = date('Y-m-d');
                                        $status = 'Belum Dimulai';
                                        $status_class = 'secondary';
                                        
                                        if ($today >= $proker['tanggal_mulai'] && $today <= $proker['tanggal_selesai']) {
                                            $status = 'Sedang Berlangsung';
                                            $status_class = 'warning';
                                        } elseif ($today > $proker['tanggal_selesai']) {
                                            $status = 'Selesai';
                                            $status_class = 'success';
                                        }
                                        ?>
                                        <tr>
                                            <td><?= $index + 1 ?></td>
                                            <td><?= htmlspecialchars($proker['nama_proker']) ?></td>
                                            <td><?= htmlspecialchars($proker['divisi']) ?></td>
                                            <td><?= formatTanggal($proker['tanggal_mulai']) ?></td>
                                            <td><?= formatTanggal($proker['tanggal_selesai']) ?></td>
                                            <td><?= htmlspecialchars($proker['lokasi']) ?></td>
                                            <td>
                                                <span class="badge bg-info"><?= $proker['jumlah_barang'] ?> item</span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?= $status_class ?>"><?= $status ?></span>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button class="btn btn-sm btn-outline-primary" onclick="editProker(<?= htmlspecialchars(json_encode($proker)) ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-info" onclick="viewDetail(<?= $proker['id'] ?>)">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteProker(<?= $proker['id'] ?>)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Proker Modal -->
    <div class="modal fade" id="addProkerModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Program Kerja</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Nama Program Kerja</label>
                                    <input type="text" class="form-control" name="nama_proker" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Divisi</label>
                                    <input type="text" class="form-control" name="divisi" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Lokasi</label>
                                    <input type="text" class="form-control" name="lokasi" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Tanggal Mulai</label>
                                    <input type="date" class="form-control" name="tanggal_mulai" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Tanggal Selesai</label>
                                    <input type="date" class="form-control" name="tanggal_selesai" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Keterangan</label>
                                    <textarea class="form-control" name="keterangan" rows="3"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Proker Modal -->
    <div class="modal fade" id="editProkerModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Program Kerja</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Nama Program Kerja</label>
                                    <input type="text" class="form-control" name="nama_proker" id="edit_nama_proker" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Divisi</label>
                                    <input type="text" class="form-control" name="divisi" id="edit_divisi" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Lokasi</label>
                                    <input type="text" class="form-control" name="lokasi" id="edit_lokasi" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Tanggal Mulai</label>
                                    <input type="date" class="form-control" name="tanggal_mulai" id="edit_tanggal_mulai" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Tanggal Selesai</label>
                                    <input type="date" class="form-control" name="tanggal_selesai" id="edit_tanggal_selesai" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Keterangan</label>
                                    <textarea class="form-control" name="keterangan" id="edit_keterangan" rows="3"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editProker(proker) {
            document.getElementById('edit_id').value = proker.id;
            document.getElementById('edit_nama_proker').value = proker.nama_proker;
            document.getElementById('edit_divisi').value = proker.divisi;
            document.getElementById('edit_tanggal_mulai').value = proker.tanggal_mulai;
            document.getElementById('edit_tanggal_selesai').value = proker.tanggal_selesai;
            document.getElementById('edit_lokasi').value = proker.lokasi;
            document.getElementById('edit_keterangan').value = proker.keterangan || '';
            
            new bootstrap.Modal(document.getElementById('editProkerModal')).show();
        }

        function viewDetail(id) {
            // Redirect to detail page or show modal with details
            window.location.href = `proker_detail.php?id=${id}`;
        }

        function deleteProker(id) {
            if (confirm('Apakah Anda yakin ingin menghapus program kerja ini?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
    <script src="assets/js/responsive.js"></script>
</body>
</html>

