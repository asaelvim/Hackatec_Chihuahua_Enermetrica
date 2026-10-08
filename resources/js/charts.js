import { Chart, LineController, LineElement, PointElement, LinearScale, CategoryScale, Tooltip, Legend, Filler } from 'chart.js';

Chart.register(LineController, LineElement, PointElement, LinearScale, CategoryScale, Tooltip, Legend, Filler);

/**
 * Alpine component factory for a Chart.js line chart that is updated from
 * Livewire via a dispatched browser event instead of Livewire's own DOM diffing
 * (the chart's wrapping element must have `wire:ignore`).
 */
window.initLineChart = function (initialData, eventName) {
    let chart = null;

    return {
        init() {
            chart = new Chart(this.$refs.canvas, {
                type: 'line',
                data: initialData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    scales: {
                        y: { beginAtZero: true },
                    },
                },
            });

            if (eventName) {
                window.addEventListener(eventName, (event) => {
                    const data = event.detail.data;

                    chart.data.labels = data.labels;
                    data.datasets.forEach((dataset, index) => {
                        if (chart.data.datasets[index]) {
                            chart.data.datasets[index].data = dataset.data;
                            chart.data.datasets[index].label = dataset.label;
                        } else {
                            chart.data.datasets[index] = dataset;
                        }
                    });
                    chart.data.datasets.length = data.datasets.length;
                    chart.update();
                });
            }
        },
    };
};
