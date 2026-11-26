const COLORS = {
    navy: '#0a2a5c',
    maroon: '#7a1f2b',
    blue: '#3a8fd6',
    sky: '#6eb6e8',
    green: '#2f7d32',
    amber: '#d97706',
    cream: '#f4f1e8',
};

const CATEGORY_COLORS = [
    COLORS.navy,
    COLORS.blue,
    COLORS.maroon,
    COLORS.green,
    COLORS.sky,
    COLORS.amber,
    '#5b7c99',
];

function readDashData() {
    const node = document.getElementById('dash-chart-data');

    if (! node) {
        return null;
    }

    try {
        return JSON.parse(node.textContent || '{}');
    } catch {
        return null;
    }
}

function paintLegendSwatches() {
    document.querySelectorAll('[data-legend-index]').forEach((el) => {
        const index = Number(el.getAttribute('data-legend-index') || 0);
        el.style.background = CATEGORY_COLORS[index % CATEGORY_COLORS.length];
    });
}

async function initCharts(data) {
    const requestsCanvas = document.getElementById('dash-chart-requests');
    const salesCanvas = document.getElementById('dash-chart-sales');
    const categoriesCanvas = document.getElementById('dash-chart-categories');

    if (! requestsCanvas && ! salesCanvas && ! categoriesCanvas) {
        return;
    }

    const { default: Chart } = await import('chart.js/auto');

    if (requestsCanvas && data.requestTrend) {
        new Chart(requestsCanvas, {
            type: 'line',
            data: {
                labels: data.requestTrend.labels ?? [],
                datasets: [{
                    label: 'Requests',
                    data: data.requestTrend.values ?? [],
                    borderColor: COLORS.navy,
                    backgroundColor: 'rgba(58, 143, 214, 0.18)',
                    fill: true,
                    tension: 0.35,
                    pointRadius: 4,
                    pointBackgroundColor: COLORS.blue,
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: COLORS.navy,
                        padding: 10,
                    },
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: 'rgba(10,42,92,0.55)', font: { size: 11 } },
                    },
                    y: {
                        beginAtZero: true,
                        ticks: { precision: 0, color: 'rgba(10,42,92,0.55)', font: { size: 11 } },
                        grid: { color: 'rgba(10,42,92,0.06)' },
                    },
                },
            },
        });
    }

    if (salesCanvas && data.salesTrend) {
        new Chart(salesCanvas, {
            type: 'bar',
            data: {
                labels: data.salesTrend.labels ?? [],
                datasets: [
                    {
                        label: 'Sales (₱)',
                        data: data.salesTrend.sales ?? [],
                        backgroundColor: COLORS.navy,
                        borderRadius: 6,
                        maxBarThickness: 28,
                    },
                    {
                        label: 'Stock-ins',
                        data: data.salesTrend.stockIns ?? [],
                        backgroundColor: COLORS.green,
                        borderRadius: 6,
                        maxBarThickness: 28,
                        yAxisID: 'y1',
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { boxWidth: 10, font: { size: 11 } },
                    },
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: 'rgba(10,42,92,0.55)', font: { size: 11 } },
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: 'rgba(10,42,92,0.55)',
                            font: { size: 10 },
                            callback(value) {
                                return `₱${Number(value).toLocaleString()}`;
                            },
                        },
                        grid: { color: 'rgba(10,42,92,0.06)' },
                    },
                    y1: {
                        beginAtZero: true,
                        position: 'right',
                        grid: { display: false },
                        ticks: { precision: 0, color: 'rgba(10,42,92,0.45)', font: { size: 10 } },
                    },
                },
            },
        });
    }

    if (categoriesCanvas && data.categories) {
        const values = data.categories.values ?? [];

        new Chart(categoriesCanvas, {
            type: 'doughnut',
            data: {
                labels: data.categories.labels ?? [],
                datasets: [{
                    data: values,
                    backgroundColor: CATEGORY_COLORS.slice(0, Math.max(values.length, 1)),
                    borderWidth: 0,
                    hoverOffset: 4,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '72%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label(ctx) {
                                const total = values.reduce((sum, n) => sum + Number(n), 0) || 1;
                                const pct = ((Number(ctx.raw) / total) * 100).toFixed(1);

                                return ` ${ctx.label}: ${ctx.raw} (${pct}%)`;
                            },
                        },
                    },
                },
            },
        });
    }
}

function initCalendar() {
    const root = document.getElementById('dash-calendar');

    if (! root) {
        return;
    }

    let dueDates = [];

    try {
        dueDates = JSON.parse(root.getAttribute('data-due-dates') || '[]');
    } catch {
        dueDates = [];
    }

    const dueSet = new Set(dueDates);
    let cursor = new Date();
    cursor = new Date(cursor.getFullYear(), cursor.getMonth(), 1);

    const prev = document.querySelector('[data-cal-prev]');
    const next = document.querySelector('[data-cal-next]');

    const render = () => {
        const year = cursor.getFullYear();
        const month = cursor.getMonth();
        const firstDow = new Date(year, month, 1).getDay();
        const daysInMonth = new Date(year, month + 1, 0).getDate();
        const now = new Date();
        const todayKey = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
        const monthLabel = cursor.toLocaleString(undefined, { month: 'long', year: 'numeric' });

        let cells = '';

        for (let i = 0; i < firstDow; i += 1) {
            cells += '<span class="dash-calendar__day is-muted"></span>';
        }

        for (let day = 1; day <= daysInMonth; day += 1) {
            const key = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            const classes = ['dash-calendar__day'];

            if (key === todayKey) {
                classes.push('is-today');
            }

            if (dueSet.has(key)) {
                classes.push('has-due');
            }

            cells += `<span class="${classes.join(' ')}" title="${dueSet.has(key) ? 'Payment due' : ''}">${day}</span>`;
        }

        root.innerHTML = `
            <p class="dash-calendar__month">${monthLabel}</p>
            <div class="dash-calendar__weekdays">
                <span>Su</span><span>Mo</span><span>Tu</span><span>We</span><span>Th</span><span>Fr</span><span>Sa</span>
            </div>
            <div class="dash-calendar__grid">${cells}</div>
        `;
    };

    prev?.addEventListener('click', () => {
        cursor = new Date(cursor.getFullYear(), cursor.getMonth() - 1, 1);
        render();
    });

    next?.addEventListener('click', () => {
        cursor = new Date(cursor.getFullYear(), cursor.getMonth() + 1, 1);
        render();
    });

    render();
}

export async function initDashboard() {
    const data = readDashData();

    if (! data) {
        return;
    }

    paintLegendSwatches();
    initCalendar();
    await initCharts(data);
}
