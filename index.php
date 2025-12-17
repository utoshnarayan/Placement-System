<?php
// index.php - Home page with backend
session_start();

// Database connection
try {
    $db = new PDO('sqlite:placement_system.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create tables if they don't exist
    createTables($db);
} catch (PDOException $e) {
    // Continue without database for frontend
}

function createTables($db) {
    // Students table
    $db->exec("CREATE TABLE IF NOT EXISTS students (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        roll TEXT UNIQUE NOT NULL,
        department TEXT NOT NULL,
        email TEXT,
        phone TEXT,
        status TEXT DEFAULT 'Active',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Companies table
    $db->exec("CREATE TABLE IF NOT EXISTS companies (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        sector TEXT,
        website TEXT,
        logo TEXT,
        description TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Placements table
    $db->exec("CREATE TABLE IF NOT EXISTS placements (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        student_id INTEGER,
        company_id INTEGER,
        package INTEGER,
        year INTEGER,
        status TEXT DEFAULT 'Placed',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES students(id),
        FOREIGN KEY (company_id) REFERENCES companies(id)
    )");
    
    // Insert sample data if empty
    $stmt = $db->query("SELECT COUNT(*) FROM students");
    if ($stmt->fetchColumn() == 0) {
        // Sample students
        $db->exec("INSERT INTO students (name, roll, department, email) VALUES 
            ('John Doe', 'CS001', 'Computer Science', 'john@example.com'),
            ('Jane Smith', 'EC001', 'Electronics', 'jane@example.com'),
            ('Mike Johnson', 'ME001', 'Mechanical', 'mike@example.com')");
        
        // Sample companies
        $db->exec("INSERT INTO companies (name, sector, website, logo) VALUES 
            ('Tech Corp', 'IT', 'https://techcorp.com', 'assets/images/logo.svg'),
            ('Electro Ltd', 'Electronics', 'https://electro.com', 'assets/images/logo.svg'),
            ('Mech Solutions', 'Manufacturing', 'https://mechsol.com', 'assets/images/logo.svg')");
        
        // Sample placements
        $db->exec("INSERT INTO placements (student_id, company_id, package, year, status) VALUES 
            (1, 1, 1200000, 2024, 'Placed'),
            (2, 2, 900000, 2024, 'Placed'),
            (3, 3, 800000, 2024, 'Pending')");
    }
}

// Handle API requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'get_dashboard_stats':
            getDashboardStats($db);
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Unknown action']);
    }
    exit;
}

function getDashboardStats($db) {
    try {
        // Total students placed
        $stmt = $db->query("SELECT COUNT(*) as total FROM placements WHERE status = 'Placed'");
        $totalStudents = $stmt->fetchColumn();
        
        // Total companies
        $stmt = $db->query("SELECT COUNT(*) as total FROM companies");
        $totalCompanies = $stmt->fetchColumn();
        
        // Placement rate
        $stmt = $db->query("SELECT COUNT(*) as total FROM students");
        $totalStudentsCount = $stmt->fetchColumn();
        $placementRate = $totalStudentsCount > 0 ? round(($totalStudents / $totalStudentsCount) * 100, 1) : 0;
        
        // Highest package
        $stmt = $db->query("SELECT MAX(package) as highest FROM placements");
        $highestPackage = $stmt->fetchColumn();
        $highestPackageLPA = $highestPackage ? round($highestPackage / 100000, 1) : 0;
        
        echo json_encode([
            'success' => true,
            'data' => [
                'totalStudents' => $totalStudents,
                'totalCompanies' => $totalCompanies,
                'placementRate' => $placementRate,
                'highestPackage' => $highestPackageLPA
            ]
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error fetching stats']);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Placement Record Management System</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header__content">
                <div class="header__logo">
                    <img src="assets/images/logo.png" alt="University Logo">
                    <h1>Placement Management</h1>
                </div>
                <nav class="nav">
                    <ul class="nav__list">
                        <li><a href="index.php" class="nav__link active">Home</a></li>
                        <li><a href="placements.php" class="nav__link">Placements</a></li>
                        <li><a href="company.php" class="nav__link">Companies</a></li>
                        <li><a href="analytics.php" class="nav__link">Analytics</a></li>
                        <li><a href="admin.php" class="nav__link">Admin</a></li>
                    </ul>
                </nav>
                <button class="theme-toggle" id="themeToggle" aria-label="Toggle dark mode">
                    <span class="theme-toggle__icon">🌙</span>
                </button>
            </div>
        </div>
    </header>

    <main class="main">
        <section class="hero">
            <div class="container">
                <div class="hero__content">
                    <h1 class="hero__title">Placement Record Management System</h1>
                    <p class="hero__description">
                        Track and manage student placements, company recruitment, and placement analytics in one platform.
                    </p>
                    <div class="hero__stats">
                        <div class="stat-card">
                            <div class="stat-card__value" id="totalStudents">0</div>
                            <div class="stat-card__label">Students Placed</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-card__value" id="totalCompanies">0</div>
                            <div class="stat-card__label">Partner Companies</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-card__value" id="placementRate">0%</div>
                            <div class="stat-card__label">Placement Rate</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-card__value" id="highestPackage">₹0L</div>
                            <div class="stat-card__label">Highest Package</div>
                        </div>
                    </div>
                    <div class="hero__actions">
                        <a href="placements.php" class="btn btn--primary">View Placements</a>
                        <a href="company.php" class="btn btn--secondary">Company List</a>
                        <a href="analytics.php" class="btn btn--secondary">Analytics</a>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2025 Placement Management System. All rights reserved.</p>
        </div>
    </footer>

    <script>
        // Load dashboard stats
        document.addEventListener('DOMContentLoaded', function() {
            fetch('index.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=get_dashboard_stats'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('totalStudents').textContent = data.data.totalStudents;
                    document.getElementById('totalCompanies').textContent = data.data.totalCompanies;
                    document.getElementById('placementRate').textContent = data.data.placementRate + '%';
                    document.getElementById('highestPackage').textContent = '₹' + data.data.highestPackage + 'L';
                }
            })
            .catch(error => {
                console.error('Error loading stats:', error);
            });
        });
    </script>
</body>
</html>