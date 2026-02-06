<?php
require_once 'config.php';
requireLogin();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get invoice
$invoice_query = $conn->prepare("SELECT * FROM invoices WHERE id = ?");
$invoice_query->bind_param("i", $id);
$invoice_query->execute();
$invoice = $invoice_query->get_result()->fetch_assoc();

// Get templates for dropdown
$templates = $conn->query("SELECT * FROM company_templates ORDER BY is_default DESC, template_name ASC");

if (!$invoice) {
    header('Location: invoices.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_id = $_POST['customer_id'];
    $template_id = $_POST['template_id'];
    $invoice_date = $_POST['invoice_date'];
    $due_date = $_POST['due_date'];
    $terms = $_POST['terms'];
    $status = $_POST['status'];
    
    // Calculate totals
    $subtotal = 0;
    $descriptions = $_POST['description'];
    $quantities = $_POST['quantity'];
    $unit_prices = $_POST['unit_price'];
    
    for ($i = 0; $i < count($descriptions); $i++) {
        if (!empty($descriptions[$i])) {
            $amount = $quantities[$i] * $unit_prices[$i];
            $subtotal += $amount;
        }
    }
    
    $total = $subtotal;
    $balance_due = $total;
    
    // Update invoice
    $stmt = $conn->prepare("UPDATE invoices SET customer_id=?, template_id=?, invoice_date=?, due_date=?, terms=?, subtotal=?, total=?, balance_due=?, status=? WHERE id=?");
    $stmt->bind_param("iisssdddsi", $customer_id, $template_id, $invoice_date, $due_date, $terms, $subtotal, $total, $balance_due, $status, $id);
    $stmt->execute();
    $stmt->close();
    
    // Delete old items
    $conn->query("DELETE FROM invoice_items WHERE invoice_id = $id");
    
    // Insert new items
    $stmt = $conn->prepare("INSERT INTO invoice_items (invoice_id, description, quantity, unit_price, amount) VALUES (?, ?, ?, ?, ?)");
    for ($i = 0; $i < count($descriptions); $i++) {
        if (!empty($descriptions[$i])) {
            $amount = $quantities[$i] * $unit_prices[$i];
            $stmt->bind_param("isidd", $id, $descriptions[$i], $quantities[$i], $unit_prices[$i], $amount);
            $stmt->execute();
        }
    }
    $stmt->close();
    
    header("Location: view_invoice.php?id=$id");
    exit();
}

// Get customers
$customers = $conn->query("SELECT * FROM customers ORDER BY name");

// Get invoice items
$items = $conn->query("SELECT * FROM invoice_items WHERE invoice_id = $id");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Invoice - Invoice Management System</title>
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
            background: linear-gradient(135deg, #e91e63 0%, #9c27b0 100%);
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
            color: #e91e63;
            font-size: 9px;
            letter-spacing: 2px;
            margin-top: -3px;
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
            max-width: 1200px;
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
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 30px;
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
        input[type="date"],
        input[type="number"],
        select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .section-title {
            font-size: 18px;
            color: #333;
            margin: 30px 0 20px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .items-table th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #555;
            font-size: 14px;
            border-bottom: 2px solid #e0e0e0;
        }
        
        .items-table td {
            padding: 10px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .items-table input {
            width: 100%;
            padding: 8px;
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
        
        .remove-btn {
            background: #f44336;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo-text">
            <div class="logo-icon">N</div>
            <div>
                <div class="logo-name">NEPTA</div>
                <div class="logo-subtitle">SOLUTION</div>
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
        <h1 class="page-title">Edit Invoice #<?php echo htmlspecialchars($invoice['invoice_number']); ?></h1>
        
        <div class="card">
            <form method="POST" action="" id="invoiceForm">
                <div class="form-group full-width">
                    <label for="template_id">Company Template</label>
                    <select id="template_id" name="template_id" required>
                        <?php while ($template = $templates->fetch_assoc()): ?>
                            <option value="<?php echo $template['id']; ?>" <?php echo ($template['id'] == $invoice['template_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($template['template_name']); ?> - <?php echo htmlspecialchars($template['company_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="customer_id">Select Customer</label>
                        <select id="customer_id" name="customer_id" required>
                            <?php while ($customer = $customers->fetch_assoc()): ?>
                                <option value="<?php echo $customer['id']; ?>" <?php echo ($customer['id'] == $invoice['customer_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($customer['name']) . ($customer['company'] ? ' - ' . htmlspecialchars($customer['company']) : ''); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="invoice_date">Invoice Date</label>
                        <input type="date" id="invoice_date" name="invoice_date" value="<?php echo $invoice['invoice_date']; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="due_date">Due Date</label>
                        <input type="date" id="due_date" name="due_date" value="<?php echo $invoice['due_date']; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="terms">Terms</label>
                        <input type="text" id="terms" name="terms" value="<?php echo htmlspecialchars($invoice['terms']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status" required>
                            <option value="pending" <?php echo ($invoice['status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                            <option value="paid" <?php echo ($invoice['status'] == 'paid') ? 'selected' : ''; ?>>Paid</option>
                        </select>
                    </div>
                </div>
                
                <h3 class="section-title">Invoice Items</h3>
                
                <table class="items-table" id="itemsTable">
                    <thead>
                        <tr>
                            <th style="width: 40%;">Description</th>
                            <th style="width: 15%;">Quantity</th>
                            <th style="width: 20%;">Unit Price</th>
                            <th style="width: 20%;">Amount</th>
                            <th style="width: 5%;"></th>
                        </tr>
                    </thead>
                    <tbody id="itemsBody">
                        <?php while ($item = $items->fetch_assoc()): ?>
                        <tr class="item-row">
                            <td><input type="text" name="description[]" value="<?php echo htmlspecialchars($item['description']); ?>" required></td>
                            <td><input type="number" name="quantity[]" value="<?php echo $item['quantity']; ?>" min="1" step="1" class="quantity" required></td>
                            <td><input type="number" name="unit_price[]" value="<?php echo $item['unit_price']; ?>" min="0" step="0.01" class="unit-price" required></td>
                            <td><input type="number" class="amount" value="<?php echo $item['amount']; ?>" step="0.01" readonly></td>
                            <td><button type="button" class="remove-btn" onclick="removeRow(this)">Remove</button></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                
                <button type="button" class="btn btn-secondary" onclick="addRow()">Add Item</button>
                
                <div style="margin-top: 30px; text-align: right;">
                    <button type="submit" class="btn btn-success">Update Invoice</button>
                    <a href="view_invoice.php?id=<?php echo $id; ?>" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function calculateAmount(row) {
            const quantity = parseFloat(row.querySelector('.quantity').value) || 0;
            const unitPrice = parseFloat(row.querySelector('.unit-price').value) || 0;
            const amount = quantity * unitPrice;
            row.querySelector('.amount').value = amount.toFixed(2);
        }
        
        function addRow() {
            const tbody = document.getElementById('itemsBody');
            const newRow = document.createElement('tr');
            newRow.className = 'item-row';
            newRow.innerHTML = `
                <td><input type="text" name="description[]" required></td>
                <td><input type="number" name="quantity[]" value="1" min="1" step="1" class="quantity" required></td>
                <td><input type="number" name="unit_price[]" value="0" min="0" step="0.01" class="unit-price" required></td>
                <td><input type="number" class="amount" value="0" step="0.01" readonly></td>
                <td><button type="button" class="remove-btn" onclick="removeRow(this)">Remove</button></td>
            `;
            tbody.appendChild(newRow);
            attachEventListeners(newRow);
        }
        
        function removeRow(btn) {
            const rows = document.querySelectorAll('.item-row');
            if (rows.length > 1) {
                btn.closest('tr').remove();
            } else {
                alert('At least one item is required');
            }
        }
        
        function attachEventListeners(row) {
            row.querySelector('.quantity').addEventListener('input', function() {
                calculateAmount(row);
            });
            row.querySelector('.unit-price').addEventListener('input', function() {
                calculateAmount(row);
            });
        }
        
        // Attach event listeners to all rows
        document.querySelectorAll('.item-row').forEach(row => {
            attachEventListeners(row);
        });
    </script>
</body>
</html>