<?php
require_once 'config.php';
requireLogin();

$pdo = getConnection();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $nama_vendor = sanitize($_POST['nama_vendor']);
                $kontak_vendor = sanitize($_POST['kontak_vendor']);
                $alamat_vendor = sanitize($_POST['alamat_vendor']);
                $keterangan = sanitize($_POST['keterangan']);

                try {
                    $stmt = $pdo->prepare("INSERT INTO vendor (nama_vendor, kontak_vendor, alamat_vendor, keterangan) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$nama_vendor, $kontak_vendor, $alamat_vendor, $keterangan]);
                    $success = "Vendor berhasil ditambahkan!";
                } catch (PDOException $e) {
                    $error = "Error: " . $e->getMessage();
                }
                break;

            case 'edit':
                $id = (int)$_POST['id'];
                $nama_vendor = sanitize($_POST['nama_vendor']);
                $kontak_vendor = sanitize($_POST['kontak_vendor']);
                $alamat_vendor = sanitize($_POST['alamat_vendor']);
                $keterangan = sanitize($_POST['keterangan']);

                try {
                    $stmt = $pdo->prepare("UPDATE vendor SET nama_vendor = ?, kontak_vendor = ?, alamat_vendor = ?, keterangan = ? WHERE id = ?");
                    $stmt->execute([$nama_vendor, $kontak_vendor, $alamat_vendor, $keterangan, $id]);
                    $success = "Vendor berhasil diupdate!";
                } catch (PDOException $e) {
                    $error = "Error: " . $e->getMessage();
                }
                break;

            case 'delete':
                $id = (int)$_POST['id'];
                try {
                    $stmt = $pdo->prepare("DELETE FROM vendor WHERE id = ?");
                    $stmt->execute([$id]);
                    $success = "Vendor berhasil dihapus!";
                } catch (PDOException $e) {
                    $error = "Error: " . $e->getMessage();
                }
                break;

            case 'add_pj':
                $vendor_id = (int)$_POST['vendor_id'];
                $user_id = (int)$_POST['user_id'];
                $tanggal_mulai_pj = $_POST['tanggal_mulai_pj'];
                $tanggal_selesai_pj = !empty($_POST['tanggal_selesai_pj']) ? $_POST['tanggal_selesai_pj'] : null;
                $keterangan = sanitize($_POST['keterangan']);

                // Validasi tanggal
                if ($tanggal_selesai_pj && $tanggal_selesai_pj <= $tanggal_mulai_pj) {
                    $error = "Tanggal selesai harus lebih besar dari tanggal mulai!";
                } else {
                    // Cek overlap PJ aktif
                    $stmt = $pdo->prepare("
                        SELECT COUNT(*) FROM vendor_penanggung_jawab 
                        WHERE vendor_id = ? AND user_id = ? 
                        AND (tanggal_selesai_pj IS NULL OR tanggal_selesai_pj >= ?)
                        AND tanggal_mulai_pj <= ?
                    ");
                    $check_date = $tanggal_selesai_pj ?: date('Y-m-d', strtotime('+10 years'));
                    $stmt->execute([$vendor_id, $user_id, $tanggal_mulai_pj, $check_date]);

                    if ($stmt->fetchColumn() > 0) {
                        $error = "User sudah menjadi penanggung jawab aktif untuk vendor ini pada periode yang overlap!";
                    } else {
                        try {
                            $stmt = $pdo->prepare("INSERT INTO vendor_penanggung_jawab (vendor_id, user_id, tanggal_mulai_pj, tanggal_selesai_pj, keterangan) VALUES (?, ?, ?, ?, ?)");
                            $stmt->execute([$vendor_id, $user_id, $tanggal_mulai_pj, $tanggal_selesai_pj, $keterangan]);
                            $success = "Penanggung jawab vendor berhasil ditambahkan!";
                        } catch (PDOException $e) {
                            $error = "Error: " . $e->getMessage();
                        }
                    }
                }
                break;

            case 'edit_pj':
                $pj_id = (int)$_POST['pj_id'];
                $user_id = (int)$_POST['user_id'];
                $tanggal_mulai_pj = $_POST['tanggal_mulai_pj'];
                $tanggal_selesai_pj = !empty($_POST['tanggal_selesai_pj']) ? $_POST['tanggal_selesai_pj'] : null;
                $keterangan = sanitize($_POST['keterangan']);

                // Validasi tanggal
                if ($tanggal_selesai_pj && $tanggal_selesai_pj <= $tanggal_mulai_pj) {
                    $error = "Tanggal selesai harus lebih besar dari tanggal mulai!";
                } else {
                    try {
                        $stmt = $pdo->prepare("UPDATE vendor_penanggung_jawab SET user_id = ?, tanggal_mulai_pj = ?, tanggal_selesai_pj = ?, keterangan = ? WHERE id = ?");
                        $stmt->execute([$user_id, $tanggal_mulai_pj, $tanggal_selesai_pj, $keterangan, $pj_id]);
                        $success = "Penanggung jawab berhasil diupdate!";
                    } catch (PDOException $e) {
                        $error = "Error: " . $e->getMessage();
                    }
                }
                break;

            case 'delete_pj':
                $pj_id = (int)$_POST['pj_id'];
                try {
                    $stmt = $pdo->prepare("DELETE FROM vendor_penanggung_jawab WHERE id = ?");
                    $stmt->execute([$pj_id]);
                    $success = "Penanggung jawab berhasil dihapus!";
                } catch (PDOException $e) {
                    $error = "Error: " . $e->getMessage();
                }
                break;
        }
    }
}

