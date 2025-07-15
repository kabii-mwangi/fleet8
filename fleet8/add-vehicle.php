<?php
require_once 'config.php';
requireAuth();

// Handle form submission
if ($_POST) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO vehicles (registration_number, make, model, year, category_id, assigned_employee_id, department, current_mileage) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $_POST['registration_number'],
            $_POST['make'],
            $_POST['model'],
            (int)$_POST['year'],
            (int)$_POST['category_id'],
            !empty($_POST['assigned_employee_id']) ? (int)$_POST['assigned_employee_id'] : null,
            $_POST['department'],
            (int)$_POST['current_mileage']
        ]);
        
        header('Location: vehicles.php?success=1');
        exit;
    } catch(PDOException $e) {
        $error = "Error adding vehicle: " . $e->getMessage();
    }
}

// Get categories and employees for form
try {
    $stmt = $pdo->query("SELECT * FROM vehicle_categories ORDER BY name");
    $categories = $stmt->fetchAll();

    $stmt = $pdo->query("SELECT * FROM employees ORDER BY name");
    $employees = $stmt->fetchAll();
} catch(PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Vehicle - Fleet Fuel Management</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1>Add New Vehicle</h1>
            <p>Register a new vehicle to the fleet</p>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="form-container">
            <form method="POST">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label for="registration_number">Registration Number</label>
                        <input type="text" id="registration_number" name="registration_number" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="make">Make</label>
                        <input type="text" id="make" name="make" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="model">Model</label>
                        <input type="text" id="model" name="model" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="year">Year</label>
                        <input type="number" id="year" name="year" class="form-control" min="1990" max="<?php echo date('Y') + 1; ?>" required>
                    </div>
                    
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
                            <option value="Transport">Transport</option>
                            <option value="Operations">Operations</option>
                            <option value="Administration">Administration</option>
                            <option value="Maintenance">Maintenance</option>
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
                
                <div style="margin-top: 2rem;">
                    <button type="submit" class="btn btn-primary">Add Vehicle</button>
                    <a href="vehicles.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>