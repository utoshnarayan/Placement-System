<?php
// placements.php - Placements page with backend
session_start();

// Database connection
try {
    $db = new PDO('sqlite:placement_system.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Continue without database for frontend
}

// Handle API requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'get_placements':
            getPlacements($db);
            break;
        case 'get_filter_options':
            getFilterOptions($db);
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Unknown action']);
    }
    exit;
}

function getPlacements($db) {
    try {
        $page = intval($_POST['page'] ?? 1);
        $perPage = intval($_POST['perPage'] ?? 10);
        $search = $_POST['search'] ?? '';
        $department = $_POST['department'] ?? 'all';
        $company = $_POST['company'] ?? 'all';
        $year = $_POST['year'] ?? 'all';
        $status = $_POST['status'] ?? 'all';
        $packageMin = $_POST['packageMin'] ?? '';
        $packageMax = $_POST['packageMax'] ?? '';
        $sort = $_POST['sort'] ?? 'name:asc';
        
        $whereClauses = [];
        $params = [];
        
        // Build filters
        if (!empty($search)) {
            $whereClauses[] = "(s.name LIKE ? OR s.roll LIKE ? OR c.name LIKE ?)";
            $searchTerm = "%$search%";
            array_push($params, $searchTerm, $searchTerm, $searchTerm);
        }
        
        if ($department !== 'all') {
            $whereClauses[] = "s.department = ?";
            $params[] = $department;
        }
        
        if ($company !== 'all') {
            $whereClauses[] = "c.name = ?";
            $params[] = $company;
        }
        
        if ($year !== 'all') {
            $whereClauses[] = "p.year = ?";
            $params[] = $year;
        }
        
        if ($status !== 'all') {
            $whereClauses[] = "p.status = ?";
            $params[] = $status;
        }
        
        if (!empty($packageMin)) {
            $whereClauses[] = "p.package >= ?";
            $params[] = $packageMin * 100000;
        }
        
        if (!empty($packageMax)) {
            $whereClauses[] = "p.package <= ?";
            $params[] = $packageMax * 100000;
        }
        
        $whereSQL = $whereClauses ? 'WHERE ' . implode(' AND ', $whereClauses) : '';
        
        // Get total count
        $countSQL = "SELECT COUNT(*) FROM placements p 
                    JOIN students s ON p.student_id = s.id 
                    JOIN companies c ON p.company_id = c.id 
                    $whereSQL";
        $stmt = $db->prepare($countSQL);
        $stmt->execute($params);
        $total = $stmt->fetchColumn();
        
        // Build sort
        $sortField = explode(':', $sort)[0];
        $sortOrder = explode(':', $sort)[1] ?? 'asc';
        
        $orderBy = 's.name ASC';
        switch ($sortField) {
            case 'name': $orderBy = "s.name $sortOrder"; break;
            case 'package': $orderBy = "p.package $sortOrder"; break;
            case 'year': $orderBy = "p.year $sortOrder"; break;
        }
        
        // Get data
        $offset = ($page - 1) * $perPage;
        $sql = "SELECT p.*, s.name, s.roll, s.department, c.name as company 
                FROM placements p 
                JOIN students s ON p.student_id = s.id 
                JOIN companies c ON p.company_id = c.id 
                $whereSQL 
                ORDER BY $orderBy 
                LIMIT $perPage OFFSET $offset";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $placements = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => $placements,
            'total' => $total,
            'page' => $page,
            'totalPages' => ceil($total / $perPage)
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error fetching placements']);
    }
}

