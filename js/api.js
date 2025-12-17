// Mock API for Placement Record Management System
class PlacementAPI {
    constructor() {
        this.storageKey = 'placementRecords';
        this.companiesKey = 'companyRecords';
        this.activityKey = 'activityLog';
        this.initData();
    }

    // Initialize sample data if none exists
    initData() {
        if (!localStorage.getItem(this.storageKey)) {
            const sampleData = this.generateSampleData();
            localStorage.setItem(this.storageKey, JSON.stringify(sampleData));
        }

        if (!localStorage.getItem(this.companiesKey)) {
            const companies = this.generateCompanies();
            localStorage.setItem(this.companiesKey, JSON.stringify(companies));
        }

        if (!localStorage.getItem(this.activityKey)) {
            localStorage.setItem(this.activityKey, JSON.stringify([]));
        }
    }

    // Generate sample placement records
    generateSampleData() {
        const departments = ['BCA', 'B.Tech CSE', 'B.Tech ECE', 'MBA', 'BBA'];
        const companies = ['TCS', 'Infosys', 'Wipro', 'Google', 'Microsoft', 'Amazon', 'Accenture', 'Cognizant'];
        const statuses = ['Placed', 'Pending'];
        
        const records = [];
        
        for (let i = 1; i <= 25; i++) {
            const year = 2021 + Math.floor(Math.random() * 5);
            const dept = departments[Math.floor(Math.random() * departments.length)];
            const company = companies[Math.floor(Math.random() * companies.length)];
            const packageAmt = 300000 + Math.floor(Math.random() * 2000000);
            
            records.push({
                id: `placement-${i}`,
                name: `Student ${i}`,
                roll: `${dept.substring(0, 3)}-${year}-${String(i).padStart(3, '0')}`,
                department: dept,
                year: year,
                company: company,
                package: packageAmt,
                email: `student${i}@example.com`,
                phone: `+91-98765${String(i).padStart(5, '0')}`,
                photo: `assets/images/sample-photos/student${(i % 5) + 1}.jpg`,
                offer_letter: `assets/offers/student${i}_${company.toLowerCase()}.pdf`,
                status: statuses[Math.floor(Math.random() * statuses.length)],
                createdAt: new Date(Date.now() - Math.floor(Math.random() * 365 * 24 * 60 * 60 * 1000)).toISOString(),
                updatedAt: new Date().toISOString()
            });
        }
        
        return records;
    }

    // Generate sample companies
    generateCompanies() {
        return [
            {
                id: 'company-1',
                name: 'TCS',
                logo: 'assets\images\company-logos\tcs.png',
                sector: 'IT Services',
                description: 'Leading IT services, consulting and business solutions organization',
                website: 'https://www.tcs.com',
                hires: 8,
                highestPackage: 4500000,
                averagePackage: 3800000
            },
            {
                id: 'company-2',
                name: 'Infosys',
                logo: 'assets/images/company-logos/infosys.png',
                sector: 'IT Services',
                description: 'Next-generation digital services and consulting',
                website: 'https://www.infosys.com',
                hires: 6,
                highestPackage: 4200000,
                averagePackage: 3500000
            },
            {
                id: 'company-3',
                name: 'Wipro',
                logo: 'assets/images/company-logos/wipro.png',
                sector: 'IT Services',
                description: 'Leading global information technology, consulting and business process services company',
                website: 'https://www.wipro.com',
                hires: 5,
                highestPackage: 4000000,
                averagePackage: 3200000
            },
            {
                id: 'company-4',
                name: 'Google',
                logo: 'assets/images/company-logos/google.png',
                sector: 'Technology',
                description: 'Multinational technology company focusing on search engine technology',
                website: 'https://www.google.com',
                hires: 2,
                highestPackage: 5500000,
                averagePackage: 5000000
            },
            {
                id: 'company-5',
                name: 'Microsoft',
                logo: 'assets/images/company-logos/microsoft.png',
                sector: 'Technology',
                description: 'Multinational technology corporation',
                website: 'https://www.microsoft.com',
                hires: 3,
                highestPackage: 5200000,
                averagePackage: 4800000
            }
        ];
    }

