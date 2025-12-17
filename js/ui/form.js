import { showToast } from './toast.js';

class PlacementForm {
    constructor(options = {}) {
        this.options = {
            mode: 'create', // 'create' or 'edit'
            placementId: null,
            onSubmit: null,
            onCancel: null,
            ...options
        };
        
        this.form = null;
        this.init();
    }

    init() {
        this.createForm();
        this.setupEventListeners();
        
        if (this.options.mode === 'edit' && this.options.placementId) {
            this.loadPlacementData();
        }
    }

    createForm() {
        this.form = document.createElement('form');
        this.form.className = 'placement-form form';
        this.form.noValidate = true;
        
        this.form.innerHTML = `
            <div class="form-row">
                <div class="form-group">
                    <label for="studentName" class="form-label">Student Name *</label>
                    <input type="text" id="studentName" class="form-control" required>
                    <div class="form-error" id="studentNameError"></div>
                </div>
                
                <div class="form-group">
                    <label for="rollNo" class="form-label">Roll Number *</label>
                    <input type="text" id="rollNo" class="form-control" required>
                    <div class="form-error" id="rollNoError"></div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="department" class="form-label">Department *</label>
                    <select id="department" class="form-control" required>
                        <option value="">Select Department</option>
                        <option value="BCA">BCA</option>
                        <option value="B.Tech CSE">B.Tech Computer Science</option>
                        <option value="B.Tech ECE">B.Tech Electronics</option>
                        <option value="MBA">MBA</option>
                        <option value="BBA">BBA</option>
                    </select>
                    <div class="form-error" id="departmentError"></div>
                </div>
                
                <div class="form-group">
                    <label for="year" class="form-label">Year *</label>
                    <select id="year" class="form-control" required>
                        <option value="">Select Year</option>
                        ${this.generateYearOptions()}
                    </select>
                    <div class="form-error" id="yearError"></div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="company" class="form-label">Company *</label>
                    <input type="text" id="company" class="form-control" required list="companyList">
                    <datalist id="companyList">
                        <option value="TCS">
                        <option value="Infosys">
                        <option value="Wipro">
                        <option value="Google">
                        <option value="Microsoft">
                        <option value="Amazon">
                        <option value="Accenture">
                        <option value="Cognizant">
                    </datalist>
                    <div class="form-error" id="companyError"></div>
                </div>
                
                <div class="form-group">
                    <label for="package" class="form-label">Package (LPA) *</label>
                    <input type="number" id="package" class="form-control" step="0.1" min="0" required>
                    <div class="form-error" id="packageError"></div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="email" class="form-label">Email *</label>
                    <input type="email" id="email" class="form-control" required>
                    <div class="form-error" id="emailError"></div>
                </div>
                
                <div class="form-group">
                    <label for="phone" class="form-label">Phone *</label>
                    <input type="tel" id="phone" class="form-control" required>
                    <div class="form-error" id="phoneError"></div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="status" class="form-label">Status *</label>
                    <select id="status" class="form-control" required>
                        <option value="">Select Status</option>
                        <option value="Placed">Placed</option>
                        <option value="Pending">Pending</option>
                    </select>
                    <div class="form-error" id="statusError"></div>
                </div>
                
                <div class="form-group">
                    <label for="profilePhoto" class="form-label">Profile Photo</label>
                    <input type="file" id="profilePhoto" class="form-control" accept="image/*">
                    <div class="form-help">Optional - JPG, PNG up to 2MB</div>
                </div>
            </div>

            <div class="form-group">
                <label for="offerLetter" class="form-label">Offer Letter</label>
                <input type="file" id="offerLetter" class="form-control" accept=".pdf,.doc,.docx">
                <div class="form-help">Optional - PDF, DOC up to 5MB</div>
            </div>

            <div class="form-actions">
                <button type="button" class="btn btn--secondary" id="cancelBtn">Cancel</button>
                <button type="submit" class="btn btn--primary">
                    ${this.options.mode === 'create' ? 'Create Placement' : 'Update Placement'}
                </button>
            </div>
        `;
    }

    generateYearOptions() {
        const currentYear = new Date().getFullYear();
        let options = '';
        
        for (let year = currentYear; year >= currentYear - 5; year--) {
            options += `<option value="${year}">${year}</option>`;
        }
        
        return options;
    }

    setupEventListeners() {
        this.form.addEventListener('submit', this.handleSubmit.bind(this));
        document.getElementById('cancelBtn').addEventListener('click', this.handleCancel.bind(this));
        
        // Real-time validation
        this.form.querySelectorAll('input, select').forEach(field => {
            field.addEventListener('blur', () => {
                this.validateField(field);
            });
        });
    }

    async loadPlacementData() {
        try {
            // This would typically load from API
            // For now, we'll simulate loading
            showToast('Loading placement data...', 'info');
            
            // Simulate API call
            setTimeout(() => {
                // Mock data - in real implementation, this would come from API
                const mockData = {
                    name: 'Sample Student',
                    roll: 'BCA-2024-001',
                    department: 'BCA',
                    year: 2024,
                    company: 'TCS',
                    package: 350000,
                    email: 'sample@example.com',
                    phone: '+91-9876543210',
                    status: 'Placed'
                };
                
                this.populateForm(mockData);
                showToast('Placement data loaded', 'success');
            }, 500);
            
        } catch (error) {
            console.error('Error loading placement data:', error);
            showToast('Error loading placement data', 'error');
        }
    }

