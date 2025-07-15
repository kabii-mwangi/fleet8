<?php
require_once 'config.php';
requireAuth();
requirePermission('maintenance_view');

$success = '';
$error = '';

// Handle form submissions
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add' && hasPermission('maintenance_edit')) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO vehicle_maintenance (vehicle_id, maintenance_type, description, cost, maintenance_date, mileage_at_maintenance, mechanic_name, notes, office_id, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                (int)$_POST['vehicle_id'],
                $_POST['maintenance_type'],
                $_POST['description'],
                (float)$_POST['cost'],
                $_POST['maintenance_date'],
                !empty($_POST['mileage_at_maintenance']) ? (int)$_POST['mileage_at_maintenance'] : null,
                $_POST['mechanic_name'],
                $_POST['notes'],
                getUserOfficeId(),
                $_SESSION['user_id']
            ]);
            $success = "Maintenance record added successfully!";
        } catch(PDOException $e) {
            $error = "Error adding maintenance record: " . $e->getMessage();
        }
    }
    
    if ($action === 'edit' && hasPermission('maintenance_edit')) {
        try {
            $stmt = $pdo->prepare("
                UPDATE vehicle_maintenance 
                SET vehicle_id = ?, maintenance_type = ?, description = ?, cost = ?, maintenance_date = ?, 
                    mileage_at_maintenance = ?, mechanic_name = ?, notes = ?, status = ?
                WHERE id = ?
            ");
            $stmt->execute([
                (int)$_POST['vehicle_id'],
                $_POST['maintenance_type'],
                $_POST['description'],
                (float)$_POST['cost'],
                $_POST['maintenance_date'],
                !empty($_POST['mileage_at_maintenance']) ? (int)$_POST['mileage_at_maintenance'] : null,
                $_POST['mechanic_name'],
                $_POST['notes'],
                $_POST['status'],
                (int)$_POST['maintenance_id']
            ]);
            $success = "Maintenance record updated successfully!";
        } catch(PDOException $e) {
            $error = "Error updating maintenance record: " . $e->getMessage();
        }
    }
    
    if ($action === 'delete' && hasPermission('maintenance_delete')) {
        try {
            $stmt = $pdo->prepare("DELETE FROM vehicle_maintenance WHERE id = ?");
            $stmt->execute([(int)$_POST['maintenance_id']]);
            $success = "Maintenance record deleted successfully!";
        } catch(PDOException $e) {
            $error = "Error deleting maintenance record: " . $e->getMessage();
        }
    }
}

