<?php
// admin.php - Admin page with backend
session_start();

if (!isset($_SESSION['admin_logged_in'])) {
    $showLogin = true;
} else {
    $showLogin = false;
}

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
    // Users table for admin authentication
    $db->exec("CREATE TABLE IF NOT EXISTS admin_users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        password TEXT NOT NULL,
        email TEXT,
        role TEXT DEFAULT 'admin',
        last_login DATETIME,
        status TEXT DEFAULT 'active',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Insert default admin user if not exists
    $stmt = $db->query("SELECT COUNT(*) FROM admin_users");
    if ($stmt->fetchColumn() == 0) {
        $db->exec("INSERT INTO admin_users (username, password, email) VALUES 
                  ('admin', '" . password_hash('admin123', PASSWORD_DEFAULT) . "', 'admin@example.com')");
    }
}

// Handle API requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'admin_login':
            adminLogin($db);
            break;
        case 'get_admin_data':
            if (!isset($_SESSION['admin_logged_in'])) {
                echo json_encode(['success' => false, 'message' => 'Not authenticated']);
                break;
            }
            getAdminData($db, $_POST['type']);
            break;
        case 'save_admin_data':
            if (!isset($_SESSION['admin_logged_in'])) {
                echo json_encode(['success' => false, 'message' => 'Not authenticated']);
                break;
            }
            saveAdminData($db, $_POST['type']);
            break;
        case 'delete_admin_data':
            if (!isset($_SESSION['admin_logged_in'])) {
                echo json_encode(['success' => false, 'message' => 'Not authenticated']);
                break;
            }
            deleteAdminData($db, $_POST['type'], $_POST['id']);
            break;
        
        case 'logout':
    session_destroy();
    echo json_encode(['success' => true]);
    break;


        default:
            echo json_encode(['success' => false, 'message' => 'Unknown action']);
    }
    exit;
}

function adminLogin($db) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $stmt = $db->prepare("SELECT * FROM admin_users WHERE username = ? AND status = 'active'");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $user['username'];
        $_SESSION['admin_role'] = $user['role'];
        
        // Update last login
        $updateStmt = $db->prepare("UPDATE admin_users SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
        $updateStmt->execute([$user['id']]);
        
        echo json_encode(['success' => true, 'username' => $user['username']]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
    }
}

