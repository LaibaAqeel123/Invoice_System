<?php
require_once 'config.php';
requireLogin();

header('Content-Type: application/json');

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id > 0) {
    $stmt = $conn->prepare("SELECT * FROM company_templates WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $template = $result->fetch_assoc();
        echo json_encode([
            'success' => true,
            'template' => $template
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Template not found'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid template ID'
    ]);
}
?>