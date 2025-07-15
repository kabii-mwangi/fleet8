<?php
require_once 'config.php';
requireAuth();
requirePermission('vehicles_view'); // Using vehicles permission for now

$success = '';
$error = '';

// Handle form submissions
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        try {
            $unitCost = (float)$_POST['unit_cost'];
            $quantity = (int)$_POST['quantity_purchased'];
            $totalCost = $unitCost * $quantity;
            
            $stmt = $pdo->prepare("
                INSERT INTO products (product_name, category_id, purchase_date, quantity_purchased, unit_cost, total_cost, order_number, supplier_name, notes, office_id, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $_POST['product_name'],
                (int)$_POST['category_id'],
                $_POST['purchase_date'],
                $quantity,
                $unitCost,
                $totalCost,
                $_POST['order_number'],
                $_POST['supplier_name'],
                $_POST['notes'],
                getUserOfficeId(),
                $_SESSION['user_id']
            ]);
            $success = "Product added successfully!";
        } catch(PDOException $e) {
            $error = "Error adding product: " . $e->getMessage();
        }
    }
    
    if ($action === 'edit') {
        try {
            $unitCost = (float)$_POST['unit_cost'];
            $quantity = (int)$_POST['quantity_purchased'];
            $totalCost = $unitCost * $quantity;
            
            $stmt = $pdo->prepare("
                UPDATE products 
                SET product_name = ?, category_id = ?, purchase_date = ?, quantity_purchased = ?, 
                    unit_cost = ?, total_cost = ?, order_number = ?, supplier_name = ?, notes = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $_POST['product_name'],
                (int)$_POST['category_id'],
                $_POST['purchase_date'],
                $quantity,
                $unitCost,
                $totalCost,
                $_POST['order_number'],
                $_POST['supplier_name'],
                $_POST['notes'],
                (int)$_POST['id']
            ]);
            $success = "Product updated successfully!";
        } catch(PDOException $e) {
            $error = "Error updating product: " . $e->getMessage();
        }
    }
    
    if ($action === 'delete') {
        try {
            $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([(int)$_POST['id']]);
            $success = "Product deleted successfully!";
        } catch(PDOException $e) {
            $error = "Error deleting product: " . $e->getMessage();
        }
    }
}

// Get filtering parameters
$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';
$categoryFilter = $_GET['category_id'] ?? '';
$searchTerm = $_GET['search'] ?? '';

try {
    // Build query with office filtering
    $baseOfficeFilter = getOfficeFilterSQL('p', false);
    
    $sql = "
        SELECT p.*, pc.name as category_name, o.name as office_name, u.full_name as created_by_name
        FROM products p 
        JOIN product_categories pc ON p.category_id = pc.id 
        LEFT JOIN offices o ON p.office_id = o.id 
        LEFT JOIN users u ON p.created_by = u.id 
        WHERE 1=1
    ";
    
    $params = [];
    
    // Add office filtering for non-super admins
    if ($baseOfficeFilter) {
        $sql .= $baseOfficeFilter;
    }
    
    // Add date filters
    if ($startDate) {
        $sql .= " AND p.purchase_date >= ?";
        $params[] = $startDate;
    }
    
    if ($endDate) {
        $sql .= " AND p.purchase_date <= ?";
        $params[] = $endDate;
    }
    
    // Add category filter
    if ($categoryFilter) {
        $sql .= " AND p.category_id = ?";
        $params[] = (int)$categoryFilter;
    }
    
    // Add search filter
    if ($searchTerm) {
        $sql .= " AND (p.product_name LIKE ? OR p.supplier_name LIKE ? OR p.order_number LIKE ?)";
        $searchParam = '%' . $searchTerm . '%';
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
    }
    
    $sql .= " ORDER BY p.purchase_date DESC, p.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll();

    // Get product categories
    $stmt = $pdo->query("SELECT * FROM product_categories ORDER BY name");
    $categories = $stmt->fetchAll();

} catch(PDOException $e) {
    $error = "Database error: " . $e->getMessage();
    $products = [];
    $categories = [];
}

