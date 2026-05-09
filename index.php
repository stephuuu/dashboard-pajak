<?php
/**
 * =====================================================
 * INDEX.PHP - DASHBOARD OVERVIEW KPP
 * =====================================================
 * Lokasi: C:\xampp\htdocs\dashboard-pajak\index.php
 * URL: http://localhost/dashboard-pajak/
 *
 * Halaman utama dashboard menampilkan ringkasan
 * penerimaan pajak dengan data riil dari database.
 * =====================================================
 */

require_once __DIR__ . '/includes/functions.php';

// Set page metadata sebelum require header
$pageTitle = 'Overview ' . getSetting('kpp_name', 'KPP Pratama');
$pageSubtitle = 'Ringkasan penerimaan pajak';
$activePage = 'overview';

// Ambil filter dari URL
$filter = [];
if (!empty($_GET['tahun'])) $filter['thn_setor'] = (int)$_GET['tahun'];
if (!empty($_GET['bulan'])) $filter['bln_setor'] = (int)$_GET['bulan'];
if (!empty($_GET['seksi'])) $filter['unit_organisasi'] = $_GET['seksi'];

// Update subtitle dengan info filter
$bulanLabel = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
$filterInfo = [];
if (!empty($filter['thn_setor'])) $filterInfo[] = 'Tahun ' . $filter['thn_setor'];
if (!empty($filter['bln_setor'])) $filterInfo[] = 'Bulan ' . $bulanLabel[$filter['bln_setor']];
if (!empty($filter['unit_organisasi'])) $filterInfo[] = $filter['unit_organisasi'];
if (!empty($filterInfo)) {
    $pageSubtitle = 'Filter: ' . implode(' · ', $filterInfo);
}

// === QUERY DATA ===
$realisasi = getRealisasiPenerimaan($filter);
$realisasiBulan = getRealisasiBulanIni($filter);
$penerimaanHari = getPenerimaanPerHari($filter, 30);
$topWP = getTopWajibPajak($filter, 10);
$topAR = getTopAR($filter, 10);
$topMAP = getTopKodeMap($filter, 10);
$perBulan = getPenerimaanPerBulan($filter);
$perSeksi = getPenerimaanPerSeksi($filter);
$perJenis = getPenerimaanPerJenis($filter);
$perSektor = getPenerimaanPerSektor($filter, 8);
$trendHarian = getTrendHarian($filter, 30);

// Target & growth
$tahunTarget = $filter['thn_setor'] ?? date('Y');
$target = getTargetPenerimaan($tahunTarget);
$realisasiPct = $target > 0 ? min(100, ($realisasi['total'] / $target * 100)) : 0;

$filterTahunLalu = $filter;
$filterTahunLalu['thn_setor'] = $tahunTarget - 1;
$realisasiTahunLalu = getRealisasiPenerimaan($filterTahunLalu);
$growth = $realisasiTahunLalu['total'] > 0
    ? (($realisasi['total'] - $realisasiTahunLalu['total']) / $realisasiTahunLalu['total'] * 100)
    : 0;

require __DIR__ . '/includes/header.php';
?>

