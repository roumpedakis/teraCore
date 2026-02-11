// User Management JavaScript
const API_BASE = '/api';
const token = localStorage.getItem('authToken');

// Check auth on page load
document.addEventListener('DOMContentLoaded', () => {
    if (!token) {
        window.location.href = '/admin/login';
        return;
    }
    
    // Load users
    loadUsers();
    
    // Setup event listeners
    setupEventListeners();
});

// Setup event listeners
function setupEventListeners() {
    // Logout button
    const logoutBtn = document.getElementById('logout-button');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', logout);
    }
    
    // New user button
    const newUserBtn = document.getElementById('new-user-btn');
    if (newUserBtn) {
        newUserBtn.addEventListener('click', () => openUserModal());
    }
    
    // Save user button
    const saveUserBtn = document.getElementById('save-user-btn');
    if (saveUserBtn) {
        saveUserBtn.addEventListener('click', saveUser);
    }
    
    // Save permissions button
    const savePermissionsBtn = document.getElementById('save-permissions-btn');
    if (savePermissionsBtn) {
        savePermissionsBtn.addEventListener('click', saveUserPermissions);
    }
    
    // Confirm password button
    const confirmPasswordBtn = document.getElementById('confirm-password-btn');
    if (confirmPasswordBtn) {
        confirmPasswordBtn.addEventListener('click', confirmPasswordReset);
    }
    
    // Search input
    const searchInput = document.getElementById('user-search');
    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            loadUsers({ search: e.target.value });
        });
    }
}

function logout() {
    localStorage.removeItem('authToken');
    window.location.href = '/admin/login';
}

