<?php
require_once 'config.php';
requireLogin();

$pdo = getConnection();
$barang_id = (int)$_GET['barang_id'];

// Get Proker list for specific barang
$stmt = $pdo->prepare("
    SELECT bp.*, p.nama_proker, p.divisi
    FROM barang_proker bp
    JOIN proker p ON bp.proker_id = p.id
    WHERE bp.barang_id = ?
    ORDER BY bp.tanggal_mulai_pakai DESC
");
$stmt->execute([$barang_id]);
$proker_list = $stmt->fetchAll();

if (empty($proker_list)) {
    echo '<div class="alert alert-info">Belum ada penggunaan proker untuk barang ini.</div>';
} else {
    foreach ($proker_list as $proker) {
        $periode = formatTanggal($proker['tanggal_mulai_pakai']) . ' - ' . formatTanggal($proker['tanggal_selesai_pakai']);

        echo '<div class="list-group-item">';
        echo '<div class="d-flex justify-content-between align-items-start">';
        echo '<div>';
        echo '<h6 class="mb-1">' . htmlspecialchars($proker['nama_proker']) . '</h6>';
        echo '<p class="mb-1"><small class="text-muted">Divisi: ' . htmlspecialchars($proker['divisi']) . '</small></p>';
        echo '<p class="mb-1"><small class="text-muted">Periode: ' . $periode . '</small></p>';
        echo '<p class="mb-1"><small class="text-muted">Jumlah: ' . $proker['jumlah_digunakan'] . ' unit</small></p>';
        if ($proker['keterangan']) {
            echo '<p class="mb-1"><small>' . htmlspecialchars($proker['keterangan']) . '</small></p>';
        }
        echo '</div>';
        echo '<div class="btn-group-vertical">';
        echo '<button class="btn btn-sm btn-outline-primary" onclick="editProker(' . htmlspecialchars(json_encode($proker)) . ')" title="Edit">';
        echo '<i class="fas fa-edit"></i>';
        echo '</button>';
        echo '<button class="btn btn-sm btn-outline-danger" onclick="deleteProker(' . $proker['id'] . ')" title="Hapus">';
        echo '<i class="fas fa-trash"></i>';
        echo '</button>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }
}
