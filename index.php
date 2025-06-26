<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice Generator</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .invoice-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        .product-row {
            transition: all 0.3s ease;
        }
        .product-row:hover {
            background-color: #f8f9fa;
        }
        .remove-row {
            color: #dc3545;
            cursor: pointer;
            transition: color 0.3s ease;
        }
        .remove-row:hover {
            color: #c82333;
        }
        .total-section {
            background-color: #f8f9fa;
            border-radius: 0.375rem;
            padding: 1.5rem;
        }
        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="invoice-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1 class="mb-0"><i class="fas fa-file-invoice me-2"></i>Invoice Generator</h1>
                    <p class="mb-0 mt-2">Create professional invoices easily</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <img src="assets/logo.png" alt="Logo" height="60" class="d-none d-md-inline">
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <form id="invoiceForm" action="save_invoice.php" method="POST">
            <!-- Client Information -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-user me-2"></i>Client Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="clientName" class="form-label">Client Name *</label>
                                <input type="text" class="form-control" id="clientName" name="clientName" required>
                            </div>
                            <div class="mb-3">
                                <label for="invoiceDate" class="form-label">Invoice Date *</label>
                                <input type="date" class="form-control" id="invoiceDate" name="invoiceDate" required>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card shadow-sm">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="fas fa-calculator me-2"></i>Invoice Summary</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-6">
                                    <p class="mb-1 text-muted">Subtotal:</p>
                                    <p class="mb-1 text-muted">Tax (18%):</p>
                                    <p class="mb-1 text-muted">Discount:</p>
                                    <hr>
                                    <p class="mb-0 fw-bold">Grand Total:</p>
                                </div>
                                <div class="col-6 text-end">
                                    <p class="mb-1" id="subtotal">$0.00</p>
                                    <p class="mb-1" id="taxAmount">$0.00</p>
                                    <p class="mb-1" id="discountAmount">$0.00</p>
                                    <hr>
                                    <p class="mb-0 fw-bold fs-5 text-primary" id="grandTotal">$0.00</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Products Table -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-boxes me-2"></i>Products & Services</h5>
                    <button type="button" class="btn btn-light btn-sm" onclick="addProductRow()">
                        <i class="fas fa-plus me-1"></i>Add Item
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="productsTable">
                            <thead class="table-dark">
                                <tr>
                                    <th width="40%">Product Name</th>
                                    <th width="15%">Quantity</th>
                                    <th width="20%">Unit Price</th>
                                    <th width="20%">Total</th>
                                    <th width="5%">Action</th>
                                </tr>
                            </thead>
                            <tbody id="productsTableBody">
                                <!-- Product rows will be added here -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Discount Section -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card shadow-sm">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="mb-0"><i class="fas fa-percentage me-2"></i>Discount</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="discount" class="form-label">Discount Amount ($)</label>
                                <input type="number" class="form-control" id="discount" name="discount" value="0" min="0" step="0.01" onchange="calculateTotals()">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card shadow-sm">
                        <div class="card-header bg-secondary text-white">
                            <h5 class="mb-0"><i class="fas fa-notes-medical me-2"></i>Notes</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="notes" class="form-label">Additional Notes</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Enter any additional notes or terms..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="row mb-5">
                <div class="col-12 text-center">
                    <button type="submit" class="btn btn-primary btn-lg px-5">
                        <i class="fas fa-save me-2"></i>Generate Invoice
                    </button>
                </div>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let rowCounter = 0;

        // Initialize the page
        document.addEventListener('DOMContentLoaded', function() {
            // Set default date to today
            document.getElementById('invoiceDate').valueAsDate = new Date();
            
            // Add first product row
            addProductRow();
        });

        // Add a new product row
        function addProductRow() {
            rowCounter++;
            const tbody = document.getElementById('productsTableBody');
            const newRow = document.createElement('tr');
            newRow.className = 'product-row';
            newRow.innerHTML = `
                <td>
                    <input type="text" class="form-control" name="products[${rowCounter}][name]" placeholder="Enter product name" required>
                </td>
                <td>
                    <input type="number" class="form-control quantity-input" name="products[${rowCounter}][quantity]" value="1" min="1" step="1" onchange="calculateRowTotal(this)" onkeyup="calculateRowTotal(this)" required>
                </td>
                <td>
                    <input type="number" class="form-control price-input" name="products[${rowCounter}][price]" value="0.00" min="0" step="0.01" onchange="calculateRowTotal(this)" onkeyup="calculateRowTotal(this)" required>
                </td>
                <td>
                    <input type="text" class="form-control row-total" name="products[${rowCounter}][total]" value="$0.00" readonly>
                </td>
                <td class="text-center">
                    <i class="fas fa-trash remove-row" onclick="removeRow(this)" title="Remove row"></i>
                </td>
            `;
            tbody.appendChild(newRow);
            calculateTotals();
        }

        // Remove a product row
        function removeRow(element) {
            const row = element.closest('tr');
            row.remove();
            calculateTotals();
        }

        // Calculate total for a specific row
        function calculateRowTotal(input) {
            const row = input.closest('tr');
            const quantity = parseFloat(row.querySelector('.quantity-input').value) || 0;
            const price = parseFloat(row.querySelector('.price-input').value) || 0;
            const total = quantity * price;
            
            row.querySelector('.row-total').value = '$' + total.toFixed(2);
            calculateTotals();
        }

        // Calculate all totals
        function calculateTotals() {
            let subtotal = 0;
            
            // Calculate subtotal from all rows
            const rows = document.querySelectorAll('.product-row');
            rows.forEach(row => {
                const quantity = parseFloat(row.querySelector('.quantity-input').value) || 0;
                const price = parseFloat(row.querySelector('.price-input').value) || 0;
                subtotal += quantity * price;
            });

            // Calculate tax (18%)
            const taxRate = 0.18;
            const taxAmount = subtotal * taxRate;
            
            // Get discount
            const discount = parseFloat(document.getElementById('discount').value) || 0;
            
            // Calculate grand total
            const grandTotal = subtotal + taxAmount - discount;

            // Update display
            document.getElementById('subtotal').textContent = '$' + subtotal.toFixed(2);
            document.getElementById('taxAmount').textContent = '$' + taxAmount.toFixed(2);
            document.getElementById('discountAmount').textContent = '$' + discount.toFixed(2);
            document.getElementById('grandTotal').textContent = '$' + grandTotal.toFixed(2);
        }

        // Form validation
        document.getElementById('invoiceForm').addEventListener('submit', function(e) {
            const clientName = document.getElementById('clientName').value.trim();
            const invoiceDate = document.getElementById('invoiceDate').value;
            
            if (!clientName) {
                e.preventDefault();
                alert('Please enter a client name.');
                document.getElementById('clientName').focus();
                return false;
            }
            
            if (!invoiceDate) {
                e.preventDefault();
                alert('Please select an invoice date.');
                document.getElementById('invoiceDate').focus();
                return false;
            }

            // Check if at least one product is added
            const productRows = document.querySelectorAll('.product-row');
            if (productRows.length === 0) {
                e.preventDefault();
                alert('Please add at least one product or service.');
                return false;
            }

            // Validate each product row
            let hasValidProduct = false;
            productRows.forEach(row => {
                const name = row.querySelector('input[name*="[name]"]').value.trim();
                const quantity = parseFloat(row.querySelector('.quantity-input').value);
                const price = parseFloat(row.querySelector('.price-input').value);
                
                if (name && quantity > 0 && price > 0) {
                    hasValidProduct = true;
                }
            });

            if (!hasValidProduct) {
                e.preventDefault();
                alert('Please ensure all products have a name, quantity greater than 0, and price greater than 0.');
                return false;
            }
        });
    </script>
</body>
</html>
