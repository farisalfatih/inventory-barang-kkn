<?php
require_once 'config.php';
requireLogin();

$pdo = getConnection();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $barang_id = (int)$_POST['barang_id'];
                $vendor_id = !empty($_POST['vendor_id']) ? (int)$_POST['vendor_id'] : null;
                $tipe_pengadaan = sanitize($_POST['tipe_pengadaan']);
                $tanggal_pesan = $_POST['tanggal_pesan'] ?: null;
                $tanggal_terima = $_POST['tanggal_terima'] ?: null;
                $harga_per_item = (float)$_POST['harga_per_item'];
                $jumlah_pengadaan = (int)$_POST['jumlah_pengadaan'];
                $biaya_lain_lain = (float)$_POST['biaya_lain_lain'];
                $dokumen_url = sanitize($_POST['dokumen_url']);
                $keterangan_pengadaan = sanitize($_POST['keterangan_pengadaan']);
                
                // Calculate total biaya
                $total_biaya_pengadaan = ($harga_per_item * $jumlah_pengadaan) + $biaya_lain_lain;
                
                try {
                    $pdo->beginTransaction();
                    
                    // Insert pengadaan
                    $stmt = $pdo->prepare("INSERT INTO pengadaan (barang_id, vendor_id, tipe_pengadaan, tanggal_pesan, tanggal_terima, harga_per_item, jumlah_pengadaan, biaya_lain_lain, total_biaya_pengadaan, dokumen_url, keterangan_pengadaan) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$barang_id, $vendor_id, $tipe_pengadaan, $tanggal_pesan, $tanggal_terima, $harga_per_item, $jumlah_pengadaan, $biaya_lain_lain, $total_biaya_pengadaan, $dokumen_url, $keterangan_pengadaan]);
                    
                    // Update jumlah barang
                    $stmt = $pdo->prepare("UPDATE barang SET jumlah = jumlah + ? WHERE id = ?");
                    $stmt->execute([$jumlah_pengadaan, $barang_id]);
                    
                    $pdo->commit();
                    $success = "Pengadaan berhasil ditambahkan!";
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    $error = "Error: " . $e->getMessage();
                }
                break;
                
            case 'edit':
                $id = (int)$_POST['id'];
                $barang_id = (int)$_POST['barang_id'];
                $vendor_id = !empty($_POST['vendor_id']) ? (int)$_POST['vendor_id'] : null;
                $tipe_pengadaan = sanitize($_POST['tipe_pengadaan']);
                $tanggal_pesan = $_POST['tanggal_pesan'] ?: null;
                $tanggal_terima = $_POST['tanggal_terima'] ?: null;
                $harga_per_item = (float)$_POST['harga_per_item'];
                $jumlah_pengadaan = (int)$_POST['jumlah_pengadaan'];
                $biaya_lain_lain = (float)$_POST['biaya_lain_lain'];
                $dokumen_url = sanitize($_POST['dokumen_url']);
                $keterangan_pengadaan = sanitize($_POST['keterangan_pengadaan']);
                
                // Calculate total biaya
                $total_biaya_pengadaan = ($harga_per_item * $jumlah_pengadaan) + $biaya_lain_lain;
                
                try {
                    $pdo->beginTransaction();
                    
                    // Get old jumlah_pengadaan
                    $stmt = $pdo->prepare("SELECT jumlah_pengadaan, barang_id FROM pengadaan WHERE id = ?");
                    $stmt->execute([$id]);
                    $old_pengadaan = $stmt->fetch();
                    
                    // Update pengadaan
                    $stmt = $pdo->prepare("UPDATE pengadaan SET barang_id = ?, vendor_id = ?, tipe_pengadaan = ?, tanggal_pesan = ?, tanggal_terima = ?, harga_per_item = ?, jumlah_pengadaan = ?, biaya_lain_lain = ?, total_biaya_pengadaan = ?, dokumen_url = ?, keterangan_pengadaan = ? WHERE id = ?");
                    $stmt->execute([$barang_id, $vendor_id, $tipe_pengadaan, $tanggal_pesan, $tanggal_terima, $harga_per_item, $jumlah_pengadaan, $biaya_lain_lain, $total_biaya_pengadaan, $dokumen_url, $keterangan_pengadaan, $id]);
                    
                    // Adjust barang quantities
                    if ($old_pengadaan['barang_id'] == $barang_id) {
                        // Same barang, adjust quantity
                        $diff = $jumlah_pengadaan - $old_pengadaan['jumlah_pengadaan'];
                        $stmt = $pdo->prepare("UPDATE barang SET jumlah = jumlah + ? WHERE id = ?");
                        $stmt->execute([$diff, $barang_id]);
                    } else {
                        // Different barang, subtract from old and add to new
                        $stmt = $pdo->prepare("UPDATE barang SET jumlah = jumlah - ? WHERE id = ?");
                        $stmt->execute([$old_pengadaan['jumlah_pengadaan'], $old_pengadaan['barang_id']]);
                        
                        $stmt = $pdo->prepare("UPDATE barang SET jumlah = jumlah + ? WHERE id = ?");
                        $stmt->execute([$jumlah_pengadaan, $barang_id]);
                    }
                    
                    $pdo->commit();
                    $success = "Pengadaan berhasil diupdate!";
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    $error = "Error: " . $e->getMessage();
                }
                break;
                
            case 'delete':
                $id = (int)$_POST['id'];
                try {
                    $pdo->beginTransaction();
                    
                    // Get pengadaan data
                    $stmt = $pdo->prepare("SELECT jumlah_pengadaan, barang_id FROM pengadaan WHERE id = ?");
                    $stmt->execute([$id]);
                    $pengadaan = $stmt->fetch();
                    
                    // Delete pengadaan
                    $stmt = $pdo->prepare("DELETE FROM pengadaan WHERE id = ?");
                    $stmt->execute([$id]);
                    
                    // Adjust barang quantity
                    $stmt = $pdo->prepare("UPDATE barang SET jumlah = jumlah - ? WHERE id = ?");
                    $stmt->execute([$pengadaan['jumlah_pengadaan'], $pengadaan['barang_id']]);
                    
                    $pdo->commit();
                    $success = "Pengadaan berhasil dihapus!";
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    $error = "Error: " . $e->getMessage();
                }
                break;
        }
    }
}

