<?php
require_once 'config.php';
requireAuth();
requirePermission('fuel_logs_view');

// Handle form submissions
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        try {
            // Handle image upload
            $imagePath = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = 'uploads/fuel_logs/';
                $fileExtension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
                
                if (in_array($fileExtension, $allowedExtensions)) {
                    $fileName = 'fuel_log_' . time() . '_' . rand(1000, 9999) . '.' . $fileExtension;
                    $uploadPath = $uploadDir . $fileName;
                    
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath)) {
                        $imagePath = $uploadPath;
                    }
                }
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO fuel_logs (vehicle_id, date, mileage, fuel_quantity, cost, notes, order_details, image_path) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                (int)$_POST['vehicle_id'],
                $_POST['date'],
                (int)$_POST['mileage'],
                (float)$_POST['fuel_quantity'],
                (float)$_POST['cost'],
                $_POST['notes'],
                $_POST['order_details'],
                $imagePath
            ]);
            
            // Update vehicle mileage
            $stmt = $pdo->prepare("UPDATE vehicles SET current_mileage = ? WHERE id = ?");
            $stmt->execute([(int)$_POST['mileage'], (int)$_POST['vehicle_id']]);
            
            $success = "Fuel log added successfully!";
        } catch(PDOException $e) {
            $error = "Error adding fuel log: " . $e->getMessage();
        }
    }
    
    if ($action === 'edit') {
        try {
            // Handle image upload for edit
            $imagePath = $_POST['existing_image_path'] ?? null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = 'uploads/fuel_logs/';
                $fileExtension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
                
                if (in_array($fileExtension, $allowedExtensions)) {
                    $fileName = 'fuel_log_' . time() . '_' . rand(1000, 9999) . '.' . $fileExtension;
                    $uploadPath = $uploadDir . $fileName;
                    
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath)) {
                        // Delete old image if exists
                        if ($imagePath && file_exists($imagePath)) {
                            unlink($imagePath);
                        }
                        $imagePath = $uploadPath;
                    }
                }
            }
            
            $stmt = $pdo->prepare("
                UPDATE fuel_logs 
                SET vehicle_id = ?, date = ?, mileage = ?, fuel_quantity = ?, cost = ?, notes = ?, order_details = ?, image_path = ? 
                WHERE id = ?
            ");
            $stmt->execute([
                (int)$_POST['vehicle_id'],
                $_POST['date'],
                (int)$_POST['mileage'],
                (float)$_POST['fuel_quantity'],
                (float)$_POST['cost'],
                $_POST['notes'],
                $_POST['order_details'],
                $imagePath,
                (int)$_POST['log_id']
            ]);
            
            // Update vehicle mileage if needed
            $stmt = $pdo->prepare("UPDATE vehicles SET current_mileage = ? WHERE id = ?");
            $stmt->execute([(int)$_POST['mileage'], (int)$_POST['vehicle_id']]);
            
            $success = "Fuel log updated successfully!";
        } catch(PDOException $e) {
            $error = "Error updating fuel log: " . $e->getMessage();
        }
    }
    
    if ($action === 'delete') {
        try {
            $stmt = $pdo->prepare("DELETE FROM fuel_logs WHERE id = ?");
            $stmt->execute([(int)$_POST['log_id']]);
            $success = "Fuel log deleted successfully!";
        } catch(PDOException $e) {
            $error = "Error deleting fuel log: " . $e->getMessage();
        }
    }
}

