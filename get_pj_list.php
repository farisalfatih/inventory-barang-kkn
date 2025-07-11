<?php
require_once 'config.php';
requireLogin();

$pdo = getConnection();
$barang_id = (int)$_GET['barang_id'];

// Get PJ list for specific barang
$stmt = $pdo->prepare("
    SELECT bpj.*, u.nama as nama_user
    FROM barang_penanggung_jawab bpj
    JOIN users u ON bpj.user_id = u.id
    WHERE bpj.barang_id = ?
    ORDER BY bpj.tanggal_mulai_pj DESC
");
$stmt->execute([$barang_id]);
$pj_list = $stmt->fetchAll();

if (empty($pj_list)) {
    echo '<div class="alert alert-info">Belum ada penanggung jawab untuk barang ini.</div>';
} else {
    foreach ($pj_list as $pj) {
        $periode = formatTanggal($pj['tanggal_mulai_pj']);
        if ($pj['tanggal_selesai_pj']) {
            $periode .= ' - ' . formatTanggal($pj['tanggal_selesai_pj']);
        } else {
            $periode .= ' - Sekarang';
        }

        echo '<div class="list-group-item">';
        echo '<div class="d-flex justify-content-between align-items-start">';
        echo '<div>';
        echo '<h6 class="mb-1">' . htmlspecialchars($pj['nama_user']) . '</h6>';
        echo '<p class="mb-1"><small class="text-muted">Periode: ' . $periode . '</small></p>';
        if ($pj['keterangan']) {
            echo '<p class="mb-1"><small>' . htmlspecialchars($pj['keterangan']) . '</small></p>';
        }
        echo '</div>';
        echo '<div class="btn-group-vertical">';
        echo '<button class="btn btn-sm btn-outline-primary" onclick="editPJ(' . htmlspecialchars(json_encode($pj)) . ')" title="Edit">';
        echo '<i class="fas fa-edit"></i>';
        echo '</button>';
        echo '<button class="btn btn-sm btn-outline-danger" onclick="deletePJ(' . $pj['id'] . ')" title="Hapus">';
        echo '<i class="fas fa-trash"></i>';
        echo '</button>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }
}
