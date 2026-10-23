<?php

  /* Sistem Pencatatan Kas & Keuangan Organisasi Mahasiswa
   * File Log: kas_organisasi.txt
   * Anggota Kelompok: - Sekar Ayu Atmaja
   *                   - Muhammad Bumi Raja Elang
   *                   - M. Ramadhani Saputra
   */


session_start();

// ======================================== 
// RESET DATA                               
// ======================================= 
        if (isset($_GET['reset'])) {

            //Hapus semua date_add
            $_SESSION['transaksi'] = [];

            //kosongkan file log
            file_put_contents('kas_organisasi.txt', '');

            //kembali ke halaman utama
            header("location: index.php");
            exit;
        }
    
// menyimpan data di memori
if (!isset($_SESSION['transaksi'])) {
    $_SESSION['transaksi'] = [];
}

// Variabel Pesan Error / Sukses
$pesanError = '';
$pesanSukses = '';

// ============================================================================
// INPUT DATA
// ============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi']) && $_POST['aksi'] === 'tambah_transaksi') {
    
    // Input Data
    $tanggal    = $_POST['tanggal'] ?? '';
    $jenis      = $_POST['jenis'] ?? '';
    $keterangan = trim($_POST['keterangan'] ?? '');
    $nominal    = floatval($_POST['nominal'] ?? 0);

    // ============================================================================
    //  PROSES DAN VALIDASI
    // ============================================================================
    try {
        // --- Form Input & Logika Saldo ---
        if (empty($tanggal) || empty($jenis) || empty($keterangan)) {
            throw new Exception("Semua field form wajib diisi!");
        }

        if ($nominal <= 0) {
            throw new Exception("Nominal uang harus lebih besar dari 0!");
        }

        // Hitung Saldo Berjalan Saat Ini
        $saldoSaatIni = 0;
        foreach ($_SESSION['transaksi'] as $t) {
            if ($t['jenis'] === 'Pemasukan') {
                $saldoSaatIni += $t['nominal'];
            } else if ($t['jenis'] === 'Pengeluaran') {
                $saldoSaatIni -= $t['nominal'];
            }
        }

        // --- Exception Handling ---
        if ($jenis === 'Pengeluaran' && ($saldoSaatIni - $nominal) < 0) {
            throw new Exception("TRANSKSI GAGAL: Saldo tidak mencukupi! Saldo saat ini hanya Rp " . number_format($saldoSaatIni, 0, ',', '.'));
        }

        // Hitung Saldo Baru Setelah Transaksi
        $saldoBaru = ($jenis === 'Pemasukan') ? ($saldoSaatIni + $nominal) : ($saldoSaatIni - $nominal);

        // ============================================================================
        // SIMPAN DATA
        // ============================================================================
        $dataBaru = [
            'tanggal'    => $tanggal,
            'jenis'      => $jenis,
            'keterangan' => $keterangan,
            'nominal'    => $nominal,
            'saldo'      => $saldoBaru
        ];
        $_SESSION['transaksi'][] = $dataBaru;

        // --- Simpan Data ke File kas_organisasi.txt (Otomatis dibuat jika belum ada) ---
        $logEntry = sprintf(
            "%s | %-12s | %-30s | Rp %-12s | Saldo: Rp %s\n",
            $tanggal,
            $jenis,
            $keterangan,
            number_format($nominal, 0, ',', '.'),
            number_format($saldoBaru, 0, ',', '.')
        );
        file_put_contents('kas_organisasi.txt', $logEntry, FILE_APPEND);

        $pesanSukses = "Transaksi berhasil disimpan!";
 
    } catch (Exception $e) {
        // Menangkap error exception
        $pesanError = $e->getMessage();
    }
}

// ============================================================================
// LOGIKA PERHITUNGAN REKAPITULASI (PEMASUKAN, PENGELUARAN, SALDO)
// ============================================================================
$totalPemasukan  = 0;
$totalPengeluaran = 0;

foreach ($_SESSION['transaksi'] as $t) {
    if ($t['jenis'] === 'Pemasukan') {
        $totalPemasukan += $t['nominal'];
    } elseif ($t['jenis'] === 'Pengeluaran') {
        $totalPengeluaran += $t['nominal'];
    }
}

$saldoAkhir = $totalPemasukan - $totalPengeluaran;

// ============================================================================
// PENCARIAN BERDASARKAN TANGGAL
// ============================================================================
$keywordTanggal = $_GET['cari_tanggal'] ?? '';
$daftarTransaksi = $_SESSION['transaksi'];