// Load all users
async function loadUsers(params = {}) {
    try {
        const queryString = new URLSearchParams(params).toString();
        const url = `${API_BASE}/users${queryString ? '?' + queryString : ''}`;
        
        const response = await fetch(url, {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            displayUsers(result.data);
        } else {
            showToast('Failed to load users: ' + (result.error || 'Unknown error'), 'danger');
        }
    } catch (error) {
        console.error('Error loading users:', error);
        showToast('Failed to load users', 'danger');
    }
}

// Display users in table
function displayUsers(users) {
    const tbody = document.getElementById('users-table-body');
    if (!tbody) return;
    
    if (users.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center">No users found</td></tr>';
        return;
    }
    
    tbody.innerHTML = users.map(user => `
        <tr>
            <td>
                <div>
                    <i class="fa-solid fa-user-circle"></i> <strong>${escapeHtml(user.username)}</strong>
                    ${user.first_name || user.last_name ? `<br><small class="text-muted"><i class="fa-solid fa-id-badge"></i> ${escapeHtml((user.first_name || '') + ' ' + (user.last_name || ''))}</small>` : ''}
                </div>
            </td>
            <td><i class="fa-solid fa-envelope"></i> ${escapeHtml(user.email)}</td>
            <td><span class="badge bg-secondary"><i class="fa-solid fa-user-tag"></i> User</span></td>
            <td>
                <span class="badge ${user.is_active ? 'bg-success' : 'bg-danger'}">
                    <i class="fa-solid fa-${user.is_active ? 'check-circle' : 'times-circle'}"></i>
                    ${user.is_active ? 'Active' : 'Inactive'}
                </span>
            </td>
            <td>
                <button class="btn btn-sm btn-ghost" onclick="viewUserTokens(${user.id})" title="View Tokens">
                    <i class="fa-solid fa-key"></i>
                </button>
            </td>
            <td>
                <div class="btn-group" role="group">
                    <button class="btn btn-sm btn-ghost" onclick="editUser(${user.id})" title="Edit User">
                        <i class="fa-solid fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-ghost" onclick="manageUserPermissions(${user.id}, '${escapeHtml(user.username)}')" title="Manage Permissions">
                        <i class="fa-solid fa-shield-halved"></i>
                    </button>
                    <button class="btn btn-sm btn-ghost" onclick="toggleUserStatus(${user.id}, ${user.is_active})" title="${user.is_active ? 'Deactivate' : 'Activate'}">
                        <i class="fa-solid fa-${user.is_active ? 'toggle-on' : 'toggle-off'}"></i>
                    </button>
                    <button class="btn btn-sm btn-ghost" onclick="resetUserPassword(${user.id})" title="Reset Password">
                        <i class="fa-solid fa-lock-open"></i>
                    </button>
                    <button class="btn btn-sm btn-ghost text-danger" onclick="deleteUser(${user.id}, '${escapeHtml(user.username)}')" title="Delete User">
                        <i class="fa-solid fa-trash-can"></i>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

// Open user modal (create or edit)
function openUserModal(user = null) {
    const modal = document.getElementById('user-modal');
    const modalTitle = document.getElementById('user-modal-title');
    const form = document.getElementById('user-form');
    
    if (!modal || !form) return;
    
    // Reset form
    form.reset();
    document.getElementById('user-id').value = user ? user.id : '';
    
    if (user) {
        // Edit mode
        modalTitle.textContent = 'Edit User';
        document.getElementById('user-username').value = user.username;
        document.getElementById('user-username').disabled = true; // Can't change username
        document.getElementById('user-email').value = user.email;
        document.getElementById('user-first-name').value = user.first_name || '';
        document.getElementById('user-last-name').value = user.last_name || '';
        document.getElementById('user-active').checked = user.is_active;
        document.getElementById('user-password').required = false;
        document.getElementById('user-password').placeholder = 'Leave empty to keep current password';
    } else {
        // Create mode
        modalTitle.textContent = 'New User';
        document.getElementById('user-username').disabled = false;
        document.getElementById('user-password').required = true;
        document.getElementById('user-password').placeholder = '';
        document.getElementById('user-active').checked = true;
    }
    
    // Show modal
    const bsModal = new bootstrap.Modal(modal);
    bsModal.show();
}

// Edit user
async function editUser(userId) {
    try {
        const response = await fetch(`${API_BASE}/users/${userId}`, {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            openUserModal(result.data);
        } else {
            showToast('Failed to load user: ' + (result.error || 'Unknown error'), 'danger');
        }
    } catch (error) {
        console.error('Error loading user:', error);
        showToast('Failed to load user', 'danger');
    }
}

// Save user (create or update)
async function saveUser() {
    const userId = document.getElementById('user-id').value;
    const isEdit = !!userId;
    
    const data = {
        username: document.getElementById('user-username').value,
        email: document.getElementById('user-email').value,
        first_name: document.getElementById('user-first-name').value,
        last_name: document.getElementById('user-last-name').value,
        is_active: document.getElementById('user-active').checked ? 1 : 0,
    };
    
    const password = document.getElementById('user-password').value;
    if (password) {
        data.password = password;
    }
    
    try {
        const url = isEdit ? `${API_BASE}/users/${userId}` : `${API_BASE}/users`;
        const method = isEdit ? 'PUT' : 'POST';
        
        const response = await fetch(url, {
            method: method,
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast(result.message || (isEdit ? 'User updated' : 'User created'), 'success');
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('user-modal'));
            modal.hide();
            // Reload users
            loadUsers();
        } else {
            showToast(result.error || 'Failed to save user', 'danger');
        }
    } catch (error) {
        console.error('Error saving user:', error);
        showToast('Failed to save user', 'danger');
    }
}

// Toggle user status
async function toggleUserStatus(userId, currentStatus) {
    const action = currentStatus ? 'deactivate' : 'activate';
    
    showConfirmModal(
        `Are you sure you want to ${action} this user?`,
        async () => {
            try {
                const response = await fetch(`${API_BASE}/users/${userId}/toggle-status`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'application/json'
                    }
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showToast(result.message, 'success');
                    loadUsers();
                } else {
                    showToast(result.error || 'Failed to toggle status', 'danger');
                }
            } catch (error) {
                console.error('Error toggling status:', error);
                showToast('Failed to toggle status', 'danger');
            }
        }
    );
}

// Reset user password
function resetUserPassword(userId) {
    showPasswordModal(userId);
}

// Confirm password reset
async function confirmPasswordReset() {
    const userId = document.getElementById('password-user-id').value;
    const newPassword = document.getElementById('new-password').value;
    
    if (!newPassword) {
        showToast('Please enter a password', 'warning');
        return;
    }
    
    try {
        const response = await fetch(`${API_BASE}/users/${userId}/reset-password`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ password: newPassword })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast(result.message, 'success');
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('password-modal'));
            modal.hide();
        } else {
            showToast(result.error || 'Failed to reset password', 'danger');
        }
    } catch (error) {
        console.error('Error resetting password:', error);
        showToast('Failed to reset password', 'danger');
    }
}

// Delete user
function deleteUser(userId, username) {
    showConfirmModal(
        `Are you sure you want to delete user "${username}"? This action cannot be undone.`,
        async () => {
            try {
                const response = await fetch(`${API_BASE}/users/${userId}`, {
                    method: 'DELETE',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'application/json'
                    }
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showToast(result.message, 'success');
                    loadUsers();
                } else {
                    showToast(result.error || 'Failed to delete user', 'danger');
                }
            } catch (error) {
                console.error('Error deleting user:', error);
                showToast('Failed to delete user', 'danger');
            }
        }
    );
}

// View user tokens (placeholder)
function viewUserTokens(userId) {
    showToast('Token management coming soon', 'info');
}

// Manage user permissions
async function manageUserPermissions(userId, username) {
    const modal = document.getElementById('permissions-modal');
    if (!modal) return;
    
    // Set user info
    document.getElementById('permissions-user-id').value = userId;
    document.getElementById('permissions-username').textContent = username;
    
    // Show modal with loading state
    const bsModal = new bootstrap.Modal(modal);
    bsModal.show();
    
    try {
        // Load user permissions
        const response = await fetch(`${API_BASE}/users/${userId}/permissions`, {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            displayPermissions(result.data);
        } else {
            showToast('Failed to load permissions', 'danger');
        }
    } catch (error) {
        console.error('Error loading permissions:', error);
        showToast('Failed to load permissions', 'danger');
    }
}

// Display permissions
function displayPermissions(permissions) {
    const container = document.getElementById('permissions-list');
    if (!container) return;
    
    const modules = permissions.modules || {};
    const userModules = permissions.user_modules || {};
    
    if (Object.keys(modules).length === 0) {
        container.innerHTML = '<p class="text-muted"><i class="fa-solid fa-info-circle"></i> No modules available</p>';
        return;
    }
    
    container.innerHTML = Object.keys(modules).map(moduleName => {
        const currentLevel = userModules[moduleName] || 0;
        return `
            <div class="card mb-2">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1"><i class="fa-solid fa-cube"></i> ${escapeHtml(moduleName)}</h6>
                            <small class="text-muted">Version: ${escapeHtml(modules[moduleName])}</small>
                        </div>
                        <div>
                            <select class="form-select form-select-sm" data-module="${escapeHtml(moduleName)}" style="width: 150px;">
                                <option value="0" ${currentLevel === 0 ? 'selected' : ''}><i class="fa-solid fa-ban"></i> No Access</option>
                                <option value="1" ${currentLevel === 1 ? 'selected' : ''}><i class="fa-solid fa-eye"></i> Read Only</option>
                                <option value="2" ${currentLevel === 2 ? 'selected' : ''}><i class="fa-solid fa-pen"></i> Read/Write</option>
                                <option value="3" ${currentLevel === 3 ? 'selected' : ''}><i class="fa-solid fa-crown"></i> Full Access</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

// Save user permissions
async function saveUserPermissions() {
    const userId = document.getElementById('permissions-user-id').value;
    const selects = document.querySelectorAll('#permissions-list select');
    
    const permissions = {};
    selects.forEach(select => {
        const moduleName = select.dataset.module;
        const level = parseInt(select.value);
        permissions[moduleName] = level;
    });
    
    try {
        const response = await fetch(`${API_BASE}/users/${userId}/permissions`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ permissions })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast(result.message || 'Permissions updated', 'success');
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('permissions-modal'));
            modal.hide();
        } else {
            showToast(result.error || 'Failed to save permissions', 'danger');
        }
    } catch (error) {
        console.error('Error saving permissions:', error);
        showToast('Failed to save permissions', 'danger');
    }
}

