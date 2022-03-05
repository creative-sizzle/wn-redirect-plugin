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
        success: function (response, textStatus, jqXhr) {
            chart.data.datasets = response.data.datasets;
            chart.data.labels = response.data.labels;

            chart.update();

            this.success(response, textStatus, jqXhr);
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
