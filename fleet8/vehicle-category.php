<?php
require_once 'config.php';
requireAuth();
requirePermission('vehicles_view');

$categoryId = (int)($_GET['category_id'] ?? 0);
$categoryName = $_GET['category_name'] ?? 'Unknown Category';

if (!$categoryId) {
    header('Location: dashboard.php');
    exit;
}

// Get vehicles in this category with office filtering
try {
    $officeFilter = getOfficeFilterSQL('v', false);
    
    $sql = "
        SELECT v.*, vc.name as category_name, e.name as employee_name, e.email as employee_email,
               o.name as office_name
        FROM vehicles v 
        JOIN vehicle_categories vc ON v.category_id = vc.id 
        LEFT JOIN employees e ON v.assigned_employee_id = e.id 
        LEFT JOIN offices o ON v.office_id = o.id 
        WHERE v.category_id = ?" . $officeFilter . "
        ORDER BY v.registration_number
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$categoryId]);
    $vehicles = $stmt->fetchAll();

    // Get category info
    $stmt = $pdo->prepare("SELECT * FROM vehicle_categories WHERE id = ?");
    $stmt->execute([$categoryId]);
    $category = $stmt->fetch();
    
    if (!$category) {
        header('Location: dashboard.php');
        exit;
    }

    // Get statistics for this category
    $totalVehicles = count($vehicles);
    $activeVehicles = count(array_filter($vehicles, function($v) { return $v['status'] === 'active'; }));
    $maintenanceVehicles = count(array_filter($vehicles, function($v) { return $v['status'] === 'maintenance'; }));

} catch(PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($category['name']); ?> Vehicles - Fleet Management</title>
    <meta name="description" content="View all <?php echo htmlspecialchars($category['name']); ?> vehicles in the fleet">
    <link rel="stylesheet" href="styles.css">
    <style>
        .category-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            text-align: center;
        }
        .category-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin: 1rem 0;
        }
        .stat-item {
            background: rgba(255,255,255,0.2);
            padding: 1rem;
            border-radius: 6px;
            text-align: center;
        }
        .stat-number {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 0.25rem;
        }
        .stat-label {
            font-size: 0.85rem;
            opacity: 0.9;
        }
        .vehicle-card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            border: 1px solid #e2e8f0;
            transition: box-shadow 0.2s;
        }
        .vehicle-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .vehicle-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 1rem;
        }
        .vehicle-reg {
            font-size: 1.2rem;
            font-weight: bold;
            color: #1e293b;
        }
        .vehicle-status {
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        .status-active { background: #d4edda; color: #155724; }
        .status-inactive { background: #f8d7da; color: #721c24; }
        .status-maintenance { background: #fff3cd; color: #856404; }
        .vehicle-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        .detail-item {
            display: flex;
            flex-direction: column;
        }
        .detail-label {
            font-size: 0.85rem;
            color: #64748b;
            margin-bottom: 0.25rem;
        }
        .detail-value {
            font-weight: 500;
            color: #1e293b;
        }
        .back-btn {
            display: inline-flex;
            align-items: center;
            color: #667eea;
            text-decoration: none;
            margin-bottom: 1rem;
            font-weight: 500;
        }
        .back-btn:hover {
            color: #5a6fd8;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <a href="dashboard.php" class="back-btn">← Back to Dashboard</a>
        
        <div class="category-header">
            <h1>
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
                <?php echo htmlspecialchars($category['name']); ?> Vehicles
            </h1>
            <p><?php echo htmlspecialchars($category['description'] ?: 'Fleet vehicle category'); ?></p>
            
            <div class="category-stats">
                <div class="stat-item">
                    <div class="stat-number"><?php echo $totalVehicles; ?></div>
                    <div class="stat-label">Total Vehicles</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo $activeVehicles; ?></div>
                    <div class="stat-label">Active</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo $maintenanceVehicles; ?></div>
                    <div class="stat-label">In Maintenance</div>
                </div>
            </div>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Vehicles List -->
        <div class="section">
            <?php if (empty($vehicles)): ?>
                <div class="alert alert-info">
                    <h3>No vehicles found</h3>
                    <p>There are no vehicles in this category for your office.</p>
                    <?php if (hasPermission('vehicles_edit')): ?>
                        <a href="add-vehicle.php" class="btn btn-primary">Add New Vehicle</a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <?php foreach ($vehicles as $vehicle): ?>
                    <div class="vehicle-card">
                        <div class="vehicle-header">
                            <div class="vehicle-reg"><?php echo htmlspecialchars($vehicle['registration_number']); ?></div>
                            <span class="vehicle-status status-<?php echo $vehicle['status']; ?>">
                                <?php echo ucfirst($vehicle['status']); ?>
                            </span>
                        </div>
                        
                        <div class="vehicle-details">
                            <div class="detail-item">
                                <span class="detail-label">Make & Model</span>
                                <span class="detail-value"><?php echo htmlspecialchars($vehicle['make'] . ' ' . $vehicle['model']); ?></span>
                            </div>
                            
                            <div class="detail-item">
                                <span class="detail-label">Year</span>
                                <span class="detail-value"><?php echo htmlspecialchars($vehicle['year']); ?></span>
                            </div>
                            
                            <div class="detail-item">
                                <span class="detail-label">Department</span>
                                <span class="detail-value"><?php echo htmlspecialchars($vehicle['department']); ?></span>
                            </div>
                            
                            <div class="detail-item">
                                <span class="detail-label">Current Mileage</span>
                                <span class="detail-value"><?php echo number_format($vehicle['current_mileage']); ?> km</span>
                            </div>
                            
                            <div class="detail-item">
                                <span class="detail-label">Assigned Driver</span>
                                <span class="detail-value">
                                    <?php if ($vehicle['employee_name']): ?>
                                        <?php echo htmlspecialchars($vehicle['employee_name']); ?>
                                        <?php if ($vehicle['employee_email']): ?>
                                            <br><small style="color: #64748b;"><?php echo htmlspecialchars($vehicle['employee_email']); ?></small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <em style="color: #64748b;">Unassigned</em>
                                    <?php endif; ?>
                                </span>
                            </div>
                            
                            <?php if (isSuperAdmin()): ?>
                                <div class="detail-item">
                                    <span class="detail-label">Office</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($vehicle['office_name']); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (hasPermission('vehicles_edit')): ?>
                            <div style="margin-top: 1rem; text-align: right;">
                                <a href="vehicles.php?edit=<?php echo $vehicle['id']; ?>" class="btn btn-secondary btn-sm">Edit Vehicle</a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- Action Buttons -->
        <div class="section" style="text-align: center;">
            <?php if (hasPermission('vehicles_edit')): ?>
                <a href="add-vehicle.php?category_id=<?php echo $categoryId; ?>" class="btn btn-primary">Add New <?php echo htmlspecialchars($category['name']); ?></a>
            <?php endif; ?>
            <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
    </div>
</body>
</html>