<?php
require_once 'config.php';
requireLogin();

// Create uploads directory if it doesn't exist
if (!file_exists('uploads')) {
    mkdir('uploads', 0777, true);
}

// Handle template addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $template_name = $_POST['template_name'];
    $company_name = $_POST['company_name'];
    $company_email = $_POST['company_email'];
    $company_mobile = $_POST['company_mobile'];
    $company_website = $_POST['company_website'];
    $bank_name = $_POST['bank_name'];
    $account_number = $_POST['account_number'];
    $sort_code = $_POST['sort_code'];
    
    $logo_path = NULL;
    
    // Handle logo upload
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
        $file_type = $_FILES['logo']['type'];
        
        if (in_array($file_type, $allowed_types)) {
            $file_extension = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
            $new_filename = 'logo_' . time() . '_' . uniqid() . '.' . $file_extension;
            $upload_path = 'uploads/' . $new_filename;
            
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $upload_path)) {
                $logo_path = $upload_path;
            }
        }
    }
    
    $stmt = $conn->prepare("INSERT INTO company_templates (template_name, logo_path, company_name, company_email, company_mobile, company_website, bank_name, account_number, sort_code) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssssss", $template_name, $logo_path, $company_name, $company_email, $company_mobile, $company_website, $bank_name, $account_number, $sort_code);
    $stmt->execute();
    $stmt->close();
    
    header('Location: templates.php?success=added');
    exit();
}

// Handle template update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $template_id = $_POST['template_id'];
    $template_name = $_POST['template_name'];
    $company_name = $_POST['company_name'];
    $company_email = $_POST['company_email'];
    $company_mobile = $_POST['company_mobile'];
    $company_website = $_POST['company_website'];
    $bank_name = $_POST['bank_name'];
    $account_number = $_POST['account_number'];
    $sort_code = $_POST['sort_code'];
    
    // Get existing logo path
    $existing = $conn->query("SELECT logo_path FROM company_templates WHERE id = $template_id")->fetch_assoc();
    $logo_path = $existing['logo_path'];
    
    // Handle new logo upload
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
        $file_type = $_FILES['logo']['type'];
        
        if (in_array($file_type, $allowed_types)) {
            // Delete old logo if exists
            if ($logo_path && file_exists($logo_path)) {
                unlink($logo_path);
            }
            
            $file_extension = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
            $new_filename = 'logo_' . time() . '_' . uniqid() . '.' . $file_extension;
            $upload_path = 'uploads/' . $new_filename;
            
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $upload_path)) {
                $logo_path = $upload_path;
            }
        }
    }
    
    $stmt = $conn->prepare("UPDATE company_templates SET template_name=?, logo_path=?, company_name=?, company_email=?, company_mobile=?, company_website=?, bank_name=?, account_number=?, sort_code=? WHERE id=?");
    $stmt->bind_param("sssssssssi", $template_name, $logo_path, $company_name, $company_email, $company_mobile, $company_website, $bank_name, $account_number, $sort_code, $template_id);
    $stmt->execute();
    $stmt->close();
    
    header('Location: templates.php?success=updated');
    exit();
}

// Handle template deletion
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    
    // Don't allow deleting default or if it's being used
    $check = $conn->query("SELECT is_default, logo_path FROM company_templates WHERE id = $id")->fetch_assoc();
    $usage = $conn->query("SELECT COUNT(*) as count FROM invoices WHERE template_id = $id")->fetch_assoc()['count'];
    
    if ($check['is_default'] == 1) {
        header('Location: templates.php?error=cannot_delete_default');
        exit();
    }
    
    if ($usage > 0) {
        header('Location: templates.php?error=template_in_use');
        exit();
    }
    
    // Delete logo file
    if ($check['logo_path'] && file_exists($check['logo_path'])) {
        unlink($check['logo_path']);
    }
    
    $conn->query("DELETE FROM company_templates WHERE id = $id");
    header('Location: templates.php?success=deleted');
    exit();
}

// Handle set default
if (isset($_GET['set_default'])) {
    $id = intval($_GET['set_default']);
    
    // Remove default from all
    $conn->query("UPDATE company_templates SET is_default = 0");
    
    // Set new default
    $conn->query("UPDATE company_templates SET is_default = 1 WHERE id = $id");
    
    header('Location: templates.php?success=default_set');
    exit();
}

