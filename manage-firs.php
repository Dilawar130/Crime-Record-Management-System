<?php
session_start();
include 'database.php';

// Check if user is logged in as police
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'police') {
    header('Location: login.php?type=police');
    exit;
}

$message = '';
$error = '';

// Handle status updates
if ($_POST && isset($_POST['update_status'])) {
    $fir_id = $_POST['fir_id'];
    $new_status = $_POST['new_status'];
    
    try {
        $stmt = $pdo->prepare("UPDATE fir SET Status = ?, updated_at = NOW() WHERE FIRID = ?");
        $stmt->execute([$new_status, $fir_id]);
        $message = "FIR status updated successfully.";
    } catch(Exception $e) {
        $error = "Failed to update FIR status.";
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$priority_filter = $_GET['priority'] ?? '';
$search = $_GET['search'] ?? '';

// Build query with filters
$where_conditions = [];
$params = [];

if ($status_filter) {
    $where_conditions[] = "f.Status = ?";
    $params[] = $status_filter;
}

if ($priority_filter) {
    $where_conditions[] = "f.Priority = ?";
    $params[] = $priority_filter;
}

if ($search) {
    $where_conditions[] = "(f.Title LIKE ? OR f.ReferenceNumber LIKE ? OR r.Name LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$where_clause = $where_conditions ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get FIRs with pagination
$page = $_GET['page'] ?? 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

$stmt = $pdo->prepare("
    SELECT f.*, r.Name as ResidentName, r.CNIC, ps.Name as StationName, 
           l.AreaName, l.City, cc.Name as CategoryName
    FROM fir f 
    LEFT JOIN resident r ON f.FiledBy = r.ResidentID 
    LEFT JOIN police_station ps ON f.PoliceStationID = ps.StationID
    LEFT JOIN crime_information ci ON f.FIRID = ci.FIRID
    LEFT JOIN location l ON ci.LocationID = l.LocationID
    LEFT JOIN crime_category cc ON ci.CategoryID = cc.CategoryID
    $where_clause
    ORDER BY f.created_at DESC
    LIMIT $per_page OFFSET $offset
");
$stmt->execute($params);
$firs = $stmt->fetchAll();

// Get total count for pagination
$count_stmt = $pdo->prepare("
    SELECT COUNT(*) as total
    FROM fir f 
    LEFT JOIN resident r ON f.FiledBy = r.ResidentID 
    LEFT JOIN crime_information ci ON f.FIRID = ci.FIRID
    $where_clause
");
$count_stmt->execute($params);
$total_firs = $count_stmt->fetch()['total'];
$total_pages = ceil($total_firs / $per_page);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage FIRs - Pakistan Crime Management System</title>
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
                    <a href="manage-firs.php" class="nav-link active">Manage FIRs</a>
                    <?php if(in_array($_SESSION['user_role'], ['admin', 'ig', 'sp'])): ?>
                    <a href="admin-dashboard.php" class="nav-link">Admin Panel</a>
                    <?php endif; ?>
                    <a href="reports.php" class="nav-link">Reports</a>
                    <a href="logout.php" class="nav-link">Logout</a>
                </nav>
            </div>
        </div>
    </header>

    <main class="main">
        <section class="section active">
            <div class="container">
                <div class="manage-firs">
                    <div class="page-header">
                        <div>
                            <h2>Manage FIRs</h2>
                            <p>Review, update and process First Information Reports</p>
                        </div>
                        <div class="header-stats">
                            <span class="stat-item">
                                <i class="fas fa-file-alt"></i>
                                Total: <?php echo number_format($total_firs); ?>
                            </span>
                        </div>
                    </div>

                    <?php if ($message): ?>
                    <div class="success-message">
                        <i class="fas fa-check-circle"></i>
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-triangle"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                    <?php endif; ?>

                    <!-- Filters -->
                    <div class="filters-section">
                        <form method="GET" class="filters-form">
                            <div class="filter-group">
                                <input type="text" name="search" placeholder="Search by title, reference number, or citizen name..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            
                            <div class="filter-group">
                                <select name="status">
                                    <option value="">All Statuses</option>
                                    <option value="submitted" <?php echo $status_filter === 'submitted' ? 'selected' : ''; ?>>Submitted</option>
                                    <option value="under_review" <?php echo $status_filter === 'under_review' ? 'selected' : ''; ?>>Under Review</option>
                                    <option value="investigating" <?php echo $status_filter === 'investigating' ? 'selected' : ''; ?>>Investigating</option>
                                    <option value="resolved" <?php echo $status_filter === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                                    <option value="closed" <?php echo $status_filter === 'closed' ? 'selected' : ''; ?>>Closed</option>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <select name="priority">
                                    <option value="">All Priorities</option>
                                    <option value="low" <?php echo $priority_filter === 'low' ? 'selected' : ''; ?>>Low</option>
                                    <option value="medium" <?php echo $priority_filter === 'medium' ? 'selected' : ''; ?>>Medium</option>
                                    <option value="high" <?php echo $priority_filter === 'high' ? 'selected' : ''; ?>>High</option>
                                    <option value="urgent" <?php echo $priority_filter === 'urgent' ? 'selected' : ''; ?>>Urgent</option>
                                </select>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i>
                                Filter
                            </button>
                            
                            <a href="manage-firs.php" class="btn btn-outline">
                                <i class="fas fa-times"></i>
                                Clear
                            </a>
                        </form>
                    </div>

                    <!-- FIRs Table -->
                    <div class="firs-table-section">
                        <?php if (empty($firs)): ?>
                        <div class="empty-state">
                            <div class="empty-icon">
                                <i class="fas fa-search"></i>
                            </div>
                            <h3>No FIRs Found</h3>
                            <p>No FIRs match your current filters. Try adjusting your search criteria.</p>
                        </div>
                        <?php else: ?>
                        
                        <div class="table-container">
                            <table class="firs-table">
                                <thead>
                                    <tr>
                                        <th>Reference #</th>
                                        <th>Title</th>
                                        <th>Filed By</th>
                                        <th>Category</th>
                                        <th>Location</th>
                                        <th>Priority</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($firs as $fir): ?>
                                    <tr>
                                        <td>
                                            <span class="reference-number"><?php echo htmlspecialchars($fir['ReferenceNumber']); ?></span>
                                        </td>
                                        <td>
                                            <div class="fir-title">
                                                <?php echo htmlspecialchars($fir['Title']); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="citizen-info">
                                                <strong><?php echo htmlspecialchars($fir['ResidentName']); ?></strong>
                                                <small><?php echo htmlspecialchars($fir['CNIC']); ?></small>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($fir['CategoryName'] ?? 'N/A'); ?></td>
                                        <td>
                                            <?php if ($fir['AreaName']): ?>
                                            <div class="location-info">
                                                <?php echo htmlspecialchars($fir['AreaName']); ?>
                                                <small><?php echo htmlspecialchars($fir['City']); ?></small>
                                            </div>
                                            <?php else: ?>
                                            N/A
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="priority-badge priority-<?php echo $fir['Priority']; ?>">
                                                <?php echo ucfirst($fir['Priority']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <form method="POST" class="status-form" style="display: inline;">
                                                <input type="hidden" name="fir_id" value="<?php echo $fir['FIRID']; ?>">
                                                <select name="new_status" onchange="this.form.submit()" class="status-select">
                                                    <option value="submitted" <?php echo $fir['Status'] === 'submitted' ? 'selected' : ''; ?>>Submitted</option>
                                                    <option value="under_review" <?php echo $fir['Status'] === 'under_review' ? 'selected' : ''; ?>>Under Review</option>
                                                    <option value="investigating" <?php echo $fir['Status'] === 'investigating' ? 'selected' : ''; ?>>Investigating</option>
                                                    <option value="resolved" <?php echo $fir['Status'] === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                                                    <option value="closed" <?php echo $fir['Status'] === 'closed' ? 'selected' : ''; ?>>Closed</option>
                                                </select>
                                                <input type="hidden" name="update_status" value="1">
                                            </form>
                                        </td>
                                        <td>
                                            <div class="date-info">
                                                <?php echo date('M d, Y', strtotime($fir['Date'])); ?>
                                                <small><?php echo date('g:i A', strtotime($fir['created_at'])); ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <button onclick="viewFIR(<?php echo $fir['FIRID']; ?>)" class="btn btn-sm btn-outline" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button onclick="printFIR(<?php echo $fir['FIRID']; ?>)" class="btn btn-sm btn-outline" title="Print">
                                                    <i class="fas fa-print"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>&status=<?php echo $status_filter; ?>&priority=<?php echo $priority_filter; ?>&search=<?php echo urlencode($search); ?>" class="btn btn-outline">
                                <i class="fas fa-chevron-left"></i>
                                Previous
                            </a>
                            <?php endif; ?>
                            
                            <span class="pagination-info">
                                Page <?php echo $page; ?> of <?php echo $total_pages; ?>
                                (<?php echo number_format($total_firs); ?> total FIRs)
                            </span>
                            
                            <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?>&status=<?php echo $status_filter; ?>&priority=<?php echo $priority_filter; ?>&search=<?php echo urlencode($search); ?>" class="btn btn-outline">
                                Next
                                <i class="fas fa-chevron-right"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- FIR Details Modal -->
    <div id="firModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>FIR Details</h3>
                <span class="modal-close">&times;</span>
            </div>
            <div class="modal-body" id="firDetails">
                <!-- FIR details will be loaded here -->
            </div>
        </div>
    </div>

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
                        <li><a href="manage-firs.php">Manage FIRs</a></li>
                        <li><a href="reports.php">Reports</a></li>
                        <li><a href="track-status.php">Track Status</a></li>
                        <li><a href="index.php">Dashboard</a></li>
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
                            <i class="fas fa-shield-alt"></i>
                            <span>Police Access</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; 2024 Pakistan Crime Management System. Police management portal.</p>
            </div>
        </div>
    </footer>

    <script src="script.js"></script>
    <script>
    // FIR Modal functionality
    function viewFIR(firId) {
        // In a real implementation, you would fetch FIR details via AJAX
        document.getElementById('firModal').style.display = 'block';
        document.getElementById('firDetails').innerHTML = '<p>Loading FIR details...</p>';
        
        // Simulate loading
        setTimeout(() => {
            document.getElementById('firDetails').innerHTML = `
                <p><strong>Reference Number:</strong> FIR-2024-${firId}</p>
                <p><strong>Status:</strong> Under Investigation</p>
                <p><strong>Priority:</strong> High</p>
                <p>Detailed FIR information would be displayed here...</p>
            `;
        }, 500);
    }

    function printFIR(firId) {
        // In a real implementation, you would open a print-friendly page
        window.open(`print-fir.php?id=${firId}`, '_blank');
    }

    // Modal close functionality
    document.querySelector('.modal-close').onclick = function() {
        document.getElementById('firModal').style.display = 'none';
    }

    window.onclick = function(event) {
        const modal = document.getElementById('firModal');
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }

    // Confirm status changes
    document.querySelectorAll('.status-select').forEach(select => {
        select.addEventListener('change', function(e) {
            if (!confirm('Are you sure you want to change the status of this FIR?')) {
                e.preventDefault();
                // Reset to original value
                this.selectedIndex = 0;
                return false;
            }
        });
    });
    </script>
</body>
</html>
