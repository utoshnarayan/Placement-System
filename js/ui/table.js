import placementAPI from '../api.js';
import { showToast } from './toast.js';

class PlacementsTable {
    constructor() {
        this.currentPage = 1;
        this.perPage = 10;
        this.filters = {};
        this.sort = { field: 'name', direction: 'asc' };
        this.totalRecords = 0;
        this.totalPages = 0;
        
        this.init();
    }

    async init() {
        await this.loadFilterOptions();
        this.setupEventListeners();
        await this.loadPlacements();
    }

    async loadFilterOptions() {
        try {
            const placements = await placementAPI.getPlacements({ perPage: 1000 });
            const companies = await placementAPI.getCompanies();
            
            // Load departments
            const departments = [...new Set(placements.data.map(p => p.department))].sort();
            const departmentFilter = document.getElementById('departmentFilter');
            departments.forEach(dept => {
                const option = document.createElement('option');
                option.value = dept;
                option.textContent = dept;
                departmentFilter.appendChild(option);
            });

            // Load companies
            const companyFilter = document.getElementById('companyFilter');
            companies.forEach(company => {
                const option = document.createElement('option');
                option.value = company.name;
                option.textContent = company.name;
                companyFilter.appendChild(option);
            });

            // Load years
            const years = [...new Set(placements.data.map(p => p.year))].sort((a, b) => b - a);
            const yearFilter = document.getElementById('yearFilter');
            years.forEach(year => {
                const option = document.createElement('option');
                option.value = year;
                option.textContent = year;
                yearFilter.appendChild(option);
            });

        } catch (error) {
            console.error('Error loading filter options:', error);
        }
    }

    setupEventListeners() {
        // Search input with debounce
        const searchInput = document.getElementById('searchInput');
        searchInput.addEventListener('input', this.debounce(() => {
            this.filters.search = searchInput.value;
            this.currentPage = 1;
            this.loadPlacements();
        }, 300));

        // Filter changes
        ['departmentFilter', 'companyFilter', 'yearFilter', 'statusFilter'].forEach(id => {
            document.getElementById(id).addEventListener('change', (e) => {
                const value = e.target.value;
                const filterName = id.replace('Filter', '');
                this.filters[filterName] = value === 'all' ? '' : value;
                this.currentPage = 1;
                this.loadPlacements();
            });
        });

        // Package range filters
        ['packageMin', 'packageMax'].forEach(id => {
            document.getElementById(id).addEventListener('input', this.debounce(() => {
                const min = document.getElementById('packageMin').value;
                const max = document.getElementById('packageMax').value;
                
                if (min) this.filters.minPackage = min * 100000; // Convert LPA to actual amount
                else delete this.filters.minPackage;
                
                if (max) this.filters.maxPackage = max * 100000; // Convert LPA to actual amount
                else delete this.filters.maxPackage;
                
                this.currentPage = 1;
                this.loadPlacements();
            }, 500));
        });

        // Clear filters
        document.getElementById('clearFilters').addEventListener('click', () => {
            this.clearFilters();
        });

        // Sort control
        document.getElementById('sortSelect').addEventListener('change', (e) => {
            const [field, direction] = e.target.value.split(':');
            this.sort = { field, direction };
            this.loadPlacements();
        });

        // Pagination
        document.getElementById('prevPage').addEventListener('click', () => {
            if (this.currentPage > 1) {
                this.currentPage--;
                this.loadPlacements();
            }
        });

        document.getElementById('nextPage').addEventListener('click', () => {
            if (this.currentPage < this.totalPages) {
                this.currentPage++;
                this.loadPlacements();
            }
        });

        // Modal handlers
        document.getElementById('modalClose').addEventListener('click', this.closeModal.bind(this));
        document.getElementById('modalCloseBtn').addEventListener('click', this.closeModal.bind(this));
        
        // Close modal on backdrop click
        document.getElementById('placementModal').addEventListener('click', (e) => {
            if (e.target.id === 'placementModal') {
                this.closeModal();
            }
        });
    }

