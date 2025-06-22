<?php
session_start();
include 'database.php';

// Check if user is logged in as police
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'police') {
    header('Location: login.php?type=police');
    exit;
}

// Get date range for reports
$start_date = $_GET['start_date'] ?? date('Y-m-01'); // First day of current month
$end_date = $_GET['end_date'] ?? date('Y-m-d'); // Today

// Crime statistics by category
$stmt = $pdo->prepare("
    SELECT cc.Name, COUNT(ci.CrimeID) as count 
    FROM crime_category cc 
    LEFT JOIN crime_information ci ON cc.CategoryID = ci.CategoryID 
    LEFT JOIN fir f ON ci.FIRID = f.FIRID 
    WHERE f.created_at BETWEEN ? AND ? 
    GROUP BY cc.CategoryID, cc.Name 
    ORDER BY count DESC
");
$stmt->execute([$start_date, $end_date . ' 23:59:59']);
$crime_by_category = $stmt->fetchAll();

// FIR status distribution
$stmt = $pdo->prepare("
    SELECT Status, COUNT(*) as count 
    FROM fir 
    WHERE created_at BETWEEN ? AND ? 
    GROUP BY Status
");
$stmt->execute([$start_date, $end_date . ' 23:59:59']);
$status_distribution = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Monthly trends (last 12 months)
$stmt = $pdo->query("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as count 
    FROM fir 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH) 
    GROUP BY DATE_FORMAT(created_at, '%Y-%m') 
    ORDER BY month
");
$monthly_trends = $stmt->fetchAll();

// Location-wise crime data
$stmt = $pdo->prepare("
    SELECT l.City, l.AreaName, COUNT(ci.CrimeID) as crime_count 
    FROM location l 
    LEFT JOIN crime_information ci ON l.LocationID = ci.LocationID 
    LEFT JOIN fir f ON ci.FIRID = f.FIRID 
    WHERE f.created_at BETWEEN ? AND ? 
    GROUP BY l.LocationID, l.City, l.AreaName 
    HAVING crime_count > 0 
    ORDER BY crime_count DESC 
    LIMIT 10
");
$stmt->execute([$start_date, $end_date . ' 23:59:59']);
$location_crimes = $stmt->fetchAll();

// Priority distribution
$stmt = $pdo->prepare("
    SELECT Priority, COUNT(*) as count 
    FROM fir 
    WHERE created_at BETWEEN ? AND ? 
    GROUP BY Priority
");
$stmt->execute([$start_date, $end_date . ' 23:59:59']);
$priority_distribution = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Resolution rate
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_firs,
        SUM(CASE WHEN Status IN ('resolved', 'closed') THEN 1 ELSE 0 END) as resolved_firs
    FROM fir 
    WHERE created_at BETWEEN ? AND ?
");
$stmt->execute([$start_date, $end_date . ' 23:59:59']);
$resolution_data = $stmt->fetch();
$resolution_rate = $resolution_data['total_firs'] > 0 ? 
    round(($resolution_data['resolved_firs'] / $resolution_data['total_firs']) * 100, 1) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crime Reports & Analytics - Pakistan Crime Management System</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+Pro:wght@300;400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <i class="fas fa-shield-alt"></i>
                    <h1>پاکستان کرائم مینجمنٹ سسٹم</h1>
                    <span class="logo-subtitle">Pakistan Crime Management System</span>
                </div>
                <nav class="nav">
                    <a href="index.php" class="nav-link">Dashboard</a>
                    <a href="manage-firs.php" class="nav-link">Manage FIRs</a>
                    <a href="reports.php" class="nav-link active">Reports</a>
                    <?php if(in_array($_SESSION['user_role'], ['admin', 'ig', 'sp'])): ?>
                    <a href="admin-dashboard.php" class="nav-link">Admin Panel</a>
                    <?php endif; ?>
                    <a href="logout.php" class="nav-link">Logout</a>
                </nav>
            </div>
        </div>
    </header>

    <main class="main">
        <section class="section active">
            <div class="container">
                <div class="reports-page">
                    <div class="page-header">
                        <div>
                            <h2>Crime Reports & Analytics</h2>
                            <p>Comprehensive crime statistics and trends analysis</p>
                        </div>
                        <div class="header-actions">
                            <button onclick="exportReport()" class="btn btn-primary">
                                <i class="fas fa-download"></i>
                                Export Report
                            </button>
                            <button onclick="printReport()" class="btn btn-outline">
                                <i class="fas fa-print"></i>
                                Print
                            </button>
                        </div>
                    </div>

                    <!-- Date Range Filter -->
                    <div class="filter-section">
                        <form method="GET" class="date-filter">
                            <div class="filter-group">
                                <label for="start_date">From Date:</label>
                                <input type="date" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
                            </div>
                            <div class="filter-group">
                                <label for="end_date">To Date:</label>
                                <input type="date" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter"></i>
                                Apply Filter
                            </button>
                        </form>
                    </div>

                    <!-- Key Metrics -->
                    <div class="metrics-row">
                        <div class="metric-card">
                            <div class="metric-icon">
                                <i class="fas fa-file-alt"></i>
                            </div>
                            <div class="metric-content">
                                <h3><?php echo number_format($resolution_data['total_firs']); ?></h3>
                                <p>Total FIRs</p>
                                <span class="metric-period">Period: <?php echo date('M d', strtotime($start_date)) . ' - ' . date('M d, Y', strtotime($end_date)); ?></span>
                            </div>
                        </div>

                        <div class="metric-card">
                            <div class="metric-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="metric-content">
                                <h3><?php echo number_format($resolution_data['resolved_firs']); ?></h3>
                                <p>Resolved Cases</p>
                                <span class="metric-trend success">
                                    <i class="fas fa-arrow-up"></i>
                                    <?php echo $resolution_rate; ?>% Resolution Rate
                                </span>
                            </div>
                        </div>

                        <div class="metric-card">
                            <div class="metric-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="metric-content">
                                <h3><?php echo number_format(($status_distribution['under_review'] ?? 0) + ($status_distribution['investigating'] ?? 0)); ?></h3>
                                <p>Active Cases</p>
                                <span class="metric-trend warning">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    Pending Resolution
                                </span>
                            </div>
                        </div>

                        <div class="metric-card">
                            <div class="metric-icon">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <div class="metric-content">
                                <h3><?php echo number_format(($priority_distribution['high'] ?? 0) + ($priority_distribution['urgent'] ?? 0)); ?></h3>
                                <p>High Priority</p>
                                <span class="metric-trend danger">
                                    <i class="fas fa-arrow-up"></i>
                                    Urgent Attention
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Charts Section -->
                    <div class="charts-section">
                        <div class="chart-row">
                            <div class="chart-card">
                                <div class="chart-header">
                                    <h3>Crime by Category</h3>
                                    <p>Distribution of crimes by type</p>
                                </div>
                                <div class="chart-container">
                                    <canvas id="categoryChart"></canvas>
                                </div>
                            </div>

                            <div class="chart-card">
                                <div class="chart-header">
                                    <h3>FIR Status Distribution</h3>
                                    <p>Current status of all FIRs</p>
                                </div>
                                <div class="chart-container">
                                    <canvas id="statusChart"></canvas>
                                </div>
                            </div>
                        </div>

                        <div class="chart-row">
                            <div class="chart-card full-width">
                                <div class="chart-header">
                                    <h3>Monthly Crime Trends</h3>
                                    <p>Crime reports over the last 12 months</p>
                                </div>
                                <div class="chart-container">
                                    <canvas id="trendsChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Data Tables -->
                    <div class="tables-section">
                        <div class="table-row">
                            <div class="table-card">
                                <div class="table-header">
                                    <h3>Crime Hotspots</h3>
                                    <p>Areas with highest crime reports</p>
                                </div>
                                <div class="table-container">
                                    <table class="data-table">
                                        <thead>
                                            <tr>
                                                <th>Area</th>
                                                <th>City</th>
                                                <th>Crime Count</th>
                                                <th>Risk Level</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($location_crimes as $index => $location): 
                                                $risk_level = $location['crime_count'] > 5 ? 'High' : ($location['crime_count'] > 2 ? 'Medium' : 'Low');
                                                $risk_class = strtolower($risk_level);
                                            ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($location['AreaName']); ?></td>
                                                <td><?php echo htmlspecialchars($location['City']); ?></td>
                                                <td><strong><?php echo number_format($location['crime_count']); ?></strong></td>
                                                <td>
                                                    <span class="risk-badge risk-<?php echo $risk_class; ?>">
                                                        <?php echo $risk_level; ?>
                                                    </span>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="table-card">
                                <div class="table-header">
                                    <h3>Crime Categories</h3>
                                    <p>Breakdown by crime type</p>
                                </div>
                                <div class="table-container">
                                    <table class="data-table">
                                        <thead>
                                            <tr>
                                                <th>Crime Type</th>
                                                <th>Count</th>
                                                <th>Percentage</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $total_crimes = array_sum(array_column($crime_by_category, 'count'));
                                            foreach ($crime_by_category as $crime): 
                                                $percentage = $total_crimes > 0 ? round(($crime['count'] / $total_crimes) * 100, 1) : 0;
                                            ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($crime['Name']); ?></td>
                                                <td><strong><?php echo number_format($crime['count']); ?></strong></td>
                                                <td>
                                                    <div class="percentage-bar">
                                                        <div class="percentage-fill" style="width: <?php echo $percentage; ?>%"></div>
                                                        <span><?php echo $percentage; ?>%</span>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Summary Report -->
                    <div class="summary-section">
                        <div class="summary-card">
                            <div class="summary-header">
                                <h3>Executive Summary</h3>
                                <p>Crime analysis for <?php echo date('M d', strtotime($start_date)) . ' - ' . date('M d, Y', strtotime($end_date)); ?></p>
                            </div>
                            <div class="summary-content">
                                <div class="summary-item">
                                    <h4>Crime Volume</h4>
                                    <p>A total of <strong><?php echo number_format($resolution_data['total_firs']); ?> FIRs</strong> were filed during this period, with <strong><?php echo $resolution_rate; ?>%</strong> resolution rate.</p>
                                </div>
                                
                                <div class="summary-item">
                                    <h4>Most Common Crimes</h4>
                                    <p>
                                        <?php if (!empty($crime_by_category)): ?>
                                        <strong><?php echo htmlspecialchars($crime_by_category[0]['Name']); ?></strong> leads with <?php echo $crime_by_category[0]['count']; ?> reports, 
                                        followed by <?php echo !empty($crime_by_category[1]) ? htmlspecialchars($crime_by_category[1]['Name']) . ' (' . $crime_by_category[1]['count'] . ')' : 'other crimes'; ?>.
                                        <?php else: ?>
                                        No crime data available for this period.
                                        <?php endif; ?>
                                    </p>
                                </div>
                                
                                <div class="summary-item">
                                    <h4>Geographic Distribution</h4>
                                    <p>
                                        <?php if (!empty($location_crimes)): ?>
                                        <strong><?php echo htmlspecialchars($location_crimes[0]['AreaName']) . ', ' . htmlspecialchars($location_crimes[0]['City']); ?></strong> 
                                        reports the highest crime activity with <?php echo $location_crimes[0]['crime_count']; ?> incidents.
                                        <?php else: ?>
                                        Crime distribution data not available.
                                        <?php endif; ?>
                                    </p>
                                </div>
                                
                                <div class="summary-item">
                                    <h4>Recommendations</h4>
                                    <ul>
                                        <li>Increase patrol frequency in high-crime areas</li>
                                        <li>Focus resources on <?php echo !empty($crime_by_category) ? strtolower($crime_by_category[0]['Name']) : 'common crime types'; ?> prevention</li>
                                        <li>Improve response time for high-priority cases</li>
                                        <li>Community outreach programs in affected areas</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <div class="footer-logo">
                        <i class="fas fa-shield-alt"></i>
                        <h3>Crime Management System</h3>
                    </div>
                    <p>Serving the people of Pakistan with modern, efficient crime reporting and management.</p>
                </div>
                
                <div class="footer-section">
                    <h4>Analytics</h4>
                    <ul>
                        <li><a href="#crime-trends">Crime Trends</a></li>
                        <li><a href="#geographic-analysis">Geographic Analysis</a></li>
                        <li><a href="#performance-metrics">Performance Metrics</a></li>
                        <li><a href="#predictive-analysis">Predictive Analysis</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>System Info</h4>
                    <div class="system-info">
                        <div class="info-item">
                            <i class="fas fa-chart-bar"></i>
                            <span>Real-time Analytics</span>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-database"></i>
                            <span>MySQL Database</span>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-shield-alt"></i>
                            <span>Secure Reports</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; 2024 Pakistan Crime Management System. Crime analytics and reporting portal.</p>
            </div>
        </div>
    </footer>

    <script src="script.js"></script>
    <script>
    // Chart.js configurations
    const pakistanColors = {
        green: '#2D7D32',
        navy: '#1565C0',
        red: '#D32F2F',
        orange: '#FF9800',
        yellow: '#FFC107',
        purple: '#7B1FA2',
        teal: '#00695C',
        pink: '#C2185B'
    };

    // Crime by Category Chart
    const categoryData = <?php echo json_encode($crime_by_category); ?>;
    const categoryLabels = categoryData.map(item => item.Name);
    const categoryCounts = categoryData.map(item => parseInt(item.count));

    new Chart(document.getElementById('categoryChart'), {
        type: 'doughnut',
        data: {
            labels: categoryLabels,
            datasets: [{
                data: categoryCounts,
                backgroundColor: [
                    pakistanColors.green,
                    pakistanColors.navy,
                    pakistanColors.red,
                    pakistanColors.orange,
                    pakistanColors.yellow,
                    pakistanColors.purple,
                    pakistanColors.teal,
                    pakistanColors.pink
                ]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    // FIR Status Chart
    const statusData = <?php echo json_encode($status_distribution); ?>;
    const statusLabels = Object.keys(statusData).map(status => status.replace('_', ' ').toUpperCase());
    const statusCounts = Object.values(statusData);

    new Chart(document.getElementById('statusChart'), {
        type: 'pie',
        data: {
            labels: statusLabels,
            datasets: [{
                data: statusCounts,
                backgroundColor: [
                    pakistanColors.navy,
                    pakistanColors.orange,
                    pakistanColors.purple,
                    pakistanColors.green,
                    '#757575'
                ]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    // Monthly Trends Chart
    const trendsData = <?php echo json_encode($monthly_trends); ?>;
    const trendLabels = trendsData.map(item => {
        const date = new Date(item.month + '-01');
        return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
    });
    const trendCounts = trendsData.map(item => parseInt(item.count));

    new Chart(document.getElementById('trendsChart'), {
        type: 'line',
        data: {
            labels: trendLabels,
            datasets: [{
                label: 'FIRs Filed',
                data: trendCounts,
                borderColor: pakistanColors.green,
                backgroundColor: pakistanColors.green + '20',
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Export and Print functions
    function exportReport() {
        // In a real implementation, this would generate a PDF or CSV
        alert('Export functionality would be implemented here.');
    }

    function printReport() {
        window.print();
    }
    </script>
</body>
</html>