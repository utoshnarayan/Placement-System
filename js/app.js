import placementAPI from './api.js';
import { showToast } from './ui/toast.js';

// Initialize the application
document.addEventListener('DOMContentLoaded', function() {
    initializeTheme();
    initializePage();
});

// Initialize theme (dark/light mode)
function initializeTheme() {
    const themeToggle = document.getElementById('themeToggle');
    const savedTheme = localStorage.getItem('theme') || 
                      (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
    
    // Apply saved theme
    if (savedTheme === 'dark') {
        document.documentElement.classList.add('dark');
        themeToggle.innerHTML = '<span class="theme-toggle__icon">☀️</span>';
    } else {
        document.documentElement.classList.remove('dark');
        themeToggle.innerHTML = '<span class="theme-toggle__icon">🌙</span>';
    }
    
    // Toggle theme on button click
    themeToggle.addEventListener('click', function() {
        if (document.documentElement.classList.contains('dark')) {
            document.documentElement.classList.remove('dark');
            localStorage.setItem('theme', 'light');
            themeToggle.innerHTML = '<span class="theme-toggle__icon">🌙</span>';
        } else {
            document.documentElement.classList.add('dark');
            localStorage.setItem('theme', 'dark');
            themeToggle.innerHTML = '<span class="theme-toggle__icon">☀️</span>';
        }
    });
}

// Initialize page-specific functionality
function initializePage() {
    const currentPage = window.location.pathname.split('/').pop();
    
    switch (currentPage) {
        case 'index.php':
        case '':
            initializeHomePage();
            break;
        case 'placements.php':
            initializePlacementsPage();
            break;
        case 'company.php':
            initializeCompanyPage();
            break;
        case 'analytics.php':
            initializeAnalyticsPage();
            break;
        case 'admin.php':
            initializeAdminPage();
            break;
        
    }
}

// Home page initialization
async function initializeHomePage() {
    try {
        const stats = await placementAPI.getDashboardStats();
        
        // Update stats on the page
        document.getElementById('totalStudents').textContent = stats.totalStudents;
        document.getElementById('totalCompanies').textContent = stats.totalCompanies;
        document.getElementById('placementRate').textContent = `${stats.placementRate}%`;
        document.getElementById('highestPackage').textContent = `₹${(stats.highestPackage / 100000).toFixed(1)}L`;
    } catch (error) {
        console.error('Error loading dashboard stats:', error);
        showToast('Error loading statistics', 'error');
    }
}

// Placements page initialization
function initializePlacementsPage() {
    // This will be handled by the table component
    console.log('Placements page initialized');
}

// Company page initialization
function initializeCompanyPage() {
    // This will be handled by the company component
    console.log('Company page initialized');
}

// Analytics page initialization
function initializeAnalyticsPage() {
    // This will be handled by the charts component
    console.log('Analytics page initialized');
}

// Admin page initialization
function initializeAdminPage() {
    const isLoggedIn = sessionStorage.getItem('adminLoggedIn') === 'true';
    
    if (isLoggedIn) {
        showAdminDashboard();
    } else {
        showAdminLogin();
    }
}



// Show admin login form
function showAdminLogin() {
    const main = document.querySelector('main');
    main.innerHTML = `
        <section class="admin-login">
            <div class="container">
                <div class="admin-login__card card">
                    <div class="admin-login__header">
                        <h2>Admin Login</h2>
                        <p>Access the admin dashboard to manage placement records</p>
                    </div>
                    <form class="admin-login__form form" id="adminLoginForm">
                        <div class="form-group">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" id="email" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" id="password" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn--primary">Login</button>
                    </form>
                    <div class="admin-login__demo">
                        <h3>Demo Credentials</h3>
                        <p>Email: admin@example.com</p>
                        <p>Password: Admin@123</p>
                    </div>
                </div>
            </div>
        </section>
    `;
    
    // Handle login form submission
    document.getElementById('adminLoginForm').addEventListener('submit', handleAdminLogin);
}

// Handle admin login
async function handleAdminLogin(e) {
    e.preventDefault();
    
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    
    // Mock validation - in a real app, this would be handled by a backend
    if (email === 'admin@example.com' && password === 'Admin@123') {
        sessionStorage.setItem('adminLoggedIn', 'true');
        sessionStorage.setItem('adminUser', email);
        showAdminDashboard();
        showToast('Login successful!', 'success');
    } else {
        showToast('Invalid credentials. Please try again.', 'error');
    }
}

// Show admin dashboard
function showAdminDashboard() {
    const main = document.querySelector('main');
    main.innerHTML = `
        <section class="admin-dashboard">
            <div class="container">
                <div class="admin-dashboard__header">
                    <h1>Admin Dashboard</h1>
                    <div class="admin-dashboard__actions">
                        <button class="btn btn--primary" id="addPlacementBtn">Add Placement</button>
                        <button class="btn btn--secondary" id="exportBtn">Export Data</button>
                        <button class="btn btn--secondary" id="importBtn">Import Data</button>
                        <button class="btn btn--danger" id="logoutBtn">Logout</button>
                    </div>
                </div>
                
                <div class="admin-dashboard__stats">
                    <div class="stat-card">
                        <div class="stat-card__value" id="adminTotalStudents">0</div>
                        <div class="stat-card__label">Total Students</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-card__value" id="adminPlacedStudents">0</div>
                        <div class="stat-card__label">Placed Students</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-card__value" id="adminPlacementRate">0%</div>
                        <div class="stat-card__label">Placement Rate</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-card__value" id="adminHighestPackage">₹0L</div>
                        <div class="stat-card__label">Highest Package</div>
                    </div>
                </div>
                
                <div class="admin-dashboard__content">
                    <div class="admin-dashboard__section">
                        <h2>Recent Activity</h2>
                        <div class="activity-log card" id="activityLog">
                            <!-- Activity log will be populated here -->
                        </div>
                    </div>
                    
                    <div class="admin-dashboard__section">
                        <h2>Quick Actions</h2>
                        <div class="quick-actions">
                            <button class="btn btn--secondary" id="resetDataBtn">Reset Sample Data</button>
                            <button class="btn btn--secondary" id="bulkDeleteBtn">Bulk Delete</button>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    `;
    
    // Load dashboard data
    loadAdminDashboardData();
    
    // Add event listeners
    document.getElementById('logoutBtn').addEventListener('click', handleAdminLogout);
    document.getElementById('addPlacementBtn').addEventListener('click', () => {
        // This would open the placement form modal
        showToast('Add placement feature would open here', 'info');
    });
    document.getElementById('resetDataBtn').addEventListener('click', handleResetData);
}

// Load admin dashboard data
async function loadAdminDashboardData() {
    try {
        const stats = await placementAPI.getDashboardStats();
        const activityLog = await placementAPI.getActivityLog();
        
        // Update stats
        document.getElementById('adminTotalStudents').textContent = stats.totalStudents;
        document.getElementById('adminPlacedStudents').textContent = stats.placedStudents;
        document.getElementById('adminPlacementRate').textContent = `${stats.placementRate}%`;
        document.getElementById('adminHighestPackage').textContent = `₹${(stats.highestPackage / 100000).toFixed(1)}L`;
        
        // Update activity log
        const activityLogElement = document.getElementById('activityLog');
        activityLogElement.innerHTML = activityLog.slice(0, 10).map(activity => `
            <div class="activity-item">
                <div class="activity-item__header">
                    <span class="activity-item__action">${activity.action}</span>
                    <span class="activity-item__time">${new Date(activity.timestamp).toLocaleString()}</span>
                </div>
                <div class="activity-item__description">${activity.description}</div>
                <div class="activity-item__user">By: ${activity.user}</div>
            </div>
        `).join('');
        
    } catch (error) {
        console.error('Error loading admin dashboard:', error);
        showToast('Error loading dashboard data', 'error');
    }
}

// Handle admin logout
function handleAdminLogout() {
    sessionStorage.removeItem('adminLoggedIn');
    sessionStorage.removeItem('adminUser');
    showAdminLogin();
    showToast('Logged out successfully', 'success');
}

// Handle reset data
async function handleResetData() {
    if (confirm('Are you sure you want to reset all data to sample data? This action cannot be undone.')) {
        try {
            await placementAPI.resetData();
            showToast('Data reset successfully', 'success');
            loadAdminDashboardData();
        } catch (error) {
            console.error('Error resetting data:', error);
            showToast('Error resetting data', 'error');
        }
    }
}