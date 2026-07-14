<?php
    session_start();
    include "koneksi.php";
    date_default_timezone_set('Asia/Jakarta');

    if(!isset($_GET['id'])) die("Data tidak ditemukan");

    $id = $_GET['id'];

    $data = $conn->query("
    SELECT p.*, pg.nama_pegawai, pg.UNIT_II
    FROM pengajuan_bmn p
    LEFT JOIN tb_pegawai pg 
        ON p.nip_peminjam = pg.nip_pegawai
    WHERE p.id_pengajuan='$id'
    ")->fetch_assoc();

    if(!$data) die("Data tidak ada");

    $detail = $conn->query("
    SELECT 
        b.*,
        k.ur_sskel,
        pg.nama_pegawai AS nama_pengguna_barang
    FROM pengajuan_bmn_detail d
    JOIN daftar_bmn b 
        ON d.id_bmn = b.id_bmn
    LEFT JOIN tb_kode_barang k 
        ON b.kode_barang = k.kode_barang
    LEFT JOIN tb_pegawai pg
        ON b.nip_pegawai = pg.nip_pegawai
    WHERE d.id_pengajuan='$id'
    ");

    $statusClass="";
    if($data['status']=="Diajukan") $statusClass="badge bg-warning";
    if($data['status']=="Disetujui") $statusClass="badge bg-success";
    if($data['status']=="Selesai") $statusClass="badge bg-secondary";
    if($data['status']=="Batal") $statusClass="badge bg-danger";
?>

<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Detail Pengajuan BMN</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <style>
            .btn-back{background-color:#22466c; color:#fff}
            body{background:#f5f6fa;font-family:'Inter',sans-serif}
            .card-header-grad{background:linear-gradient(90deg,#1f4e5f,#2e9c94);color:white;font-weight:600;}
            .section-title{font-size:20px;font-weight:600;color:#22466c}
            .section-subtitle {color:#22466c}
            .icon-header{border-radius:10px;display:flex;align-items:center;justify-content:center;color:#22466c;font-size:28px;}
            .detail-row{display:flex;align-items:flex-start;border-bottom:1px solid #e9ecef;padding:12px 0;}
            .detail-row:last-child{border-bottom:none;}
            .detail-label{width:220px;min-width:220px;color:#22466c;font-weight:600;}
            .detail-value{flex:1;word-wrap:break-word;overflow-wrap:break-word;font-weight:400}
            .detail-documentation-label{width:850px;min-width:200px;color:#22466c;font-weight:500;}
        </style>
    </head>
    <body>
        <div class="container mt-4 mb-4">
            <div class="page-header mb-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center">
                        <div class="icon-header me-3">
                            <i class="fa-solid fa-cubes"></i>
                        </div>
                        <div>
                            <div class="section-title fs-5 fw-bold">
                                Detail Pengajuan Barang Milik Negara (BMN)
                            </div>
                            <div class="section-subtitle fs-6">
                                Informasi Data Peminjam BMN
                            </div>
                        </div>
                    </div>
                    <a href="dashboard_pengajuan_bmn.php" class="btn btn-back rounded-pill">
                        <i class="fa fa-arrow-left me-2"></i> Kembali ke Daftar
                    </a>
                </div>
            </div>
            <?php while($b=$detail->fetch_assoc()): ?>
            <div class="card shadow-sm rounded-4 mb-4">
                <div class="card-header card-header-grad rounded-top-4">
                    <i class="fa fa-bag-shopping me-2"></i>Detail BMN
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="detail-row">
                                <span class="detail-label">
                                    <i class="fa fa fa-cubes me-2"></i>
                                Barang / Kode - NUP
                                </span>
                                <span>
                                    <b><?= $b['ur_sskel'] ?></b><br>
                                    <span class="text-muted"><?= $b['kode_barang'] ?> - NUP <?= $b['NUP'] ?></span>
                                </span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">
                                    <i class="fa fa-pen me-2 text-warning"></i>
                                    Spesifikasi BMN
                                </span>
                                <span><?= $b['spek_bmn'] ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">
                                    <i class="fa fa-shield-alt me-2"></i>
                                    Kondisi Barang
                                </span>
                                <span><?= $b['kondisi_bmn'] ?></span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-row">
                            <span class="detail-label">
                                <i class="fa fa-user me-2 text-primary"></i>
                                Pengguna
                            </span>
                            <span><?= $b['nama_pengguna_barang'] ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">
                                <i class="fa fa-building text-muted me-2"></i>
                                Unit Kerja
                            </span>
                            <span><?= $b['UNIT_II'] ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
        <div class="card shadow-sm rounded-4 mb-4">
            <div class="card-header card-header-grad rounded-top-4">
                <i class="fa fa-info-circle me-2"></i>Detail Peminjaman
            </div>
            <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="detail-row">
                        <div class="detail-label">
                            <i class="fa fa-user text-primary me-2"></i>
                            Nama Peminjam
                        </div>
                        <div class="detail-value">
                            <?= $data['nama_pegawai'] ?>
                        </div>
                        <div class="text-end">
                            <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $data['no_hp']) ?>" target="_blank" class="text-success">
                                <i class="fab fa-whatsapp me-2"></i>
                            </a>
                        </div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">
                            <i class="fa fa-building text-muted me-2"></i>
                            Unit Kerja
                        </div>
                        <div class="detail-value">
                            <?= $data['UNIT_II'] ?>
                        </div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">
                            <i class="fa fa-calendar text-info me-2"></i>
                            Tanggal Pengajuan
                        </div>
                        <div class="detail-value">
                            <?= $data['tgl_pengajuan'] ?>
                        </div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">
                            <i class="fa fa-pen-to-square text-info me-2"></i>
                            Keterangan
                        </div>
                        <div class="detail-value">
                            <?= $data['keterangan'] ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="detail-row">
                        <div class="detail-label">
                            <i class="fa fa-arrow-right-from-bracket fa-flip-horizontal me-2"></i>
                            Tanggal Pinjam
                        </div>
                        <div class="detail-value">
                            <?= $data['tgl_pinjam'] ?>
                        </div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">
                            <i class="fa fa-arrow-right-to-bracket me-2"></i>
                            Tanggal Pengembalian
                        </div>
                        <div class="detail-value">
                            <?= $data['tgl_kembali'] ?>
                        </div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">
                            <i class="fa fa-circle-info me-2"></i>
                            Status
                        </div>
                        <div class="detail-value">
                            <span class="<?= $statusClass ?>"><?= $data['status'] ?></span>
                        </div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">
                            <i class="fa fa-file-lines text-danger me-2"></i>File Pendukung</div>
                        <span>
                            <?php if($data['file_pengajuan']): ?>
                                <?php
                                    $file_pengajuan_path = $data['file_pengajuan'];
                                    $file_pengajuan_ext = pathinfo($file_pengajuan_path, PATHINFO_EXTENSION);
                                    $file_pengajuan_name = "Bukti Peminjaman.{$file_pengajuan_ext}";
                                ?>
                                <button class="btn btn-sm btn-outline-danger"
                                        data-bs-toggle="modal"
                                        data-bs-target="#previewModal"
                                        onclick="previewImage('<?= $file_pengajuan_path ?>','Bukti Peminjaman BMN')">
                                    <i class="fas fa-file me-1"></i> <?= $file_pengajuan_name ?>
                                </button>
                            <?php endif; ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        </div>
            <?php if($data['status'] != 'Diajukan'): ?>
            <div class="card shadow-sm rounded-4">
                <div class="card-header card-header-grad rounded-top-4">
                    <i class="fa fa-file-circle-plus me-2"></i>Bukti Serah Terima
                </div>
                <div class="card-body">
                    <?php if($data['bukti_serah']): ?>
                        <div class="detail-row justify-content-between align-items-center">
                            <div class="detail-documentation-label">
                                <i class="fas fa-file-lines me-2"></i>
                                Dokumentasi Bukti Penyerahan BMN
                            </div>
                            <div class="detail-value text-center">
                                <span>
                                    <?php if($data['bukti_serah']): ?>
                                        <?php
                                            $bukti_serah_path = "dokumen/".$data['bukti_serah'];
                                            $bukti_serah_ext = pathinfo($bukti_serah_path, PATHINFO_EXTENSION);
                                            $bukti_serah_name = "Bukti Penyerahan.{$bukti_serah_ext}";
                                        ?>
                                        <button class="btn btn-sm btn-outline-primary"
                                                data-bs-toggle="modal"
                                                data-bs-target="#previewModal"
                                                onclick="previewImage('<?= $bukti_serah_path ?>','Bukti Penyerahan BMN')">
                                            <i class="fas fa-file me-1"></i> <?= $bukti_serah_name ?>
                                        </button>
                                        </a>
                                    <?php endif; ?>
                                </span><br>
                                <small class="text-muted">
                                    <?= $data['tgl_serah'] ?>
                                </small>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php if($data['status']=='Selesai' && $data['bukti_terima']): ?>
                        <div class="detail-row justify-content-between align-items-center">
                            <div class="detail-documentation-label">
                                <i class="fa fa-file-lines me-2"></i>
                                Dokumentasi Bukti Pengembalian BMN
                            </div>
                            <div class="detail-value text-center">
                                <span>
                                    <?php if($data['bukti_terima']): ?>
                                        <?php
                                            $bukti_terima_path = "dokumen/".$data['bukti_terima'];
                                            $bukti_terima_ext = pathinfo($bukti_terima_path, PATHINFO_EXTENSION);
                                            $bukti_terima_name = "Bukti Pengembalian.{$bukti_terima_ext}";
                                        ?>
                                        <button class="btn btn-sm btn-outline-primary"
                                                data-bs-toggle="modal"
                                                data-bs-target="#previewModal"
                                                onclick="previewImage('<?= $bukti_terima_path ?>','Bukti Pengembalian BMN')">
                                            <i class="fas fa-file me-1"></i> <?= $bukti_terima_name ?>
                                        </button>
                                    <?php endif; ?>
                                </span><br>
                                <small class="text-muted">
                                    <?= $data['tgl_terima'] ?>
                                </small>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <div class="modal fade" id="previewModal" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content shadow">
                <div class="modal-header text-white"
                    style="background:linear-gradient(90deg,#1f4e5f,#2e9c94);">
                    <h5 class="modal-title" id="previewTitle">
                    Preview Foto
                    </h5>
                    <button type="button"
                            class="btn-close btn-close-white"
                            data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center" id="previewContainer">
                    <img id="previewImage"
                        src=""
                        class="img-fluid rounded shadow"
                        style="max-height:75vh; object-fit:contain; display:none;">
                    <iframe id="previewPDF"
                        src=""
                        style="width:100%; height:75vh; border:none; display:none;"></iframe>
                </div>
            </div>
        </div>
        </div>
        <script>
        function previewImage(src, title){
            document.getElementById('previewImage').src = src;
            document.getElementById('previewTitle').innerText = title;
        }
        </script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
        <script>
            function previewImage(src, title){
            document.getElementById('previewTitle').innerText = title;
            var img = document.getElementById('previewImage');
            var pdf = document.getElementById('previewPDF');
            var ext = src.split('.').pop().toLowerCase();
            if(['jpg','jpeg','png','gif','bmp','webp'].includes(ext)){
                img.src = src;
                img.style.display = '';
                pdf.style.display = 'none';
            } else if(ext === 'pdf'){
                pdf.src = src;
                pdf.style.display = '';
                img.style.display = 'none';
            } else {
                img.style.display = 'none';
                pdf.style.display = 'none';
            }
        }
        </script>
    </body>
</html>