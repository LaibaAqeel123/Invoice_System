<?php
require_once 'config.php';
requireLogin();

// Check if email was just sent
$email_sent = isset($_GET['email_sent']) ? true : false;

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get invoice details with template
$invoice_query = $conn->prepare("
    SELECT i.*, c.name as customer_name, c.company, c.address, c.email, c.phone,
           t.logo_path, t.company_name as template_company_name, t.company_email as template_email,
           t.company_mobile as template_mobile, t.company_website as template_website,
           t.bank_name, t.account_number, t.sort_code
    FROM invoices i 
    LEFT JOIN customers c ON i.customer_id = c.id 
    LEFT JOIN company_templates t ON i.template_id = t.id
    WHERE i.id = ?
");
$invoice_query->bind_param("i", $id);
$invoice_query->execute();
$invoice = $invoice_query->get_result()->fetch_assoc();

if (!$invoice) {
    header('Location: invoices.php');
    exit();
}

// Get invoice items
$items = $conn->query("SELECT * FROM invoice_items WHERE invoice_id = $id");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice <?php echo htmlspecialchars($invoice['invoice_number']); ?></title>
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
            max-width: 900px;
            margin: 30px auto;
            padding: 0 30px;
        }
        
        .action-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin-right: 10px;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-success {
            background: #4caf50;
            color: white;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .invoice-container {
            background: white;
            padding: 50px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 40px;
        }
        
        .company-logo {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .company-logo-image {
            max-width: 200px;
            max-height: 80px;
            object-fit: contain;
        }
        
        .company-logo-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 28px;
        }
        
        .company-info {
            display: flex;
            flex-direction: column;
        }
        
        .company-name {
            font-size: 32px;
            font-weight: bold;
            color: #333;
        }
        
        .invoice-title {
            font-size: 36px;
            font-weight: bold;
            color: #333;
        }
        
        .invoice-meta {
            display: flex;
            justify-content: space-between;
            margin-bottom: 40px;
        }
        
        .invoice-to,
        .invoice-details {
            flex: 1;
        }
        
        .invoice-to h3,
        .invoice-details h3 {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
        }
        
        .invoice-to p,
        .invoice-details p {
            margin: 5px 0;
            color: #333;
            font-size: 14px;
        }
        
        .invoice-details {
            text-align: right;
        }
        
        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        
        .invoice-table thead {
            background: #667eea;
            color: white;
        }
        
        .invoice-table th {
            padding: 12px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
        }
        
        .invoice-table td {
            padding: 12px;
            border-bottom: 1px solid #e0e0e0;
            color: #333;
            font-size: 14px;
        }
        
        .invoice-table tbody tr:last-child td {
            border-bottom: none;
        }
        
        .totals-section {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 40px;
        }
        
        .totals {
            width: 300px;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .total-row.final {
            border-top: 2px solid #333;
            border-bottom: 3px double #333;
            font-weight: bold;
            font-size: 16px;
            margin-top: 10px;
        }
        
        .footer-info {
            border-top: 2px solid #e0e0e0;
            padding-top: 20px;
            color: #666;
            font-size: 12px;
            line-height: 1.6;
        }
        
        .footer-info p {
            margin: 5px 0;
        }
        
        @media print {
            body {
                background: white;
            }
            .header,
            .action-bar {
                display: none;
            }
            .container {
                margin: 0;
                padding: 0;
            }
            .invoice-container {
                box-shadow: none;
                padding: 20px;
            }
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
            <a href="settings.php">Settings</a>
            <a href="templates.php">Templates</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <div class="action-bar">
            <a href="invoices.php" class="btn btn-secondary">Back to Invoices</a>
            <div>
                <a href="email_invoice.php?id=<?php echo $id; ?>" class="btn btn-success">Send Email</a>
                <a href="edit_invoice.php?id=<?php echo $id; ?>" class="btn btn-primary">Edit Invoice</a>
                <a href="generate_pdf.php?id=<?php echo $id; ?>" class="btn btn-success" target="_blank">Download PDF</a>
                <button onclick="window.print()" class="btn btn-primary">Print</button>
            </div>
        </div>
        
        <?php if ($email_sent): ?>
            <div style="background: #d4edda; color: #155724; padding: 12px 20px; border-radius: 4px; margin-bottom: 20px;">
                 Invoice email sent successfully!
            </div>
        <?php endif; ?>
        
        <?php if (isset($invoice['email_sent']) && $invoice['email_sent']): ?>
            <div style="background: #e3f2fd; color: #1976d2; padding: 12px 20px; border-radius: 4px; margin-bottom: 20px;">
                 This invoice was emailed to <?php echo htmlspecialchars($invoice['email_sent_to']); ?> 
                on <?php echo date('d/m/Y H:i', strtotime($invoice['email_sent_at'])); ?>
            </div>
        <?php endif; ?>
        
        <div class="invoice-container" id="invoiceContent">
            <div class="invoice-header">
                <div class="company-logo">
                    <?php if (!empty($invoice['logo_path']) && file_exists($invoice['logo_path'])): ?>
                        <!-- Display template logo -->
                        <img src="<?php echo htmlspecialchars($invoice['logo_path']); ?>?v=<?php echo time(); ?>" 
                             alt="<?php echo htmlspecialchars($invoice['template_company_name']); ?>" 
                             class="company-logo-image">
                    <?php else: ?>
                        <!-- Display company name if no logo -->
                        <div class="company-logo-icon">IG</div>
                        <div class="company-info">
                            <div class="company-name"><?php echo htmlspecialchars($invoice['template_company_name']); ?></div>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="invoice-title">INVOICE</div>
            </div>
            
            <div class="invoice-meta">
                <div class="invoice-to">
                    <h3>Invoice To:</h3>
                    <p><strong><?php echo htmlspecialchars($invoice['customer_name']); ?></strong></p>
                    <?php if ($invoice['company']): ?>
                        <p><?php echo htmlspecialchars($invoice['company']); ?></p>
                    <?php endif; ?>
                    <p><?php echo nl2br(htmlspecialchars($invoice['address'])); ?></p>
                </div>
                
                <div class="invoice-details">
                    <p><strong>Invoice Number:</strong> <?php echo htmlspecialchars($invoice['invoice_number']); ?></p>
                    <p><strong>Date:</strong> <?php echo date('d/m/Y', strtotime($invoice['invoice_date'])); ?></p>
                    <p><strong>Terms:</strong> <?php echo htmlspecialchars($invoice['terms']); ?></p>
                    <p><strong>Due Date:</strong> <?php echo date('d/m/Y', strtotime($invoice['due_date'])); ?></p>
                </div>
            </div>
            
            <table class="invoice-table">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th style="text-align: center;">QTY</th>
                        <th style="text-align: right;">Unit Price</th>
                        <th style="text-align: right;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($item = $items->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['description']); ?></td>
                        <td style="text-align: center;"><?php echo $item['quantity']; ?></td>
                        <td style="text-align: right;">£<?php echo number_format($item['unit_price'], 2); ?></td>
                        <td style="text-align: right;">£<?php echo number_format($item['amount'], 2); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            
            <div class="totals-section">
                <div class="totals">
                    <div class="total-row">
                        <span>Total:</span>
                        <span>£<?php echo number_format($invoice['total'], 2); ?></span>
                    </div>
                    <div class="total-row final">
                        <span>Balance Due:</span>
                        <span>£<?php echo number_format($invoice['balance_due'], 2); ?></span>
                    </div>
                </div>
            </div>
            
            <div class="footer-info">
                <p><strong><?php echo htmlspecialchars($invoice['template_company_name']); ?></strong></p>
                <p>Email: <?php echo htmlspecialchars($invoice['template_email']); ?></p>
                <p>Mobile: <?php echo htmlspecialchars($invoice['template_mobile']); ?></p>
                <p>Website: <?php echo htmlspecialchars($invoice['template_website']); ?></p>
                <p style="margin-top: 15px;"><strong>Bank Details:</strong></p>
                <p>Account Name: <?php echo htmlspecialchars($invoice['bank_name']); ?></p>
                <p>AC NO: <?php echo htmlspecialchars($invoice['account_number']); ?></p>
                <p>Sort Code: <?php echo htmlspecialchars($invoice['sort_code']); ?></p>
            </div>
        </div>
    </div>
</body>
</html>