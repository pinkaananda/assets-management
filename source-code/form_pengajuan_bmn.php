<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Asia/Jakarta');

include "koneksi.php";

if ($_SERVER['SERVER_NAME'] === 'localhost') {

    $kode_satker  = '694762';
    $nip_pemohon  = '3510007854612345'; // dummy login

} else {

    $lokasiRoot = "/home/u299101980/domains/demenumkm.info/public_html/";

    include $lokasiRoot.'controller/user.php';
    include $lokasiRoot.'koneksi.php';

    $kode_satker = $satker;
    $nip_pemohon = $nip; // dari login
}

/* ===============================
   AMBIL NAMA PEMOHON DARI tb_user
================================= */

$qUser = $conn->prepare("
SELECT nama
FROM tb_user
WHERE nip = ?
");

$qUser->bind_param("s",$nip_pemohon);
$qUser->execute();
$user = $qUser->get_result()->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    try {
        $conn->begin_transaction();

        $bmnIds = $_POST['id_bmn'] ?? [];
        if (!is_array($bmnIds)) $bmnIds = [$bmnIds];

        $nip_peminjam    = $_POST['nip_peminjam'] ?? '';
        $unit_kerja      = $_POST['unit_kerja'] ?? '';
        $no_hp      = $_POST['no_hp'] ?? '';
        $tanggal_pinjam  = $_POST['tanggal_pinjam'] ?? '';
        $tanggal_kembali = $_POST['tanggal_kembali'] ?? '';
        $keterangan      = $_POST['keterangan'] ?? '';

        if(empty($nip_peminjam)) throw new Exception("Nama peminjam belum dipilih");
        if(empty($bmnIds)) throw new Exception("Barang belum dipilih");
        if(empty($no_hp)) throw new Exception("Nomor WhatsApp belum diisi");

        $lama = (strtotime($tanggal_kembali) - strtotime($tanggal_pinjam)) / 86400;
        if ($lama < 0) throw new Exception("Tanggal kembali salah");

        foreach($bmnIds as $id_bmn){

            $cek = $conn->prepare("
                SELECT d.id_detail
                FROM pengajuan_bmn_detail d
                JOIN pengajuan_bmn h 
                    ON d.id_pengajuan = h.id_pengajuan
                WHERE d.id_bmn = ?
                AND h.status IN ('Diajukan','Disetujui')
            ");
            $cek->bind_param("s",$id_bmn);
            $cek->execute();
            $cek->store_result();

            if($cek->num_rows > 0){
                throw new Exception("Barang yang diajukan sedang dipinjam.");
            }
        }

        $upload = '';

        if (!empty($_FILES['file_upload']['name'])) {

        if (!is_dir('dokumen')) {
            mkdir('dokumen', 0777, true);
        }

        $fileTmp  = $_FILES['file_upload']['tmp_name'];
        $fileSize = $_FILES['file_upload']['size'];
        $fileError= $_FILES['file_upload']['error'];

        if ($fileError === 0) {

            $ext = strtolower(pathinfo($_FILES['file_upload']['name'], PATHINFO_EXTENSION));

            $allowed = ['pdf','doc','docx','xls','xlsx','png','jpg','jpeg'];
            if (!in_array($ext, $allowed)) {
                echo "<script>alert('Format file tidak diizinkan');history.back();</script>";
                exit;
            }

            $uniqueName = uniqid() . '.' . $ext;

            $upload = 'dokumen/'.$uniqueName;

            move_uploaded_file($fileTmp, $upload);
        }
    }
        $no_pengajuan = "BMN-".date("YmdHis");

        $stmt = $conn->prepare("
            INSERT INTO pengajuan_bmn
            (no_pengajuan,
             nip_pemohon,
             nip_peminjam,
             unit_kerja,
             no_hp,
             kd_satker,
             tgl_pinjam,
             tgl_kembali,
             lama_hari,
             keterangan,
             file_pengajuan,
             status)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,'Diajukan')
        ");

        $stmt->bind_param(
            "sssssssssss",
            $no_pengajuan,
            $nip_pemohon,
            $nip_peminjam,
            $unit_kerja,
            $no_hp,
            $kode_satker,
            $tanggal_pinjam,
            $tanggal_kembali,
            $lama,
            $keterangan,
            $upload
        );

        $stmt->execute();
        $id_pengajuan = $stmt->insert_id;

        $stmtDetail = $conn->prepare("
            INSERT INTO pengajuan_bmn_detail
            (id_pengajuan,id_bmn)
            VALUES (?,?)
        ");

        foreach ($bmnIds as $id_bmn) {
            $stmtDetail->bind_param("is",$id_pengajuan,$id_bmn);
            $stmtDetail->execute();
        }

        $conn->commit();

        echo "<script>
        alert('✅ Data berhasil disimpan');
        location.href='form_pengajuan_bmn.php';
        </script>";
        exit;

        } catch (Exception $e) {
            $conn->rollback();
            echo "<div style='padding:20px;background:#f8d7da;color:#842029'>";
            echo "<b>Error:</b> ".$e->getMessage();
            echo "</div>";
            exit;
        }
    }

    if(isset($_GET['action']) && $_GET['action']=='search_barang'){

    header('Content-Type: application/json');

    $q = $_GET['q'] ?? '';
    $keyword = "%".$q."%";
    $tgl1 = $_GET['tgl1'] ?? '';
    $tgl2 = $_GET['tgl2'] ?? '';

    if(empty($tgl1) || empty($tgl2)){
        echo json_encode([]);
        exit;
    }

    $stmt = $conn->prepare("
        SELECT d.id_bmn,d.kode_barang,d.NUP,d.merek_bmn,d.spek_bmn,k.ur_sskel
        FROM daftar_bmn d 
        LEFT JOIN tb_kode_barang k ON d.kode_barang = k.kode_barang
        WHERE d.kd_satker = ?
        AND (
            k.ur_sskel LIKE ?
            OR d.spek_bmn LIKE ?
            OR d.merek_bmn LIKE ?
            OR d.NUP LIKE ?
            OR d.kode_barang LIKE ?
        )
        AND d.id_bmn NOT IN (

            SELECT detail.id_bmn
            FROM pengajuan_bmn_detail detail
            JOIN pengajuan_bmn h 
                ON detail.id_pengajuan = h.id_pengajuan
            WHERE h.status IN ('Diajukan','Disetujui')
            AND (
                (? <= h.tgl_kembali)
                AND
                (? >= h.tgl_pinjam)
            )

        )
        ORDER BY k.ur_sskel ASC
        LIMIT 100
    ");

    $stmt->bind_param(
        "ssssssss",
        $kode_satker,
        $keyword,
        $keyword,
        $keyword,
        $keyword,
        $keyword,
        $tgl1,
        $tgl2
    );

    $stmt->execute();
    $result = $stmt->get_result();

    echo json_encode($result->fetch_all(MYSQLI_ASSOC));
    exit;
}

    if(isset($_GET['action']) && $_GET['action']=='search_pegawai'){

        header('Content-Type: application/json');

        $keyword = "%".$_GET['q']."%";

        $stmt = $conn->prepare("
            SELECT nip_pegawai, nama_pegawai, UNIT_II
            FROM tb_pegawai
            WHERE kd_satker = ?
            AND (nama_pegawai LIKE ? OR nip_pegawai LIKE ?)
            LIMIT 20
        ");

        $stmt->bind_param("sss",$kode_satker,$keyword,$keyword);
        $stmt->execute();
        $res = $stmt->get_result();

        echo json_encode($res->fetch_all(MYSQLI_ASSOC));
        exit;
    }
?>

<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title>Form Pengajuan BMN</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <style>
            body{background:#fff;font-family:'Inter',sans-serif}
            .card-header{background:linear-gradient(90deg,#22466c,#2a9086);color:#fff}
            .btn-save {background: #1E74AB;color: #fff;border: none;}
            .btn-save:hover {background:#155d87; color:#fff;}
            .hint-text {font-size: 0.75rem;}
            .page-header{margin-top:20px;}
            .icon-header{border-radius:10px;display:flex;align-items:center;justify-content:center;color:#22466c;font-size:28px;}
            .judul-page{color: #22466c;}
            .subjudul-page{color: #22466c}
            .no-resize {resize: none;}
            .file-info {font-size: 0.75rem;}
            .btn-file {background-color: #F5F5F5; color:#2A6099; padding: 0.375rem 0.75rem;border-radius: 0.375rem;cursor: pointer;transition: background-color 0.3s ease;}
            .btn-file:hover {background-color: #d6d8db;}
            .file-group{border-radius: 0.375rem;overflow: hidden;}
            .file-group .btn-file{border-radius: 0.375rem 0 0 0.375rem;cursor: pointer;}
            .file-group .file-placeholder{border-radius: 0 0.375rem 0.375rem 0;}
            .input-group-text {margin-right:calc(var(--border-width)*-1);margin-left: 0 !important;border-top-left-radius: var(--bs-border-radius) !important;border-bottom-left-radius: var(--bs-border-radius) !important;}
            .multi-select-box { position:relative;display: flex;flex-wrap: wrap;align-items: center;border: 1px solid #ced4da;border-radius: 8px;padding: 5px;min-height: 45px;background: #fff;}
            .selected-tags {display: flex;flex-wrap: wrap;gap: 5px;}
            .selected-tags .tag {background: #e9ecef;padding: 4px 8px;border-radius: 6px;display: flex;align-items: center;font-size: 13px;}
            .selected-tags .tag button {background: none;border: none;margin-left: 6px;cursor: pointer;font-size: 12px;}
            .search-input {border: none;outline: none;flex: 1;min-width: 120px;padding: 5px;}
#listBarang{
    position:absolute;
    z-index:999;
    background:#fff;
    width:100%;
    max-height:220px;
    overflow-y:auto;
    border:1px solid #ced4da;
    border-radius:8px;
    box-shadow:0 4px 12px rgba(0,0,0,0.15);
    left:0;
    top:100%;     /* default muncul di bawah */
    margin-top:4px;
}

.search-input:disabled{
    cursor:not-allowed;
}
        </style>
    </head>
    <body>
        <div class="container mt-4">
            <div class="page-header mb-4">
                <div class="d-flex align-items-center">
                    <div class="icon-header me-3">
                        <i class="fa-solid fa-cart-flatbed"></i>
                    </div>
                    <div class="mb-2 mt-2">
                        <div class="judul-page fs-5 fw-bold mb-0">
                            Input Pengajuan Peminjaman Barang Milik Negara (BMN)
                        </div>
                        <div class="subjudul-page fs-6">
                            Pengelolaan data peminjaman BMN dan ketersediaan BMN.
                        </div>
                    </div>
                </div>
            </div>
            <div class="card shadow border-0 mb-4 rounded-bottom-4 ">
                <div class="card-header py-3 rounded-top-4">
                    <h5 class="mb-0"><i class="fa-solid fa-business-time me-2"></i>Formulir Pengajuan Peminjaman BMN</h5>
                </div>
                <div class="card-body bg-light">
                    <form method="post" enctype="multipart/form-data">
                        <div class="row g-2 mt-2">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-user text-primary me-2"></i>Nama Peminjam
                                </label>
                                <div class="position-relative">
                                    <input type="text" id="searchPegawai" class="form-control" placeholder="Nama pegawai yang meminjam">
                                    <input type="hidden" name="nip_peminjam" id="nipPeminjam">
                                    <div id="dropdownPegawai" class="list-group position-absolute w-100 shadow" style="z-index:1000; display:none;"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-building text-icon me-2"></i>Unit Kerja
                                </label>
                                <input type="text" name="unit_kerja" class="form-control" placeholder="Unit Eselon I/II/III/Lainnya" required readonly>
                            </div>
                        </div>
                        <div class="mt-4">
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">
                                        <i class="fas fa-calendar text-success me-2"></i>Tanggal Pinjam
                                    </label>
                                    <input type="date" name="tanggal_pinjam" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">
                                        <i class="fas fa-clock text-warning me-2"></i>Lama Peminjaman
                                    </label>
                                    <input type="text" id="lama" class="form-control" placeholder="Auto calculate" readonly>
                                </div>
                            </div>
                            <div class="mt-4">
                                <div class="row g-2">
                                    <div class="col-md-6">
                                    <label class="form-label fw-semibold">
                                        <i class="fas fa-calendar text-danger me-2"></i>Tanggal Kembali
                                    </label>
                                    <input type="date" name="tanggal_kembali" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">
                                        <i class="fab fa-whatsapp text-success me-2"></i>Narahubung (WA)
                                    </label>
                                    <input type="text" class="form-control" name="no_hp" id="no_hp" placeholder="62xxxxxxxxxxx" required>
                                    <div class="invalid-feedback">
                                        Nomor WhatsApp harus diawali dengan <strong>62</strong>.
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-cubes text-icon me-2"></i>Barang Yang Dipinjam
                                </label>
                                <div class="multi-select-box position-relative" id="multiBox">
                                    <div id="selectedBarang" class="selected-tags"></div>
                                        <input type="text" id="searchBarang" class="search-input" placeholder="Cari BMN (Nama/Kode/NUP/Merk)" disabled>
                                        <div id="listBarang" class="p-3 mt-1" style="display: none;"></div>
                                    </div>
                                </div>
                                <small class="text-danger hint-text peserta_hybrid"> *Hanya memunculkan BMN yang tersedia saat ini.</small>
                                </div>
                            <div class="mt-4">
                                <label for="keterangan" class="form-label fw-semibold">
                                    <i class="fa-solid fa-pen-to-square text-info me-2"></i>Keterangan
                                </label>
                                <textarea name="keterangan" class="form-control no-resize" rows="4"placeholder="Isi kegunaan BMN, lokasi, dan kegiatan yang dilaksanakan" required></textarea>
                            </div>
                            <div class="mt-4">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-file-upload text-primary me-2"></i>Upload Dokumen Pendukung
                                </label>
                                <div class="input-group">
                                    <div class="input-group file-group">
                                        <input type="file" class="form-control" id="fileUpload" name="file_upload" required hidden>
                                        <label class="input-group-text btn-file" for="fileUpload">
                                            Pilih File
                                        </label>
                                        <label for="fileUpload" class="form-control text-muted file-placeholder" id="fileName" style="cursor:pointer">
                                            Tidak ada file yang dipilih
                                        </label>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <small class="text-muted file-info">Maksimal ukuran 5MB</small>
                                </div>
                                <div class="text-center mt-4 mb-4">
                                    <button type="submit" class="btn btn-save btn-lg">
                                        Simpan Pengajuan
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
        <script>
            $(function(){
                let selectedBMN = [];
                const dropdownBtn = $('#dropdownBMN');
                const form = $('form');

                $('.bmn-check').on('change', function(){
                    if(this.checked){
                        selectedBMN.push({ id: this.value, nama: $(this).data('nama')
                    });
                    }else{
                        selectedBMN = selectedBMN.filter(b => b.id !== this.value);
                    }
                    updateDropdown();
                });
                function updateDropdown(){
                    if(selectedBMN.length === 0){
                        dropdownBtn.text('Pilih Barang');
                    }else{
                        dropdownBtn.text(selectedBMN.map(b=>b.nama).join(', '));
                    }
                    form.find('input[name="id_bmn[]"]').remove();
                    selectedBMN.forEach(b=>{
                        $('<input>').attr({
                            type:'hidden',
                            name:'id_bmn[]',
                            value:b.id
                        }).appendTo(form);
                    });
                }
            });
            (function(){

const t1 = document.querySelector('[name="tanggal_pinjam"]');
const t2 = document.querySelector('[name="tanggal_kembali"]');
const lama = document.getElementById('lama');
const searchBarang = document.getElementById('searchBarang');

function hitung(){

    if(!t1.value || !t2.value){
        lama.value='';
        searchBarang.disabled = true;
        return;
    }

    const d1 = new Date(t1.value);
    const d2 = new Date(t2.value);
    const diff = (d2 - d1)/(1000*60*60*24);

    if(diff >= 0){
        lama.value = diff + " Hari";
        searchBarang.disabled = false;
    }else{
        lama.value='';
        searchBarang.disabled = true;
    }

}

t1.addEventListener('change',hitung);
t2.addEventListener('change',hitung);

})();
            const fileUpload = $(this).attr('data-file');
            const fileInput  = document.getElementById('fileUpload');
            const fileNameInput = document.getElementById('fileName');

                fileInput.value = '';
                fileNameInput.value = '';

                existingFileName = '';

                if (fileUpload && fileUpload.trim() !== '') {
                    existingFileName = fileUpload.split('/').pop();
                    $('#fileName').text(existingFileName || 'Belum ada file');

                    const dummyFile = new File([''], existingFileName, { type: 'application/octet-stream' });
                    const dataTransfer = new DataTransfer();
                    dataTransfer.items.add(dummyFile);
                    fileInput.files = dataTransfer.files;
                }
                $('#btnChangeFile').on('click', function(e){
                    e.preventDefault();
                    $('#fileInputReplacement').click();
                });
                $('#fileUpload').on('change', function () {
                    if (this.files && this.files.length > 0) {
                        const newFileName = this.files[0].name;
                        $('#fileName').text(newFileName);
                        existingFileName = newFileName;
                    } else {
                        $('#fileName').text(existingFileName || 'Belum ada file');
                    }
                });
                $('#fileName').on('click', function () {
                    if (!$('#fileUpload').prop('disabled')) {
                        $('#fileUpload').trigger('click');
                    }
                });
                const input = document.getElementById('searchBarang');
                const list  = document.getElementById('listBarang');

                let selectedItems = {}; 
                input.addEventListener('keyup', function(){

let q = this.value;

loadBarang(q);

});
                function loadBarang(keyword=''){

let tgl1 = document.querySelector('[name="tanggal_pinjam"]').value;
let tgl2 = document.querySelector('[name="tanggal_kembali"]').value;


fetch(`?action=search_barang&q=${keyword}&tgl1=${tgl1}&tgl2=${tgl2}`)
.then(res => res.json())
.then(data => {

    let html = '';

    if(data.length === 0){
        html = '<div class="text-muted p-2">Tidak ada barang tersedia</div>';
    }

    data.forEach(item => {

        let label = `${item.kode_barang} - NUP ${item.NUP}`;
        let checked = selectedItems[item.id_bmn] ? 'checked' : '';

        html += `
        <div class="form-check mb-2">
            <input class="form-check-input barang-check"
                type="checkbox"
                value="${item.id_bmn}"
                data-label="${label}"
                id="bmn_${item.id_bmn}"
                ${checked}>

            <label class="form-check-label" for="bmn_${item.id_bmn}">
                <strong>${item.kode_barang} - NUP ${item.NUP}</strong><br>
                <small class="text-muted">
                    ${item.ur_sskel ?? ''} - ${item.merek_bmn ?? ''} - ${item.spek_bmn ?? ''}
                </small>
            </label>
        </div>
        `;
    });

    list.innerHTML = html;
    list.style.display='block';

});
}

input.addEventListener('focus', function(){

let tgl1 = document.querySelector('[name="tanggal_pinjam"]').value;
let tgl2 = document.querySelector('[name="tanggal_kembali"]').value;


loadBarang();

});
document.addEventListener('click', function(e){

const multiBox = document.getElementById('multiBox');
const list = document.getElementById('listBarang');

if(!multiBox.contains(e.target) && !list.contains(e.target)){
    list.style.display = 'none';
}

});
            document.addEventListener('change', function(e){
                if(e.target.classList.contains('barang-check')){
                    let id    = e.target.value;
                    let label = e.target.dataset.label;
                    if(e.target.checked){
                        selectedItems[id] = label;
                    }else{
                        delete selectedItems[id];
                    }
                    updateDisplay();
                }
            });
            function updateDisplay(){
                const selectedContainer = document.getElementById('selectedBarang');
                selectedContainer.innerHTML='';

                document.querySelectorAll('.hidden-barang')
                    .forEach(el=>el.remove());
                Object.keys(selectedItems).forEach(id=>{
                    let tag=document.createElement('div');
                    tag.className='tag';
                    tag.innerHTML=`
                        ${selectedItems[id]}
                        <button type="button" data-id="${id}">&times;</button>
                    `;
                    selectedContainer.appendChild(tag);
                    let hidden=document.createElement('input');
                    hidden.type='hidden';
                    hidden.name='id_bmn[]';
                    hidden.value=id;
                    hidden.classList.add('hidden-barang');
                    document.querySelector('form').appendChild(hidden);
                });
            }
            document.addEventListener('click', function(e){
                if(e.target.closest('.tag button')){
                    let id = e.target.dataset.id;
                    delete selectedItems[id];
                    let checkbox = document.getElementById('bmn_'+id);
                    if(checkbox) checkbox.checked = false;

                    updateDisplay();
                }
            });
            document.getElementById('multiBox').addEventListener('click', function(){
                document.getElementById('searchBarang').focus();
            });
            const pegawaiInput = document.getElementById('searchPegawai');
            const dropdownPegawai = document.getElementById('dropdownPegawai');
            const nipInput = document.getElementById('nipPeminjam');
            const unitInput = document.querySelector('[name="unit_kerja"]');

            pegawaiInput.addEventListener('keyup', function(){
                let q = this.value;
                if(q.length < 2){
                    dropdownPegawai.style.display='none';
                    return;
                }
                fetch(`?action=search_pegawai&q=${q}`)
                .then(res=>res.json())
                .then(data=>{
                    if(data.length===0){
                        dropdownPegawai.style.display='none';
                        return;
                    }
                    let html='';
                    data.forEach(p=>{
                        html+=`
                        <a href="#"
                        class="list-group-item list-group-item-action"
                        data-nip="${p.nip_pegawai}"
                        data-unit="${p.UNIT_II}">
                        <strong>${p.nama_pegawai}</strong><br>
                        <small class="text-muted">${p.nip_pegawai}</small>
                            <span class="badge bg-success badge-status ms-2">Aktif</span>
                        </a>`;
                    });
                    dropdownPegawai.innerHTML=html;
                    dropdownPegawai.style.display='block';
                });
            });
            dropdownPegawai.addEventListener('click', function(e){
                e.preventDefault();

                let item=e.target.closest('.list-group-item');
                if(!item) return;

                pegawaiInput.value=item.querySelector('strong').innerText;
                nipInput.value=item.dataset.nip;
                unitInput.value=item.dataset.unit;

                dropdownPegawai.style.display='none';
            });
            (function () {
            const input = document.getElementById('no_hp');

            input.addEventListener('input', function () {
                const value = input.value.trim();

                input.value = value.replace(/[^0-9]/g, '');

                if (!input.value.startsWith('62')) {
                input.classList.add('is-invalid');
                } else {
                input.classList.remove('is-invalid');
                }
            });

            document.querySelector('form').addEventListener('submit', function (e) {
                if (!input.value.startsWith('62')) {
                input.classList.add('is-invalid');
                input.focus();
                e.preventDefault();
                }
            });
            })();
        </script>
    </body>
</html>