// Get all vendors with aggregated data
$stmt = $pdo->prepare("
    SELECT v.*, 
           COUNT(DISTINCT vpj.id) as jumlah_pj,
           COUNT(DISTINCT CASE WHEN vpj.tanggal_selesai_pj IS NULL OR vpj.tanggal_selesai_pj >= CURDATE() THEN vpj.id END) as pj_aktif,
           COUNT(DISTINCT p.id) as jumlah_pengadaan,
           COALESCE(SUM(p.total_biaya_pengadaan + p.biaya_lain_lain), 0) as total_nilai_pengadaan
    FROM vendor v
    LEFT JOIN vendor_penanggung_jawab vpj ON v.id = vpj.vendor_id
    LEFT JOIN pengadaan p ON v.id = p.vendor_id
    GROUP BY v.id
    ORDER BY v.nama_vendor
");
$stmt->execute();
$vendor_list = $stmt->fetchAll();

// Get users for dropdown
$users = getAllUsers();

// Function to get PJ details for a vendor
function getPJDetails($pdo, $vendor_id)
{
    $stmt = $pdo->prepare("
        SELECT vpj.*, u.nama as pj_nama, u.email as pj_email,
               CASE 
                   WHEN vpj.tanggal_selesai_pj IS NULL OR vpj.tanggal_selesai_pj >= CURDATE() 
                   THEN 'Aktif' 
                   ELSE 'Non-Aktif' 
               END as status_pj
        FROM vendor_penanggung_jawab vpj
        JOIN users u ON vpj.user_id = u.id
        WHERE vpj.vendor_id = ?
        ORDER BY vpj.tanggal_mulai_pj DESC
    ");
    $stmt->execute([$vendor_id]);
    return $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Vendor - Inventaris KKN</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/responsive.css" rel="stylesheet">
    <style>
        .status-badge {
            font-size: 0.75rem;
        }

        .pj-info {
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .modal-lg {
            max-width: 900px;
        }

        .table-sm td {
            padding: 0.3rem;
        }
    </style>
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
                    <a class="nav-link" href="proker.php">Program Kerja</a>
                    <a class="nav-link active" href="vendor.php">Vendor</a>
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
                    <h2><i class="fas fa-store"></i> Manajemen Vendor</h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addVendorModal">
                        <i class="fas fa-plus"></i> Tambah Vendor
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
                                        <th>Nama Vendor</th>
                                        <th>Kontak</th>
                                        <th>Alamat</th>
                                        <th>Penanggung Jawab</th>
                                        <th>Jumlah Pengadaan</th>
                                        <th>Total Nilai</th>
                                        <th>Keterangan</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($vendor_list as $index => $vendor): ?>
                                        <tr>
                                            <td><?= $index + 1 ?></td>
                                            <td><strong><?= htmlspecialchars($vendor['nama_vendor']) ?></strong></td>
                                            <td><?= htmlspecialchars($vendor['kontak_vendor']) ?></td>
                                            <td class="pj-info"><?= htmlspecialchars($vendor['alamat_vendor'] ?: '-') ?></td>
                                            <td>
                                                <div class="d-flex flex-column">
                                                    <span class="badge bg-info">Lihat Detail PJ</span>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-info"><?= $vendor['jumlah_pengadaan'] ?> transaksi</span>
                                            </td>
                                            <td><?= formatRupiah($vendor['total_nilai_pengadaan']) ?></td>
                                            <td class="pj-info"><?= htmlspecialchars($vendor['keterangan'] ?: '-') ?></td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button class="btn btn-sm btn-outline-primary" onclick="editVendor(<?= htmlspecialchars(json_encode($vendor)) ?>)" title="Edit Vendor">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-success" onclick="managePJ(<?= $vendor['id'] ?>, '<?= htmlspecialchars($vendor['nama_vendor']) ?>')" title="Kelola Penanggung Jawab">
                                                        <i class="fas fa-users"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-info" onclick="addPJ(<?= $vendor['id'] ?>)" title="Tambah Penanggung Jawab">
                                                        <i class="fas fa-user-plus"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteVendor(<?= $vendor['id'] ?>)" title="Hapus Vendor">
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

    <!-- Add Vendor Modal -->
    <div class="modal fade" id="addVendorModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Vendor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label class="form-label">Nama Vendor <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nama_vendor" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Kontak Vendor <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="kontak_vendor" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Alamat Vendor</label>
                            <textarea class="form-control" name="alamat_vendor" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Keterangan</label>
                            <textarea class="form-control" name="keterangan" rows="3"></textarea>
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

    <!-- Edit Vendor Modal -->
    <div class="modal fade" id="editVendorModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Vendor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="mb-3">
                            <label class="form-label">Nama Vendor <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nama_vendor" id="edit_nama_vendor" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Kontak Vendor <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="kontak_vendor" id="edit_kontak_vendor" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Alamat Vendor</label>
                            <textarea class="form-control" name="alamat_vendor" id="edit_alamat_vendor" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Keterangan</label>
                            <textarea class="form-control" name="keterangan" id="edit_keterangan" rows="3"></textarea>
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

    <!-- Manage PJ Modal -->
    <div class="modal fade" id="managePJModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="managePJTitle">Kelola Penanggung Jawab</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="pjListContainer">
                        <!-- PJ list will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add PJ Modal -->
    <div class="modal fade" id="addPJModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Penanggung Jawab Vendor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_pj">
                        <input type="hidden" name="vendor_id" id="pj_vendor_id">
                        <div class="mb-3">
                            <label class="form-label">Penanggung Jawab <span class="text-danger">*</span></label>
                            <select class="form-select" name="user_id" required>
                                <option value="">Pilih Penanggung Jawab</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['nama']) ?> (<?= htmlspecialchars($user['npm']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Tanggal Mulai <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" name="tanggal_mulai_pj" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Tanggal Selesai</label>
                                    <input type="date" class="form-control" name="tanggal_selesai_pj">
                                    <small class="text-muted">Kosongkan jika tidak ada batas waktu</small>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Keterangan</label>
                            <textarea class="form-control" name="keterangan" rows="3"></textarea>
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

    <!-- Edit PJ Modal -->
    <div class="modal fade" id="editPJModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Penanggung Jawab</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit_pj">
                        <input type="hidden" name="pj_id" id="edit_pj_id">
                        <div class="mb-3">
                            <label class="form-label">Penanggung Jawab <span class="text-danger">*</span></label>
                            <select class="form-select" name="user_id" id="edit_pj_user_id" required>
                                <option value="">Pilih Penanggung Jawab</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['nama']) ?> (<?= htmlspecialchars($user['npm']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Tanggal Mulai <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" name="tanggal_mulai_pj" id="edit_pj_tanggal_mulai" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Tanggal Selesai</label>
                                    <input type="date" class="form-control" name="tanggal_selesai_pj" id="edit_pj_tanggal_selesai">
                                    <small class="text-muted">Kosongkan jika tidak ada batas waktu</small>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Keterangan</label>
                            <textarea class="form-control" name="keterangan" id="edit_pj_keterangan" rows="3"></textarea>
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
        function editVendor(vendor) {
            document.getElementById('edit_id').value = vendor.id;
            document.getElementById('edit_nama_vendor').value = vendor.nama_vendor;
            document.getElementById('edit_kontak_vendor').value = vendor.kontak_vendor;
            document.getElementById('edit_alamat_vendor').value = vendor.alamat_vendor || '';
            document.getElementById('edit_keterangan').value = vendor.keterangan || '';

            new bootstrap.Modal(document.getElementById('editVendorModal')).show();
        }

        function deleteVendor(id) {
            if (confirm('Apakah Anda yakin ingin menghapus vendor ini? Semua data penanggung jawab akan ikut terhapus.')) {
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

        function addPJ(vendorId) {
            document.getElementById('pj_vendor_id').value = vendorId;
            new bootstrap.Modal(document.getElementById('addPJModal')).show();
        }

        function managePJ(vendorId, vendorName) {
            document.getElementById('managePJTitle').textContent = `Kelola Penanggung Jawab - ${vendorName}`;

            // Load PJ data via AJAX
            fetch(`get_pj_details.php?vendor_id=${vendorId}`)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('pjListContainer').innerHTML = html;
                    new bootstrap.Modal(document.getElementById('managePJModal')).show();
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Gagal memuat data penanggung jawab');
                });
        }

        function editPJ(pj) {
            document.getElementById('edit_pj_id').value = pj.id;
            document.getElementById('edit_pj_user_id').value = pj.user_id;
            document.getElementById('edit_pj_tanggal_mulai').value = pj.tanggal_mulai_pj;
            document.getElementById('edit_pj_tanggal_selesai').value = pj.tanggal_selesai_pj || '';
            document.getElementById('edit_pj_keterangan').value = pj.keterangan || '';

            new bootstrap.Modal(document.getElementById('editPJModal')).show();
        }

        function deletePJ(pjId) {
            if (confirm('Apakah Anda yakin ingin menghapus penanggung jawab ini?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_pj">
                    <input type="hidden" name="pj_id" value="${pjId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Auto-refresh PJ list after modal operations
        document.addEventListener('DOMContentLoaded', function() {
            const modals = ['addPJModal', 'editPJModal'];
            modals.forEach(modalId => {
                document.getElementById(modalId).addEventListener('hidden.bs.modal', function() {
                    // Refresh the manage PJ modal if it's open
                    const managePJModal = bootstrap.Modal.getInstance(document.getElementById('managePJModal'));
                    if (managePJModal && managePJModal._isShown) {
                        // Reload the current vendor's PJ data
                        const vendorId = document.getElementById('pj_vendor_id').value;
                        if (vendorId) {
                            fetch(`get_pj_details.php?vendor_id=${vendorId}`)
                                .then(response => response.text())
                                .then(html => {
                                    document.getElementById('pjListContainer').innerHTML = html;
                                });
                        }
                    }
                });
            });
        });
    </script>
</body>

</html>