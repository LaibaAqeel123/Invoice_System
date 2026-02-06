<?php
require_once 'config.php';
requireLogin();

// Handle invoice deletion
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM invoices WHERE id = $id");
    header('Location: invoices.php?success=deleted');
    exit();
}

// Get all invoices
$invoices = $conn->query("
    SELECT i.*, c.name as customer_name 
    FROM invoices i 
    LEFT JOIN customers c ON i.customer_id = c.id 
    ORDER BY i.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Invoices - Invoice Management System</title>
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
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 30px;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .page-title {
            font-size: 28px;
            color: #333;
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
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 12px 20px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .card {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        thead {
            background: #f8f9fa;
        }
        
        th {
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #555;
            font-size: 14px;
            border-bottom: 2px solid #e0e0e0;
        }
        
        td {
            padding: 12px;
            border-bottom: 1px solid #e0e0e0;
            color: #666;
            font-size: 14px;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-paid {
            background: #d4edda;
            color: #155724;
        }
        
        .action-link {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            margin-right: 10px;
        }
        
        .action-link:hover {
            text-decoration: underline;
        }
        
        .action-link.delete {
            color: #f44336;
        }
        
        .no-data {
            text-align: center;
            padding: 40px;
            color: #999;
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
            <a href="invoices.php" class="active">Invoices</a>
            <a href="settings.php">Settings</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <div class="page-header">
            <h1 class="page-title">All Invoices</h1>
            <a href="create_invoice.php" class="btn btn-primary">Create New Invoice</a>
        </div>
        
        <?php if (isset($_GET['success']) && $_GET['success'] === 'deleted'): ?>
            <div class="success-message">Invoice deleted successfully!</div>
        <?php endif; ?>
        
        <div class="card">
            <?php if ($invoices->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Invoice Number</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Due Date</th>
                            <th>Total</th>
                            <th>Balance Due</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($invoice = $invoices->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($invoice['invoice_number']); ?></td>
                            <td><?php echo htmlspecialchars($invoice['customer_name']); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($invoice['invoice_date'])); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($invoice['due_date'])); ?></td>
                            <td>£<?php echo number_format($invoice['total'], 2); ?></td>
                            <td>£<?php echo number_format($invoice['balance_due'], 2); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $invoice['status']; ?>">
                                    <?php echo ucfirst($invoice['status']); ?>
                                </span>
                            </td>
                            <td>
                                <a href="view_invoice.php?id=<?php echo $invoice['id']; ?>" class="action-link">View</a>
                                <a href="edit_invoice.php?id=<?php echo $invoice['id']; ?>" class="action-link">Edit</a>
                                <a href="generate_pdf.php?id=<?php echo $invoice['id']; ?>" class="action-link" target="_blank">PDF</a>
                                <a href="?delete=<?php echo $invoice['id']; ?>" 
                                   class="action-link delete" 
                                   onclick="return confirm('Are you sure you want to delete this invoice?')">Delete</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-data">
                    No invoices found. <a href="create_invoice.php" style="color: #667eea;">Create your first invoice</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>