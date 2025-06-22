<?php
session_start();
include 'database.php';

$error = '';
$success = '';

if ($_POST) {
    $name = $_POST['name'];
    $cnic = $_POST['cnic'];
    $address = $_POST['address'];
    $contact = $_POST['contact'];
    $email = $_POST['email'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    try {
        // Check if CNIC already exists
        $stmt = $pdo->prepare("SELECT ResidentID FROM resident WHERE CNIC = ?");
        $stmt->execute([$cnic]);
        if ($stmt->fetch()) {
            $error = 'CNIC already registered';
        } else {
            // Check if username already exists
            $stmt = $pdo->prepare("SELECT CredentialID FROM resident_credential WHERE Username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $error = 'Username already taken';
            } else {
                // Begin transaction
                $pdo->beginTransaction();
                
                // Insert resident
                $stmt = $pdo->prepare("INSERT INTO resident (Name, CNIC, Address, Contact, Email) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$name, $cnic, $address, $contact, $email]);
                $resident_id = $pdo->lastInsertId();
                
                // Insert credentials
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO resident_credential (ResidentID, Username, Password) VALUES (?, ?, ?)");
                $stmt->execute([$resident_id, $username, $hashed_password]);
                
                $pdo->commit();
                $success = 'Registration successful! You can now login.';
            }
        }
    } catch(Exception $e) {
        $pdo->rollback();
        $error = 'Registration failed. Please try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Public Registration - Pakistan Crime Management System</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+Pro:wght@300;400;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="register-page">
        <div class="register-container">
            <div class="form-card">
                <div class="card-header">
                    <i class="fas fa-user-plus"></i>
                    <div>
                        <h3>Public Registration / عوامی رجسٹریشن</h3>
                        <p>Register with CNIC to file FIR online</p>
                    </div>
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
                    <a href="login.php?type=public" class="btn btn-primary">Login Now</a>
                </div>
                <?php else: ?>
                
                <form method="POST" class="register-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">Full Name / مکمل نام *</label>
                            <input type="text" id="name" name="name" required value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="cnic">CNIC / شناختی کارڈ نمبر *</label>
                            <input type="text" id="cnic" name="cnic" placeholder="42101-1234567-1" required value="<?php echo htmlspecialchars($_POST['cnic'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="contact">Phone Number / فون نمبر *</label>
                            <input type="tel" id="contact" name="contact" placeholder="+92-300-1234567" required value="<?php echo htmlspecialchars($_POST['contact'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="email">Email / ای میل</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="address">Complete Address / مکمل پتہ *</label>
                        <textarea id="address" name="address" rows="2" required><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="username">Username / صارف نام *</label>
                            <input type="text" id="username" name="username" required value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="password">Password / پاس ورڈ *</label>
                            <input type="password" id="password" name="password" required>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-user-plus"></i>
                            Create Account / اکاؤنٹ بنائیں
                        </button>
                        <a href="login.php?type=public" class="btn btn-outline">
                            <i class="fas fa-arrow-left"></i>
                            Back to Login / لاگ ان پر واپس
                        </a>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>