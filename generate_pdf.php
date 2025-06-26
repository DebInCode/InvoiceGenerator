<?php
// Include TCPDF library
require_once('tcpdf/tcpdf.php');

// Include database configuration
require_once('config.php');

// Check if invoice ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<h2>Invoice not found</h2><p>Invalid invoice ID provided.</p>";
    exit;
}

$invoice_id = (int)$_GET['id'];

try {
    // Create database connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Fetch invoice data
    $stmt = $pdo->prepare("SELECT * FROM invoices WHERE id = ?");
    $stmt->execute([$invoice_id]);
    $invoice = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$invoice) {
        echo "<h2>Invoice not found</h2><p>Invoice with ID $invoice_id does not exist.</p>";
        exit;
    }
    
    // Fetch invoice items
    $stmt = $pdo->prepare("SELECT * FROM invoice_items WHERE invoice_id = ? ORDER BY id");
    $stmt->execute([$invoice_id]);
    $invoice_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch company settings
    $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM settings WHERE setting_key IN (?, ?, ?, ?, ?, ?)");
    $stmt->execute(['company_name', 'company_address', 'company_phone', 'company_email', 'currency_symbol', 'logo_path']);
    $settings = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    
    // Create new PDF document
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator('Invoice Generator');
    $pdf->SetAuthor($settings['company_name'] ?? 'Your Company');
    $pdf->SetTitle('Invoice ' . $invoice['invoice_number']);
    $pdf->SetSubject('Invoice');
    $pdf->SetKeywords('invoice, billing, payment');
    
    // Set default header data
    $pdf->SetHeaderData('', 0, '', '', array(0,0,0), array(255,255,255));
    $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
    $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
    
    // Set default monospaced font
    $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
    
    // Set margins
    $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
    
    // Set auto page breaks
    $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
    
    // Set image scale factor
    $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
    
    // Set font
    $pdf->SetFont('helvetica', '', 10);
    
    // Add a page
    $pdf->AddPage();
    
    // Get currency symbol
    $currency = $settings['currency_symbol'] ?? '$';
    
    // Company Logo and Header
    $logo_path = $settings['logo_path'] ?? 'assets/logo.png';
    if (file_exists($logo_path)) {
        $pdf->Image($logo_path, 15, 15, 40, 0, 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
    }
    
    // Company Information
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->SetXY(120, 15);
    $pdf->Cell(0, 8, $settings['company_name'] ?? 'Your Company Name', 0, 1, 'R');
    
    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetXY(120, 25);
    $pdf->Cell(0, 6, $settings['company_address'] ?? 'Your Company Address', 0, 1, 'R');
    
    $pdf->SetXY(120, 31);
    $pdf->Cell(0, 6, 'Phone: ' . ($settings['company_phone'] ?? '+1 (555) 123-4567'), 0, 1, 'R');
    
    $pdf->SetXY(120, 37);
    $pdf->Cell(0, 6, 'Email: ' . ($settings['company_email'] ?? 'info@yourcompany.com'), 0, 1, 'R');
    
    // Invoice Title
    $pdf->SetFont('helvetica', 'B', 20);
    $pdf->SetXY(15, 60);
    $pdf->Cell(0, 10, 'INVOICE', 0, 1, 'L');
    
    // Invoice Details
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->SetXY(15, 80);
    $pdf->Cell(30, 6, 'Invoice #:', 0, 0, 'L');
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 6, $invoice['invoice_number'], 0, 1, 'L');
    
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->SetXY(15, 86);
    $pdf->Cell(30, 6, 'Date:', 0, 0, 'L');
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 6, date('F j, Y', strtotime($invoice['invoice_date'])), 0, 1, 'L');
    
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->SetXY(15, 92);
    $pdf->Cell(30, 6, 'Status:', 0, 0, 'L');
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 6, ucfirst($invoice['status']), 0, 1, 'L');
    
    // Client Information
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->SetXY(15, 110);
    $pdf->Cell(0, 6, 'Bill To:', 0, 1, 'L');
    
    $pdf->SetFont('helvetica', '', 12);
    $pdf->SetXY(15, 116);
    $pdf->Cell(0, 6, $invoice['client_name'], 0, 1, 'L');
    
    // Items Table Header
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetXY(15, 140);
    $pdf->SetFillColor(240, 240, 240);
    $pdf->Cell(80, 8, 'Product/Service', 1, 0, 'L', true);
    $pdf->Cell(25, 8, 'Quantity', 1, 0, 'C', true);
    $pdf->Cell(30, 8, 'Unit Price', 1, 0, 'R', true);
    $pdf->Cell(30, 8, 'Total', 1, 1, 'R', true);
    
    // Items Table Content
    $pdf->SetFont('helvetica', '', 10);
    $y_position = 148;
    
    foreach ($invoice_items as $item) {
        if ($y_position > 250) { // Check if we need a new page
            $pdf->AddPage();
            $y_position = 20;
            
            // Repeat table header on new page
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->SetXY(15, $y_position);
            $pdf->SetFillColor(240, 240, 240);
            $pdf->Cell(80, 8, 'Product/Service', 1, 0, 'L', true);
            $pdf->Cell(25, 8, 'Quantity', 1, 0, 'C', true);
            $pdf->Cell(30, 8, 'Unit Price', 1, 0, 'R', true);
            $pdf->Cell(30, 8, 'Total', 1, 1, 'R', true);
            $y_position += 8;
        }
        
        $pdf->SetXY(15, $y_position);
        $pdf->Cell(80, 8, $item['product_name'], 1, 0, 'L');
        $pdf->Cell(25, 8, $item['quantity'], 1, 0, 'C');
        $pdf->Cell(30, 8, $currency . number_format($item['unit_price'], 2), 1, 0, 'R');
        $pdf->Cell(30, 8, $currency . number_format($item['total_amount'], 2), 1, 1, 'R');
        
        $y_position += 8;
    }
    
    // Totals Section
    $totals_y = $y_position + 10;
    
    // Subtotal
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetXY(120, $totals_y);
    $pdf->Cell(30, 6, 'Subtotal:', 0, 0, 'R');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(30, 6, $currency . number_format($invoice['subtotal'], 2), 0, 1, 'R');
    
    // Tax
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetXY(120, $totals_y + 6);
    $pdf->Cell(30, 6, 'Tax (' . $invoice['tax_rate'] . '%):', 0, 0, 'R');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(30, 6, $currency . number_format($invoice['tax_amount'], 2), 0, 1, 'R');
    
    // Discount
    if ($invoice['discount_amount'] > 0) {
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetXY(120, $totals_y + 12);
        $pdf->Cell(30, 6, 'Discount:', 0, 0, 'R');
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(30, 6, '-' . $currency . number_format($invoice['discount_amount'], 2), 0, 1, 'R');
        
        // Grand Total
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->SetXY(120, $totals_y + 18);
        $pdf->Cell(30, 8, 'Total:', 0, 0, 'R');
        $pdf->Cell(30, 8, $currency . number_format($invoice['grand_total'], 2), 0, 1, 'R');
    } else {
        // Grand Total (no discount)
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->SetXY(120, $totals_y + 12);
        $pdf->Cell(30, 8, 'Total:', 0, 0, 'R');
        $pdf->Cell(30, 8, $currency . number_format($invoice['grand_total'], 2), 0, 1, 'R');
    }
    
    // Notes Section
    if (!empty($invoice['notes'])) {
        $notes_y = $totals_y + 30;
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetXY(15, $notes_y);
        $pdf->Cell(0, 6, 'Notes:', 0, 1, 'L');
        
        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetXY(15, $notes_y + 6);
        $pdf->MultiCell(0, 6, $invoice['notes'], 0, 'L');
    }
    
    // Footer
    $pdf->SetFont('helvetica', 'I', 8);
    $pdf->SetXY(15, $pdf->GetPageHeight() - 20);
    $pdf->Cell(0, 6, 'Generated by Invoice Generator on ' . date('F j, Y \a\t g:i A'), 0, 1, 'C');
    
    // Output PDF
    $filename = 'Invoice_' . $invoice['invoice_number'] . '_' . date('Y-m-d') . '.pdf';
    $pdf->Output($filename, 'D'); // 'D' for download
    
} catch (PDOException $e) {
    echo "<h2>Database Error</h2><p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
} catch (Exception $e) {
    echo "<h2>Error</h2><p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
