<?php
require_once 'config.php';
requireLogin();

$pdo = getConnection();

// Get statistics
$stats = [];

// Total barang
$stmt = $pdo->prepare("SELECT COUNT(*) as total, SUM(jumlah) as total_qty FROM barang");
$stmt->execute();
$barang_stats = $stmt->fetch();
$stats['total_barang'] = $barang_stats['total'];
$stats['total_qty_barang'] = $barang_stats['total_qty'];

// Total vendor
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM vendor");
$stmt->execute();
$stats['total_vendor'] = $stmt->fetch()['total'];

// Total proker
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM proker");
$stmt->execute();
$stats['total_proker'] = $stmt->fetch()['total'];

// Total pengadaan
$stmt = $pdo->prepare("SELECT COUNT(*) as total, SUM(total_biaya_pengadaan) as total_nilai FROM pengadaan");
$stmt->execute();
$pengadaan_stats = $stmt->fetch();
$stats['total_pengadaan'] = $pengadaan_stats['total'];
$stats['total_nilai_pengadaan'] = $pengadaan_stats['total_nilai'] ?: 0;

// Barang dengan kondisi rusak
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM barang WHERE kondisi LIKE '%Rusak%'");
$stmt->execute();
$stats['barang_rusak'] = $stmt->fetch()['total'];

// Proker yang sedang berlangsung
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM proker WHERE CURDATE() BETWEEN tanggal_mulai AND tanggal_selesai");
$stmt->execute();
$stats['proker_aktif'] = $stmt->fetch()['total'];