if (!empty($keywordTanggal)) {
    $daftarTransaksi = array_filter($daftarTransaksi, function ($t) use ($keywordTanggal) {
        return $t['tanggal'] === $keywordTanggal;
    });
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Kas Organisasi Mahasiswa</title>
    <!-- Framework Bootstrap untuk Tampilan -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body{
            background-color:#f8f9fa;
            position:relative;
        }

        body::before{
            content:"";
            position:fixed;
            inset:0;
            background:url("BGppm.jpg") center center no-repeat;
            background-size: cover;
            opacity: 0.50;
            z-index:-1
        }
        body::after{
            content:"";
            position:fixed;
            top:20px;
            left:520px;
            width:140px;
            height: 60px;
            background:url("logoppm.png") no-repeat center;
            background-size:contain;
            opacity:1;
        }

        .text-succes{
            color:#008000 !important;
        }

        .text-danger{
            color:#ad102f !important;
        }

        .btn-danger{
            background:#6B7280;
            border:none;
        }
        .btn-danger:hover{
            background:#6B7280;
        }


    </style>
</head>
<body class="bg-light p-4">
<div class="header">
    <h2 class="mb-4 text-center">Sistem Pencatatan Kas Organisasi Mahasiswa</h2>

    <!-- NOTIFIKASI ERROR / SUKSES -->
    <?php if ($pesanError): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Gagal:
            </strong>
            <?= htmlspecialchars($pesanError) ?>
        </div>
    <?php endif; ?>

    <?php if ($pesanSukses): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <strong>Sukses:
            </strong>
            <?= htmlspecialchars($pesanSukses) ?>
        </div>
    <?php endif; ?>

    <div class="row">
<!-- ============================================================================ -->
<!-- INPUT DATA                                        -->
<!-- ============================================================================ -->
        <div class="col-md-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white font-weight-bold">
                    Form Input Data
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="aksi" value="tambah_transaksi">
                        
                        <div class="mb-3">
                            <label class="form-label">Tanggal Transaksi</label>
                            <input type="date" name="tanggal" class="form-control" required value="<?= date('Y-m-d') ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Jenis Transaksi</label>
                            <select name="jenis" class="form-select" required>
                                <option value="Pemasukan">Pemasukan</option>
                                <option value="Pengeluaran">Pengeluaran</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Keterangan</label>
                            <input type="text" name="keterangan" class="form-control" placeholder="Contoh: Seminar" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Nominal Uang (Rp)</label>
                            <input type="number" name="nominal" class="form-control" min="1" placeholder="0" required>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Simpan Transaksi</button>
                    </form>
                </div>
            </div>

 <!-- ============================================================================ -->
 <!-- TAMPILAN SALDO AKHIR & REKAPITULASI                              -->
 <!-- ============================================================================ -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-dark text-white font-weight-bold">
                    Saldo Akhir
                </div>
                <div class="card-body">
                    <!-- Total Pemasukan -->
                    <div class="d-flex justify-content-between mb-2">
                        <span>Total Pemasukan:</span>
                        <strong class="text-success">Rp <?= number_format($totalPemasukan, 0, ',', '.') ?></strong>
                    </div>

                    <!-- Total Pengeluaran -->
                    <div class="d-flex justify-content-between mb-2">
                        <span>Total Pengeluaran:</span>
                        <strong class="text-danger">Rp <?= number_format($totalPengeluaran, 0, ',', '.') ?></strong>
                    </div>
                    <hr>

                    <!-- Saldo Terakhir -->
                    <div class="p-3 bg-light rounded text-center border">
                        <small class="text-dark d-block mb-1">SALDO AKHIR SAAT INI</small>
                        <h3 class=" text-dark m-0 <?= $saldoAkhir >= 0 ? 'text-primary' : 'text-danger' ?> m-0">
                            Rp <?= number_format($saldoAkhir, 0, ',', '.') ?>
                        </h3>
                    </div>
                </div>
</div>
    </div>

        <!-- ============================================================================ -->
        <!-- LAPORAN JURNAL KEUANGAN & FITUR PENCARIAN                                   -->
        <!-- ============================================================================ -->
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
                    <span> Jurnal Laporan Keuangan</span>
                </div>
                <div class="card-body">
                    
                    <!-- Form Pencarian Berdasarkan Tanggal -->
                    <form method="GET" action="" class="row g-2 mb-3">
                        <div class="col-auto">
                            <label class="col-form-label fw-bold">Cari Tanggal:
                            </label>
                        </div>
                        <div class="col-auto">
                            <input type="date" name="cari_tanggal" class="form-control" value="<?= htmlspecialchars($keywordTanggal) ?>">
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-secondary"> Cari
                            </button>

                            <?php if (!empty($_SESSION['transaksi'])): ?>
                                <a   
                                href="?reset=1"
                                class="btn btn-danger"
                                onclick="return confirm('Apakah anda yakin ingin menghapus semua data transaksi?')">
                                 Reset
                            </a>
                            <?php endif; ?>

                            <?php if (!empty($keywordTanggal)): ?>
                                <a
                                href="index.php"
                                class="btn btn-secondary">
                                Batal Cari
                                </a>
                             <?php endif; ?>
                        </div>
                    </form>

                    <!-- Tampilan Tabel Jurnal Laporan -->
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th>No</th>
                                    <th>Tanggal</th>
                                    <th>Jenis</th>
                                    <th>Keterangan</th>
                                    <th>Nominal</th>
                                    <th>Saldo Akhir</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($daftarTransaksi)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">
                                            <?= !empty($keywordTanggal) ? 'Data transaksi pada tanggal tersebut tidak ditemukan.' : 'Belum ada data transaksi.' ?>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php $no = 1; foreach ($daftarTransaksi as $t): ?>
                                        <tr>
                                            <td><?= $no++ ?></td>
                                            <td><?= htmlspecialchars($t['tanggal']) ?></td>
                                            <td>
                                                <span class="badge bg-<?= $t['jenis'] === 'Pemasukan' ? 'success' : 'danger' ?>">
                                                    <?= htmlspecialchars($t['jenis']) ?>
                                                </span>
                                            </td>
                                            <td><?= htmlspecialchars($t['keterangan']) ?></td>
                                            <td>Rp <?= number_format($t['nominal'], 0, ',', '.') ?></td>
                                            <td><strong>Rp <?= number_format($t['saldo'], 0, ',', '.') ?></strong></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
</div>
</div>
</body>
</html>