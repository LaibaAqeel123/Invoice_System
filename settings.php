<?php
require_once 'config.php';
requireLogin();

$success_message = '';
$error_message = '';

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $company_name = $_POST['company_name'];
    $company_email = $_POST['company_email'];
    $company_mobile = $_POST['company_mobile'];
    $company_website = $_POST['company_website'];
    $bank_name = $_POST['bank_name'];
    $account_number = $_POST['account_number'];
    $sort_code = $_POST['sort_code'];
    
    // Handle logo upload
    $logo_path = null;
    $current_logo = $_POST['current_logo'] ?? null;
    
    // Check if user wants to remove logo
    if (isset($_POST['remove_logo']) && $_POST['remove_logo'] == '1') {
        if ($current_logo && file_exists($current_logo)) {
            unlink($current_logo);
        }
        $logo_path = null;
        $success_message = 'Logo removed successfully!';
    } 
    // Check if new logo is uploaded
    elseif (isset($_FILES['logo']) && $_FILES['logo']['error'] == UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/logos/';
        
        // Create upload directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_tmp = $_FILES['logo']['tmp_name'];
        $file_name = $_FILES['logo']['name'];
        $file_size = $_FILES['logo']['size'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        // Validate file
        $allowed_extensions = array('jpg', 'jpeg', 'png', 'gif', 'svg');
        $max_size = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($file_ext, $allowed_extensions)) {
            $error_message = 'Invalid file type. Only JPG, PNG, GIF, and SVG are allowed.';
        } elseif ($file_size > $max_size) {
            $error_message = 'File is too large. Maximum size is 5MB.';
        } else {
            // Generate unique filename
            $new_filename = 'logo_' . time() . '.' . $file_ext;
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($file_tmp, $upload_path)) {
                $logo_path = $upload_path;
                
                // Delete old logo if exists
                if ($current_logo && file_exists($current_logo)) {
                    unlink($current_logo);
                }
                
                $success_message = 'Logo uploaded successfully!';
            } else {
                $error_message = 'Failed to upload logo.';
            }
        }
    } else {
        // Keep existing logo if no new upload or removal
        $logo_path = $current_logo;
    }
    
    // Update company settings only if no error occurred
    if (empty($error_message)) {
        $stmt = $conn->prepare("UPDATE company_settings SET logo_path=?, company_name=?, company_email=?, company_mobile=?, company_website=?, bank_name=?, account_number=?, sort_code=? WHERE id=1");
        $stmt->bind_param("ssssssss", $logo_path, $company_name, $company_email, $company_mobile, $company_website, $bank_name, $account_number, $sort_code);
        
        if ($stmt->execute()) {
            if (empty($success_message)) {
                $success_message = 'Settings updated successfully!';
            }
            $stmt->close();
            
            // Redirect to prevent form resubmission
            header('Location: settings.php?success=' . urlencode($success_message));
            exit();
        } else {
            $error_message = 'Failed to update settings.';
        }
    }
}

// Get current settings
$settings = $conn->query("SELECT * FROM company_settings WHERE id = 1")->fetch_assoc();

