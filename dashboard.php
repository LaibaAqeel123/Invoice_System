<?php
require_once 'config.php';
requireLogin();

// Get statistics
$total_invoices = $conn->query("SELECT COUNT(*) as count FROM invoices")->fetch_assoc()['count'];
$total_customers = $conn->query("SELECT COUNT(*) as count FROM customers")->fetch_assoc()['count'];
$pending_invoices = $conn->query("SELECT COUNT(*) as count FROM invoices WHERE status = 'pending'")->fetch_assoc()['count'];
$total_revenue = $conn->query("SELECT SUM(total) as sum FROM invoices")->fetch_assoc()['sum'] ?? 0;

// Get recent invoices
$recent_invoices = $conn->query("
    SELECT i.*, c.name as customer_name 
    FROM invoices i 
    LEFT JOIN customers c ON i.customer_id = c.id 
    ORDER BY i.created_at DESC 
    LIMIT 10
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Invoice Generator</title>
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
        
        .logo-subtitle {
            color: #667eea;
            font-size: 9px;
            letter-spacing: 2px;
            margin-top: -3px;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .user-name {
            color: #666;
            font-size: 14px;
        }
        
        .logout-btn {
            padding: 8px 20px;
            background: #f44336;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
        }
        
        .logout-btn:hover {
            background: #d32f2f;
        }
        
        .container {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 30px;
        }
        
        .page-title {
            font-size: 28px;
            color: #333;
            margin-bottom: 30px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .stat-label {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .stat-value {
            font-size: 32px;
            font-weight: bold;
            color: #333;
        }
        
        .actions-section {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .section-title {
            font-size: 20px;
            color: #333;
            margin-bottom: 20px;
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
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
        
        .btn-success {
            background: #4caf50;
            color: white;
        }
        
        .btn-info {
            background: #2196f3;
            color: white;
        }
        
        .btn-warning {
            background: #ff9800;
            color: white;
        }
        
        .recent-invoices {
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
        }
        
        .action-link:hover {
            text-decoration: underline;
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
        <div class="user-info">
            <span class="user-name">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <h1 class="page-title">Dashboard</h1>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Total Invoices</div>
                <div class="stat-value"><?php echo $total_invoices; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Total Customers</div>
                <div class="stat-value"><?php echo $total_customers; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Pending Invoices</div>
                <div class="stat-value"><?php echo $pending_invoices; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Total Revenue</div>
                <div class="stat-value">£<?php echo number_format($total_revenue, 2); ?></div>
            </div>
        </div>
        
        <div class="actions-section">
            <h2 class="section-title">Quick Actions</h2>
            <div class="action-buttons">
                <a href="create_invoice.php" class="btn btn-primary">Create New Invoice</a>
                <a href="customers.php" class="btn btn-success">Manage Customers</a>
                <a href="invoices.php" class="btn btn-info">View All Invoices</a>
                <a href="settings.php" class="btn btn-info">Company Settings</a>
                <a href="manage_admins.php" class="btn btn-warning">Manage Admins</a>
            </div>
        </div>
        
        <div class="recent-invoices">
            <h2 class="section-title">Recent Invoices</h2>
            <?php if ($recent_invoices->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Invoice Number</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Due Date</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($invoice = $recent_invoices->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($invoice['invoice_number']); ?></td>
                            <td><?php echo htmlspecialchars($invoice['customer_name']); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($invoice['invoice_date'])); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($invoice['due_date'])); ?></td>
                            <td>£<?php echo number_format($invoice['total'], 2); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $invoice['status']; ?>">
                                    <?php echo ucfirst($invoice['status']); ?>
                                </span>
                            </td>
                            <td>
                                <a href="view_invoice.php?id=<?php echo $invoice['id']; ?>" class="action-link">View</a> |
                                <a href="edit_invoice.php?id=<?php echo $invoice['id']; ?>" class="action-link">Edit</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-data">No invoices found. Create your first invoice to get started.</div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>