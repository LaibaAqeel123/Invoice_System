<?php
require_once 'config.php';
requireLogin();

$invoice_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get invoice and customer details
$invoice_query = $conn->prepare("
    SELECT i.*, c.name as customer_name, c.email as customer_email, c.company,
           t.company_name as template_company_name
    FROM invoices i 
    LEFT JOIN customers c ON i.customer_id = c.id 
    LEFT JOIN company_templates t ON i.template_id = t.id
    WHERE i.id = ?
");
$invoice_query->bind_param("i", $invoice_id);
$invoice_query->execute();
$invoice = $invoice_query->get_result()->fetch_assoc();

if (!$invoice) {
    header('Location: invoices.php');
    exit();
}

// Handle email sending
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recipient_email = $_POST['recipient_email'];
    $recipient_name = $_POST['recipient_name'];
    $message = $_POST['message'];
    
    require_once 'send_email.php';
    $result = sendInvoiceEmail($invoice_id, $recipient_email, $recipient_name);
    
    if ($result['success']) {
        header("Location: view_invoice.php?id=$invoice_id&email_sent=1");
    } else {
        $error = $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send Invoice via Email</title>
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
        
        .nav-links a:hover {
            background: #f0f0f0;
            color: #333;
        }
        
        .container {
            max-width: 800px;
            margin: 30px auto;
            padding: 0 30px;
        }
        
        .page-title {
            font-size: 28px;
            color: #333;
            margin-bottom: 30px;
        }
        
        .card {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .invoice-summary {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .summary-row:last-child {
            border-bottom: none;
            font-weight: bold;
            font-size: 16px;
        }
        
        .form-group {
            margin-bottom: 20px;
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
        textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            font-family: Arial, sans-serif;
        }
        
        textarea {
            min-height: 100px;
            resize: vertical;
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
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 12px 20px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .info-box {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 4px;
            color: #1976d2;
            font-size: 14px;
            margin-bottom: 20px;
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
            <a href="logout.php">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <h1 class="page-title">Send Invoice via Email</h1>
        
        <?php if (isset($error)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="card">
            <h2 style="margin-bottom: 20px; color: #333;">Invoice Summary</h2>
            
            <div class="invoice-summary">
                <div class="summary-row">
                    <span>Invoice Number:</span>
                    <span><?php echo htmlspecialchars($invoice['invoice_number']); ?></span>
                </div>
                <div class="summary-row">
                    <span>Customer:</span>
                    <span><?php echo htmlspecialchars($invoice['customer_name']); ?></span>
                </div>
                <div class="summary-row">
                    <span>Company:</span>
                    <span><?php echo htmlspecialchars($invoice['template_company_name']); ?></span>
                </div>
                <div class="summary-row">
                    <span>Invoice Date:</span>
                    <span><?php echo date('d/m/Y', strtotime($invoice['invoice_date'])); ?></span>
                </div>
                <div class="summary-row">
                    <span>Total Amount:</span>
                    <span>£<?php echo number_format($invoice['total'], 2); ?></span>
                </div>
            </div>
            
            
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="recipient_email">Recipient Email *</label>
                    <input type="email" id="recipient_email" name="recipient_email" 
                           value="<?php echo htmlspecialchars($invoice['customer_email']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="recipient_name">Recipient Name</label>
                    <input type="text" id="recipient_name" name="recipient_name" 
                           value="<?php echo htmlspecialchars($invoice['customer_name']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="message">Additional Message (Optional)</label>
                    <textarea id="message" name="message" placeholder="Add a personal message to include in the email..."></textarea>
                </div>
                
                <div style="margin-top: 30px;">
                    <button type="submit" class="btn btn-success">Send Email</button>
                    <a href="view_invoice.php?id=<?php echo $invoice_id; ?>" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
