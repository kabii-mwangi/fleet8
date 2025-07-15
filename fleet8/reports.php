<?php
require_once 'config.php';
requireAuth();
requirePermission('reports_view');

// Handle form submissions for filtering
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-d');
$vehicleFilter = $_GET['vehicle_id'] ?? '';
$categoryFilter = $_GET['category_id'] ?? '';
$officeFilter = $_GET['office_id'] ?? '';

// Get filtered fuel logs with office filtering
try {
    $baseOfficeFilter = getOfficeFilterSQL('v', false);
    
    $sql = "
        SELECT fl.*, v.registration_number, v.make, v.model, vc.name as category_name, 
               e.name as employee_name, o.name as office_name
        FROM fuel_logs fl 
        JOIN vehicles v ON fl.vehicle_id = v.id 
        JOIN vehicle_categories vc ON v.category_id = vc.id 
        LEFT JOIN employees e ON v.assigned_employee_id = e.id 
        LEFT JOIN offices o ON v.office_id = o.id 
        WHERE fl.date BETWEEN ? AND ?
    ";
    
    $params = [$startDate, $endDate];
    
    // Add base office filtering for non-super admins
    if ($baseOfficeFilter) {
        $sql .= $baseOfficeFilter;
    }
    
    // Add additional filters
    if ($vehicleFilter) {
        $sql .= " AND v.id = ?";
        $params[] = (int)$vehicleFilter;
    }
    
    if ($categoryFilter) {
        $sql .= " AND v.category_id = ?";
        $params[] = (int)$categoryFilter;
    }
    
    if ($officeFilter && isSuperAdmin()) {
        $sql .= " AND v.office_id = ?";
        $params[] = (int)$officeFilter;
    }
    
    $sql .= " ORDER BY fl.date DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $reportLogs = $stmt->fetchAll();

    // Calculate statistics
    $totalCost = array_sum(array_column($reportLogs, 'cost'));
    $totalFuel = array_sum(array_column($reportLogs, 'fuel_quantity'));
    $totalLogs = count($reportLogs);
    
    // Get unique vehicles in this period
    $uniqueVehicles = array_unique(array_column($reportLogs, 'vehicle_id'));
    $totalVehicles = count($uniqueVehicles);
    
    // Calculate efficiency by vehicle
    $vehicleStats = [];
    foreach ($reportLogs as $log) {
        $vehicleId = $log['vehicle_id'];
        if (!isset($vehicleStats[$vehicleId])) {
            $vehicleStats[$vehicleId] = [
                'vehicle' => $log['registration_number'] . ' - ' . $log['make'] . ' ' . $log['model'],
                'category' => $log['category_name'],
                'office' => $log['office_name'],
                'totalCost' => 0,
                'totalFuel' => 0,
                'logs' => []
            ];
        }
        $vehicleStats[$vehicleId]['totalCost'] += $log['cost'];
        $vehicleStats[$vehicleId]['totalFuel'] += $log['fuel_quantity'];
        $vehicleStats[$vehicleId]['logs'][] = $log;
    }

    // Get vehicles for filter dropdown (with office filtering)
    $vehicleSql = "
        SELECT v.*, vc.name as category_name, o.name as office_name 
        FROM vehicles v 
        JOIN vehicle_categories vc ON v.category_id = vc.id 
        LEFT JOIN offices o ON v.office_id = o.id 
        WHERE v.status = 'active'" . $baseOfficeFilter . "
        ORDER BY v.registration_number
    ";
    $stmt = $pdo->query($vehicleSql);
    $vehicles = $stmt->fetchAll();

    // Get vehicle categories
    $categories = getVehicleCategories();
    
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
    <title>Reports - Fleet Fuel Management</title>
    <meta name="description" content="Generate detailed fuel consumption and cost reports with date range and vehicle filtering">
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
        .office-indicator {
            background: #e3f2fd;
            color: #1565c0;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            font-weight: 500;
            text-align: center;
        }
        @media print {
            .report-filters, .navbar { display: none; }
            body { font-size: 12px; }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1>Fuel Consumption Reports</h1>
            <p>Generate detailed reports with custom date ranges and filters</p>
        </div>

        <!-- Office Indicator -->
        <?php if (!isSuperAdmin()): ?>
            <div class="office-indicator">
                📍 Report data for: <?php echo htmlspecialchars($_SESSION['office_name']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Report Filters -->
        <div class="report-filters">
            <h3>Report Filters</h3>
            <form method="GET">
                <div class="filter-grid">
                    <div class="form-group">
                        <label for="start_date">Start Date</label>
                        <input type="date" id="start_date" name="start_date" class="form-control" value="<?php echo htmlspecialchars($startDate); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="end_date">End Date</label>
                        <input type="date" id="end_date" name="end_date" class="form-control" value="<?php echo htmlspecialchars($endDate); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="category_id">Vehicle Category</label>
                        <select id="category_id" name="category_id" class="form-control">
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
                            <label for="office_id">Office/Section</label>
                            <select id="office_id" name="office_id" class="form-control">
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
                        <label for="vehicle_id">Specific Vehicle</label>
                        <select id="vehicle_id" name="vehicle_id" class="form-control">
                            <option value="">All Vehicles</option>
                            <?php foreach ($vehicles as $vehicle): ?>
                                <option value="<?php echo $vehicle['id']; ?>" <?php echo $vehicleFilter == $vehicle['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($vehicle['registration_number'] . ' - ' . $vehicle['make'] . ' ' . $vehicle['model']); ?>
                                    <?php if (isSuperAdmin()): ?>
                                        (<?php echo htmlspecialchars($vehicle['office_name']); ?>)
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Generate Report</button>
                        <button type="button" onclick="window.print()" class="btn btn-secondary">Print Report</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Summary Statistics -->
        <div class="stats-summary">
            <div class="summary-card">
                <div class="summary-value"><?php echo formatCurrency($totalCost); ?></div>
                <div class="summary-label">Total Fuel Cost</div>
            </div>
            <div class="summary-card">
                <div class="summary-value"><?php echo number_format($totalFuel, 1); ?>L</div>
                <div class="summary-label">Total Fuel Consumed</div>
            </div>
            <div class="summary-card">
                <div class="summary-value"><?php echo $totalVehicles; ?></div>
                <div class="summary-label">Vehicles Used</div>
            </div>
            <div class="summary-card">
                <div class="summary-value"><?php echo $totalLogs; ?></div>
                <div class="summary-label">Total Fuel Logs</div>
            </div>
        </div>

        <!-- Vehicle Statistics -->
        <?php if (!empty($vehicleStats)): ?>
        <div class="section">
            <h2>Vehicle Breakdown</h2>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Vehicle</th>
                            <th>Category</th>
                            <?php if (isSuperAdmin()): ?><th>Office</th><?php endif; ?>
                            <th>Total Fuel (L)</th>
                            <th>Total Cost</th>
                            <th>Average Cost/L</th>
                            <th>Number of Fill-ups</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($vehicleStats as $stats): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($stats['vehicle']); ?></td>
                                <td><?php echo htmlspecialchars($stats['category']); ?></td>
                                <?php if (isSuperAdmin()): ?>
                                    <td><?php echo htmlspecialchars($stats['office']); ?></td>
                                <?php endif; ?>
                                <td><?php echo number_format($stats['totalFuel'], 1); ?>L</td>
                                <td><?php echo formatCurrency($stats['totalCost']); ?></td>
                                <td><?php echo formatCurrency($stats['totalCost'] / $stats['totalFuel']); ?></td>
                                <td><?php echo count($stats['logs']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Detailed Logs -->
        <div class="section">
            <h2>Detailed Fuel Logs (<?php echo formatDate($startDate); ?> to <?php echo formatDate($endDate); ?>)</h2>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Vehicle</th>
                            <th>Category</th>
                            <?php if (isSuperAdmin()): ?><th>Office</th><?php endif; ?>
                            <th>Employee</th>
                            <th>Mileage</th>
                            <th>Fuel (L)</th>
                            <th>Cost</th>
                            <th>Cost/L</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($reportLogs)): ?>
                            <tr>
                                <td colspan="<?php echo isSuperAdmin() ? '10' : '9'; ?>" class="no-data">No fuel logs found for the selected period</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($reportLogs as $log): ?>
                                <tr>
                                    <td><?php echo formatDate($log['date']); ?></td>
                                    <td>
                                        <div class="vehicle-info">
                                            <span class="registration"><?php echo htmlspecialchars($log['registration_number']); ?></span>
                                            <span class="vehicle-details"><?php echo htmlspecialchars($log['make'] . ' ' . $log['model']); ?></span>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($log['category_name']); ?></td>
                                    <?php if (isSuperAdmin()): ?>
                                        <td><?php echo htmlspecialchars($log['office_name']); ?></td>
                                    <?php endif; ?>
                                    <td><?php echo $log['employee_name'] ? htmlspecialchars($log['employee_name']) : '<em>Unassigned</em>'; ?></td>
                                    <td><?php echo number_format($log['mileage']); ?> km</td>
                                    <td><?php echo number_format($log['fuel_quantity'], 1); ?>L</td>
                                    <td><?php echo formatCurrency($log['cost']); ?></td>
                                    <td><?php echo formatCurrency($log['cost'] / $log['fuel_quantity']); ?></td>
                                    <td><?php echo htmlspecialchars($log['notes'] ?: '-'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>