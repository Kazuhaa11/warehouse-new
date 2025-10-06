<?php ?>
<div class="card mb-4">
    <div class="card-header"><i class="fas fa-chart-line me-2"></i> Tren Material (6 Bulan)</div>
    <div class="card-body">
        <canvas id="chartMaterial" height="120"></canvas>
    </div>
</div>

<script>
    (function () {
        function loadChartJs(cb) {
            if (window.Chart) return cb();
            if (window.__chartjs_loading__) return document.addEventListener('chartjs:ready', cb, { once: true });
            window.__chartjs_loading__ = true;
            const s = document.createElement('script');
            s.src = "https://cdn.jsdelivr.net/npm/chart.js";
            s.onload = () => { window.__chartjs_loading__ = false; document.dispatchEvent(new Event('chartjs:ready')); cb(); };
            document.head.appendChild(s);
        }

        function render() {
            const API = "<?= base_url('api/v1/stats/material') ?>";
            fetch(API).then(r => r.json()).then(j => {
                if (!j.success) throw new Error(j?.error?.message || 'Gagal load chart');
                const d = j.data || {};
                const ctx = document.getElementById('chartMaterial').getContext('2d');
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: d.labels || [],
                        datasets: [{
                            label: 'Material',
                            data: d.series || [],
                            tension: 0.25,
                            fill: false,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: true } },
                        scales: {
                            y: { beginAtZero: true, ticks: { precision: 0 } }
                        }
                    }
                });
            }).catch(err => console.warn('material chart:', err.message));
        }
        loadChartJs(render);
    })();
</script>