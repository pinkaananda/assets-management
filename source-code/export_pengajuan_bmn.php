<?php
    session_start();
    include "koneksi.php";

    $kode_satker = '694762';
    $search = $_GET['searchTerm'] ?? '';

    $where = "WHERE p.kd_satker='$kode_satker'";

    if($search){
        $searchLike = "%".$conn->real_escape_string($search)."%";
        $where .= " AND (
            pg.nama_pegawai LIKE '$searchLike'
            OR b.kode_barang LIKE '$searchLike'
            OR k.ur_sskel LIKE '$searchLike'
        )";
    }

    $query = $conn->query("
    SELECT 
        p.id_pengajuan,
        p.tgl_pengajuan,
        p.tgl_pinjam,
        p.tgl_kembali,
        p.keterangan,
        pg.nama_pegawai,
        pg.UNIT_II,

        GROUP_CONCAT(
            CONCAT(b.kode_barang,' - ',k.ur_sskel)
            SEPARATOR '\n'
        ) AS barang_dipinjam

    FROM pengajuan_bmn p

    LEFT JOIN tb_pegawai pg 
        ON p.nip_peminjam = pg.nip_pegawai

    LEFT JOIN pengajuan_bmn_detail d 
        ON p.id_pengajuan = d.id_pengajuan

    LEFT JOIN daftar_bmn b 
        ON d.id_bmn = b.id_bmn

    LEFT JOIN tb_kode_barang k 
        ON b.kode_barang = k.kode_barang

    $where

    GROUP BY p.id_pengajuan

    ORDER BY p.id_pengajuan DESC
    ");

    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=Daftar_Pengajuan_BMN.xls");

    echo "<table border='1'>";
    echo "<tr>
            <th>No</th>
            <th>Nama Peminjam</th>
            <th>Unit Kerja</th>
            <th>Barang Yang Dipinjam</th>
            <th>Tanggal Pengajuan</th>
            <th>Tanggal Pinjam</th>
            <th>Tanggal Kembali</th>
            <th>Keterangan</th>
        </tr>";

    $no = 1;

    while($row = $query->fetch_assoc()){
        $barang = nl2br($row['barang_dipinjam']);
        echo "<tr>
                <td>".$no++."</td>
                <td>".$row['nama_pegawai']."</td>
                <td>".$row['UNIT_II']."</td>
                <td>".$barang."</td>
                <td>".$row['tgl_pengajuan']."</td>
                <td>".$row['tgl_pinjam']."</td>
                <td>".$row['tgl_kembali']."</td>
                <td>".$row['keterangan']."</td>
            </tr>";
    }
    echo "</table>";
    exit;
?>