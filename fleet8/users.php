<?php
require_once 'config.php';
requireAuth();
requirePermission('users_view');

$success = '';
$error = '';

// Handle form submissions
if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_user':
                if (hasPermission('users_edit')) {
                    $username = trim($_POST['username']);
                    $email = trim($_POST['email']);
                    $full_name = trim($_POST['full_name']);
                    $password = $_POST['password'];
                    $role_id = (int)$_POST['role_id'];
                    $office_id = (int)$_POST['office_id'];
                    
                    if ($username && $email && $full_name && $password && $role_id && $office_id) {
                        $password_hash = password_hash($password, PASSWORD_DEFAULT);
                        
                        try {
                            $stmt = $pdo->prepare("
                                INSERT INTO users (username, email, full_name, password_hash, role_id, office_id) 
                                VALUES (?, ?, ?, ?, ?, ?)
                            ");
                            $stmt->execute([$username, $email, $full_name, $password_hash, $role_id, $office_id]);
                            $success = "User created successfully!";
                        } catch (PDOException $e) {
                            if ($e->getCode() == 23000) {
                                $error = "Username or email already exists.";
                            } else {
                                $error = "Error creating user: " . $e->getMessage();
                            }
                        }
                    } else {
                        $error = "All fields are required.";
                    }
                } else {
                    $error = "You don't have permission to add users.";
                }
                break;
                
            case 'toggle_status':
                if (hasPermission('users_edit')) {
                    $user_id = (int)$_POST['user_id'];
                    $status = $_POST['status'] === 'active' ? 'inactive' : 'active';
                    
                    $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
                    $stmt->execute([$status, $user_id]);
                    $success = "User status updated successfully!";
                }
                break;
                
            case 'edit_user':
                if (isSuperAdmin()) {
                    $user_id = (int)$_POST['user_id'];
                    $username = trim($_POST['username']);
                    $email = trim($_POST['email']);
                    $full_name = trim($_POST['full_name']);
                    $role_id = (int)$_POST['role_id'];
                    $office_id = (int)$_POST['office_id'];
                    
                    if ($username && $email && $full_name && $role_id && $office_id) {
                        try {
                            // Check if username/email already exists for other users
                            $stmt = $pdo->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
                            $stmt->execute([$username, $email, $user_id]);
                            
                            if ($stmt->rowCount() > 0) {
                                $error = "Username or email already exists.";
                            } else {
                                $sql = "UPDATE users SET username = ?, email = ?, full_name = ?, role_id = ?, office_id = ?";
                                $params = [$username, $email, $full_name, $role_id, $office_id];
                                
                                // Update password if provided
                                if (!empty($_POST['password'])) {
                                    $sql .= ", password_hash = ?";
                                    $params[] = password_hash($_POST['password'], PASSWORD_DEFAULT);
                                }
                                
                                $sql .= " WHERE id = ?";
                                $params[] = $user_id;
                                
                                $stmt = $pdo->prepare($sql);
                                $stmt->execute($params);
                                $success = "User updated successfully!";
                            }
                        } catch (PDOException $e) {
                            $error = "Error updating user: " . $e->getMessage();
                        }
                    } else {
                        $error = "All fields are required.";
                    }
                } else {
                    $error = "You don't have permission to edit users.";
                }
                break;
                
            case 'delete_user':
                if (isSuperAdmin()) {
                    $user_id = (int)$_POST['user_id'];
                    
                    // Prevent deletion of current user
                    if ($user_id === $_SESSION['user_id']) {
                        $error = "You cannot delete your own account.";
                    } else {
                        try {
                            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                            $stmt->execute([$user_id]);
                            $success = "User deleted successfully!";
                        } catch (PDOException $e) {
                            $error = "Error deleting user: " . $e->getMessage();
                        }
                    }
                } else {
                    $error = "You don't have permission to delete users.";
                }
                break;
        }
    }
}

