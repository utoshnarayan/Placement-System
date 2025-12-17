class Pagination {
    constructor(options = {}) {
        this.options = {
            container: null,
            currentPage: 1,
            totalPages: 1,
            totalItems: 0,
            perPage: 10,
            onPageChange: null,
            showInfo: true,
            showPageNumbers: true,
            maxVisiblePages: 5,
            ...options
        };
        
        this.container = typeof this.options.container === 'string' 
            ? document.querySelector(this.options.container) 
            : this.options.container;
            
        this.init();
    }

    init() {
        if (!this.container) {
            console.error('Pagination container not found');
            return;
        }
        
        this.render();
        this.setupEventListeners();
    }

    render() {
        const { currentPage, totalPages, totalItems, perPage, showInfo, showPageNumbers } = this.options;
        
        const startItem = (currentPage - 1) * perPage + 1;
        const endItem = Math.min(currentPage * perPage, totalItems);
        
        this.container.innerHTML = `
            <div class="pagination">
                ${showInfo ? `
                    <div class="pagination__info">
                        Showing ${startItem}-${endItem} of ${totalItems} items
                    </div>
                ` : ''}
                
                <div class="pagination__controls">
                    <button class="pagination__btn pagination__prev" 
                            ${currentPage === 1 ? 'disabled' : ''}>
                        Previous
                    </button>
                    
                    ${showPageNumbers ? this.renderPageNumbers() : ''}
                    
                    <button class="pagination__btn pagination__next" 
                            ${currentPage === totalPages ? 'disabled' : ''}>
                        Next
                    </button>
                </div>
            </div>
        `;
    }

    renderPageNumbers() {
        const { currentPage, totalPages, maxVisiblePages } = this.options;
        
        if (totalPages <= 1) return '';
        
        let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
        let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);
        
        // Adjust if we're near the end
        if (endPage - startPage + 1 < maxVisiblePages) {
            startPage = Math.max(1, endPage - maxVisiblePages + 1);
        }
        
        let pagesHtml = '';
        
        // First page
        if (startPage > 1) {
            pagesHtml += this.renderPageButton(1);
            if (startPage > 2) {
                pagesHtml += '<span class="pagination__ellipsis">...</span>';
            }
        }
        
        // Page numbers
        for (let i = startPage; i <= endPage; i++) {
            pagesHtml += this.renderPageButton(i);
        }
        
        // Last page
        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                pagesHtml += '<span class="pagination__ellipsis">...</span>';
            }
            pagesHtml += this.renderPageButton(totalPages);
        }
        
        return pagesHtml;
    }

    renderPageButton(page) {
        const isActive = page === this.options.currentPage;
        return `
            <button class="pagination__btn pagination__page ${isActive ? 'pagination__btn--active' : ''}" 
                    data-page="${page}">
                ${page}
            </button>
        `;
    }

    setupEventListeners() {
        // Previous button
        this.container.querySelector('.pagination__prev').addEventListener('click', () => {
            if (this.options.currentPage > 1) {
                this.goToPage(this.options.currentPage - 1);
            }
        });

        // Next button
        this.container.querySelector('.pagination__next').addEventListener('click', () => {
            if (this.options.currentPage < this.options.totalPages) {
                this.goToPage(this.options.currentPage + 1);
            }
        });

        // Page number buttons
        this.container.querySelectorAll('.pagination__page').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const page = parseInt(e.target.dataset.page);
                this.goToPage(page);
            });
        });
    }

    goToPage(page) {
        if (page < 1 || page > this.options.totalPages || page === this.options.currentPage) {
            return;
        }

        this.options.currentPage = page;
        this.render();
        this.setupEventListeners();

        if (this.options.onPageChange) {
            this.options.onPageChange(page);
        }
    }

    update(options) {
        this.options = { ...this.options, ...options };
        this.render();
        this.setupEventListeners();
    }

    destroy() {
        this.container.innerHTML = '';
    }
}

// Add pagination styles
const paginationStyle = document.createElement('style');
paginationStyle.textContent = `
    .pagination {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: var(--space-lg) 0;
        flex-wrap: wrap;
        gap: var(--space-md);
    }

    .pagination__info {
        color: var(--text-muted);
        font-size: 0.875rem;
    }

    .pagination__controls {
        display: flex;
        gap: var(--space-sm);
        align-items: center;
    }

    .pagination__btn {
        padding: var(--space-sm) var(--space-md);
        border: 1px solid var(--border);
        background-color: var(--card-bg);
        color: var(--text);
        border-radius: var(--radius-md);
        cursor: pointer;
        transition: var(--transition);
        font-size: 0.875rem;
        min-width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .pagination__btn:hover:not(:disabled) {
        background-color: var(--border);
        border-color: var(--border-dark);
    }

    .pagination__btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .pagination__btn--active {
        background-color: var(--primary);
        color: white;
        border-color: var(--primary);
    }

    .pagination__ellipsis {
        padding: var(--space-sm) var(--space-xs);
        color: var(--text-muted);
    }

    @media (max-width: 768px) {
        .pagination {
            flex-direction: column;
            text-align: center;
        }
        
        .pagination__controls {
            flex-wrap: wrap;
            justify-content: center;
        }
    }

    @media (max-width: 480px) {
        .pagination__btn {
            padding: var(--space-xs) var(--space-sm);
            min-width: 36px;
            height: 36px;
            font-size: 0.75rem;
        }
    }
`;
document.head.appendChild(paginationStyle);

export default Pagination;