// Get fuel logs with vehicle details
try {
    $stmt = $pdo->query("
        SELECT fl.*, v.registration_number, v.make, v.model, vc.name as category_name 
        FROM fuel_logs fl 
        JOIN vehicles v ON fl.vehicle_id = v.id 
        JOIN vehicle_categories vc ON v.category_id = vc.id 
        ORDER BY fl.date DESC, fl.id DESC
    ");
    $fuelLogs = $stmt->fetchAll();

    // Get vehicles for form
    $stmt = $pdo->query("
        SELECT v.*, vc.name as category_name 
        FROM vehicles v 
        JOIN vehicle_categories vc ON v.category_id = vc.id 
        WHERE v.status = 'active'
        ORDER BY v.registration_number
    ");
    $vehicles = $stmt->fetchAll();

} catch(PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fuel Logs - Fleet Fuel Management</title>
    <meta name="description" content="Track and manage fuel consumption logs for fleet vehicles with detailed cost and efficiency tracking">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1>Fuel Logs</h1>
            <p>Track fuel consumption and costs</p>
        </div>

        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Add Fuel Log Form -->
        <div class="section">
            <h2>Add Fuel Log</h2>
            <div class="form-container">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add">
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label for="vehicle_id">Vehicle</label>
                            <select id="vehicle_id" name="vehicle_id" class="form-control" required>
                                <option value="">Select Vehicle</option>
                                <?php foreach ($vehicles as $vehicle): ?>
                                    <option value="<?php echo $vehicle['id']; ?>">
                                        <?php echo htmlspecialchars($vehicle['registration_number'] . ' - ' . $vehicle['make'] . ' ' . $vehicle['model']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="date">Date</label>
                            <input type="date" id="date" name="date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="mileage">Current Mileage (km)</label>
                            <input type="number" id="mileage" name="mileage" class="form-control" min="0" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="fuel_quantity">Fuel Quantity (Liters)</label>
                            <input type="number" id="fuel_quantity" name="fuel_quantity" class="form-control" step="0.001" min="0" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="cost">Total Fuel Cost (KSH)</label>
                            <input type="number" id="cost" name="cost" class="form-control" step="0.01" min="0" required>
                        </div>
						
						<div class="form-group">
                            <label for="notes">Notes (Optional) </label>
                            <input type="text" id="notes" name="notes" class="form-control" placeholder="e.g., Refill at Rubis">
                        </div>
                        
                        <div class="form-group">
                            <label for="order_details">Order Details</label>
                            <textarea id="order_details" name="order_details" class="form-control" rows="3" placeholder="Enter order details, receipt information, etc."></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="image">Upload Receipt/Image (Optional)</label>
                            <input type="file" id="image" name="image" class="form-control" accept="image/*">
                            <small class="text-muted">Accepted formats: JPG, JPEG, PNG, GIF (Max 5MB)</small>
                        </div>

                    </div>
                    
                    <button type="submit" class="btn btn-primary">Add Fuel Log</button>
                </form>
            </div>
        </div>

        <!-- Fuel Logs List -->
        <div class="section">
            <h2>Fuel Log History</h2>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Vehicle</th>
                            <th>Mileage</th>
                            <th>Fuel (L)</th>
                            <th>Cost</th>
                            <th>Cost/Liter</th>
                            <th>Notes</th>
                            <th>Order Details</th>
                            <th>Image</th>
                            <?php if (hasPermission('fuel_logs_edit') || hasPermission('fuel_logs_delete')): ?>
                                <th>Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($fuelLogs)): ?>
                            <tr>
                                <td colspan="<?php echo (hasPermission('fuel_logs_edit') || hasPermission('fuel_logs_delete')) ? '10' : '9'; ?>" class="no-data">No fuel logs found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($fuelLogs as $log): ?>
                                <tr>
                                    <td><?php echo formatDate($log['date']); ?></td>
                                    <td>
                                        <div class="vehicle-info">
                                            <span class="registration"><?php echo htmlspecialchars($log['registration_number']); ?></span>
                                            <span class="vehicle-details"><?php echo htmlspecialchars($log['make'] . ' ' . $log['model']); ?></span>
                                        </div>
                                    </td>
                                    <td><?php echo number_format($log['mileage']); ?> km</td>
                                    <td><?php echo number_format($log['fuel_quantity'], 1); ?>L</td>
                                    <td><?php echo formatCurrency($log['cost']); ?></td>
                                    <td><?php echo formatCurrency($log['cost'] / $log['fuel_quantity']); ?></td>
                                    <td><?php echo htmlspecialchars($log['notes'] ?: '-'); ?></td>
                                    <td>
                                        <?php if (!empty($log['order_details'])): ?>
                                            <div class="order-details">
                                                <?php echo nl2br(htmlspecialchars(substr($log['order_details'], 0, 100))); ?>
                                                <?php if (strlen($log['order_details']) > 100): ?>
                                                    <span class="more-details" onclick="showFullDetails('<?php echo htmlspecialchars($log['order_details'], ENT_QUOTES); ?>')">... <a href="javascript:void(0)">Show more</a></span>
                                                <?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($log['image_path']) && file_exists($log['image_path'])): ?>
                                            <img src="<?php echo htmlspecialchars($log['image_path']); ?>" 
                                                 alt="Fuel Log Image" 
                                                 style="width: 50px; height: 50px; object-fit: cover; cursor: pointer; border-radius: 4px;" 
                                                 onclick="showImageModal('<?php echo htmlspecialchars($log['image_path']); ?>')">
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <?php if (hasPermission('fuel_logs_edit') || hasPermission('fuel_logs_delete')): ?>
                                        <td>
                                            <?php if (hasPermission('fuel_logs_edit')): ?>
                                                <button onclick="editFuelLog(<?php echo $log['id']; ?>, <?php echo $log['vehicle_id']; ?>, '<?php echo $log['date']; ?>', <?php echo $log['mileage']; ?>, <?php echo $log['fuel_quantity']; ?>, <?php echo $log['cost']; ?>, '<?php echo htmlspecialchars($log['notes'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($log['order_details'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($log['image_path'], ENT_QUOTES); ?>')" class="btn btn-primary" style="padding: 0.25rem 0.5rem; font-size: 0.8rem; margin-right: 0.5rem;">Edit</button>
                                            <?php endif; ?>
                                            <?php if (hasPermission('fuel_logs_delete')): ?>
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this fuel log?');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="log_id" value="<?php echo $log['id']; ?>">
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

    <!-- Edit Fuel Log Modal -->
    <div id="editModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 2rem; border-radius: 8px; width: 90%; max-width: 600px;">
            <h3>Edit Fuel Log</h3>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="log_id" id="editLogId">
                <input type="hidden" name="existing_image_path" id="editExistingImagePath">
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label for="editVehicleId">Vehicle</label>
                        <select id="editVehicleId" name="vehicle_id" class="form-control" required>
                            <option value="">Select Vehicle</option>
                            <?php foreach ($vehicles as $vehicle): ?>
                                <option value="<?php echo $vehicle['id']; ?>">
                                    <?php echo htmlspecialchars($vehicle['registration_number'] . ' - ' . $vehicle['make'] . ' ' . $vehicle['model']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="editDate">Date</label>
                        <input type="date" id="editDate" name="date" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="editMileage">Current Mileage (km)</label>
                        <input type="number" id="editMileage" name="mileage" class="form-control" min="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="editFuelQuantity">Fuel Quantity (Liters)</label>
                        <input type="number" id="editFuelQuantity" name="fuel_quantity" class="form-control" step="0.1" min="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="editCost">Cost (KSH)</label>
                        <input type="number" id="editCost" name="cost" class="form-control" step="0.01" min="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="editNotes">Notes (Optional)</label>
                        <input type="text" id="editNotes" name="notes" class="form-control" placeholder="e.g., Shell Station, Regular refuel">
                    </div>
                    
                    <div class="form-group">
                        <label for="editOrderDetails">Order Details</label>
                        <textarea id="editOrderDetails" name="order_details" class="form-control" rows="3" placeholder="Enter order details, receipt information, etc."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="editImage">Upload New Receipt/Image (Optional)</label>
                        <input type="file" id="editImage" name="image" class="form-control" accept="image/*">
                        <small class="text-muted">Leave empty to keep existing image</small>
                        <div id="currentImagePreview" style="margin-top: 10px;"></div>
                    </div>
                </div>
                
                <div style="margin-top: 2rem;">
                    <button type="submit" class="btn btn-primary">Update Fuel Log</button>
                    <button type="button" onclick="closeEditModal()" class="btn btn-secondary">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Auto-fill mileage based on selected vehicle
        document.getElementById('vehicle_id').addEventListener('change', function() {
            const vehicleId = this.value;
            if (vehicleId) {
                const vehicles = <?php echo json_encode($vehicles); ?>;
                const selectedVehicle = vehicles.find(v => v.id == vehicleId);
                if (selectedVehicle) {
                    document.getElementById('mileage').value = selectedVehicle.current_mileage;
                }
            }
        });

        function editFuelLog(id, vehicleId, date, mileage, fuelQuantity, cost, notes, orderDetails, imagePath) {
            document.getElementById('editLogId').value = id;
            document.getElementById('editVehicleId').value = vehicleId;
            document.getElementById('editDate').value = date;
            document.getElementById('editMileage').value = mileage;
            document.getElementById('editFuelQuantity').value = fuelQuantity;
            document.getElementById('editCost').value = cost;
            document.getElementById('editNotes').value = notes;
            document.getElementById('editOrderDetails').value = orderDetails || '';
            document.getElementById('editExistingImagePath').value = imagePath || '';
            
            // Show current image preview
            const imagePreview = document.getElementById('currentImagePreview');
            if (imagePath) {
                imagePreview.innerHTML = '<small>Current image:</small><br><img src="' + imagePath + '" style="width: 100px; height: 100px; object-fit: cover; border-radius: 4px;">';
            } else {
                imagePreview.innerHTML = '<small>No image uploaded</small>';
            }
            
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
        
        // Show full order details modal
        function showFullDetails(details) {
            alert(details); // Simple approach - you can create a proper modal if needed
        }
        
        // Show image modal
        function showImageModal(imagePath) {
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