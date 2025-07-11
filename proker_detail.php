<?php
require_once 'config.php';
requireLogin();

$pdo = getConnection();

// Get proker ID from URL parameter
$proker_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($proker_id <= 0) {
    header('Location: proker.php');
    exit;
}

// Get proker details
$stmt = $pdo->prepare("SELECT * FROM proker WHERE id = ?");
$stmt->execute([$proker_id]);
$proker = $stmt->fetch();

if (!$proker) {
    header('Location: proker.php');
    exit;
}

// Get barang yang digunakan dalam proker ini beserta penanggung jawabnya
$stmt = $pdo->prepare("
    SELECT 
        b.id as barang_id,
        b.nama_barang,
        b.jumlah as total_barang,
        b.kondisi,
        b.status_barang,
        bp.jumlah_digunakan,
        bp.tanggal_mulai_pakai,
        bp.tanggal_selesai_pakai,
        bp.keterangan as keterangan_pemakaian,
        u.nama as penanggung_jawab,
        u.npm,
        bpj.tanggal_mulai_pj,
        bpj.tanggal_selesai_pj
    FROM barang_proker bp
    JOIN barang b ON bp.barang_id = b.id
    LEFT JOIN barang_penanggung_jawab bpj ON b.id = bpj.barang_id
    LEFT JOIN users u ON bpj.user_id = u.id
    WHERE bp.proker_id = ?
    ORDER BY b.nama_barang
");
$stmt->execute([$proker_id]);
$barang_list = $stmt->fetchAll();

// Calculate status
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

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Program Kerja - <?= htmlspecialchars($proker['nama_proker']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/responsive.css" rel="stylesheet">
    <style>
        .detail-card {
            border-left: 4px solid #0d6efd;
        }

        .info-item {
            border-bottom: 1px solid #eee;
            padding: 10px 0;
        }

        .info-item:last-child {
            border-bottom: none;
        }

        .barang-card {
            transition: transform 0.2s;
        }

        .barang-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .status-badge {
            font-size: 0.9em;
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
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="proker.php">Program Kerja</a></li>
                <li class="breadcrumb-item active" aria-current="page">Detail Program Kerja</li>
            </ol>
        </nav>

        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-project-diagram"></i> Detail Program Kerja</h2>
            <div>
                <a href="proker.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
                <button class="btn btn-primary" onclick="window.print()">
                    <i class="fas fa-print"></i> Cetak
                </button>
            </div>
        </div>

        <div class="row">
            <!-- Informasi Program Kerja -->
            <div class="col-lg-6 mb-4">
                <div class="card detail-card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-info-circle"></i> Informasi Program Kerja</h5>
                    </div>
                    <div class="card-body">
                        <div class="info-item">
                            <strong>Nama Program Kerja:</strong><br>
                            <span class="text-primary fs-5"><?= htmlspecialchars($proker['nama_proker']) ?></span>
                        </div>
                        <div class="info-item">
                            <strong>Divisi:</strong><br>
                            <span class="badge bg-info"><?= htmlspecialchars($proker['divisi']) ?></span>
                        </div>
                        <div class="info-item">
                            <strong>Tanggal Pelaksanaan:</strong><br>
                            <i class="fas fa-calendar-start text-success"></i> <?= formatTanggal($proker['tanggal_mulai']) ?>
                            <span class="mx-2">s/d</span>
                            <i class="fas fa-calendar-check text-danger"></i> <?= formatTanggal($proker['tanggal_selesai']) ?>
                        </div>
                        <div class="info-item">
                            <strong>Lokasi:</strong><br>
                            <i class="fas fa-map-marker-alt text-warning"></i> <?= htmlspecialchars($proker['lokasi']) ?>
                        </div>
                        <div class="info-item">
                            <strong>Status:</strong><br>
                            <span class="badge bg-<?= $status_class ?> status-badge"><?= $status ?></span>
                        </div>
                        <?php if (!empty($proker['keterangan'])): ?>
                            <div class="info-item">
                                <strong>Keterangan:</strong><br>
                                <?= nl2br(htmlspecialchars($proker['keterangan'])) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Statistik -->
            <div class="col-lg-6 mb-4">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-chart-bar"></i> Statistik Barang</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $total_barang = count($barang_list);
                        $total_unit = array_sum(array_column($barang_list, 'jumlah_digunakan'));
                        $barang_dengan_pj = count(array_filter($barang_list, function ($item) {
                            return !empty($item['penanggung_jawab']);
                        }));
                        ?>
                        <div class="row text-center">
                            <div class="col-4">
                                <div class="border-end">
                                    <h3 class="text-primary"><?= $total_barang ?></h3>
                                    <small class="text-muted">Jenis Barang</small>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="border-end">
                                    <h3 class="text-success"><?= $total_unit ?></h3>
                                    <small class="text-muted">Total Unit</small>
                                </div>
                            </div>
                            <div class="col-4">
                                <h3 class="text-warning"><?= $barang_dengan_pj ?></h3>
                                <small class="text-muted">Ada PJ</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Durasi Program -->
                <div class="card mt-3">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-clock"></i> Durasi Program</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $start_date = new DateTime($proker['tanggal_mulai']);
                        $end_date = new DateTime($proker['tanggal_selesai']);
                        $interval = $start_date->diff($end_date);
                        $durasi = $interval->days + 1; // +1 karena termasuk hari mulai
                        ?>
                        <div class="text-center">
                            <h3 class="text-info"><?= $durasi ?></h3>
                            <small class="text-muted">Hari</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Daftar Barang -->
        <div class="card">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0"><i class="fas fa-boxes"></i> Daftar Barang yang Digunakan</h5>
            </div>
            <div class="card-body">
                <?php if (empty($barang_list)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Belum ada barang yang terdaftar untuk program kerja ini</h5>
                        <p class="text-muted">Silakan tambahkan barang melalui halaman manajemen program kerja</p>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($barang_list as $index => $barang): ?>
                            <div class="col-lg-6 col-xl-4 mb-3">
                                <div class="card barang-card h-100">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h6 class="card-title text-primary mb-0">
                                                <?= htmlspecialchars($barang['nama_barang']) ?>
                                            </h6>
                                            <span class="badge bg-<?= $barang['status_barang'] == 'Tersedia' ? 'success' : 'warning' ?>">
                                                <?= htmlspecialchars($barang['status_barang']) ?>
                                            </span>
                                        </div>

                                        <div class="mb-2">
                                            <small class="text-muted">Jumlah Digunakan:</small><br>
                                            <span class="fw-bold text-success"><?= $barang['jumlah_digunakan'] ?> unit</span>
                                            <small class="text-muted">dari <?= $barang['total_barang'] ?> total</small>
                                        </div>

                                        <div class="mb-2">
                                            <small class="text-muted">Kondisi:</small><br>
                                            <span class="badge bg-<?= $barang['kondisi'] == 'Baik' ? 'success' : ($barang['kondisi'] == 'Rusak Ringan' ? 'warning' : 'danger') ?>">
                                                <?= htmlspecialchars($barang['kondisi']) ?>
                                            </span>
                                        </div>

                                        <?php if (!empty($barang['tanggal_mulai_pakai']) && !empty($barang['tanggal_selesai_pakai'])): ?>
                                            <div class="mb-2">
                                                <small class="text-muted">Periode Pemakaian:</small><br>
                                                <small>
                                                    <i class="fas fa-calendar-alt"></i>
                                                    <?= formatTanggal($barang['tanggal_mulai_pakai']) ?> -
                                                    <?= formatTanggal($barang['tanggal_selesai_pakai']) ?>
                                                </small>
                                            </div>
                                        <?php endif; ?>

                                        <?php if (!empty($barang['penanggung_jawab'])): ?>
                                            <div class="mb-2">
                                                <small class="text-muted">Penanggung Jawab:</small><br>
                                                <div class="d-flex align-items-center">
                                                    <i class="fas fa-user-circle text-primary me-2"></i>
                                                    <div>
                                                        <div class="fw-bold"><?= htmlspecialchars($barang['penanggung_jawab']) ?></div>
                                                        <small class="text-muted"><?= htmlspecialchars($barang['npm']) ?></small>
                                                    </div>
                                                </div>
                                            </div>

                                            <?php if (!empty($barang['tanggal_mulai_pj']) && !empty($barang['tanggal_selesai_pj'])): ?>
                                                <div class="mb-2">
                                                    <small class="text-muted">Periode PJ:</small><br>
                                                    <small>
                                                        <i class="fas fa-user-clock"></i>
                                                        <?= formatTanggal($barang['tanggal_mulai_pj']) ?> -
                                                        <?= formatTanggal($barang['tanggal_selesai_pj']) ?>
                                                    </small>
                                                </div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <div class="mb-2">
                                                <small class="text-muted">Penanggung Jawab:</small><br>
                                                <span class="text-warning">
                                                    <i class="fas fa-exclamation-triangle"></i> Belum ditentukan
                                                </span>
                                            </div>
                                        <?php endif; ?>

                                        <?php if (!empty($barang['keterangan_pemakaian'])): ?>
                                            <div class="mt-2">
                                                <small class="text-muted">Keterangan:</small><br>
                                                <small><?= nl2br(htmlspecialchars($barang['keterangan_pemakaian'])) ?></small>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    <style media="print">
        .navbar,
        .btn,
        .breadcrumb {
            display: none !important;
        }

        .card {
            border: 1px solid #ddd !important;
            box-shadow: none !important;
        }

        .barang-card:hover {
            transform: none !important;
            box-shadow: none !important;
        }
    </style>
</body>

</html>