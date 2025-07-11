<?php
require_once 'config.php';
requireLogin();

$pdo = getConnection();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $nama_barang = sanitize($_POST['nama_barang']);
                $jumlah = (int)$_POST['jumlah'];
                $kondisi = sanitize($_POST['kondisi']);
                $status_barang = sanitize($_POST['status_barang']);
                $keterangan = sanitize($_POST['keterangan']);

                try {
                    $stmt = $pdo->prepare("INSERT INTO barang (nama_barang, jumlah, kondisi, status_barang, keterangan) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$nama_barang, $jumlah, $kondisi, $status_barang, $keterangan]);
                    $success = "Barang berhasil ditambahkan!";
                } catch (PDOException $e) {
                    $error = "Error: " . $e->getMessage();
                }
                break;

            case 'edit':
                $id = (int)$_POST['id'];
                $nama_barang = sanitize($_POST['nama_barang']);
                $jumlah = (int)$_POST['jumlah'];
                $kondisi = sanitize($_POST['kondisi']);
                $status_barang = sanitize($_POST['status_barang']);
                $keterangan = sanitize($_POST['keterangan']);

                try {
                    $stmt = $pdo->prepare("UPDATE barang SET nama_barang = ?, jumlah = ?, kondisi = ?, status_barang = ?, keterangan = ? WHERE id = ?");
                    $stmt->execute([$nama_barang, $jumlah, $kondisi, $status_barang, $keterangan, $id]);
                    $success = "Barang berhasil diupdate!";
                } catch (PDOException $e) {
                    $error = "Error: " . $e->getMessage();
                }
                break;

            case 'delete':
                $id = (int)$_POST['id'];
                try {
                    $stmt = $pdo->prepare("DELETE FROM barang WHERE id = ?");
                    $stmt->execute([$id]);
                    $success = "Barang berhasil dihapus!";
                } catch (PDOException $e) {
                    $error = "Error: " . $e->getMessage();
                }
                break;

            case 'add_pj':
                $barang_id = (int)$_POST['barang_id'];
                $user_id = (int)$_POST['user_id'];
                $tanggal_mulai_pj = $_POST['tanggal_mulai_pj'];
                $tanggal_selesai_pj = $_POST['tanggal_selesai_pj'] ?: null;
                $keterangan = sanitize($_POST['keterangan']);

                try {
                    $stmt = $pdo->prepare("INSERT INTO barang_penanggung_jawab (barang_id, user_id, tanggal_mulai_pj, tanggal_selesai_pj, keterangan) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$barang_id, $user_id, $tanggal_mulai_pj, $tanggal_selesai_pj, $keterangan]);
                    $success = "Penanggung jawab berhasil ditambahkan!";
                } catch (PDOException $e) {
                    $error = "Error: " . $e->getMessage();
                }
                break;

            case 'edit_pj':
                $id = (int)$_POST['id'];
                $user_id = (int)$_POST['user_id'];
                $tanggal_mulai_pj = $_POST['tanggal_mulai_pj'];
                $tanggal_selesai_pj = $_POST['tanggal_selesai_pj'] ?: null;
                $keterangan = sanitize($_POST['keterangan']);

                try {
                    $stmt = $pdo->prepare("UPDATE barang_penanggung_jawab SET user_id = ?, tanggal_mulai_pj = ?, tanggal_selesai_pj = ?, keterangan = ? WHERE id = ?");
                    $stmt->execute([$user_id, $tanggal_mulai_pj, $tanggal_selesai_pj, $keterangan, $id]);
                    $success = "Penanggung jawab berhasil diupdate!";
                } catch (PDOException $e) {
                    $error = "Error: " . $e->getMessage();
                }
                break;

            case 'delete_pj':
                $id = (int)$_POST['id'];
                try {
                    $stmt = $pdo->prepare("DELETE FROM barang_penanggung_jawab WHERE id = ?");
                    $stmt->execute([$id]);
                    $success = "Penanggung jawab berhasil dihapus!";
                } catch (PDOException $e) {
                    $error = "Error: " . $e->getMessage();
                }
                break;

            case 'add_proker':
                $barang_id = (int)$_POST['barang_id'];
                $proker_id = (int)$_POST['proker_id'];
                $tanggal_mulai_pakai = $_POST['tanggal_mulai_pakai'];
                $tanggal_selesai_pakai = $_POST['tanggal_selesai_pakai'];
                $jumlah_digunakan = (int)$_POST['jumlah_digunakan'];
                $keterangan = sanitize($_POST['keterangan']);

                try {
                    $stmt = $pdo->prepare("INSERT INTO barang_proker (barang_id, proker_id, tanggal_mulai_pakai, tanggal_selesai_pakai, jumlah_digunakan, keterangan) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$barang_id, $proker_id, $tanggal_mulai_pakai, $tanggal_selesai_pakai, $jumlah_digunakan, $keterangan]);
                    $success = "Barang berhasil ditambahkan ke proker!";
                } catch (PDOException $e) {
                    $error = "Error: " . $e->getMessage();
                }
                break;

            case 'edit_proker':
                $id = (int)$_POST['id'];
                $proker_id = (int)$_POST['proker_id'];
                $tanggal_mulai_pakai = $_POST['tanggal_mulai_pakai'];
                $tanggal_selesai_pakai = $_POST['tanggal_selesai_pakai'];
                $jumlah_digunakan = (int)$_POST['jumlah_digunakan'];
                $keterangan = sanitize($_POST['keterangan']);

                try {
                    $stmt = $pdo->prepare("UPDATE barang_proker SET proker_id = ?, tanggal_mulai_pakai = ?, tanggal_selesai_pakai = ?, jumlah_digunakan = ?, keterangan = ? WHERE id = ?");
                    $stmt->execute([$proker_id, $tanggal_mulai_pakai, $tanggal_selesai_pakai, $jumlah_digunakan, $keterangan, $id]);
                    $success = "Penggunaan proker berhasil diupdate!";
                } catch (PDOException $e) {
                    $error = "Error: " . $e->getMessage();
                }
                break;

            case 'delete_proker':
                $id = (int)$_POST['id'];
                try {
                    $stmt = $pdo->prepare("DELETE FROM barang_proker WHERE id = ?");
                    $stmt->execute([$id]);
                    $success = "Penggunaan proker berhasil dihapus!";
                } catch (PDOException $e) {
                    $error = "Error: " . $e->getMessage();
                }
                break;
        }
    }
}

