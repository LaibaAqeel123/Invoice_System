<?php
require_once 'config.php';
requireLogin();

$success_message = '';
$error_message = '';

// Handle Add Admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    if (empty($username) || empty($password)) {
        $error_message = "Username and password are required!";
    } elseif ($password !== $confirm_password) {
        $error_message = "Passwords do not match!";
    } elseif (strlen($password) < 6) {
        $error_message = "Password must be at least 6 characters long!";
    } else {
        // Check if username already exists
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $check_stmt->bind_param("s", $username);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error_message = "Username already exists!";
        } else {
            // Hash password and insert
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $insert_stmt = $conn->prepare("INSERT INTO users (username, password, email) VALUES (?, ?, ?)");
            $insert_stmt->bind_param("sss", $username, $hashed_password, $email);
            
            if ($insert_stmt->execute()) {
                $success_message = "Admin added successfully!";
            } else {
                $error_message = "Error adding admin: " . $conn->error;
            }
            $insert_stmt->close();
        }
        $check_stmt->close();
    }
}

// Handle Delete Admin
if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    
    // Prevent deleting yourself
    if ($delete_id == $_SESSION['user_id']) {
        $error_message = "You cannot delete your own account!";
    } else {
        $delete_stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $delete_stmt->bind_param("i", $delete_id);
        
        if ($delete_stmt->execute()) {
            $success_message = "Admin deleted successfully!";
        } else {
            $error_message = "Error deleting admin: " . $conn->error;
        }
        $delete_stmt->close();
    }
}

// Handle Edit Admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $edit_id = (int)$_POST['edit_id'];
    $username = trim($_POST['edit_username']);
    $email = trim($_POST['edit_email']);
    $new_password = $_POST['edit_password'];
    
    if (empty($username)) {
        $error_message = "Username is required!";
    } else {
        // Check if username already exists (excluding current user)
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $check_stmt->bind_param("si", $username, $edit_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error_message = "Username already exists!";
        } else {
            if (!empty($new_password)) {
                // Update with new password
                if (strlen($new_password) < 6) {
                    $error_message = "Password must be at least 6 characters long!";
                } else {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $update_stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, password = ? WHERE id = ?");
                    $update_stmt->bind_param("sssi", $username, $email, $hashed_password, $edit_id);
                    
                    if ($update_stmt->execute()) {
                        $success_message = "Admin updated successfully!";
                    } else {
                        $error_message = "Error updating admin: " . $conn->error;
                    }
                    $update_stmt->close();
                }
            } else {
                // Update without changing password
                $update_stmt = $conn->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
                $update_stmt->bind_param("ssi", $username, $email, $edit_id);
                
                if ($update_stmt->execute()) {
                    $success_message = "Admin updated successfully!";
                } else {
                    $error_message = "Error updating admin: " . $conn->error;
                }
                $update_stmt->close();
            }
        }
        $check_stmt->close();
    }
}