// Calculate summary statistics
$totalCost = array_sum(array_column($products, 'total_cost'));
$totalItems = array_sum(array_column($products, 'quantity_purchased'));
$totalRecords = count($products);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Management - Fleet Management</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .filter-section {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            margin-bottom: 1.5rem;
        }
        
        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            align-items: end;
        }
        
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .summary-card {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            text-align: center;
        }
        
        .summary-value {
            font-size: 1.8rem;
            font-weight: bold;
            color: #1e293b;
            margin-bottom: 0.5rem;
        }
        
        .summary-label {
            color: #64748b;
            font-size: 0.9rem;
        }
        
        .products-table {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }
        
        .table-header {
            background: #f8fafc;
            padding: 1rem;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .btn-add {
            background: #059669;
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-add:hover {
            background: #047857;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: white;
            margin: 2% auto;
            padding: 0;
            border-radius: 8px;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-body {
            padding: 1.5rem;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        .form-group-full {
            grid-column: 1 / -1;
        }
        
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover {
            color: #000;
        }
        
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-edit, .btn-delete {
            padding: 0.25rem 0.5rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.85rem;
        }
        
        .btn-edit {
            background: #3b82f6;
            color: white;
        }
        
        .btn-delete {
            background: #ef4444;
            color: white;
        }
        
        .btn-edit:hover {
            background: #2563eb;
        }
        
        .btn-delete:hover {
            background: #dc2626;
        }
        
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .filter-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1>Product Management</h1>
            <p>Manage automotive products and supplies inventory</p>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Filter Section -->
        <div class="filter-section">
            <h3>Filter Products</h3>
            <form method="GET" class="filter-grid">
                <div class="form-group">
                    <label for="start_date">Start Date</label>
                    <input type="date" id="start_date" name="start_date" value="<?php echo htmlspecialchars($startDate); ?>">
                </div>
                
                <div class="form-group">
                    <label for="end_date">End Date</label>
                    <input type="date" id="end_date" name="end_date" value="<?php echo htmlspecialchars($endDate); ?>">
                </div>
                
                <div class="form-group">
                    <label for="category_id">Product Category</label>
                    <select id="category_id" name="category_id">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" <?php echo $categoryFilter == $category['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="search">Search</label>
                    <input type="text" id="search" name="search" placeholder="Product name, supplier, order..." value="<?php echo htmlspecialchars($searchTerm); ?>">
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="products.php" class="btn btn-secondary">Clear</a>
                </div>
            </form>
        </div>

        <!-- Summary Cards -->
        <div class="summary-cards">
            <div class="summary-card">
                <div class="summary-value">$<?php echo number_format($totalCost, 2); ?></div>
                <div class="summary-label">Total Cost</div>
            </div>
            <div class="summary-card">
                <div class="summary-value"><?php echo number_format($totalItems); ?></div>
                <div class="summary-label">Total Items</div>
            </div>
            <div class="summary-card">
                <div class="summary-value"><?php echo $totalRecords; ?></div>
                <div class="summary-label">Total Records</div>
            </div>
        </div>

        <!-- Products Table -->
        <div class="products-table">
            <div class="table-header">
                <h3>Products (<?php echo count($products); ?> records)</h3>
                <button class="btn-add" onclick="openAddModal()">Add Product</button>
            </div>
            
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Product Name</th>
                            <th>Category</th>
                            <th>Purchase Date</th>
                            <th>Quantity</th>
                            <th>Unit Cost</th>
                            <th>Total Cost</th>
                            <th>Order Number</th>
                            <th>Supplier</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($products)): ?>
                            <tr>
                                <td colspan="9" style="text-align: center; padding: 2rem; color: #64748b;">
                                    No products found. Click "Add Product" to get started.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($product['product_name']); ?></strong>
                                        <?php if ($product['notes']): ?>
                                            <br><small style="color: #64748b;"><?php echo htmlspecialchars(substr($product['notes'], 0, 50)); ?><?php echo strlen($product['notes']) > 50 ? '...' : ''; ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($product['purchase_date'])); ?></td>
                                    <td><?php echo number_format($product['quantity_purchased']); ?></td>
                                    <td>$<?php echo number_format($product['unit_cost'], 2); ?></td>
                                    <td><strong>$<?php echo number_format($product['total_cost'], 2); ?></strong></td>
                                    <td><?php echo htmlspecialchars($product['order_number']); ?></td>
                                    <td><?php echo htmlspecialchars($product['supplier_name']); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-edit" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($product)); ?>)">Edit</button>
                                            <button class="btn-delete" onclick="deleteProduct(<?php echo $product['id']; ?>)">Delete</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add/Edit Product Modal -->
    <div id="productModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Add Product</h3>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="productForm" method="POST">
                    <input type="hidden" name="action" id="formAction" value="add">
                    <input type="hidden" name="id" id="productId">
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="product_name">Product Name *</label>
                            <input type="text" id="product_name" name="product_name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="category_id">Category *</label>
                            <select id="category_id_modal" name="category_id" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="purchase_date">Purchase Date *</label>
                            <input type="date" id="purchase_date" name="purchase_date" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="quantity_purchased">Quantity Purchased *</label>
                            <input type="number" id="quantity_purchased" name="quantity_purchased" min="1" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="unit_cost">Unit Cost ($) *</label>
                            <input type="number" id="unit_cost" name="unit_cost" step="0.01" min="0" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="total_cost_display">Total Cost ($)</label>
                            <input type="text" id="total_cost_display" readonly style="background: #f8fafc; color: #64748b;">
                        </div>
                        
                        <div class="form-group">
                            <label for="order_number">Order Number</label>
                            <input type="text" id="order_number" name="order_number">
                        </div>
                        
                        <div class="form-group">
                            <label for="supplier_name">Supplier Name</label>
                            <input type="text" id="supplier_name" name="supplier_name">
                        </div>
                        
                        <div class="form-group form-group-full">
                            <label for="notes">Notes</label>
                            <textarea id="notes" name="notes" rows="3"></textarea>
                        </div>
                    </div>
                    
                    <div style="margin-top: 1.5rem; display: flex; gap: 1rem; justify-content: flex-end;">
                        <button type="button" onclick="closeModal()" class="btn btn-secondary">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Calculate total cost when unit cost or quantity changes
        function updateTotalCost() {
            const unitCost = parseFloat(document.getElementById('unit_cost').value) || 0;
            const quantity = parseInt(document.getElementById('quantity_purchased').value) || 0;
            const totalCost = unitCost * quantity;
            document.getElementById('total_cost_display').value = '$' + totalCost.toFixed(2);
        }

        document.getElementById('unit_cost').addEventListener('input', updateTotalCost);
        document.getElementById('quantity_purchased').addEventListener('input', updateTotalCost);

        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Add Product';
            document.getElementById('formAction').value = 'add';
            document.getElementById('productForm').reset();
            document.getElementById('productId').value = '';
            document.getElementById('total_cost_display').value = '$0.00';
            document.getElementById('productModal').style.display = 'block';
        }

        function openEditModal(product) {
            document.getElementById('modalTitle').textContent = 'Edit Product';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('productId').value = product.id;
            document.getElementById('product_name').value = product.product_name;
            document.getElementById('category_id_modal').value = product.category_id;
            document.getElementById('purchase_date').value = product.purchase_date;
            document.getElementById('quantity_purchased').value = product.quantity_purchased;
            document.getElementById('unit_cost').value = product.unit_cost;
            document.getElementById('order_number').value = product.order_number || '';
            document.getElementById('supplier_name').value = product.supplier_name || '';
            document.getElementById('notes').value = product.notes || '';
            updateTotalCost();
            document.getElementById('productModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('productModal').style.display = 'none';
        }

        function deleteProduct(id) {
            if (confirm('Are you sure you want to delete this product? This action cannot be undone.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('productModal');
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>