    // Get placements with pagination, filtering, and sorting
    async getPlacements({ page = 1, perPage = 10, filters = {}, sort = {} } = {}) {
        return new Promise((resolve) => {
            setTimeout(() => {
                let data = JSON.parse(localStorage.getItem(this.storageKey) || '[]');
                
                // Apply filters
                if (filters.search) {
                    const searchTerm = filters.search.toLowerCase();
                    data = data.filter(record => 
                        record.name.toLowerCase().includes(searchTerm) ||
                        record.roll.toLowerCase().includes(searchTerm) ||
                        record.company.toLowerCase().includes(searchTerm) ||
                        record.department.toLowerCase().includes(searchTerm)
                    );
                }
                
                if (filters.department && filters.department !== 'all') {
                    data = data.filter(record => record.department === filters.department);
                }
                
                if (filters.company && filters.company !== 'all') {
                    data = data.filter(record => record.company === filters.company);
                }
                
                if (filters.year && filters.year !== 'all') {
                    data = data.filter(record => record.year.toString() === filters.year);
                }
                
                if (filters.status && filters.status !== 'all') {
                    data = data.filter(record => record.status === filters.status);
                }
                
                if (filters.minPackage) {
                    data = data.filter(record => record.package >= parseInt(filters.minPackage));
                }
                
                if (filters.maxPackage) {
                    data = data.filter(record => record.package <= parseInt(filters.maxPackage));
                }
                
                // Apply sorting
                if (sort.field) {
                    data.sort((a, b) => {
                        if (a[sort.field] < b[sort.field]) return sort.direction === 'asc' ? -1 : 1;
                        if (a[sort.field] > b[sort.field]) return sort.direction === 'asc' ? 1 : -1;
                        return 0;
                    });
                }
                
                const total = data.length;
                const startIndex = (page - 1) * perPage;
                const endIndex = startIndex + perPage;
                const paginatedData = data.slice(startIndex, endIndex);
                
                resolve({
                    data: paginatedData,
                    total,
                    page,
                    perPage,
                    totalPages: Math.ceil(total / perPage)
                });
            }, 300); // Simulate network delay
        });
    }

    // Get placement by ID
    async getPlacementById(id) {
        return new Promise((resolve, reject) => {
            setTimeout(() => {
                const data = JSON.parse(localStorage.getItem(this.storageKey) || '[]');
                const record = data.find(item => item.id === id);
                
                if (record) {
                    resolve(record);
                } else {
                    reject(new Error('Placement record not found'));
                }
            }, 200);
        });
    }

    // Create new placement record
    async createPlacement(placementData) {
        return new Promise((resolve) => {
            setTimeout(() => {
                const data = JSON.parse(localStorage.getItem(this.storageKey) || '[]');
                const newRecord = {
                    ...placementData,
                    id: `placement-${Date.now()}`,
                    createdAt: new Date().toISOString(),
                    updatedAt: new Date().toISOString()
                };
                
                data.push(newRecord);
                localStorage.setItem(this.storageKey, JSON.stringify(data));
                
                // Log activity
                this.logActivity('CREATE', `Created placement record for ${placementData.name}`);
                
                resolve(newRecord);
            }, 300);
        });
    }

    // Update placement record
    async updatePlacement(id, placementData) {
        return new Promise((resolve, reject) => {
            setTimeout(() => {
                const data = JSON.parse(localStorage.getItem(this.storageKey) || '[]');
                const index = data.findIndex(item => item.id === id);
                
                if (index !== -1) {
                    data[index] = {
                        ...data[index],
                        ...placementData,
                        updatedAt: new Date().toISOString()
                    };
                    
                    localStorage.setItem(this.storageKey, JSON.stringify(data));
                    
                    // Log activity
                    this.logActivity('UPDATE', `Updated placement record for ${data[index].name}`);
                    
                    resolve(data[index]);
                } else {
                    reject(new Error('Placement record not found'));
                }
            }, 300);
        });
    }

    // Delete placement record
    async deletePlacement(id) {
        return new Promise((resolve, reject) => {
            setTimeout(() => {
                const data = JSON.parse(localStorage.getItem(this.storageKey) || '[]');
                const recordIndex = data.findIndex(item => item.id === id);
                
                if (recordIndex !== -1) {
                    const deletedRecord = data.splice(recordIndex, 1)[0];
                    localStorage.setItem(this.storageKey, JSON.stringify(data));
                    
                    // Log activity
                    this.logActivity('DELETE', `Deleted placement record for ${deletedRecord.name}`);
                    
                    resolve();
                } else {
                    reject(new Error('Placement record not found'));
                }
            }, 300);
        });
    }

    // Get companies
    async getCompanies() {
        return new Promise((resolve) => {
            setTimeout(() => {
                const companies = JSON.parse(localStorage.getItem(this.companiesKey) || '[]');
                resolve(companies);
            }, 200);
        });
    }

    // Get company by ID
    async getCompanyById(id) {
        return new Promise((resolve, reject) => {
            setTimeout(() => {
                const companies = JSON.parse(localStorage.getItem(this.companiesKey) || '[]');
                const company = companies.find(item => item.id === id);
                
                if (company) {
                    resolve(company);
                } else {
                    reject(new Error('Company not found'));
                }
            }, 200);
        });
    }

    // Export placements in specified format
    async exportPlacements(format = 'json') {
        return new Promise((resolve) => {
            setTimeout(() => {
                const data = JSON.parse(localStorage.getItem(this.storageKey) || '[]');
                
                if (format === 'csv') {
                    const csv = this.convertToCSV(data);
                    resolve(csv);
                } else {
                    resolve(JSON.stringify(data, null, 2));
                }
            }, 500);
        });
    }

