<script type="text/javascript">
        var gk_isXlsx = false;
        var gk_xlsxFileLookup = {};
        var gk_fileData = {};
        function filledCell(cell) {
          return cell !== '' && cell != null;
        }
        function loadFileData(filename) {
        if (gk_isXlsx && gk_xlsxFileLookup[filename]) {
            try {
                var workbook = XLSX.read(gk_fileData[filename], { type: 'base64' });
                var firstSheetName = workbook.SheetNames[0];
                var worksheet = workbook.Sheets[firstSheetName];

                // Convert sheet to JSON to filter blank rows
                var jsonData = XLSX.utils.sheet_to_json(worksheet, { header: 1, blankrows: false, defval: '' });
                // Filter out blank rows (rows where all cells are empty, null, or undefined)
                var filteredData = jsonData.filter(row => row.some(filledCell));

                // Heuristic to find the header row by ignoring rows with fewer filled cells than the next row
                var headerRowIndex = filteredData.findIndex((row, index) =>
                  row.filter(filledCell).length >= filteredData[index + 1]?.filter(filledCell).length
                );
                // Fallback
                if (headerRowIndex === -1 || headerRowIndex > 25) {
                  headerRowIndex = 0;
                }

                // Convert filtered JSON back to CSV
                var csv = XLSX.utils.aoa_to_sheet(filteredData.slice(headerRowIndex)); // Create a new sheet from filtered array of arrays
                csv = XLSX.utils.sheet_to_csv(csv, { header: 1 });
                return csv;
            } catch (e) {
                console.error(e);
                return "";
            }
        }
        return gk_fileData[filename] || "";
        }
        </script><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Successful Orders Graph - TechMarket</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            padding: 20px;
        }
        .graph-container {
            background-color: #1a2a44;
            padding: 20px;
            border-radius: 8px;
            max-width: 1000px;
            margin: 0 auto;
            color: #fff;
        }
        .graph-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .graph-header h2 {
            margin: 0;
            font-size: 1.5em;
        }
        .graph-header .filters {
            display: flex;
            gap: 10px;
        }
        .graph-header .filters button {
            padding: 8px 16px;
            border: 1px solid #1ed760;
            background-color: transparent;
            color: #1ed760;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .graph-header .filters button:hover,
        .graph-header .filters button.active {
            background-color: #1ed760;
            color: #fff;
        }
        canvas {
            max-width: 100%;
        }
    </style>
</head>
<body>
    <div class="graph-container">
        <div class="graph-header">
            <h2>TOTAL SUCCESSFUL ORDERS</h2>
            <div class="filters">
                <button class="filter-btn active" data-period="last_3_months">Last 3 months</button>
                <button class="filter-btn" data-period="last_30_days">Last 30 days</button>
                <button class="filter-btn" data-period="last_7_days">Last 7 days</button>
            </div>
        </div>
        <canvas id="ordersChart"></canvas>
    </div>

    <!-- Include Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Initialize Chart.js
        const ctx = document.getElementById('ordersChart').getContext('2d');
        let ordersChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: 'Successful Orders',
                    data: [],
                    borderColor: '#1ed760',
                    backgroundColor: 'rgba(30, 215, 96, 0.2)',
                    fill: false,
                    tension: 0.4,
                    pointBackgroundColor: '#1ed760',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 5
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: '#fff',
                            callback: function(value) {
                                return value.toLocaleString();
                            }
                        },
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
                        }
                    },
                    x: {
                        ticks: {
                            color: '#fff'
                        },
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
                        }
                    }
                },
                plugins: {
                    legend: {
                        labels: {
                            color: '#fff'
                        }
                    }
                },
                responsive: true,
                maintainAspectRatio: false
            }
        });

        // Function to fetch and update chart data
        function fetchOrdersData(period) {
            fetch(`fetch_successful_orders.php?period=${period}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        console.error('Error fetching data:', data.error);
                        return;
                    }

                    // Update chart data
                    ordersChart.data.labels = data.labels;
                    ordersChart.data.datasets[0].data = data.data;
                    ordersChart.update();

                    // Update subtitle
                    const subtitle = document.createElement('p');
                    subtitle.textContent = `Total for the ${period.replace('_', ' ')}`;
                    subtitle.style.color = '#fff';
                    document.querySelector('.graph-header h2').after(subtitle);
                })
                .catch(error => console.error('Error:', error));
        }

        // Add event listeners to filter buttons
        document.querySelectorAll('.filter-btn').forEach(button => {
            button.addEventListener('click', function() {
                // Remove active class from all buttons
                document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
                // Add active class to clicked button
                this.classList.add('active');

                // Remove existing subtitle
                const existingSubtitle = document.querySelector('.graph-header p');
                if (existingSubtitle) {
                    existingSubtitle.remove();
                }

                // Fetch data for the selected period
                const period = this.getAttribute('data-period');
                fetchOrdersData(period);
            });
        });

        // Initial load with default period (last 3 months)
        fetchOrdersData('last_3_months');
    </script>
</body>
</html>