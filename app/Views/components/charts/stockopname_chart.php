<?php /** Stock Opname Chart */ ?>
<div class="card mb-4">
  <div class="card-header"><i class="fas fa-chart-area me-2"></i> Stock Opname (6 Bulan)</div>
  <div class="card-body">
    <canvas id="chartStockOpname" height="120"></canvas>
  </div>
</div>

<script>
(function(){
  function onReady(){
    const API = "<?= base_url('api/v1/stats/stock-opname') ?>";
    fetch(API).then(r=>r.json()).then(j=>{
      if (!j.success) throw new Error(j?.error?.message || 'Gagal load chart');
      const d = j.data || {};
      const ctx = document.getElementById('chartStockOpname').getContext('2d');
      new Chart(ctx, {
        type: 'line',
        data: {
          labels: d.labels || [],
          datasets: [{ label: 'Sesi', data: d.series || [], tension: 0.25, fill: true }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: { legend: { display: true } },
          scales: { y: { beginAtZero: true, ticks: { precision:0 } } }
        }
      });
    }).catch(err=>console.warn('stock opname chart:', err.message));
  }
  if (window.Chart) onReady(); else document.addEventListener('chartjs:ready', onReady, {once:true});
})();
</script>