// Get all users with role and office info
try {
    $sql = "
        SELECT u.*, r.name as role_name, o.name as office_name 
        FROM users u 
        JOIN roles r ON u.role_id = r.id 
        JOIN offices o ON u.office_id = o.id 
        ORDER BY u.created_at DESC
    ";
    
    $stmt = $pdo->query($sql);
    $users = $stmt->fetchAll();
    
    // Get roles and offices for form dropdowns
    $roles = getAllRoles();
    $offices = getAllOffices();
    
} catch(PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Fleet Fuel Management</title>
    <meta name="description" content="Manage system users, roles, and permissions">
    <link rel="stylesheet" href="styles.css">
    <style>
        .user-form {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            margin-bottom: 2rem;
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }
        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        .status-active { background: #d4edda; color: #155724; }
        .status-inactive { background: #f8d7da; color: #721c24; }
        .btn-toggle {
            padding: 0.25rem 0.5rem;
            font-size: 0.8rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-left: 0.5rem;
        }
        .btn-deactivate { background: #dc3545; color: white; }
        .btn-activate { background: #28a745; color: white; }
        .password-field {
            position: relative;
        }
        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            user-select: none;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1>User Management</h1>
            <p>Create and manage system users with roles and permissions</p>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Add User Form -->
        <?php if (hasPermission('users_edit')): ?>
        <div class="user-form">
            <h2>Add New User</h2>
            <form method="POST">
                <input type="hidden" name="action" value="add_user">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="full_name">Full Name</label>
                        <input type="text" id="full_name" name="full_name" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="password-field">
                            <input type="password" id="password" name="password" class="form-control" required>
                            <span class="password-toggle" onclick="togglePassword('password')">👁️</span>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="role_id">Role</label>
                        <select id="role_id" name="role_id" class="form-control" required>
                            <option value="">Select Role</option>
                            <?php foreach ($roles as $role): ?>
                                <option value="<?php echo $role['id']; ?>"><?php echo htmlspecialchars($role['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="office_id">Office</label>
                        <select id="office_id" name="office_id" class="form-control" required>
                            <option value="">Select Office</option>
                            <?php foreach ($offices as $office): ?>
                                <option value="<?php echo $office['id']; ?>"><?php echo htmlspecialchars($office['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">Add User</button>
            </form>
        </div>
        <?php endif; ?>

        <!-- Users List -->
        <div class="section">
            <h2>System Users</h2>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Office</th>
                            <th>Status</th>
                            <th>Last Login</th>
                            <th>Created</th>
                            <?php if (hasPermission('users_edit')): ?>
                                <th>Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="<?php echo hasPermission('users_edit') ? '9' : '8'; ?>" class="no-data">No users found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo htmlspecialchars($user['role_name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['office_name']); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $user['status']; ?>">
                                            <?php echo ucfirst($user['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $user['last_login'] ? formatDate($user['last_login']) : 'Never'; ?></td>
                                    <td><?php echo formatDate($user['created_at']); ?></td>
                                    <?php if (hasPermission('users_edit') && $user['id'] != $_SESSION['user_id']): ?>
                                        <td>
                                            <?php if (isSuperAdmin()): ?>
                                                <button onclick="editUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($user['email'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($user['full_name'], ENT_QUOTES); ?>', <?php echo $user['role_id']; ?>, <?php echo $user['office_id']; ?>)" class="btn btn-primary" style="padding: 0.25rem 0.5rem; font-size: 0.8rem; margin-right: 0.5rem;">Edit</button>
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                                    <input type="hidden" name="action" value="delete_user">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <button type="submit" class="btn btn-danger" style="padding: 0.25rem 0.5rem; font-size: 0.8rem; margin-right: 0.5rem;">Delete</button>
                                                </form>
                                            <?php endif; ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="toggle_status">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <input type="hidden" name="status" value="<?php echo $user['status']; ?>">
                                                <button type="submit" class="btn-toggle <?php echo $user['status'] === 'active' ? 'btn-deactivate' : 'btn-activate'; ?>">
                                                    <?php echo $user['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>
                                                </button>
                                            </form>
                                        </td>
                                    <?php elseif (hasPermission('users_edit')): ?>
                                        <td><em>Current User</em></td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div id="editUserModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 2rem; border-radius: 8px; width: 90%; max-width: 600px; max-height: 80%; overflow-y: auto;">
            <h3>Edit User</h3>
            <form method="POST">
                <input type="hidden" name="action" value="edit_user">
                <input type="hidden" name="user_id" id="editUserId">
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label for="editUsername">Username</label>
                        <input type="text" id="editUsername" name="username" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="editEmail">Email</label>
                        <input type="email" id="editEmail" name="email" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="editFullName">Full Name</label>
                        <input type="text" id="editFullName" name="full_name" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="editPassword">New Password (leave blank to keep current)</label>
                        <div style="position: relative;">
                            <input type="password" id="editPassword" name="password" class="form-control">
                            <span onclick="togglePassword('editPassword')" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer;">👁️</span>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="editRoleId">Role</label>
                        <select id="editRoleId" name="role_id" class="form-control" required>
                            <?php foreach ($roles as $role): ?>
                                <option value="<?php echo $role['id']; ?>"><?php echo htmlspecialchars($role['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="editOfficeId">Office</label>
                        <select id="editOfficeId" name="office_id" class="form-control" required>
                            <?php foreach ($offices as $office): ?>
                                <option value="<?php echo $office['id']; ?>"><?php echo htmlspecialchars($office['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div style="margin-top: 2rem; display: flex; gap: 1rem;">
                    <button type="submit" class="btn btn-primary">Update User</button>
                    <button type="button" onclick="closeEditModal()" class="btn btn-secondary">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const toggle = field.nextElementSibling;
            
            if (field.type === 'password') {
                field.type = 'text';
                toggle.textContent = '🙈';
            } else {
                field.type = 'password';
                toggle.textContent = '👁️';
            }
        }
        
        function editUser(id, username, email, fullName, roleId, officeId) {
            document.getElementById('editUserId').value = id;
            document.getElementById('editUsername').value = username;
            document.getElementById('editEmail').value = email;
            document.getElementById('editFullName').value = fullName;
            document.getElementById('editRoleId').value = roleId;
            document.getElementById('editOfficeId').value = officeId;
            document.getElementById('editPassword').value = '';
            document.getElementById('editUserModal').style.display = 'block';
        }
        
        function closeEditModal() {
            document.getElementById('editUserModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        document.getElementById('editUserModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeEditModal();
            }
        });
    </script>
</body>
</html>