<?php
require_once '../config.php';
requireLogin();

$pdo = getDB();
$user_role = $_SESSION['role'] ?? 'enumerator';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Population Demographic - RBIS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="../assets/css/custom.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .stat-card {
            border-radius: 15px;
            padding: 20px;
            transition: transform 0.3s, box-shadow 0.3s;
            cursor: default;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
        }
        .stat-label {
            font-size: 0.9rem;
            opacity: 0.8;
        }
        .stat-icon {
            font-size: 2.5rem;
            opacity: 0.3;
        }
        .chart-card {
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.3s;
            height: 100%;
        }
        .chart-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }
        .chart-container-sm {
            height: 250px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php include '../includes/sidebar.php'; ?>
            
            <main class="col-md-10 ms-sm-auto px-md-4 main-content">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-users text-primary"></i> Population Demographic
                    </h1>
                    <button class="btn btn-outline-secondary btn-sm" onclick="refreshData()">
                        <i class="fas fa-sync"></i> Refresh Data
                    </button>
                </div>

                <!-- Loading indicator -->
                <div id="loadingOverlay" class="text-center py-5" style="display: none;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading demographic data...</p>
                </div>

                <div id="demographicContent">
                    <!-- Summary Cards -->
                    <div class="row mb-4" id="summaryCards">
                        <div class="col-md-3">
                            <div class="stat-card bg-primary text-white">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="stat-number" id="totalPopulation">0</div>
                                        <div class="stat-label">Total Population</div>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="fas fa-users"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card bg-success text-white">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="stat-number" id="totalHouseholds">0</div>
                                        <div class="stat-label">Total Households</div>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="fas fa-home"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card bg-info text-white">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="stat-number" id="totalMale">0</div>
                                        <div class="stat-label">Total Male</div>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="fas fa-mars"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card bg-warning text-white">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="stat-number" id="totalFemale">0</div>
                                        <div class="stat-label">Total Female</div>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="fas fa-venus"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Charts Row 1 -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card chart-card">
                                <div class="card-header bg-white">
                                    <h5 class="mb-0"><i class="fas fa-venus-mars text-primary"></i> Gender Distribution</h5>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="genderChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card chart-card">
                                <div class="card-header bg-white">
                                    <h5 class="mb-0"><i class="fas fa-ring text-info"></i> Civil Status Distribution</h5>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="civilChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Charts Row 2 -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card chart-card">
                                <div class="card-header bg-white">
                                    <h5 class="mb-0"><i class="fas fa-calendar-alt text-warning"></i> Age Distribution</h5>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="ageChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card chart-card">
                                <div class="card-header bg-white">
                                    <h5 class="mb-0"><i class="fas fa-graduation-cap text-success"></i> Education Level</h5>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="educationChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Charts Row 3 -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card chart-card">
                                <div class="card-header bg-white">
                                    <h5 class="mb-0"><i class="fas fa-passport text-primary"></i> Citizenship Distribution</h5>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="citizenshipChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card chart-card">
                                <div class="card-header bg-white">
                                    <h5 class="mb-0"><i class="fas fa-briefcase text-warning"></i> Top Occupations</h5>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="occupationChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Monthly Trend -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="card chart-card">
                                <div class="card-header bg-white">
                                    <h5 class="mb-0"><i class="fas fa-chart-line text-danger"></i> Monthly Registration Trend</h5>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="monthlyChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        // Chart instances
        let genderChart, civilChart, ageChart, educationChart, citizenshipChart, occupationChart, monthlyChart;
        let chartData = null;

        // Colors
        const colors = {
            blue: '#4e73df',
            green: '#1cc88a',
            red: '#e74a3b',
            yellow: '#f6c23e',
            purple: '#6f42c1',
            orange: '#fd7e14',
            teal: '#20c997',
            pink: '#e83e8c',
            indigo: '#6610f2',
            cyan: '#0dcaf0',
            dark: '#343a40'
        };

        const colorPalette = [
            colors.blue, colors.green, colors.red, colors.yellow, 
            colors.purple, colors.orange, colors.teal, colors.pink,
            colors.indigo, colors.cyan
        ];

        // Load data and render charts
        function loadDemographicData() {
            $('#loadingOverlay').show();
            $('#demographicContent').hide();
            
            $.ajax({
                url: '../api/demographics.php',
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    chartData = data;
                    renderAllCharts(data);
                    updateSummaryCards(data);
                    $('#loadingOverlay').hide();
                    $('#demographicContent').show();
                },
                error: function(xhr, status, error) {
                    console.error('Error loading demographic data:', error);
                    $('#loadingOverlay').html(`
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i> Failed to load demographic data.
                            <button class="btn btn-danger btn-sm ms-2" onclick="loadDemographicData()">
                                <i class="fas fa-sync"></i> Retry
                            </button>
                        </div>
                    `);
                }
            });
        }

        // Update summary cards
        function updateSummaryCards(data) {
            if (data.totals) {
                $('#totalPopulation').text(data.totals.total_individuals || 0);
                $('#totalHouseholds').text(data.totals.total_households || 0);
                $('#totalMale').text(data.totals.total_male || 0);
                $('#totalFemale').text(data.totals.total_female || 0);
            }
        }

        // Render all charts
        function renderAllCharts(data) {
            renderGenderChart(data);
            renderCivilChart(data);
            renderAgeChart(data);
            renderEducationChart(data);
            renderCitizenshipChart(data);
            renderOccupationChart(data);
            renderMonthlyChart(data);
        }

        // 1. Gender Chart
        function renderGenderChart(data) {
            const ctx = document.getElementById('genderChart').getContext('2d');
            
            // Combine gender data from individual and household
            const genderData = {};
            ['Male', 'Female', 'Other'].forEach(gender => {
                genderData[gender] = 0;
            });
            
            // Add individual gender counts
            data.gender_individual.forEach(item => {
                if (genderData[item.sex] !== undefined) {
                    genderData[item.sex] += parseInt(item.count);
                }
            });
            
            // Add household gender counts
            data.gender_household.forEach(item => {
                if (genderData[item.sex] !== undefined) {
                    genderData[item.sex] += parseInt(item.count);
                }
            });
            
            const labels = Object.keys(genderData);
            const values = Object.values(genderData);
            
            if (genderChart) genderChart.destroy();
            
            genderChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: values,
                        backgroundColor: ['#4e73df', '#e74a3b', '#6c757d'],
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    let percentage = total > 0 ? ((context.parsed / total) * 100).toFixed(1) : 0;
                                    return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                                }
                            }
                        }
                    }
                }
            });
        }

        // 2. Civil Status Chart
        function renderCivilChart(data) {
            const ctx = document.getElementById('civilChart').getContext('2d');
            
            const civilData = {};
            data.civil_status.forEach(item => {
                civilData[item.civil_status] = parseInt(item.count);
            });
            
            const labels = Object.keys(civilData);
            const values = Object.values(civilData);
            
            if (civilChart) civilChart.destroy();
            
            civilChart = new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: labels,
                    datasets: [{
                        data: values,
                        backgroundColor: colorPalette.slice(0, labels.length),
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    let percentage = total > 0 ? ((context.parsed / total) * 100).toFixed(1) : 0;
                                    return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                                }
                            }
                        }
                    }
                }
            });
        }

        // 3. Age Distribution Chart
        function renderAgeChart(data) {
            const ctx = document.getElementById('ageChart').getContext('2d');
            
            const ageLabels = [];
            const ageValues = [];
            
            data.age_distribution.forEach(item => {
                ageLabels.push(item.age_group);
                ageValues.push(parseInt(item.count));
            });
            
            if (ageChart) ageChart.destroy();
            
            ageChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ageLabels,
                    datasets: [{
                        label: 'Population',
                        data: ageValues,
                        backgroundColor: colorPalette.slice(0, ageLabels.length),
                        borderColor: '#fff',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        }

        // 4. Education Chart
        function renderEducationChart(data) {
            const ctx = document.getElementById('educationChart').getContext('2d');
            
            const eduLabels = [];
            const eduValues = [];
            
            data.education.forEach(item => {
                eduLabels.push(item.highest_education || 'Unknown');
                eduValues.push(parseInt(item.count));
            });
            
            if (educationChart) educationChart.destroy();
            
            educationChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: eduLabels,
                    datasets: [{
                        label: 'Count',
                        data: eduValues,
                        backgroundColor: ['#1cc88a', '#4e73df', '#f6c23e', '#e74a3b', '#6f42c1', '#20c997'],
                        borderColor: '#fff',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        }

        // 5. Citizenship Chart
        function renderCitizenshipChart(data) {
            const ctx = document.getElementById('citizenshipChart').getContext('2d');
            
            const citLabels = [];
            const citValues = [];
            
            data.citizenship.forEach(item => {
                citLabels.push(item.citizenship);
                citValues.push(parseInt(item.count));
            });
            
            if (citizenshipChart) citizenshipChart.destroy();
            
            citizenshipChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: citLabels,
                    datasets: [{
                        data: citValues,
                        backgroundColor: colorPalette.slice(0, citLabels.length),
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    let percentage = total > 0 ? ((context.parsed / total) * 100).toFixed(1) : 0;
                                    return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                                }
                            }
                        }
                    }
                }
            });
        }

        // 6. Occupation Chart
        function renderOccupationChart(data) {
            const ctx = document.getElementById('occupationChart').getContext('2d');
            
            const occLabels = [];
            const occValues = [];
            
            data.occupation.forEach(item => {
                occLabels.push(item.occupation);
                occValues.push(parseInt(item.count));
            });
            
            if (occupationChart) occupationChart.destroy();
            
            occupationChart = new Chart(ctx, {
                type: 'horizontalBar',
                data: {
                    labels: occLabels,
                    datasets: [{
                        label: 'Count',
                        data: occValues,
                        backgroundColor: colorPalette.slice(0, occLabels.length),
                        borderColor: '#fff',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: 'y',
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        }

        // 7. Monthly Trend Chart
        function renderMonthlyChart(data) {
            const ctx = document.getElementById('monthlyChart').getContext('2d');
            
            const months = [];
            const totals = [];
            
            data.monthly_trend.forEach(item => {
                months.push(item.month);
                totals.push(parseInt(item.total));
            });
            
            if (monthlyChart) monthlyChart.destroy();
            
            monthlyChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: months,
                    datasets: [{
                        label: 'New Registrations',
                        data: totals,
                        borderColor: '#4e73df',
                        backgroundColor: 'rgba(78, 115, 223, 0.1)',
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#4e73df',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        }

        // Refresh data
        function refreshData() {
            loadDemographicData();
        }

        // Load on page ready
        $(document).ready(function() {
            loadDemographicData();
        });
    </script>
</body>
</html>