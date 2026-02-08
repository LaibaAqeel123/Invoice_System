<?php
require_once 'config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Load PHPMailer
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

// Load Dompdf
require_once 'dompdf/autoload.inc.php';
use Dompdf\Dompdf;
use Dompdf\Options;

function sendInvoiceEmail($invoice_id, $recipient_email, $recipient_name = '') {
    global $conn;
    
    // Get SMTP settings
    $smtp = $conn->query("SELECT * FROM smtp_settings LIMIT 1")->fetch_assoc();
    if (!$smtp) {
        return ['success' => false, 'message' => 'SMTP settings not configured'];
    }
    
    // Get invoice details
    $invoice_query = $conn->prepare("
        SELECT i.*, c.name as customer_name, c.email as customer_email,
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
        return ['success' => false, 'message' => 'Invoice not found'];
    }
    
    // Use customer email if no recipient provided
    if (empty($recipient_email)) {
        $recipient_email = $invoice['customer_email'];
        $recipient_name = $invoice['customer_name'];
    }
    
    if (empty($recipient_email)) {
        return ['success' => false, 'message' => 'No recipient email address'];
    }
    
    // Create PHPMailer instance
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = $smtp['smtp_host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtp['smtp_username'];
        $mail->Password   = $smtp['smtp_password'];
        $mail->SMTPSecure = $smtp['smtp_encryption'];
        $mail->Port       = $smtp['smtp_port'];
        
        // Recipients
        $mail->setFrom($smtp['from_email'], $smtp['from_name']);
        $mail->addAddress($recipient_email, $recipient_name);
        $mail->addReplyTo($smtp['from_email'], $smtp['from_name']);
        
        // Generate and attach PDF
        $pdf_content = generateInvoicePDF($invoice_id);
        if ($pdf_content) {
            $mail->addStringAttachment($pdf_content, 'Invoice_' . $invoice['invoice_number'] . '.pdf', 'base64', 'application/pdf');
        }
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Invoice ' . $invoice['invoice_number'] . ' from ' . $invoice['template_company_name'];
        
        // Email body
        $mail->Body = getEmailTemplate($invoice);
        $mail->AltBody = strip_tags($mail->Body);
        
        // Send email
        $mail->send();
        
        // Update invoice email status
        $stmt = $conn->prepare("UPDATE invoices SET email_sent = 1, email_sent_at = NOW(), email_sent_to = ? WHERE id = ?");
        $stmt->bind_param("si", $recipient_email, $invoice_id);
        $stmt->execute();
        
        return ['success' => true, 'message' => 'Email sent successfully with PDF attachment'];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Email could not be sent. Error: ' . $mail->ErrorInfo];
    }
}

function sendTestEmail($recipient_email) {
    global $conn;
    
    // Get SMTP settings
    $smtp = $conn->query("SELECT * FROM smtp_settings LIMIT 1")->fetch_assoc();
    if (!$smtp) {
        return ['success' => false, 'message' => 'SMTP settings not configured'];
    }
    
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = $smtp['smtp_host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtp['smtp_username'];
        $mail->Password   = $smtp['smtp_password'];
        $mail->SMTPSecure = $smtp['smtp_encryption'];
        $mail->Port       = $smtp['smtp_port'];
        
        // Recipients
        $mail->setFrom($smtp['from_email'], $smtp['from_name']);
        $mail->addAddress($recipient_email);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Test Email from Invoice Generator';
        $mail->Body    = '<h2>SMTP Configuration Test</h2><p>If you received this email, your SMTP settings are working correctly!</p><p><strong>Sent at:</strong> ' . date('Y-m-d H:i:s') . '</p>';
        $mail->AltBody = 'SMTP Configuration Test - If you received this email, your SMTP settings are working correctly!';
        
        $mail->send();
        return ['success' => true, 'message' => 'Test email sent successfully'];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Email could not be sent. Error: ' . $mail->ErrorInfo];
    }
}

function generateInvoicePDF($invoice_id) {
    global $conn;
    
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
    $invoice_query->bind_param("i", $invoice_id);
    $invoice_query->execute();
    $invoice = $invoice_query->get_result()->fetch_assoc();
    
    if (!$invoice) {
        return false;
    }
    
    // Get invoice items
    $items = $conn->query("SELECT * FROM invoice_items WHERE invoice_id = $invoice_id");
    
    // Build HTML for PDF
    $html = getPDFHTML($invoice, $items);
    
    // Configure Dompdf
    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', true);
    $options->set('defaultFont', 'Arial');
    
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    
    // Return PDF content as string
    return $dompdf->output();
}

