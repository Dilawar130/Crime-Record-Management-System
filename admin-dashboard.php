<?php
session_start();
include 'database.php';

// Check if user is logged in as police with admin or high-level access
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'police' || !in_array($_SESSION['user_role'], ['admin', 'ig', 'sp'])) {
    header('Location: login.php?type=police');
    exit;
}

// Get comprehensive statistics
$stmt = $pdo->query("SELECT COUNT(*) as total_firs FROM fir");
$total_firs = $stmt->fetch()['total_firs'];

$stmt = $pdo->query("SELECT COUNT(*) as total_residents FROM resident");
$total_residents = $stmt->fetch()['total_residents'];

$stmt = $pdo->query("SELECT COUNT(*) as total_officers FROM user");
$total_officers = $stmt->fetch()['total_officers'];

$stmt = $pdo->query("SELECT COUNT(*) as total_stations FROM police_station");
$total_stations = $stmt->fetch()['total_stations'];

// Get FIR status breakdown
$stmt = $pdo->query("SELECT Status, COUNT(*) as count FROM fir GROUP BY Status");
$fir_status = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Get recent FIRs
$stmt = $pdo->query("SELECT f.*, r.Name as ResidentName, ps.Name as StationName FROM fir f LEFT JOIN resident r ON f.FiledBy = r.ResidentID LEFT JOIN police_station ps ON f.PoliceStationID = ps.StationID ORDER BY f.created_at DESC LIMIT 10");
$recent_firs = $stmt->fetchAll();

