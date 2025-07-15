<?php
require_once 'config.php';
requireAuth();
requirePermission('reports_view');

// Handle form submissions for filtering
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-d');
$categoryFilter = $_GET['category_id'] ?? '';
$officeFilter = $_GET['office_id'] ?? '';

try {
    $baseOfficeFilter = getOfficeFilterSQL('p', false);
    
    $sql = "
        SELECT p.*, pc.name as category_name, o.name as office_name, u.full_name as created_by_name
        FROM products p 
        JOIN product_categories pc ON p.category_id = pc.id 
        LEFT JOIN offices o ON p.office_id = o.id 
        LEFT JOIN users u ON p.created_by = u.id 
        WHERE p.purchase_date BETWEEN ? AND ?
    ";
    
    $params = [$startDate, $endDate];
    
    // Add base office filtering for non-super admins
    if ($baseOfficeFilter) {
        $sql .= $baseOfficeFilter;
    }
    
    // Add category filter
    if ($categoryFilter) {
        $sql .= " AND p.category_id = ?";
        $params[] = (int)$categoryFilter;
    }
    
    if ($officeFilter && isSuperAdmin()) {
        $sql .= " AND p.office_id = ?";
        $params[] = (int)$officeFilter;
    }
    
    $sql .= " ORDER BY p.purchase_date DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $reportProducts = $stmt->fetchAll();

    // Calculate statistics
    $totalCost = array_sum(array_column($reportProducts, 'total_cost'));
    $totalItems = array_sum(array_column($reportProducts, 'quantity_purchased'));
    $totalRecords = count($reportProducts);
    
    // Calculate statistics by category
    $categoryStats = [];
    foreach ($reportProducts as $product) {
        $categoryId = $product['category_id'];
        if (!isset($categoryStats[$categoryId])) {
            $categoryStats[$categoryId] = [
                'category' => $product['category_name'],
                'totalCost' => 0,
                'totalItems' => 0,
                'records' => 0
            ];
        }
        $categoryStats[$categoryId]['totalCost'] += $product['total_cost'];
        $categoryStats[$categoryId]['totalItems'] += $product['quantity_purchased'];
        $categoryStats[$categoryId]['records']++;
    }

    // Get product categories
    $stmt = $pdo->query("SELECT * FROM product_categories ORDER BY name");
    $categories = $stmt->fetchAll();
    
    // Get offices (only for super admin)
    $offices = [];
    if (isSuperAdmin()) {
        $offices = getAllOffices();
    }

} catch(PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Reports - Fleet Management</title>
    <meta name="description" content="Generate detailed product purchase reports with date range and category filtering">
    <link rel="stylesheet" href="styles.css">
    <style>
        .report-filters {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            margin-bottom: 2rem;
        }
        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            align-items: end;
        }
        .stats-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
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
        .chart-section {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            margin-bottom: 2rem;
        }
        .category-breakdown {
            display: grid;
            gap: 1rem;
        }
        .category-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            background: #f8fafc;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
        }
        .category-name {
            font-weight: 600;
            color: #1e293b;
        }
        .category-stats {
            display: flex;
            gap: 1rem;
            color: #64748b;
            font-size: 0.9rem;
        }
        .export-section {
            background: white;
            padding: 1rem;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .btn-export {
            background: #0066cc;
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .btn-export:hover {
            background: #0052a3;
        }
        .reports-table {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }
        .table-header {
            background: #f8fafc;
            padding: 1rem;
            border-bottom: 1px solid #e2e8f0;
        }
        @media print {
            .report-filters, .export-section, .page-header p {
                display: none;
            }
            body {
                background: white;
                color: black;
            }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1>Product Purchase Reports</h1>
            <p>Detailed analysis of product purchases and inventory costs</p>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Filter Section -->
        <div class="report-filters">
            <h3>Report Filters</h3>
            <form method="GET" class="filter-grid">
                <div class="form-group">
                    <label for="start_date">Start Date</label>
                    <input type="date" id="start_date" name="start_date" value="<?php echo htmlspecialchars($startDate); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="end_date">End Date</label>
                    <input type="date" id="end_date" name="end_date" value="<?php echo htmlspecialchars($endDate); ?>" required>
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
                
                <?php if (isSuperAdmin()): ?>
                <div class="form-group">
                    <label for="office_id">Office</label>
                    <select id="office_id" name="office_id">
                        <option value="">All Offices</option>
                        <?php foreach ($offices as $office): ?>
                            <option value="<?php echo $office['id']; ?>" <?php echo $officeFilter == $office['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($office['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Generate Report</button>
                </div>
            </form>
        </div>

        <!-- Export Section -->
        <div class="export-section">
            <div>
                <strong>Report Period:</strong> <?php echo date('M j, Y', strtotime($startDate)); ?> - <?php echo date('M j, Y', strtotime($endDate)); ?>
            </div>
            <div>
                <button onclick="window.print()" class="btn-export">Print Report</button>
                <button onclick="exportToCSV()" class="btn-export">Export CSV</button>
            </div>
        </div>

        <!-- Summary Statistics -->
        <div class="stats-summary">
            <div class="summary-card">
                <div class="summary-value">$<?php echo number_format($totalCost, 2); ?></div>
                <div class="summary-label">Total Purchase Cost</div>
            </div>
            <div class="summary-card">
                <div class="summary-value"><?php echo number_format($totalItems); ?></div>
                <div class="summary-label">Total Items Purchased</div>
            </div>
            <div class="summary-card">
                <div class="summary-value"><?php echo $totalRecords; ?></div>
                <div class="summary-label">Purchase Records</div>
            </div>
            <div class="summary-card">
                <div class="summary-value"><?php echo count($categoryStats); ?></div>
                <div class="summary-label">Product Categories</div>
            </div>
        </div>

        <!-- Category Breakdown -->
        <?php if (!empty($categoryStats)): ?>
        <div class="chart-section">
            <h3>Purchase Breakdown by Category</h3>
            <div class="category-breakdown">
                <?php foreach ($categoryStats as $stat): ?>
                    <div class="category-item">
                        <div class="category-name"><?php echo htmlspecialchars($stat['category']); ?></div>
                        <div class="category-stats">
                            <span><strong>$<?php echo number_format($stat['totalCost'], 2); ?></strong></span>
                            <span><?php echo number_format($stat['totalItems']); ?> items</span>
                            <span><?php echo $stat['records']; ?> records</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Detailed Table -->
        <div class="reports-table">
            <div class="table-header">
                <h3>Product Purchase Details (<?php echo count($reportProducts); ?> records)</h3>
            </div>
            
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Purchase Date</th>
                            <th>Product Name</th>
                            <th>Category</th>
                            <th>Quantity</th>
                            <th>Unit Cost</th>
                            <th>Total Cost</th>
                            <th>Order Number</th>
                            <th>Supplier</th>
                            <?php if (isSuperAdmin()): ?>
                            <th>Office</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($reportProducts)): ?>
                            <tr>
                                <td colspan="<?php echo isSuperAdmin() ? '9' : '8'; ?>" style="text-align: center; padding: 2rem; color: #64748b;">
                                    No product purchases found for the selected period and filters.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($reportProducts as $product): ?>
                                <tr>
                                    <td><?php echo date('M j, Y', strtotime($product['purchase_date'])); ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($product['product_name']); ?></strong>
                                        <?php if ($product['notes']): ?>
                                            <br><small style="color: #64748b;"><?php echo htmlspecialchars(substr($product['notes'], 0, 50)); ?><?php echo strlen($product['notes']) > 50 ? '...' : ''; ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                                    <td><?php echo number_format($product['quantity_purchased']); ?></td>
                                    <td>$<?php echo number_format($product['unit_cost'], 2); ?></td>
                                    <td><strong>$<?php echo number_format($product['total_cost'], 2); ?></strong></td>
                                    <td><?php echo htmlspecialchars($product['order_number']); ?></td>
                                    <td><?php echo htmlspecialchars($product['supplier_name']); ?></td>
                                    <?php if (isSuperAdmin()): ?>
                                    <td><?php echo htmlspecialchars($product['office_name']); ?></td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function exportToCSV() {
            const table = document.querySelector('.reports-table table');
            const rows = table.querySelectorAll('tr');
            let csv = [];
            
            for (let i = 0; i < rows.length; i++) {
                const cells = rows[i].querySelectorAll('td, th');
                let row = [];
                for (let j = 0; j < cells.length; j++) {
                    let cellText = cells[j].textContent.replace(/\s+/g, ' ').trim();
                    // Escape quotes and wrap in quotes if contains comma
                    if (cellText.includes(',') || cellText.includes('"') || cellText.includes('\n')) {
                        cellText = '"' + cellText.replace(/"/g, '""') + '"';
                    }
                    row.push(cellText);
                }
                csv.push(row.join(','));
            }
            
            const csvContent = csv.join('\n');
            const blob = new Blob([csvContent], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `product_report_${new Date().toISOString().split('T')[0]}.csv`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        }
    </script>
</body>
</html>