try {
    // Get maintenance records with office filtering
    $officeFilter = getOfficeFilterSQL('vm', false);
    $stmt = $pdo->query("
        SELECT vm.*, v.registration_number, v.make, v.model, v.year, o.name as office_name,
               u.full_name as created_by_name
        FROM vehicle_maintenance vm
        JOIN vehicles v ON vm.vehicle_id = v.id
        LEFT JOIN offices o ON vm.office_id = o.id
        LEFT JOIN users u ON vm.created_by = u.id
        WHERE 1=1 $officeFilter
        ORDER BY vm.maintenance_date DESC, vm.created_at DESC
    ");
    $maintenanceRecords = $stmt->fetchAll();

    // Get vehicles for form dropdown with office filtering
    $vehicleFilter = getOfficeFilterSQL('v', false);
    $stmt = $pdo->query("
        SELECT v.id, v.registration_number, v.make, v.model, v.current_mileage 
        FROM vehicles v 
        WHERE v.status IN ('active', 'maintenance') $vehicleFilter
        ORDER BY v.registration_number
    ");
    $vehicles = $stmt->fetchAll();

    // Calculate summary statistics
    $totalCost = array_sum(array_column($maintenanceRecords, 'cost'));
    $totalRecords = count($maintenanceRecords);
    $avgCost = $totalRecords > 0 ? $totalCost / $totalRecords : 0;
    
    // Current month statistics
    $currentMonth = date('Y-m');
    $monthlyRecords = array_filter($maintenanceRecords, function($record) use ($currentMonth) {
        return strpos($record['maintenance_date'], $currentMonth) === 0;
    });
    $monthlyCost = array_sum(array_column($monthlyRecords, 'cost'));

} catch(PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vehicle Maintenance - Fleet Fuel Management</title>
    <meta name="description" content="Manage vehicle maintenance records, track costs, and generate reports">
    <link rel="stylesheet" href="styles.css">
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            text-align: center;
        }
        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: #2563eb;
        }
        .stat-label {
            color: #64748b;
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }
        .maintenance-type-scheduled { background-color: #dbeafe; color: #1e40af; }
        .maintenance-type-repair { background-color: #fef3c7; color: #d97706; }
        .maintenance-type-emergency { background-color: #fee2e2; color: #dc2626; }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1>Vehicle Maintenance</h1>
            <p>Track and manage vehicle maintenance records</p>
        </div>

        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Statistics Summary -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?php echo $totalRecords; ?></div>
                <div class="stat-label">Total Records</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo formatCurrency($totalCost); ?></div>
                <div class="stat-label">Total Cost</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo formatCurrency($avgCost); ?></div>
                <div class="stat-label">Average Cost</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo formatCurrency($monthlyCost); ?></div>
                <div class="stat-label">This Month</div>
            </div>
        </div>

        <?php if (hasPermission('maintenance_edit')): ?>
        <!-- Add Maintenance Form -->
        <div class="section">
            <h2>Add Maintenance Record</h2>
            <form method="POST" class="maintenance-form">
                <input type="hidden" name="action" value="add">
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                    <div class="form-group">
                        <label for="vehicle_id">Vehicle</label>
                        <select name="vehicle_id" class="form-control" required>
                            <option value="">Select Vehicle</option>
                            <?php foreach ($vehicles as $vehicle): ?>
                                <option value="<?php echo $vehicle['id']; ?>" data-mileage="<?php echo $vehicle['current_mileage']; ?>">
                                    <?php echo htmlspecialchars($vehicle['registration_number'] . ' - ' . $vehicle['make'] . ' ' . $vehicle['model']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="maintenance_type">Type</label>
                        <select name="maintenance_type" class="form-control" required>
                            <option value="scheduled">Scheduled Maintenance</option>
                            <option value="repair">Repair</option>
                            <option value="emergency">Emergency</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="maintenance_date">Date</label>
                        <input type="date" name="maintenance_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="cost">Cost (KSH)</label>
                        <input type="number" name="cost" class="form-control" step="0.01" min="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="mileage_at_maintenance">Mileage</label>
                        <input type="number" name="mileage_at_maintenance" id="mileage_input" class="form-control" min="0">
                    </div>
                    
                    <div class="form-group">
                        <label for="mechanic_name">Mechanic/Service Provider</label>
                        <input type="text" name="mechanic_name" class="form-control">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea name="description" class="form-control" rows="3" required placeholder="Describe the maintenance work performed..."></textarea>
                </div>
                
                <div class="form-group">
                    <label for="notes">Notes</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="Additional notes..."></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary">Add Maintenance Record</button>
            </form>
        </div>
        <?php endif; ?>

        <!-- Maintenance Records List -->
        <div class="section">
            <h2>Maintenance Records</h2>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Vehicle</th>
                            <th>Type</th>
                            <th>Description</th>
                            <th>Cost</th>
                            <th>Mileage</th>
                            <th>Mechanic</th>
                            <th>Status</th>
                            <th>Office</th>
                            <?php if (hasPermission('maintenance_edit') || hasPermission('maintenance_delete')): ?>
                                <th>Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($maintenanceRecords)): ?>
                            <tr>
                                <td colspan="<?php echo (hasPermission('maintenance_edit') || hasPermission('maintenance_delete')) ? '10' : '9'; ?>" class="no-data">No maintenance records found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($maintenanceRecords as $record): ?>
                                <tr>
                                    <td><?php echo formatDate($record['maintenance_date']); ?></td>
                                    <td>
                                        <div class="vehicle-info">
                                            <span class="registration"><?php echo htmlspecialchars($record['registration_number']); ?></span>
                                            <span class="vehicle-details"><?php echo htmlspecialchars($record['make'] . ' ' . $record['model']); ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge maintenance-type-<?php echo $record['maintenance_type']; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $record['maintenance_type'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars(substr($record['description'], 0, 50)) . (strlen($record['description']) > 50 ? '...' : ''); ?></td>
                                    <td><?php echo formatCurrency($record['cost']); ?></td>
                                    <td><?php echo $record['mileage_at_maintenance'] ? number_format($record['mileage_at_maintenance']) . ' km' : '-'; ?></td>
                                    <td><?php echo htmlspecialchars($record['mechanic_name'] ?: '-'); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $record['status']; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $record['status'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($record['office_name']); ?></td>
                                    <?php if (hasPermission('maintenance_edit') || hasPermission('maintenance_delete')): ?>
                                        <td>
                                            <?php if (hasPermission('maintenance_edit')): ?>
                                                <button onclick="editMaintenance(<?php echo htmlspecialchars(json_encode($record)); ?>)" class="btn btn-primary" style="padding: 0.25rem 0.5rem; font-size: 0.8rem; margin-right: 0.5rem;">Edit</button>
                                            <?php endif; ?>
                                            <?php if (hasPermission('maintenance_delete')): ?>
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this maintenance record?');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="maintenance_id" value="<?php echo $record['id']; ?>">
                                                    <button type="submit" class="btn btn-danger" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;">Delete</button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Edit Maintenance Modal -->
    <?php if (hasPermission('maintenance_edit')): ?>
    <div id="editModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 2rem; border-radius: 8px; width: 90%; max-width: 800px; max-height: 80%; overflow-y: auto;">
            <h3>Edit Maintenance Record</h3>
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="maintenance_id" id="editMaintenanceId">
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                    <div class="form-group">
                        <label for="editVehicleId">Vehicle</label>
                        <select name="vehicle_id" id="editVehicleId" class="form-control" required>
                            <?php foreach ($vehicles as $vehicle): ?>
                                <option value="<?php echo $vehicle['id']; ?>">
                                    <?php echo htmlspecialchars($vehicle['registration_number'] . ' - ' . $vehicle['make'] . ' ' . $vehicle['model']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="editMaintenanceType">Type</label>
                        <select name="maintenance_type" id="editMaintenanceType" class="form-control" required>
                            <option value="scheduled">Scheduled Maintenance</option>
                            <option value="repair">Repair</option>
                            <option value="emergency">Emergency</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="editMaintenanceDate">Date</label>
                        <input type="date" name="maintenance_date" id="editMaintenanceDate" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="editCost">Cost (KSH)</label>
                        <input type="number" name="cost" id="editCost" class="form-control" step="0.01" min="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="editMileage">Mileage</label>
                        <input type="number" name="mileage_at_maintenance" id="editMileage" class="form-control" min="0">
                    </div>
                    
                    <div class="form-group">
                        <label for="editMechanic">Mechanic/Service Provider</label>
                        <input type="text" name="mechanic_name" id="editMechanic" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label for="editStatus">Status</label>
                        <select name="status" id="editStatus" class="form-control" required>
                            <option value="planned">Planned</option>
                            <option value="in_progress">In Progress</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="editDescription">Description</label>
                    <textarea name="description" id="editDescription" class="form-control" rows="3" required></textarea>
                </div>
                
                <div class="form-group">
                    <label for="editNotes">Notes</label>
                    <textarea name="notes" id="editNotes" class="form-control" rows="2"></textarea>
                </div>
                
                <div style="margin-top: 2rem; display: flex; gap: 1rem;">
                    <button type="submit" class="btn btn-primary">Update Record</button>
                    <button type="button" onclick="closeEditModal()" class="btn btn-secondary">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <script>
        // Auto-populate mileage when vehicle is selected
        document.querySelector('select[name="vehicle_id"]').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const mileage = selectedOption.getAttribute('data-mileage');
            if (mileage) {
                document.getElementById('mileage_input').value = mileage;
            }
        });

        function editMaintenance(record) {
            document.getElementById('editMaintenanceId').value = record.id;
            document.getElementById('editVehicleId').value = record.vehicle_id;
            document.getElementById('editMaintenanceType').value = record.maintenance_type;
            document.getElementById('editMaintenanceDate').value = record.maintenance_date;
            document.getElementById('editCost').value = record.cost;
            document.getElementById('editMileage').value = record.mileage_at_maintenance || '';
            document.getElementById('editMechanic').value = record.mechanic_name || '';
            document.getElementById('editStatus').value = record.status;
            document.getElementById('editDescription').value = record.description;
            document.getElementById('editNotes').value = record.notes || '';
            document.getElementById('editModal').style.display = 'block';
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        // Close modal when clicking outside
        document.getElementById('editModal')?.addEventListener('click', function(e) {
            if (e.target === this) {
                closeEditModal();
            }
        });
    </script>
</body>
</html>