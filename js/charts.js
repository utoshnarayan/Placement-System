import placementAPI from './api.js';
import { showToast } from './ui/toast.js';

class AnalyticsCharts {
    constructor() {
        this.charts = {};
        this.init();
    }

    async init() {
        try {
            await this.loadData();
            this.initCharts();
            this.updateMetrics();
            this.renderStatsTables();
        } catch (error) {
            console.error('Error initializing charts:', error);
            showToast('Error loading analytics data', 'error');
        }
    }

    async loadData() {
        const placements = await placementAPI.getPlacements({ perPage: 1000 });
        const companies = await placementAPI.getCompanies();
        const stats = await placementAPI.getDashboardStats();
        
        this.placements = placements.data;
        this.companies = companies;
        this.stats = stats;
    }

    initCharts() {
        this.createPlacementsByYearChart();
        this.createPlacementsByDeptChart();
        this.createPackageDistributionChart();
        this.createCompaniesChart();
        this.createMonthlyTrendChart();
        this.createStatusChart();
    }

    createPlacementsByYearChart() {
        const ctx = document.getElementById('placementsByYearChart').getContext('2d');
        
        // Group by year
        const yearData = {};
        this.placements.forEach(placement => {
            if (!yearData[placement.year]) {
                yearData[placement.year] = 0;
            }
            yearData[placement.year]++;
        });

        const years = Object.keys(yearData).sort();
        const counts = years.map(year => yearData[year]);

        this.charts.placementsByYear = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: years,
                datasets: [{
                    label: 'Placements',
                    data: counts,
                    backgroundColor: '#2563eb',
                    borderColor: '#1d4ed8',
                    borderWidth: 1,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `Placements: ${context.parsed.y}`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Number of Placements'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Year'
                        }
                    }
                }
            }
        });
    }

    createPlacementsByDeptChart() {
        const ctx = document.getElementById('placementsByDeptChart').getContext('2d');
        
        const deptData = this.stats.departmentStats;
        const departments = Object.keys(deptData);
        const counts = departments.map(dept => deptData[dept].count);

        // Generate colors for departments
        const backgroundColors = this.generateColors(departments.length);

        this.charts.placementsByDept = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: departments,
                datasets: [{
                    data: counts,
                    backgroundColor: backgroundColors,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'right'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((context.parsed / total) * 100).toFixed(1);
                                return `${context.label}: ${context.parsed} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    }

    createPackageDistributionChart() {
        const ctx = document.getElementById('packageDistributionChart').getContext('2d');
        
        // Create package ranges
        const packages = this.placements.map(p => p.package / 100000); // Convert to LPA
        const minPackage = Math.floor(Math.min(...packages));
        const maxPackage = Math.ceil(Math.max(...packages));
        
        const ranges = [];
        const counts = [];
        const rangeSize = 2; // 2 LPA per range

        for (let i = minPackage; i < maxPackage; i += rangeSize) {
            const rangeStart = i;
            const rangeEnd = i + rangeSize;
            const count = packages.filter(pkg => pkg >= rangeStart && pkg < rangeEnd).length;
            
            ranges.push(`₹${rangeStart}-${rangeEnd}L`);
            counts.push(count);
        }

        this.charts.packageDistribution = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ranges,
                datasets: [{
                    label: 'Students',
                    data: counts,
                    backgroundColor: '#10b981',
                    borderColor: '#059669',
                    borderWidth: 1,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Number of Students'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Package Range (LPA)'
                        }
                    }
                }
            }
        });
    }

    createCompaniesChart() {
        const ctx = document.getElementById('companiesChart').getContext('2d');
        
        // Get top 8 companies by hires
        const topCompanies = this.companies
            .sort((a, b) => b.hires - a.hires)
            .slice(0, 8);

        const companyNames = topCompanies.map(c => c.name);
        const hires = topCompanies.map(c => c.hires);

        this.charts.companies = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: companyNames,
                datasets: [{
                    data: hires,
                    backgroundColor: this.generateColors(companyNames.length)
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'right'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((context.parsed / total) * 100).toFixed(1);
                                return `${context.label}: ${context.parsed} hires (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    }

    createMonthlyTrendChart() {
        const ctx = document.getElementById('monthlyTrendChart').getContext('2d');
        
        // Group by month (mock data for demonstration)
        const monthlyData = {
            'Jan': 8, 'Feb': 12, 'Mar': 15, 'Apr': 10, 'May': 5,
            'Jun': 3, 'Jul': 7, 'Aug': 14, 'Sep': 18, 'Oct': 22,
            'Nov': 16, 'Dec': 11
        };

        const months = Object.keys(monthlyData);
        const counts = Object.values(monthlyData);

        this.charts.monthlyTrend = new Chart(ctx, {
            type: 'line',
            data: {
                labels: months,
                datasets: [{
                    label: 'Placements',
                    data: counts,
                    borderColor: '#f59e0b',
                    backgroundColor: 'rgba(245, 158, 11, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Number of Placements'
                        }
                    }
                }
            }
        });
    }

    createStatusChart() {
        const ctx = document.getElementById('statusChart').getContext('2d');
        
        const placed = this.placements.filter(p => p.status === 'Placed').length;
        const pending = this.placements.filter(p => p.status === 'Pending').length;

        this.charts.status = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Placed', 'Pending'],
                datasets: [{
                    data: [placed, pending],
                    backgroundColor: ['#10b981', '#f59e0b'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'right'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((context.parsed / total) * 100).toFixed(1);
                                return `${context.label}: ${context.parsed} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    }

    updateMetrics() {
        document.getElementById('totalPlacements').textContent = this.stats.totalStudents;
        document.getElementById('placementRate').textContent = `${this.stats.placementRate}%`;
        
        const avgPackage = this.placements.reduce((sum, p) => sum + p.package, 0) / this.placements.length;
        document.getElementById('avgPackage').textContent = `₹${(avgPackage / 100000).toFixed(1)}L`;
        
        const topCompany = this.companies.reduce((prev, current) => 
            (prev.hires > current.hires) ? prev : current
        );
        document.getElementById('topCompany').textContent = topCompany.name;
    }

    renderStatsTables() {
        this.renderDeptStats();
        this.renderCompanyStats();
    }

    renderDeptStats() {
        const tbody = document.getElementById('deptStatsBody');
        const deptData = this.stats.departmentStats;
        
        tbody.innerHTML = Object.entries(deptData).map(([dept, data]) => {
            const avgPackage = data.totalPackage / data.count;
            const placementRate = Math.round((data.count / this.stats.totalStudents) * 100);
            const highestPackage = Math.max(...this.placements
                .filter(p => p.department === dept)
                .map(p => p.package)
            );

            return `
                <tr>
                    <td>${dept}</td>
                    <td>${data.count}</td>
                    <td>₹${(avgPackage / 100000).toFixed(1)}L</td>
                    <td>₹${(highestPackage / 100000).toFixed(1)}L</td>
                    <td>${placementRate}%</td>
                </tr>
            `;
        }).join('');
    }

    renderCompanyStats() {
        const tbody = document.getElementById('companyStatsBody');
        
        tbody.innerHTML = this.companies.map(company => {
            const companyPlacements = this.placements.filter(p => p.company === company.name);
            const departments = [...new Set(companyPlacements.map(p => p.department))].join(', ');

            return `
                <tr>
                    <td>${company.name}</td>
                    <td>${company.hires}</td>
                    <td>₹${(company.averagePackage / 100000).toFixed(1)}L</td>
                    <td>₹${(company.highestPackage / 100000).toFixed(1)}L</td>
                    <td>${departments || '-'}</td>
                </tr>
            `;
        }).join('');
    }

    generateColors(count) {
        const baseColors = [
            '#2563eb', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6',
            '#06b6d4', '#84cc16', '#f97316', '#ec4899', '#64748b'
        ];
        
        const colors = [];
        for (let i = 0; i < count; i++) {
            colors.push(baseColors[i % baseColors.length]);
        }
        return colors;
    }

    // Method to update charts when data changes
    async refresh() {
        await this.loadData();
        
        // Update all charts
        Object.values(this.charts).forEach(chart => {
            chart.destroy();
        });
        
        this.initCharts();
        this.updateMetrics();
        this.renderStatsTables();
    }
}

// Initialize charts when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new AnalyticsCharts();
});

// Add analytics-specific CSS
const analyticsStyle = document.createElement('style');
analyticsStyle.textContent = `
    .analytics-metrics {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: var(--space-lg);
        margin-bottom: var(--space-2xl);
    }

    .metric-card {
        text-align: center;
        padding: var(--space-xl);
        transition: var(--transition);
    }

    .metric-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
    }

    .metric-card__value {
        font-size: 2.5rem;
        font-weight: 800;
        color: var(--primary);
        margin-bottom: var(--space-sm);
    }

    .metric-card__label {
        color: var(--text-muted);
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.875rem;
        letter-spacing: 0.5px;
    }

    .charts-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
        gap: var(--space-lg);
        margin-bottom: var(--space-2xl);
    }

    .chart-container {
        padding: var(--space-lg);
    }

    .chart-header {
        margin-bottom: var(--space-lg);
        text-align: center;
    }

    .chart-header h3 {
        font-size: 1.25rem;
        font-weight: 700;
        margin-bottom: var(--space-sm);
        color: var(--text);
    }

    .chart-header p {
        color: var(--text-muted);
        font-size: 0.875rem;
    }

    .chart-container canvas {
        max-height: 300px;
    }

    .detailed-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
        gap: var(--space-lg);
    }

    .stats-section {
        padding: var(--space-lg);
    }

    .stats-section h3 {
        margin-bottom: var(--space-lg);
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--text);
    }

    .stats-table {
        overflow-x: auto;
    }

    @media (max-width: 1024px) {
        .charts-grid {
            grid-template-columns: 1fr;
        }
        
        .detailed-stats {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 768px) {
        .analytics-metrics {
            grid-template-columns: repeat(2, 1fr);
        }
        
        .charts-grid {
            grid-template-columns: 1fr;
        }
        
        .chart-container {
            padding: var(--space-md);
        }
        
        .metric-card {
            padding: var(--space-lg);
        }
        
        .metric-card__value {
            font-size: 2rem;
        }
    }

    @media (max-width: 480px) {
        .analytics-metrics {
            grid-template-columns: 1fr;
        }
    }
`;
document.head.appendChild(analyticsStyle);