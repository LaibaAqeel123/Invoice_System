<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';
requireLogin();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id == 0) {
    die('Invalid invoice ID');
}

// Get invoice details
$invoice_query = $conn->prepare("
    SELECT i.*, c.name as customer_name, c.company, c.address, c.email, c.phone 
    FROM invoices i 
    LEFT JOIN customers c ON i.customer_id = c.id 
    WHERE i.id = ?
");
$invoice_query->bind_param("i", $id);
$invoice_query->execute();
$invoice = $invoice_query->get_result()->fetch_assoc();

if (!$invoice) {
    die('Invoice not found');
}

// Get invoice items
$items = $conn->query("SELECT * FROM invoice_items WHERE invoice_id = $id");

// Get company settings
$settings = $conn->query("SELECT * FROM company_settings WHERE id = 1")->fetch_assoc();

if (!$settings) {
    die('Company settings not found');
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Invoice <?php echo htmlspecialchars($invoice['invoice_number']); ?></title>
    <style>
        @page {
            margin: 15mm;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            font-size: 11pt;
            color: #333;
            line-height: 1.5;
            background: white;
        }
        
        .invoice-container {
            max-width: 210mm;
            margin: 0 auto;
            padding: 20px;
            background: white;
        }
        
        /* Header Section */
        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 50px;
            border-bottom: 3px solid #333;
            padding-bottom: 20px;
        }
        
        .logo-section {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .company-logo-image {
            max-width: 200px;
            max-height: 80px;
            object-fit: contain;
        }
        
        .company-info-text {
            display: flex;
            flex-direction: column;
        }
        
        .company-name-text {
            font-size: 24px;
            font-weight: bold;
            color: #333;
            line-height: 1.2;
        }
        
        .invoice-title-section {
            text-align: right;
        }
        
        .invoice-title {
            font-size: 42px;
            font-weight: bold;
            color: #333;
            letter-spacing: 1px;
        }
        
        /* Invoice Meta Section */
        .invoice-meta {
            display: table;
            width: 100%;
            margin-bottom: 40px;
        }
        
        .invoice-to,
        .invoice-details {
            display: table-cell;
            vertical-align: top;
            width: 50%;
        }
        
        .invoice-details {
            text-align: right;
        }
        
        .section-heading {
            font-size: 11px;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 12px;
            font-weight: 600;
        }
        
        .invoice-to p,
        .invoice-details p {
            margin: 4px 0;
            font-size: 11pt;
            line-height: 1.6;
            color: #333;
        }
        
        .invoice-to p strong,
        .invoice-details p strong {
            color: #000;
            font-weight: 600;
        }
        
        /* Table Styles */
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 30px 0;
        }
        
        thead {
            background: #333;
            color: white;
        }
        
        th {
            padding: 12px;
            text-align: left;
            font-weight: 600;
            font-size: 10pt;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
            font-size: 11pt;
            color: #333;
        }
        
        tbody tr:last-child td {
            border-bottom: 2px solid #333;
        }
        
        /* Totals Section */
        .totals-section {
            display: flex;
            justify-content: flex-end;
            margin-top: 30px;
            margin-bottom: 40px;
        }
        
        .totals {
            width: 300px;
            border: 2px solid #333;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 20px;
            border-bottom: 1px solid #ddd;
        }
        
        .total-row:last-child {
            border-bottom: none;
        }
        
        .total-row .label {
            font-weight: 500;
            color: #555;
        }
        
        .total-row .value {
            font-weight: 600;
            color: #333;
        }
        
        .total-row.final {
            background: #333;
            color: white;
            font-size: 14pt;
            font-weight: bold;
            padding: 15px 20px;
            border-bottom: none;
        }
        
        .total-row.final .label,
        .total-row.final .value {
            color: white;
        }
        
        /* Footer Section */
        .footer-info {
            margin-top: 60px;
            padding-top: 20px;
            border-top: 2px solid #333;
            font-size: 10pt;
            color: #555;
        }
        
        .footer-company {
            margin-bottom: 10px;
        }
        
        .footer-company strong {
            font-size: 12pt;
            color: #333;
        }
        
        .footer-section {
            margin-bottom: 15px;
            line-height: 1.8;
        }
        
        .footer-section p {
            margin: 3px 0;
        }
        
        .bank-details {
            background: #f5f5f5;
            padding: 12px 15px;
            border-left: 3px solid #333;
            margin-top: 15px;
        }
        
        .bank-details strong {
            color: #333;
            font-size: 11pt;
            display: block;
            margin-bottom: 8px;
        }
        
        .bank-details p {
            font-size: 10pt;
            color: #555;
            margin: 3px 0;
        }
        
        /* Print Styles */
        @media print {
            .no-print {
                display: none !important;
            }
            
            body {
                margin: 0;
                padding: 0;
            }
            
            .invoice-container {
                max-width: 100%;
                padding: 0;
            }
            
            @page {
                margin: 15mm;
            }
        }
        
        /* Action Buttons */
        .action-buttons {
            position: fixed;
            bottom: 30px;
            right: 30px;
            display: flex;
            gap: 10px;
            z-index: 1000;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
            transition: all 0.2s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }
        
        .btn-primary {
            background: #333;
            color: white;
        }
        
        .btn-secondary {
            background: #999;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #777;
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <!-- Header -->
        <div class="invoice-header">
            <div class="logo-section">
                <?php if (!empty($settings['logo_path']) && file_exists($settings['logo_path'])): ?>
                    <!-- Display uploaded logo -->
                    <img src="<?php echo htmlspecialchars($settings['logo_path']); ?>?v=<?php echo time(); ?>" 
                         alt="<?php echo htmlspecialchars($settings['company_name']); ?>" 
                         class="company-logo-image">
                <?php else: ?>
                    <!-- Display company name if no logo -->
                    <div class="company-info-text">
                        <div class="company-name-text"><?php echo htmlspecialchars($settings['company_name']); ?></div>
                    </div>
                <?php endif; ?>
            </div>
            <div class="invoice-title-section">
                <div class="invoice-title">INVOICE</div>
            </div>
        </div>
        
        <!-- Invoice Meta -->
        <div class="invoice-meta">
            <div class="invoice-to">
                <div class="section-heading">Invoice To:</div>
                <p><strong><?php echo htmlspecialchars($invoice['customer_name']); ?></strong></p>
                <?php if ($invoice['company']): ?>
                    <p><?php echo htmlspecialchars($invoice['company']); ?></p>
                <?php endif; ?>
                <p><?php echo nl2br(htmlspecialchars($invoice['address'])); ?></p>
                <?php if ($invoice['email']): ?>
                    <p><?php echo htmlspecialchars($invoice['email']); ?></p>
                <?php endif; ?>
                <?php if ($invoice['phone']): ?>
                    <p><?php echo htmlspecialchars($invoice['phone']); ?></p>
                <?php endif; ?>
            </div>
            
            <div class="invoice-details">
                <p><strong>Invoice Number:</strong> <?php echo htmlspecialchars($invoice['invoice_number']); ?></p>
                <p><strong>Date:</strong> <?php echo date('d/m/Y', strtotime($invoice['invoice_date'])); ?></p>
                <p><strong>Terms:</strong> <?php echo htmlspecialchars($invoice['terms']); ?></p>
                <p><strong>Due Date:</strong> <?php echo date('d/m/Y', strtotime($invoice['due_date'])); ?></p>
            </div>
        </div>
        
        <!-- Items Table -->
        <table>
            <thead>
                <tr>
                    <th style="width: 50%;">Description</th>
                    <th style="text-align: center; width: 15%;">QTY</th>
                    <th style="text-align: right; width: 17.5%;">Unit Price</th>
                    <th style="text-align: right; width: 17.5%;">Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $items->data_seek(0);
                while ($item = $items->fetch_assoc()): 
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['description']); ?></td>
                    <td style="text-align: center;"><?php echo number_format($item['quantity']); ?></td>
                    <td style="text-align: right;">£<?php echo number_format($item['unit_price'], 2); ?></td>
                    <td style="text-align: right;">£<?php echo number_format($item['amount'], 2); ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        
        <!-- Totals -->
        <div class="totals-section">
            <div class="totals">
                <div class="total-row">
                    <span class="label">Total:</span>
                    <span class="value">£<?php echo number_format($invoice['total'], 2); ?></span>
                </div>
                <div class="total-row final">
                    <span class="label">Balance Due:</span>
                    <span class="value">£<?php echo number_format($invoice['balance_due'], 2); ?></span>
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="footer-info">
            <div class="footer-company">
                <strong><?php echo htmlspecialchars($settings['company_name']); ?></strong>
            </div>
            
            <div class="footer-section">
                <p>Email: <?php echo htmlspecialchars($settings['company_email']); ?></p>
                <p>Mobile: <?php echo htmlspecialchars($settings['company_mobile']); ?></p>
                <p>Website: <?php echo htmlspecialchars($settings['company_website']); ?></p>
            </div>
            
            <div class="bank-details">
                <strong>Bank Details</strong>
                <p>Account Name: <?php echo htmlspecialchars($settings['bank_name']); ?></p>
                <p>AC NO: <?php echo htmlspecialchars($settings['account_number']); ?></p>
                <p>Sort Code: <?php echo htmlspecialchars($settings['sort_code']); ?></p>
            </div>
        </div>
    </div>
    
    <!-- Action Buttons -->
    <div class="action-buttons no-print">
        <button class="btn btn-secondary" onclick="window.close()">Close</button>
        <button class="btn btn-primary" onclick="window.print()">Print / Save as PDF</button>
    </div>
    
    <script>
        // Optional: Auto-print on load
        // window.onload = function() { window.print(); };
    </script>
</body>
</html>