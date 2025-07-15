<?php
require_once 'config.php';
requireAuth();
requirePermission('reports_view');

// Handle form submissions for filtering
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-d');
$vehicleFilter = $_GET['vehicle_id'] ?? '';
$categoryFilter = $_GET['category_id'] ?? '';
$maintenanceTypeFilter = $_GET['maintenance_type'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$officeFilter = $_GET['office_id'] ?? '';

try {
    $baseOfficeFilter = getOfficeFilterSQL('v', false);
    
    $sql = "
        SELECT vm.*, v.registration_number, v.make, v.model, vc.name as category_name, 
               o.name as office_name, u.full_name as created_by_name
        FROM vehicle_maintenance vm 
        JOIN vehicles v ON vm.vehicle_id = v.id 
        JOIN vehicle_categories vc ON v.category_id = vc.id 
        LEFT JOIN offices o ON v.office_id = o.id 
        LEFT JOIN users u ON vm.created_by = u.id 
        WHERE vm.maintenance_date BETWEEN ? AND ?
    ";
    
    $params = [$startDate, $endDate];
    
    // Add base office filtering for non-super admins
    if ($baseOfficeFilter) {
        $sql .= $baseOfficeFilter;
    }
    
    // Add vehicle filter
    if ($vehicleFilter) {
        $sql .= " AND v.id = ?";
        $params[] = (int)$vehicleFilter;
    }
    
    // Add category filter
    if ($categoryFilter) {
        $sql .= " AND v.category_id = ?";
        $params[] = (int)$categoryFilter;
    }
    
    // Add maintenance type filter
    if ($maintenanceTypeFilter) {
        $sql .= " AND vm.maintenance_type = ?";
        $params[] = $maintenanceTypeFilter;
    }
    
    // Add status filter
    if ($statusFilter) {
        $sql .= " AND vm.status = ?";
        $params[] = $statusFilter;
    }
    
    if ($officeFilter && isSuperAdmin()) {
        $sql .= " AND v.office_id = ?";
        $params[] = (int)$officeFilter;
    }
    
    $sql .= " ORDER BY vm.maintenance_date DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $reportMaintenance = $stmt->fetchAll();

    // Calculate statistics
    $totalCost = array_sum(array_column($reportMaintenance, 'cost'));
    $totalRecords = count($reportMaintenance);
    
    // Get unique vehicles
    $uniqueVehicles = array_unique(array_column($reportMaintenance, 'vehicle_id'));
    $totalVehicles = count($uniqueVehicles);
    
    // Calculate statistics by type
    $typeStats = [];
    foreach ($reportMaintenance as $maintenance) {
        $type = $maintenance['maintenance_type'];
        if (!isset($typeStats[$type])) {
            $typeStats[$type] = [
                'type' => ucfirst($type),
                'totalCost' => 0,
                'records' => 0
            ];
        }
        $typeStats[$type]['totalCost'] += $maintenance['cost'];
        $typeStats[$type]['records']++;
    }
    
    // Calculate statistics by vehicle
    $vehicleStats = [];
    foreach ($reportMaintenance as $maintenance) {
        $vehicleId = $maintenance['vehicle_id'];
        if (!isset($vehicleStats[$vehicleId])) {
            $vehicleStats[$vehicleId] = [
                'vehicle' => $maintenance['registration_number'] . ' - ' . $maintenance['make'] . ' ' . $maintenance['model'],
                'category' => $maintenance['category_name'],
                'totalCost' => 0,
                'records' => 0
            ];
        }
        $vehicleStats[$vehicleId]['totalCost'] += $maintenance['cost'];
        $vehicleStats[$vehicleId]['records']++;
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
    <title>Maintenance Reports - Fleet Management</title>
    <meta name="description" content="Generate detailed vehicle maintenance reports with date range and vehicle filtering">
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
        .breakdown {
            display: grid;
            gap: 1rem;
        }
        .breakdown-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            background: #f8fafc;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
        }
        .breakdown-name {
            font-weight: 600;
            color: #1e293b;
        }
        .breakdown-stats {
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
        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        .status-planned { background: #fef3c7; color: #92400e; }
        .status-in_progress { background: #dbeafe; color: #1e40af; }
        .status-completed { background: #d1fae5; color: #065f46; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }
        .type-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        .type-scheduled { background: #e0e7ff; color: #3730a3; }
        .type-repair { background: #fed7d7; color: #c53030; }
        .type-emergency { background: #fbb6ce; color: #97266d; }
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
            <h1>Maintenance Reports</h1>
            <p>Detailed analysis of vehicle maintenance costs and schedules</p>
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
                    <label for="category_id">Vehicle Category</label>
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
                    <label for="vehicle_id">Specific Vehicle</label>
                    <select id="vehicle_id" name="vehicle_id">
                        <option value="">All Vehicles</option>
                        <?php foreach ($vehicles as $vehicle): ?>
                            <option value="<?php echo $vehicle['id']; ?>" <?php echo $vehicleFilter == $vehicle['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($vehicle['registration_number'] . ' - ' . $vehicle['make'] . ' ' . $vehicle['model']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="maintenance_type">Maintenance Type</label>
                    <select id="maintenance_type" name="maintenance_type">
                        <option value="">All Types</option>
                        <option value="scheduled" <?php echo $maintenanceTypeFilter == 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                        <option value="repair" <?php echo $maintenanceTypeFilter == 'repair' ? 'selected' : ''; ?>>Repair</option>
                        <option value="emergency" <?php echo $maintenanceTypeFilter == 'emergency' ? 'selected' : ''; ?>>Emergency</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="">All Status</option>
                        <option value="planned" <?php echo $statusFilter == 'planned' ? 'selected' : ''; ?>>Planned</option>
                        <option value="in_progress" <?php echo $statusFilter == 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                        <option value="completed" <?php echo $statusFilter == 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="cancelled" <?php echo $statusFilter == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
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
                <div class="summary-label">Total Maintenance Cost</div>
            </div>
            <div class="summary-card">
                <div class="summary-value"><?php echo $totalRecords; ?></div>
                <div class="summary-label">Maintenance Records</div>
            </div>
            <div class="summary-card">
                <div class="summary-value"><?php echo $totalVehicles; ?></div>
                <div class="summary-label">Vehicles Serviced</div>
            </div>
            <div class="summary-card">
                <div class="summary-value">$<?php echo $totalRecords > 0 ? number_format($totalCost / $totalRecords, 2) : '0.00'; ?></div>
                <div class="summary-label">Average Cost/Record</div>
            </div>
        </div>

        <!-- Maintenance Type Breakdown -->
        <?php if (!empty($typeStats)): ?>
        <div class="chart-section">
            <h3>Maintenance Breakdown by Type</h3>
            <div class="breakdown">
                <?php foreach ($typeStats as $stat): ?>
                    <div class="breakdown-item">
                        <div class="breakdown-name"><?php echo htmlspecialchars($stat['type']); ?></div>
                        <div class="breakdown-stats">
                            <span><strong>$<?php echo number_format($stat['totalCost'], 2); ?></strong></span>
                            <span><?php echo $stat['records']; ?> records</span>
                            <span><?php echo number_format(($stat['totalCost'] / $totalCost) * 100, 1); ?>% of total</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Top Vehicles by Maintenance Cost -->
        <?php if (!empty($vehicleStats)): ?>
        <div class="chart-section">
            <h3>Top Vehicles by Maintenance Cost</h3>
            <div class="breakdown">
                <?php 
                // Sort by total cost descending and take top 10
                uasort($vehicleStats, function($a, $b) { return $b['totalCost'] - $a['totalCost']; });
                $topVehicles = array_slice($vehicleStats, 0, 10, true);
                ?>
                <?php foreach ($topVehicles as $stat): ?>
                    <div class="breakdown-item">
                        <div>
                            <div class="breakdown-name"><?php echo htmlspecialchars($stat['vehicle']); ?></div>
                            <small style="color: #64748b;"><?php echo htmlspecialchars($stat['category']); ?></small>
                        </div>
                        <div class="breakdown-stats">
                            <span><strong>$<?php echo number_format($stat['totalCost'], 2); ?></strong></span>
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
                <h3>Maintenance Details (<?php echo count($reportMaintenance); ?> records)</h3>
            </div>
            
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Vehicle</th>
                            <th>Category</th>
                            <th>Type</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Cost</th>
                            <th>Mechanic</th>
                            <?php if (isSuperAdmin()): ?>
                            <th>Office</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($reportMaintenance)): ?>
                            <tr>
                                <td colspan="<?php echo isSuperAdmin() ? '9' : '8'; ?>" style="text-align: center; padding: 2rem; color: #64748b;">
                                    No maintenance records found for the selected period and filters.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($reportMaintenance as $maintenance): ?>
                                <tr>
                                    <td><?php echo date('M j, Y', strtotime($maintenance['maintenance_date'])); ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($maintenance['registration_number']); ?></strong>
                                        <br><small style="color: #64748b;"><?php echo htmlspecialchars($maintenance['make'] . ' ' . $maintenance['model']); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($maintenance['category_name']); ?></td>
                                    <td>
                                        <span class="type-badge type-<?php echo $maintenance['maintenance_type']; ?>">
                                            <?php echo ucfirst($maintenance['maintenance_type']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($maintenance['description']); ?></strong>
                                        <?php if ($maintenance['notes']): ?>
                                            <br><small style="color: #64748b;"><?php echo htmlspecialchars(substr($maintenance['notes'], 0, 50)); ?><?php echo strlen($maintenance['notes']) > 50 ? '...' : ''; ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $maintenance['status']; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $maintenance['status'])); ?>
                                        </span>
                                    </td>
                                    <td><strong>$<?php echo number_format($maintenance['cost'], 2); ?></strong></td>
                                    <td><?php echo htmlspecialchars($maintenance['mechanic_name']); ?></td>
                                    <?php if (isSuperAdmin()): ?>
                                    <td><?php echo htmlspecialchars($maintenance['office_name']); ?></td>
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
            a.download = `maintenance_report_${new Date().toISOString().split('T')[0]}.csv`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        }
    </script>
</body>
</html>