function getFilterOptions($db) {
    try {
        // Departments
        $stmt = $db->query("SELECT DISTINCT department FROM students ORDER BY department");
        $departments = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Companies
        $stmt = $db->query("SELECT DISTINCT name FROM companies ORDER BY name");
        $companies = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Years
        $stmt = $db->query("SELECT DISTINCT year FROM placements ORDER BY year DESC");
        $years = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo json_encode([
            'success' => true,
            'data' => [
                'departments' => $departments,
                'companies' => $companies,
                'years' => $years
            ]
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error fetching filter options']);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Placement Records - Placement Management System</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header__content">
                <div class="header__logo">
                    <img src="assets\images\logo.png" alt="University Logo">
                    <h1>Placement Management</h1>
                </div>
                <nav class="nav">
                    <ul class="nav__list">
                        <li><a href="index.php" class="nav__link">Home</a></li>
                        <li><a href="placements.php" class="nav__link active">Placements</a></li>
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
        <section class="placements-section">
            <div class="container">
                <div class="section-header">
                    <h1>Placement Records</h1>
                    <p>Browse and search through all placement records</p>
                </div>

                <!-- Search and Filters -->
                <div class="search-filters card">
                    <div class="search-box">
                        <label for="searchInput" class="filter-label">Search</label>
                        <input type="text" id="searchInput" class="form-control" placeholder="Search by name, roll no, company...">
                    </div>
                    
                    <div class="filter-group">
                        <label for="departmentFilter" class="filter-label">Department</label>
                        <select id="departmentFilter" class="form-control">
                            <option value="all">All Departments</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="companyFilter" class="filter-label">Company</label>
                        <select id="companyFilter" class="form-control">
                            <option value="all">All Companies</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="yearFilter" class="filter-label">Year</label>
                        <select id="yearFilter" class="form-control">
                            <option value="all">All Years</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="statusFilter" class="filter-label">Status</label>
                        <select id="statusFilter" class="form-control">
                            <option value="all">All Status</option>
                            <option value="Placed">Placed</option>
                            <option value="Pending">Pending</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="packageMin" class="filter-label">Min Package (LPA)</label>
                        <input type="number" id="packageMin" class="form-control" placeholder="Min" min="0" step="0.1">
                    </div>

                    <div class="filter-group">
                        <label for="packageMax" class="filter-label">Max Package (LPA)</label>
                        <input type="number" id="packageMax" class="form-control" placeholder="Max" min="0" step="0.1">
                    </div>

                    <button class="btn btn--secondary" id="clearFilters">Clear Filters</button>
                </div>

                <!-- Results Count and Sort -->
                <div class="results-header">
                    <div class="results-count" id="resultsCount">Loading...</div>
                    <div class="sort-controls">
                        <label for="sortSelect" class="filter-label">Sort by:</label>
                        <select id="sortSelect" class="form-control">
                            <option value="name:asc">Name (A-Z)</option>
                            <option value="name:desc">Name (Z-A)</option>
                            <option value="package:desc">Package (High to Low)</option>
                            <option value="package:asc">Package (Low to High)</option>
                            <option value="year:desc">Year (Newest)</option>
                            <option value="year:asc">Year (Oldest)</option>
                        </select>
                    </div>
                </div>

                <!-- Placements Table -->
                <div class="table-container card">
                    <table class="table" id="placementsTable">
                        <thead>
                            <tr>
                                <th scope="col">Student</th>
                                <th scope="col">Roll No</th>
                                <th scope="col">Department</th>
                                <th scope="col">Company</th>
                                <th scope="col">Package</th>
                                <th scope="col">Year</th>
                                <th scope="col">Status</th>
                            </tr>
                        </thead>
                        <tbody id="placementsTableBody">
                            <!-- Will be populated by JavaScript -->
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="pagination" id="pagination">
                    <div class="pagination__info" id="paginationInfo">Page 1 of 1</div>
                    <div class="pagination__controls">
                        <button class="pagination__btn" id="prevPage" disabled>Previous</button>
                        <div class="pagination__pages" id="paginationPages"></div>
                        <button class="pagination__btn" id="nextPage" disabled>Next</button>
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
    let currentPage = 1;
    const perPage = 10;
    let totalPages = 1;

    document.addEventListener('DOMContentLoaded', function() {
        loadFilterOptions();
        loadPlacements();
        setupEventListeners();
    });

    function loadFilterOptions() {
        fetch('placements.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=get_filter_options'
        })
        .then(r => r.json())
        .then(data => {
            if (!data.success) return;

            const deptSelect = document.getElementById('departmentFilter');
            data.data.departments.forEach(dept => {
                deptSelect.innerHTML += `<option value="${dept}">${dept}</option>`;
            });

            const companySelect = document.getElementById('companyFilter');
            data.data.companies.forEach(company => {
                companySelect.innerHTML += `<option value="${company}">${company}</option>`;
            });

            const yearSelect = document.getElementById('yearFilter');
            data.data.years.forEach(year => {
                yearSelect.innerHTML += `<option value="${year}">${year}</option>`;
            });
        });
    }

    function loadPlacements() {
        const filters = {
            search: document.getElementById('searchInput').value,
            department: document.getElementById('departmentFilter').value,
            company: document.getElementById('companyFilter').value,
            year: document.getElementById('yearFilter').value,
            status: document.getElementById('statusFilter').value,
            packageMin: document.getElementById('packageMin').value,
            packageMax: document.getElementById('packageMax').value,
            sort: document.getElementById('sortSelect').value,
            page: currentPage,
            perPage: perPage
        };

        const params = new URLSearchParams();
        params.append('action', 'get_placements');
        Object.keys(filters).forEach(key => params.append(key, filters[key]));

        fetch('placements.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: params
        })
        .then(r => r.json())
        .then(data => {
            if (!data.success) return;

            updateTable(data.data);
            updatePagination(data.total, data.page, data.totalPages);

            document.getElementById('resultsCount').textContent =
                `Showing ${data.data.length} of ${data.total} records`;
        });
    }

    function updateTable(placements) {
        const tbody = document.getElementById('placementsTableBody');
        tbody.innerHTML = '';

        if (placements.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:20px;">No placements found</td></tr>';
            return;
        }

        placements.forEach(p => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${escapeHtml(p.name)}</td>
                <td>${escapeHtml(p.roll)}</td>
                <td>${escapeHtml(p.department)}</td>
                <td>${escapeHtml(p.company)}</td>
                <td>₹${(p.package / 100000).toFixed(1)}L</td>
                <td>${p.year}</td>
                <td><span class="status-badge status-${p.status.toLowerCase()}">${p.status}</span></td>
            `;
            tbody.appendChild(row);
        });
    }

    function updatePagination(total, page, tPages) {
        currentPage = page;
        totalPages = tPages;

        document.getElementById('paginationInfo').textContent = `Page ${page} of ${tPages}`;
        document.getElementById('prevPage').disabled = page <= 1;
        document.getElementById('nextPage').disabled = page >= tPages;

        const pagesContainer = document.getElementById('paginationPages');
        pagesContainer.innerHTML = '';

        for (let i = 1; i <= tPages; i++) {
            const btn = document.createElement('button');
            btn.className = `pagination__btn ${i === page ? 'active' : ''}`;
            btn.textContent = i;
            btn.onclick = () => {
                currentPage = i;
                loadPlacements();
            };
            pagesContainer.appendChild(btn);
        }
    }

    function setupEventListeners() {
        document.getElementById('searchInput').addEventListener('input', debounce(loadPlacements, 300));
        document.getElementById('departmentFilter').addEventListener('change', loadPlacements);
        document.getElementById('companyFilter').addEventListener('change', loadPlacements);
        document.getElementById('yearFilter').addEventListener('change', loadPlacements);
        document.getElementById('statusFilter').addEventListener('change', loadPlacements);
        document.getElementById('packageMin').addEventListener('input', debounce(loadPlacements, 300));
        document.getElementById('packageMax').addEventListener('input', debounce(loadPlacements, 300));
        document.getElementById('sortSelect').addEventListener('change', loadPlacements);

        document.getElementById('clearFilters').addEventListener('click', () => {
            document.getElementById('searchInput').value = '';
            document.getElementById('departmentFilter').value = 'all';
            document.getElementById('companyFilter').value = 'all';
            document.getElementById('yearFilter').value = 'all';
            document.getElementById('statusFilter').value = 'all';
            document.getElementById('packageMin').value = '';
            document.getElementById('packageMax').value = '';
            document.getElementById('sortSelect').value = 'name:asc';
            loadPlacements();
        });

        document.getElementById('prevPage').addEventListener('click', () => {
            if (currentPage > 1) {
                currentPage--;
                loadPlacements();
            }
        });

        document.getElementById('nextPage').addEventListener('click', () => {
            if (currentPage < totalPages) {
                currentPage++;
                loadPlacements();
            }
        });
    }

    function debounce(fn, delay) {
        let t;
        return (...args) => {
            clearTimeout(t);
            t = setTimeout(() => fn(...args), delay);
        };
    }

    function escapeHtml(str) {
        return str.replace(/[&<>"']/g, m => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;',
            '"': '&quot;', "'": '&#039;'
        })[m]);
    }
</script>


    <style>
        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
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
    </style>
</body>
</html>