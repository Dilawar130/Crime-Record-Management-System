<?php
session_start();
include 'database.php';

$fir_data = null;
$error = '';

if ($_POST && $_POST['reference_number']) {
    $reference_number = $_POST['reference_number'];
    
    $stmt = $pdo->prepare("SELECT f.*, ps.Name as StationName, l.AreaName, l.City FROM fir f LEFT JOIN police_station ps ON f.PoliceStationID = ps.StationID LEFT JOIN location l ON ps.LocationID = l.LocationID WHERE f.ReferenceNumber = ?");
    $stmt->execute([$reference_number]);
    $fir_data = $stmt->fetch();
    
    if (!$fir_data) {
        $error = 'FIR not found. Please check the reference number and try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track FIR Status - Pakistan Crime Management System</title>
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
                    <a href="track-status.php" class="nav-link active">Track Status</a>
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <a href="logout.php" class="nav-link">Logout</a>
                    <?php endif; ?>
                </nav>
            </div>
        </div>
    </header>

    <main class="main">
        <section class="section active">
            <div class="container">
                <div class="track-status-container">
                    <div class="track-header">
                        <div class="track-icon">
                            <i class="fas fa-search"></i>
                        </div>
                        <h2>Track FIR Status / ایف آئی آر کی حالت معلوم کریں</h2>
                        <p>Enter your FIR reference number to check status</p>
                    </div>

                    <div class="track-form-card">
                        <?php if ($error): ?>
                        <div class="error-message">
                            <i class="fas fa-exclamation-triangle"></i>
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                        <?php endif; ?>

                        <form method="POST" class="track-form">
                            <div class="form-group">
                                <label for="reference_number">FIR Reference Number / ایف آئی آر ریفرنس نمبر</label>
                                <input type="text" id="reference_number" name="reference_number" placeholder="FIR-2024-0001" required value="<?php echo htmlspecialchars($_POST['reference_number'] ?? ''); ?>">
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i>
                                Track Status / حالت معلوم کریں
                            </button>
                        </form>
                    </div>

                    <?php if ($fir_data): ?>
                    <!-- FIR Details -->
                    <div class="fir-details-card">
                        <div class="card-header">
                            <div class="status-icon status-<?php echo $fir_data['Status']; ?>">
                                <?php
                                $status_icons = [
                                    'submitted' => 'fas fa-clock',
                                    'under_review' => 'fas fa-file-alt',
                                    'investigating' => 'fas fa-search',
                                    'resolved' => 'fas fa-check-circle',
                                    'closed' => 'fas fa-times-circle'
                                ];
                                $icon = $status_icons[$fir_data['Status']] ?? 'fas fa-clock';
                                ?>
                                <i class="<?php echo $icon; ?>"></i>
                            </div>
                            <h3>FIR Details / ایف آئی آر کی تفصیلات</h3>
                        </div>
                        
                        <div class="card-body">
                            <div class="details-grid">
                                <div class="detail-item">
                                    <label>Reference Number</label>
                                    <span class="reference-number"><?php echo htmlspecialchars($fir_data['ReferenceNumber']); ?></span>
                                </div>
                                
                                <div class="detail-item">
                                    <label>Status</label>
                                    <span class="status-badge status-<?php echo $fir_data['Status']; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $fir_data['Status'])); ?>
                                    </span>
                                </div>
                                
                                <div class="detail-item">
                                    <label>Title</label>
                                    <span><?php echo htmlspecialchars($fir_data['Title']); ?></span>
                                </div>
                                
                                <div class="detail-item">
                                    <label>Date Filed</label>
                                    <span><?php echo date('M d, Y', strtotime($fir_data['Date'])); ?></span>
                                </div>
                                
                                <div class="detail-item">
                                    <label>Police Station</label>
                                    <span><?php echo htmlspecialchars($fir_data['StationName'] ?? 'Not Assigned'); ?></span>
                                </div>
                                
                                <div class="detail-item">
                                    <label>Priority</label>
                                    <span class="priority-badge priority-<?php echo $fir_data['Priority']; ?>">
                                        <?php echo ucfirst($fir_data['Priority']); ?>
                                    </span>
                                </div>
                            </div>

                            <div class="description-section">
                                <label>Description</label>
                                <p><?php echo nl2br(htmlspecialchars($fir_data['Description'])); ?></p>
                            </div>

                            <div class="timeline-section">
                                <h4>Status Timeline</h4>
                                <div class="timeline">
                                    <div class="timeline-item completed">
                                        <div class="timeline-icon">
                                            <i class="fas fa-file-plus"></i>
                                        </div>
                                        <div class="timeline-content">
                                            <h5>FIR Submitted</h5>
                                            <p><?php echo date('M d, Y g:i A', strtotime($fir_data['created_at'])); ?></p>
                                        </div>
                                    </div>
                                    
                                    <?php if (in_array($fir_data['Status'], ['under_review', 'investigating', 'resolved', 'closed'])): ?>
                                    <div class="timeline-item completed">
                                        <div class="timeline-icon">
                                            <i class="fas fa-eye"></i>
                                        </div>
                                        <div class="timeline-content">
                                            <h5>Under Review</h5>
                                            <p>FIR is being reviewed by authorities</p>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if (in_array($fir_data['Status'], ['investigating', 'resolved', 'closed'])): ?>
                                    <div class="timeline-item completed">
                                        <div class="timeline-icon">
                                            <i class="fas fa-search"></i>
                                        </div>
                                        <div class="timeline-content">
                                            <h5>Investigation Started</h5>
                                            <p>Case assigned to investigating officer</p>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if (in_array($fir_data['Status'], ['resolved', 'closed'])): ?>
                                    <div class="timeline-item completed">
                                        <div class="timeline-icon">
                                            <i class="fas <?php echo $fir_data['Status'] == 'resolved' ? 'fa-check-circle' : 'fa-times-circle'; ?>"></i>
                                        </div>
                                        <div class="timeline-content">
                                            <h5><?php echo $fir_data['Status'] == 'resolved' ? 'Case Resolved' : 'Case Closed'; ?></h5>
                                            <p><?php echo date('M d, Y g:i A', strtotime($fir_data['updated_at'])); ?></p>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Help Section -->
                    <div class="help-section">
                        <div class="help-card">
                            <h3>Need Help? / مدد چاہیے؟</h3>
                            <div class="help-content">
                                <div class="help-item">
                                    <h4>Contact Information</h4>
                                    <ul>
                                        <li><i class="fas fa-phone"></i> Emergency: 15 (Police)</li>
                                        <li><i class="fas fa-phone"></i> General Inquiry: 1915</li>
                                        <li><i class="fas fa-envelope"></i> Email: info@police.gov.pk</li>
                                    </ul>
                                </div>
                                
                                <div class="help-item">
                                    <h4>Office Hours</h4>
                                    <ul>
                                        <li><i class="fas fa-clock"></i> Monday - Friday: 9:00 AM - 5:00 PM</li>
                                        <li><i class="fas fa-clock"></i> Saturday: 9:00 AM - 1:00 PM</li>
                                        <li><i class="fas fa-shield-alt"></i> Emergency services: 24/7</li>
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