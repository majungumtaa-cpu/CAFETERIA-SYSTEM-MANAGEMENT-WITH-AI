<?php
// suppliers.php - With price display & compact modal
require_once 'config.php';
check_login();

if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'employee') {
    header('Location: employee_dashboard.php');
    exit();
}

$message = '';
$message_type = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_supplier'])) {
        $supplier_name = mysqli_real_escape_string($conn, $_POST['supplier_name']);
        $phone = mysqli_real_escape_string($conn, $_POST['phone']);
        
        $sql = "INSERT INTO supplier (supplier_name, phone) VALUES ('$supplier_name', '$phone')";
        
        if (mysqli_query($conn, $sql)) {
            $supplier_id = mysqli_insert_id($conn);
            $message = 'Supplier added successfully!';
            $message_type = 'success';
            if (function_exists('log_activity')) {
                log_activity($conn, 'add', 'supplier', $supplier_id, "name:$supplier_name");
            }
        } else {
            $message = 'Error: ' . mysqli_error($conn);
            $message_type = 'error';
        }
    }
    
    if (isset($_POST['delete_supplier'])) {
        $supplier_id = intval($_POST['supplier_id']);
        mysqli_query($conn, "DELETE FROM purchase WHERE supplier_id = $supplier_id");
        $sql = "DELETE FROM supplier WHERE id = $supplier_id";
        
        if (mysqli_query($conn, $sql)) {
            $message = 'Supplier deleted successfully!';
            $message_type = 'success';
        } else {
            $message = 'Error: ' . mysqli_error($conn);
            $message_type = 'error';
        }
    }
    
    if (isset($_POST['add_purchase'])) {
        $supplier_id = intval($_POST['purchase_supplier_id']);
        $product_id = intval($_POST['product_id']);
        $quantity = intval($_POST['quantity']);
        
        $sql = "INSERT INTO purchase (supplier_id, product_id, quantity) 
                VALUES ($supplier_id, $product_id, $quantity)";
        
        if (mysqli_query($conn, $sql)) {
            $update_sql = "UPDATE product SET quantity = quantity + $quantity WHERE id = $product_id";
            mysqli_query($conn, $update_sql);
            
            $purchase_id = mysqli_insert_id($conn);
            $message = 'Purchase order created successfully!';
            $message_type = 'success';
            if (function_exists('log_activity')) {
                log_activity($conn, 'add', 'purchase', $purchase_id, "supplier:$supplier_id;product:$product_id;qty:$quantity");
            }
        } else {
            $message = 'Error: ' . mysqli_error($conn);
            $message_type = 'error';
        }
    }
}

// Fetch all suppliers
$suppliers = mysqli_query($conn, "SELECT * FROM supplier ORDER BY supplier_name");
if (!$suppliers) die("<b>SQL Error (Suppliers):</b> " . mysqli_error($conn));

// Fetch products with prices
$products = mysqli_query($conn, "SELECT id, product_name, price, quantity FROM product ORDER BY product_name");
if (!$products) die("<b>SQL Error (Products):</b> " . mysqli_error($conn));