    populateForm(data) {
        document.getElementById('studentName').value = data.name || '';
        document.getElementById('rollNo').value = data.roll || '';
        document.getElementById('department').value = data.department || '';
        document.getElementById('year').value = data.year || '';
        document.getElementById('company').value = data.company || '';
        document.getElementById('package').value = data.package ? (data.package / 100000) : '';
        document.getElementById('email').value = data.email || '';
        document.getElementById('phone').value = data.phone || '';
        document.getElementById('status').value = data.status || '';
    }

    async handleSubmit(e) {
        e.preventDefault();
        
        if (!this.validateForm()) {
            showToast('Please fix the errors in the form', 'error');
            return;
        }

        const formData = this.getFormData();
        
        try {
            if (this.options.onSubmit) {
                await this.options.onSubmit(formData);
            }
            showToast(
                this.options.mode === 'create' ? 'Placement created successfully' : 'Placement updated successfully',
                'success'
            );
        } catch (error) {
            console.error('Error submitting form:', error);
            showToast('Error saving placement', 'error');
        }
    }

    handleCancel() {
        if (this.options.onCancel) {
            this.options.onCancel();
        }
    }

    validateForm() {
        let isValid = true;
        const fields = this.form.querySelectorAll('input[required], select[required]');
        
        fields.forEach(field => {
            if (!this.validateField(field)) {
                isValid = false;
            }
        });
        
        return isValid;
    }

    validateField(field) {
        const errorElement = document.getElementById(`${field.id}Error`);
        let isValid = true;
        let errorMessage = '';

        // Clear previous error
        field.classList.remove('form-control--error');
        errorElement.textContent = '';

        // Required validation
        if (field.hasAttribute('required') && !field.value.trim()) {
            isValid = false;
            errorMessage = 'This field is required';
        }

        // Email validation
        if (field.type === 'email' && field.value) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(field.value)) {
                isValid = false;
                errorMessage = 'Please enter a valid email address';
            }
        }

        // Phone validation
        if (field.id === 'phone' && field.value) {
            const phoneRegex = /^[\d\s\-\+\(\)]{10,}$/;
            if (!phoneRegex.test(field.value)) {
                isValid = false;
                errorMessage = 'Please enter a valid phone number';
            }
        }

        // Package validation
        if (field.id === 'package' && field.value) {
            const packageValue = parseFloat(field.value);
            if (packageValue <= 0) {
                isValid = false;
                errorMessage = 'Package must be greater than 0';
            }
        }

        if (!isValid) {
            field.classList.add('form-control--error');
            errorElement.textContent = errorMessage;
        }

        return isValid;
    }

    getFormData() {
        const formData = {
            name: document.getElementById('studentName').value.trim(),
            roll: document.getElementById('rollNo').value.trim(),
            department: document.getElementById('department').value,
            year: parseInt(document.getElementById('year').value),
            company: document.getElementById('company').value.trim(),
            package: parseFloat(document.getElementById('package').value) * 100000, // Convert to actual amount
            email: document.getElementById('email').value.trim(),
            phone: document.getElementById('phone').value.trim(),
            status: document.getElementById('status').value
        };

        // Handle file uploads (simulated)
        const profilePhoto = document.getElementById('profilePhoto').files[0];
        const offerLetter = document.getElementById('offerLetter').files[0];

        if (profilePhoto) {
            formData.photo = URL.createObjectURL(profilePhoto); // Simulate file upload
        }

        if (offerLetter) {
            formData.offer_letter = URL.createObjectURL(offerLetter); // Simulate file upload
        }

        return formData;
    }

    getFormElement() {
        return this.form;
    }

    destroy() {
        if (this.form && this.form.parentNode) {
            this.form.parentNode.removeChild(this.form);
        }
    }
}

// Utility function for form validation
export function validateEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

export function validatePhone(phone) {
    const phoneRegex = /^[\d\s\-\+\(\)]{10,}$/;
    return phoneRegex.test(phone);
}

// Add form-specific styles
const formStyle = document.createElement('style');
formStyle.textContent = `
    .placement-form {
        max-width: 800px;
        margin: 0 auto;
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: var(--space-lg);
        margin-bottom: var(--space-lg);
    }

    .form-group {
        display: flex;
        flex-direction: column;
        gap: var(--space-sm);
    }

    .form-label {
        font-weight: 600;
        color: var(--text);
        font-size: 0.875rem;
    }

    .form-control {
        padding: var(--space-md);
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        background-color: var(--bg);
        color: var(--text);
        transition: var(--transition);
        font-size: 1rem;
    }

    .form-control:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
    }

    .form-control--error {
        border-color: var(--error);
        box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
    }

    .form-error {
        color: var(--error);
        font-size: 0.875rem;
        margin-top: var(--space-xs);
    }

    .form-help {
        color: var(--text-muted);
        font-size: 0.75rem;
        margin-top: var(--space-xs);
    }

    .form-actions {
        display: flex;
        gap: var(--space-md);
        justify-content: flex-end;
        margin-top: var(--space-xl);
        padding-top: var(--space-lg);
        border-top: 1px solid var(--border);
    }

    @media (max-width: 768px) {
        .form-row {
            grid-template-columns: 1fr;
            gap: var(--space-md);
            margin-bottom: var(--space-md);
        }
        
        .form-actions {
            flex-direction: column-reverse;
        }
    }
`;
document.head.appendChild(formStyle);

export default PlacementForm;