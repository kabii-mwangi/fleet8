<nav class="navbar">
    <div class="nav-container">
        <div class="nav-brand">
            <a href="dashboard.php">🚗 Fleet Manager</a>
        </div>
        
        <ul class="nav-menu">
            <li><a href="dashboard.php" class="nav-link">Dashboard</a></li>
            
            <!-- Vehicles Dropdown -->
            <?php if (hasPermission('vehicles_view') || hasPermission('fuel_logs_view') || hasPermission('maintenance_view')): ?>
                <li class="nav-dropdown">
                    <a href="#" class="nav-link dropdown-toggle">Vehicles <span class="dropdown-arrow">▼</span></a>
                    <ul class="dropdown-menu">
                        <?php if (hasPermission('vehicles_view')): ?>
                            <li><a href="vehicles.php">Vehicle Management</a></li>
                        <?php endif; ?>
                        <?php if (hasPermission('fuel_logs_view')): ?>
                            <li><a href="fuel-logs.php">Fuel Logs</a></li>
                        <?php endif; ?>
                        <?php if (hasPermission('maintenance_view')): ?>
                            <li><a href="maintenance.php">Maintenance</a></li>
                        <?php endif; ?>
                        <?php if (hasPermission('vehicles_view')): ?>
                            <li><a href="products.php">Products</a></li>
                        <?php endif; ?>
                    </ul>
                </li>
            <?php endif; ?>
            
            <?php if (hasPermission('employees_view')): ?>
                <li><a href="employees.php" class="nav-link">Employees</a></li>
            <?php endif; ?>
            <?php if (hasPermission('departments_view')): ?>
                <li><a href="departments.php" class="nav-link">Departments</a></li>
            <?php endif; ?>
            <?php if (hasPermission('reports_view')): ?>
                <li class="nav-dropdown">
                    <a href="#" class="nav-link dropdown-toggle">Reports <span class="dropdown-arrow">▼</span></a>
                    <ul class="dropdown-menu">
                        <li><a href="reports.php">Fuel Logs Report</a></li>
                        <li><a href="maintenance-reports.php">Maintenance Report</a></li>
                        <li><a href="product-reports.php">Product Report</a></li>
                    </ul>
                </li>
            <?php endif; ?>
            <?php if (hasPermission('users_view')): ?>
                <li><a href="users.php" class="nav-link">Users</a></li>
            <?php endif; ?>
            <li><a href="logout.php" class="nav-link logout">Logout</a></li>
        </ul>
        
        <div class="nav-user">
            <div class="user-info">
                <span class="user-name"><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                <span class="user-role"><?php echo htmlspecialchars($_SESSION['role_name']); ?></span>
                <span class="user-office"><?php echo htmlspecialchars($_SESSION['office_name']); ?></span>
            </div>
        </div>
    </div>
</nav>

<style>
.nav-user .user-info {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    font-size: 0.85rem;
}

.user-name {
    font-weight: 600;
    color: #333;
}

.user-role {
    color: #666;
    font-size: 0.8rem;
}

.user-office {
    color: #888;
    font-size: 0.75rem;
}

/* Dropdown Styles */
.nav-dropdown {
    position: relative;
}

.dropdown-toggle {
    display: flex;
    align-items: center;
    gap: 5px;
}

.dropdown-arrow {
    font-size: 0.8rem;
    transition: transform 0.3s ease;
}

.dropdown-menu {
    position: absolute;
    top: 100%;
    left: 0;
    background: white;
    border: 1px solid #ddd;
    border-radius: 5px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    min-width: 180px;
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: all 0.3s ease;
    z-index: 1000;
    list-style: none;
    padding: 0;
    margin: 0;
}

.nav-dropdown:hover .dropdown-menu {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.nav-dropdown:hover .dropdown-arrow {
    transform: rotate(180deg);
}

.dropdown-menu li {
    margin: 0;
}

.dropdown-menu a {
    display: block;
    padding: 12px 16px;
    color: #333;
    text-decoration: none;
    border-bottom: 1px solid #f0f0f0;
    transition: background-color 0.2s ease;
}

.dropdown-menu a:hover {
    background-color: #f8f9fa;
    color: #0066cc;
}

.dropdown-menu li:last-child a {
    border-bottom: none;
}

@media (max-width: 768px) {
    .nav-user .user-info {
        align-items: center;
    }
    
    .nav-user .user-info span {
        display: block;
    }
    
    .dropdown-menu {
        position: static;
        opacity: 1;
        visibility: visible;
        transform: none;
        box-shadow: none;
        border: none;
        background: transparent;
    }
    
    .nav-dropdown:hover .dropdown-menu {
        display: block;
    }
}
</style>