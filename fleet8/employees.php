<?php
require_once 'config.php';
requireAuth();
requirePermission('employees_view');

// Handle form submissions
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        try {
            $stmt = $pdo->prepare("INSERT INTO employees (name, email, phone, department) VALUES (?, ?, ?, ?)");
            $stmt->execute([
                $_POST['name'],
                $_POST['email'],
                $_POST['phone'],
                $_POST['department']
            ]);
            $success = "Employee added successfully!";
        } catch(PDOException $e) {
            $error = "Error adding employee: " . $e->getMessage();
        }
    }
    
    if ($action === 'edit') {
        try {
            $stmt = $pdo->prepare("UPDATE employees SET name = ?, email = ?, phone = ?, department = ? WHERE id = ?");
            $stmt->execute([
                $_POST['name'],
                $_POST['email'],
                $_POST['phone'],
                $_POST['department'],
                (int)$_POST['employee_id']
            ]);
            $success = "Employee updated successfully!";
        } catch(PDOException $e) {
            $error = "Error updating employee: " . $e->getMessage();
        }
    }
    
    if ($action === 'delete') {
        try {
            $stmt = $pdo->prepare("DELETE FROM employees WHERE id = ?");
            $stmt->execute([(int)$_POST['employee_id']]);
            $success = "Employee deleted successfully!";
        } catch(PDOException $e) {
            $error = "Error deleting employee: " . $e->getMessage();
        }
    }
}

// Get employees and departments
try {
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
    <title>Employee Management - Fleet Fuel Management</title>
    <meta name="description" content="Manage fleet employees and their vehicle assignments across different departments">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1>Employee Management</h1>
            <p>Manage employees and vehicle assignments</p>
        </div>

        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Add Employee Form -->
        <div class="section">
            <h2>Add New Employee</h2>
            <div class="form-container">
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label for="name">Full Name</label>
                            <input type="text" id="name" name="name" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone" class="form-control" placeholder="+254-xxx-xxx-xxxx">
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
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Add Employee</button>
                </form>
            </div>
        </div>

        <!-- Employees List -->
        <div class="section">
            <h2>Current Employees</h2>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Department</th>
                            <?php if (hasPermission('employees_edit') || hasPermission('employees_delete')): ?>
                                <th>Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($employees)): ?>
                            <tr>
                                <td colspan="<?php echo (hasPermission('employees_edit') || hasPermission('employees_delete')) ? '5' : '4'; ?>" class="no-data">No employees found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($employees as $employee): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($employee['name']); ?></td>
                                    <td><?php echo htmlspecialchars($employee['email']); ?></td>
                                    <td><?php echo htmlspecialchars($employee['phone'] ?: '-'); ?></td>
                                    <td>
                                        <span class="badge badge-success"><?php echo htmlspecialchars($employee['department']); ?></span>
                                    </td>
                                    <?php if (hasPermission('employees_edit') || hasPermission('employees_delete')): ?>
                                        <td>
                                            <?php if (hasPermission('employees_edit')): ?>
                                                <button onclick="editEmployee(<?php echo $employee['id']; ?>, '<?php echo htmlspecialchars($employee['name'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($employee['email'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($employee['phone'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($employee['department'], ENT_QUOTES); ?>')" class="btn btn-primary" style="padding: 0.25rem 0.5rem; font-size: 0.8rem; margin-right: 0.5rem;">Edit</button>
                                            <?php endif; ?>
                                            <?php if (hasPermission('employees_delete')): ?>
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this employee?');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="employee_id" value="<?php echo $employee['id']; ?>">
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

    <!-- Edit Employee Modal -->
    <div id="editModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 2rem; border-radius: 8px; width: 90%; max-width: 500px;">
            <h3>Edit Employee</h3>
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="employee_id" id="editEmployeeId">
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label for="editName">Full Name</label>
                        <input type="text" id="editName" name="name" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="editEmail">Email Address</label>
                        <input type="email" id="editEmail" name="email" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="editPhone">Phone Number</label>
                        <input type="tel" id="editPhone" name="phone" class="form-control" placeholder="+254-xxx-xxx-xxxx">
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
                </div>
                
                <div style="margin-top: 2rem;">
                    <button type="submit" class="btn btn-primary">Update Employee</button>
                    <button type="button" onclick="closeEditModal()" class="btn btn-secondary">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function editEmployee(id, name, email, phone, department) {
            document.getElementById('editEmployeeId').value = id;
            document.getElementById('editName').value = name;
            document.getElementById('editEmail').value = email;
            document.getElementById('editPhone').value = phone;
            document.getElementById('editDepartment').value = department;
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