// Get filter parameters
$filter_proker = isset($_GET['filter_proker']) ? (int)$_GET['filter_proker'] : 0;
$filter_kondisi = isset($_GET['filter_kondisi']) ? sanitize($_GET['filter_kondisi']) : '';
$filter_status = isset($_GET['filter_status']) ? sanitize($_GET['filter_status']) : '';
$search_nama = isset($_GET['search_nama']) ? sanitize($_GET['search_nama']) : '';

// Build WHERE clause for filtering
$where_conditions = [];
$params = [];

// Base query with enhanced filtering and edit/delete capabilities
$base_query = "
    SELECT b.*, 
           GROUP_CONCAT(DISTINCT CONCAT(u.nama, ' (', DATE_FORMAT(bpj.tanggal_mulai_pj, '%d/%m/%Y'), 
                CASE WHEN bpj.tanggal_selesai_pj IS NOT NULL 
                     THEN CONCAT(' - ', DATE_FORMAT(bpj.tanggal_selesai_pj, '%d/%m/%Y'))
                     ELSE ' - Aktif' 
                END, ')') SEPARATOR ', ') as penanggung_jawab,
           GROUP_CONCAT(DISTINCT CONCAT(p.nama_proker, ' (', bp.jumlah_digunakan, ' unit, ', 
                DATE_FORMAT(bp.tanggal_mulai_pakai, '%d/%m/%Y'), ' - ', 
                DATE_FORMAT(bp.tanggal_selesai_pakai, '%d/%m/%Y'), ')') SEPARATOR ', ') as proker_terkait,
           COUNT(DISTINCT bpj.id) as total_pj,
           COUNT(DISTINCT bp.id) as total_proker,
           COALESCE(SUM(pg.total_biaya_pengadaan + COALESCE(pg.biaya_lain_lain, 0)), 0) as total_nilai_pengadaan
    FROM barang b
    LEFT JOIN barang_penanggung_jawab bpj ON b.id = bpj.barang_id
    LEFT JOIN users u ON bpj.user_id = u.id
    LEFT JOIN barang_proker bp ON b.id = bp.barang_id
    LEFT JOIN proker p ON bp.proker_id = p.id
    LEFT JOIN pengadaan pg ON b.id = pg.barang_id