// Get all admins
$admins = $conn->query("SELECT id, username, email, created_at FROM users ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Admins - Invoice Generator</title>
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
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 30px;
        }
        
        .page-title {
            font-size: 28px;
            color: #333;
            margin-bottom: 10px;
        }
        
        .breadcrumb {
            color: #666;
            font-size: 14px;
            margin-bottom: 30px;
        }
        
        .breadcrumb a {
            color: #667eea;
            text-decoration: none;
        }
        
        .breadcrumb a:hover {
            text-decoration: underline;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .card {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .card-title {
            font-size: 20px;
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
            font-size: 14px;
        }
        
        .form-label.required::after {
            content: " *";
            color: #f44336;
        }
        
        .form-input {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 500;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5568d3;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .btn-danger {
            background: #f44336;
            color: white;
            padding: 6px 12px;
            font-size: 12px;
        }
        
        .btn-danger:hover {
            background: #d32f2f;
        }
        
        .btn-edit {
            background: #2196f3;
            color: white;
            padding: 6px 12px;
            font-size: 12px;
            text-decoration: none;
            display: inline-block;
            border-radius: 4px;
            border: none;
            cursor: pointer;
        }
        
        .btn-edit:hover {
            background: #1976d2;
        }
        
        .btn-back {
            background: #6c757d;
            color: white;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 20px;
        }
        
        .btn-back:hover {
            background: #5a6268;
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
        
        .action-buttons {
            display: flex;
            gap: 10px;
        }
        
        .no-data {
            text-align: center;
            padding: 40px;
            color: #999;
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
            max-width: 500px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.2);
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .modal-title {
            font-size: 20px;
            color: #333;
        }
        
        .close {
            font-size: 28px;
            font-weight: bold;
            color: #aaa;
            cursor: pointer;
            line-height: 20px;
        }
        
        .close:hover {
            color: #000;
        }
        
        .badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 500;
            background: #e3f2fd;
            color: #1976d2;
        }
        
        .help-text {
            font-size: 12px;
            color: #999;
            margin-top: 5px;
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
        <div class="breadcrumb">
            <a href="dashboard.php">Dashboard</a> / Manage Admins
        </div>
        
        <h1 class="page-title">Manage Admins</h1>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        
        <a href="dashboard.php" class="btn btn-back">← Back to Dashboard</a>
        
        <!-- Add Admin Form -->
        <div class="card">
            <h2 class="card-title">Add New Admin</h2>
            <form method="POST" action="">
                <input type="hidden" name="action" value="add">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label required">Username</label>
                        <input type="text" name="username" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-input">
                    </div>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label required">Password</label>
                        <input type="password" name="password" class="form-input" required>
                        <div class="help-text">Minimum 6 characters</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label required">Confirm Password</label>
                        <input type="password" name="confirm_password" class="form-input" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Add Admin</button>
            </form>
        </div>
        
        <!-- Admin List -->
        <div class="card">
            <h2 class="card-title">All Admins</h2>
            <?php if ($admins->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($admin = $admins->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $admin['id']; ?></td>
                            <td>
                                <?php echo htmlspecialchars($admin['username']); ?>
                                <?php if ($admin['id'] == $_SESSION['user_id']): ?>
                                    <span class="badge">You</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($admin['email'] ?? 'N/A'); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($admin['created_at'])); ?></td>
                            <td>
                                <div class="action-buttons">
                                    <button onclick="openEditModal(<?php echo $admin['id']; ?>, '<?php echo htmlspecialchars($admin['username'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($admin['email'] ?? '', ENT_QUOTES); ?>')" class="btn-edit">Edit</button>
                                    <?php if ($admin['id'] != $_SESSION['user_id']): ?>
                                        <button onclick="confirmDelete(<?php echo $admin['id']; ?>, '<?php echo htmlspecialchars($admin['username'], ENT_QUOTES); ?>')" class="btn btn-danger">Delete</button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-data">No admins found.</div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Edit Admin Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Edit Admin</h2>
                <span class="close" onclick="closeEditModal()">&times;</span>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="edit_id" id="edit_id">
                <div class="form-group">
                    <label class="form-label required">Username</label>
                    <input type="text" name="edit_username" id="edit_username" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="edit_email" id="edit_email" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">New Password</label>
                    <input type="password" name="edit_password" id="edit_password" class="form-input">
                    <div class="help-text">Leave blank to keep current password. Minimum 6 characters if changing.</div>
                </div>
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" class="btn btn-primary">Update Admin</button>
                    <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function openEditModal(id, username, email) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_username').value = username;
            document.getElementById('edit_email').value = email;
            document.getElementById('edit_password').value = '';
            document.getElementById('editModal').style.display = 'block';
        }
        
        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }
        
        function confirmDelete(id, username) {
            if (confirm('Are you sure you want to delete admin "' + username + '"? This action cannot be undone.')) {
                window.location.href = 'manage_admins.php?delete=' + id;
            }
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            var modal = document.getElementById('editModal');
            if (event.target == modal) {
                closeEditModal();
            }
        }
    </script>
</body>
</html>
