<?php
require_once 'config.php';
requireLogin();

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_smtp') {
    $smtp_host = $_POST['smtp_host'];
    $smtp_port = intval($_POST['smtp_port']);
    $smtp_username = $_POST['smtp_username'];
    $smtp_password = $_POST['smtp_password'];
    $smtp_encryption = $_POST['smtp_encryption'];
    $from_email = $_POST['from_email'];
    $from_name = $_POST['from_name'];
    
    // Check if settings exist
    $existing = $conn->query("SELECT id FROM smtp_settings LIMIT 1")->fetch_assoc();
    
    if ($existing) {
        // Update existing
        $stmt = $conn->prepare("UPDATE smtp_settings SET smtp_host=?, smtp_port=?, smtp_username=?, smtp_password=?, smtp_encryption=?, from_email=?, from_name=? WHERE id=?");
        $stmt->bind_param("sisssssi", $smtp_host, $smtp_port, $smtp_username, $smtp_password, $smtp_encryption, $from_email, $from_name, $existing['id']);
    } else {
        // Insert new
        $stmt = $conn->prepare("INSERT INTO smtp_settings (smtp_host, smtp_port, smtp_username, smtp_password, smtp_encryption, from_email, from_name) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sisssss", $smtp_host, $smtp_port, $smtp_username, $smtp_password, $smtp_encryption, $from_email, $from_name);
    }
    
    $stmt->execute();
    $stmt->close();
    
    header('Location: smtp_settings.php?success=updated');
    exit();
}

// Handle test email
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'test_email') {
    $test_email = $_POST['test_email'];
    
    // Send test email
    require_once 'send_email.php';
    $result = sendTestEmail($test_email);
    
    if ($result['success']) {
        header('Location: smtp_settings.php?success=test_sent');
    } else {
        header('Location: smtp_settings.php?error=' . urlencode($result['message']));
    }
    exit();
}

