<?php /** Peminjaman Storage Chart */ ?>
<div class="card mb-4">
  <div class="card-header"><i class="fas fa-chart-bar me-2"></i> Peminjaman Storage (6 Bulan)</div>
  <div class="card-body">
    <canvas id="chartPeminjaman" height="120"></canvas>
  </div>
</div>

<script>
(function(){
  function onReady(){
    const API = "<?= base_url('api/v1/stats/peminjaman') ?>";
    fetch(API).then(r=>r.json()).then(j=>{
      if (!j.success) throw new Error(j?.error?.message || 'Gagal load chart');
      const d = j.data || {};
      const ctx = document.getElementById('chartPeminjaman').getContext('2d');
      new Chart(ctx, {
        type: 'bar',
        data: {
          labels: d.labels || [],
          datasets: [{ label: 'Transaksi', data: d.series || [] }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: { legend: { display: true } },
          scales: { y: { beginAtZero: true, ticks: { precision:0 } } }
        }
      });
    }).catch(err=>console.warn('peminjaman chart:', err.message));
  }
  if (window.Chart) onReady(); else document.addEventListener('chartjs:ready', onReady, {once:true});
})();
</script>
