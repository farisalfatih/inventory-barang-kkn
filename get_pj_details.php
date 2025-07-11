<?php
require_once 'config.php';
requireLogin();

$pdo = getConnection();
$vendor_id = (int)$_GET['vendor_id'];

// Get PJ details for the vendor
$stmt = $pdo->prepare("
    SELECT vpj.*, u.nama as pj_nama, u.npm as pj_npm
    FROM vendor_penanggung_jawab vpj
    JOIN users u ON vpj.user_id = u.id
    WHERE vpj.vendor_id = ?
    ORDER BY vpj.tanggal_mulai_pj DESC
");
$stmt->execute([$vendor_id]);
$pj_list = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h6 class="mb-0">Daftar Penanggung Jawab</h6>
    <button class="btn btn-sm btn-primary" onclick="addPJ(<?= $vendor_id ?>)">
        <i class="fas fa-plus"></i> Tambah PJ
    </button>
</div>

<?php if (empty($pj_list)): ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i> Belum ada penanggung jawab untuk vendor ini.
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-sm table-striped">
            <thead class="table-light">
                <tr>
                    <th>No</th>
                    <th>Nama PJ</th>
                    <th>NPM</th>
                    <th>Periode</th>
                    <th>Keterangan</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pj_list as $index => $pj): ?>
                    <tr>
                        <td><?= $index + 1 ?></td>
                        <td><strong><?= htmlspecialchars($pj['pj_nama']) ?></strong></td>
                        <td><?= htmlspecialchars($pj['pj_npm']) ?></td>
                        <td>
                            <div class="small">
                                <strong>Mulai:</strong> <?= date("d/m/Y", strtotime($pj["tanggal_mulai_pj"])) ?><br>
                                <strong>Selesai:</strong> <?= $pj["tanggal_selesai_pj"] ? date("d/m/Y", strtotime($pj["tanggal_selesai_pj"])) : "Tidak terbatas" ?>
                            </div>
                        </td>
                        <td>
                            <div class="text-truncate" style="max-width: 150px;" title="<?= htmlspecialchars($pj['keterangan']) ?>">
                                <?= htmlspecialchars($pj['keterangan'] ?: '-') ?>
                            </div>
                        </td>
                        <td>
                            <div class="btn-group" role="group">
                                <button class="btn btn-sm btn-outline-primary" onclick="editPJ(<?= htmlspecialchars(json_encode($pj)) ?>)" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger" onclick="deletePJ(<?= $pj['id'] ?>)" title="Hapus">
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