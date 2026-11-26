const COLORS = {
    navy: '#0a2a5c',
    maroon: '#7a1f2b',
    blue: '#3a8fd6',
    green: '#2f7d32',
    amber: '#d97706',
};

function readChartData() {
    const node = document.getElementById('ai-chart-data');

    if (! node) {
        return null;
    }

    try {
        return JSON.parse(node.textContent || '{}');
    } catch {
        return null;
    }
}

function hasValues(values) {
    return Array.isArray(values) && values.length > 0;
}

function toggleEmpty(key, isEmpty) {
    const empty = document.querySelector(`[data-chart-empty="${key}"]`);
    const canvas = document.getElementById(`chart-${key.replace('_', '-')}`);

    empty?.classList.toggle('hidden', ! isEmpty);
    canvas?.closest('.chart-frame')?.classList.toggle('hidden', isEmpty);
}

function baseOptions(yTitle) {
    return {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: { boxWidth: 12, font: { size: 11 } },
            },
        },
        scales: {
            x: {
                ticks: {
                    maxRotation: 45,
                    minRotation: 0,
                    font: { size: 10 },
                    callback(value) {
                        const label = this.getLabelForValue(value);
                        return label.length > 18 ? `${label.slice(0, 16)}…` : label;
                    },
                },
                grid: { display: false },
            },
            y: {
                beginAtZero: true,
                title: { display: true, text: yTitle, font: { size: 11 } },
                ticks: { precision: 0 },
            },
        },
    };
}

export async function initAiCharts() {
    const data = readChartData();

    if (! data) {
        return;
    }

    const { default: Chart } = await import('chart.js/auto');

    const low = data.low_stock ?? {};
    const stockouts = data.stockouts ?? {};
    const forecast = data.forecast ?? {};

    toggleEmpty('low_stock', ! hasValues(low.labels));
    toggleEmpty('stockouts', ! hasValues(stockouts.labels));
    toggleEmpty('forecast', ! hasValues(forecast.labels));

    const lowCanvas = document.getElementById('chart-low-stock');
    if (lowCanvas && hasValues(low.labels)) {
        new Chart(lowCanvas, {
            type: 'bar',
            data: {
                labels: low.labels,
                datasets: [
                    {
                        label: 'On hand',
                        data: low.on_hand,
                        backgroundColor: COLORS.maroon,
                    },
                    {
                        label: 'Reorder level',
                        data: low.reorder,
                        backgroundColor: COLORS.navy,
                    },
                ],
            },
            options: baseOptions('Quantity'),
        });
    }

    const stockoutCanvas = document.getElementById('chart-stockouts');
    if (stockoutCanvas && hasValues(stockouts.labels)) {
        new Chart(stockoutCanvas, {
            type: 'bar',
            data: {
                labels: stockouts.labels,
                datasets: [
                    {
                        label: stockouts.value_label || 'Stock-outs',
                        data: stockouts.values,
                        backgroundColor: COLORS.blue,
                    },
                ],
            },
            options: baseOptions(stockouts.value_label || 'Count'),
        });
    }

    const forecastCanvas = document.getElementById('chart-forecast');
    if (forecastCanvas && hasValues(forecast.labels)) {
        const colors = (forecast.risks ?? []).map((risk) => {
            if (risk === 'high') {
                return COLORS.maroon;
            }
            if (risk === 'medium') {
                return COLORS.amber;
            }
            return COLORS.green;
        });

        new Chart(forecastCanvas, {
            type: 'bar',
            data: {
                labels: forecast.labels,
                datasets: [
                    {
                        label: forecast.source === 'deepseek' ? '30-day forecast' : '30-day outlook',
                        data: forecast.values,
                        backgroundColor: colors.length === forecast.labels.length ? colors : COLORS.blue,
                    },
                ],
            },
            options: baseOptions('Expected qty out'),
        });
    }
}