    async loadPlacements() {
        this.showLoading();
        
        try {
            const result = await placementAPI.getPlacements({
                page: this.currentPage,
                perPage: this.perPage,
                filters: this.filters,
                sort: this.sort
            });

            this.totalRecords = result.total;
            this.totalPages = result.totalPages;
            this.renderTable(result.data);
            this.updatePagination();
            this.updateResultsCount();

        } catch (error) {
            console.error('Error loading placements:', error);
            showToast('Error loading placement records', 'error');
            this.showError();
        }
    }

    renderTable(placements) {
        const tbody = document.getElementById('placementsTableBody');
        
        if (placements.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="empty-state">
                        <div style="text-align: center; padding: 2rem; color: var(--text-muted);">
                            <h3>No placements found</h3>
                            <p>Try adjusting your search or filters</p>
                        </div>
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = placements.map(placement => `
            <tr class="placement-row" data-placement-id="${placement.id}">
                <td>
                    <div class="student-info">
                        <div class="student-avatar">
                            <img src="${placement.photo}" alt="${placement.name}" onerror="this.src='assets/images/avatar-placeholder.png'">
                        </div>
                        <div class="student-details">
                            <strong>${this.escapeHtml(placement.name)}</strong>
                            <span>${placement.email}</span>
                        </div>
                    </div>
                </td>
                <td>${this.escapeHtml(placement.roll)}</td>
                <td>
                    <span class="department-badge">${this.escapeHtml(placement.department)}</span>
                </td>
                <td>${this.escapeHtml(placement.company)}</td>
                <td>
                    <strong>₹${(placement.package / 100000).toFixed(1)}L</strong>
                </td>
                <td>${placement.year}</td>
                <td>
                    <span class="status-badge status-${placement.status.toLowerCase()}">
                        ${placement.status}
                    </span>
                </td>
            </tr>
        `).join('');

        // Add click event to rows
        tbody.querySelectorAll('.placement-row').forEach(row => {
            row.addEventListener('click', () => {
                const placementId = row.dataset.placementId;
                this.showPlacementDetails(placementId);
            });
        });
    }

    async showPlacementDetails(placementId) {
        try {
            const placement = await placementAPI.getPlacementById(placementId);
            
            document.getElementById('modalTitle').textContent = `${placement.name} - Placement Details`;
            document.getElementById('modalBody').innerHTML = `
                <div class="placement-details">
                    <div class="placement-details__header">
                        <div class="student-avatar-large">
                            <img src="${placement.photo}" alt="${placement.name}" onerror="this.src='assets/images/avatar-placeholder.png'">
                        </div>
                        <div class="placement-details__student">
                            <h3>${this.escapeHtml(placement.name)}</h3>
                            <p>${placement.roll} • ${placement.department}</p>
                            <p class="placement-details__contact">
                                ${placement.email} • ${placement.phone}
                            </p>
                        </div>
                    </div>

                    <div class="placement-details__grid">
                        <div class="detail-card">
                            <h4>Company Information</h4>
                            <div class="detail-item">
                                <span class="detail-label">Company:</span>
                                <span class="detail-value">${this.escapeHtml(placement.company)}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Package:</span>
                                <span class="detail-value">₹${(placement.package / 100000).toFixed(1)} LPA</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Status:</span>
                                <span class="detail-value status-badge status-${placement.status.toLowerCase()}">
                                    ${placement.status}
                                </span>
                            </div>
                        </div>

                        <div class="detail-card">
                            <h4>Academic Information</h4>
                            <div class="detail-item">
                                <span class="detail-label">Year:</span>
                                <span class="detail-value">${placement.year}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Department:</span>
                                <span class="detail-value">${this.escapeHtml(placement.department)}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Roll No:</span>
                                <span class="detail-value">${this.escapeHtml(placement.roll)}</span>
                            </div>
                        </div>

                        <div class="detail-card">
                            <h4>Documents</h4>
                            <div class="document-links">
                                <a href="${placement.offer_letter}" class="document-link" target="_blank">
                                    📄 Offer Letter
                                </a>
                                <button class="btn btn--secondary btn--sm" onclick="alert('Profile photo preview would open here')">
                                    👤 View Profile Photo
                                </button>
                            </div>
                        </div>

                        <div class="detail-card">
                            <h4>Timeline</h4>
                            <div class="detail-item">
                                <span class="detail-label">Created:</span>
                                <span class="detail-value">${new Date(placement.createdAt).toLocaleDateString()}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Last Updated:</span>
                                <span class="detail-value">${new Date(placement.updatedAt).toLocaleDateString()}</span>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            document.getElementById('placementModal').style.display = 'flex';
        } catch (error) {
            console.error('Error loading placement details:', error);
            showToast('Error loading placement details', 'error');
        }
    }

    closeModal() {
        document.getElementById('placementModal').style.display = 'none';
    }

    updatePagination() {
        const prevBtn = document.getElementById('prevPage');
        const nextBtn = document.getElementById('nextPage');
        const paginationInfo = document.getElementById('paginationInfo');
        const paginationPages = document.getElementById('paginationPages');

        prevBtn.disabled = this.currentPage === 1;
        nextBtn.disabled = this.currentPage === this.totalPages;

        paginationInfo.textContent = `Page ${this.currentPage} of ${this.totalPages} • ${this.totalRecords} records`;

        // Generate page buttons
        let pagesHtml = '';
        const startPage = Math.max(1, this.currentPage - 2);
        const endPage = Math.min(this.totalPages, this.currentPage + 2);

        for (let i = startPage; i <= endPage; i++) {
            pagesHtml += `
                <button class="pagination__btn ${i === this.currentPage ? 'pagination__btn--active' : ''}" 
                        data-page="${i}">
                    ${i}
                </button>
            `;
        }

        paginationPages.innerHTML = pagesHtml;

        // Add event listeners to page buttons
        paginationPages.querySelectorAll('.pagination__btn[data-page]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                this.currentPage = parseInt(e.target.dataset.page);
                this.loadPlacements();
            });
        });
    }

    updateResultsCount() {
        const countElement = document.getElementById('resultsCount');
        const start = (this.currentPage - 1) * this.perPage + 1;
        const end = Math.min(this.currentPage * this.perPage, this.totalRecords);
        
        countElement.textContent = `Showing ${start}-${end} of ${this.totalRecords} placements`;
    }

    clearFilters() {
        // Reset filter inputs
        document.getElementById('searchInput').value = '';
        document.getElementById('departmentFilter').value = 'all';
        document.getElementById('companyFilter').value = 'all';
        document.getElementById('yearFilter').value = 'all';
        document.getElementById('statusFilter').value = 'all';
        document.getElementById('packageMin').value = '';
        document.getElementById('packageMax').value = '';
        document.getElementById('sortSelect').value = 'name:asc';

        // Reset filter state
        this.filters = {};
        this.sort = { field: 'name', direction: 'asc' };
        this.currentPage = 1;

        this.loadPlacements();
    }

    showLoading() {
        const tbody = document.getElementById('placementsTableBody');
        tbody.innerHTML = `
            ${Array.from({ length: 5 }, () => `
                <tr class="skeleton-row">
                    <td><div class="skeleton" style="height: 20px; width: 120px;"></div></td>
                    <td><div class="skeleton" style="height: 20px; width: 100px;"></div></td>
                    <td><div class="skeleton" style="height: 20px; width: 100px;"></div></td>
                    <td><div class="skeleton" style="height: 20px; width: 100px;"></div></td>
                    <td><div class="skeleton" style="height: 20px; width: 80px;"></div></td>
                    <td><div class="skeleton" style="height: 20px; width: 60px;"></div></td>
                    <td><div class="skeleton" style="height: 20px; width: 80px;"></div></td>
                </tr>
            `).join('')}
        `;
    }

    showError() {
        const tbody = document.getElementById('placementsTableBody');
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="error-state">
                    <div style="text-align: center; padding: 2rem; color: var(--error);">
                        <h3>Error loading data</h3>
                        <p>Please try again later</p>
                    </div>
                </td>
            </tr>
        `;
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

// Initialize table when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new PlacementsTable();
});

// Add CSS for table components
const style = document.createElement('style');
style.textContent = `
    .student-info {
        display: flex;
        align-items: center;
        gap: var(--space-sm);
    }

    .student-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        overflow: hidden;
        background-color: var(--card-bg);
    }

    .student-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .student-details {
        display: flex;
        flex-direction: column;
    }

    .student-details strong {
        font-weight: 600;
    }

    .student-details span {
        font-size: 0.875rem;
        color: var(--text-muted);
    }

    .department-badge {
        background-color: var(--primary);
        color: white;
        padding: var(--space-xs) var(--space-sm);
        border-radius: var(--radius-sm);
        font-size: 0.75rem;
        font-weight: 600;
    }

    .status-badge {
        padding: var(--space-xs) var(--space-sm);
        border-radius: var(--radius-sm);
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
    }

    .status-placed {
        background-color: var(--success);
        color: white;
    }

    .status-pending {
        background-color: var(--warning);
        color: white;
    }

    .placement-row {
        cursor: pointer;
        transition: var(--transition);
    }

    .placement-row:hover {
        background-color: rgba(37, 99, 235, 0.05) !important;
    }

    .skeleton-row td {
        padding: var(--space-md);
    }

    .placement-details__header {
        display: flex;
        align-items: center;
        gap: var(--space-lg);
        margin-bottom: var(--space-lg);
        padding-bottom: var(--space-lg);
        border-bottom: 1px solid var(--border);
    }

    .student-avatar-large {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        overflow: hidden;
        background-color: var(--card-bg);
    }

    .student-avatar-large img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .placement-details__student h3 {
        margin-bottom: var(--space-sm);
        font-size: 1.5rem;
    }

    .placement-details__contact {
        color: var(--text-muted);
        margin-top: var(--space-sm);
    }

    .placement-details__grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: var(--space-lg);
    }

    .detail-card {
        background-color: var(--card-bg);
        padding: var(--space-lg);
        border-radius: var(--radius-md);
        border: 1px solid var(--border);
    }

    .detail-card h4 {
        margin-bottom: var(--space-md);
        font-weight: 600;
        color: var(--text);
    }

    .detail-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: var(--space-sm) 0;
        border-bottom: 1px solid var(--border);
    }

    .detail-item:last-child {
        border-bottom: none;
    }

    .detail-label {
        font-weight: 600;
        color: var(--text-muted);
    }

    .detail-value {
        color: var(--text);
    }

    .document-links {
        display: flex;
        flex-direction: column;
        gap: var(--space-sm);
    }

    .document-link {
        display: flex;
        align-items: center;
        gap: var(--space-sm);
        padding: var(--space-sm) var(--space-md);
        background-color: var(--card-bg);
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        text-decoration: none;
        color: var(--text);
        transition: var(--transition);
    }

    .document-link:hover {
        background-color: var(--border);
    }

    .results-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: var(--space-lg);
    }

    .results-count {
        color: var(--text-muted);
        font-weight: 600;
    }

    .sort-controls {
        display: flex;
        align-items: center;
        gap: var(--space-sm);
    }

    @media (max-width: 768px) {
        .placement-details__header {
            flex-direction: column;
            text-align: center;
        }
        
        .placement-details__grid {
            grid-template-columns: 1fr;
        }
        
        .results-header {
            flex-direction: column;
            gap: var(--space-md);
            align-items: flex-start;
        }
    }
`;
document.head.appendChild(style);