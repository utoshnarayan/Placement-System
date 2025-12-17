<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Companies - Placement Management System</title>
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
                        <li><a href="index.php" class="nav__link">Home</a></li>
                        <li><a href="placements.php" class="nav__link">Placements</a></li>
                        <li><a href="company.php" class="nav__link active">Companies</a></li>
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
        <section class="companies-section">
            <div class="container">
                <div class="section-header">
                    <h1>Partner Companies</h1>
                    <p>Our recruiting partners and their placement statistics</p>
                </div>

                <!-- Company Search -->
                <div class="search-filters">
                    <div class="search-box">
                        <input type="text" id="companySearch" class="form-control" placeholder="Search companies...">
                    </div>
                </div>

                <!-- Companies Grid -->
                <div class="companies-grid" id="companiesGrid">
                    <!-- Skeleton loading -->
                    <div class="company-card skeleton">
                        <div class="skeleton" style="height: 120px; margin-bottom: 1rem;"></div>
                        <div class="skeleton" style="height: 24px; width: 80%; margin-bottom: 0.5rem;"></div>
                        <div class="skeleton" style="height: 16px; width: 60%; margin-bottom: 1rem;"></div>
                        <div class="skeleton" style="height: 16px; width: 100%; margin-bottom: 0.5rem;"></div>
                        <div class="skeleton" style="height: 16px; width: 100%; margin-bottom: 0.5rem;"></div>
                    </div>
                    <!-- Repeat skeleton cards -->
                </div>
            </div>
        </section>
    </main>

    <!-- Company Detail Modal -->
    <div class="modal" id="companyModal" style="display: none;">
        <div class="modal__content">
            <div class="modal__header">
                <h2 class="modal__title" id="companyModalTitle">Company Details</h2>
                <button class="modal__close" id="companyModalClose">&times;</button>
            </div>
            <div class="modal__body" id="companyModalBody">
                <!-- Content will be loaded dynamically -->
            </div>
            <div class="modal__footer">
                <button class="btn btn--secondary" id="companyModalCloseBtn">Close</button>
            </div>
        </div>
    </div>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2025 Placement Management System. All rights reserved.</p>
        </div>
    </footer>

    <script type="module" src="js/app.js"></script>
    <script type="module">
        import placementAPI from './js/api.js';
        import { showToast } from './js/ui/toast.js';

        class CompanyManager {
            constructor() {
                this.companies = [];
                this.filteredCompanies = [];
                this.init();
            }

            async init() {
                await this.loadCompanies();
                this.setupEventListeners();
                this.renderCompanies();
            }

            async loadCompanies() {
                try {
                    this.companies = await placementAPI.getCompanies();
                    this.filteredCompanies = [...this.companies];
                } catch (error) {
                    console.error('Error loading companies:', error);
                    showToast('Error loading companies', 'error');
                }
            }

            setupEventListeners() {
                const searchInput = document.getElementById('companySearch');
                searchInput.addEventListener('input', this.debounce(() => {
                    this.filterCompanies(searchInput.value);
                }, 300));

                // Modal close handlers
                document.getElementById('companyModalClose').addEventListener('click', this.closeModal.bind(this));
                document.getElementById('companyModalCloseBtn').addEventListener('click', this.closeModal.bind(this));
                
                // Close modal on backdrop click
                document.getElementById('companyModal').addEventListener('click', (e) => {
                    if (e.target.id === 'companyModal') {
                        this.closeModal();
                    }
                });
            }

            filterCompanies(searchTerm) {
                if (!searchTerm) {
                    this.filteredCompanies = [...this.companies];
                } else {
                    const term = searchTerm.toLowerCase();
                    this.filteredCompanies = this.companies.filter(company => 
                        company.name.toLowerCase().includes(term) ||
                        company.sector.toLowerCase().includes(term) ||
                        company.description.toLowerCase().includes(term)
                    );
                }
                this.renderCompanies();
            }

            renderCompanies() {
                const grid = document.getElementById('companiesGrid');
                
                if (this.filteredCompanies.length === 0) {
                    grid.innerHTML = `
                        <div class="empty-state">
                            <h3>No companies found</h3>
                            <p>Try adjusting your search terms</p>
                        </div>
                    `;
                    return;
                }

                grid.innerHTML = this.filteredCompanies.map(company => `
                    <div class="company-card card" data-company-id="${company.id}">
                        <div class="company-card__header">
                            <div class="company-card__logo">
                                <img src="${company.logo}" alt="${company.name} logo" onerror="this.src='assets/images/company-placeholder.png'">
                            </div>
                            <div class="company-card__info">
                                <h3 class="company-card__name">${this.escapeHtml(company.name)}</h3>
                                <span class="company-card__sector">${this.escapeHtml(company.sector)}</span>
                            </div>
                        </div>
                        <div class="company-card__stats">
                            <div class="company-stat">
                                <span class="company-stat__value">${company.hires}</span>
                                <span class="company-stat__label">Hires</span>
                            </div>
                            <div class="company-stat">
                                <span class="company-stat__value">₹${(company.highestPackage / 100000).toFixed(1)}L</span>
                                <span class="company-stat__label">Highest</span>
                            </div>
                            <div class="company-stat">
                                <span class="company-stat__value">₹${(company.averagePackage / 100000).toFixed(1)}L</span>
                                <span class="company-stat__label">Average</span>
                            </div>
                        </div>
                        <p class="company-card__description">${this.escapeHtml(company.description)}</p>
                        <div class="company-card__actions">
                            <button class="btn btn--primary btn--sm view-company-btn">View Details</button>
                            <a href="${company.website}" target="_blank" class="btn btn--secondary btn--sm">Website</a>
                        </div>
                    </div>
                `).join('');

                // Add event listeners to view buttons
                grid.querySelectorAll('.view-company-btn').forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        const card = e.target.closest('.company-card');
                        const companyId = card.dataset.companyId;
                        this.showCompanyDetails(companyId);
                    });
                });
            }

            async showCompanyDetails(companyId) {
                try {
                    const company = await placementAPI.getCompanyById(companyId);
                    const placements = await placementAPI.getPlacements({ filters: { company: company.name } });
                    
                    document.getElementById('companyModalTitle').textContent = company.name;
                    document.getElementById('companyModalBody').innerHTML = `
                        <div class="company-details">
                            <div class="company-details__header">
                                <img src="${company.logo}" alt="${company.name} logo" class="company-details__logo" onerror="this.src='assets/images/company-placeholder.png'">
                                <div class="company-details__info">
                                    <h3>${this.escapeHtml(company.name)}</h3>
                                    <p class="company-details__sector">${this.escapeHtml(company.sector)}</p>
                                    <a href="${company.website}" target="_blank" class="company-details__website">${company.website}</a>
                                </div>
                            </div>
                            
                            <div class="company-details__description">
                                <h4>About</h4>
                                <p>${this.escapeHtml(company.description)}</p>
                            </div>
                            
                            <div class="company-details__stats">
                                <div class="stat-card">
                                    <div class="stat-card__value">${company.hires}</div>
                                    <div class="stat-card__label">Total Hires</div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-card__value">₹${(company.highestPackage / 100000).toFixed(1)}L</div>
                                    <div class="stat-card__label">Highest Package</div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-card__value">₹${(company.averagePackage / 100000).toFixed(1)}L</div>
                                    <div class="stat-card__label">Average Package</div>
                                </div>
                            </div>
                            
                            <div class="company-details__placements">
                                <h4>Recent Placements (${placements.total})</h4>
                                ${placements.total > 0 ? `
                                    <div class="placements-list">
                                        ${placements.data.map(placement => `
                                            <div class="placement-item">
                                                <div class="placement-item__student">
                                                    <strong>${this.escapeHtml(placement.name)}</strong>
                                                    <span>${placement.roll}</span>
                                                </div>
                                                <div class="placement-item__details">
                                                    <span>${placement.department}</span>
                                                    <span>₹${(placement.package / 100000).toFixed(1)}L</span>
                                                    <span class="status-badge status-${placement.status.toLowerCase()}">${placement.status}</span>
                                                </div>
                                            </div>
                                        `).join('')}
                                    </div>
                                ` : '<p>No placements recorded yet.</p>'}
                            </div>
                        </div>
                    `;
                    
                    document.getElementById('companyModal').style.display = 'flex';
                } catch (error) {
                    console.error('Error loading company details:', error);
                    showToast('Error loading company details', 'error');
                }
            }

            closeModal() {
                document.getElementById('companyModal').style.display = 'none';
            }

            debounce(func, wait) {
                let timeout;
                return function executedFunction(...args) {
                    const later = () => {
                        clearTimeout(timeout);
                        func(...args);
                    };
                    clearTimeout(timeout);
                    timeout = setTimeout(later, wait);
                };
            }

            escapeHtml(unsafe) {
                return unsafe
                    .replace(/&/g, "&amp;")
                    .replace(/</g, "&lt;")
                    .replace(/>/g, "&gt;")
                    .replace(/"/g, "&quot;")
                    .replace(/'/g, "&#039;");
            }
        }

        // Initialize company manager when DOM is loaded
        document.addEventListener('DOMContentLoaded', () => {
            new CompanyManager();
        });
    </script>

    <style>
        .companies-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: var(--space-lg);
            margin-top: var(--space-lg);
        }

        .company-card {
            display: flex;
            flex-direction: column;
            transition: var(--transition);
        }

        .company-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }

        .company-card__header {
            display: flex;
            align-items: center;
            gap: var(--space-md);
            margin-bottom: var(--space-md);
        }

        .company-card__logo {
            width: 60px;
            height: 60px;
            border-radius: var(--radius-md);
            overflow: hidden;
            background-color: var(--card-bg);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .company-card__logo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .company-card__info {
            flex: 1;
        }

        .company-card__name {
            font-size: 1.125rem;
            font-weight: 700;
            margin-bottom: var(--space-xs);
            color: var(--text);
        }

        .company-card__sector {
            color: var(--text-muted);
            font-size: 0.875rem;
        }

        .company-card__stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: var(--space-md);
            margin-bottom: var(--space-md);
            padding: var(--space-md);
            background-color: var(--bg);
            border-radius: var(--radius-md);
        }

        .company-stat {
            text-align: center;
        }

        .company-stat__value {
            display: block;
            font-weight: 700;
            font-size: 1.125rem;
            color: var(--primary);
        }

        .company-stat__label {
            font-size: 0.75rem;
            color: var(--text-muted);
        }

        .company-card__description {
            color: var(--text-muted);
            font-size: 0.875rem;
            line-height: 1.5;
            margin-bottom: var(--space-lg);
            flex: 1;
        }

        .company-card__actions {
            display: flex;
            gap: var(--space-sm);
        }

        .btn--sm {
            padding: var(--space-sm) var(--space-md);
            font-size: 0.875rem;
        }

        .company-details__header {
            display: flex;
            align-items: center;
            gap: var(--space-lg);
            margin-bottom: var(--space-lg);
        }

        .company-details__logo {
            width: 80px;
            height: 80px;
            border-radius: var(--radius-md);
            object-fit: cover;
        }

        .company-details__info h3 {
            font-size: 1.5rem;
            margin-bottom: var(--space-sm);
        }

        .company-details__sector {
            color: var(--text-muted);
            font-weight: 600;
        }

        .company-details__website {
            color: var(--primary);
            text-decoration: none;
        }

        .company-details__description {
            margin-bottom: var(--space-lg);
        }

        .company-details__description h4 {
            margin-bottom: var(--space-sm);
        }

        .company-details__stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: var(--space-md);
            margin-bottom: var(--space-lg);
        }

        .company-details__placements h4 {
            margin-bottom: var(--space-md);
        }

        .placements-list {
            max-height: 300px;
            overflow-y: auto;
        }

        .placement-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: var(--space-md);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            margin-bottom: var(--space-sm);
        }

        .placement-item__student {
            display: flex;
            flex-direction: column;
        }

        .placement-item__student strong {
            margin-bottom: var(--space-xs);
        }

        .placement-item__student span {
            font-size: 0.875rem;
            color: var(--text-muted);
        }

        .placement-item__details {
            display: flex;
            gap: var(--space-md);
            align-items: center;
            font-size: 0.875rem;
        }

        .status-badge {
            padding: var(--space-xs) var(--space-sm);
            border-radius: var(--radius-sm);
            font-size: 0.75rem;
            font-weight: 600;
        }

        .status-placed {
            background-color: var(--success);
            color: white;
        }

        .status-pending {
            background-color: var(--warning);
            color: white;
        }

        .empty-state {
            grid-column: 1 / -1;
            text-align: center;
            padding: var(--space-2xl);
            color: var(--text-muted);
        }

        @media (max-width: 768px) {
            .companies-grid {
                grid-template-columns: 1fr;
            }
            
            .company-details__header {
                flex-direction: column;
                text-align: center;
            }
            
            .company-details__stats {
                grid-template-columns: 1fr;
            }
            
            .placement-item {
                flex-direction: column;
                align-items: flex-start;
                gap: var(--space-sm);
            }
            
            .placement-item__details {
                width: 100%;
                justify-content: space-between;
            }
        }
    </style>
</body>
</html>