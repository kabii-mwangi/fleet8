<?php
require_once 'config.php';
requireAuth();

// Get dashboard statistics with office filtering
try {
    $officeFilter = getOfficeFilterSQL('v', false);
    
    // Total vehicles
    $sql = "SELECT COUNT(*) as total FROM vehicles v WHERE v.status = 'active'" . $officeFilter;
    $stmt = $pdo->query($sql);
    $totalVehicles = $stmt->fetch()['total'];

    // Monthly fuel cost (current month)
    $currentMonth = date('Y-m');
    $sql = "
        SELECT SUM(fl.cost) as total 
        FROM fuel_logs fl 
        JOIN vehicles v ON fl.vehicle_id = v.id 
        WHERE DATE_FORMAT(fl.date, '%Y-%m') = ?" . $officeFilter;
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$currentMonth]);
    $monthlyFuelCost = $stmt->fetch()['total'] ?? 0;

    // Total fuel used this month
    $sql = "
        SELECT SUM(fl.fuel_quantity) as total 
        FROM fuel_logs fl 
        JOIN vehicles v ON fl.vehicle_id = v.id 
        WHERE DATE_FORMAT(fl.date, '%Y-%m') = ?" . $officeFilter;
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$currentMonth]);
    $totalFuelUsed = $stmt->fetch()['total'] ?? 0;

    // Recent fuel logs with assigned driver
    $sql = "
        SELECT fl.*, v.registration_number, v.make, v.model, vc.name as category_name, e.name as driver_name
        FROM fuel_logs fl 
        JOIN vehicles v ON fl.vehicle_id = v.id 
        JOIN vehicle_categories vc ON v.category_id = vc.id 
        LEFT JOIN employees e ON v.assigned_employee_id = e.id
        WHERE 1=1" . $officeFilter . "
        ORDER BY fl.date DESC 
        LIMIT 5
    ";
    $stmt = $pdo->query($sql);
    $recentLogs = $stmt->fetchAll();

    // Vehicle overview by category with office filtering
    $sql = "
        SELECT vc.name, vc.id, COUNT(v.id) as count 
        FROM vehicle_categories vc 
        LEFT JOIN vehicles v ON vc.id = v.category_id AND v.status = 'active'" . $officeFilter . "
        GROUP BY vc.id, vc.name
        ORDER BY vc.name
    ";
    $stmt = $pdo->query($sql);
    $vehiclesByCategory = $stmt->fetchAll();

} catch(PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

// Calculate average efficiency (simplified)
$avgEfficiency = $totalFuelUsed > 0 ? round(1000 / $totalFuelUsed, 2) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Fleet Fuel Management</title>
    <meta name="description" content="Fleet management dashboard showing fuel consumption statistics and vehicle overview">
    <link rel="stylesheet" href="styles.css">
    <style>
        .office-indicator {
            background: #e3f2fd;
            color: #1565c0;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            font-weight: 500;
            text-align: center;
        }
        .category-card {
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            text-decoration: none;
            color: inherit;
            display: block;
        }
        .category-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            text-decoration: none;
            color: inherit;
        }
        .driver-info {
            color: #666;
            font-size: 0.85rem;
            font-style: italic;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1>Dashboard</h1>
            <p>Overview of your fleet fuel management</p>
        </div>

        <!-- Office Indicator -->
        <?php if (!isSuperAdmin()): ?>
            <div class="office-indicator">
                📍 Viewing data for: <?php echo htmlspecialchars($_SESSION['office_name']); ?>
            </div>
        <?php endif; ?>

        <!-- Metric Cards -->
        <div class="metrics-grid">
            <div class="metric-card">
                <div class="metric-icon">🚗</div>
                <div class="metric-content">
                    <h3>Total Vehicles</h3>
                    <div class="metric-value"><?php echo $totalVehicles; ?></div>
                    <div class="metric-label">Active vehicles</div>
                </div>
            </div>
            
            <div class="metric-card">
                <div class="metric-icon">💰</div>
                <div class="metric-content">
                    <h3>Monthly Fuel Cost</h3>
                    <div class="metric-value"><?php echo formatCurrency($monthlyFuelCost); ?></div>
                    <div class="metric-label">This month</div>
                </div>
            </div>
            
            <div class="metric-card">
                <div class="metric-icon">⛽</div>
                <div class="metric-content">
                    <h3>Fuel Used</h3>
                    <div class="metric-value"><?php echo number_format($totalFuelUsed, 1); ?>L</div>
                    <div class="metric-label">This month</div>
                </div>
            </div>
            
            <div class="metric-card">
                <div class="metric-icon">📊</div>
                <div class="metric-content">
                    <h3>Avg Efficiency</h3>
                    <div class="metric-value"><?php echo $avgEfficiency; ?> km/L</div>
                    <div class="metric-label">Fleet average</div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="section">
            <h2>Quick Actions</h2>
            <div class="quick-actions">
                <?php if (hasPermission('fuel_logs_edit')): ?>
                    <a href="add-fuel-log.php" class="action-btn primary">
                        <span class="btn-icon">⛽</span>
                        Add Fuel Log
                    </a>
                <?php endif; ?>
                <?php if (hasPermission('vehicles_edit')): ?>
                    <a href="add-vehicle.php" class="action-btn">
                        <span class="btn-icon">🚗</span>
                        Add Vehicle
                    </a>
                <?php endif; ?>
                <?php if (hasPermission('reports_view')): ?>
                    <a href="reports.php" class="action-btn">
                        <span class="btn-icon">📊</span>
                        Generate Report
                    </a>
                <?php endif; ?>
                <a href="export.php" class="action-btn">
                    <span class="btn-icon">📁</span>
                    Export Data
                </a>
            </div>
        </div>

        <!-- Recent Fuel Logs -->
        <div class="section">
            <div class="section-header">
                <h2>Recent Fuel Logs</h2>
                <?php if (hasPermission('fuel_logs_view')): ?>
                    <a href="fuel-logs.php" class="view-all-btn">View All</a>
                <?php endif; ?>
            </div>
            
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Vehicle</th>
                            <th>Driver</th>
                            <th>Mileage</th>
                            <th>Fuel (L)</th>
                            <th>Cost</th>
                            <th>Order Details</th>
                            <th>Image</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentLogs)): ?>
                            <tr>
                                <td colspan="8" class="no-data">No fuel logs found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recentLogs as $log): ?>
                                <tr>
                                    <td><?php echo formatDate($log['date']); ?></td>
                                    <td>
                                        <div class="vehicle-info">
                                            <span class="registration"><?php echo htmlspecialchars($log['registration_number']); ?></span>
                                            <span class="vehicle-details"><?php echo htmlspecialchars($log['make'] . ' ' . $log['model']); ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($log['driver_name']): ?>
                                            <span><?php echo htmlspecialchars($log['driver_name']); ?></span>
                                        <?php else: ?>
                                            <span class="driver-info">Unassigned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo number_format($log['mileage']); ?> km</td>
                                    <td><?php echo number_format($log['fuel_quantity'], 1); ?>L</td>
                                    <td><?php echo formatCurrency($log['cost']); ?></td>
                                    <td>
                                        <?php if (!empty($log['order_details'])): ?>
                                            <div style="max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?php echo htmlspecialchars($log['order_details']); ?>">
                                                <?php echo htmlspecialchars(substr($log['order_details'], 0, 50)); ?>
                                                <?php if (strlen($log['order_details']) > 50): ?>...<?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($log['image_path']) && file_exists($log['image_path'])): ?>
                                            <img src="<?php echo htmlspecialchars($log['image_path']); ?>" 
                                                 alt="Fuel Log Image" 
                                                 style="width: 40px; height: 40px; object-fit: cover; cursor: pointer; border-radius: 4px;" 
                                                 onclick="showDashboardImageModal('<?php echo htmlspecialchars($log['image_path']); ?>')">
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Vehicle Overview -->
        <div class="section">
            <h2>Fleet Overview</h2>
            <p>Click on any category to view detailed vehicle list</p>
            <div class="vehicle-overview">
                <?php foreach ($vehiclesByCategory as $category): ?>
                    <a href="vehicle-category.php?category_id=<?php echo $category['id']; ?>&category_name=<?php echo urlencode($category['name']); ?>" class="category-card">
                        <div class="category-icon">
                            <?php 
                            $icons = [
                                'Car' => '🚗',
                                'Personal Car' => '🚙', 
                                'Motorcycle' => '🏍️',
                                'Truck' => '🚛',
                                'Van' => '🚐'
                            ];
                            echo $icons[$category['name']] ?? '🚗';
                            ?>
                        </div>
                        <div class="category-content">
                            <h3><?php echo htmlspecialchars($category['name']); ?></h3>
                            <div class="category-count"><?php echo $category['count']; ?></div>
                            <div class="category-label">vehicles</div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <script>
        // Show image modal for dashboard
        function showDashboardImageModal(imagePath) {
            const modal = document.createElement('div');
            modal.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 2000; display: flex; align-items: center; justify-content: center;';
            modal.onclick = function() { document.body.removeChild(modal); };
            
            const img = document.createElement('img');
            img.src = imagePath;
            img.style.cssText = 'max-width: 90%; max-height: 90%; object-fit: contain;';
            
            modal.appendChild(img);
            document.body.appendChild(modal);
        }
    </script>
</body>
</html>