// Recent activities - pengadaan terbaru
$stmt = $pdo->prepare("
    SELECT p.*, b.nama_barang, v.nama_vendor
    FROM pengadaan p
    JOIN barang b ON p.barang_id = b.id
    LEFT JOIN vendor v ON p.vendor_id = v.id
    ORDER BY p.id DESC
    LIMIT 5
");
$stmt->execute();
$recent_pengadaan = $stmt->fetchAll();

// Barang dengan stok rendah (kurang dari 5)
$stmt = $pdo->prepare("
    SELECT * FROM barang 
    WHERE jumlah < 5 AND jumlah > 0
    ORDER BY jumlah ASC
    LIMIT 5
");
$stmt->execute();
$low_stock = $stmt->fetchAll();

// Proker mendatang
$stmt = $pdo->prepare("
    SELECT * FROM proker 
    WHERE tanggal_mulai > CURDATE()
    ORDER BY tanggal_mulai ASC
    LIMIT 5
");
$stmt->execute();
$upcoming_proker = $stmt->fetchAll();

// Pengadaan per bulan (6 bulan terakhir)
$stmt = $pdo->prepare("
    SELECT 
        DATE_FORMAT(tanggal_terima, '%Y-%m') as bulan,
        COUNT(*) as jumlah_pengadaan,
        SUM(total_biaya_pengadaan) as total_biaya
    FROM pengadaan 
    WHERE tanggal_terima >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(tanggal_terima, '%Y-%m')
    ORDER BY bulan DESC
");
$stmt->execute();
$monthly_stats = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Inventaris KKN</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/responsive.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                    <a class="nav-link active" href="index.php">Dashboard</a>
                    <a class="nav-link" href="barang.php">Barang</a>
                    <a class="nav-link" href="proker.php">Program Kerja</a>
                    <a class="nav-link" href="vendor.php">Vendor</a>
                    <a class="nav-link" href="pengadaan.php">Pengadaan</a>
                    <a class="nav-link" href="laporan.php">Laporan</a>
                    <a class="nav-link" href="logout.php">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Welcome Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h2><i class="fas fa-tachometer-alt"></i> Dashboard Inventaris KKN</h2>
                        <p class="mb-0">Selamat datang, <?= htmlspecialchars($_SESSION['nama']) ?>! Kelola inventaris KKN dengan mudah dan efisien.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4><?= $stats['total_barang'] ?></h4>
                                <p class="mb-0">Total Jenis Barang</p>
                                <small><?= $stats['total_qty_barang'] ?> unit total</small>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-boxes fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4><?= $stats['total_vendor'] ?></h4>
                                <p class="mb-0">Total Vendor</p>
                                <small>Partner kerjasama</small>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-store fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4><?= $stats['total_proker'] ?></h4>
                                <p class="mb-0">Total Program Kerja</p>
                                <small><?= $stats['proker_aktif'] ?> sedang aktif</small>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-project-diagram fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4><?= $stats['total_pengadaan'] ?></h4>
                                <p class="mb-0">Total Pengadaan</p>
                                <small><?= formatRupiah($stats['total_nilai_pengadaan']) ?></small>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-shopping-cart fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alert Section -->
        <?php if ($stats['barang_rusak'] > 0 || count($low_stock) > 0): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="alert alert-warning" role="alert">
                    <h5><i class="fas fa-exclamation-triangle"></i> Perhatian!</h5>
                    <?php if ($stats['barang_rusak'] > 0): ?>
                        <p class="mb-1">• Ada <?= $stats['barang_rusak'] ?> barang dengan kondisi rusak yang perlu perhatian.</p>
                    <?php endif; ?>
                    <?php if (count($low_stock) > 0): ?>
                        <p class="mb-0">• Ada <?= count($low_stock) ?> barang dengan stok rendah (kurang dari 5 unit).</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="row">
            <!-- Recent Activities -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-clock"></i> Pengadaan Terbaru</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_pengadaan)): ?>
                            <p class="text-muted">Belum ada pengadaan.</p>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($recent_pengadaan as $pengadaan): ?>
                                    <div class="list-group-item d-flex justify-content-between align-items-start">
                                        <div class="ms-2 me-auto">
                                            <div class="fw-bold"><?= htmlspecialchars($pengadaan['nama_barang']) ?></div>
                                            <small class="text-muted">
                                                <?= htmlspecialchars($pengadaan['nama_vendor'] ?: 'Tanpa vendor') ?> • 
                                                <?= $pengadaan['jumlah_pengadaan'] ?> unit • 
                                                <?= formatRupiah($pengadaan['total_biaya_pengadaan']) ?>
                                            </small>
                                        </div>
                                        <span class="badge bg-<?= $pengadaan['tipe_pengadaan'] == 'Beli' ? 'success' : 'warning' ?> rounded-pill">
                                            <?= $pengadaan['tipe_pengadaan'] ?>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Low Stock Items -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-exclamation-circle"></i> Stok Rendah</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($low_stock)): ?>
                            <p class="text-muted">Semua barang memiliki stok yang cukup.</p>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($low_stock as $item): ?>
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="fw-bold"><?= htmlspecialchars($item['nama_barang']) ?></div>
                                            <small class="text-muted">Kondisi: <?= htmlspecialchars($item['kondisi']) ?></small>
                                        </div>
                                        <span class="badge bg-danger rounded-pill"><?= $item['jumlah'] ?> unit</span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Upcoming Programs -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-calendar-alt"></i> Program Kerja Mendatang</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($upcoming_proker)): ?>
                            <p class="text-muted">Tidak ada program kerja yang dijadwalkan.</p>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($upcoming_proker as $proker): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1"><?= htmlspecialchars($proker['nama_proker']) ?></h6>
                                            <small><?= formatTanggal($proker['tanggal_mulai']) ?></small>
                                        </div>
                                        <p class="mb-1"><?= htmlspecialchars($proker['divisi']) ?></p>
                                        <small class="text-muted"><?= htmlspecialchars($proker['lokasi']) ?></small>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Monthly Statistics Chart -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-chart-bar"></i> Statistik Pengadaan Bulanan</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="monthlyChart" width="400" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-bolt"></i> Aksi Cepat</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 mb-2">
                                <a href="barang.php" class="btn btn-outline-primary w-100">
                                    <i class="fas fa-plus"></i> Tambah Barang
                                </a>
                            </div>
                            <div class="col-md-3 mb-2">
                                <a href="pengadaan.php" class="btn btn-outline-success w-100">
                                    <i class="fas fa-shopping-cart"></i> Buat Pengadaan
                                </a>
                            </div>
                            <div class="col-md-3 mb-2">
                                <a href="proker.php" class="btn btn-outline-warning w-100">
                                    <i class="fas fa-project-diagram"></i> Tambah Proker
                                </a>
                            </div>
                            <div class="col-md-3 mb-2">
                                <a href="laporan.php" class="btn btn-outline-info w-100">
                                    <i class="fas fa-file-alt"></i> Lihat Laporan
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Monthly Statistics Chart
        const ctx = document.getElementById('monthlyChart').getContext('2d');
        const monthlyData = <?= json_encode($monthly_stats) ?>;
        
        const labels = monthlyData.map(item => {
            const date = new Date(item.bulan + '-01');
            return date.toLocaleDateString('id-ID', { year: 'numeric', month: 'short' });
        }).reverse();
        
        const data = monthlyData.map(item => item.jumlah_pengadaan).reverse();
        
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Jumlah Pengadaan',
                    data: data,
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    </script>
    <script src="assets/js/responsive.js"></script>
</body>
</html>

