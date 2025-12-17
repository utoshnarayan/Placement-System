<?php
// analytics.php
session_start();

try {
    $db = new PDO('sqlite:placement_system.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed.");
}

// Handle AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    switch ($_POST['action']) {
        case 'get_analytics':
            getAnalytics($db);
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Unknown action']);
    }
    exit;
}

function getAnalytics($db) {
    try {
        // Basic stats
        $totalStudents = $db->query("SELECT COUNT(*) FROM students")->fetchColumn();
        $totalCompanies = $db->query("SELECT COUNT(*) FROM companies")->fetchColumn();
        $placedStudents = $db->query("SELECT COUNT(*) FROM placements WHERE status='Placed'")->fetchColumn();
        $highestPackage = $db->query("SELECT MAX(package) FROM placements")->fetchColumn();

        $placementRate = $totalStudents > 0 ? round(($placedStudents / $totalStudents) * 100, 1) : 0;
        $highestLPA = $highestPackage ? round($highestPackage / 100000, 1) : 0;

        // Department-wise stats
        $deptStats = $db->query("
            SELECT s.department AS department,
                   COUNT(p.id) AS placed,
                   AVG(p.package) AS avg_package
            FROM students s
            LEFT JOIN placements p ON s.id = p.student_id
            GROUP BY s.department
        ")->fetchAll(PDO::FETCH_ASSOC);

        // Company-wise hires
        $companyStats = $db->query("
            SELECT c.name AS company,
                   COUNT(p.id) AS hires
            FROM companies c
            LEFT JOIN placements p ON c.id = p.company_id
            GROUP BY c.name
        ")->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'data' => [
                'totalStudents' => $totalStudents,
                'totalCompanies' => $totalCompanies,
                'placementRate' => $placementRate,
                'highestPackage' => $highestLPA,
                'deptStats' => $deptStats,
                'companyStats' => $companyStats
            ]
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics | Placement Management</title>
    <link rel="stylesheet" href="css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .chart-container {
            width: 90%;
            max-width: 800px;
            margin: 30px auto;
            background: var(--card-bg, #fff);
            padding: 20px;
            border-radius: 16px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        .stats-grid {
            display: flex;
            justify-content: space-around;
            flex-wrap: wrap;
            gap: 20px;
            margin-top: 20px;
        }
        .stat-box {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            flex: 1 1 200px;
        }
        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: #007bff;
        }
        .stat-label {
            font-size: 1rem;
            color: #555;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header__content">
                <div class="header__logo">
                    <img src="assets/images/logo.png" alt="University Logo">
                    <h1>Placement Analytics</h1>
                </div>
                <nav class="nav">
                    <ul class="nav__list">
                        <li><a href="index.php" class="nav__link">Home</a></li>
                        <li><a href="placements.php" class="nav__link">Placements</a></li>
                        <li><a href="company.php" class="nav__link">Companies</a></li>
                        <li><a href="analytics.php" class="nav__link active">Analytics</a></li>
                        <li><a href="admin.php" class="nav__link">Admin</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>

    <main class="main">
        <div class="container">
            <section class="stats-grid" id="statsGrid">
                <div class="stat-box">
                    <div class="stat-value" id="totalStudents">0</div>
                    <div class="stat-label">Total Students</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value" id="totalCompanies">0</div>
                    <div class="stat-label">Total Companies</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value" id="placementRate">0%</div>
                    <div class="stat-label">Placement Rate</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value" id="highestPackage">₹0L</div>
                    <div class="stat-label">Highest Package</div>
                </div>
            </section>

            <div class="chart-container">
                <h2>Department-wise Placements</h2>
                <canvas id="deptChart"></canvas>
            </div>

            <div class="chart-container">
                <h2>Company-wise Hires</h2>
                <canvas id="companyChart"></canvas>
            </div>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            fetch('analytics.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=get_analytics'
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const d = data.data;
                    document.getElementById('totalStudents').textContent = d.totalStudents;
                    document.getElementById('totalCompanies').textContent = d.totalCompanies;
                    document.getElementById('placementRate').textContent = d.placementRate + '%';
                    document.getElementById('highestPackage').textContent = '₹' + d.highestPackage + 'L';

                    // Department Chart
                    const deptNames = d.deptStats.map(i => i.department);
                    const deptPlacements = d.deptStats.map(i => i.placed);
                    new Chart(document.getElementById('deptChart'), {
                        type: 'bar',
                        data: {
                            labels: deptNames,
                            datasets: [{
                                label: 'Placed Students',
                                data: deptPlacements,
                                borderWidth: 1
                            }]
                        },
                        options: { scales: { y: { beginAtZero: true } } }
                    });

                    // Company Chart
                    const compNames = d.companyStats.map(i => i.company);
                    const compHires = d.companyStats.map(i => i.hires);
                    new Chart(document.getElementById('companyChart'), {
                        type: 'pie',
                        data: {
                            labels: compNames,
                            datasets: [{
                                label: 'Company Hires',
                                data: compHires
                            }]
                        }
                    });
                } else {
                    alert('Error loading analytics: ' + data.message);
                }
            })
            .catch(err => console.error('Fetch error:', err));
        });
    </script>
</body>
</html>