function getPDFHTML($invoice, $items) {
    $logo_html = '';
    if (!empty($invoice['logo_path']) && file_exists($invoice['logo_path'])) {
        $logo_data = base64_encode(file_get_contents($invoice['logo_path']));
        $logo_type = pathinfo($invoice['logo_path'], PATHINFO_EXTENSION);
        $logo_html = '<img src="data:image/' . $logo_type . ';base64,' . $logo_data . '" style="max-width: 200px; max-height: 80px;">';
    } else {
        $logo_html = '<h1 style="font-size: 32px; margin: 0;">' . htmlspecialchars($invoice['template_company_name']) . '</h1>';
    }
    
    $items_html = '';
    while ($item = $items->fetch_assoc()) {
        $items_html .= '
        <tr>
            <td style="padding: 12px; border-bottom: 1px solid #ddd;">' . htmlspecialchars($item['description']) . '</td>
            <td style="padding: 12px; border-bottom: 1px solid #ddd; text-align: center;">' . $item['quantity'] . '</td>
            <td style="padding: 12px; border-bottom: 1px solid #ddd; text-align: right;">£' . number_format($item['unit_price'], 2) . '</td>
            <td style="padding: 12px; border-bottom: 1px solid #ddd; text-align: right;">£' . number_format($item['amount'], 2) . '</td>
        </tr>';
    }
    
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body {
                font-family: Arial, sans-serif;
                font-size: 11pt;
                color: #333;
                margin: 0;
                padding: 20px;
            }
            .invoice-header {
                border-bottom: 3px solid #333;
                padding-bottom: 20px;
                margin-bottom: 30px;
            }
            .invoice-header table {
                width: 100%;
            }
            .invoice-title {
                font-size: 42px;
                font-weight: bold;
                text-align: right;
            }
            .invoice-meta {
                margin-bottom: 30px;
            }
            .invoice-meta table {
                width: 100%;
            }
            .section-heading {
                font-size: 11px;
                color: #888;
                text-transform: uppercase;
                margin-bottom: 10px;
                font-weight: 600;
            }
            table.items {
                width: 100%;
                border-collapse: collapse;
                margin: 30px 0;
            }
            table.items thead {
                background: #333;
                color: white;
            }
            table.items th {
                padding: 12px;
                text-align: left;
                font-weight: 600;
                font-size: 10pt;
            }
            .totals {
                width: 300px;
                margin-left: auto;
                border: 2px solid #333;
                margin-top: 20px;
            }
            .total-row {
                padding: 12px 20px;
                border-bottom: 1px solid #ddd;
            }
            .total-row.final {
                background: #333;
                color: white;
                font-size: 14pt;
                font-weight: bold;
            }
            .footer {
                margin-top: 40px;
                padding-top: 20px;
                border-top: 2px solid #333;
                font-size: 10pt;
                color: #555;
            }
        </style>
    </head>
    <body>
        <div class="invoice-header">
            <table>
                <tr>
                    <td style="width: 50%;">' . $logo_html . '</td>
                    <td style="width: 50%; text-align: right;">
                        <div class="invoice-title">INVOICE</div>
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="invoice-meta">
            <table>
                <tr>
                    <td style="width: 50%; vertical-align: top;">
                        <div class="section-heading">Invoice To:</div>
                        <p style="margin: 0;"><strong>' . htmlspecialchars($invoice['customer_name']) . '</strong></p>
                        ' . ($invoice['company'] ? '<p style="margin: 0;">' . htmlspecialchars($invoice['company']) . '</p>' : '') . '
                        <p style="margin: 0;">' . nl2br(htmlspecialchars($invoice['address'])) . '</p>
                    </td>
                    <td style="width: 50%; vertical-align: top; text-align: right;">
                        <p style="margin: 4px 0;"><strong>Invoice Number:</strong> ' . htmlspecialchars($invoice['invoice_number']) . '</p>
                        <p style="margin: 4px 0;"><strong>Date:</strong> ' . date('d/m/Y', strtotime($invoice['invoice_date'])) . '</p>
                        <p style="margin: 4px 0;"><strong>Terms:</strong> ' . htmlspecialchars($invoice['terms']) . '</p>
                        <p style="margin: 4px 0;"><strong>Due Date:</strong> ' . date('d/m/Y', strtotime($invoice['due_date'])) . '</p>
                    </td>
                </tr>
            </table>
        </div>
        
        <table class="items">
            <thead>
                <tr>
                    <th style="width: 50%;">Description</th>
                    <th style="width: 15%; text-align: center;">QTY</th>
                    <th style="width: 17.5%; text-align: right;">Unit Price</th>
                    <th style="width: 17.5%; text-align: right;">Amount</th>
                </tr>
            </thead>
            <tbody>
                ' . $items_html . '
            </tbody>
        </table>
        
        <div class="totals">
            <table style="width: 100%;">
                <tr class="total-row">
                    <td><strong>Total:</strong></td>
                    <td style="text-align: right;">£' . number_format($invoice['total'], 2) . '</td>
                </tr>
                <tr class="total-row final">
                    <td><strong>Balance Due:</strong></td>
                    <td style="text-align: right;">£' . number_format($invoice['balance_due'], 2) . '</td>
                </tr>
            </table>
        </div>
        
        <div class="footer">
            <p><strong>' . htmlspecialchars($invoice['template_company_name']) . '</strong></p>
            <p>Email: ' . htmlspecialchars($invoice['template_email']) . ' | Mobile: ' . htmlspecialchars($invoice['template_mobile']) . ' | Website: ' . htmlspecialchars($invoice['template_website']) . '</p>
            <p style="margin-top: 10px;"><strong>Bank Details:</strong> Account Name: ' . htmlspecialchars($invoice['bank_name']) . ' | AC NO: ' . htmlspecialchars($invoice['account_number']) . ' | Sort Code: ' . htmlspecialchars($invoice['sort_code']) . '</p>
        </div>
    </body>
    </html>
    ';
    
    return $html;
}