    // Import placements from file
    async importPlacements(file, format = 'json') {
        return new Promise((resolve, reject) => {
            setTimeout(() => {
                try {
                    let importedData;
                    
                    if (format === 'csv') {
                        importedData = this.parseCSV(file);
                    } else {
                        importedData = JSON.parse(file);
                    }
                    
                    // Validate imported data
                    if (!Array.isArray(importedData)) {
                        throw new Error('Invalid data format: expected array');
                    }
                    
                    const currentData = JSON.parse(localStorage.getItem(this.storageKey) || '[]');
                    const mergedData = [...currentData, ...importedData.map(item => ({
                        ...item,
                        id: `placement-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`,
                        createdAt: new Date().toISOString(),
                        updatedAt: new Date().toISOString()
                    }))];
                    
                    localStorage.setItem(this.storageKey, JSON.stringify(mergedData));
                    
                    // Log activity
                    this.logActivity('IMPORT', `Imported ${importedData.length} placement records`);
                    
                    resolve(mergedData.length);
                } catch (error) {
                    reject(error);
                }
            }, 500);
        });
    }

    // Get activity log
    async getActivityLog() {
        return new Promise((resolve) => {
            setTimeout(() => {
                const log = JSON.parse(localStorage.getItem(this.activityKey) || '[]');
                resolve(log);
            }, 200);
        });
    }

    // Log activity
    logActivity(action, description) {
        const log = JSON.parse(localStorage.getItem(this.activityKey) || '[]');
        const activity = {
            id: `activity-${Date.now()}`,
            action,
            description,
            timestamp: new Date().toISOString(),
            user: sessionStorage.getItem('adminUser') || 'Unknown'
        };
        
        log.unshift(activity);
        localStorage.setItem(this.activityKey, JSON.stringify(log));
    }

    // Reset data to sample
    async resetData() {
        return new Promise((resolve) => {
            setTimeout(() => {
                const sampleData = this.generateSampleData();
                localStorage.setItem(this.storageKey, JSON.stringify(sampleData));
                
                const companies = this.generateCompanies();
                localStorage.setItem(this.companiesKey, JSON.stringify(companies));
                
                localStorage.setItem(this.activityKey, JSON.stringify([]));
                
                resolve();
            }, 500);
        });
    }

    // Utility function to convert data to CSV
    convertToCSV(data) {
        if (data.length === 0) return '';
        
        const headers = Object.keys(data[0]);
        const csvRows = [headers.join(',')];
        
        for (const row of data) {
            const values = headers.map(header => {
                const escaped = ('' + row[header]).replace(/"/g, '""');
                return `"${escaped}"`;
            });
            csvRows.push(values.join(','));
        }
        
        return csvRows.join('\n');
    }

    // Utility function to parse CSV
    parseCSV(csvText) {
        const lines = csvText.split('\n');
        const result = [];
        const headers = lines[0].replace(/"/g, '').split(',');
        
        for (let i = 1; i < lines.length; i++) {
            if (!lines[i]) continue;
            
            const obj = {};
            const currentline = lines[i].split(/,(?=(?:(?:[^"]*"){2})*[^"]*$)/);
            
            for (let j = 0; j < headers.length; j++) {
                let value = currentline[j] || '';
                value = value.replace(/^"|"$/g, ''); // Remove surrounding quotes
                
                // Try to parse numbers
                if (!isNaN(value) && value.trim() !== '') {
                    obj[headers[j]] = Number(value);
                } else {
                    obj[headers[j]] = value;
                }
            }
            
            result.push(obj);
        }
        
        return result;
    }

    // Get dashboard statistics
    async getDashboardStats() {
        return new Promise((resolve) => {
            setTimeout(() => {
                const placements = JSON.parse(localStorage.getItem(this.storageKey) || '[]');
                const companies = JSON.parse(localStorage.getItem(this.companiesKey) || '[]');
                
                const totalStudents = placements.length;
                const placedStudents = placements.filter(p => p.status === 'Placed').length;
                const placementRate = totalStudents > 0 ? Math.round((placedStudents / totalStudents) * 100) : 0;
                const highestPackage = placements.length > 0 ? Math.max(...placements.map(p => p.package)) : 0;
                const totalCompanies = companies.length;
                
                // Department-wise stats
                const departmentStats = {};
                placements.forEach(placement => {
                    if (!departmentStats[placement.department]) {
                        departmentStats[placement.department] = {
                            count: 0,
                            totalPackage: 0
                        };
                    }
                    departmentStats[placement.department].count++;
                    departmentStats[placement.department].totalPackage += placement.package;
                });
                
                // Year-wise stats
                const yearStats = {};
                placements.forEach(placement => {
                    if (!yearStats[placement.year]) {
                        yearStats[placement.year] = 0;
                    }
                    yearStats[placement.year]++;
                });
                
                resolve({
                    totalStudents,
                    placedStudents,
                    placementRate,
                    highestPackage,
                    totalCompanies,
                    departmentStats,
                    yearStats
                });
            }, 200);
        });
    }
}

// Create and export a singleton instance
const placementAPI = new PlacementAPI();
export default placementAPI;