// Fetch recent purchases with product prices
$purchases = mysqli_query($conn, "
    SELECT pu.*, s.supplier_name, p.product_name, 
           p.price as unit_price,
           (pu.quantity * p.price) as total_price
    FROM purchase pu
    JOIN supplier s ON pu.supplier_id = s.id
    JOIN product p ON pu.product_id = p.id
    ORDER BY pu.id DESC
    LIMIT 10
");
if (!$purchases) die("<b>SQL Error (Purchases):</b> " . mysqli_error($conn));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suppliers - Cafeteria Management</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <?php include 'sidebar.php'; ?>
        
        <main class="main-content">
            <div class="page-header">
                <h1><i class="fas fa-truck"></i> Supplier Management</h1>
                <div style="display: flex; gap: 10px;">
                    <button onclick="document.getElementById('addPurchaseModal').style.display='flex'" 
                            class="btn btn-success">
                        <i class="fas fa-shopping-cart"></i> New Purchase
                    </button>
                    <button onclick="document.getElementById('addSupplierModal').style.display='flex'" 
                            class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add Supplier
                    </button>
                </div>
            </div>
            
            <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
            <?php endif; ?>
            
            <!-- Suppliers List -->
            <div class="card">
                <div class="card-header">
                    <h2>All Suppliers</h2>
                    <span><?php echo mysqli_num_rows($suppliers); ?> suppliers</span>
                </div>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Supplier Name</th>
                                <th>Contact</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($supplier = mysqli_fetch_assoc($suppliers)): ?>
                            <tr>
                                <td>#<?php echo str_pad($supplier['id'], 3, '0', STR_PAD_LEFT); ?></td>
                                <td><strong><?php echo htmlspecialchars($supplier['supplier_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($supplier['phone']); ?></td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="supplier_id" value="<?php echo $supplier['id']; ?>">
                                        <button type="submit" name="delete_supplier" 
                                                class="btn btn-sm btn-danger"
                                                onclick="return confirm('Delete this supplier?')">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php if(mysqli_num_rows($suppliers) == 0): ?>
                            <tr><td colspan="4" style="text-align: center;">No suppliers found</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Recent Purchases with Price -->
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-history"></i> Recent Purchases</h2>
                </div>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Purchase #</th>
                                <th>Supplier</th>
                                <th>Product</th>
                                <th>Quantity</th>
                                <th>Unit Price</th>
                                <th>Total</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $grand_total = 0;
                            while($purchase = mysqli_fetch_assoc($purchases)): 
                                $grand_total += $purchase['total_price'];
                            ?>
                            <tr>
                                <td>#<?php echo str_pad($purchase['id'], 3, '0', STR_PAD_LEFT); ?></td>
                                <td><?php echo htmlspecialchars($purchase['supplier_name']); ?></td>
                                <td><?php echo htmlspecialchars($purchase['product_name']); ?></td>
                                <td><?php echo $purchase['quantity']; ?></td>
                                <td>$<?php echo number_format($purchase['unit_price'], 2); ?></td>
                                <td><strong style="color: #E07B39;">$<?php echo number_format($purchase['total_price'], 2); ?></strong></td>
                                <td><?php echo date('M d, Y'); ?></td>
                            </tr>
                            <?php endwhile; ?>
                            <?php if(mysqli_num_rows($purchases) == 0): ?>
                            <tr><td colspan="7" style="text-align: center;">No purchases found</td></tr>
                            <?php else: ?>
                            <tr style="background-color: #f8f9fa; font-weight: bold;">
                                <td colspan="5" style="text-align: right;">Grand Total:</td>
                                <td style="color: #2ecc71;">$<?php echo number_format($grand_total, 2); ?></td>
                                <td></td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Add Supplier Modal -->
    <div id="addSupplierModal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); justify-content: center; align-items: center;">
        <div style="background-color: white; padding: 20px; width: 90%; max-width: 420px; border-radius: 8px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h2 style="margin: 0; font-size: 1.3em;">Add New Supplier</h2>
                <span style="cursor: pointer; font-size: 22px;" 
                      onclick="document.getElementById('addSupplierModal').style.display='none'">&times;</span>
            </div>
            
            <form method="POST">
                <div class="form-group" style="margin-bottom: 12px;">
                    <label style="font-size: 13px;">Supplier Name *</label>
                    <input type="text" name="supplier_name" class="form-control" required>
                </div>
                <div class="form-group" style="margin-bottom: 12px;">
                    <label style="font-size: 13px;">Phone Number *</label>
                    <input type="tel" name="phone" class="form-control" required>
                </div>
                <div style="display: flex; gap: 8px;">
                    <button type="submit" name="add_supplier" class="btn btn-primary" style="flex: 1;">
                        <i class="fas fa-save"></i> Save Supplier
                    </button>
                    <button type="button" class="btn btn-secondary" 
                            onclick="document.getElementById('addSupplierModal').style.display='none'">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Add Purchase Modal (COMPACT) -->
    <div id="addPurchaseModal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); justify-content: center; align-items: center; padding: 15px; box-sizing: border-box;">
        <div style="background-color: white; padding: 20px; width: 100%; max-width: 440px; border-radius: 8px; max-height: 95vh; overflow-y: auto; box-sizing: border-box;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h2 style="margin: 0; font-size: 1.3em;">Create Purchase Order</h2>
                <span style="cursor: pointer; font-size: 22px;" 
                      onclick="document.getElementById('addPurchaseModal').style.display='none'">&times;</span>
            </div>
            
            <form method="POST">
                <div class="form-group" style="margin-bottom: 10px;">
                    <label style="font-size: 13px;">Supplier *</label>
                    <select name="purchase_supplier_id" class="form-control" required>
                        <option value="">Select Supplier</option>
                        <?php 
                        mysqli_data_seek($suppliers, 0);
                        while($supplier = mysqli_fetch_assoc($suppliers)): 
                        ?>
                        <option value="<?php echo $supplier['id']; ?>">
                            <?php echo htmlspecialchars($supplier['supplier_name']); ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group" style="margin-bottom: 10px;">
                    <label style="font-size: 13px;">Product *</label>
                    <select name="product_id" id="purchaseProduct" class="form-control" required onchange="updatePurchaseTotal()">
                        <option value="">Select Product</option>
                        <?php 
                        mysqli_data_seek($products, 0);
                        while($product = mysqli_fetch_assoc($products)): 
                        ?>
                        <option value="<?php echo $product['id']; ?>" 
                                data-price="<?php echo $product['price']; ?>"
                                data-stock="<?php echo $product['quantity']; ?>">
                            <?php echo htmlspecialchars($product['product_name']); ?> 
                            ($<?php echo number_format($product['price'], 2); ?> — Stock: <?php echo $product['quantity']; ?>)
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group" style="margin-bottom: 12px;">
                    <label style="font-size: 13px;">Quantity *</label>
                    <input type="number" name="quantity" id="purchaseQuantity" class="form-control" 
                           min="1" required value="1" oninput="updatePurchaseTotal()" onchange="updatePurchaseTotal()">
                </div>
                
                <!-- COMPACT PRICE DISPLAY -->
                <div style="background: #fff8f0; padding: 12px 15px; border-radius: 6px; border-left: 4px solid #E07B39; margin-bottom: 12px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                        <span style="color: #E07B39; font-size: 13px; font-weight: 600;">Unit Price:</span>
                        <span id="purchaseUnitPrice" style="font-size: 1em; font-weight: bold; color: #333;">$0.00</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="color: #E07B39; font-size: 13px; font-weight: 600;">Total Cost:</span>
                        <span id="purchaseTotal" style="font-size: 1.4em; font-weight: bold; color: #2ecc71;">$0.00</span>
                    </div>
                </div>
                
                <div style="display: flex; gap: 8px;">
                    <button type="submit" name="add_purchase" class="btn btn-primary" style="flex: 1;">
                        <i class="fas fa-shopping-cart"></i> Create Purchase
                    </button>
                    <button type="button" class="btn btn-secondary" 
                            onclick="document.getElementById('addPurchaseModal').style.display='none'">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
    function updatePurchaseTotal() {
        var select = document.getElementById('purchaseProduct');
        var qty = document.getElementById('purchaseQuantity').value;
        var selectedOption = select.options[select.selectedIndex];
        var price = parseFloat(selectedOption.getAttribute('data-price')) || 0;
        var quantity = parseInt(qty) || 0;
        var total = price * quantity;
        
        document.getElementById('purchaseUnitPrice').textContent = '$' + price.toFixed(2);
        document.getElementById('purchaseTotal').textContent = '$' + total.toFixed(2);
    }
    
    // Close modal when clicking outside
    window.onclick = function(event) {
        var purchaseModal = document.getElementById('addPurchaseModal');
        var supplierModal = document.getElementById('addSupplierModal');
        
        if (event.target == purchaseModal) {
            purchaseModal.style.display = 'none';
        }
        if (event.target == supplierModal) {
            supplierModal.style.display = 'none';
        }
    }
    </script>
</body>
</html>