// Get monthly FIR trends (last 6 months)
$stmt = $pdo->query("SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count FROM fir WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH) GROUP BY DATE_FORMAT(created_at, '%Y-%m') ORDER BY month");
$monthly_trends = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Pakistan Crime Management System</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+Pro:wght@300;400;600;700&display=swap" rel="stylesheet">
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
                    <a href="admin-dashboard.php" class="nav-link active">Admin Panel</a>
                    <a href="manage-firs.php" class="nav-link">Manage FIRs</a>
                    <a href="reports.php" class="nav-link">Reports</a>
                    <a href="logout.php" class="nav-link">Logout</a>
                </nav>
            </div>
        </div>
    </header>

    <main class="main">
        <section class="section active">
            <div class="container">
                <div class="admin-dashboard">
                    <div class="dashboard-header">
                        <div>
                            <h2>Admin Dashboard</h2>
                            <p>System Overview and Management - <?php echo ucfirst($_SESSION['user_role']); ?> Access</p>
                        </div>
                        <div class="header-actions">
                            <span class="current-user">
                                <i class="fas fa-user-shield"></i>
                                <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                            </span>
                        </div>
                    </div>

                    <!-- Key Metrics -->
                    <div class="metrics-grid">
                        <div class="metric-card primary">
                            <div class="metric-icon">
                                <i class="fas fa-file-alt"></i>
                            </div>
                            <div class="metric-content">
                                <h3><?php echo number_format($total_firs); ?></h3>
                                <p>Total FIRs</p>
                                <span class="metric-trend">
                                    <i class="fas fa-arrow-up"></i>
                                    +12% this month
                                </span>
                            </div>
                        </div>

                        <div class="metric-card success">
                            <div class="metric-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="metric-content">
                                <h3><?php echo number_format($total_residents); ?></h3>
                                <p>Registered Citizens</p>
                                <span class="metric-trend">
                                    <i class="fas fa-arrow-up"></i>
                                    +8% this month
                                </span>
                            </div>
                        </div>

                        <div class="metric-card info">
                            <div class="metric-icon">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <div class="metric-content">
                                <h3><?php echo number_format($total_officers); ?></h3>
                                <p>Police Officers</p>
                                <span class="metric-trend">
                                    <i class="fas fa-minus"></i>
                                    No change
                                </span>
                            </div>
                        </div>

                        <div class="metric-card warning">
                            <div class="metric-icon">
                                <i class="fas fa-building"></i>
                            </div>
                            <div class="metric-content">
                                <h3><?php echo number_format($total_stations); ?></h3>
                                <p>Police Stations</p>
                                <span class="metric-trend">
                                    <i class="fas fa-check"></i>
                                    All active
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- FIR Status Overview -->
                    <div class="status-overview">
                        <div class="section-header">
                            <h3>FIR Status Overview</h3>
                            <p>Current distribution of FIR statuses across the system</p>
                        </div>
                        
                        <div class="status-grid">
                            <?php
                            $status_config = [
                                'submitted' => ['icon' => 'fa-clock', 'color' => 'blue', 'label' => 'Submitted'],
                                'under_review' => ['icon' => 'fa-eye', 'color' => 'orange', 'label' => 'Under Review'],
                                'investigating' => ['icon' => 'fa-search', 'color' => 'purple', 'label' => 'Investigating'],
                                'resolved' => ['icon' => 'fa-check-circle', 'color' => 'green', 'label' => 'Resolved'],
                                'closed' => ['icon' => 'fa-times-circle', 'color' => 'gray', 'label' => 'Closed']
                            ];
                            
                            foreach ($status_config as $status => $config):
                                $count = $fir_status[$status] ?? 0;
                                $percentage = $total_firs > 0 ? round(($count / $total_firs) * 100, 1) : 0;
                            ?>
                            <div class="status-card <?php echo $config['color']; ?>">
                                <div class="status-icon">
                                    <i class="fas <?php echo $config['icon']; ?>"></i>
                                </div>
                                <div class="status-info">
                                    <h4><?php echo number_format($count); ?></h4>
                                    <p><?php echo $config['label']; ?></p>
                                    <span class="percentage"><?php echo $percentage; ?>%</span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Recent FIRs and Quick Actions -->
                    <div class="dashboard-content">
                        <div class="content-left">
                            <div class="recent-activity">
                                <div class="section-header">
                                    <h3>Recent FIRs</h3>
                                    <a href="manage-firs.php" class="btn btn-outline btn-sm">View All</a>
                                </div>
                                
                                <div class="activity-list">
                                    <?php foreach ($recent_firs as $fir): ?>
                                    <div class="activity-item">
                                        <div class="activity-icon">
                                            <i class="fas fa-file-alt"></i>
                                        </div>
                                        <div class="activity-content">
                                            <h4><?php echo htmlspecialchars($fir['Title']); ?></h4>
                                            <p>Filed by: <?php echo htmlspecialchars($fir['ResidentName']); ?></p>
                                            <span class="activity-time"><?php echo date('M d, Y g:i A', strtotime($fir['created_at'])); ?></span>
                                        </div>
                                        <div class="activity-status">
                                            <span class="status-badge status-<?php echo $fir['Status']; ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $fir['Status'])); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <div class="content-right">
                            <div class="quick-actions">
                                <div class="section-header">
                                    <h3>Quick Actions</h3>
                                </div>
                                
                                <div class="action-list">
                                    <a href="manage-firs.php" class="action-item">
                                        <div class="action-icon primary">
                                            <i class="fas fa-tasks"></i>
                                        </div>
                                        <div class="action-content">
                                            <h4>Manage FIRs</h4>
                                            <p>Review and update FIR status</p>
                                        </div>
                                    </a>

                                    <a href="reports.php" class="action-item">
                                        <div class="action-icon success">
                                            <i class="fas fa-chart-bar"></i>
                                        </div>
                                        <div class="action-content">
                                            <h4>Generate Reports</h4>
                                            <p>Crime statistics and analytics</p>
                                        </div>
                                    </a>

                                    <a href="manage-users.php" class="action-item">
                                        <div class="action-icon info">
                                            <i class="fas fa-users-cog"></i>
                                        </div>
                                        <div class="action-content">
                                            <h4>User Management</h4>
                                            <p>Manage officers and residents</p>
                                        </div>
                                    </a>

                                    <a href="system-settings.php" class="action-item">
                                        <div class="action-icon warning">
                                            <i class="fas fa-cog"></i>
                                        </div>
                                        <div class="action-content">
                                            <h4>System Settings</h4>
                                            <p>Configure parameters</p>
                                        </div>
                                    </a>
                                </div>
                            </div>

                            <!-- System Status -->
                            <div class="system-status">
                                <div class="section-header">
                                    <h3>Status</h3>
                                </div>
                                
                                <div class="status-items">
                                    <div class="status-item online">
                                        <i class="fas fa-circle"></i>
                                        <span>Database: Online</span>
                                    </div>
                                    <div class="status-item online">
                                        <i class="fas fa-circle"></i>
                                        <span>Web Server: Active</span>
                                    </div>
                                    <div class="status-item online">
                                        <i class="fas fa-circle"></i>
                                        <span>File System: Healthy</span>
                                    </div>
                                    <div class="status-item warning">
                                        <i class="fas fa-circle"></i>
                                        <span>Backup: Due Tomorrow</span>
                                    </div>
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
                    <h4>Admin Panel</h4>
                    <ul>
                        <li><a href="manage-firs.php">Manage FIRs</a></li>
                        <li><a href="reports.php">Analytics & Reports</a></li>
                        <li><a href="manage-users.php">User Management</a></li>
                        <li><a href="system-settings.php">System Settings</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>Info</h4>
                    <div class="system-info">
                        <div class="info-item">
                            <i class="fas fa-database"></i>
                            <span>Your Data is Save</span>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-server"></i>
                            <span>ISlAMABAD</span>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-shield-alt"></i>
                            <span>Admin Access</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; 2024 Pakistan Crime Management System. Admin Panel for authorized personnel only.</p>
            </div>
        </div>
    </footer>

    <script src="script.js"></script>
</body>
</html>