<style>
  .dash-grid { display: grid; gap: 12px; margin-bottom: 12px; }
  .grid-row1 { grid-template-columns: 1.4fr 2fr 1.7fr 1.7fr 1.6fr; }
  .grid-row2 { grid-template-columns: repeat(4, 1fr); }
  .grid-row3 { grid-template-columns: 1.3fr 1.3fr 1.7fr 1.7fr; }
  .grid-row4 { grid-template-columns: 1fr 2fr; }

  .card-tbl {
    background: white;
    border: 1px solid var(--tblr-border-color);
    border-radius: 8px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.03);
    cursor: pointer;
    transition: all 0.15s;
    display: flex;
    flex-direction: column;
    overflow: hidden;
  }
  .card-tbl:hover {
    box-shadow: 0 4px 12px rgba(32, 107, 196, 0.1);
    border-color: #c5d3e6;
    transform: translateY(-1px);
  }
  .card-tbl-header {
    padding: 10px 14px 6px;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }
  .card-tbl-title {
    font-size: 11px; font-weight: 600;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    margin: 0;
  }
  .card-tbl-action {
    color: #adb5bd;
    text-decoration: none;
  }
  .card-tbl-action i { font-size: 14px; }
  .card-tbl-body {
    padding: 6px 14px 12px;
    flex: 1;
    display: flex;
    flex-direction: column;
    overflow: hidden;
  }

  .row1 .card-tbl { height: 250px; }
  .row2 .card-tbl { height: 230px; }
  .row3 .card-tbl { height: 240px; }
  .row4 .card-tbl { height: 120px; }

  .big-number {
    font-size: 19px;
    font-weight: 700;
    color: var(--tblr-primary);
    line-height: 1.1;
    letter-spacing: -0.3px;
  }
  .small-number { font-size: 12px; font-weight: 600; color: #1e293b; }
  .label-text { font-size: 10px; color: #6c757d; }

  .table-mini { width: 100%; font-size: 11px; border-collapse: collapse; }
  .table-mini th {
    color: #6c757d;
    font-weight: 600;
    border-bottom: 1px solid var(--tblr-border-color);
    padding: 6px 4px 4px;
    background: white;
    position: sticky; top: 0;
    text-transform: uppercase;
    font-size: 10px;
    letter-spacing: 0.3px;
  }
  .table-mini td { padding: 5px 4px; border-bottom: 1px solid #f3f4f6; }
  .table-mini tbody tr:hover { background: #f8f9fa; }
  .text-end { text-align: right; }
  .scroll-y { flex: 1; overflow-y: auto; min-height: 0; }

  .kpi-row {
    display: flex;
    justify-content: space-around;
    padding-top: 8px;
    border-top: 1px solid var(--tblr-border-color);
    margin-top: 8px;
  }
  .stat-block { text-align: center; }
  .stat-block .big { font-size: 13px; font-weight: 600; color: #1e293b; }
  .stat-block .label { font-size: 9px; color: #6c757d; text-transform: uppercase; }
  .delta-positive { color: #10b981; }
  .delta-negative { color: #ef4444; }

  .detail-stat {
    padding: 8px 10px;
    background: #f8f9fa;
    border-radius: 6px;
    margin-top: 6px;
  }
  .detail-stat .row-stat {
    display: flex;
    justify-content: space-between;
    padding: 3px 0;
    font-size: 11px;
  }

  .chart-wrap { position: relative; flex: 1; min-height: 0; }
  .echart-container { width: 100%; height: 100%; }

  .empty-state {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
    color: #adb5bd;
    font-size: 12px;
    text-align: center;
    padding: 20px;
  }

  .modal-tbl {
    display: none;
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(15, 23, 42, 0.5);
    z-index: 1000;
    align-items: center;
    justify-content: center;
  }
  .modal-tbl.show { display: flex; }
  .modal-tbl-content {
    background: white;
    border-radius: 12px;
    width: 600px;
    max-width: 90vw;
    box-shadow: 0 20px 50px rgba(0,0,0,0.2);
  }
  .modal-tbl-header {
    padding: 16px 20px;
    border-bottom: 1px solid var(--tblr-border-color);
    display: flex;
    justify-content: space-between;
    align-items: center;
  }
  .modal-tbl-header h3 { font-size: 15px; margin: 0; font-weight: 600; }
  .modal-tbl-close {
    background: none;
    border: none;
    font-size: 22px;
    cursor: pointer;
    color: #6c757d;
  }
  .modal-tbl-body { padding: 20px; font-size: 13px; }

  .no-data-banner {
    background: #fff7ed;
    border-left: 3px solid #f59e0b;
    padding: 12px 16px;
    border-radius: 6px;
    font-size: 13px;
    margin-bottom: 12px;
  }
  .no-data-banner a { color: #206bc4; }
</style>

<?php if ($realisasi['count_trx'] === 0): ?>
  <div class="no-data-banner">
    <i class="ti ti-alert-circle"></i> <strong>Tidak ada data untuk filter saat ini.</strong>
    Coba ubah atau hapus filter, atau <a href="pages/upload.php">upload data</a> terlebih dahulu.
  </div>
<?php endif; ?>

<!-- ============== ROW 1 ============== -->
<div class="dash-grid grid-row1 row1">

  <!-- Penerimaan per Hari -->
  <div class="card-tbl" onclick="openDetail('penerimaan-hari')">
    <div class="card-tbl-header">
      <h3 class="card-tbl-title">Penerimaan per Hari</h3>
      <span class="card-tbl-action"><i class="ti ti-arrows-diagonal"></i></span>
    </div>
    <div class="card-tbl-body">
      <?php if (empty($penerimaanHari)): ?>
        <div class="empty-state">Tidak ada data</div>
      <?php else: ?>
        <div class="scroll-y">
          <table class="table-mini">
            <thead><tr><th>Tgl</th><th class="text-end">Setor</th><th class="text-end">Trx</th></tr></thead>
            <tbody>
              <?php foreach ($penerimaanHari as $r): ?>
                <tr>
                  <td><?= htmlspecialchars($r['tgl']) ?></td>
                  <td class="text-end"><?= formatRupiahShort($r['total'], true) ?></td>
                  <td class="text-end"><?= number_format($r['count_trx']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Gauge Realisasi -->
  <div class="card-tbl">
    <div class="card-tbl-header">
      <h3 class="card-tbl-title">Realisasi vs Target <?= $tahunTarget ?></h3>
      <a href="pages/settings.php" class="card-tbl-action" title="Edit target di settings">
        <i class="ti ti-pencil"></i>
      </a>
    </div>
    <div class="card-tbl-body">
      <div class="chart-wrap" style="max-height: 110px;">
        <div id="gaugeRealisasi" class="echart-container"></div>
      </div>
      <div style="text-align:center; margin-top:4px;">
        <div class="big-number"><?= $target > 0 ? formatRupiahShort($target, true) : 'Belum di-set' ?></div>
        <div class="label-text">Target Penerimaan</div>
      </div>
      <div class="kpi-row">
        <div class="stat-block">
          <div class="big"><?= number_format($realisasi['count_wp']) ?></div>
          <div class="label">WP Bayar</div>
        </div>
        <div class="stat-block">
          <div class="big <?= $growth >= 0 ? 'delta-positive' : 'delta-negative' ?>">
            <?= ($growth >= 0 ? '+' : '') . number_format($growth, 1) ?>%
          </div>
          <div class="label">Growth</div>
        </div>
        <div class="stat-block">
          <div class="big" style="font-size:11px;"><?= formatRupiahShort($realisasiTahunLalu['total'], true) ?></div>
          <div class="label">Tahun Lalu</div>
        </div>
      </div>
    </div>
  </div>

  <!-- KPI s.d. Hari -->
  <div class="card-tbl" onclick="openDetail('kpi-sd-hari')">
    <div class="card-tbl-header">
      <h3 class="card-tbl-title">Realisasi s.d. Hari Ini</h3>
      <span class="card-tbl-action"><i class="ti ti-arrows-diagonal"></i></span>
    </div>
    <div class="card-tbl-body">
      <div class="big-number"><?= number_format($realisasi['total'], 0, ',', ',') ?></div>
      <div class="label-text">Total Jumlah Setor Akumulasi</div>
      <div class="detail-stat" style="flex:1; margin-top: 10px;">
        <div class="row-stat">
          <span class="label-text">Total Transaksi</span>
          <span class="small-number"><?= number_format($realisasi['count_trx']) ?></span>
        </div>
        <div class="row-stat">
          <span class="label-text">WP Unik</span>
          <span class="small-number"><?= number_format($realisasi['count_wp']) ?></span>
        </div>
        <div class="row-stat">
          <span class="label-text">Avg per Trx</span>
          <span class="small-number">
            <?= $realisasi['count_trx'] > 0 ? formatRupiahShort($realisasi['total'] / $realisasi['count_trx'], true) : '-' ?>
          </span>
        </div>
      </div>
    </div>
  </div>

  <!-- KPI Bulan Ini -->
  <div class="card-tbl" onclick="openDetail('kpi-bulan')">
    <div class="card-tbl-header">
      <h3 class="card-tbl-title">Realisasi Bulan <?= $bulanLabel[(int)date('n')] ?></h3>
      <span class="card-tbl-action"><i class="ti ti-arrows-diagonal"></i></span>
    </div>
    <div class="card-tbl-body">
      <div class="big-number"><?= number_format($realisasiBulan['total'], 0, ',', ',') ?></div>
      <div class="label-text">Total Setor Bulan Ini</div>
      <div class="detail-stat" style="flex:1; margin-top: 10px;">
        <div class="row-stat">
          <span class="label-text">Total Transaksi</span>
          <span class="small-number"><?= number_format($realisasiBulan['count_trx']) ?></span>
        </div>
        <div class="row-stat">
          <span class="label-text">WP Unik</span>
          <span class="small-number"><?= number_format($realisasiBulan['count_wp']) ?></span>
        </div>
        <div class="row-stat">
          <span class="label-text">% dari Total</span>
          <span class="small-number">
            <?= $realisasi['total'] > 0 ? number_format(($realisasiBulan['total'] / $realisasi['total']) * 100, 1) : '0' ?>%
          </span>
        </div>
      </div>
    </div>
  </div>

  <!-- Top 10 WP -->
  <div class="card-tbl" onclick="openDetail('top-wp')">
    <div class="card-tbl-header">
      <h3 class="card-tbl-title">Top <?= count($topWP) ?> Wajib Pajak</h3>
      <span class="card-tbl-action"><i class="ti ti-arrows-diagonal"></i></span>
    </div>
    <div class="card-tbl-body">
      <?php if (empty($topWP)): ?>
        <div class="empty-state">Tidak ada data</div>
      <?php else: ?>
        <div class="scroll-y">
          <table class="table-mini">
            <thead><tr><th>Inisial</th><th class="text-end">Setor</th></tr></thead>
            <tbody>
              <?php foreach ($topWP as $r): ?>
                <tr>
                  <td><?= htmlspecialchars($r['nama_wp_anonim']) ?></td>
                  <td class="text-end"><?= formatRupiahShort($r['total'], true) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- ============== ROW 2 ============== -->
<div class="dash-grid grid-row2 row2">
  <div class="card-tbl" onclick="openDetail('penerimaan-bulan')">
    <div class="card-tbl-header">
      <h3 class="card-tbl-title">Penerimaan per Bulan Setor</h3>
      <span class="card-tbl-action"><i class="ti ti-arrows-diagonal"></i></span>
    </div>
    <div class="card-tbl-body"><div class="chart-wrap"><div id="chartBulan" class="echart-container"></div></div></div>
  </div>
  <div class="card-tbl" onclick="openDetail('penerimaan-seksi')">
    <div class="card-tbl-header">
      <h3 class="card-tbl-title">Penerimaan per Unit Organisasi</h3>
      <span class="card-tbl-action"><i class="ti ti-arrows-diagonal"></i></span>
    </div>
    <div class="card-tbl-body"><div class="chart-wrap"><div id="chartSeksi" class="echart-container"></div></div></div>
  </div>
  <div class="card-tbl" onclick="openDetail('penerimaan-jenis')">
    <div class="card-tbl-header">
      <h3 class="card-tbl-title">Penerimaan per Jenis Pajak</h3>
      <span class="card-tbl-action"><i class="ti ti-arrows-diagonal"></i></span>
    </div>
    <div class="card-tbl-body"><div class="chart-wrap"><div id="chartJenis" class="echart-container"></div></div></div>
  </div>
  <div class="card-tbl" onclick="openDetail('penerimaan-sektor')">
    <div class="card-tbl-header">
      <h3 class="card-tbl-title">Penerimaan per Sektor (Top 8)</h3>
      <span class="card-tbl-action"><i class="ti ti-arrows-diagonal"></i></span>
    </div>
    <div class="card-tbl-body"><div class="chart-wrap"><div id="chartSektor" class="echart-container"></div></div></div>
  </div>
</div>

<!-- ============== ROW 3 ============== -->
<div class="dash-grid grid-row3 row3">
  <div class="card-tbl" onclick="openDetail('top-ar')">
    <div class="card-tbl-header">
      <h3 class="card-tbl-title">Top <?= count($topAR) ?> AR (by Setoran)</h3>
      <span class="card-tbl-action"><i class="ti ti-arrows-diagonal"></i></span>
    </div>
    <div class="card-tbl-body"><div class="chart-wrap"><div id="chartTopAR" class="echart-container"></div></div></div>
  </div>
  <div class="card-tbl" onclick="openDetail('top-map')">
    <div class="card-tbl-header">
      <h3 class="card-tbl-title">Top <?= count($topMAP) ?> Kode MAP</h3>
      <span class="card-tbl-action"><i class="ti ti-arrows-diagonal"></i></span>
    </div>
    <div class="card-tbl-body"><div class="chart-wrap"><div id="chartTopMAP" class="echart-container"></div></div></div>
  </div>
  <div class="card-tbl" onclick="openDetail('jenis-pie')">
    <div class="card-tbl-header">
      <h3 class="card-tbl-title">Distribusi Jenis Pajak</h3>
      <span class="card-tbl-action"><i class="ti ti-arrows-diagonal"></i></span>
    </div>
    <div class="card-tbl-body"><div class="chart-wrap"><div id="chartJenisPie" class="echart-container"></div></div></div>
  </div>
  <div class="card-tbl" onclick="openDetail('seksi-pie')">
    <div class="card-tbl-header">
      <h3 class="card-tbl-title">Distribusi Per Seksi</h3>
      <span class="card-tbl-action"><i class="ti ti-arrows-diagonal"></i></span>
    </div>
    <div class="card-tbl-body"><div class="chart-wrap"><div id="chartSeksiPie" class="echart-container"></div></div></div>
  </div>
</div>

<!-- ============== ROW 4 ============== -->
<div class="dash-grid grid-row4 row4">
  <div class="card-tbl" style="cursor:default;">
    <div class="card-tbl-header">
      <h3 class="card-tbl-title"><i class="ti ti-megaphone"></i> Pengumuman</h3>
    </div>
    <div class="card-tbl-body" style="font-size:11px;">
      Selamat datang di Dashboard <b><?= htmlspecialchars(getSetting('kpp_name', '[Nama KPP]')) ?></b>.<br>
      Total <b><?= number_format($realisasi['count_trx']) ?></b> transaksi tercatat dari <b><?= number_format($realisasi['count_wp']) ?></b> Wajib Pajak.
    </div>
  </div>
  <div class="card-tbl" onclick="openDetail('trend-harian')">
    <div class="card-tbl-header">
      <h3 class="card-tbl-title">Tren Harian Penerimaan</h3>
      <span class="card-tbl-action"><i class="ti ti-arrows-diagonal"></i></span>
    </div>
    <div class="card-tbl-body"><div class="chart-wrap"><div id="chartTrend" class="echart-container"></div></div></div>
  </div>
</div>

<!-- ============== MODAL ============== -->
<div class="modal-tbl" id="detailModal" onclick="if(event.target===this)closeDetail()">
  <div class="modal-tbl-content">
    <div class="modal-tbl-header">
      <h3 id="modalTitle">Detail</h3>
      <button class="modal-tbl-close" onclick="closeDetail()">&times;</button>
    </div>
    <div class="modal-tbl-body">
      <p style="color:#6c757d; margin-bottom:12px;">Halaman detail untuk widget ini akan menampilkan: tabel lengkap dengan filter tanggal, ekspor Excel/PDF, dan breakdown per dimensi.</p>
      <div style="background:#eef4fc; border-left:3px solid var(--tblr-primary); padding:10px 12px; border-radius:6px; font-size:12px;">
        <i class="ti ti-info-circle"></i>
        <b>Catatan:</b> Konten modal akan di-load via AJAX dari <code>api/detail.php?widget=NAMA</code>. Endpoint ini akan dibikin di iterasi berikutnya.
      </div>
    </div>
  </div>
</div>

<script>
// === DATA DARI PHP ===
var dashboardData = {
    perBulan: <?= json_encode($perBulan) ?>,
    perSeksi: <?= json_encode($perSeksi) ?>,
    perJenis: <?= json_encode($perJenis) ?>,
    perSektor: <?= json_encode($perSektor) ?>,
    topAR: <?= json_encode($topAR) ?>,
    topMAP: <?= json_encode($topMAP) ?>,
    trendHarian: <?= json_encode($trendHarian) ?>,
    realisasiPct: <?= $realisasiPct ?>
};

// === ECHARTS COMMON ===
var tblrPrimary = '#206bc4';
var tblrPrimaryLight = '#4299e1';
var tblrSecondary = '#bfdbfe';
var echartFontFamily = 'Inter, sans-serif';
var baseGrid = { left: 30, right: 10, top: 30, bottom: 20, containLabel: true };
var baseTextStyle = { fontFamily: echartFontFamily, fontSize: 10 };

window.echartInstances = {};

function formatNumberShort(v) {
    if (v === null || v === undefined) return '0';
    var n = parseFloat(v);
    if (n >= 1e12) return (n/1e12).toFixed(1).replace('.', ',') + ' T';
    if (n >= 1e9) return (n/1e9).toFixed(1).replace('.', ',') + ' M';
    if (n >= 1e6) return (n/1e6).toFixed(1).replace('.', ',') + ' jt';
    if (n >= 1e3) return (n/1e3).toFixed(0) + ' rb';
    return n.toLocaleString('id-ID');
}

function makeBar(elId, labels, dataA, dataB, labelA, labelB) {
    var el = document.getElementById(elId);
    if (!el) return null;
    var chart = echarts.init(el);
    var series = [];
    if (dataA && dataA.length > 0) {
        series.push({
            name: labelA,
            type: 'bar',
            data: dataA,
            itemStyle: { color: tblrSecondary, borderRadius: [3,3,0,0] }
        });
    }
    if (dataB && dataB.length > 0) {
        series.push({
            name: labelB,
            type: 'bar',
            data: dataB,
            itemStyle: { color: tblrPrimary, borderRadius: [3,3,0,0] }
        });
    }
    chart.setOption({
        tooltip: {
            trigger: 'axis',
            textStyle: baseTextStyle,
            axisPointer: { type: 'shadow' },
            valueFormatter: function(v) { return formatNumberShort(v); }
        },
        legend: series.length > 1 ? { top: 0, textStyle: baseTextStyle, itemWidth: 10, itemHeight: 10 } : { show: false },
        grid: baseGrid,
        xAxis: {
            type: 'category',
            data: labels,
            axisLabel: { fontSize: 10, fontFamily: echartFontFamily, interval: 0, rotate: labels.length > 6 ? 30 : 0 },
            axisLine: { lineStyle: { color: '#e6e7e9' } }
        },
        yAxis: {
            type: 'value',
            axisLabel: {
                fontSize: 10,
                fontFamily: echartFontFamily,
                formatter: function(v) { return formatNumberShort(v); }
            },
            splitLine: { lineStyle: { color: '#f3f4f6' } }
        },
        series: series
    });
    return chart;
}

function makeHorizontalBar(elId, categories, values, color, customTooltip) {
    var el = document.getElementById(elId);
    if (!el) return null;
    var chart = echarts.init(el);
    chart.setOption({
        tooltip: {
            trigger: 'axis',
            textStyle: baseTextStyle,
            axisPointer: { type: 'shadow' },
            formatter: customTooltip || function(p) {
                return p[0].name + '<br/>' + formatNumberShort(p[0].value);
            }
        },
        grid: { left: 90, right: 30, top: 10, bottom: 20 },
        xAxis: {
            type: 'value',
            axisLabel: { fontSize: 9, formatter: function(v) { return formatNumberShort(v); } },
            splitLine: { lineStyle: { color: '#f3f4f6' } }
        },
        yAxis: {
            type: 'category',
            data: categories,
            axisLabel: { fontSize: 9 }
        },
        series: [{
            type: 'bar',
            data: values,
            itemStyle: { color: color || tblrPrimary, borderRadius: [0,3,3,0] }
        }]
    });
    return chart;
}

function makePie(elId, data) {
    var el = document.getElementById(elId);
    if (!el) return null;
    var chart = echarts.init(el);
    chart.setOption({
        tooltip: {
            trigger: 'item',
            textStyle: baseTextStyle,
            valueFormatter: function(v) { return formatNumberShort(v); }
        },
        legend: { top: 0, textStyle: baseTextStyle, itemWidth: 10, itemHeight: 10, type: 'scroll' },
        series: [{
            type: 'pie',
            radius: ['45%', '70%'],
            center: ['50%', '60%'],
            avoidLabelOverlap: true,
            label: { show: false },
            labelLine: { show: false },
            itemStyle: { borderRadius: 4, borderColor: '#fff', borderWidth: 2 },
            data: data
        }]
    });
    return chart;
}

function initCharts() {
    // GAUGE REALISASI
    var gaugeEl = document.getElementById('gaugeRealisasi');
    if (gaugeEl) {
        window.echartInstances.gaugeRealisasi = echarts.init(gaugeEl);
        window.echartInstances.gaugeRealisasi.setOption({
            series: [{
                type: 'gauge',
                startAngle: 200, endAngle: -20,
                min: 0, max: 100,
                progress: { show: true, width: 14, itemStyle: { color: tblrPrimary } },
                axisLine: { lineStyle: { width: 14, color: [[1, '#e6e7e9']] } },
                axisTick: { show: false },
                splitLine: { show: false },
                axisLabel: { show: false },
                pointer: { show: false },
                detail: {
                    valueAnimation: true,
                    formatter: '{value}%',
                    fontSize: 22,
                    fontWeight: 700,
                    color: tblrPrimary,
                    offsetCenter: [0, '0%']
                },
                data: [{ value: parseFloat(dashboardData.realisasiPct.toFixed(2)) }]
            }]
        });
    }

    // PER BULAN (komparasi 2 tahun)
    if (dashboardData.perBulan && dashboardData.perBulan.labels && dashboardData.perBulan.labels.length > 0) {
        window.echartInstances.chartBulan = makeBar(
            'chartBulan',
            dashboardData.perBulan.labels,
            dashboardData.perBulan.previous,
            dashboardData.perBulan.current,
            String(dashboardData.perBulan.previous_year),
            String(dashboardData.perBulan.current_year)
        );
    }

    // PER SEKSI (single bar vertikal, label dipendekkan)
    if (dashboardData.perSeksi && dashboardData.perSeksi.length > 0) {
        var seksiLabels = dashboardData.perSeksi.map(function(r) {
            // "Seksi Pengawasan I" → "Wask I"
            return (r.unit_organisasi || '').replace('Seksi Pengawasan ', 'Wask ');
        });
        var seksiValues = dashboardData.perSeksi.map(function(r) { return parseFloat(r.total); });
        window.echartInstances.chartSeksi = makeBar('chartSeksi', seksiLabels, null, seksiValues, null, 'Total');
    }

    // PER JENIS
    if (dashboardData.perJenis && dashboardData.perJenis.length > 0) {
        var jenisLabels = dashboardData.perJenis.map(function(r) { return r.kategori || 'Lain'; });
        var jenisValues = dashboardData.perJenis.map(function(r) { return parseFloat(r.total); });
        window.echartInstances.chartJenis = makeBar('chartJenis', jenisLabels, null, jenisValues, null, 'Total');
    }

    // PER SEKTOR (horizontal)
    if (dashboardData.perSektor && dashboardData.perSektor.length > 0) {
        var sortedSektor = dashboardData.perSektor.slice().reverse();
        var sektorCats = sortedSektor.map(function(r) {
            var name = r.nm_kategori || '';
            return name.length > 18 ? name.substring(0, 16) + '...' : name;
        });
        var sektorVals = sortedSektor.map(function(r) { return parseFloat(r.total); });
        window.echartInstances.chartSektor = makeHorizontalBar('chartSektor', sektorCats, sektorVals);
    }

    // TOP AR
    if (dashboardData.topAR && dashboardData.topAR.length > 0) {
        var sortedAR = dashboardData.topAR.slice().reverse();
        var arCats = sortedAR.map(function(r) { return r.nama_ar_anonim || '-'; });
        var arVals = sortedAR.map(function(r) { return parseFloat(r.total); });
        window.echartInstances.chartTopAR = makeHorizontalBar('chartTopAR', arCats, arVals);
    }

    // TOP MAP (dengan custom tooltip nampilin nama pajak)
    if (dashboardData.topMAP && dashboardData.topMAP.length > 0) {
        var sortedMAP = dashboardData.topMAP.slice().reverse();
        var mapCats = sortedMAP.map(function(r) { return r.kode_map; });
        var mapVals = sortedMAP.map(function(r) { return parseFloat(r.total); });
        window.echartInstances.chartTopMAP = makeHorizontalBar('chartTopMAP', mapCats, mapVals, tblrPrimaryLight,
            function(p) {
                var idx = sortedMAP.length - 1 - p[0].dataIndex;
                var item = dashboardData.topMAP[idx];
                return '<b>' + item.kode_map + '</b><br/>' +
                       (item.nama_pajak || '-') + '<br/>' +
                       formatNumberShort(p[0].value);
            }
        );
    }

    // PIE JENIS
    if (dashboardData.perJenis && dashboardData.perJenis.length > 0) {
        var jenisData = dashboardData.perJenis.map(function(r) {
            return { value: parseFloat(r.total), name: r.kategori || 'Lain' };
        });
        window.echartInstances.chartJenisPie = makePie('chartJenisPie', jenisData);
    }

    // PIE SEKSI
    if (dashboardData.perSeksi && dashboardData.perSeksi.length > 0) {
        var seksiData = dashboardData.perSeksi.map(function(r) {
            return { value: parseFloat(r.total), name: r.unit_organisasi || '-' };
        });
        window.echartInstances.chartSeksiPie = makePie('chartSeksiPie', seksiData);
    }

    // TREND HARIAN
    if (dashboardData.trendHarian && dashboardData.trendHarian.dates && dashboardData.trendHarian.dates.length > 0) {
        var trendEl = document.getElementById('chartTrend');
        if (trendEl) {
            window.echartInstances.chartTrend = echarts.init(trendEl);
            window.echartInstances.chartTrend.setOption({
                tooltip: {
                    trigger: 'axis',
                    textStyle: baseTextStyle,
                    valueFormatter: function(v) { return formatNumberShort(v); }
                },
                grid: { left: 5, right: 5, top: 5, bottom: 18 },
                xAxis: {
                    type: 'category',
                    boundaryGap: false,
                    data: dashboardData.trendHarian.dates.map(function(d) {
                        var parts = d.split('-');
                        return parts[2] + '/' + parts[1];
                    }),
                    axisLabel: { fontSize: 9 },
                    axisLine: { lineStyle: { color: '#e6e7e9' } }
                },
                yAxis: { type: 'value', show: false },
                series: [{
                    type: 'line',
                    smooth: true,
                    data: dashboardData.trendHarian.values,
                    lineStyle: { color: tblrPrimary, width: 2 },
                    areaStyle: {
                        color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
                            { offset: 0, color: 'rgba(32,107,196,0.3)' },
                            { offset: 1, color: 'rgba(32,107,196,0.02)' }
                        ])
                    },
                    itemStyle: { color: tblrPrimary },
                    symbol: 'none'
                }]
            });
        }
    }
}

// === MODAL ===
var widgetTitles = {
    'penerimaan-hari': 'Detail Penerimaan per Hari',
    'kpi-sd-hari': 'Detail Realisasi s.d. Hari ini',
    'kpi-bulan': 'Detail Realisasi Bulan ini',
    'top-wp': 'Detail Top Wajib Pajak',
    'penerimaan-bulan': 'Detail Penerimaan per Bulan',
    'penerimaan-seksi': 'Detail per Unit Organisasi',
    'penerimaan-jenis': 'Detail per Jenis Pajak',
    'penerimaan-sektor': 'Detail per Sektor',
    'top-ar': 'Detail Top AR',
    'top-map': 'Detail Top Kode MAP',
    'jenis-pie': 'Distribusi Jenis Pajak',
    'seksi-pie': 'Distribusi Per Seksi',
    'trend-harian': 'Detail Tren Harian'
};

function openDetail(id) {
    var titleEl = document.getElementById('modalTitle');
    var modalEl = document.getElementById('detailModal');
    if (titleEl) titleEl.textContent = widgetTitles[id] || 'Detail';
    if (modalEl) modalEl.classList.add('show');
}

function closeDetail() {
    var modalEl = document.getElementById('detailModal');
    if (modalEl) modalEl.classList.remove('show');
}

// === INIT ===
document.addEventListener('DOMContentLoaded', initCharts);

// Resize charts saat window resize
window.addEventListener('resize', function() {
    Object.values(window.echartInstances).forEach(function(c) {
        if (c) c.resize();
    });
});
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