// Get success message from URL if exists
if (isset($_GET['success'])) {
    $success_message = $_GET['success'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Company Settings - Invoice Generator</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
        }
        
        .header {
            background: white;
            padding: 15px 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo-text {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .logo-icon {
            width: 35px;
            height: 35px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 20px;
        }
        
        .logo-name {
            font-size: 22px;
            font-weight: bold;
            color: #333;
        }
        
        .nav-links {
            display: flex;
            gap: 20px;
        }
        
        .nav-links a {
            color: #666;
            text-decoration: none;
            font-size: 14px;
            padding: 8px 16px;
            border-radius: 4px;
            transition: background 0.3s;
        }
        
        .nav-links a:hover,
        .nav-links a.active {
            background: #f0f0f0;
            color: #333;
        }
        
        .container {
            max-width: 1000px;
            margin: 30px auto;
            padding: 0 30px;
        }
        
        .page-title {
            font-size: 28px;
            color: #333;
            margin-bottom: 30px;
        }
        
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 12px 20px;
            border-radius: 4px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }
        
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 12px 20px;
            border-radius: 4px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }
        
        .card {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .section-title {
            font-size: 20px;
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
            font-size: 14px;
        }
        
        input[type="text"],
        input[type="email"],
        input[type="file"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        input[type="file"] {
            padding: 8px;
        }
        
        .help-text {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        
        .logo-preview-container {
            margin-top: 15px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 4px;
            text-align: center;
        }
        
        .logo-preview {
            max-width: 300px;
            max-height: 150px;
            border: 2px solid #ddd;
            border-radius: 4px;
            padding: 10px;
            background: white;
            margin: 15px auto;
            display: block;
        }
        
        .no-logo {
            padding: 40px;
            color: #999;
            font-style: italic;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: transform 0.2s;
            margin-right: 10px;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .btn-success {
            background: #4caf50;
            color: white;
        }
        
        .btn-danger {
            background: #f44336;
            color: white;
        }
        
        .info-box {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 4px;
            color: #1976d2;
            font-size: 14px;
            line-height: 1.6;
        }
        
        .logo-actions {
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo-text">
            <div class="logo-icon">IG</div>
            <div>
                <div class="logo-name">Invoice Generator</div>
            </div>
        </div>
        <div class="nav-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="customers.php">Customers</a>
            <a href="invoices.php">Invoices</a>
            <a href="settings.php" class="active">Settings</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <h1 class="page-title">Company Settings</h1>
        
        <?php if (!empty($success_message)): ?>
            <div class="success-message"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="" enctype="multipart/form-data">
            <input type="hidden" name="current_logo" value="<?php echo htmlspecialchars($settings['logo_path'] ?? ''); ?>">
            
            <!-- Logo Section -->
            <div class="card">
                <h2 class="section-title">Company Logo</h2>
                
                <div class="form-group">
                    <label for="logo">Upload Logo</label>
                    <input type="file" id="logo" name="logo" accept="image/*">
                    <div class="help-text">Supported formats: JPG, PNG, GIF, SVG (Max 5MB). This logo will appear on all your invoices.</div>
                </div>
                
                <?php if (!empty($settings['logo_path']) && file_exists($settings['logo_path'])): ?>
                    <div class="logo-preview-container">
                        <div><strong>Current Logo:</strong></div>
                        <img src="<?php echo htmlspecialchars($settings['logo_path']); ?>?v=<?php echo time(); ?>" alt="Company Logo" class="logo-preview">
                        <div class="logo-actions">
                            <button type="submit" name="remove_logo" value="1" class="btn btn-danger" 
                                    onclick="return confirm('Are you sure you want to remove the logo?')">
                                Remove Logo
                            </button>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="logo-preview-container">
                        <div class="no-logo">No logo uploaded yet. Upload a logo to display on your invoices.</div>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Company Information Section -->
            <div class="card">
                <h2 class="section-title">Company Information</h2>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="company_name">Company Name</label>
                        <input type="text" id="company_name" name="company_name" value="<?php echo htmlspecialchars($settings['company_name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="company_email">Email</label>
                        <input type="email" id="company_email" name="company_email" value="<?php echo htmlspecialchars($settings['company_email']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="company_mobile">Mobile</label>
                        <input type="text" id="company_mobile" name="company_mobile" value="<?php echo htmlspecialchars($settings['company_mobile']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="company_website">Website</label>
                        <input type="text" id="company_website" name="company_website" value="<?php echo htmlspecialchars($settings['company_website']); ?>" required>
                    </div>
                </div>
            </div>
            
            <!-- Bank Details Section -->
            <div class="card">
                <h2 class="section-title">Bank Details</h2>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="bank_name">Account Name</label>
                        <input type="text" id="bank_name" name="bank_name" value="<?php echo htmlspecialchars($settings['bank_name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="account_number">Account Number</label>
                        <input type="text" id="account_number" name="account_number" value="<?php echo htmlspecialchars($settings['account_number']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="sort_code">Sort Code</label>
                        <input type="text" id="sort_code" name="sort_code" value="<?php echo htmlspecialchars($settings['sort_code']); ?>" required placeholder="XX-XX-XX">
                    </div>
                </div>
            </div>
            
            <!-- Submit Button -->
            <div style="margin-top: 20px;">
                <button type="submit" class="btn btn-success">Save All Settings</button>
            </div>
        </form>
        
        <!-- Information Box -->
        <div class="card">
            <h2 class="section-title">Information</h2>
            <div class="info-box">
                <p><strong>Note:</strong> All information entered above will appear on your invoices.</p>
                <p style="margin-top: 10px;">• Upload your company logo to personalize your invoices</p>
                <p>• Make sure all company details are accurate</p>
                <p>• Bank details will be displayed at the bottom of each invoice</p>
            </div>
        </div>
    </div>
</body>
</html>