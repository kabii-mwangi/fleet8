<?php
// Database configuration - Update these values with your hosting details
$db_host = 'localhost:3306';  // Your MySQL server host
$db_name = 'maggie_fleet';  // Your database name
$db_user = 'maggie_mwas';  // Your MySQL username
$db_pass = 'Mwaskabii123#';  // Your MySQL password

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Database connection failed. Please check your database configuration in config.php");
}

// Enhanced authentication check function
function requireAuth() {
    session_start();
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['user_id'])) {
        header('Location: index.php');
        exit;
    }
    return $_SESSION;
}

// Check if user has specific permission
function hasPermission($permission) {
    if (!isset($_SESSION['permissions'])) {
        return false;
    }
    $permissions = json_decode($_SESSION['permissions'], true);
    return isset($permissions[$permission]) && $permissions[$permission] === true;
}

// Require specific permission or redirect
function requirePermission($permission, $redirect = 'dashboard.php') {
    if (!hasPermission($permission)) {
        header("Location: $redirect");
        exit;
    }
}

// Get current user info
function getCurrentUser() {
    global $pdo;
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    
    $stmt = $pdo->prepare("
        SELECT u.*, r.name as role_name, r.permissions, o.name as office_name 
        FROM users u 
        JOIN roles r ON u.role_id = r.id 
        JOIN offices o ON u.office_id = o.id 
        WHERE u.id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

// Get user's office ID for data filtering
function getUserOfficeId() {
    return $_SESSION['office_id'] ?? 1;
}

// Check if current user is Super Admin (can see all offices)
function isSuperAdmin() {
    return isset($_SESSION['role_name']) && $_SESSION['role_name'] === 'Super Admin';
}

// Check if current user can view all offices (Super Admin or Admin)
function canViewAllOffices() {
    return hasPermission('view_all_offices') || isSuperAdmin();
}

// Format currency in KSH
function formatCurrency($amount) {
    return 'KSH ' . number_format($amount, 2);
}

// Format date
function formatDate($date) {
    return date('M j, Y', strtotime($date));
}

// Calculate efficiency (km/L)
function calculateEfficiency($distance, $fuel) {
    return $fuel > 0 ? round($distance / $fuel, 2) : 0;
}

// Get office-filtered SQL condition
function getOfficeFilterSQL($tableAlias = '', $includeWhere = true) {
    if (canViewAllOffices()) {
        return ''; // Super admin and admin see all offices
    }
    
    $prefix = $tableAlias ? $tableAlias . '.' : '';
    $condition = $prefix . "office_id = " . getUserOfficeId();
    
    return $includeWhere ? " WHERE $condition" : " AND $condition";
}

// Authenticate user login
function authenticateUser($username, $password) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT u.*, r.name as role_name, r.permissions, o.name as office_name 
        FROM users u 
        JOIN roles r ON u.role_id = r.id 
        JOIN offices o ON u.office_id = o.id 
        WHERE u.username = ? AND u.status = 'active'
    ");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password_hash'])) {
        // Update last login
        $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$user['id']]);
        
        // Set session variables
        $_SESSION['logged_in'] = true;
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role_id'] = $user['role_id'];
        $_SESSION['role_name'] = $user['role_name'];
        $_SESSION['office_id'] = $user['office_id'];
        $_SESSION['office_name'] = $user['office_name'];
        $_SESSION['permissions'] = $user['permissions'];
        
        return true;
    }
    
    return false;
}

// Get all offices (for dropdowns)
function getAllOffices() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM offices WHERE status = 'active' ORDER BY name");
    return $stmt->fetchAll();
}

// Get all roles (for user management)
function getAllRoles() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM roles ORDER BY name");
    return $stmt->fetchAll();
}

// Get vehicle categories
function getVehicleCategories() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM vehicle_categories ORDER BY name");
    return $stmt->fetchAll();
}
?>