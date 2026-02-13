/**
 * Permissions and Roles Management
 */

const API_BASE = '/api';
let allUsers = [];
let selectedUserId = null;
let selectedUserLabel = '-';
let roleList = [];
let moduleList = [];
let userPermissions = {};

// Initialize
window.addEventListener('DOMContentLoaded', () => {
  setupEventListeners();
  loadRoles();
  loadUsers();
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

  const roleForm = document.getElementById('role-form');
  if (roleForm) {
    roleForm.addEventListener('submit', onRoleFormSubmit);
  }

  const roleReset = document.getElementById('role-reset');
  if (roleReset) {
    roleReset.addEventListener('click', resetRoleForm);
  }

  const savePermissions = document.getElementById('savePermissionsBtn');
  if (savePermissions) {
    savePermissions.addEventListener('click', saveUserPermissions);
  }
}

async function loadRoles() {
  try {
    const response = await fetch(`${API_BASE}/roles`, {
      headers: {
        'Content-Type': 'application/json'
      }
    });

    if (!response.ok) throw new Error('Failed to load roles');

    const result = await response.json();
    roleList = result.data || [];
    renderRoles();
  } catch (error) {
    console.error('Error loading roles:', error);
    showError('Failed to load roles');
  }
}

function renderRoles() {
  const tbody = document.getElementById('roles-table-body');
  if (!tbody) {
    return;
  }

  if (roleList.length === 0) {
    tbody.innerHTML = '<tr><td colspan="3">No roles found</td></tr>';
    return;
  }

  tbody.innerHTML = roleList.map(role => {
    const description = role.description || '';
    return `
      <tr>
        <td>${role.name}</td>
        <td>${description}</td>
        <td>
          <button class="btn btn-ghost btn-sm btn-icon role-edit" data-role-id="${role.id}" data-role-name="${role.name}" data-role-description="${description}" title="Edit" aria-label="Edit">
            <i class="fa-solid fa-pen"></i>
          </button>
          <button class="btn btn-danger btn-sm btn-icon role-delete ms-2" data-role-id="${role.id}" title="Delete" aria-label="Delete">
            <i class="fa-solid fa-trash"></i>
          </button>
        </td>
      </tr>
    `;
  }).join('');

  tbody.querySelectorAll('.role-edit').forEach(button => {
    button.addEventListener('click', () => {
      const roleId = button.getAttribute('data-role-id');
      const roleName = button.getAttribute('data-role-name') || '';
      const roleDescription = button.getAttribute('data-role-description') || '';
      fillRoleForm(roleId, roleName, roleDescription);
    });
  });

  tbody.querySelectorAll('.role-delete').forEach(button => {
    button.addEventListener('click', () => {
      const roleId = button.getAttribute('data-role-id');
      deleteRole(roleId);
    });
  });
}

function fillRoleForm(roleId, name, description) {
  const idInput = document.getElementById('role-id');
  const nameInput = document.getElementById('role-name');
  const descInput = document.getElementById('role-description');

  if (idInput) idInput.value = roleId || '';
  if (nameInput) nameInput.value = name || '';
  if (descInput) descInput.value = description || '';
}

function resetRoleForm() {
  fillRoleForm('', '', '');
}

async function onRoleFormSubmit(event) {
  event.preventDefault();

  const id = document.getElementById('role-id')?.value?.trim();
  const name = document.getElementById('role-name')?.value?.trim();
  const description = document.getElementById('role-description')?.value?.trim();

  if (!name) {
    showError('Role name is required');
    return;
  }

  const payload = { name, description };

  try {
    const response = await fetch(id ? `${API_BASE}/roles/${id}` : `${API_BASE}/roles`, {
      method: id ? 'PUT' : 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(payload)
    });

    if (!response.ok) throw new Error('Failed to save role');

    await response.json();
    resetRoleForm();
    await loadRoles();
  } catch (error) {
    console.error('Error saving role:', error);
    showError('Failed to save role');
  }
}

async function deleteRole(roleId) {
  if (!roleId) {
    return;
  }

  if (!window.confirm('Delete this role?')) {
    return;
  }

  try {
    const response = await fetch(`${API_BASE}/roles/${roleId}`, {
      method: 'DELETE',
      headers: {
        'Content-Type': 'application/json'
      }
    });

    if (!response.ok) throw new Error('Failed to delete role');

    await response.json();
    await loadRoles();
  } catch (error) {
    console.error('Error deleting role:', error);
    showError('Failed to delete role');
  }
}

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
    list.innerHTML = '<div class="user-list-empty">No users found</div>';
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
    const container = document.getElementById('userPermissionsContainer');
    if (container) {
      container.style.display = 'none';
    }
    return;
  }

  loadUserPermissions(selectedUserId, selectedUserLabel);
  renderUserOptions(allUsers);
}

async function loadUserPermissions(userId, label) {
  try {
    const response = await fetch(`${API_BASE}/users/${userId}/permissions`, {
      headers: {
        'Content-Type': 'application/json'
      }
    });

    if (!response.ok) throw new Error('Failed to load permissions');

    const result = await response.json();
    const data = result.data || {};

    moduleList = Object.keys(data.modules || {}).map(name => ({ name }));
    userPermissions = data.user_modules || {};

    const selectedNode = document.getElementById('selectedUsername');
    if (selectedNode) {
      selectedNode.textContent = label || selectedUserLabel || '-';
    }

    renderPermissionsList();

    const container = document.getElementById('userPermissionsContainer');
    if (container) {
      container.style.display = 'block';
    }
  } catch (error) {
    console.error('Error loading permissions:', error);
    showError('Failed to load permissions');
  }
}

function renderPermissionsList() {
  const list = document.getElementById('userPermissionsList');
  if (!list) {
    return;
  }

  if (moduleList.length === 0) {
    list.innerHTML = '<div class="user-list-empty">No modules available</div>';
    return;
  }

  list.innerHTML = moduleList.map(module => {
    const permission = Number(userPermissions[module.name] ?? 0);
    return `
      <div class="user-module-item">
        <div class="module-checkbox">
          <strong>${module.name}</strong>
        </div>
        <div class="permission-select">
          <label>Permission:</label>
          <select class="permission-dropdown" data-module="${module.name}">
            <option value="0" ${permission === 0 ? 'selected' : ''}>None</option>
            <option value="1" ${permission === 1 ? 'selected' : ''}>Read Only</option>
            <option value="7" ${permission === 7 ? 'selected' : ''}>Read/Write</option>
            <option value="15" ${permission === 15 ? 'selected' : ''}>Full Access</option>
          </select>
        </div>
      </div>
    `;
  }).join('');
}

async function saveUserPermissions() {
  if (!selectedUserId) {
    return;
  }

  const list = document.getElementById('userPermissionsList');
  if (!list) {
    return;
  }

  const permissions = {};
  list.querySelectorAll('.permission-dropdown').forEach(select => {
    const moduleName = select.getAttribute('data-module');
    const level = Number(select.value);
    if (moduleName) {
      permissions[moduleName] = level;
    }
  });

  try {
    const response = await fetch(`${API_BASE}/users/${selectedUserId}/permissions`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ permissions })
    });

    if (!response.ok) throw new Error('Failed to save permissions');

    await response.json();
    showSuccess('Permissions updated');
  } catch (error) {
    console.error('Error saving permissions:', error);
    showError('Failed to save permissions');
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
