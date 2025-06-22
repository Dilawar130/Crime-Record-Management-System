<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pakistani Crime Management System - FIR Online | پاکستان کرائم مینجمنٹ سسٹم</title>
    <meta name="description" content="Online FIR filing system for Pakistan Police. File crime reports using CNIC, track status, and manage cases. Available 24/7 for community safety.">
    <link rel="stylesheet" href="styles.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+Pro:wght@300;400;600;700&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
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
                    <a href="index.php" class="nav-link active">Dashboard</a>
                    <a href="fir-form.php" class="nav-link">FIR Online</a>
                    <a href="track-status.php" class="nav-link">Track Status</a>
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <a href="logout.php" class="nav-link">Logout</a>
                    <?php endif; ?>
                </nav>
                <button class="mobile-menu-btn" id="mobileMenuBtn">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>
    </header>

    <!-- Mobile Menu -->
    <div class="mobile-menu" id="mobileMenu">
        <a href="index.php" class="mobile-nav-link">Dashboard</a>
        <a href="fir-form.php" class="mobile-nav-link">FIR Online</a>
        <a href="track-status.php" class="mobile-nav-link">Track Status</a>
        <?php if(isset($_SESSION['user_id'])): ?>
            <a href="logout.php" class="mobile-nav-link">Logout</a>
        <?php endif; ?>
    </div>

    <!-- Main Content -->
    <main class="main">
        <?php if(!isset($_SESSION['user_id'])): ?>
        <!-- Welcome/Login Section -->
        <section id="welcome" class="section active">
            <div class="container">
                <div class="welcome-container">
                    <div class="welcome-header">
                        <h2>خوش آمدید - Welcome to Crime Management System</h2>
                        <p>Please select your role to continue | اپنا کردار منتخب کریں</p>
                        <div class="migration-notice">
                            <div class="notice-icon">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <div class="notice-content">
                                <strong>Migration Notice:</strong> System has been successfully migrated from MySQL to PostgreSQL for better performance and compatibility with modern cloud platforms.
                            </div>
                        </div>
                    </div>
                    
                    <div class="role-selection">
                        <div class="role-card" onclick="location.href='login.php?type=public'">
                            <div class="role-icon civilian">
                                <i class="fas fa-user"></i>
                            </div>
                            <h3>Public / عوام</h3>
                            <p>File FIR online using CNIC<br>شناختی کارڈ سے ایف آئی آر داخل کریں</p>
                            <button class="btn btn-primary">Continue as Public</button>
                        </div>
                        
                        <div class="role-card" onclick="location.href='login.php?type=police'">
                            <div class="role-icon police">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <h3>Police Officer / پولیس افسر</h3>
                            <p>Manage FIRs, cases and investigations<br>مقدمات اور تحقیقات کا انتظام</p>
                            <button class="btn btn-navy">پولیس لاگ ان</button>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <?php else: ?>
        <!-- Dashboard Section -->
        <?php 
        include 'database.php';
        
        if($_SESSION['user_type'] == 'resident'):
            // Get resident data
            $stmt = $pdo->prepare("SELECT r.*, COUNT(f.FIRID) as total_firs FROM resident r LEFT JOIN fir f ON r.ResidentID = f.FiledBy WHERE r.ResidentID = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $resident = $stmt->fetch();
            
            // Get FIR statistics
            $stmt = $pdo->prepare("SELECT Status, COUNT(*) as count FROM fir WHERE FiledBy = ? GROUP BY Status");
            $stmt->execute([$_SESSION['user_id']]);
            $stats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            
            // Get recent FIRs
            $stmt = $pdo->prepare("SELECT f.*, cc.Name as CategoryName FROM fir f LEFT JOIN crime_information ci ON f.FIRID = ci.FIRID LEFT JOIN crime_category cc ON ci.CategoryID = cc.CategoryID WHERE f.FiledBy = ? ORDER BY f.created_at DESC LIMIT 5");
            $stmt->execute([$_SESSION['user_id']]);
            $recent_firs = $stmt->fetchAll();
        ?>
        <section id="civilian-dashboard" class="section active">
            <div class="container">
                <div class="dashboard-header">
                    <div>
                        <h2>خوش آمدید، <span id="civilianName"><?php echo htmlspecialchars($resident['Name']); ?></span></h2>
                        <p>Manage your FIR reports and track their progress</p>
                    </div>
                    <a href="logout.php" class="btn btn-outline">
                        <i class="fas fa-sign-out-alt"></i>
                        Logout / لاگ آؤٹ
                    </a>
                </div>

                <!-- Quick Actions for Public -->
                <div class="quick-actions">
                    <h3>Quick Actions / فوری اقدامات</h3>
                    <div class="action-buttons">
                        <a href="fir-form.php" class="btn btn-danger">
                            <i class="fas fa-plus"></i>
                            File New FIR / نئی ایف آئی آر
                        </a>
                        <a href="my-firs.php" class="btn btn-primary">
                            <i class="fas fa-list"></i>
                            My FIRs / میری ایف آئی آرز
                        </a>
                        <a href="track-status.php" class="btn btn-navy">
                            <i class="fas fa-search"></i>
                            Track Status / حالت معلوم کریں
                        </a>
                    </div>
                </div>

                <!-- My FIRs Summary -->
                <div class="reports-summary">
                    <h3>My FIR Summary / میری ایف آئی آر کا خلاصہ</h3>
                    <div class="summary-grid">
                        <div class="summary-card">
                            <div class="summary-icon submitted">
                                <i class="fas fa-file-alt"></i>
                            </div>
                            <div class="summary-content">
                                <span class="summary-number"><?php echo $resident['total_firs'] ?? 0; ?></span>
                                <span class="summary-label">Total FIRs</span>
                            </div>
                        </div>
                        
                        <div class="summary-card">
                            <div class="summary-icon under-review">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="summary-content">
                                <span class="summary-number"><?php echo $stats['under_review'] ?? 0; ?></span>
                                <span class="summary-label">Under Review</span>
                            </div>
                        </div>
                        
                        <div class="summary-card">
                            <div class="summary-icon investigating">
                                <i class="fas fa-search"></i>
                            </div>
                            <div class="summary-content">
                                <span class="summary-number"><?php echo $stats['investigating'] ?? 0; ?></span>
                                <span class="summary-label">Investigating</span>
                            </div>
                        </div>
                        
                        <div class="summary-card">
                            <div class="summary-icon resolved">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="summary-content">
                                <span class="summary-number"><?php echo $stats['resolved'] ?? 0; ?></span>
                                <span class="summary-label">Resolved</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent FIRs -->
                <?php if($recent_firs): ?>
                <div class="recent-firs">
                    <h3>Recent FIRs / حالیہ ایف آئی آرز</h3>
                    <div class="firs-table-container">
                        <table class="firs-table">
                            <thead>
                                <tr>
                                    <th>Reference #</th>
                                    <th>Title</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Priority</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($recent_firs as $fir): ?>
                                <tr>
                                    <td class="reference-number"><?php echo htmlspecialchars($fir['ReferenceNumber']); ?></td>
                                    <td><?php echo htmlspecialchars($fir['Title']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($fir['Date'])); ?></td>
                                    <td><span class="status-badge status-<?php echo $fir['Status']; ?>"><?php echo ucfirst(str_replace('_', ' ', $fir['Status'])); ?></span></td>
                                    <td><span class="priority-badge priority-<?php echo $fir['Priority']; ?>"><?php echo ucfirst($fir['Priority']); ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </section>
        <?php elseif($_SESSION['user_type'] == 'police'): ?>
        <!-- Police Dashboard -->
        <section id="police-dashboard" class="section active">
            <div class="container">
                <div class="dashboard-header">
                    <div>
                        <h2>Welcome, Officer <span><?php echo htmlspecialchars($_SESSION['user_name']); ?></span></h2>
                        <p>Manage cases, investigations, and police operations</p>
                    </div>
                    <a href="logout.php" class="btn btn-outline">
                        <i class="fas fa-sign-out-alt"></i>
                        Logout / لاگ آؤٹ
                    </a>
                </div>

                <div class="police-stats">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <div class="stat-content">
                            <h3>0</h3>
                            <p>Active Cases</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-content">
                            <h3>0</h3>
                            <p>Pending FIRs</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-content">
                            <h3>0</h3>
                            <p>Resolved Today</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <div class="stat-content">
                            <h3>0</h3>
                            <p>Evidence Items</p>
                        </div>
                    </div>
                </div>

                <div class="police-content">
                    <div class="content-card">
                        <div class="card-header">
                            <h3>Police Dashboard</h3>
                        </div>
                        <div class="card-body">
                            <div class="text-center">
                                <i class="fas fa-shield-alt police-icon"></i>
                                <h4>Police Dashboard</h4>
                                <p>This is the police officer dashboard. Full police functionality including case management, evidence tracking, and investigation tools are ready to be implemented.</p>
                                <div class="info-box">
                                    <p><strong>Note:</strong> Your role: <?php echo ucfirst($_SESSION['user_role']); ?></p>
                                    <p>Police dashboard features are ready for implementation with the MySQL database backend.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <?php endif; ?>
        <?php endif; ?>
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
                            <i class="fas fa-cloud"></i>
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

    <script src="script.js"></script>
</body>
</html>