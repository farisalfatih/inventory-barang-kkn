<?php
require_once 'config.php';
require_once 'vendor/autoload.php';
requireLogin();

$pdo = getConnection();

// Custom FPDF class for better UTF-8 support
class PDF extends FPDF
{
    function Header()
    {
        $this->SetFont('Arial', 'B', 16);
        $this->Cell(0, 10, 'LAPORAN INVENTARIS KKN', 0, 1, 'C');
        $this->SetFont('Arial', '', 10);
        $this->Cell(0, 5, 'Tanggal: ' . date('d F Y'), 0, 1, 'C');
        $this->Ln(10);
    }

    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Halaman ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }

    // Better cell function for UTF-8
    function CellUTF8($w, $h = 0, $txt = '', $border = 0, $ln = 0, $align = '', $fill = false, $link = '')
    {
        $this->Cell($w, $h, iconv('UTF-8', 'windows-1252//IGNORE', $txt), $border, $ln, $align, $fill, $link);
    }
}

// Handle export requests - IMPROVED with FPDF
if (isset($_GET['export'])) {
    $export_type = $_GET['export'];
    $format = $_GET['format'] ?? 'pdf';

    switch ($export_type) {
        case 'inventory':
            exportInventoryReport($pdo, $format);
            break;
        case 'procurement':
            exportProcurementReport($pdo, $format);
            break;
        case 'vendor':
            exportVendorReport($pdo, $format);
            break;
        case 'project':
            exportProjectReport($pdo, $format);
            break;
        case 'financial':
            exportFinancialReport($pdo, $format);
            break;
    }
    exit();
}

// Get filter parameters
$filter_proker = $_GET['filter_proker'] ?? '';
$filter_vendor = $_GET['filter_vendor'] ?? '';
$filter_tipe = $_GET['filter_tipe'] ?? '';
$filter_start = $_GET['filter_start'] ?? '';
$filter_end = $_GET['filter_end'] ?? '';

// Build WHERE clauses for filters
$where_conditions = [];
$params = [];

if ($filter_proker) {
    $where_conditions[] = "p.id = ?";
    $params[] = $filter_proker;
}

if ($filter_vendor) {
    $where_conditions[] = "v.id = ?";
    $params[] = $filter_vendor;
}

if ($filter_tipe) {
    $where_conditions[] = "pg.tipe_pengadaan = ?";
    $params[] = $filter_tipe;
}

if ($filter_start) {
    $where_conditions[] = "pg.tanggal_terima >= ?";
    $params[] = $filter_start;
}

