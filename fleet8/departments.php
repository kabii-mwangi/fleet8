<?php
require_once 'config.php';
requireAuth();
requirePermission('departments_view');

// Handle form submissions
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        try {
            $stmt = $pdo->prepare("INSERT INTO departments (name, description) VALUES (?, ?)");
            $stmt->execute([
                $_POST['name'],
                $_POST['description']
            ]);
            $success = "Department added successfully!";
        } catch(PDOException $e) {
            $error = "Error adding department: " . $e->getMessage();
        }
    }
    
    if ($action === 'edit') {
        try {
            $stmt = $pdo->prepare("UPDATE departments SET name = ?, description = ? WHERE id = ?");
            $stmt->execute([
                $_POST['name'],
                $_POST['description'],
                (int)$_POST['department_id']
            ]);
            $success = "Department updated successfully!";
        } catch(PDOException $e) {
            $error = "Error updating department: " . $e->getMessage();
        }
    }
    
    if ($action === 'delete') {
        try {
            $stmt = $pdo->prepare("DELETE FROM departments WHERE id = ?");
            $stmt->execute([(int)$_POST['department_id']]);
            $success = "Department deleted successfully!";
        } catch(PDOException $e) {
            $error = "Error deleting department: " . $e->getMessage();
        }
    }
}

// Get departments
try {
    $stmt = $pdo->query("SELECT * FROM departments ORDER BY name");
    $departments = $stmt->fetchAll();
} catch(PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Department Management - Fleet Fuel Management</title>
    <meta name="description" content="Manage company departments for vehicle and employee assignments">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1>Department Management</h1>
            <p>Manage company departments</p>
        </div>

        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Add Department Form -->
        <div class="section">
            <h2>Add New Department</h2>
            <div class="form-container">
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label for="name">Department Name</label>
                            <input type="text" id="name" name="name" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Description</label>
                            <input type="text" id="description" name="description" class="form-control" placeholder="Brief description of department">
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Add Department</button>
                </form>
            </div>
        </div>

        <!-- Departments List -->
        <div class="section">
            <h2>Current Departments</h2>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Description</th>
                            <?php if (hasPermission('departments_edit') || hasPermission('departments_delete')): ?>
                                <th>Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($departments)): ?>
                            <tr>
                                <td colspan="<?php echo (hasPermission('departments_edit') || hasPermission('departments_delete')) ? '3' : '2'; ?>" class="no-data">No departments found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($departments as $department): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($department['name']); ?></td>
                                    <td><?php echo htmlspecialchars($department['description'] ?: '-'); ?></td>
                                    <?php if (hasPermission('departments_edit') || hasPermission('departments_delete')): ?>
                                        <td>
                                            <?php if (hasPermission('departments_edit')): ?>
                                                <button onclick="editDepartment(<?php echo $department['id']; ?>, '<?php echo htmlspecialchars($department['name'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($department['description'], ENT_QUOTES); ?>')" class="btn btn-primary" style="padding: 0.25rem 0.5rem; font-size: 0.8rem; margin-right: 0.5rem;">Edit</button>
                                            <?php endif; ?>
                                            <?php if (hasPermission('departments_delete')): ?>
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this department?');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="department_id" value="<?php echo $department['id']; ?>">
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

    <!-- Edit Department Modal -->
    <div id="editModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 2rem; border-radius: 8px; width: 90%; max-width: 500px;">
            <h3>Edit Department</h3>
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="department_id" id="editId">
                
                <div class="form-group">
                    <label for="editName">Department Name</label>
                    <input type="text" id="editName" name="name" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="editDescription">Description</label>
                    <input type="text" id="editDescription" name="description" class="form-control">
                </div>
                
                <div style="margin-top: 1rem;">
                    <button type="submit" class="btn btn-primary">Update Department</button>
                    <button type="button" onclick="closeEditModal()" class="btn btn-secondary">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function editDepartment(id, name, description) {
            document.getElementById('editId').value = id;
            document.getElementById('editName').value = name;
            document.getElementById('editDescription').value = description;
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