// Get all pengadaan with related data - IMPROVED to show all columns
$stmt = $pdo->prepare("
    SELECT p.*, b.nama_barang, v.nama_vendor, v.kontak_vendor,
           (p.harga_per_item * p.jumlah_pengadaan) as subtotal_biaya
    FROM pengadaan p
    JOIN barang b ON p.barang_id = b.id
    LEFT JOIN vendor v ON p.vendor_id = v.id
    ORDER BY p.tanggal_terima DESC, p.id DESC
");
$stmt->execute();
$pengadaan_list = $stmt->fetchAll();

// Get barang and vendor for dropdowns
$stmt = $pdo->prepare("SELECT id, nama_barang FROM barang ORDER BY nama_barang");
$stmt->execute();
$barang_list = $stmt->fetchAll();

$vendors = getAllVendor();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Pengadaan - Inventaris KKN</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/responsive.css" rel="stylesheet">
    <style>
        .table-responsive {
            font-size: 0.85em;
        }
        .detail-text {
            max-width: 150px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .detail-text:hover {
            white-space: normal;
            overflow: visible;
        }
        .dokumen-link {
            color: #0d6efd;
            text-decoration: none;
        }
        .dokumen-link:hover {
            text-decoration: underline;
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
                <a class="nav-link" href="vendor.php">Vendor</a>
                <a class="nav-link active" href="pengadaan.php">Pengadaan</a>
                <a class="nav-link" href="laporan.php">Laporan</a>
                <a class="nav-link" href="logout.php">Logout</a>
            </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-shopping-cart"></i> Manajemen Pengadaan</h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPengadaanModal">
                        <i class="fas fa-plus"></i> Tambah Pengadaan
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
                                        <th>Barang</th>
                                        <th>Vendor</th>
                                        <th>Kontak Vendor</th>
                                        <th>Tipe</th>
                                        <th>Tanggal Pesan</th>
                                        <th>Tanggal Terima</th>
                                        <th>Harga/Item</th>
                                        <th>Jumlah</th>
                                        <th>Subtotal</th>
                                        <th>Biaya Lain</th>
                                        <th>Total Biaya</th>
                                        <th>Dokumen</th>
                                        <th>Keterangan</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pengadaan_list as $index => $pengadaan): ?>
                                        <tr>
                                            <td><?= $index + 1 ?></td>
                                            <td><?= htmlspecialchars($pengadaan['nama_barang']) ?></td>
                                            <td><?= htmlspecialchars($pengadaan['nama_vendor'] ?: 'Tidak ada vendor') ?></td>
                                            <td class="detail-text" title="<?= htmlspecialchars($pengadaan['kontak_vendor']) ?>">
                                                <?= htmlspecialchars($pengadaan['kontak_vendor'] ?: '-') ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?= $pengadaan['tipe_pengadaan'] == 'Beli' ? 'success' : ($pengadaan['tipe_pengadaan'] == 'Pinjam' ? 'warning' : 'info') ?>">
                                                    <?= htmlspecialchars($pengadaan['tipe_pengadaan']) ?>
                                                </span>
                                            </td>
                                            <td><?= $pengadaan['tanggal_pesan'] ? formatTanggal($pengadaan['tanggal_pesan']) : '-' ?></td>
                                            <td><?= $pengadaan['tanggal_terima'] ? formatTanggal($pengadaan['tanggal_terima']) : '-' ?></td>
                                            <td><?= formatRupiah($pengadaan['harga_per_item']) ?></td>
                                            <td><?= $pengadaan['jumlah_pengadaan'] ?></td>
                                            <td><?= formatRupiah($pengadaan['subtotal_biaya']) ?></td>
                                            <td><?= formatRupiah($pengadaan['biaya_lain_lain']) ?></td>
                                            <td><strong><?= formatRupiah($pengadaan['total_biaya_pengadaan']) ?></strong></td>
                                            <td>
                                                <?php if ($pengadaan['dokumen_url']): ?>
                                                    <a href="<?= htmlspecialchars($pengadaan['dokumen_url']) ?>" target="_blank" class="dokumen-link" title="Buka dokumen">
                                                        <i class="fas fa-file-alt"></i> Dokumen
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="detail-text" title="<?= htmlspecialchars($pengadaan['keterangan_pengadaan']) ?>">
                                                <?= htmlspecialchars($pengadaan['keterangan_pengadaan'] ?: '-') ?>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button class="btn btn-sm btn-outline-primary" onclick="editPengadaan(<?= htmlspecialchars(json_encode($pengadaan)) ?>)" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger" onclick="deletePengadaan(<?= $pengadaan['id'] ?>)" title="Hapus">
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

    <!-- Add Pengadaan Modal -->
    <div class="modal fade" id="addPengadaanModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Pengadaan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Barang</label>
                                    <select class="form-select" name="barang_id" required>
                                        <option value="">Pilih Barang</option>
                                        <?php foreach ($barang_list as $barang): ?>
                                            <option value="<?= $barang['id'] ?>"><?= htmlspecialchars($barang['nama_barang']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Vendor (Opsional)</label>
                                    <select class="form-select" name="vendor_id">
                                        <option value="">Pilih Vendor</option>
                                        <?php foreach ($vendors as $vendor): ?>
                                            <option value="<?= $vendor['id'] ?>"><?= htmlspecialchars($vendor['nama_vendor']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Tipe Pengadaan</label>
                                    <select class="form-select" name="tipe_pengadaan" required>
                                        <option value="">Pilih Tipe</option>
                                        <option value="Beli">Beli</option>
                                        <option value="Pinjam">Pinjam</option>
                                        <option value="Sewa">Sewa</option>
                                        <option value="Hibah">Hibah</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Tanggal Pesan</label>
                                    <input type="date" class="form-control" name="tanggal_pesan">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Tanggal Terima</label>
                                    <input type="date" class="form-control" name="tanggal_terima">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Harga per Item</label>
                                    <input type="number" class="form-control" name="harga_per_item" min="0" step="0.01" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Jumlah Pengadaan</label>
                                    <input type="number" class="form-control" name="jumlah_pengadaan" min="1" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Biaya Lain-lain</label>
                                    <input type="number" class="form-control" name="biaya_lain_lain" min="0" step="0.01" value="0">
                                    <div class="form-text">Biaya tambahan seperti ongkir, pajak, dll.</div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">URL Dokumen</label>
                                    <input type="url" class="form-control" name="dokumen_url" placeholder="https://...">
                                    <div class="form-text">Link ke dokumen terkait (invoice, kontrak, dll.)</div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Keterangan</label>
                                    <textarea class="form-control" name="keterangan_pengadaan" rows="3" placeholder="Keterangan tambahan"></textarea>
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

    <!-- Edit Pengadaan Modal -->
    <div class="modal fade" id="editPengadaanModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Pengadaan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Barang</label>
                                    <select class="form-select" name="barang_id" id="edit_barang_id" required>
                                        <option value="">Pilih Barang</option>
                                        <?php foreach ($barang_list as $barang): ?>
                                            <option value="<?= $barang['id'] ?>"><?= htmlspecialchars($barang['nama_barang']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Vendor (Opsional)</label>
                                    <select class="form-select" name="vendor_id" id="edit_vendor_id">
                                        <option value="">Pilih Vendor</option>
                                        <?php foreach ($vendors as $vendor): ?>
                                            <option value="<?= $vendor['id'] ?>"><?= htmlspecialchars($vendor['nama_vendor']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Tipe Pengadaan</label>
                                    <select class="form-select" name="tipe_pengadaan" id="edit_tipe_pengadaan" required>
                                        <option value="">Pilih Tipe</option>
                                        <option value="Beli">Beli</option>
                                        <option value="Pinjam">Pinjam</option>
                                        <option value="Sewa">Sewa</option>
                                        <option value="Hibah">Hibah</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Tanggal Pesan</label>
                                    <input type="date" class="form-control" name="tanggal_pesan" id="edit_tanggal_pesan">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Tanggal Terima</label>
                                    <input type="date" class="form-control" name="tanggal_terima" id="edit_tanggal_terima">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Harga per Item</label>
                                    <input type="number" class="form-control" name="harga_per_item" id="edit_harga_per_item" min="0" step="0.01" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Jumlah Pengadaan</label>
                                    <input type="number" class="form-control" name="jumlah_pengadaan" id="edit_jumlah_pengadaan" min="1" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Biaya Lain-lain</label>
                                    <input type="number" class="form-control" name="biaya_lain_lain" id="edit_biaya_lain_lain" min="0" step="0.01">
                                    <div class="form-text">Biaya tambahan seperti ongkir, pajak, dll.</div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">URL Dokumen</label>
                                    <input type="url" class="form-control" name="dokumen_url" id="edit_dokumen_url" placeholder="https://...">
                                    <div class="form-text">Link ke dokumen terkait (invoice, kontrak, dll.)</div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Keterangan</label>
                                    <textarea class="form-control" name="keterangan_pengadaan" id="edit_keterangan_pengadaan" rows="3" placeholder="Keterangan tambahan"></textarea>
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
        function editPengadaan(pengadaan) {
            document.getElementById('edit_id').value = pengadaan.id;
            document.getElementById('edit_barang_id').value = pengadaan.barang_id;
            document.getElementById('edit_vendor_id').value = pengadaan.vendor_id || '';
            document.getElementById('edit_tipe_pengadaan').value = pengadaan.tipe_pengadaan;
            document.getElementById('edit_tanggal_pesan').value = pengadaan.tanggal_pesan || '';
            document.getElementById('edit_tanggal_terima').value = pengadaan.tanggal_terima || '';
            document.getElementById('edit_harga_per_item').value = pengadaan.harga_per_item;
            document.getElementById('edit_jumlah_pengadaan').value = pengadaan.jumlah_pengadaan;
            document.getElementById('edit_biaya_lain_lain').value = pengadaan.biaya_lain_lain || 0;
            document.getElementById('edit_dokumen_url').value = pengadaan.dokumen_url || '';
            document.getElementById('edit_keterangan_pengadaan').value = pengadaan.keterangan_pengadaan || '';
            
            new bootstrap.Modal(document.getElementById('editPengadaanModal')).show();
        }

        function deletePengadaan(id) {
            if (confirm('Apakah Anda yakin ingin menghapus pengadaan ini? Jumlah barang akan dikurangi sesuai dengan jumlah pengadaan.')) {
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

