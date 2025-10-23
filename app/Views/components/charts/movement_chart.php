<?php ?>
<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-chart-pie me-2"></i> Fast / Slow / Dead Moving (12 Bulan)
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-12">
                <canvas id="chartMovementBar" height="200"></canvas>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        let trendChart = null;

        async function onReady() {
            try {
                const res = await fetch("<?= base_url('api/v1/barang/movement-trend') ?>");
                const trend = await res.json();
                if (!trend.success) throw new Error('Gagal load chart data');

                const t = trend.data;
                const ctxBar = document.getElementById('chartMovementBar').getContext('2d');
                trendChart = new Chart(ctxBar, {
                    type: 'bar',
                    data: {
                        labels: t.labels,
                        datasets: [
                            { label: 'Fast Moving', data: t.fast, backgroundColor: '#28a745' },
                            { label: 'Slow Moving', data: t.slow, backgroundColor: '#ffc107' },
                            { label: 'Dead Item', data: t.dead, backgroundColor: '#dc3545' }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { position: 'bottom' },
                            title: { display: true, text: 'Tren Pergerakan Barang (12 Bulan Terakhir)' }
                        },
                        scales: {
                            x: { stacked: true },
                            y: { stacked: true, beginAtZero: true, ticks: { precision: 0 } }
                        },
                        onClick: async (evt, elements) => {
                            if (!elements.length) return;
                            const el = elements[0];
                            const datasetIndex = el.datasetIndex;
                            const labelIndex = el.index;
                            const categoryLabel = trendChart.data.datasets[datasetIndex].label;
                            const month = trendChart.data.labels[labelIndex];
                            console.log(`Klik: ${month} - ${categoryLabel}`);
                        }
                    }
                });
            } catch (err) {
                console.warn('movement chart:', err.message);
            }
        }

        if (window.Chart) onReady();
        else document.addEventListener('chartjs:ready', onReady, { once: true });
    })();
</script>