// Get current SMTP settings
$smtp = $conn->query("SELECT * FROM smtp_settings LIMIT 1")->fetch_assoc();
if (!$smtp) {
    // Default values if no settings exist
    $smtp = [
        'smtp_host' => 'smtp.gmail.com',
        'smtp_port' => 587,
        'smtp_username' => '',
        'smtp_password' => '',
        'smtp_encryption' => 'tls',
        'from_email' => '',
        'from_name' => 'Invoice Generator'
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMTP Email Settings - Invoice Management System</title>
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
        }
        
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 12px 20px;
            border-radius: 4px;
            margin-bottom: 20px;
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
        input[type="password"],
        input[type="number"],
        select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
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
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .info-box {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 4px;
            color: #1976d2;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        
        .warning-box {
            background: #fff3cd;
            padding: 15px;
            border-radius: 4px;
            color: #856404;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        
        .help-text {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        
        code {
            background: #f5f5f5;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
            font-size: 13px;
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
            <a href="templates.php">Templates</a>
            <a href="settings.php">Settings</a>
            <a href="smtp_settings.php" class="active">Email</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <h1 class="page-title">SMTP Email Settings</h1>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="success-message">
                <?php 
                if ($_GET['success'] === 'updated') echo 'SMTP settings updated successfully!';
                if ($_GET['success'] === 'test_sent') echo 'Test email sent successfully! Check your inbox.';
                ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['error'])): ?>
            <div class="error-message">
                Error: <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <h2 class="section-title">Email Configuration</h2>
            
            <form method="POST" action="">
                <input type="hidden" name="action" value="update_smtp">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="smtp_host">SMTP Host</label>
                        <input type="text" id="smtp_host" name="smtp_host" value="<?php echo htmlspecialchars($smtp['smtp_host']); ?>" required>
                        <div class="help-text">Gmail: <code>smtp.gmail.com</code> | Outlook: <code>smtp-mail.outlook.com</code></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="smtp_port">SMTP Port</label>
                        <input type="number" id="smtp_port" name="smtp_port" value="<?php echo htmlspecialchars($smtp['smtp_port']); ?>" required>
                        <div class="help-text">TLS: <code>587</code> | SSL: <code>465</code></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="smtp_username">SMTP Username (Email)</label>
                        <input type="text" id="smtp_username" name="smtp_username" value="<?php echo htmlspecialchars($smtp['smtp_username']); ?>" required>
                        <div class="help-text">Your full email address</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="smtp_password">SMTP Password</label>
                        <input type="password" id="smtp_password" name="smtp_password" value="<?php echo htmlspecialchars($smtp['smtp_password']); ?>" required>
                        <div class="help-text">For Gmail, use App Password (16 characters)</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="smtp_encryption">Encryption</label>
                        <select id="smtp_encryption" name="smtp_encryption" required>
                            <option value="tls" <?php echo ($smtp['smtp_encryption'] === 'tls') ? 'selected' : ''; ?>>TLS</option>
                            <option value="ssl" <?php echo ($smtp['smtp_encryption'] === 'ssl') ? 'selected' : ''; ?>>SSL</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="from_email">From Email</label>
                        <input type="email" id="from_email" name="from_email" value="<?php echo htmlspecialchars($smtp['from_email']); ?>" required>
                        <div class="help-text">Email address invoices will be sent from</div>
                    </div>
                    
                    <div class="form-group full-width">
                        <label for="from_name">From Name</label>
                        <input type="text" id="from_name" name="from_name" value="<?php echo htmlspecialchars($smtp['from_name']); ?>" required>
                        <div class="help-text">Name that will appear as sender (e.g., "Invoice Generator")</div>
                    </div>
                </div>
                
                <div style="margin-top: 30px;">
                    <button type="submit" class="btn btn-success">Save SMTP Settings</button>
                </div>
            </form>
        </div>
        
        <div class="card">
            <h2 class="section-title">Test Email</h2>
            
            <form method="POST" action="">
                <input type="hidden" name="action" value="test_email">
                
                <div class="form-group">
                    <label for="test_email">Test Email Address</label>
                    <input type="email" id="test_email" name="test_email" placeholder="your-email@example.com" required>
                </div>
                
                <button type="submit" class="btn btn-primary">Send Test Email</button>
            </form>
        </div>
        
        <div class="card">
            <h2 class="section-title">Popular Email Providers</h2>
            
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #f8f9fa;">
                        <th style="padding: 12px; text-align: left; border-bottom: 2px solid #e0e0e0;">Provider</th>
                        <th style="padding: 12px; text-align: left; border-bottom: 2px solid #e0e0e0;">SMTP Host</th>
                        <th style="padding: 12px; text-align: left; border-bottom: 2px solid #e0e0e0;">Port</th>
                        <th style="padding: 12px; text-align: left; border-bottom: 2px solid #e0e0e0;">Encryption</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="padding: 12px; border-bottom: 1px solid #e0e0e0;">Gmail</td>
                        <td style="padding: 12px; border-bottom: 1px solid #e0e0e0;"><code>smtp.gmail.com</code></td>
                        <td style="padding: 12px; border-bottom: 1px solid #e0e0e0;">587</td>
                        <td style="padding: 12px; border-bottom: 1px solid #e0e0e0;">TLS</td>
                    </tr>
                    <tr>
                        <td style="padding: 12px; border-bottom: 1px solid #e0e0e0;">Outlook/Hotmail</td>
                        <td style="padding: 12px; border-bottom: 1px solid #e0e0e0;"><code>smtp-mail.outlook.com</code></td>
                        <td style="padding: 12px; border-bottom: 1px solid #e0e0e0;">587</td>
                        <td style="padding: 12px; border-bottom: 1px solid #e0e0e0;">TLS</td>
                    </tr>
                    <tr>
                        <td style="padding: 12px; border-bottom: 1px solid #e0e0e0;">Yahoo</td>
                        <td style="padding: 12px; border-bottom: 1px solid #e0e0e0;"><code>smtp.mail.yahoo.com</code></td>
                        <td style="padding: 12px; border-bottom: 1px solid #e0e0e0;">587</td>
                        <td style="padding: 12px; border-bottom: 1px solid #e0e0e0;">TLS</td>
                    </tr>
                    <tr>
                        <td style="padding: 12px;">Office 365</td>
                        <td style="padding: 12px;"><code>smtp.office365.com</code></td>
                        <td style="padding: 12px;">587</td>
                        <td style="padding: 12px;">TLS</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>