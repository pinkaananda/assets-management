<?php
  session_start();
  include "koneksi.php";
  date_default_timezone_set('Asia/Jakarta');

  $_SESSION['nip'] = '089647057385';
  $_SESSION['kd_satker'] = '694762';
  $_SESSION['role'] = 'admin';

  $kode_satker = $_SESSION['kd_satker'];
  $nip_login   = $_SESSION['nip'];
  $role        = $_SESSION['role'];

  if(isset($_POST['confirm_batal'])){
    if($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'staf umum'){
      $id = $_POST['id_pengajuan'];
      $conn->query("UPDATE pengajuan_bmn 
      SET status='Batal' 
      WHERE id_pengajuan='$id'");
      $page = $_GET['page'] ?? 1;
      $search = $_GET['searchTerm'] ?? '';

      header("Location: dashboard_pengajuan_bmn.php?page=$page&searchTerm=$search");
      exit;
    }
  }
  if(isset($_POST['upload_serah'])){
    $id = $_POST['id_pengajuan'];
    $uploadDir = "dokumen/";

    if(!is_dir($uploadDir)){
        mkdir($uploadDir,0777,true);
    }
    if($_FILES['file_serah']['name']!=''){
        $ext = pathinfo($_FILES['file_serah']['name'], PATHINFO_EXTENSION);
        $nama = "serah"."_".time().".".$ext;
        move_uploaded_file($_FILES['file_serah']['tmp_name'], $uploadDir.$nama);

        $conn->query("UPDATE pengajuan_bmn 
          SET bukti_serah='$nama',
              tgl_serah=NOW(),
              status='Disetujui'
          WHERE id_pengajuan='$id'");
    }
  }
  if(isset($_POST['upload_terima'])){
    $id = $_POST['id_pengajuan'];
    $uploadDir = "dokumen/";

    if($_FILES['file_terima']['name']!=''){

        $ext = pathinfo($_FILES['file_terima']['name'], PATHINFO_EXTENSION);
        $nama = "terima"."_".time().".".$ext;

        move_uploaded_file($_FILES['file_terima']['tmp_name'], $uploadDir.$nama);

        $conn->query("UPDATE pengajuan_bmn 
            SET bukti_terima='$nama',
                tgl_terima=NOW(),
                status='Selesai'
            WHERE id_pengajuan='$id'");
    }
  }
  function uploadDokumen($fileInput, $folder = 'dokumen'){
    if(empty($_FILES[$fileInput]['name'])){
        return null;
    }
    if(!is_dir($folder)){
        mkdir($folder,0777,true);
    }
    $fileTmp  = $_FILES[$fileInput]['tmp_name'];
    $fileSize = $_FILES[$fileInput]['size'];
    $fileErr  = $_FILES[$fileInput]['error'];

    if($fileErr !== 0){
        return null;
    }
    if($fileSize > 5 * 1024 * 1024){
        return null;
    }
    $ext = strtolower(pathinfo($_FILES[$fileInput]['name'], PATHINFO_EXTENSION));

    $allowed = ['pdf','jpg','jpeg','png','doc','docx','xls','xlsx'];

    if(!in_array($ext,$allowed)){
        return null;
    }
    $uniqueName = uniqid().'.'.$ext;
    $path = $folder.'/'.$uniqueName;
    if(move_uploaded_file($fileTmp,$path)){
        return $path;
    }
    return null;
  }

  if(isset($_POST['update_pengajuan'])){

    $id = $_POST['id_pengajuan'];
    $nip_peminjam = $_POST['nip_peminjam'];
    $unit_kerja   = $_POST['unit_kerja'];
    $tgl_pinjam   = $_POST['tgl_pinjam'];
    $tgl_kembali  = $_POST['tgl_kembali'];
    $keterangan   = $_POST['keterangan'];

    $lama = (strtotime($tgl_kembali) - strtotime($tgl_pinjam))/86400;

    $upload = uploadDokumen('file_pengajuan');

    if($upload){

      $old = $conn->query("
      SELECT file_pengajuan 
      FROM pengajuan_bmn 
      WHERE id_pengajuan='$id'
      ")->fetch_assoc();

    if(!empty($old['file_pengajuan']) && file_exists($old['file_pengajuan'])){
      unlink($old['file_pengajuan']);
    }
    $stmt = $conn->prepare("
      UPDATE pengajuan_bmn
      SET nip_peminjam=?,
      unit_kerja=?,
      tgl_pinjam=?,
      tgl_kembali=?,
      lama_hari=?,
      keterangan=?,
      file_pengajuan=?
      WHERE id_pengajuan=?
    ");

    $stmt->bind_param(
      "ssssissi",
      $nip_peminjam,
      $unit_kerja,
      $tgl_pinjam,
      $tgl_kembali,
      $lama,
      $keterangan,
      $upload,
      $id
    );

    }else{

      $stmt = $conn->prepare("
        UPDATE pengajuan_bmn
        SET nip_peminjam=?,
        unit_kerja=?,
        tgl_pinjam=?,
        tgl_kembali=?,
        lama_hari=?,
        keterangan=?
        WHERE id_pengajuan=?
      ");

      $stmt->bind_param(
        "ssssisi",
        $nip_peminjam,
        $unit_kerja,
        $tgl_pinjam,
        $tgl_kembali,
        $lama,
        $keterangan,
        $id
      );
    }

    $stmt->execute();
    $page = $_GET['page'] ?? 1;
    $search = $_GET['searchTerm'] ?? '';
    header("Location: dashboard_pengajuan_bmn.php?page=$page&searchTerm=$search");
    exit;
  }

  $search = $_GET['searchTerm'] ?? '';
  $where = "";

  $limit = 10;
  $page  = isset($_GET['page']) ? (int)$_GET['page'] : 1;
  if($page < 1) $page = 1;
  $start = ($page - 1) * $limit;

  $where = "WHERE p.kd_satker='$kode_satker'";

  if($role == 'user'){
      $where .= " AND (
          p.nip_pemohon = '$nip_login'
          OR p.nip_peminjam = '$nip_login'
      )";
  }
  if($search){
    $searchLike = "%".$conn->real_escape_string($search)."%";
    $where .= " AND (
        pg.nama_pegawai LIKE '$searchLike'
        OR b.kode_barang LIKE '$searchLike'
        OR k.ur_sskel LIKE '$searchLike'
    )";
  }
  $totalQuery = $conn->query("
    SELECT COUNT(DISTINCT p.id_pengajuan) as total
    FROM pengajuan_bmn p
    LEFT JOIN tb_pegawai pg ON p.nip_peminjam = pg.nip_pegawai
    LEFT JOIN pengajuan_bmn_detail d ON p.id_pengajuan=d.id_pengajuan
    LEFT JOIN daftar_bmn b ON d.id_bmn=b.id_bmn
    LEFT JOIN tb_kode_barang k ON b.kode_barang=k.kode_barang
    $where
  ");
  $totalData = $totalQuery->fetch_assoc()['total'];
  $totalPage = ceil($totalData / $limit);

  $q = $conn->query("
    SELECT DISTINCT p.*, pg.nama_pegawai, pg.UNIT_II
    FROM pengajuan_bmn p
    LEFT JOIN tb_pegawai pg ON p.nip_peminjam = pg.nip_pegawai
    LEFT JOIN pengajuan_bmn_detail d ON p.id_pengajuan=d.id_pengajuan
    LEFT JOIN daftar_bmn b ON d.id_bmn=b.id_bmn
    LEFT JOIN tb_kode_barang k ON b.kode_barang=k.kode_barang
    $where
    ORDER BY p.id_pengajuan DESC
    LIMIT $start,$limit
  ");
?>
<!DOCTYPE html>
<html>
  <head>
    <meta charset="UTF-8">
    <title>Dashboard Pengajuan BMN</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
      body{background:#f5f6fa;font-family:'Inter',sans-serif}
      .header-grad{background:linear-gradient(90deg,#1c3f66,#2d8b85);color:white;padding:15px 20px;border-radius:12px;}
      .badge-status{padding:6px 12px;font-size:.75rem;font-weight:600}
      .status-diajukan{background:#f1a208;color:white}
      .status-disetujui{background:#198754;color:white}
      .status-selesai{background:#6c757d;color:white}
      .status-batal{background:#dc3545;color:white}
      .no-resize {resize: none;}
      .text-muted{opacity: 0.8;}
      .label-edit{color:#22466c;}
      .modal-footer-submit{border-top: none !important;}
      .card-header-grad{background:linear-gradient(90deg,#22466c,#2a9086);color:white;}
      .btn-search{background-color:#22466C;color:#fff;}
      .btn-reset{border-color:solid #000;background-color: #fff;}
      .btn-outline-info{border-color:#22466c !important;color:#22466c !important;}
      .btn-outline-info:hover{background-color:#22466c !important;border-color:#22466c !important;color:#fff !important;}
      .btn-outline-warning{border-color:#ffc107 !important;color:#ffc107 !important;}
      .btn-outline-warning:hover{background-color:#ffc107 !important;border-color:#ffc107 !important;color:#fff !important;}
      .btn-outline-danger:hover{background-color:#dc3545 !important;border-color:#dc3545 !important;color:#fff !important;}
      .btn-outline-success:hover{background-color:#198754 !important;border-color:#198754 !important;color:#fff !important;}
      .btn-outline-primary:hover{background-color:#0d6efd !important;border-color:#0d6efd !important;color:#fff !important;}
      .aksi-btn:hover i{color:#fff !important;}
      .aksi-btn{width:30px;height:30px;display:flex;align-items:center;justify-content:center;padding:0;}
      .table-header-custom{background: linear-gradient(90deg,#1f4e5f,#2e9c94);}
      .table-header-custom th{background: transparent !important;color:#fff;border:none !important;font-weight:600;padding:14px 12px; vertical-align: middle;}
      .custom-table{border-collapse: separate;border-spacing: 0;}
      .custom-table thead tr th:first-child{border-top-left-radius:10px;}
      .custom-table thead tr th:last-child{border-top-right-radius:10px;}
      .custom-table tbody tr{border-bottom:1px solid #e9ecef;}
      .custom-table tbody tr:hover{background:#f8fbfc;transition:0.2s;}
      .modal-body .row{padding:10px 12px;border-bottom:1px solid #f1f1f1;margin-left:-16px;margin-right:-16px;}
      .btn-file{background:#F5F5F5;color:#2A6099;cursor:pointer;}
      .file-placeholder{background:#fff;cursor:pointer;}
      .alert-terima{background: rgba(13,110,253,0.1);}
    </style>
  </head>
  <body>
    <div class="container mt-4">
      <div class="rounded-4 shadow-sm mb-4" style="background:linear-gradient(90deg,#1f4e5f,#2e9c94); padding:18px 25px;color:white;">
        <h5 class="mb-0 fw-semibold">
            <i class="fa-solid fa-cubes me-2"></i>
            Pengajuan Barang Milik Negara (BMN)
        </h5>
      </div>
      <div class=" col-md-12 p-2 mb-2">
        <form method="GET" class="mb-4">
          <label class="form-label fw-semibold mb-1">
              Cari Pengajuan BMN
          </label>
          <div class="row g-2 align-items-stretch">
            <div class="col-12 col-md-8">
              <input type="text" name="searchTerm" class="form-control" placeholder="Masukkan kata kunci..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-12 col-md-4">
              <div class="d-flex gap-2">
                <button class="btn flex-fill" style="background:#0b5c6d;color:white;">
                  <i class="fa fa-search me-1"></i> Cari
                </button>
                <a href="dashboard_pengajuan_bmn.php" class="btn btn-outline-secondary flex-fill">
                  <i class="fa fa-undo me-1"></i> Reset
                </a>
              </div>
            </div>
          </div>
        </form>
        <div class="text-end mb-3">
          <a href="export_pengajuan_bmn.php?searchTerm=<?= urlencode($search) ?>" class="btn btn-success p-2">
            <i class="fa fa-file-excel me-1"></i> Download Excel
          </a>
        </div>
      </div>
      <div class="table-responsive shadow-sm rounded bg-white mt-2">
        <table class="table custom-table mb-0">
          <thead class="table-header-custom">
            <tr style="background:linear-gradient(90deg,#005267,#008b8b);color:#fff">
              <th class="text-center" style="width:5%">No</th>
              <th style="width:25%">Peminjam</th>
              <th style="width:25%">Barang/Kode - NUP</th>
              <th class="text-center" style="width:11%">Tgl Pinjam</th>
              <th class="text-center" style="width:11%">Tgl Kembali</th>
              <th class="text-center" style="width:10%">Status</th>
              <th class="text-center" style="width:15%">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php
              $no = ($page - 1) * $limit + 1;
              while($r = $q->fetch_assoc()):
              $detail = $conn->query("
              SELECT 
                  b.kode_barang,
                  b.NUP,
                  k.ur_sskel
              FROM pengajuan_bmn_detail d
              JOIN daftar_bmn b 
                  ON d.id_bmn = b.id_bmn
              LEFT JOIN tb_kode_barang k
                  ON b.kode_barang = k.kode_barang
              WHERE d.id_pengajuan = '$r[id_pengajuan]'");

              $barang="";
              while($d=$detail->fetch_assoc()){
                $barang .= 
                '<div class="mb-2">
                    <div class="fw-semibold">'.$d['ur_sskel'].'</div>
                      <div class="text-muted small">
                        <i class="fa fa-barcode me-2"></i>'.$d['kode_barang'].' - NUP '.$d['NUP'].'
                    </div>
                  </div>
                ';
              }
              $statusClass="";
              if($r['status']=="Diajukan") $statusClass="status-diajukan";
              if($r['status']=="Disetujui") $statusClass="status-disetujui";
              if($r['status']=="Selesai") $statusClass="status-selesai";
              if($r['status']=="Batal") $statusClass="status-batal";
            ?>
            <tr>
              <td><?= $no++ ?></td>
              <td>
                <strong><?= $r['nama_pegawai'] ?></strong><br>
                <?php if(!empty($r['UNIT_II'])): ?>
                  <div class="text-muted small">
                    <i class="fas fa-building"></i> <?= $r['UNIT_II'] ?>
                  </div>
                <?php endif; ?>
              </td>
              <td><?= $barang ?></td>
              <td class="text-muted"><?= $r['tgl_pinjam'] ?></td>
              <td class="text-muted"><?= $r['tgl_kembali'] ?></td>
              <td><span class="rounded-4 badge-status <?= $statusClass ?>"><?= $r['status'] ?></span></td>
              <td>
                <div class="d-flex justify-content-center align-items-center gap-1 flex-nowrap">
                  <a href="detail_bmn.php?id=<?= $r['id_pengajuan'] ?>" class="btn btn-sm btn-outline-info aksi-btn"><i class="fa fa-eye"></i></a>
                  <?php if(
                    ($_SESSION['role'] == 'user' || $_SESSION['role'] == 'staf umum' || $_SESSION['role'] == 'admin')
                    && empty($r['bukti_terima']) && $r['status'] != 'Batal'): ?>
                    <button class="btn btn-sm btn-outline-primary aksi-btn" data-bs-toggle="modal" data-bs-target="#uploadModal<?= $r['id_pengajuan'] ?>">
                      <i class="fa fa-upload"></i>
                    </button>
                  <?php endif; ?>
                  <?php if($r['status'] != 'Selesai' && $r['status'] != 'Batal'): ?>
                    <?php if($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'staf umum'): ?>
                      <button class="btn btn-sm btn-outline-danger aksi-btn" data-bs-toggle="modal" data-bs-target="#batalModal<?= $r['id_pengajuan'] ?>">
                        <i class="fa fa-times"></i>
                      </button>
                    <?php endif; ?>
                    <button class="btn btn-sm btn-outline-warning aksi-btn" data-bs-toggle="modal" data-bs-target="#edit<?= $r['id_pengajuan'] ?>">
                      <i class="fa fa-pen"></i>
                    </button>
                  <?php endif; ?>
                  <button class="btn btn-sm btn-outline-success aksi-btn" data-bs-toggle="modal" data-bs-target="#fileModal<?= $r['id_pengajuan'] ?>">
                    <i class="fa fa-file"></i>
                  </button>
                </div>
              </td>
            </tr>
            <div class="modal fade" id="edit<?= $r['id_pengajuan'] ?>" tabindex="-1">
              <div class="modal-dialog modal-lg">
                <div class="modal-content">
                  <div class="modal-header text-white bg-primary">
                    <h5 class="modal-title">
                      <i class="fa fa-pen me-2"></i>
                      Ubah Data Pengajuan BMN
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                  </div>
                  <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                      <input type="hidden" name="id_pengajuan" value="<?= $r['id_pengajuan'] ?>">
                      <div class="container-fluid">
                        <div class="row align-items-center mb-1">
                          <div class="label-edit col-md-4 fw-semibold">
                            Nama Peminjam
                          </div>
                          <div class="col-md-8 position-relative">
                            <input type="text"
                              id="searchPegawai<?= $r['id_pengajuan'] ?>"
                              class="form-control"
                              value="<?= $r['nama_pegawai'] ?>"
                              autocomplete="off"
                              required placeholder="Nama pegawai yang meminjam">
                            <input type="hidden"
                              name="nip_peminjam"
                              id="nipPeminjam<?= $r['id_pengajuan'] ?>"
                              value="<?= $r['nip_peminjam'] ?>">
                            <div id="dropdownPegawai<?= $r['id_pengajuan'] ?>" class="list-group position-absolute w-100 shadow" style="z-index:1000; display:none;"></div>
                          </div>
                        </div>
                        <div class="row align-items-center mb-1">
                          <div class="label-edit col-md-4 fw-semibold">
                            Unit Kerja
                          </div>
                          <div class="col-md-8">
                              <input type="text"
                                    id="unitKerja<?= $r['id_pengajuan'] ?>"
                                    class="form-control bg-light"
                                    name="unit_kerja"
                                    value="<?= $r['UNIT_II'] ?>"
                                    readonly placeholder="Unit kerja pegawai">
                          </div>
                        </div>
                        <div class="row mb-1">
                          <div class="label-edit col-md-4 fw-semibold">
                            Barang Yang Dipinjam
                          </div>
                          <div class="col-md-8 text-muted">
                            <div class="form-control bg-light text-muted" style="height:auto;">
                              <?php
                              $detail2 = $conn->query("
                                  SELECT b.kode_barang, b.NUP
                                  FROM pengajuan_bmn_detail d
                                  JOIN daftar_bmn b ON d.id_bmn=b.id_bmn
                                  WHERE d.id_pengajuan='$r[id_pengajuan]'
                              ");
                              $noBarang = 1;
                              while($b2 = $detail2->fetch_assoc()):
                              ?>
                                  <div><?= $noBarang++ ?>. <?= $b2['kode_barang'] ?> - NUP <?= $b2['NUP'] ?></div>
                              <?php endwhile; ?>
                            </div>
                          </div>
                        </div>
                        <div class="row align-items-center mb-1">
                          <div class="label-edit col-md-4 fw-semibold">
                            Tanggal Pengajuan
                          </div>
                          <div class="col-md-8">
                            <input type="text"
                                  class="form-control bg-light text-muted"
                                  value="<?= $r['tgl_pengajuan'] ?>"
                                  readonly>
                          </div>
                        </div>
                        <div class="row mb-1">
                          <div class="label-edit col-md-4 fw-semibold">
                              Keterangan
                          </div>
                          <div class="col-md-8">
                            <textarea name="keterangan"
                                      class="form-control"
                                      rows="3"><?= $r['keterangan'] ?></textarea>
                          </div>
                        </div>
                        <div class="row align-items-center mb-1">
                          <div class="label-edit col-md-4 fw-semibold">
                              Tanggal Pinjam
                          </div>
                          <div class="col-md-8">
                              <input type="date"
                                    name="tgl_pinjam"
                                    value="<?= $r['tgl_pinjam'] ?>"
                                    class="form-control">
                          </div>
                        </div>
                        <div class="row align-items-center mb-1">
                          <div class="label-edit col-md-4 fw-semibold">
                              Tanggal Pengembalian
                          </div>
                          <div class="col-md-8">
                            <input type="date"
                                  name="tgl_kembali"
                                  value="<?= $r['tgl_kembali'] ?>"
                                  class="form-control">
                          </div>
                        </div>
                        <div class="row align-items-center mb-1">
                          <div class="label-edit col-md-4 fw-semibold">
                              Status
                          </div>
                          <div class="col-md-8">
                              <span class="rounded-4 badge-status <?= $statusClass ?>">
                                  <?= $r['status'] ?>
                              </span>
                          </div>
                        </div>
                        <div class="row align-items-center mb-1">
                          <div class="label-edit col-md-4 fw-semibold">
                            File Pendukung
                          </div>
                          <div class="col-md-8">
                            <div class="input-group file-group">
                              <input type="file" name="file_pengajuan" id="fileUpload<?= $r['id_pengajuan'] ?>" class="form-control" hidden>
                              <label class="input-group-text btn-file"
                                for="fileUpload<?= $r['id_pengajuan'] ?>">
                                Pilih File
                              </label>
                              <div id="fileName<?= $r['id_pengajuan'] ?>"
                                class="form-control text-muted file-placeholder"
                                data-old="<?= basename($r['file_pengajuan']) ?>">
                                <?= $r['file_pengajuan'] ? basename($r['file_pengajuan']) : 'Tidak ada file yang dipilih' ?>
                              </div>
                            </div>
                            <small class="text-muted d-block mt-1">
                              Maksimal ukuran file 5MB
                            </small>
                          </div>
                        </div>
                        <div class="modal-footer-submit d-flex justify-content-center mt-3 mb-4">
                          <button type="submit"
                            name="update_pengajuan"
                            class="btn btn-primary">
                            Simpan Perubahan
                          </button>
                        </div>
                      </div>
                    </div>
                  </form>
                </div>
              </div>
            </div>
            <div class="modal fade" id="fileModal<?= $r['id_pengajuan'] ?>" tabindex="-1">
              <div class="modal-dialog modal-xl">
                <div class="modal-content">
                  <div class="modal-header text-white" style="background:linear-gradient(90deg,#1f4e5f,#2e9c94);">
                    <h5 class="modal-title">
                      <i class="fa fa-file me-2"></i>
                      Preview File Pendukung
                    </h5>
                    <button type="button" class="btn-close btn-close-white"
                            data-bs-dismiss="modal"></button>
                  </div>
                  <div class="modal-body text-center">
                    <?php
                      $file = $r['file_pengajuan'];
                      $ext  = strtolower(pathinfo($file, PATHINFO_EXTENSION));?>
                    <?php if($ext == 'pdf'): ?>
                      <iframe src="<?= $file ?>" width="100%" height="600px" style="border:none;">
                      </iframe>
                    <?php elseif(in_array($ext,['jpg','jpeg','png','gif'])): ?>
                      <img src="<?= $file ?>" class="img-fluid rounded shadow" style="max-height:600px">
                    <?php else: ?>
                      <div class="alert alert-warning">
                          File tidak dapat dipreview.
                          <br><br>
                          <a href="<?= $file ?>"
                            target="_blank"
                            class="btn btn-primary">
                            Download File
                          </a>
                      </div>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            </div>
            <div class="modal fade" id="batalModal<?= $r['id_pengajuan'] ?>" tabindex="-1" aria-hidden="true">
              <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                  <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fa fa-exclamation-triangle me-2"></i>
                      Konfirmasi Pembatalan
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                  </div>
                  <form method="POST">
                    <div class="modal-body">
                      <input type="hidden" name="id_pengajuan" value="<?= $r['id_pengajuan'] ?>">
                      <p> Apakah Anda yakin ingin membatalkan pengajuan ini? </p>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-secondary"data-bs-dismiss="modal">
                        Tidak
                      </button>
                      <button type="submit" name="confirm_batal" class="btn btn-danger">
                        Ya, Batalkan
                      </button>
                    </div>
                  </form>
                </div>
              </div>
            </div>
            <div class="modal fade" id="uploadModal<?= $r['id_pengajuan'] ?>" tabindex="-1">
              <div class="modal-dialog modal-lg">
                <div class="modal-content">
                  <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                      <i class="fa fa-upload me-2"></i>
                      Upload Bukti Serah Terima BMN
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                  </div>
                  <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body p-4">
                      <input type="hidden" name="id_pengajuan" value="<?= $r['id_pengajuan'] ?>">
                      <?php if(empty($r['bukti_serah'])): ?>
                        <label class="fw-semibold mb-2" style="color:#22466c">
                          <i class="fa fa-file-import me-2"></i>Ambil atau pilih foto Bukti Serah (JPG/PNG)
                        </label>
                        <div class="input-group file-group">
                          <input type="file"
                            name="file_serah"
                            id="fileUploadSerah<?= $r['id_pengajuan'] ?>"
                            class="form-control"
                            hidden required>
                          <label class="input-group-text btn-file"
                            for="fileUploadSerah<?= $r['id_pengajuan'] ?>">
                            Pilih File
                          </label>
                          <div id="fileNameSerah<?= $r['id_pengajuan'] ?>"
                            class="form-control text-muted file-placeholder"
                            data-old=""> Tidak ada file yang dipilih
                          </div>
                        </div>
                        <small class="text-muted">
                          Maksimal ukuran 5MB
                        </small>
                      <?php elseif(empty($r['bukti_terima'])): ?>
                        <div class="alert alert-terima">
                          <b>Bukti Serah sudah diupload:</b><br>
                          <a href="dokumen/<?= $r['bukti_serah'] ?>"
                            target="_blank">
                            <?= $r['bukti_serah'] ?>
                          </a>
                        </div>
                        <label class="fw-semibold mb-2" style="color:#22466c">
                          <i class="fa fa-file-import me-2"></i>Ambil atau pilih foto Bukti Terima (JPG/PNG)
                        </label>
                        <div class="input-group file-group">
                          <input type="file"
                            name="file_terima"
                            id="fileUploadTerima<?= $r['id_pengajuan'] ?>"
                            class="form-control"
                            hidden required>
                          <label class="input-group-text btn-file"
                            for="fileUploadTerima<?= $r['id_pengajuan'] ?>">
                            Pilih File
                          </label>
                          <div id="fileNameTerima<?= $r['id_pengajuan'] ?>"
                            class="form-control text-muted file-placeholder"
                            data-old=""> Tidak ada file yang dipilih
                          </div>
                        </div>
                        <small class="text-muted">
                          Maksimal ukuran 5MB
                        </small>
                      <?php endif; ?>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        Batal
                      </button>
                      <?php if(empty($r['bukti_serah'])): ?>
                          <button class="btn btn-primary" name="upload_serah">
                            Simpan
                          </button>
                      <?php elseif(empty($r['bukti_terima'])): ?>
                          <button class="btn btn-primary" name="upload_terima">
                            Simpan
                          </button>
                      <?php endif; ?>
                    </div>
                  </form>
                </div>
              </div>
            </div>
            <?php endwhile; ?>
          </tbody>
        </table>
        <div class="text-center mt-4">
          <nav>
            <ul class="pagination justify-content-center mb-2">
              <?php
                $queryString = "&searchTerm=".$search;
                if($page > 1){
                  echo '<li class="page-item">
                  <a class="page-link" href="?page=1'.$queryString.'">&laquo;</a>
                  </li>';
                }
                for($i=1;$i<=$totalPage;$i++){
                  $active = ($i==$page) ? "active" : "";
                  echo '<li class="page-item '.$active.'">
                  <a class="page-link" href="?page='.$i.$queryString.'">'.$i.'</a>
                  </li>';
                }
                // Tombol ke halaman terakhir
                if($page < $totalPage){
                  echo '<li class="page-item">
                  <a class="page-link" href="?page='.$totalPage.$queryString.'">&raquo;</a>
                  </li>';
                }
              ?>
            </ul>
          </nav>
          <div class="text-muted small">
            Halaman <?= $page ?> dari <?= $totalPage ?> 
            (Total: <?= $totalData ?> data)
          </div>
        </div>
      </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
      document.querySelectorAll('[id^="searchPegawai"]').forEach(input => {
        let id = input.id.replace('searchPegawai','');
        let dropdown = document.getElementById('dropdownPegawai'+id);
        let nipInput = document.getElementById('nipPeminjam'+id);
        let unitInput = document.getElementById('unitKerja'+id);

        input.addEventListener('keyup', function(){
          let q = this.value;
          if(q.length < 2){
              dropdown.style.display='none';
              return;
          }

          fetch(`form_pengajuan_bmn.php?action=search_pegawai&q=${q}`)
          .then(res=>res.json())
          .then(data=>{
              let html='';
              data.forEach(p=>{
                  html+=`
                  <a href="#"
                    class="list-group-item list-group-item-action"
                    data-nip="${p.nip_pegawai}"
                    data-unit="${p.UNIT_II}">
                    <strong>${p.nama_pegawai}</strong><br>
                    <small>${p.nip_pegawai}</small>
                  </a>`;
              });
              dropdown.innerHTML=html;
              dropdown.style.display='block';
          });
        });

        dropdown.addEventListener('click', function(e){
          e.preventDefault();
          let item=e.target.closest('.list-group-item');
          if(!item) return;

          input.value=item.querySelector('strong').innerText;
          nipInput.value=item.dataset.nip;
          unitInput.value=item.dataset.unit;

          dropdown.style.display='none';
        });

      });
      document.querySelectorAll('[id^="fileUpload"]').forEach(function(input){
              
        let id = input.id.replace('fileUpload','');
        let nameBox = document.getElementById('fileName'+id);
        let oldName = nameBox.innerText;

        input.addEventListener('change', function(){
          if(this.files && this.files.length > 0){
            nameBox.innerText = this.files[0].name;
          }else{
            nameBox.innerText = oldName;
          }
        });
        nameBox.addEventListener('click', function(){
          input.click();
        });
      });
      document.querySelectorAll('.modal').forEach(function(modal){

        modal.addEventListener('hidden.bs.modal', function(){
          let fileInputs = modal.querySelectorAll('[id^="fileUpload"]');
          fileInputs.forEach(function(input){

            let id = input.id.replace('fileUpload','');
            let nameBox = document.getElementById('fileName'+id);

            let oldFile = nameBox.getAttribute('data-old');

            input.value = '';

            nameBox.innerText = oldFile ? oldFile : 'Tidak ada file yang dipilih';
          });
        });
      });

      document.querySelectorAll('[id^="fileUploadSerah"]').forEach(function(input){

        let id = input.id.replace('fileUploadSerah','');
        let nameBox = document.getElementById('fileNameSerah'+id);

        input.addEventListener('change', function(){
          if(this.files.length > 0){
            nameBox.innerText = this.files[0].name;
          }
        });
      });
      document.querySelectorAll('[id^="fileUploadTerima"]').forEach(function(input){

        let id = input.id.replace('fileUploadTerima','');
        let nameBox = document.getElementById('fileNameTerima'+id);

        input.addEventListener('change', function(){
          if(this.files.length > 0){
          nameBox.innerText = this.files[0].name;
          }
        });
      });
    </script>
  </body>
</html>