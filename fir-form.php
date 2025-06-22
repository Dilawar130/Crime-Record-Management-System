<?php
session_start();
include 'database.php';

// Check if user is logged in as resident
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'resident') {
    header('Location: login.php?type=public');
    exit;
}

$error = '';
$success = '';

// Get locations and categories for dropdowns
$stmt = $pdo->query("SELECT * FROM location ORDER BY City, AreaName");
$locations = $stmt->fetchAll();

$stmt = $pdo->query("SELECT * FROM crime_category ORDER BY Name");
$categories = $stmt->fetchAll();

$stmt = $pdo->query("SELECT * FROM police_station ORDER BY Name");
$police_stations = $stmt->fetchAll();

if ($_POST) {
    $title = $_POST['title'];
    $category_id = $_POST['category_id'];
    $incident_date = $_POST['incident_date'];
    $incident_time = $_POST['incident_time'];
    $location_id = $_POST['location_id'];
    $specific_location = $_POST['specific_location'];
    $description = $_POST['description'];
    $witnesses = $_POST['witnesses'];
    $suspect_info = $_POST['suspect_info'];
    
    try {
        $pdo->beginTransaction();
        
        // Generate reference number
        $year = date('Y');
        $random = sprintf('%04d', mt_rand(1, 9999));
        $reference_number = "FIR-{$year}-{$random}";
        
        // Check if reference number already exists
        $stmt = $pdo->prepare("SELECT FIRID FROM fir WHERE ReferenceNumber = ?");
        $stmt->execute([$reference_number]);
        while ($stmt->fetch()) {
            $random = sprintf('%04d', mt_rand(1, 9999));
            $reference_number = "FIR-{$year}-{$random}";
            $stmt->execute([$reference_number]);
        }
        
        // Get default police station (first one available)
        $default_station = $police_stations[0]['StationID'];
        
        // Insert FIR
        $stmt = $pdo->prepare("INSERT INTO fir (Title, Description, Date, FiledBy, PoliceStationID, ReferenceNumber) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $description, $incident_date, $_SESSION['user_id'], $default_station, $reference_number]);
        $fir_id = $pdo->lastInsertId();
        
        // Create detailed description for crime information
        $crime_description = $description . "\n\n";
        $crime_description .= "Specific Location: " . $specific_location . "\n";
        if ($incident_time) {
            $crime_description .= "Incident Time: " . $incident_time . "\n";
        }
        if ($witnesses) {
            $crime_description .= "Witnesses: " . $witnesses . "\n";
        }
        if ($suspect_info) {
            $crime_description .= "Suspect Information: " . $suspect_info . "\n";
        }
        
        // Insert crime information
        $incident_datetime = $incident_date . ' ' . ($incident_time ?: '00:00:00');
        $stmt = $pdo->prepare("INSERT INTO crime_information (CategoryID, Description, Date, LocationID, FIRID) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$category_id, $crime_description, $incident_datetime, $location_id, $fir_id]);
        
        $pdo->commit();
        $success = "FIR submitted successfully! Reference Number: " . $reference_number;
        
    } catch(Exception $e) {
        $pdo->rollback();
        $error = 'Failed to submit FIR. Please try again.';
    }
}

// Group locations by city
$locations_by_city = [];
foreach ($locations as $location) {
    $locations_by_city[$location['City']][] = $location;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File New FIR - Pakistan Crime Management System</title>
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
                    <a href="fir-form.php" class="nav-link active">FIR Online</a>
                    <a href="track-status.php" class="nav-link">Track Status</a>
                    <a href="logout.php" class="nav-link">Logout</a>
                </nav>
            </div>
        </div>
    </header>

    <main class="main">
        <section class="section active">
            <div class="container">
                <div class="fir-form-container">
                    <div class="form-header">
                        <div class="form-icon">
                            <i class="fas fa-file-plus"></i>
                        </div>
                        <h2>File New FIR / نئی ایف آئی آر درج کریں</h2>
                        <p>Submit a detailed crime report for investigation</p>
                    </div>

                    <?php if ($error): ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-triangle"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                    <div class="success-message">
                        <i class="fas fa-check-circle"></i>
                        <?php echo htmlspecialchars($success); ?>
                        <div class="success-actions">
                            <a href="index.php" class="btn btn-primary">Go to Dashboard</a>
                            <a href="track-status.php" class="btn btn-outline">Track Status</a>
                        </div>
                    </div>
                    <?php else: ?>

                    <form method="POST" class="fir-form">
                        <!-- Basic Information -->
                        <div class="form-section">
                            <h3>Basic Information / بنیادی معلومات</h3>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="title">FIR Title / ایف آئی آر کا عنوان *</label>
                                    <input type="text" id="title" name="title" required value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="category_id">Crime Category / جرم کی قسم *</label>
                                    <select id="category_id" name="category_id" required>
                                        <option value="">Select Category</option>
                                        <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['CategoryID']; ?>" <?php echo (($_POST['category_id'] ?? '') == $category['CategoryID']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($category['Name']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="incident_date">Incident Date / واقعہ کی تاریخ *</label>
                                    <input type="date" id="incident_date" name="incident_date" required value="<?php echo htmlspecialchars($_POST['incident_date'] ?? ''); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="incident_time">Incident Time / واقعہ کا وقت</label>
                                    <input type="time" id="incident_time" name="incident_time" value="<?php echo htmlspecialchars($_POST['incident_time'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>

                        <!-- Location Information -->
                        <div class="form-section">
                            <h3>Location Information / مقام کی تفصیلات</h3>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="city">City / شہر *</label>
                                    <select id="city" name="city" required onchange="updateAreas()">
                                        <option value="">Select City</option>
                                        <?php foreach (array_keys($locations_by_city) as $city): ?>
                                        <option value="<?php echo htmlspecialchars($city); ?>" <?php echo (($_POST['city'] ?? '') == $city) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($city); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="location_id">Area / علاقہ *</label>
                                    <select id="location_id" name="location_id" required>
                                        <option value="">Select Area</option>
                                        <?php 
                                        $selected_city = $_POST['city'] ?? '';
                                        if ($selected_city && isset($locations_by_city[$selected_city])) {
                                            foreach ($locations_by_city[$selected_city] as $location) {
                                                $selected = (($_POST['location_id'] ?? '') == $location['LocationID']) ? 'selected' : '';
                                                echo "<option value='{$location['LocationID']}' {$selected}>{$location['AreaName']}</option>";
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="specific_location">Specific Location / مخصوص مقام *</label>
                                <textarea id="specific_location" name="specific_location" rows="3" required placeholder="Provide detailed address or landmarks / تفصیلی پتہ یا نشانات فراہم کریں"><?php echo htmlspecialchars($_POST['specific_location'] ?? ''); ?></textarea>
                            </div>
                        </div>

                        <!-- Crime Details -->
                        <div class="form-section">
                            <h3>Crime Details / جرم کی تفصیلات</h3>
                            
                            <div class="form-group">
                                <label for="description">Detailed Description / تفصیلی تبصرہ *</label>
                                <textarea id="description" name="description" rows="6" required placeholder="Describe the incident in detail, including what happened, when, and any other relevant information / واقعہ کی تفصیل سے وضاحت کریں"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="witnesses">Witnesses / گواہ</label>
                                    <textarea id="witnesses" name="witnesses" rows="3" placeholder="Names and contact information of witnesses / گواہوں کے نام اور رابطے کی معلومات"><?php echo htmlspecialchars($_POST['witnesses'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <label for="suspect_info">Suspect Information / مشتبہ شخص کی معلومات</label>
                                    <textarea id="suspect_info" name="suspect_info" rows="3" placeholder="Any information about the suspect(s) / مشتبہ شخص(افراد) کے بارے میں کوئی معلومات"><?php echo htmlspecialchars($_POST['suspect_info'] ?? ''); ?></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Actions -->
                        <div class="form-actions">
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-file-plus"></i>
                                Submit FIR / ایف آئی آر جمع کریں
                            </button>
                            
                            <a href="index.php" class="btn btn-outline">
                                <i class="fas fa-arrow-left"></i>
                                Back to Dashboard / ڈیش بورڈ پر واپس
                            </a>
                        </div>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>

    <script>
    // Location data for JavaScript
    const locationsByCity = <?php echo json_encode($locations_by_city); ?>;
    
    function updateAreas() {
        const citySelect = document.getElementById('city');
        const areaSelect = document.getElementById('location_id');
        const selectedCity = citySelect.value;
        
        // Clear current options
        areaSelect.innerHTML = '<option value="">Select Area</option>';
        
        if (selectedCity && locationsByCity[selectedCity]) {
            locationsByCity[selectedCity].forEach(location => {
                const option = document.createElement('option');
                option.value = location.LocationID;
                option.textContent = location.AreaName;
                areaSelect.appendChild(option);
            });
        }
    }
    </script>
</body>
</html>