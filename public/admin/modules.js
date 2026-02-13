/**
 * Module Management JavaScript
 */

const API_BASE = '/api';
let allModules = [];
let selectedUserId = null;
let allUsers = [];
let userPurchases = {};
let selectedUserLabel = '-';

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    loadModules();
    loadUsers();
    setupEventListeners();
});

function setupEventListeners() {
    const logoutButton = document.getElementById('logout-button');
    if (logoutButton) {
        logoutButton.addEventListener('click', () => {
            window.location.href = '/admin/logout';
        });
    }

    const userSearch = document.getElementById('userSearch');
    if (userSearch) {
        userSearch.addEventListener('input', onUserSearch);
    }

    const saveButton = document.getElementById('saveModulesBtn');
    if (saveButton) {
        saveButton.addEventListener('click', saveUserModules);
    }
}

// Load all available modules
async function loadModules() {
    try {
        const response = await fetch(`${API_BASE}/modules`, {
            headers: {
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
    if (!grid) {
        return;
    }
    grid.innerHTML = modules.map(module => `
        <div class="module-card ${module.isCore ? 'core' : 'paid'}">
            <div class="module-header">
                <h3>${module.name}</h3>
                <span class="module-version">v${module.version}</span>
            </div>
            <p class="module-description">${module.description}</p>
            <div class="module-info">
                <span class="module-entities">📊 ${module.entities} entities</span>
                ${Array.isArray(module.dependencies) && module.dependencies.length > 0 ? `
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
                'Content-Type': 'application/json'
            }
        });

        if (!response.ok) throw new Error('Failed to load users');

        const result = await response.json();
        allUsers = result.data || [];
        renderUserOptions(allUsers);
    } catch (error) {
        console.error('Error loading users:', error);
    }
}

function renderUserOptions(users) {
    const list = document.getElementById('userList');
    if (!list) {
        return;
    }

    if (users.length === 0) {
        list.innerHTML = '<div class="user-list-empty">Δεν βρέθηκαν χρήστες</div>';
        return;
    }

    const items = users.map(user => {
        const label = `${user.username} (${user.email})`;
        const selectedClass = String(user.id) === String(selectedUserId) ? ' is-selected' : '';
        return `
            <button type="button" class="user-list-item${selectedClass}" data-user-id="${user.id}" data-label="${label}">
                <span class="user-name">${user.username}</span>
                <span class="user-email">${user.email}</span>
            </button>
        `;
    });

    list.innerHTML = items.join('');

    list.querySelectorAll('.user-list-item').forEach(item => {
        item.addEventListener('click', () => {
            const userId = item.getAttribute('data-user-id');
            const label = item.getAttribute('data-label') || '-';
            setSelectedUser(userId, label);
        });
    });
}

function onUserSearch(event) {
    const term = event.target.value.trim().toLowerCase();
    if (!term) {
        renderUserOptions(allUsers);
        return;
    }

    const filtered = allUsers.filter(user => {
        const username = (user.username || '').toLowerCase();
        const email = (user.email || '').toLowerCase();
        const first = (user.first_name || '').toLowerCase();
        const last = (user.last_name || '').toLowerCase();

        return (
            username.includes(term) ||
            email.includes(term) ||
            first.includes(term) ||
            last.includes(term)
        );
    });

    renderUserOptions(filtered);
}

function setSelectedUser(userId, label) {
    selectedUserId = userId;
    selectedUserLabel = label || '-';

    if (!selectedUserId) {
        const container = document.getElementById('userModulesContainer');
        if (container) {
            container.style.display = 'none';
        }
        return;
    }

    loadUserModules(selectedUserId, selectedUserLabel);
    renderUserOptions(allUsers);
}

// Load user's assigned modules
async function loadUserModules(userId, label) {
    try {
        const response = await fetch(`${API_BASE}/users/${userId}/modules`, {
            headers: {
                'Content-Type': 'application/json'
            }
        });

        if (!response.ok) throw new Error('Failed to load user modules');

        const result = await response.json();
        const userModules = result.data.modules || [];
        userPurchases = result.data.purchases || {};
        const billing = result.data.billing;

        const selectedName = label || selectedUserLabel || '-';
        const selectedNode = document.getElementById('selectedUsername');
        if (selectedNode) {
            selectedNode.textContent = selectedName;
        }

        displayUserModules(userModules, userPurchases);
        updateBillingInfo(billing);

        const container = document.getElementById('userModulesContainer');
        if (container) {
            container.style.display = 'block';
        }
    } catch (error) {
        console.error('Error loading user modules:', error);
        showError('Failed to load user modules');
    }
}

function displayUserModules(userModules, purchases) {
    const container = document.getElementById('userModulesList');
    if (!container) {
        return;
    }
    
    // Create map of user's modules for easier lookup
    const userModuleMap = {};
    userModules.forEach(m => {
        userModuleMap[m.name] = m.permission;
    });

    // Display all available modules with checkboxes
    container.innerHTML = allModules.map(module => {
        const isCore = module.isCore === true;
        const hasAccess = isCore || Object.prototype.hasOwnProperty.call(userModuleMap, module.name);
        const permission = userModuleMap[module.name] ?? (isCore ? 15 : 0);
        const purchase = purchases[module.name] || null;
        const purchaseStatus = purchase?.status || 'none';
        const purchased = purchaseStatus === 'active';

        return `
            <div class="user-module-item">
                <div class="module-checkbox">
                    <input type="checkbox" 
                           id="module_${module.name}" 
                           value="${module.name}"
                           ${hasAccess ? 'checked' : ''}
                           ${isCore ? 'checked disabled' : ''}>
                    <label for="module_${module.name}">
                        <strong>${module.name}</strong>
                        ${isCore ? '<span class="badge-core">Core</span>' : ''}
                        ${!isCore && module.price > 0 ? `<span class="badge-price">€${module.price}/mo</span>` : ''}
                        ${!isCore ? (purchased ? '<span class="badge-purchased">Purchased</span>' : '<span class="badge-available">Available</span>') : ''}
                    </label>
                </div>
                <div class="permission-select">
                    <label>Permission:</label>
                    <select class="permission-dropdown" data-module="${module.name}" ${(!hasAccess || isCore) ? 'disabled' : ''}>
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
    const total = billing?.total ?? 0;
    const count = billing?.count ?? 0;
    const paid = billing?.paidModules ?? 0;

    const totalNode = document.getElementById('monthlyTotal');
    const countNode = document.getElementById('activeModulesCount');
    const paidNode = document.getElementById('paidModulesCount');

    if (totalNode) totalNode.textContent = `€${Number(total).toFixed(2)}`;
    if (countNode) countNode.textContent = count;
    if (paidNode) paidNode.textContent = paid;
}

// Save user modules
async function saveUserModules() {
    if (!selectedUserId) return;

    const container = document.getElementById('userModulesList');
    const modules = {};

    // Collect enabled modules and their permissions
    container.querySelectorAll('input[type="checkbox"]:checked:not(:disabled)').forEach(checkbox => {
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
    if (typeof window.showAdminModal === 'function') {
        window.showAdminModal(message, 'success');
        return;
    }
    alert('✓ ' + message);
}

function showError(message) {
    if (typeof window.showAdminModal === 'function') {
        window.showAdminModal(message, 'error');
        return;
    }
    alert('✗ ' + message);
}