if ($filter_end) {
    $where_conditions[] = "pg.tanggal_terima <= ?";
    $params[] = $filter_end;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get comprehensive inventory report - IMPROVED to include all columns
$inventory_query = "
    SELECT 
        b.nama_barang,
        b.jumlah,
        b.kondisi,
        b.status_barang,
        b.keterangan as keterangan_barang,
        GROUP_CONCAT(DISTINCT CONCAT(u.nama, ' (', DATE_FORMAT(bpj.tanggal_mulai_pj, '%d/%m/%Y'),
            CASE WHEN bpj.tanggal_selesai_pj IS NOT NULL THEN CONCAT(' - ', DATE_FORMAT(bpj.tanggal_selesai_pj, '%d/%m/%Y')) ELSE '' END,
            CASE WHEN bpj.keterangan != '' THEN CONCAT(': ', bpj.keterangan) ELSE '' END,
            ')') SEPARATOR '; ') as penanggung_jawab,
        GROUP_CONCAT(DISTINCT CONCAT(pr.nama_proker, ' (', bp.jumlah_digunakan, ' unit, ',
            DATE_FORMAT(bp.tanggal_mulai_pakai, '%d/%m/%Y'), ' - ', DATE_FORMAT(bp.tanggal_selesai_pakai, '%d/%m/%Y'),
            CASE WHEN bp.keterangan != '' THEN CONCAT(': ', bp.keterangan) ELSE '' END,
            ')') SEPARATOR '; ') as proker_terkait,
        COUNT(DISTINCT pg.id) as total_pengadaan,
        COALESCE(SUM(pg.total_biaya_pengadaan + COALESCE(pg.biaya_lain_lain, 0)), 0) as total_nilai_pengadaan
    FROM barang b
    LEFT JOIN barang_penanggung_jawab bpj ON b.id = bpj.barang_id
    LEFT JOIN users u ON bpj.user_id = u.id
    LEFT JOIN barang_proker bp ON b.id = bp.barang_id
    LEFT JOIN proker pr ON bp.proker_id = pr.id
    LEFT JOIN pengadaan pg ON b.id = pg.barang_id
    GROUP BY b.id
    ORDER BY b.nama_barang
";

$stmt = $pdo->prepare($inventory_query);
$stmt->execute();
$inventory_data = $stmt->fetchAll();

// Get procurement report with filters - IMPROVED to include all columns
$procurement_query = "
    SELECT 
        pg.*,
        b.nama_barang,
        b.keterangan as keterangan_barang,
        v.nama_vendor,
        v.kontak_vendor,
        v.alamat_vendor,
        v.keterangan as keterangan_vendor,
        pr.nama_proker,
        pr.keterangan as keterangan_proker,
        (pg.harga_per_item * pg.jumlah_pengadaan) as subtotal_biaya
    FROM pengadaan pg
    JOIN barang b ON pg.barang_id = b.id
    LEFT JOIN vendor v ON pg.vendor_id = v.id
    LEFT JOIN barang_proker bp ON b.id = bp.barang_id
    LEFT JOIN proker pr ON bp.proker_id = pr.id
    $where_clause
    ORDER BY pg.tanggal_terima DESC, pg.id DESC
";

$stmt = $pdo->prepare($procurement_query);
$stmt->execute($params);
$procurement_data = $stmt->fetchAll();

// Get vendor performance report - IMPROVED to include all columns
$vendor_query = "
    SELECT 
        v.*,
        COUNT(DISTINCT pg.id) as total_pengadaan,
        COALESCE(SUM(pg.total_biaya_pengadaan + COALESCE(pg.biaya_lain_lain, 0)), 0) as total_nilai,
        COALESCE(AVG(pg.total_biaya_pengadaan + COALESCE(pg.biaya_lain_lain, 0)), 0) as rata_rata_nilai,
        GROUP_CONCAT(DISTINCT CONCAT(u.nama, ' (', DATE_FORMAT(vpj.tanggal_mulai_pj, '%d/%m/%Y'),
            CASE WHEN vpj.tanggal_selesai_pj IS NOT NULL THEN CONCAT(' - ', DATE_FORMAT(vpj.tanggal_selesai_pj, '%d/%m/%Y')) ELSE '' END,
            CASE WHEN vpj.keterangan != '' THEN CONCAT(': ', vpj.keterangan) ELSE '' END,
            ')') SEPARATOR '; ') as penanggung_jawab
    FROM vendor v
    LEFT JOIN pengadaan pg ON v.id = pg.vendor_id
    LEFT JOIN vendor_penanggung_jawab vpj ON v.id = vpj.vendor_id
    LEFT JOIN users u ON vpj.user_id = u.id
    GROUP BY v.id
    ORDER BY total_nilai DESC
";

$stmt = $pdo->prepare($vendor_query);
$stmt->execute();
$vendor_data = $stmt->fetchAll();

// Get project report - IMPROVED to include all columns
$project_query = "
    SELECT 
        pr.*,
        COUNT(DISTINCT bp.barang_id) as jumlah_barang,
        COALESCE(SUM(bp.jumlah_digunakan), 0) as total_barang_digunakan,
        COALESCE(SUM(pg.total_biaya_pengadaan + COALESCE(pg.biaya_lain_lain, 0)), 0) as total_biaya_proker
    FROM proker pr
    LEFT JOIN barang_proker bp ON pr.id = bp.proker_id
    LEFT JOIN pengadaan pg ON bp.barang_id = pg.barang_id
    GROUP BY pr.id
    ORDER BY pr.tanggal_mulai DESC
";

$stmt = $pdo->prepare($project_query);
$stmt->execute();
$project_data = $stmt->fetchAll();

// Get financial summary - IMPROVED to include biaya_lain_lain
$financial_query = "
    SELECT 
        pg.tipe_pengadaan,
        COUNT(*) as jumlah_transaksi,
        SUM(pg.total_biaya_pengadaan) as total_biaya,
        SUM(COALESCE(pg.biaya_lain_lain, 0)) as total_biaya_lain,
        SUM(pg.total_biaya_pengadaan + COALESCE(pg.biaya_lain_lain, 0)) as total_biaya_keseluruhan,
        AVG(pg.total_biaya_pengadaan + COALESCE(pg.biaya_lain_lain, 0)) as rata_rata_biaya
    FROM pengadaan pg
    GROUP BY pg.tipe_pengadaan
    ORDER BY total_biaya_keseluruhan DESC
";

$stmt = $pdo->prepare($financial_query);
$stmt->execute();
$financial_data = $stmt->fetchAll();

// Get dropdowns for filters
$proker_list = getAllProker();
$vendor_list = getAllVendor();

// Export functions - IMPROVED with FPDF
function exportInventoryReport($pdo, $format)
{
    $stmt = $pdo->prepare("
        SELECT 
            b.nama_barang,
            b.jumlah,
            b.kondisi,
            b.status_barang,
            b.keterangan as keterangan_barang,
            GROUP_CONCAT(DISTINCT CONCAT(u.nama, ' (', DATE_FORMAT(bpj.tanggal_mulai_pj, '%d/%m/%Y'),
                CASE WHEN bpj.tanggal_selesai_pj IS NOT NULL THEN CONCAT(' - ', DATE_FORMAT(bpj.tanggal_selesai_pj, '%d/%m/%Y')) ELSE '' END,
                CASE WHEN bpj.keterangan != '' THEN CONCAT(': ', bpj.keterangan) ELSE '' END,
                ')') SEPARATOR '; ') as penanggung_jawab,
            COUNT(DISTINCT pg.id) as total_pengadaan,
            COALESCE(SUM(pg.total_biaya_pengadaan + COALESCE(pg.biaya_lain_lain, 0)), 0) as total_nilai_pengadaan
        FROM barang b
        LEFT JOIN barang_penanggung_jawab bpj ON b.id = bpj.barang_id
        LEFT JOIN users u ON bpj.user_id = u.id
        LEFT JOIN pengadaan pg ON b.id = pg.barang_id
        GROUP BY b.id
        ORDER BY b.nama_barang
    ");
    $stmt->execute();
    $data = $stmt->fetchAll();

    if ($format === 'csv') {
        exportToCSV($data, 'laporan_inventaris_' . date('Y-m-d'), [
            'nama_barang' => 'Nama Barang',
            'jumlah' => 'Jumlah',
            'kondisi' => 'Kondisi',
            'status_barang' => 'Status',
            'keterangan_barang' => 'Keterangan Barang',
            'penanggung_jawab' => 'Penanggung Jawab',
            'total_pengadaan' => 'Total Pengadaan',
            'total_nilai_pengadaan' => 'Total Nilai Pengadaan'
        ]);
    } else {
        exportToPDF($data, 'Laporan Inventaris Barang', 'laporan_inventaris_' . date('Y-m-d'), [
            'nama_barang' => 'Nama Barang',
            'jumlah' => 'Jumlah',
            'kondisi' => 'Kondisi',
            'status_barang' => 'Status',
            'keterangan_barang' => 'Keterangan',
            'penanggung_jawab' => 'Penanggung Jawab',
            'total_pengadaan' => 'Total Pengadaan',
            'total_nilai_pengadaan' => 'Total Nilai'
        ]);
    }
}

function exportProcurementReport($pdo, $format)
{
    $stmt = $pdo->prepare("
        SELECT 
            pg.tanggal_pesan,
            pg.tanggal_terima,
            b.nama_barang,
            b.keterangan as keterangan_barang,
            v.nama_vendor,
            v.kontak_vendor,
            pg.tipe_pengadaan,
            pg.jumlah_pengadaan,
            pg.harga_per_item,
            (pg.harga_per_item * pg.jumlah_pengadaan) as subtotal_biaya,
            COALESCE(pg.biaya_lain_lain, 0) as biaya_lain_lain,
            pg.total_biaya_pengadaan,
            pg.dokumen_url,
            pg.keterangan_pengadaan
        FROM pengadaan pg
        JOIN barang b ON pg.barang_id = b.id
        LEFT JOIN vendor v ON pg.vendor_id = v.id
        ORDER BY pg.tanggal_terima DESC
    ");
    $stmt->execute();
    $data = $stmt->fetchAll();

    if ($format === 'csv') {
        exportToCSV($data, 'laporan_pengadaan_' . date('Y-m-d'), [
            'tanggal_pesan' => 'Tanggal Pesan',
            'tanggal_terima' => 'Tanggal Terima',
            'nama_barang' => 'Nama Barang',
            'keterangan_barang' => 'Keterangan Barang',
            'nama_vendor' => 'Vendor',
            'kontak_vendor' => 'Kontak Vendor',
            'tipe_pengadaan' => 'Tipe Pengadaan',
            'jumlah_pengadaan' => 'Jumlah',
            'harga_per_item' => 'Harga per Item',
            'subtotal_biaya' => 'Subtotal',
            'biaya_lain_lain' => 'Biaya Lain-lain',
            'total_biaya_pengadaan' => 'Total Biaya',
            'dokumen_url' => 'Dokumen URL',
            'keterangan_pengadaan' => 'Keterangan'
        ]);
    } else {
        exportToPDF($data, 'Laporan Pengadaan Barang', 'laporan_pengadaan_' . date('Y-m-d'), [
            'tanggal_pesan' => 'Tgl Pesan',
            'tanggal_terima' => 'Tgl Terima',
            'nama_barang' => 'Nama Barang',
            'nama_vendor' => 'Vendor',
            'tipe_pengadaan' => 'Tipe',
            'jumlah_pengadaan' => 'Jumlah',
            'harga_per_item' => 'Harga/Item',
            'subtotal_biaya' => 'Subtotal',
            'biaya_lain_lain' => 'Biaya Lain',
            'total_biaya_pengadaan' => 'Total'
        ]);
    }
}

function exportVendorReport($pdo, $format)
{
    $stmt = $pdo->prepare("
        SELECT 
            v.nama_vendor,
            v.kontak_vendor,
            v.alamat_vendor,
            v.keterangan as keterangan_vendor,
            COUNT(DISTINCT pg.id) as total_pengadaan,
            COALESCE(SUM(pg.total_biaya_pengadaan + COALESCE(pg.biaya_lain_lain, 0)), 0) as total_nilai,
            GROUP_CONCAT(DISTINCT CONCAT(u.nama, ' (', DATE_FORMAT(vpj.tanggal_mulai_pj, '%d/%m/%Y'),
                CASE WHEN vpj.tanggal_selesai_pj IS NOT NULL THEN CONCAT(' - ', DATE_FORMAT(vpj.tanggal_selesai_pj, '%d/%m/%Y')) ELSE '' END,
                ')') SEPARATOR '; ') as penanggung_jawab
        FROM vendor v
        LEFT JOIN pengadaan pg ON v.id = pg.vendor_id
        LEFT JOIN vendor_penanggung_jawab vpj ON v.id = vpj.vendor_id
        LEFT JOIN users u ON vpj.user_id = u.id
        GROUP BY v.id
        ORDER BY total_nilai DESC
    ");
    $stmt->execute();
    $data = $stmt->fetchAll();

    if ($format === 'csv') {
        exportToCSV($data, 'laporan_vendor_' . date('Y-m-d'), [
            'nama_vendor' => 'Nama Vendor',
            'kontak_vendor' => 'Kontak',
            'alamat_vendor' => 'Alamat',
            'keterangan_vendor' => 'Keterangan Vendor',
            'total_pengadaan' => 'Total Pengadaan',
            'total_nilai' => 'Total Nilai',
            'penanggung_jawab' => 'Penanggung Jawab'
        ]);
    } else {
        exportToPDF($data, 'Laporan Kinerja Vendor', 'laporan_vendor_' . date('Y-m-d'), [
            'nama_vendor' => 'Nama Vendor',
            'kontak_vendor' => 'Kontak',
            'alamat_vendor' => 'Alamat',
            'total_pengadaan' => 'Total Pengadaan',
            'total_nilai' => 'Total Nilai',
            'penanggung_jawab' => 'Penanggung Jawab'
        ]);
    }
}

function exportProjectReport($pdo, $format)
{
    $stmt = $pdo->prepare("
        SELECT 
            pr.nama_proker,
            pr.divisi,
            pr.tanggal_mulai,
            pr.tanggal_selesai,
            pr.lokasi,
            pr.keterangan as keterangan_proker,
            COUNT(DISTINCT bp.barang_id) as jumlah_barang,
            COALESCE(SUM(bp.jumlah_digunakan), 0) as total_barang_digunakan
        FROM proker pr
        LEFT JOIN barang_proker bp ON pr.id = bp.proker_id
        GROUP BY pr.id
        ORDER BY pr.tanggal_mulai DESC
    ");
    $stmt->execute();
    $data = $stmt->fetchAll();

    if ($format === 'csv') {
        exportToCSV($data, 'laporan_proker_' . date('Y-m-d'), [
            'nama_proker' => 'Nama Program Kerja',
            'divisi' => 'Divisi',
            'tanggal_mulai' => 'Tanggal Mulai',
            'tanggal_selesai' => 'Tanggal Selesai',
            'lokasi' => 'Lokasi',
            'keterangan_proker' => 'Keterangan',
            'jumlah_barang' => 'Jumlah Jenis Barang',
            'total_barang_digunakan' => 'Total Barang Digunakan'
        ]);
    } else {
        exportToPDF($data, 'Laporan Program Kerja', 'laporan_proker_' . date('Y-m-d'), [
            'nama_proker' => 'Nama Proker',
            'divisi' => 'Divisi',
            'tanggal_mulai' => 'Tgl Mulai',
            'tanggal_selesai' => 'Tgl Selesai',
            'lokasi' => 'Lokasi',
            'jumlah_barang' => 'Jml Barang',
            'total_barang_digunakan' => 'Total Digunakan'
        ]);
    }
}

function exportFinancialReport($pdo, $format)
{
    $stmt = $pdo->prepare("
        SELECT 
            pg.tipe_pengadaan,
            COUNT(*) as jumlah_transaksi,
            SUM(pg.total_biaya_pengadaan) as total_biaya,
            SUM(COALESCE(pg.biaya_lain_lain, 0)) as total_biaya_lain,
            SUM(pg.total_biaya_pengadaan + COALESCE(pg.biaya_lain_lain, 0)) as total_biaya_keseluruhan,
            AVG(pg.total_biaya_pengadaan + COALESCE(pg.biaya_lain_lain, 0)) as rata_rata_biaya,
            MIN(pg.total_biaya_pengadaan + COALESCE(pg.biaya_lain_lain, 0)) as biaya_minimum,
            MAX(pg.total_biaya_pengadaan + COALESCE(pg.biaya_lain_lain, 0)) as biaya_maksimum
        FROM pengadaan pg
        GROUP BY pg.tipe_pengadaan
        ORDER BY total_biaya_keseluruhan DESC
    ");
    $stmt->execute();
    $data = $stmt->fetchAll();

    if ($format === 'csv') {
        exportToCSV($data, 'laporan_keuangan_' . date('Y-m-d'), [
            'tipe_pengadaan' => 'Tipe Pengadaan',
            'jumlah_transaksi' => 'Jumlah Transaksi',
            'total_biaya' => 'Total Biaya Utama',
            'total_biaya_lain' => 'Total Biaya Lain-lain',
            'total_biaya_keseluruhan' => 'Total Biaya Keseluruhan',
            'rata_rata_biaya' => 'Rata-rata Biaya',
            'biaya_minimum' => 'Biaya Minimum',
            'biaya_maksimum' => 'Biaya Maksimum'
        ]);
    } else {
        exportToPDF($data, 'Laporan Keuangan', 'laporan_keuangan_' . date('Y-m-d'), [
            'tipe_pengadaan' => 'Tipe Pengadaan',
            'jumlah_transaksi' => 'Jml Transaksi',
            'total_biaya' => 'Total Biaya',
            'total_biaya_lain' => 'Biaya Lain',
            'total_biaya_keseluruhan' => 'Total Keseluruhan',
            'rata_rata_biaya' => 'Rata-rata'
        ]);
    }
}

function exportToCSV($data, $filename, $headers)
{
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');

    $output = fopen('php://output', 'w');

    // Add BOM for UTF-8
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

    // Write headers
    fputcsv($output, array_values($headers));

    // Write data
    foreach ($data as $row) {
        $csv_row = [];
        foreach (array_keys($headers) as $key) {
            $csv_row[] = $row[$key] ?? '';
        }
        fputcsv($output, $csv_row);
    }

    fclose($output);
}

function exportToPDF($data, $title, $filename, $headers)
{
    // Generate PDF using FPDF
    $pdf = new PDF();
    $pdf->AliasNbPages();
    $pdf->AddPage('L'); // Landscape for better table display
    $pdf->SetFont('Arial', '', 8);

    // Summary section
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->CellUTF8(0, 8, $title, 0, 1, 'L');
    $pdf->SetFont('Arial', '', 10);
    $pdf->CellUTF8(0, 6, 'Total Data: ' . count($data) . ' record', 0, 1, 'L');
    $pdf->Ln(5);

    if (!empty($data)) {
        // Calculate column widths based on content
        $col_count = count($headers);
        $col_width = 270 / $col_count; // 270 is approximate usable width in landscape

        // Table header
        $pdf->SetFont('Arial', 'B', 7);
        $pdf->CellUTF8(15, 6, 'No', 1, 0, 'C');
        foreach ($headers as $header) {
            $pdf->CellUTF8($col_width, 6, $header, 1, 0, 'C');
        }
        $pdf->Ln();

        // Table data
        $pdf->SetFont('Arial', '', 6);
        foreach ($data as $index => $item) {
            // Check if we need a new page
            if ($pdf->GetY() > 180) {
                $pdf->AddPage('L');
                // Repeat header
                $pdf->SetFont('Arial', 'B', 7);
                $pdf->CellUTF8(15, 6, 'No', 1, 0, 'C');
                foreach ($headers as $header) {
                    $pdf->CellUTF8($col_width, 6, $header, 1, 0, 'C');
                }
                $pdf->Ln();
                $pdf->SetFont('Arial', '', 6);
            }

            $pdf->CellUTF8(15, 5, ($index + 1), 1, 0, 'C');
            foreach (array_keys($headers) as $key) {
                $value = $item[$key] ?? '';
                // Truncate long text for PDF display
                if (strlen($value) > 20) {
                    $value = substr($value, 0, 17) . '...';
                }
                $pdf->CellUTF8($col_width, 5, $value, 1, 0, 'L');
            }
            $pdf->Ln();
        }
    } else {
        $pdf->CellUTF8(0, 10, 'Tidak ada data untuk ditampilkan.', 0, 1, 'C');
    }

    // Output PDF
    $pdf->Output('D', $filename . '.pdf');
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan - Inventaris KKN</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/responsive.css" rel="stylesheet">
    <style>
        .report-card {
            transition: transform 0.2s;
        }

        .report-card:hover {
            transform: translateY(-2px);
        }

        .table-responsive {
            max-height: 400px;
            overflow-y: auto;
        }

        .detail-text {
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .detail-text:hover {
            white-space: normal;
            overflow: visible;
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
                <a class="nav-link" href="pengadaan.php">Pengadaan</a>
                <a class="nav-link active" href="laporan.php">Laporan</a>
                <a class="nav-link" href="logout.php">Logout</a>
            </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <h2><i class="fas fa-file-alt"></i> Laporan Inventaris KKN</h2>
                <p class="text-muted">Laporan komprehensif untuk manajemen inventaris dan keuangan dengan ekspor PDF menggunakan FPDF</p>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-filter"></i> Filter Laporan</h5>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Program Kerja</label>
                                <select class="form-select" name="filter_proker">
                                    <option value="">Semua Proker</option>
                                    <?php foreach ($proker_list as $proker): ?>
                                        <option value="<?= $proker['id'] ?>" <?= $filter_proker == $proker['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($proker['nama_proker']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Vendor</label>
                                <select class="form-select" name="filter_vendor">
                                    <option value="">Semua Vendor</option>
                                    <?php foreach ($vendor_list as $vendor): ?>
                                        <option value="<?= $vendor['id'] ?>" <?= $filter_vendor == $vendor['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($vendor['nama_vendor']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Tipe Pengadaan</label>
                                <select class="form-select" name="filter_tipe">
                                    <option value="">Semua Tipe</option>
                                    <option value="Beli" <?= $filter_tipe == 'Beli' ? 'selected' : '' ?>>Beli</option>
                                    <option value="Pinjam" <?= $filter_tipe == 'Pinjam' ? 'selected' : '' ?>>Pinjam</option>
                                    <option value="Sewa" <?= $filter_tipe == 'Sewa' ? 'selected' : '' ?>>Sewa</option>
                                    <option value="Hibah" <?= $filter_tipe == 'Hibah' ? 'selected' : '' ?>>Hibah</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-primary" style="margin-top: 32px;">
                                    <i class="fas fa-search"></i> Filter
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Export Buttons - IMPROVED with FPDF -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-download"></i> Export Laporan (Menggunakan FPDF)</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-2 mb-2">
                                <div class="dropdown">
                                    <button class="btn btn-success dropdown-toggle w-100" type="button" data-bs-toggle="dropdown">
                                        <i class="fas fa-boxes"></i> Inventaris
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="?export=inventory&format=pdf">
                                                <i class="fas fa-file-pdf"></i> PDF (FPDF)
                                            </a></li>
                                        <li><a class="dropdown-item" href="?export=inventory&format=csv">
                                                <i class="fas fa-file-csv"></i> CSV
                                            </a></li>
                                    </ul>
                                </div>
                            </div>
                            <div class="col-md-2 mb-2">
                                <div class="dropdown">
                                    <button class="btn btn-info dropdown-toggle w-100" type="button" data-bs-toggle="dropdown">
                                        <i class="fas fa-shopping-cart"></i> Pengadaan
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="?export=procurement&format=pdf">
                                                <i class="fas fa-file-pdf"></i> PDF (FPDF)
                                            </a></li>
                                        <li><a class="dropdown-item" href="?export=procurement&format=csv">
                                                <i class="fas fa-file-csv"></i> CSV
                                            </a></li>
                                    </ul>
                                </div>
                            </div>
                            <div class="col-md-2 mb-2">
                                <div class="dropdown">
                                    <button class="btn btn-warning dropdown-toggle w-100" type="button" data-bs-toggle="dropdown">
                                        <i class="fas fa-store"></i> Vendor
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="?export=vendor&format=pdf">
                                                <i class="fas fa-file-pdf"></i> PDF (FPDF)
                                            </a></li>
                                        <li><a class="dropdown-item" href="?export=vendor&format=csv">
                                                <i class="fas fa-file-csv"></i> CSV
                                            </a></li>
                                    </ul>
                                </div>
                            </div>
                            <div class="col-md-2 mb-2">
                                <div class="dropdown">
                                    <button class="btn btn-secondary dropdown-toggle w-100" type="button" data-bs-toggle="dropdown">
                                        <i class="fas fa-project-diagram"></i> Proker
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="?export=project&format=pdf">
                                                <i class="fas fa-file-pdf"></i> PDF (FPDF)
                                            </a></li>
                                        <li><a class="dropdown-item" href="?export=project&format=csv">
                                                <i class="fas fa-file-csv"></i> CSV
                                            </a></li>
                                    </ul>
                                </div>
                            </div>
                            <div class="col-md-2 mb-2">
                                <div class="dropdown">
                                    <button class="btn btn-danger dropdown-toggle w-100" type="button" data-bs-toggle="dropdown">
                                        <i class="fas fa-chart-line"></i> Keuangan
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="?export=financial&format=pdf">
                                                <i class="fas fa-file-pdf"></i> PDF (FPDF)
                                            </a></li>
                                        <li><a class="dropdown-item" href="?export=financial&format=csv">
                                                <i class="fas fa-file-csv"></i> CSV
                                            </a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="alert alert-info mt-3">
                            <i class="fas fa-info-circle"></i>
                            <strong>Perbaikan Terbaru:</strong> Export PDF sekarang menggunakan FPDF library yang stabil dan menampilkan semua kolom database termasuk keterangan, dokumen URL, biaya lain-lain, dan detail penanggung jawab.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Report Cards -->
        <div class="row mb-4">
            <div class="col-md-6 mb-3">
                <div class="card report-card">
                    <div class="card-header bg-success text-white">
                        <h5><i class="fas fa-boxes"></i> Laporan Inventaris</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Nama Barang</th>
                                        <th>Jumlah</th>
                                        <th>Kondisi</th>
                                        <th>Status</th>
                                        <th>Keterangan</th>
                                        <th>Total Nilai</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($inventory_data, 0, 5) as $item): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($item['nama_barang']) ?></td>
                                            <td><?= $item['jumlah'] ?></td>
                                            <td><span class="badge bg-<?= $item['kondisi'] == 'Baik' ? 'success' : 'warning' ?>"><?= $item['kondisi'] ?></span></td>
                                            <td><span class="badge bg-<?= $item['status_barang'] == 'Tersedia' ? 'success' : 'warning' ?>"><?= $item['status_barang'] ?></span></td>
                                            <td class="detail-text" title="<?= htmlspecialchars($item['keterangan_barang']) ?>"><?= htmlspecialchars($item['keterangan_barang'] ?: '-') ?></td>
                                            <td><?= formatRupiah($item['total_nilai_pengadaan']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <small class="text-muted">Menampilkan 5 data teratas dari <?= count($inventory_data) ?> total data</small>
                    </div>
                </div>
            </div>

            <div class="col-md-6 mb-3">
                <div class="card report-card">
                    <div class="card-header bg-info text-white">
                        <h5><i class="fas fa-shopping-cart"></i> Laporan Pengadaan</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Barang</th>
                                        <th>Vendor</th>
                                        <th>Tipe</th>
                                        <th>Subtotal</th>
                                        <th>Biaya Lain</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($procurement_data, 0, 5) as $item): ?>
                                        <tr>
                                            <td><?= $item['tanggal_terima'] ? formatTanggal($item['tanggal_terima']) : '-' ?></td>
                                            <td><?= htmlspecialchars($item['nama_barang']) ?></td>
                                            <td><?= htmlspecialchars($item['nama_vendor'] ?: 'Tidak ada') ?></td>
                                            <td><span class="badge bg-<?= $item['tipe_pengadaan'] == 'Beli' ? 'success' : 'info' ?>"><?= $item['tipe_pengadaan'] ?></span></td>
                                            <td><?= formatRupiah($item['subtotal_biaya']) ?></td>
                                            <td><?= formatRupiah($item['biaya_lain_lain'] ?? 0) ?></td>
                                            <td><strong><?= formatRupiah($item['total_biaya_pengadaan']) ?></strong></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <small class="text-muted">Menampilkan 5 data teratas dari <?= count($procurement_data) ?> total data</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-6 mb-3">
                <div class="card report-card">
                    <div class="card-header bg-warning text-dark">
                        <h5><i class="fas fa-store"></i> Laporan Vendor</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Nama Vendor</th>
                                        <th>Kontak</th>
                                        <th>Total Pengadaan</th>
                                        <th>Total Nilai</th>
                                        <th>Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($vendor_data, 0, 5) as $item): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($item['nama_vendor']) ?></td>
                                            <td><?= htmlspecialchars($item['kontak_vendor'] ?: '-') ?></td>
                                            <td><span class="badge bg-info"><?= $item['total_pengadaan'] ?></span></td>
                                            <td><?= formatRupiah($item['total_nilai']) ?></td>
                                            <td class="detail-text" title="<?= htmlspecialchars($item['keterangan']) ?>"><?= htmlspecialchars($item['keterangan'] ?: '-') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <small class="text-muted">Menampilkan 5 data teratas dari <?= count($vendor_data) ?> total data</small>
                    </div>
                </div>
            </div>

            <div class="col-md-6 mb-3">
                <div class="card report-card">
                    <div class="card-header bg-danger text-white">
                        <h5><i class="fas fa-chart-line"></i> Laporan Keuangan</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Tipe Pengadaan</th>
                                        <th>Jumlah Transaksi</th>
                                        <th>Total Biaya</th>
                                        <th>Biaya Lain</th>
                                        <th>Total Keseluruhan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($financial_data as $item): ?>
                                        <tr>
                                            <td><span class="badge bg-<?= $item['tipe_pengadaan'] == 'Beli' ? 'success' : 'info' ?>"><?= $item['tipe_pengadaan'] ?></span></td>
                                            <td><?= $item['jumlah_transaksi'] ?></td>
                                            <td><?= formatRupiah($item['total_biaya']) ?></td>
                                            <td><?= formatRupiah($item['total_biaya_lain']) ?></td>
                                            <td><strong><?= formatRupiah($item['total_biaya_keseluruhan']) ?></strong></td>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/responsive.js"></script>
</body>

</html>