// Get all templates
$templates = $conn->query("SELECT * FROM company_templates ORDER BY is_default DESC, template_name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Company Templates - Invoice Management System</title>
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
        
        .btn-success {
            background: #4caf50;
            color: white;
        }
        
        .btn-danger {
            background: #f44336;
            color: white;
        }
        
        .btn-warning {
            background: #ff9800;
            color: white;
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
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
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
        input[type="file"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .templates-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
        }
        
        .template-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.2s;
        }
        
        .template-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .template-logo {
            height: 120px;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .template-logo img {
            max-width: 90%;
            max-height: 100px;
            object-fit: contain;
        }
        
        .template-logo-placeholder {
            font-size: 48px;
            color: #999;
            font-weight: bold;
        }
        
        .template-body {
            padding: 20px;
        }
        
        .template-name {
            font-size: 20px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }
        
        .template-info {
            font-size: 13px;
            color: #666;
            line-height: 1.6;
            margin-bottom: 15px;
        }
        
        .template-info p {
            margin: 4px 0;
        }
        
        .template-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .template-badge {
            display: inline-block;
            padding: 4px 12px;
            background: #4caf50;
            color: white;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .btn-small {
            padding: 8px 16px;
            font-size: 12px;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background: white;
            margin: 50px auto;
            padding: 30px;
            border-radius: 8px;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .modal-title {
            font-size: 24px;
            color: #333;
        }
        
        .close-btn {
            font-size: 28px;
            font-weight: bold;
            color: #999;
            cursor: pointer;
            background: none;
            border: none;
        }
        
        .close-btn:hover {
            color: #333;
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
            <a href="templates.php" class="active">Templates</a>
            <a href="settings.php">Settings</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <div class="page-header">
            <h1 class="page-title">Company Templates</h1>
            <button class="btn btn-primary" onclick="openAddModal()">Add New Template</button>
        </div>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="success-message">
                <?php 
                if ($_GET['success'] === 'added') echo 'Template added successfully!';
                if ($_GET['success'] === 'updated') echo 'Template updated successfully!';
                if ($_GET['success'] === 'deleted') echo 'Template deleted successfully!';
                if ($_GET['success'] === 'default_set') echo 'Default template set successfully!';
                ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['error'])): ?>
            <div class="error-message">
                <?php 
                if ($_GET['error'] === 'cannot_delete_default') echo 'Cannot delete default template!';
                if ($_GET['error'] === 'template_in_use') echo 'Cannot delete template - it is being used by invoices!';
                ?>
            </div>
        <?php endif; ?>
        
        <div class="templates-grid">
            <?php if ($templates->num_rows > 0): ?>
                <?php while ($template = $templates->fetch_assoc()): ?>
                <div class="template-card">
                    <div class="template-logo">
                        <?php if ($template['logo_path'] && file_exists($template['logo_path'])): ?>
                            <img src="<?php echo htmlspecialchars($template['logo_path']); ?>?v=<?php echo time(); ?>" alt="Logo">
                        <?php else: ?>
                            <div class="template-logo-placeholder">
                                <?php echo strtoupper(substr($template['template_name'], 0, 2)); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="template-body">
                        <?php if ($template['is_default']): ?>
                            <span class="template-badge">DEFAULT</span>
                        <?php endif; ?>
                        
                        <div class="template-name"><?php echo htmlspecialchars($template['template_name']); ?></div>
                        
                        <div class="template-info">
                            <p><strong><?php echo htmlspecialchars($template['company_name']); ?></strong></p>
                            <p><?php echo htmlspecialchars($template['company_email']); ?></p>
                            <p><?php echo htmlspecialchars($template['company_mobile']); ?></p>
                        </div>
                        
                        <div class="template-actions">
                            <button class="btn btn-primary btn-small" onclick="editTemplate(<?php echo $template['id']; ?>)">Edit</button>
                            <?php if (!$template['is_default']): ?>
                                <a href="?set_default=<?php echo $template['id']; ?>" class="btn btn-warning btn-small">Set Default</a>
                                <a href="?delete=<?php echo $template['id']; ?>" 
                                   class="btn btn-danger btn-small" 
                                   onclick="return confirm('Are you sure you want to delete this template?')">Delete</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="text-align: center; color: #999; grid-column: 1/-1;">No templates found. Create your first template!</p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Add Template Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Add New Template</h2>
                <button class="close-btn" onclick="closeAddModal()">&times;</button>
            </div>
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add">
                
                <div class="form-group">
                    <label for="template_name">Template Name</label>
                    <input type="text" id="template_name" name="template_name" placeholder="e.g., ABC Company, XYZ Corp" required>
                </div>
                
                <div class="form-group">
                    <label for="logo">Company Logo (Optional)</label>
                    <input type="file" id="logo" name="logo" accept="image/jpeg,image/png,image/jpg">
                    <small style="color: #666;">Recommended: 200x80px, PNG or JPG</small>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="company_name">Company Name</label>
                        <input type="text" id="company_name" name="company_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="company_email">Email</label>
                        <input type="email" id="company_email" name="company_email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="company_mobile">Mobile</label>
                        <input type="text" id="company_mobile" name="company_mobile" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="company_website">Website</label>
                        <input type="text" id="company_website" name="company_website" required>
                    </div>
                </div>
                
                <h3 style="margin: 20px 0 15px 0; color: #333; font-size: 18px;">Bank Details</h3>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="bank_name">Account Name</label>
                        <input type="text" id="bank_name" name="bank_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="account_number">Account Number</label>
                        <input type="text" id="account_number" name="account_number" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="sort_code">Sort Code</label>
                        <input type="text" id="sort_code" name="sort_code" required>
                    </div>
                </div>
                
                <div style="margin-top: 30px; text-align: right;">
                    <button type="button" class="btn btn-secondary" onclick="closeAddModal()">Cancel</button>
                    <button type="submit" class="btn btn-success">Add Template</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Edit Template Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Edit Template</h2>
                <button class="close-btn" onclick="closeEditModal()">&times;</button>
            </div>
            <div id="editFormContainer"></div>
        </div>
    </div>
    
    <script>
        function openAddModal() {
            document.getElementById('addModal').style.display = 'block';
        }
        
        function closeAddModal() {
            document.getElementById('addModal').style.display = 'none';
        }
        
        function editTemplate(id) {
            fetch('get_template.php?id=' + id)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const template = data.template;
                        const formHtml = `
                            <form method="POST" action="" enctype="multipart/form-data">
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="template_id" value="${template.id}">
                                
                                <div class="form-group">
                                    <label for="edit_template_name">Template Name</label>
                                    <input type="text" id="edit_template_name" name="template_name" value="${template.template_name}" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="edit_logo">Company Logo (Optional - leave empty to keep current)</label>
                                    ${template.logo_path ? `<p style="margin-bottom: 10px; color: #666;">Current logo: <img src="${template.logo_path}?v=${Date.now()}" style="max-height: 40px; vertical-align: middle;"></p>` : ''}
                                    <input type="file" id="edit_logo" name="logo" accept="image/jpeg,image/png,image/jpg">
                                </div>
                                
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label for="edit_company_name">Company Name</label>
                                        <input type="text" id="edit_company_name" name="company_name" value="${template.company_name}" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="edit_company_email">Email</label>
                                        <input type="email" id="edit_company_email" name="company_email" value="${template.company_email}" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="edit_company_mobile">Mobile</label>
                                        <input type="text" id="edit_company_mobile" name="company_mobile" value="${template.company_mobile}" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="edit_company_website">Website</label>
                                        <input type="text" id="edit_company_website" name="company_website" value="${template.company_website}" required>
                                    </div>
                                </div>
                                
                                <h3 style="margin: 20px 0 15px 0; color: #333; font-size: 18px;">Bank Details</h3>
                                
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label for="edit_bank_name">Account Name</label>
                                        <input type="text" id="edit_bank_name" name="bank_name" value="${template.bank_name}" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="edit_account_number">Account Number</label>
                                        <input type="text" id="edit_account_number" name="account_number" value="${template.account_number}" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="edit_sort_code">Sort Code</label>
                                        <input type="text" id="edit_sort_code" name="sort_code" value="${template.sort_code}" required>
                                    </div>
                                </div>
                                
                                <div style="margin-top: 30px; text-align: right;">
                                    <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
                                    <button type="submit" class="btn btn-success">Update Template</button>
                                </div>
                            </form>
                        `;
                        document.getElementById('editFormContainer').innerHTML = formHtml;
                        document.getElementById('editModal').style.display = 'block';
                    }
                });
        }
        
        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }
        
        // Close modals on outside click
        window.onclick = function(event) {
            const addModal = document.getElementById('addModal');
            const editModal = document.getElementById('editModal');
            if (event.target == addModal) {
                closeAddModal();
            }
            if (event.target == editModal) {
                closeEditModal();
            }
        }
    </script>
</body>
</html>