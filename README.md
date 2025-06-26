# Invoice Generator 🧾

A simple yet powerful web-based invoice generator built using PHP and MySQL. This tool helps individuals and small businesses create clean, professional invoices in just a few clicks. With features like product management, tax calculation, and PDF export, it’s designed to make invoicing straightforward and efficient.

---

## 🔧 What It Does

- Add client details and invoice date
- Dynamically add multiple products or services
- Automatically calculates totals, tax (18%), discount, and grand total
- Save invoice records to the database
- Export invoice as a downloadable PDF
- (Optional) View a list of all generated invoices with filters

---

---

## ⚙️ How to Use

### Requirements

- PHP 7.4 or above
- MySQL database
- A local server like XAMPP, WAMP, or LAMP

### Setup Instructions

1. **Place the folder in your local server root**
   - For XAMPP: `C:/xampp/htdocs/invoice-generator`
   - For LAMP: `/var/www/html/invoice-generator`

2. **Import the Database**
   - Open phpMyAdmin or MySQL terminal
   - Run the SQL script from: `db/create_tables.sql`

3. **Edit Database Config**
   - Update `config.php` with your DB login info:
     ```php
     $host = 'localhost';
     $dbname = 'invoice_generator';
     $username = 'root';
     $password = '';
     ```
## 📋 Features at a Glance

- Responsive and clean UI with Bootstrap 5
- Easy itemized product/service input
- Subtotal, tax, discount, and grand total calculation
- Client notes and optional fields
- Generates PDF invoice with company branding
- Optional: Invoice list page with search/filter

---

## 🧰 Technologies Used

- PHP (Procedural)
- MySQL
- HTML5 + Bootstrap 5
- JavaScript for calculations
- TCPDF for PDF generation

---

## ✨ Ideas for Future Enhancements

- Add user authentication/login
- Email invoice directly to client
- Custom tax rates or currencies
- Export to Excel
- Recurring billing support

---


