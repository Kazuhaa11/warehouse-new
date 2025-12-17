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
    (function() {
        let trendChart = null;

        async function onReady() {
            try {
                const res = await fetch("<?= base_url('api/v1/barang/movement-trend') ?>");
                const trend = await res.json();
                if (!trend.success) throw new Error("Gagal load chart data");

                const t = trend.data;

                const ctx = document.getElementById("chartMovementBar").getContext("2d");

                if (trendChart) trendChart.destroy();

                trendChart = new Chart(ctx, {
                    type: "bar",
                    data: {
                        labels: t.labels, 
                        datasets: [{
                                label: "Fast Moving",
                                data: t.fast,
                                backgroundColor: "#28a745",
                            },
                            {
                                label: "Slow Moving",
                                data: t.slow,
                                backgroundColor: "#ffc107",
                            },
                            {
                                label: "Dead Item",
                                data: t.dead,
                                backgroundColor: "#dc3545",
                            },
                        ],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,

                        plugins: {
                            legend: {
                                position: "bottom",
                            },
                            title: {
                                display: true,
                                text: "Tren Fast / Slow / Dead Movement (12 Bulan Terakhir)",
                                padding: {
                                    top: 10,
                                    bottom: 10
                                },
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(ctx) {
                                        return `${ctx.dataset.label}: ${ctx.parsed.y}`;
                                    },
                                },
                            },
                        },

                        scales: {
                            x: {
                                stacked: true
                            },
                            y: {
                                stacked: true,
                                beginAtZero: true,
                                ticks: {
                                    precision: 0
                                },
                            },
                        },

                        onClick: (evt, elements) => {
                            if (!elements.length) return;

                            const bar = elements[0];
                            const datasetIndex = bar.datasetIndex;
                            const labelIndex = bar.index;

                            const selectedCategory = trendChart.data.datasets[datasetIndex].label;
                            const selectedMonth = trendChart.data.labels[labelIndex];

                            const typeMap = {
                                "Fast Moving": "fast",
                                "Slow Moving": "slow",
                                "Dead Item": "dead",
                            };

                            const typeValue = typeMap[selectedCategory];

                            console.log("CLICK:", selectedMonth, typeValue);

                            const monthSel = document.getElementById("filterMonth");
                            const typeSel = document.getElementById("filterType");

                            if (monthSel && typeSel) {
                                monthSel.value = selectedMonth; // ex: "Oct 2025"
                                typeSel.value = typeValue;

                                monthSel.dispatchEvent(new Event("change"));
                                typeSel.dispatchEvent(new Event("change"));
                            }
                        },
                    },
                });
            } catch (err) {
                console.warn("movement chart:", err.message);
            }
        }

        if (window.Chart) onReady();
        else document.addEventListener("chartjs:ready", onReady, {
            once: true
        });

    })();
</script>