";

// Add filter conditions
if ($filter_proker > 0) {
    $where_conditions[] = "b.id IN (SELECT DISTINCT barang_id FROM barang_proker WHERE proker_id = ?)";
    $params[] = $filter_proker;
}

if (!empty($filter_kondisi)) {
    $where_conditions[] = "b.kondisi = ?";
    $params[] = $filter_kondisi;
}

if (!empty($filter_status)) {
    $where_conditions[] = "b.status_barang = ?";
    $params[] = $filter_status;
}

if (!empty($search_nama)) {
    $where_conditions[] = "b.nama_barang LIKE ?";
    $params[] = '%' . $search_nama . '%';
}

// Combine WHERE conditions
if (!empty($where_conditions)) {
    $base_query .= " WHERE " . implode(" AND ", $where_conditions);
}

$base_query .= " GROUP BY b.id ORDER BY b.nama_barang";

// Execute query
$stmt = $pdo->prepare($base_query);
$stmt->execute($params);
$barang_list = $stmt->fetchAll();

// Get users and proker for dropdowns
$users = getAllUsers();
$proker_list = getAllProker();

// Get statistics for filter info
$total_barang = count($barang_list);
$total_all_barang = $pdo->query("SELECT COUNT(*) FROM barang")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Barang - Inventaris KKN</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/responsive.css" rel="stylesheet">
    <style>
        .filter-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .filter-card .form-control,
        .filter-card .form-select {
            background: rgba(255, 255, 255, 0.9);
            border: none;
            border-radius: 8px;
        }

        .filter-card .btn {
            border-radius: 8px;
            font-weight: 500;
        }

        .stats-card {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
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

        .table-responsive {
            font-size: 0.9em;
        }

        .filter-badge {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8em;
            margin: 2px;
            display: inline-block;
        }

        .clear-filter {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
        }

        .clear-filter:hover {
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }

        .btn-group-vertical .btn {
            margin-bottom: 2px;
        }

        .modal-lg {
            max-width: 900px;
        }

        .list-group-item {
            border: 1px solid #dee2e6;
            margin-bottom: 5px;
            border-radius: 5px;
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
                    <a class="nav-link active" href="barang.php">Barang</a>
                    <a class="nav-link" href="proker.php">Program Kerja</a>
                    <a class="nav-link" href="vendor.php">Vendor</a>
                    <a class="nav-link" href="pengadaan.php">Pengadaan</a>
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
                    <h2><i class="fas fa-boxes"></i> Manajemen Barang Lengkap</h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBarangModal">
                        <i class="fas fa-plus"></i> Tambah Barang
                    </button>
                </div>

                <!-- Filter Card -->
                <div class="filter-card">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <form method="GET" id="filterForm">
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label"><i class="fas fa-project-diagram"></i> Filter Proker</label>
                                        <select class="form-select" name="filter_proker" onchange="submitFilter()">
                                            <option value="">Semua Proker</option>
                                            <?php foreach ($proker_list as $proker): ?>
                                                <option value="<?= $proker['id'] ?>" <?= $filter_proker == $proker['id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($proker['nama_proker']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label"><i class="fas fa-tools"></i> Kondisi</label>
                                        <select class="form-select" name="filter_kondisi" onchange="submitFilter()">
                                            <option value="">Semua Kondisi</option>
                                            <option value="Baik" <?= $filter_kondisi == 'Baik' ? 'selected' : '' ?>>Baik</option>
                                            <option value="Rusak Ringan" <?= $filter_kondisi == 'Rusak Ringan' ? 'selected' : '' ?>>Rusak Ringan</option>
                                            <option value="Rusak Berat" <?= $filter_kondisi == 'Rusak Berat' ? 'selected' : '' ?>>Rusak Berat</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label"><i class="fas fa-flag"></i> Status</label>
                                        <select class="form-select" name="filter_status" onchange="submitFilter()">
                                            <option value="">Semua Status</option>
                                            <option value="Kosong" <?= $filter_status == 'Kosong' ? 'selected' : '' ?>>Kosong</option>
                                            <option value="Tersedia" <?= $filter_status == 'Tersedia' ? 'selected' : '' ?>>Tersedia</option>
                                            <option value="Dipinjam" <?= $filter_status == 'Dipinjam' ? 'selected' : '' ?>>Dipinjam</option>
                                            <option value="Rusak" <?= $filter_status == 'Rusak' ? 'selected' : '' ?>>Rusak</option>
                                            <option value="Hilang" <?= $filter_status == 'Hilang' ? 'selected' : '' ?>>Hilang</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label"><i class="fas fa-search"></i> Cari Nama Barang</label>
                                        <input type="text" class="form-control" name="search_nama" value="<?= htmlspecialchars($search_nama) ?>" placeholder="Ketik nama barang..." onkeyup="delayedSubmit()">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">&nbsp;</label>
                                        <div class="d-grid">
                                            <button type="button" class="btn clear-filter" onclick="clearAllFilters()">
                                                <i class="fas fa-times"></i> Reset
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="col-md-4">
                            <div class="stats-card">
                                <h5><i class="fas fa-chart-bar"></i> Statistik Filter</h5>
                                <p class="mb-1"><strong><?= $total_barang ?></strong> dari <strong><?= $total_all_barang ?></strong> barang</p>
                                <div class="mt-2">
                                    <?php if ($filter_proker > 0): ?>
                                        <?php
                                        $selected_proker = array_filter($proker_list, function ($p) use ($filter_proker) {
                                            return $p['id'] == $filter_proker;
                                        });
                                        $selected_proker = reset($selected_proker);
                                        ?>
                                        <span class="filter-badge">
                                            <i class="fas fa-project-diagram"></i> <?= htmlspecialchars($selected_proker['nama_proker']) ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if (!empty($filter_kondisi)): ?>
                                        <span class="filter-badge">
                                            <i class="fas fa-tools"></i> <?= htmlspecialchars($filter_kondisi) ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if (!empty($filter_status)): ?>
                                        <span class="filter-badge">
                                            <i class="fas fa-flag"></i> <?= htmlspecialchars($filter_status) ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if (!empty($search_nama)): ?>
                                        <span class="filter-badge">
                                            <i class="fas fa-search"></i> "<?= htmlspecialchars($search_nama) ?>"
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
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
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-list"></i> Daftar Barang
                            <span class="badge bg-primary"><?= $total_barang ?> item</span>
                            <?php if ($total_barang != $total_all_barang): ?>
                                <small class="text-muted">(dari total <?= $total_all_barang ?> barang)</small>
                            <?php endif; ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($barang_list)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-search fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">Tidak ada barang yang sesuai dengan filter</h5>
                                <p class="text-muted">Coba ubah kriteria filter atau reset semua filter</p>
                                <button class="btn btn-outline-primary" onclick="clearAllFilters()">
                                    <i class="fas fa-refresh"></i> Reset Filter
                                </button>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>No</th>
                                            <th>Nama Barang</th>
                                            <th>Jumlah</th>
                                            <th>Kondisi</th>
                                            <th>Status</th>
                                            <th>Keterangan</th>
                                            <th>PJ</th>
                                            <th>Proker</th>
                                            <th>Total Nilai</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($barang_list as $index => $barang): ?>
                                            <tr>
                                                <td><?= $index + 1 ?></td>
                                                <td><strong><?= htmlspecialchars($barang['nama_barang']) ?></strong></td>
                                                <td><span class="badge bg-info"><?= $barang['jumlah'] ?></span></td>
                                                <td>
                                                    <span class="badge bg-<?= $barang['kondisi'] == 'Baik' ? 'success' : ($barang['kondisi'] == 'Rusak' ? 'danger' : 'warning') ?>">
                                                        <?= htmlspecialchars($barang['kondisi']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?=
                                                                            $barang['status_barang'] == 'Tersedia' ? 'success' : ($barang['status_barang'] == 'Dipinjam' ? 'warning' : ($barang['status_barang'] == 'Rusak' ? 'danger' : ($barang['status_barang'] == 'Hilang' ? 'dark' : ($barang['status_barang'] == '' || is_null($barang['status_barang']) ? 'secondary' : 'secondary')))) ?>">
                                                        <?= htmlspecialchars($barang['status_barang'] ?: 'Kosong') ?>
                                                    </span>
                                                </td>
                                                <td class="detail-text" title="<?= htmlspecialchars($barang['keterangan']) ?>">
                                                    <?= htmlspecialchars($barang['keterangan'] ?: '-') ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-secondary"><?= $barang['total_pj'] ?> PJ</span>
                                                    <button class="btn btn-sm btn-outline-primary ms-1" onclick="managePJ(<?= $barang['id'] ?>)" title="Kelola PJ">
                                                        <i class="fas fa-users"></i>
                                                    </button>
                                                </td>
                                                <td>
                                                    <span class="badge bg-secondary"><?= $barang['total_proker'] ?> Proker</span>
                                                    <button class="btn btn-sm btn-outline-info ms-1" onclick="manageProker(<?= $barang['id'] ?>)" title="Kelola Proker">
                                                        <i class="fas fa-project-diagram"></i>
                                                    </button>
                                                </td>
                                                <td><?= formatRupiah($barang['total_nilai_pengadaan']) ?></td>
                                                <td>
                                                    <div class="btn-group-vertical" role="group">
                                                        <button class="btn btn-sm btn-outline-primary" onclick="editBarang(<?= htmlspecialchars(json_encode($barang)) ?>)" title="Edit Barang">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-outline-success" onclick="addPJ(<?= $barang['id'] ?>)" title="Tambah PJ">
                                                            <i class="fas fa-user-plus"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-outline-info" onclick="addProker(<?= $barang['id'] ?>)" title="Tambah Proker">
                                                            <i class="fas fa-plus-circle"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteBarang(<?= $barang['id'] ?>)" title="Hapus Barang">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
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
        </div>
    </div>

    <!-- Add Barang Modal -->
    <div class="modal fade" id="addBarangModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Barang</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label class="form-label">Nama Barang</label>
                            <input type="text" class="form-control" name="nama_barang" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Jumlah</label>
                            <input type="number" class="form-control" name="jumlah" min="0" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Kondisi</label>
                            <select class="form-select" name="kondisi" required>
                                <option value="">Pilih Kondisi</option>
                                <option value="Baik">Baik</option>
                                <option value="Rusak Ringan">Rusak Ringan</option>
                                <option value="Rusak Berat">Rusak Berat</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status Barang</label>
                            <select class="form-select" name="status_barang" required>
                                <option value="">Pilih Status</option>
                                <option value="Kosong" <?= $barang['status_barang'] == 'Kosong' ? 'selected' : '' ?>>Kosong</option>
                                <option value="Tersedia">Tersedia</option>
                                <option value="Dipinjam">Dipinjam</option>
                                <option value="Rusak">Rusak</option>
                                <option value="Hilang">Hilang</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Keterangan</label>
                            <textarea class="form-control" name="keterangan" rows="3" placeholder="Keterangan tambahan tentang barang"></textarea>
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

    <!-- Edit Barang Modal -->
    <div class="modal fade" id="editBarangModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Barang</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="mb-3">
                            <label class="form-label">Nama Barang</label>
                            <input type="text" class="form-control" name="nama_barang" id="edit_nama_barang" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Jumlah</label>
                            <input type="number" class="form-control" name="jumlah" id="edit_jumlah" min="0" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Kondisi</label>
                            <select class="form-select" name="kondisi" id="edit_kondisi" required>
                                <option value="">Pilih Kondisi</option>
                                <option value="Baik">Baik</option>
                                <option value="Rusak Ringan">Rusak Ringan</option>
                                <option value="Rusak Berat">Rusak Berat</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status Barang</label>
                            <select class="form-select" name="status_barang" id="edit_status_barang" required>
                                <option value="">Pilih Status</option>
                                <option value="Kosong" <?= $barang['status_barang'] == 'Kosong' ? 'selected' : '' ?>>Kosong</option>
                                <option value="Tersedia">Tersedia</option>
                                <option value="Dipinjam">Dipinjam</option>
                                <option value="Rusak">Rusak</option>
                                <option value="Hilang">Hilang</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Keterangan</label>
                            <textarea class="form-control" name="keterangan" id="edit_keterangan" rows="3" placeholder="Keterangan tambahan tentang barang"></textarea>
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
                    <h5 class="modal-title">Kelola Penanggung Jawab</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-5">
                            <h6>Tambah Penanggung Jawab Baru</h6>
                            <form method="POST">
                                <input type="hidden" name="action" value="add_pj">
                                <input type="hidden" name="barang_id" id="manage_pj_barang_id">
                                <div class="mb-3">
                                    <label class="form-label">Penanggung Jawab</label>
                                    <select class="form-select" name="user_id" required>
                                        <option value="">Pilih User</option>
                                        <?php foreach ($users as $user): ?>
                                            <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['nama']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Tanggal Mulai</label>
                                    <input type="date" class="form-control" name="tanggal_mulai_pj" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Tanggal Selesai (Opsional)</label>
                                    <input type="date" class="form-control" name="tanggal_selesai_pj">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Keterangan</label>
                                    <textarea class="form-control" name="keterangan" rows="2" placeholder="Keterangan penanggung jawab"></textarea>
                                </div>
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-plus"></i> Tambah PJ
                                </button>
                            </form>
                        </div>
                        <div class="col-md-7">
                            <h6>Daftar Penanggung Jawab</h6>
                            <div id="pjList">
                                <!-- PJ list will be loaded here via AJAX -->
                            </div>
                        </div>
                    </div>
                </div>
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
                        <input type="hidden" name="id" id="edit_pj_id">
                        <div class="mb-3">
                            <label class="form-label">Penanggung Jawab</label>
                            <select class="form-select" name="user_id" id="edit_pj_user_id" required>
                                <option value="">Pilih User</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['nama']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tanggal Mulai</label>
                            <input type="date" class="form-control" name="tanggal_mulai_pj" id="edit_pj_tanggal_mulai" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tanggal Selesai (Opsional)</label>
                            <input type="date" class="form-control" name="tanggal_selesai_pj" id="edit_pj_tanggal_selesai">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Keterangan</label>
                            <textarea class="form-control" name="keterangan" id="edit_pj_keterangan" rows="3" placeholder="Keterangan penanggung jawab"></textarea>
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

    <!-- Add PJ Modal (Simple) -->
    <div class="modal fade" id="addPJModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Penanggung Jawab</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_pj">
                        <input type="hidden" name="barang_id" id="pj_barang_id">
                        <div class="mb-3">
                            <label class="form-label">Penanggung Jawab</label>
                            <select class="form-select" name="user_id" required>
                                <option value="">Pilih User</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['nama']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tanggal Mulai</label>
                            <input type="date" class="form-control" name="tanggal_mulai_pj" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tanggal Selesai (Opsional)</label>
                            <input type="date" class="form-control" name="tanggal_selesai_pj">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Keterangan</label>
                            <textarea class="form-control" name="keterangan" rows="3" placeholder="Keterangan penanggung jawab"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success">Tambah</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Manage Proker Modal -->
    <div class="modal fade" id="manageProkerModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Kelola Program Kerja</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-5">
                            <h6>Tambah Penggunaan Proker Baru</h6>
                            <form method="POST">
                                <input type="hidden" name="action" value="add_proker">
                                <input type="hidden" name="barang_id" id="manage_proker_barang_id">
                                <div class="mb-3">
                                    <label class="form-label">Program Kerja</label>
                                    <select class="form-select" name="proker_id" required>
                                        <option value="">Pilih Proker</option>
                                        <?php foreach ($proker_list as $proker): ?>
                                            <option value="<?= $proker['id'] ?>"><?= htmlspecialchars($proker['nama_proker']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Tanggal Mulai Pakai</label>
                                    <input type="date" class="form-control" name="tanggal_mulai_pakai" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Tanggal Selesai Pakai</label>
                                    <input type="date" class="form-control" name="tanggal_selesai_pakai" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Jumlah Digunakan</label>
                                    <input type="number" class="form-control" name="jumlah_digunakan" min="1" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Keterangan</label>
                                    <textarea class="form-control" name="keterangan" rows="2" placeholder="Keterangan penggunaan"></textarea>
                                </div>
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-plus"></i> Tambah Proker
                                </button>
                            </form>
                        </div>
                        <div class="col-md-7">
                            <h6>Daftar Penggunaan Proker</h6>
                            <div id="prokerList">
                                <!-- Proker list will be loaded here via AJAX -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Proker Modal -->
    <div class="modal fade" id="editProkerModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Penggunaan Proker</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit_proker">
                        <input type="hidden" name="id" id="edit_proker_id">
                        <div class="mb-3">
                            <label class="form-label">Program Kerja</label>
                            <select class="form-select" name="proker_id" id="edit_proker_proker_id" required>
                                <option value="">Pilih Proker</option>
                                <?php foreach ($proker_list as $proker): ?>
                                    <option value="<?= $proker['id'] ?>"><?= htmlspecialchars($proker['nama_proker']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tanggal Mulai Pakai</label>
                            <input type="date" class="form-control" name="tanggal_mulai_pakai" id="edit_proker_tanggal_mulai" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tanggal Selesai Pakai</label>
                            <input type="date" class="form-control" name="tanggal_selesai_pakai" id="edit_proker_tanggal_selesai" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Jumlah Digunakan</label>
                            <input type="number" class="form-control" name="jumlah_digunakan" id="edit_proker_jumlah" min="1" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Keterangan</label>
                            <textarea class="form-control" name="keterangan" id="edit_proker_keterangan" rows="3" placeholder="Keterangan penggunaan"></textarea>
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

    <!-- Add Proker Modal (Simple) -->
    <div class="modal fade" id="addProkerModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah ke Program Kerja</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_proker">
                        <input type="hidden" name="barang_id" id="proker_barang_id">
                        <div class="mb-3">
                            <label class="form-label">Program Kerja</label>
                            <select class="form-select" name="proker_id" required>
                                <option value="">Pilih Proker</option>
                                <?php foreach ($proker_list as $proker): ?>
                                    <option value="<?= $proker['id'] ?>"><?= htmlspecialchars($proker['nama_proker']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tanggal Mulai Pakai</label>
                            <input type="date" class="form-control" name="tanggal_mulai_pakai" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tanggal Selesai Pakai</label>
                            <input type="date" class="form-control" name="tanggal_selesai_pakai" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Jumlah Digunakan</label>
                            <input type="number" class="form-control" name="jumlah_digunakan" min="1" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Keterangan</label>
                            <textarea class="form-control" name="keterangan" rows="3" placeholder="Keterangan penggunaan"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success">Tambah</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let searchTimeout;

        // Submit filter form
        function submitFilter() {
            document.getElementById('filterForm').submit();
        }

        // Delayed submit for search input
        function delayedSubmit() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                submitFilter();
            }, 500);
        }

        // Clear all filters
        function clearAllFilters() {
            window.location.href = window.location.pathname;
        }

        // Edit barang function
        function editBarang(barang) {
            document.getElementById('edit_id').value = barang.id;
            document.getElementById('edit_nama_barang').value = barang.nama_barang;
            document.getElementById('edit_jumlah').value = barang.jumlah;
            document.getElementById('edit_kondisi').value = barang.kondisi;
            document.getElementById('edit_status_barang').value = barang.status_barang;
            document.getElementById('edit_keterangan').value = barang.keterangan || '';

            new bootstrap.Modal(document.getElementById('editBarangModal')).show();
        }

        // Delete barang function
        function deleteBarang(id) {
            if (confirm('Apakah Anda yakin ingin menghapus barang ini? Semua data terkait (PJ dan Proker) akan ikut terhapus.')) {
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

        // Add PJ function (simple)
        function addPJ(barangId) {
            document.getElementById('pj_barang_id').value = barangId;
            new bootstrap.Modal(document.getElementById('addPJModal')).show();
        }

        // Add Proker function (simple)
        function addProker(barangId) {
            document.getElementById('proker_barang_id').value = barangId;
            new bootstrap.Modal(document.getElementById('addProkerModal')).show();
        }

        // Manage PJ function (advanced)
        function managePJ(barangId) {
            document.getElementById('manage_pj_barang_id').value = barangId;
            loadPJList(barangId);
            new bootstrap.Modal(document.getElementById('managePJModal')).show();
        }

        // Manage Proker function (advanced)
        function manageProker(barangId) {
            document.getElementById('manage_proker_barang_id').value = barangId;
            loadProkerList(barangId);
            new bootstrap.Modal(document.getElementById('manageProkerModal')).show();
        }

        // Load PJ list via AJAX
        function loadPJList(barangId) {
            fetch(`get_pj_list.php?barang_id=${barangId}`)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('pjList').innerHTML = data;
                })
                .catch(error => {
                    console.error('Error loading PJ list:', error);
                    document.getElementById('pjList').innerHTML = '<p class="text-danger">Error loading data</p>';
                });
        }

        // Load Proker list via AJAX
        function loadProkerList(barangId) {
            fetch(`get_proker_list.php?barang_id=${barangId}`)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('prokerList').innerHTML = data;
                })
                .catch(error => {
                    console.error('Error loading Proker list:', error);
                    document.getElementById('prokerList').innerHTML = '<p class="text-danger">Error loading data</p>';
                });
        }

        // Edit PJ function
        function editPJ(pj) {
            document.getElementById('edit_pj_id').value = pj.id;
            document.getElementById('edit_pj_user_id').value = pj.user_id;
            document.getElementById('edit_pj_tanggal_mulai').value = pj.tanggal_mulai_pj;
            document.getElementById('edit_pj_tanggal_selesai').value = pj.tanggal_selesai_pj || '';
            document.getElementById('edit_pj_keterangan').value = pj.keterangan || '';

            new bootstrap.Modal(document.getElementById('editPJModal')).show();
        }

        // Edit Proker function
        function editProker(proker) {
            document.getElementById('edit_proker_id').value = proker.id;
            document.getElementById('edit_proker_proker_id').value = proker.proker_id;
            document.getElementById('edit_proker_tanggal_mulai').value = proker.tanggal_mulai_pakai;
            document.getElementById('edit_proker_tanggal_selesai').value = proker.tanggal_selesai_pakai;
            document.getElementById('edit_proker_jumlah').value = proker.jumlah_digunakan;
            document.getElementById('edit_proker_keterangan').value = proker.keterangan || '';

            new bootstrap.Modal(document.getElementById('editProkerModal')).show();
        }

        // Delete PJ function
        function deletePJ(id) {
            if (confirm('Apakah Anda yakin ingin menghapus penanggung jawab ini?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_pj">
                    <input type="hidden" name="id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Delete Proker function
        function deleteProker(id) {
            if (confirm('Apakah Anda yakin ingin menghapus penggunaan proker ini?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_proker">
                    <input type="hidden" name="id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Auto-focus search input on page load
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.querySelector('input[name="search_nama"]');
            if (searchInput && !searchInput.value) {
                searchInput.focus();
            }
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl+F to focus search
            if (e.ctrlKey && e.key === 'f') {
                e.preventDefault();
                document.querySelector('input[name="search_nama"]').focus();
            }

            // Escape to clear filters
            if (e.key === 'Escape') {
                clearAllFilters();
            }
        });

        // Auto refresh after modal operations
        document.addEventListener('DOMContentLoaded', function() {
            // Refresh page after successful operations
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('refresh') === '1') {
                // Remove refresh parameter and reload
                urlParams.delete('refresh');
                const newUrl = window.location.pathname + (urlParams.toString() ? '?' + urlParams.toString() : '');
                window.history.replaceState({}, '', newUrl);
            }
        });
    </script>
    <script src="assets/js/responsive.js"></script>
</body>

</html>