function getAdminData($db, $type) {
    try {
        switch ($type) {
            case 'placements':
                $stmt = $db->query("SELECT p.*, s.name, s.roll, s.department, c.name as company 
                                  FROM placements p 
                                  JOIN students s ON p.student_id = s.id 
                                  JOIN companies c ON p.company_id = c.id 
                                  ORDER BY p.created_at DESC");
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                break;
                
            case 'companies':
                $stmt = $db->query("SELECT c.*, 
                                  COUNT(p.id) as hires,
                                  MAX(p.package) as highestPackage,
                                  AVG(p.package) as averagePackage
                                  FROM companies c
                                  LEFT JOIN placements p ON c.id = p.company_id
                                  GROUP BY c.id 
                                  ORDER BY c.name");
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                break;
                
            case 'students':
                $stmt = $db->query("SELECT * FROM students ORDER BY name");
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                break;
                
            case 'users':
                $stmt = $db->query("SELECT id, username, email, role, last_login, status FROM admin_users ORDER BY username");
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid data type']);
                return;
        }
        
        echo json_encode(['success' => true, 'data' => $data]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error fetching data']);
    }
}

function saveAdminData($db, $type) {
    try {
        switch ($type) {
            case 'placement':
                savePlacement($db);
                break;
            case 'company':
                saveCompany($db);
                break;
            case 'student':
                saveStudent($db);
                break;
            case 'user':
                $username = $_POST['username'] ?? '';
                $email = $_POST['email'] ?? '';
                $password = $_POST['password'] ?? 'admin123';
                $role = $_POST['role'] ?? 'admin';

                if (!$username) {
                    echo json_encode(['success' => false, 'message' => 'Username is required']);
                    return;
                }

                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("INSERT INTO admin_users (username, password, email, role) VALUES (?, ?, ?, ?)");
                $stmt->execute([$username, $hashedPassword, $email, $role]);

                echo json_encode(['success' => true]);
                return;



            default:
                echo json_encode(['success' => false, 'message' => 'Invalid data type']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error saving data']);
    }
}

function savePlacement($db) {
    $id = $_POST['id'] ?? null;
    $student_id = $_POST['student_id'] ?? '';
    $company_id = $_POST['company_id'] ?? '';
    $package = floatval($_POST['package'] ?? 0) * 100000;
    $year = $_POST['year'] ?? date('Y');
    $status = $_POST['status'] ?? 'Placed';
    
    if ($id) {
        $stmt = $db->prepare("UPDATE placements SET student_id = ?, company_id = ?, package = ?, year = ?, status = ? WHERE id = ?");
        $stmt->execute([$student_id, $company_id, $package, $year, $status, $id]);
    } else {
        $stmt = $db->prepare("INSERT INTO placements (student_id, company_id, package, year, status) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$student_id, $company_id, $package, $year, $status]);
    }
    
    echo json_encode(['success' => true]);
}

function saveCompany($db) {
    $id = $_POST['id'] ?? null;
    $name = $_POST['name'] ?? '';
    $sector = $_POST['sector'] ?? '';
    $website = $_POST['website'] ?? '';
    $logo = $_POST['logo'] ?? '';
    $description = $_POST['description'] ?? '';
    
    if ($id) {
        $stmt = $db->prepare("UPDATE companies SET name = ?, sector = ?, website = ?, logo = ?, description = ? WHERE id = ?");
        $stmt->execute([$name, $sector, $website, $logo, $description, $id]);
    } else {
        $stmt = $db->prepare("INSERT INTO companies (name, sector, website, logo, description) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $sector, $website, $logo, $description]);
    }
    
    echo json_encode(['success' => true]);
}

function saveStudent($db) {
    $id = $_POST['id'] ?? null;
    $name = $_POST['name'] ?? '';
    $roll = $_POST['roll'] ?? '';
    $department = $_POST['department'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    
    if ($id) {
        $stmt = $db->prepare("UPDATE students SET name = ?, roll = ?, department = ?, email = ?, phone = ? WHERE id = ?");
        $stmt->execute([$name, $roll, $department, $email, $phone, $id]);
    } else {
        $stmt = $db->prepare("INSERT INTO students (name, roll, department, email, phone) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $roll, $department, $email, $phone]);
    }
    
    echo json_encode(['success' => true]);
}

function deleteAdminData($db, $type, $id) {
    try {
        switch ($type) {
            case 'placement':
                $stmt = $db->prepare("DELETE FROM placements WHERE id = ?");
                break;
            case 'company':
                $stmt = $db->prepare("DELETE FROM companies WHERE id = ?");
                break;
            case 'student':
                $stmt = $db->prepare("DELETE FROM students WHERE id = ?");
                break;
            case 'user':
                $stmt = $db->prepare("DELETE FROM admin_users WHERE id = ?");
                break;
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid data type']);
                return;
        }
        
        $stmt->execute([$id]);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error deleting data']);
    }
}

// Check if user is logged in
$isLoggedIn = isset($_SESSION['admin_logged_in']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Placement Management System</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php if (!$isLoggedIn): ?>
    <!-- Login Form -->
    <div class="modal" style="display: flex;">
        <div class="modal__content">
            <div class="modal__header">
                <h2 class="modal__title">Admin Login</h2>
            </div>
            <div class="modal__body">
                <form id="loginForm">
                    <div class="form-group">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" id="username" name="username" class="form-control" value="admin" required>
                    </div>
                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" id="password" name="password" class="form-control" value="admin123" required>
                    </div>
                </form>
                <div id="loginMessage" style="color: red; margin-top: 10px; display: none;"></div>
            </div>
            <div class="modal__footer">
                <button class="btn btn--primary" id="loginBtn">Login</button>
            </div>
        </div>
    </div>
    <?php else: ?>
    <!-- Admin Dashboard -->
    <header class="header">
        <div class="container">
            <div class="header__content">
                <div class="header__logo">
                    <img src="assets/images/logo.png" alt="University Logo">
                    <h1>Placement Management - Admin</h1>
                </div>
                <nav class="nav">
                    <ul class="nav__list">
                        <li><a href="index.php" class="nav__link">Home</a></li>
                        <li><a href="placements.php" class="nav__link">Placements</a></li>
                        <li><a href="company.php" class="nav__link">Companies</a></li>
                        <li><a href="analytics.php" class="nav__link">Analytics</a></li>
                        <li><a href="admin.php" class="nav__link active">Admin</a></li>
                    </ul>
                </nav>
                <div class="header-actions">
                    <span>Welcome, <?php echo $_SESSION['admin_username']; ?></span>
                    <button class="btn btn--sm btn--secondary" id="logoutBtn">Logout</button>
                </div>
            </div>
        </div>
    </header>

    <main class="main">
        <section class="admin-section">
            <div class="container">
                <div class="section-header">
                    <h1>Admin Dashboard</h1>
                    <p>Manage all placement data and system settings</p>
                </div>

                <div class="admin-tabs">
                    <div class="tabs">
                        <button class="tab-btn active" data-tab="placements">Placements</button>
                        <button class="tab-btn" data-tab="companies">Companies</button>
                        <button class="tab-btn" data-tab="students">Students</button>
                        <button class="tab-btn" data-tab="users">Users</button>
                    </div>

                    <div class="tab-content active" id="placements-tab">
                        <div class="tab-header">
                            <h2>Manage Placements</h2>
                            <button class="btn btn--primary" onclick="openModal('placement')">Add New Placement</button>
                        </div>
                        <div class="table-container card">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Company</th>
                                        <th>Package</th>
                                        <th>Year</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="admin-placements-table">
                                    <!-- Populated by JavaScript -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="tab-content" id="companies-tab">
                        <div class="tab-header">
                            <h2>Manage Companies</h2>
                            <button class="btn btn--primary" onclick="openModal('company')">Add New Company</button>
                        </div>
                        <div class="table-container card">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Sector</th>
                                        <th>Website</th>
                                        <th>Hires</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="admin-companies-table">
                                    <!-- Populated by JavaScript -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="tab-content" id="students-tab">
                        <div class="tab-header">
                            <h2>Manage Students</h2>
                            <button class="btn btn--primary" onclick="openModal('student')">Add New Student</button>
                        </div>
                        <div class="table-container card">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Roll No</th>
                                        <th>Department</th>
                                        <th>Email</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="admin-students-table">
                                    <!-- Populated by JavaScript -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="tab-content" id="users-tab">
                        <div class="tab-header">
                            <h2>Manage Users</h2>
                           <button class="btn btn--primary" onclick="openModal('user')">Add New User</button>

                        </div>
                        <div class="table-container card">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Last Login</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="admin-users-table">
                                    <!-- Populated by JavaScript -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Modals for Add/Edit -->
    <div class="modal" id="placementModal" style="display: none;">
        <div class="modal__content">
            <div class="modal__header">
                <h2 class="modal__title">Add Placement</h2>
                <button class="modal__close" onclick="closeModal('placement')">&times;</button>
            </div>
            <div class="modal__body">
                <form id="placementForm">
                    <input type="hidden" id="placement_id" name="id">
                    <div class="form-group">
                        <label class="form-label">Student</label>
                        <select id="placement_student" name="student_id" class="form-control" required>
                            <option value="">Select Student</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Company</label>
                        <select id="placement_company" name="company_id" class="form-control" required>
                            <option value="">Select Company</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Package (LPA)</label>
                        <input type="number" id="placement_package" name="package" class="form-control" step="0.1" min="0" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Year</label>
                        <input type="number" id="placement_year" name="year" class="form-control" min="2000" max="2030" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select id="placement_status" name="status" class="form-control" required>
                            <option value="Placed">Placed</option>
                            <option value="Pending">Pending</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal__footer">
                <button class="btn btn--secondary" onclick="closeModal('placement')">Cancel</button>
                <button class="btn btn--primary" onclick="saveData('placement')">Save</button>
            </div>
        </div>
    </div>

    <div class="modal" id="companyModal" style="display: none;">
        <div class="modal__content">
            <div class="modal__header">
                <h2 class="modal__title">Add Company</h2>
                <button class="modal__close" onclick="closeModal('company')">&times;</button>
            </div>
            <div class="modal__body">
                <form id="companyForm">
                    <input type="hidden" id="company_id" name="id">
                    <div class="form-group">
                        <label class="form-label">Company Name</label>
                        <input type="text" id="company_name" name="name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Sector</label>
                        <input type="text" id="company_sector" name="sector" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Website</label>
                        <input type="url" id="company_website" name="website" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Logo URL</label>
                        <input type="url" id="company_logo" name="logo" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea id="company_description" name="description" class="form-control" rows="4"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal__footer">
                <button class="btn btn--secondary" onclick="closeModal('company')">Cancel</button>
                <button class="btn btn--primary" onclick="saveData('company')">Save</button>
            </div>
        </div>
    </div>

    <div class="modal" id="studentModal" style="display: none;">
        <div class="modal__content">
            <div class="modal__header">
                <h2 class="modal__title">Add Student</h2>
                <button class="modal__close" onclick="closeModal('student')">&times;</button>
            </div>
            <div class="modal__body">
                <form id="studentForm">
                    <input type="hidden" id="student_id" name="id">
                    <div class="form-group">
                        <label class="form-label">Full Name</label>
                        <input type="text" id="student_name" name="name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Roll Number</label>
                        <input type="text" id="student_roll" name="roll" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Department</label>
                        <select id="student_department" name="department" class="form-control" required>
                            <option value="">Select Department</option>
                            <option value="Computer Science">Computer Science</option>
                            <option value="Electronics">Electronics</option>
                            <option value="Mechanical">Mechanical</option>
                            <option value="Civil">Civil</option>
                            <option value="Chemical">Chemical</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" id="student_email" name="email" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone</label>
                        <input type="tel" id="student_phone" name="phone" class="form-control">
                    </div>
                </form>
            </div>
            <div class="modal__footer">
                <button class="btn btn--secondary" onclick="closeModal('student')">Cancel</button>
                <button class="btn btn--primary" onclick="saveData('student')">Save</button>
            </div>
        </div>
    </div>
  <div class="modal" id="userModal" style="display: none;">
    <div class="modal__content">
        <div class="modal__header">
            <h2 class="modal__title">Add User</h2>
            <button class="modal__close" onclick="closeModal('user')">&times;</button>
        </div>

        <div class="modal__body">
            <form id="userForm">
                <input type="hidden" id="user_id" name="id">

                <div class="form-group">
                    <label class="form-label">Username</label>
                    <input type="text" id="user_username" name="username" class="form-control" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" id="user_email" name="email" class="form-control">
                </div>

                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" id="user_password" name="password" class="form-control" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Role</label>
                    <select id="user_role" name="role" class="form-control" required>
                        <option value="admin">Admin</option>
                        <option value="editor">Editor</option>
                        <option value="viewer">Viewer</option>
                    </select>
                </div>
            </form>
        </div>

        <div class="modal__footer">
            <button class="btn btn--secondary" onclick="closeModal('user')">Cancel</button>
            <button class="btn btn--primary" onclick="saveData('user')">Save</button>
        </div>
    </div>
</div>



    <footer class="footer">
        <div class="container">
            <p>&copy; 2025 Placement Management System. All rights reserved.</p>
        </div>
    </footer>
    <?php endif; ?>

    <script>
        <?php if (!$isLoggedIn): ?>
        // Login functionality
        document.getElementById('loginBtn').addEventListener('click', function() {
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;
            const messageEl = document.getElementById('loginMessage');

            if (!username || !password) {
                messageEl.textContent = 'Please enter both username and password';
                messageEl.style.display = 'block';
                return;
            }

            fetch('admin.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=admin_login&username=${encodeURIComponent(username)}&password=${encodeURIComponent(password)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    messageEl.textContent = data.message || 'Login failed';
                    messageEl.style.display = 'block';
                }
            })
            .catch(error => {
                messageEl.textContent = 'Login error';
                messageEl.style.display = 'block';
            });
        });
        <?php else: ?>
        // Admin dashboard functionality
        let currentTab = 'placements';

        document.addEventListener('DOMContentLoaded', function() {
            loadTabData('placements');
            setupTabListeners();
            document.getElementById('logoutBtn').addEventListener('click', function () {
    fetch('admin.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=logout'
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            window.location.reload();
        }
    });
});

        });

        function setupTabListeners() {
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                    document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                    
                    this.classList.add('active');
                    currentTab = this.dataset.tab;
                    document.getElementById(`${currentTab}-tab`).classList.add('active');
                    
                    loadTabData(currentTab);
                });
            });
        }

        function loadTabData(tab) {
            fetch('admin.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=get_admin_data&type=${tab}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateTable(tab, data.data);
                }
            });
        }

        function updateTable(tab, data) {
            const tableBody = document.getElementById(`admin-${tab}-table`);
            if (!tableBody) return;

            let html = '';
            
            switch (tab) {
                case 'placements':
                    html = data.map(item => `
                        <tr>
                            <td>${item.name} (${item.roll})</td>
                            <td>${item.company}</td>
                            <td>₹${(item.package / 100000).toFixed(1)}L</td>
                            <td>${item.year}</td>
                            <td><span class="status-badge status-${item.status.toLowerCase()}">${item.status}</span></td>
                            <td>
                                <button class="btn btn--sm btn--primary" onclick="editItem('placement', ${item.id})">Edit</button>
                                <button class="btn btn--sm btn--danger" onclick="deleteItem('placement', ${item.id})">Delete</button>
                            </td>
                        </tr>
                    `).join('');
                    break;
                    
                case 'companies':
                    html = data.map(item => `
                        <tr>
                            <td>${item.name}</td>
                            <td>${item.sector}</td>
                            <td>${item.website || '-'}</td>
                            <td>${item.hires || 0}</td>
                            <td>
                                <button class="btn btn--sm btn--primary" onclick="editItem('company', ${item.id})">Edit</button>
                                <button class="btn btn--sm btn--danger" onclick="deleteItem('company', ${item.id})">Delete</button>
                            </td>
                        </tr>
                    `).join('');
                    break;
                    
                case 'students':
                    html = data.map(item => `
                        <tr>
                            <td>${item.name}</td>
                            <td>${item.roll}</td>
                            <td>${item.department}</td>
                            <td>${item.email || '-'}</td>
                            <td>
                                <button class="btn btn--sm btn--primary" onclick="editItem('student', ${item.id})">Edit</button>
                                <button class="btn btn--sm btn--danger" onclick="deleteItem('student', ${item.id})">Delete</button>
                            </td>
                        </tr>
                    `).join('');
                    break;
                    
                case 'users':
                    html = data.map(item => `
                        <tr>
                            <td>${item.username}</td>
                            <td>${item.email || '-'}</td>
                            <td>${item.role}</td>
                            <td>${item.last_login || 'Never'}</td>
                            <td>
                                <button class="btn btn--sm btn--danger" onclick="deleteItem('user', ${item.id})">Delete</button>
                            </td>
                        </tr>
                    `).join('');
                    break;
            }
            
            tableBody.innerHTML = html || '<tr><td colspan="6" style="text-align: center; padding: 20px;">No data found</td></tr>';
        }

        function openModal(type) {
            document.getElementById(`${type}Modal`).style.display = 'flex';
            document.getElementById(`${type}_id`).value = '';
            document.getElementById(`${type}Form`).reset();
            
            if (type === 'placement') {
                loadStudentsForPlacement();
                loadCompaniesForPlacement();
            }
        }

        function closeModal(type) {
            document.getElementById(`${type}Modal`).style.display = 'none';
        }

        function editItem(type, id) {
            // Implementation for editing items
            console.log(`Edit ${type} with ID: ${id}`);
        }

        function deleteItem(type, id) {
            if (confirm(`Are you sure you want to delete this ${type}?`)) {
                fetch('admin.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=delete_admin_data&type=${type}&id=${id}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        loadTabData(currentTab);
                    } else {
                        alert('Error deleting item: ' + data.message);
                    }
                });
            }
        }

        function saveData(type) {
            const form = document.getElementById(`${type}Form`);
            const formData = new FormData(form);
            const data = {};
            formData.forEach((value, key) => data[key] = value);
            
            const params = new URLSearchParams();
            params.append('action', 'save_admin_data');
            params.append('type', type);
            Object.keys(data).forEach(key => {
                params.append(key, data[key]);
            });

            fetch('admin.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: params
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    closeModal(type);
                    loadTabData(currentTab);
                } else {
                    alert('Error saving data: ' + result.message);
                }
            });
        }

        function loadStudentsForPlacement() {
            fetch('admin.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=get_admin_data&type=students'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const select = document.getElementById('placement_student');
                    select.innerHTML = '<option value="">Select Student</option>';
                    data.data.forEach(student => {
                        select.innerHTML += `<option value="${student.id}">${student.name} (${student.roll})</option>`;
                    });
                }
            });
        }

        function loadCompaniesForPlacement() {
            fetch('admin.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=get_admin_data&type=companies'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const select = document.getElementById('placement_company');
                    select.innerHTML = '<option value="">Select Company</option>';
                    data.data.forEach(company => {
                        select.innerHTML += `<option value="${company.id}">${company.name}</option>`;
                    });
                }
            });
        }
        <?php endif; ?>
    </script>

    <style>
        .admin-tabs {
            margin-top: 2rem;
        }

        .tabs {
            display: flex;
            border-bottom: 2px solid #e9ecef;
            margin-bottom: 1.5rem;
        }

        .tab-btn {
            padding: 1rem 2rem;
            background: none;
            border: none;
            cursor: pointer;
            font-weight: 600;
            color: #6c757d;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }

        .tab-btn.active {
            color: #007bff;
            border-bottom-color: #007bff;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .tab-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .btn--danger {
            background-color: #dc3545;
            color: white;
        }

        .btn--danger:hover {
            background-color: #c82333;
        }

        .btn--sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
        }

        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .status-placed {
            background-color: #28a745;
            color: white;
        }

        .status-pending {
            background-color: #ffc107;
            color: black;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        @media (max-width: 768px) {
            .tabs {
                flex-direction: column;
            }
            
            .tab-btn {
                text-align: left;
                border-bottom: 1px solid #e9ecef;
                border-left: 3px solid transparent;
            }
            
            .tab-btn.active {
                border-left-color: #007bff;
                border-bottom-color: #e9ecef;
            }
            
            .tab-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
        }
    </style>
</body>
</html>