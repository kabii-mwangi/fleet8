<?php
require_once 'config.php';
requireAuth();
requirePermission('vehicles_view');

// Handle form submissions
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add' && hasPermission('vehicles_edit')) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO vehicles (registration_number, make, model, year, category_id, assigned_employee_id, department, current_mileage, office_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $_POST['registration_number'],
                $_POST['make'],
                $_POST['model'],
                (int)$_POST['year'],
                (int)$_POST['category_id'],
                !empty($_POST['assigned_employee_id']) ? (int)$_POST['assigned_employee_id'] : null,
                $_POST['department'],
                (int)$_POST['current_mileage'],
                getUserOfficeId()
            ]);
            $success = "Vehicle added successfully!";
        } catch(PDOException $e) {
            $error = "Error adding vehicle: " . $e->getMessage();
        }
    }
    
    if ($action === 'edit' && hasPermission('vehicles_edit')) {
        try {
            $stmt = $pdo->prepare("
                UPDATE vehicles 
                SET registration_number = ?, make = ?, model = ?, year = ?, category_id = ?, 
                    assigned_employee_id = ?, department = ?, current_mileage = ? 
                WHERE id = ?
            ");
            $stmt->execute([
                $_POST['registration_number'],
                $_POST['make'],
                $_POST['model'],
                (int)$_POST['year'],
                (int)$_POST['category_id'],
                !empty($_POST['assigned_employee_id']) ? (int)$_POST['assigned_employee_id'] : null,
                $_POST['department'],
                (int)$_POST['current_mileage'],
                (int)$_POST['vehicle_id']
            ]);
            $success = "Vehicle updated successfully!";
        } catch(PDOException $e) {
            $error = "Error updating vehicle: " . $e->getMessage();
        }
    }
    
    if ($action === 'update_status' && hasPermission('vehicles_edit')) {
        try {
            $stmt = $pdo->prepare("UPDATE vehicles SET status = ? WHERE id = ?");
            $stmt->execute([$_POST['status'], (int)$_POST['vehicle_id']]);
            $success = "Vehicle status updated successfully!";
        } catch(PDOException $e) {
            $error = "Error updating vehicle status: " . $e->getMessage();
        }
    }
    
    if ($action === 'delete' && hasPermission('vehicles_delete')) {
        try {
            $stmt = $pdo->prepare("DELETE FROM vehicles WHERE id = ?");
            $stmt->execute([(int)$_POST['vehicle_id']]);
            $success = "Vehicle deleted successfully!";
        } catch(PDOException $e) {
            $error = "Error deleting vehicle: " . $e->getMessage();
        }
    }
}

