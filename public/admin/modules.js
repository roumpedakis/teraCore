/**
 * Module Management JavaScript
 */

const API_BASE = '/api';
let token = localStorage.getItem('authToken');
let allModules = [];
let selectedUserId = null;

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    checkAuth();
    loadModules();
    loadUsers();
    setupEventListeners();
});

function setupEventListeners() {
    document.getElementById('logoutBtn').addEventListener('click', logout);
    document.getElementById('userSelect').addEventListener('change', onUserSelect);
    document.getElementById('saveModulesBtn').addEventListener('click', saveUserModules);
}

function checkAuth() {
    if (!token) {
        window.location.href = '/admin/login';
        return;
    }
}

function logout() {
    localStorage.removeItem('authToken');
    window.location.href = '/admin/login';
}

// Load all available modules
async function loadModules() {
    try {
        const response = await fetch(`${API_BASE}/modules`, {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            }
        });

        if (!response.ok) throw new Error('Failed to load modules');

        const result = await response.json();
        allModules = result.data;
        displayModules(allModules);
        updateModuleStats(allModules);
    } catch (error) {
        console.error('Error loading modules:', error);
        showError('Failed to load modules');
    }
}

function displayModules(modules) {
    const grid = document.getElementById('modulesGrid');
    grid.innerHTML = modules.map(module => `
        <div class="module-card ${module.isCore ? 'core' : 'paid'}">
            <div class="module-header">
                <h3>${module.name}</h3>
                <span class="module-version">v${module.version}</span>
            </div>
            <p class="module-description">${module.description}</p>
            <div class="module-info">
                <span class="module-entities">📊 ${module.entities} entities</span>
                ${module.dependencies.length > 0 ? `
                    <span class="module-deps">🔗 Depends on: ${module.dependencies.join(', ')}</span>
                ` : ''}
            </div>
            <div class="module-footer">
                <span class="module-type">${module.isCore ? '🆓 Core' : '💰 Paid'}</span>
                <span class="module-price">
                    ${module.isCore ? 'FREE' : `€${module.price}/${module.billingPeriod}`}
                </span>
            </div>
        </div>
    `).join('');
}

function updateModuleStats(modules) {
    const total = modules.length;
    const core = modules.filter(m => m.isCore).length;
    const paid = modules.filter(m => !m.isCore && m.price > 0).length;

    document.getElementById('totalModules').textContent = total;
    document.getElementById('coreModules').textContent = core;
    document.getElementById('paidModules').textContent = paid;
}

// Load users for dropdown
async function loadUsers() {
    try {
        const response = await fetch(`${API_BASE}/users`, {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            }
        });

        if (!response.ok) throw new Error('Failed to load users');

        const result = await response.json();
        const users = result.data || [];

        const select = document.getElementById('userSelect');
        select.innerHTML = '<option value="">-- Select User --</option>' +
            users.map(user => `
                <option value="${user.id}">${user.username} (${user.email})</option>
            `).join('');
    } catch (error) {
        console.error('Error loading users:', error);
    }
}

// When user is selected
async function onUserSelect(event) {
    selectedUserId = event.target.value;
    
    if (!selectedUserId) {
        document.getElementById('userModulesContainer').style.display = 'none';
        return;
    }

    loadUserModules(selectedUserId);
}

// Load user's assigned modules
async function loadUserModules(userId) {
    try {
        const response = await fetch(`${API_BASE}/users/${userId}/modules`, {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            }
        });

        if (!response.ok) throw new Error('Failed to load user modules');

        const result = await response.json();
        const userModules = result.data.modules || [];
        const billing = result.data.billing;

        const username = document.getElementById('userSelect').selectedOptions[0].text;
        document.getElementById('selectedUsername').textContent = username;

        displayUserModules(userModules);
        updateBillingInfo(billing);

        document.getElementById('userModulesContainer').style.display = 'block';
    } catch (error) {
        console.error('Error loading user modules:', error);
        showError('Failed to load user modules');
    }
}

function displayUserModules(userModules) {
    const container = document.getElementById('userModulesList');
    
    // Create map of user's modules for easier lookup
    const userModuleMap = {};
    userModules.forEach(m => {
        userModuleMap[m.name] = m.permission;
    });

    // Display all available modules with checkboxes
    container.innerHTML = allModules.map(module => {
        const hasAccess = userModuleMap.hasOwnProperty(module.name);
        const permission = userModuleMap[module.name] || 0;

        return `
            <div class="user-module-item">
                <div class="module-checkbox">
                    <input type="checkbox" 
                           id="module_${module.name}" 
                           value="${module.name}"
                           ${hasAccess ? 'checked' : ''}
                           ${module.isCore ? 'checked disabled' : ''}>
                    <label for="module_${module.name}">
                        <strong>${module.name}</strong>
                        ${module.isCore ? '<span class="badge-core">Core</span>' : ''}
                        ${!module.isCore && module.price > 0 ? `<span class="badge-price">€${module.price}/mo</span>` : ''}
                    </label>
                </div>
                <div class="permission-select">
                    <label>Permission:</label>
                    <select class="permission-dropdown" data-module="${module.name}" ${!hasAccess && !modulecore ? 'disabled' : ''}>
                        <option value="0" ${permission === 0 ? 'selected' : ''}>None</option>
                        <option value="1" ${permission === 1 ? 'selected' : ''}>Read Only</option>
                        <option value="7" ${permission === 7 ? 'selected' : ''}>Read/Write</option>
                        <option value="15" ${permission === 15 ? 'selected' : ''}>Full Access</option>
                    </select>
                </div>
            </div>
        `;
    }).join('');

    // Add event listeners to checkboxes
    container.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
        checkbox.addEventListener('change', (e) => {
            const moduleName = e.target.value;
            const permissionSelect = container.querySelector(`select[data-module="${moduleName}"]`);
            if (permissionSelect) {
                permissionSelect.disabled = !e.target.checked;
                if (e.target.checked && permissionSelect.value === '0') {
                    permissionSelect.value = '1'; // Default to Read Only
                }
            }
        });
    });
}

function updateBillingInfo(billing) {
    document.getElementById('monthlyTotal').textContent = `€${billing.total.toFixed(2)}`;
    document.getElementById('activeModulesCount').textContent = billing.count;
    document.getElementById('paidModulesCount').textContent = billing.paidModules;
}

// Save user modules
async function saveUserModules() {
    if (!selectedUserId) return;

    const container = document.getElementById('userModulesList');
    const modules = {};

    // Collect enabled modules and their permissions
    container.querySelectorAll('input[type="checkbox"]:checked').forEach(checkbox => {
        const moduleName = checkbox.value;
        const permissionSelect = container.querySelector(`select[data-module="${moduleName}"]`);
        const permission = parseInt(permissionSelect.value);
        
        if (permission > 0) {
            modules[moduleName] = permission;
        }
    });

    try {
        const response = await fetch(`${API_BASE}/users/${selectedUserId}/modules`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ modules })
        });

        if (!response.ok) throw new Error('Failed to save modules');

        const result = await response.json();
        showSuccess('Modules updated successfully');
        
        // Reload user modules to reflect changes
        loadUserModules(selectedUserId);
    } catch (error) {
        console.error('Error saving modules:', error);
        showError('Failed to save modules');
    }
}

function showSuccess(message) {
    // Simple alert for now - you can enhance this with a toast notification
    alert('✓ ' + message);
}

function showError(message) {
    alert('✗ ' + message);
}
