let myChart = null;
let currentPage = 1;
let totalPages = 1;

function chargerHistorique(formData = new FormData()) {
    fetch('historique.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            document.getElementById('tableHistorique').innerHTML = data.tbody;
            currentPage = data.currentPage;
            totalPages = data.totalPages;
            document.getElementById('page-info').innerText = `Page ${currentPage} / ${totalPages}`;
            document.getElementById('prevPage').disabled = (currentPage <= 1);
            document.getElementById('nextPage').disabled = (currentPage >= totalPages || totalPages === 0);
            renderChart(data.trendLabels, data.trendData);
        });
}

function renderChart(labels, values) {
    const ctx = document.getElementById('trendChart').getContext('2d');
    if (myChart) myChart.destroy();
    myChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Arrêts',
                data: values,
                borderColor: '#1976d2',
                backgroundColor: 'rgba(25, 118, 210, 0.1)',
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
        }
    });
}

function changePage(delta) {
    let newPage = currentPage + delta;
    if (newPage >= 1 && newPage <= totalPages) {
        let formData = new FormData(document.getElementById('formhist'));
        formData.append('page', newPage);
        chargerHistorique(formData);
    }
}

document.addEventListener('DOMContentLoaded', () => chargerHistorique());
document.getElementById('formhist').addEventListener('submit', function(e) {
    e.preventDefault();
    chargerHistorique(new FormData(this));
});
document.getElementById('clearFilter').addEventListener('click', () => {
    document.getElementById('formhist').reset();
    chargerHistorique();
});
document.getElementById('exportCSV').addEventListener('click', () => {
    let params = new URLSearchParams(new FormData(document.getElementById('formhist'))).toString();
    window.location.href = 'export_csv.php?' + params;
});