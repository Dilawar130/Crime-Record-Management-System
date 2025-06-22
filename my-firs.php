<?php
session_start();
include 'database.php';

// Check if user is logged in as resident
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'resident') {
    header('Location: login.php?type=public');
    exit;
}

// Get all FIRs for the current user
$stmt = $pdo->prepare("SELECT f.*, cc.Name as CategoryName, l.AreaName, l.City FROM fir f LEFT JOIN crime_information ci ON f.FIRID = ci.FIRID LEFT JOIN crime_category cc ON ci.CategoryID = cc.CategoryID LEFT JOIN location l ON ci.LocationID = l.LocationID WHERE f.FiledBy = ? ORDER BY f.created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$firs = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My FIRs - Pakistan Crime Management System</title>
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
                    <a href="fir-form.php" class="nav-link">FIR Online</a>
                    <a href="my-firs.php" class="nav-link active">My FIRs</a>
                    <a href="track-status.php" class="nav-link">Track Status</a>
                    <a href="logout.php" class="nav-link">Logout</a>
                </nav>
            </div>
        </div>
    </header>

    <main class="main">
        <section class="section active">
            <div class="container">
                <div class="my-firs-container">
                    <div class="page-header">
                        <div>
                            <h2>My FIRs / میری ایف آئی آرز</h2>
                            <p>View and track all your filed FIR reports</p>
                        </div>
                        <a href="fir-form.php" class="btn btn-danger">
                            <i class="fas fa-plus"></i>
                            File New FIR
                        </a>
                    </div>

                    <?php if (empty($firs)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <h3>No FIRs Filed Yet</h3>
                        <p>You haven't filed any FIR reports yet. Click the button below to file your first FIR.</p>
                        <a href="fir-form.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i>
                            File Your First FIR
                        </a>
                    </div>
                    <?php else: ?>
                    
                    <div class="firs-grid">
                        <?php foreach ($firs as $fir): ?>
                        <div class="fir-card">
                            <div class="fir-header">
                                <div class="fir-reference">
                                    <i class="fas fa-file-alt"></i>
                                    <span><?php echo htmlspecialchars($fir['ReferenceNumber']); ?></span>
                                </div>
                                <div class="fir-status">
                                    <span class="status-badge status-<?php echo $fir['Status']; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $fir['Status'])); ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="fir-body">
                                <h3><?php echo htmlspecialchars($fir['Title']); ?></h3>
                                <p class="fir-description"><?php echo htmlspecialchars(substr($fir['Description'], 0, 150)) . (strlen($fir['Description']) > 150 ? '...' : ''); ?></p>
                                
                                <div class="fir-meta">
                                    <div class="meta-item">
                                        <i class="fas fa-calendar"></i>
                                        <span><?php echo date('M d, Y', strtotime($fir['Date'])); ?></span>
                                    </div>
                                    
                                    <?php if ($fir['CategoryName']): ?>
                                    <div class="meta-item">
                                        <i class="fas fa-tag"></i>
                                        <span><?php echo htmlspecialchars($fir['CategoryName']); ?></span>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($fir['AreaName']): ?>
                                    <div class="meta-item">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <span><?php echo htmlspecialchars($fir['AreaName'] . ', ' . $fir['City']); ?></span>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="meta-item">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        <span class="priority-badge priority-<?php echo $fir['Priority']; ?>">
                                            <?php echo ucfirst($fir['Priority']); ?> Priority
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="fir-footer">
                                <div class="fir-dates">
                                    <small>Filed: <?php echo date('M d, Y g:i A', strtotime($fir['created_at'])); ?></small>
                                    <?php if ($fir['updated_at'] != $fir['created_at']): ?>
                                    <small>Updated: <?php echo date('M d, Y g:i A', strtotime($fir['updated_at'])); ?></small>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="fir-actions">
                                    <form method="POST" action="track-status.php" style="display: inline;">
                                        <input type="hidden" name="reference_number" value="<?php echo htmlspecialchars($fir['ReferenceNumber']); ?>">
                                        <button type="submit" class="btn btn-outline btn-sm">
                                            <i class="fas fa-search"></i>
                                            Track Status
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Summary Statistics -->
                    <div class="firs-summary">
                        <h3>Summary Statistics</h3>
                        <div class="summary-grid">
                            <?php
                            $status_counts = [];
                            foreach ($firs as $fir) {
                                $status = $fir['Status'];
                                $status_counts[$status] = ($status_counts[$status] ?? 0) + 1;
                            }
                            
                            $status_labels = [
                                'submitted' => 'Submitted',
                                'under_review' => 'Under Review',
                                'investigating' => 'Investigating',
                                'resolved' => 'Resolved',
                                'closed' => 'Closed'
                            ];
                            
                            foreach ($status_labels as $status => $label):
                                $count = $status_counts[$status] ?? 0;
                                if ($count > 0):
                            ?>
                            <div class="summary-item">
                                <div class="summary-count"><?php echo $count; ?></div>
                                <div class="summary-label"><?php echo $label; ?></div>
                            </div>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                        </div>
                    </div>
                    
                    <?php endif; ?>
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
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="#">Emergency Numbers</a></li>
                        <li><a href="#">Police Stations</a></li>
                        <li><a href="#">Legal Resources</a></li>
                        <li><a href="#">Citizen Rights</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>System Info</h4>
                    <div class="system-info">
                        <div class="info-item">
                            <i class="fas fa-database"></i>
                            <span>MySQL Database</span>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-server"></i>
                            <span>XAMPP Compatible</span>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-mobile-alt"></i>
                            <span>Mobile Responsive</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; 2024 Pakistan Crime Management System. Optimized for XAMPP with MySQL database.</p>
            </div>
        </div>
    </footer>
</body>
</html>