function getEmailTemplate($invoice) {
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body {
                font-family: Arial, sans-serif;
                line-height: 1.6;
                color: #333;
            }
            .container {
                max-width: 600px;
                margin: 0 auto;
                padding: 20px;
            }
            .header {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 30px;
                text-align: center;
                border-radius: 8px 8px 0 0;
            }
            .content {
                background: #f9f9f9;
                padding: 30px;
                border: 1px solid #e0e0e0;
            }
            .invoice-details {
                background: white;
                padding: 20px;
                border-radius: 8px;
                margin: 20px 0;
            }
            .detail-row {
                display: flex;
                justify-content: space-between;
                padding: 8px 0;
                border-bottom: 1px solid #f0f0f0;
            }
            .detail-label {
                font-weight: bold;
                color: #666;
            }
            .footer {
                background: #333;
                color: #999;
                padding: 20px;
                text-align: center;
                border-radius: 0 0 8px 8px;
                font-size: 12px;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>Invoice from ' . htmlspecialchars($invoice['template_company_name']) . '</h1>
            </div>
            
            <div class="content">
                <p>Dear ' . htmlspecialchars($invoice['customer_name']) . ',</p>
                
                <p>Thank you for your business. Please find attached your invoice in PDF format.</p>
                
                <div class="invoice-details">
                    <div class="detail-row">
                        <span class="detail-label">Invoice Number:</span>
                        <span>' . htmlspecialchars($invoice['invoice_number']) . '</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Invoice Date:</span>
                        <span>' . date('d/m/Y', strtotime($invoice['invoice_date'])) . '</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Due Date:</span>
                        <span>' . date('d/m/Y', strtotime($invoice['due_date'])) . '</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Total Amount:</span>
                        <span style="font-size: 18px; font-weight: bold; color: #4caf50;">£' . number_format($invoice['total'], 2) . '</span>
                    </div>
                </div>
                
                <p>The invoice is attached to this email as a PDF file. Please review it and let us know if you have any questions.</p>
                
                <p>Payment is due by <strong>' . date('F d, Y', strtotime($invoice['due_date'])) . '</strong>.</p>
                
                <p>We accept the following payment methods:</p>
                <ul>
                    <li>Bank Transfer</li>
                    <li>Online Payment</li>
                    <li>Check</li>
                </ul>
                
                <p>If you have already paid this invoice, please disregard this email.</p>
                
                <p>Best regards,<br>
                <strong>' . htmlspecialchars($invoice['template_company_name']) . '</strong></p>
            </div>
            
            <div class="footer">
                <p>This is an automated email. Please do not reply to this message.</p>
                <p>&copy; ' . date('Y') . ' ' . htmlspecialchars($invoice['template_company_name']) . '. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ';
    
    return $html;
}
?>