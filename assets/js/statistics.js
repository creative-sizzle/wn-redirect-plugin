import Chart from 'chart.js/auto';

const config = {
    type: 'bar',
    data: {
        labels: [],
        datasets: [],
    },
    options: {
        plugins: {
            legend: { position: 'bottom' },
        },
        scales: {
            x: { stacked: true },
            y: { stacked: true },
        },
    },
};

const updateChart = (chart) => {
    const $monthYearSelected = document.getElementById('period-month-year');

    // @ts-ignore
    $.request('onLoadHitsPerDay', {
        data: {
            'period_month_year': $monthYearSelected.value,
        },
        loading: $.wn.stripeLoadIndicator,
        success: (response) => {
            chart.data.datasets = response.datasets;
            chart.data.labels = response.labels;

            chart.update();
        }
    })
}

document.addEventListener('DOMContentLoaded', () => {
    const $hitsPerDayChart = document.getElementById('hitsPerDayChart');
    const hitsPerDayChart = new Chart($hitsPerDayChart, config);

    updateChart(hitsPerDayChart);

    document.getElementById('period-month-year')?.addEventListener('change', () => {
        updateChart(hitsPerDayChart);
    });
});