// Utility functions
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Show toast notification
function showToast(message, type = 'info') {
    const toastContainer = document.getElementById('toast-container');
    if (!toastContainer) return;
    
    const icons = {
        success: 'fa-circle-check',
        danger: 'fa-circle-xmark',
        warning: 'fa-triangle-exclamation',
        info: 'fa-circle-info'
    };
    
    const icon = icons[type] || icons.info;
    const toastId = 'toast-' + Date.now();
    
    const toastHtml = `
        <div id="${toastId}" class="toast align-items-center text-bg-${type} border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fa-solid ${icon}"></i> ${escapeHtml(message)}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    `;
    
    toastContainer.insertAdjacentHTML('beforeend', toastHtml);
    
    const toastElement = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastElement, { delay: 3000 });
    toast.show();
    
    // Remove from DOM after hidden
    toastElement.addEventListener('hidden.bs.toast', () => {
        toastElement.remove();
    });
}

// Show confirmation modal
function showConfirmModal(message, onConfirm) {
    const modal = document.getElementById('confirm-modal');
    if (!modal) return;
    
    document.getElementById('confirm-message').textContent = message;
    
    const bsModal = new bootstrap.Modal(modal);
    bsModal.show();
    
    // Setup confirm button
    const confirmBtn = document.getElementById('confirm-action-btn');
    const newConfirmBtn = confirmBtn.cloneNode(true);
    confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);
    
    newConfirmBtn.addEventListener('click', () => {
        bsModal.hide();
        if (onConfirm) onConfirm();
    });
}

// Show password modal
function showPasswordModal(userId) {
    const modal = document.getElementById('password-modal');
    if (!modal) return;
    
    document.getElementById('password-user-id').value = userId;
    document.getElementById('new-password').value = '';
    
    const bsModal = new bootstrap.Modal(modal);
    bsModal.show();
}