try {
    // Get all vehicles with office filtering
    $officeFilter = getOfficeFilterSQL('v', false);
    $stmt = $pdo->query("
        SELECT v.*, vc.name as category_name, e.name as employee_name, o.name as office_name
        FROM vehicles v 
        JOIN vehicle_categories vc ON v.category_id = vc.id 
        LEFT JOIN employees e ON v.assigned_employee_id = e.id 
        LEFT JOIN offices o ON v.office_id = o.id
        WHERE 1=1 $officeFilter
        ORDER BY v.registration_number
    ");
    $vehicles = $stmt->fetchAll();

    // Get categories for form
    $stmt = $pdo->query("SELECT * FROM vehicle_categories ORDER BY name");
    $categories = $stmt->fetchAll();

    // Get employees for form
    $stmt = $pdo->query("SELECT * FROM employees ORDER BY name");
    $employees = $stmt->fetchAll();

    // Get departments for form
    $stmt = $pdo->query("SELECT * FROM departments ORDER BY name");
    $departmentList = $stmt->fetchAll();

} catch(PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vehicle Management - Fleet Fuel Management</title>
    <meta name="description" content="Manage fleet vehicles including cars, motorcycles, and trucks with detailed information and assignments">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1>Vehicle Management</h1>
            <p>Manage your fleet vehicles</p>
        </div>

        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Add Vehicle Form -->
        <div class="section">
            <h2>Add New Vehicle</h2>
            <div class="form-container">
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label for="registration_number">Registration Number</label>
                            <input type="text" id="registration_number" name="registration_number" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="make">Make/Model</label>
                            <input type="text" id="make" name="make" class="form-control" required>
                        </div>
                        
                      <!--  <div class="form-group">
                            <label for="model">Model</label>
                            <input type="text" id="model" name="model" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="year">Year</label>
                            <input type="number" id="year" name="year" class="form-control" min="1990" max="<?php echo date('Y') + 1; ?>" required>
                        </div> -->
                        
                        <div class="form-group">
                            <label for="category_id">Category</label>
                            <select id="category_id" name="category_id" class="form-control" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="department">Department</label>
                            <select id="department" name="department" class="form-control" required>
                                <option value="">Select Department</option>
                                <?php foreach ($departmentList as $dept): ?>
                                    <option value="<?php echo htmlspecialchars($dept['name']); ?>"><?php echo htmlspecialchars($dept['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="assigned_employee_id">Assigned Employee (Optional)</label>
                            <select id="assigned_employee_id" name="assigned_employee_id" class="form-control">
                                <option value="">Unassigned</option>
                                <?php foreach ($employees as $employee): ?>
                                    <option value="<?php echo $employee['id']; ?>"><?php echo htmlspecialchars($employee['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="current_mileage">Current Mileage (km)</label>
                            <input type="number" id="current_mileage" name="current_mileage" class="form-control" min="0" value="0" required>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Add Vehicle</button>
                </form>
            </div>
        </div>

        <!-- Vehicles List -->
        <div class="section">
            <h2>Current Vehicles</h2>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Registration</th>
                            <th>Vehicle</th>
                            <th>Category</th>
                            <th>Department</th>
                            <th>Office</th>
                            <th>Assigned To</th>
                            <th>Status</th>
                            <th>Mileage</th>
                            <?php if (hasPermission('vehicles_edit') || hasPermission('vehicles_delete')): ?>
                                <th>Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($vehicles)): ?>
                            <tr>
                                <td colspan="<?php echo (hasPermission('vehicles_edit') || hasPermission('vehicles_delete')) ? '9' : '8'; ?>" class="no-data">No vehicles found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($vehicles as $vehicle): ?>
                                <tr>
                                    <td>
                                        <span class="registration"><?php echo htmlspecialchars($vehicle['registration_number']); ?></span>
                                    </td>
                                    <td>
                                        <div class="vehicle-info">
                                            <span class="registration"><?php echo htmlspecialchars($vehicle['make'] . ' ' . $vehicle['model']); ?></span>
                                            <span class="vehicle-details"><?php echo $vehicle['year']; ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-success"><?php echo htmlspecialchars($vehicle['category_name']); ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars($vehicle['department']); ?></td>
                                    <td><?php echo htmlspecialchars($vehicle['office_name']); ?></td>
                                    <td><?php echo $vehicle['employee_name'] ? htmlspecialchars($vehicle['employee_name']) : '<em>Unassigned</em>'; ?></td>
                                    <td>
                                        <?php if (hasPermission('vehicles_edit')): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="update_status">
                                                <input type="hidden" name="vehicle_id" value="<?php echo $vehicle['id']; ?>">
                                                <select name="status" onchange="this.form.submit()" class="status-select status-<?php echo $vehicle['status']; ?>">
                                                    <option value="active" <?php echo $vehicle['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                                    <option value="maintenance" <?php echo $vehicle['status'] === 'maintenance' ? 'selected' : ''; ?>>Under Maintenance</option>
                                                    <option value="inactive" <?php echo $vehicle['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                                </select>
                                            </form>
                                        <?php else: ?>
                                            <span class="status-badge status-<?php echo $vehicle['status']; ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $vehicle['status'])); ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo number_format($vehicle['current_mileage']); ?> km</td>
                                    <?php if (hasPermission('vehicles_edit') || hasPermission('vehicles_delete')): ?>
                                        <td>
                                            <?php if (hasPermission('vehicles_edit')): ?>
                                                <button onclick="editVehicle(<?php echo $vehicle['id']; ?>, '<?php echo htmlspecialchars($vehicle['registration_number'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($vehicle['make'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($vehicle['model'], ENT_QUOTES); ?>', <?php echo $vehicle['year']; ?>, <?php echo $vehicle['category_id']; ?>, <?php echo $vehicle['assigned_employee_id'] ?: 'null'; ?>, '<?php echo htmlspecialchars($vehicle['department'], ENT_QUOTES); ?>', <?php echo $vehicle['current_mileage']; ?>)" class="btn btn-primary" style="padding: 0.25rem 0.5rem; font-size: 0.8rem; margin-right: 0.5rem;">Edit</button>
                                            <?php endif; ?>
                                            <?php if (hasPermission('vehicles_delete')): ?>
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this vehicle?');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="vehicle_id" value="<?php echo $vehicle['id']; ?>">
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

    <!-- Edit Vehicle Modal -->
    <div id="editModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 2rem; border-radius: 8px; width: 90%; max-width: 600px; max-height: 80%; overflow-y: auto;">
            <h3>Edit Vehicle</h3>
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="vehicle_id" id="editVehicleId">
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label for="editRegistrationNumber">Registration Number</label>
                        <input type="text" id="editRegistrationNumber" name="registration_number" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="editMake">Make</label>
                        <input type="text" id="editMake" name="make" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="editModel">Model</label>
                        <input type="text" id="editModel" name="model" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="editYear">Year</label>
                        <input type="number" id="editYear" name="year" class="form-control" min="1990" max="<?php echo date('Y') + 1; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="editCategoryId">Category</label>
                        <select id="editCategoryId" name="category_id" class="form-control" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="editDepartment">Department</label>
                        <select id="editDepartment" name="department" class="form-control" required>
                            <option value="">Select Department</option>
                            <?php foreach ($departmentList as $dept): ?>
                                <option value="<?php echo htmlspecialchars($dept['name']); ?>"><?php echo htmlspecialchars($dept['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="editAssignedEmployeeId">Assigned Employee (Optional)</label>
                        <select id="editAssignedEmployeeId" name="assigned_employee_id" class="form-control">
                            <option value="">Unassigned</option>
                            <?php foreach ($employees as $employee): ?>
                                <option value="<?php echo $employee['id']; ?>"><?php echo htmlspecialchars($employee['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="editCurrentMileage">Current Mileage (km)</label>
                        <input type="number" id="editCurrentMileage" name="current_mileage" class="form-control" min="0" required>
                    </div>
                </div>
                
                <div style="margin-top: 2rem;">
                    <button type="submit" class="btn btn-primary">Update Vehicle</button>
                    <button type="button" onclick="closeEditModal()" class="btn btn-secondary">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function editVehicle(id, regNumber, make, model, year, categoryId, employeeId, department, mileage) {
            document.getElementById('editVehicleId').value = id;
            document.getElementById('editRegistrationNumber').value = regNumber;
            document.getElementById('editMake').value = make;
            document.getElementById('editModel').value = model;
            document.getElementById('editYear').value = year;
            document.getElementById('editCategoryId').value = categoryId;
            document.getElementById('editAssignedEmployeeId').value = employeeId || '';
            document.getElementById('editDepartment').value = department;
            document.getElementById('editCurrentMileage').value = mileage;
            document.getElementById('editModal').style.display = 'block';
        }
        
        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        document.getElementById('editModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeEditModal();
            }
        });
    </script>
</body>
</html>