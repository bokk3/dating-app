/**
 * Admin Panel JavaScript
 * 
 * Handles admin interface functionality, real-time updates, and user management
 */

class AdminPanel {
    constructor() {
        this.apiBase = '/api/admin.php';
        this.refreshInterval = 30000; // 30 seconds
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.loadDashboard();
        this.startAutoRefresh();
        this.setupSearch();
        this.setupFilters();
    }

    setupEventListeners() {
        // Navigation
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const section = e.target.dataset.section;
                this.showSection(section);
            });
        });

        // User management actions
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('btn-activate')) {
                this.toggleUserStatus(e.target.dataset.userId, 'activate');
            } else if (e.target.classList.contains('btn-deactivate')) {
                this.toggleUserStatus(e.target.dataset.userId, 'deactivate');
            } else if (e.target.classList.contains('btn-make-admin')) {
                this.toggleAdminStatus(e.target.dataset.userId, true);
            } else if (e.target.classList.contains('btn-remove-admin')) {
                this.toggleAdminStatus(e.target.dataset.userId, false);
            } else if (e.target.classList.contains('btn-view-user')) {
                this.viewUserDetails(e.target.dataset.userId);
            } else if (e.target.classList.contains('btn-delete-user')) {
                this.deleteUser(e.target.dataset.userId);
            }
        });

        // Help request actions
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('btn-resolve-ticket')) {
                this.resolveTicket(e.target.dataset.ticketId);
            } else if (e.target.classList.contains('btn-respond-ticket')) {
                this.showResponseModal(e.target.dataset.ticketId);
            }
        });

        // Modal handlers
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal-close') || e.target.classList.contains('modal-overlay')) {
                this.closeModal();
            }
        });

        // Form submissions
        document.addEventListener('submit', (e) => {
            if (e.target.id === 'response-form') {
                e.preventDefault();
                this.submitResponse(e.target);
            }
        });
    }

    async loadDashboard() {
        try {
            const response = await fetch(`${this.apiBase}?action=dashboard`);
            const data = await response.json();
            
            if (data.success) {
                this.updateDashboardStats(data.stats);
                this.updateRecentActivity(data.recent_activity);
            }
        } catch (error) {
            console.error('Failed to load dashboard:', error);
            this.showError('Failed to load dashboard data');
        }
    }

    updateDashboardStats(stats) {
        document.getElementById('total-users').textContent = stats.total_users || 0;
        document.getElementById('active-users').textContent = stats.active_users || 0;
        document.getElementById('new-users-today').textContent = stats.new_users_today || 0;
        document.getElementById('total-matches').textContent = stats.total_matches || 0;
        document.getElementById('messages-today').textContent = stats.messages_today || 0;
        document.getElementById('open-tickets').textContent = stats.open_tickets || 0;
        document.getElementById('resolved-tickets').textContent = stats.resolved_tickets || 0;
        document.getElementById('total-revenue').textContent = '$' + (stats.total_revenue || 0);
    }

    updateRecentActivity(activities) {
        const container = document.getElementById('recent-activity');
        if (!container) return;

        container.innerHTML = activities.map(activity => `
            <div class="activity-item">
                <div class="activity-time">${this.formatDateTime(activity.created_at)}</div>
                <div class="activity-description">${activity.description}</div>
                <div class="activity-user">by ${activity.admin_name}</div>
            </div>
        `).join('');
    }

    async loadUsers(page = 1, search = '', filters = {}) {
        try {
            const params = new URLSearchParams({
                action: 'users',
                page: page,
                search: search,
                ...filters
            });

            const response = await fetch(`${this.apiBase}?${params}`);
            const data = await response.json();
            
            if (data.success) {
                this.displayUsers(data.users);
                this.updatePagination(data.pagination);
            }
        } catch (error) {
            console.error('Failed to load users:', error);
            this.showError('Failed to load users');
        }
    }

    displayUsers(users) {
        const container = document.getElementById('users-table-body');
        if (!container) return;

        container.innerHTML = users.map(user => `
            <tr>
                <td>${user.id}</td>
                <td>
                    <div class="user-info">
                        <img src="${user.profile_photo || '/images/default-avatar.png'}" 
                             alt="Profile" class="user-avatar">
                        <div>
                            <div class="user-name">${user.first_name} ${user.last_name}</div>
                            <div class="user-email">${user.email}</div>
                        </div>
                    </div>
                </td>
                <td><span class="status-badge status-${user.status}">${user.status}</span></td>
                <td>${user.is_admin ? '<span class="admin-badge">Admin</span>' : 'User'}</td>
                <td>${this.formatDateTime(user.created_at)}</td>
                <td>${this.formatDateTime(user.last_login)}</td>
                <td>
                    <div class="action-buttons">
                        <button class="btn btn-sm btn-primary btn-view-user" 
                                data-user-id="${user.id}">View</button>
                        ${user.status === 'active' ? 
                            `<button class="btn btn-sm btn-warning btn-deactivate" 
                                     data-user-id="${user.id}">Deactivate</button>` :
                            `<button class="btn btn-sm btn-success btn-activate" 
                                     data-user-id="${user.id}">Activate</button>`
                        }
                        ${!user.is_admin ? 
                            `<button class="btn btn-sm btn-info btn-make-admin" 
                                     data-user-id="${user.id}">Make Admin</button>` :
                            `<button class="btn btn-sm btn-secondary btn-remove-admin" 
                                     data-user-id="${user.id}">Remove Admin</button>`
                        }
                        <button class="btn btn-sm btn-danger btn-delete-user" 
                                data-user-id="${user.id}">Delete</button>
                    </div>
                </td>
            </tr>
        `).join('');
    }

    async loadHelpRequests(page = 1, status = '', search = '') {
        try {
            const params = new URLSearchParams({
                action: 'help_requests',
                page: page,
                status: status,
                search: search
            });

            const response = await fetch(`${this.apiBase}?${params}`);
            const data = await response.json();
            
            if (data.success) {
                this.displayHelpRequests(data.tickets);
                this.updatePagination(data.pagination);
            }
        } catch (error) {
            console.error('Failed to load help requests:', error);
            this.showError('Failed to load help requests');
        }
    }

    displayHelpRequests(tickets) {
        const container = document.getElementById('tickets-table-body');
        if (!container) return;

        container.innerHTML = tickets.map(ticket => `
            <tr>
                <td>${ticket.id}</td>
                <td>${ticket.user_name}</td>
                <td>${ticket.subject}</td>
                <td><span class="priority-badge priority-${ticket.priority}">${ticket.priority}</span></td>
                <td><span class="status-badge status-${ticket.status}">${ticket.status}</span></td>
                <td>${this.formatDateTime(ticket.created_at)}</td>
                <td>
                    <div class="action-buttons">
                        <button class="btn btn-sm btn-primary btn-respond-ticket" 
                                data-ticket-id="${ticket.id}">Respond</button>
                        ${ticket.status !== 'resolved' ? 
                            `<button class="btn btn-sm btn-success btn-resolve-ticket" 
                                     data-ticket-id="${ticket.id}">Resolve</button>` : ''
                        }
                    </div>
                </td>
            </tr>
        `).join('');
    }

    setupSearch() {
        const searchInput = document.getElementById('search-input');
        if (!searchInput) return;

        let searchTimeout;
        searchInput.addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                const currentSection = document.querySelector('.content-section.active').id;
                if (currentSection === 'users-section') {
                    this.loadUsers(1, e.target.value);
                } else if (currentSection === 'tickets-section') {
                    this.loadHelpRequests(1, '', e.target.value);
                }
            }, 500);
        });
    }

    setupFilters() {
        // Status filters
        document.querySelectorAll('.filter-status').forEach(filter => {
            filter.addEventListener('change', () => {
                const currentSection = document.querySelector('.content-section.active').id;
                if (currentSection === 'users-section') {
                    this.loadUsers(1, '', { status: filter.value });
                } else if (currentSection === 'tickets-section') {
                    this.loadHelpRequests(1, filter.value);
                }
            });
        });
    }

    async toggleUserStatus(userId, action) {
        if (!confirm(`Are you sure you want to ${action} this user?`)) return;

        try {
            const response = await fetch(this.apiBase, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'toggle_user_status',
                    user_id: userId,
                    status: action
                })
            });

            const data = await response.json();
            if (data.success) {
                this.showSuccess(`User ${action}d successfully`);
                this.loadUsers(); // Refresh user list
            } else {
                this.showError(data.message);
            }
        } catch (error) {
            console.error('Failed to toggle user status:', error);
            this.showError('Failed to update user status');
        }
    }

    showSection(sectionId) {
        // Hide all sections
        document.querySelectorAll('.content-section').forEach(section => {
            section.classList.remove('active');
        });

        // Show target section
        document.getElementById(`${sectionId}-section`).classList.add('active');

        // Update navigation
        document.querySelectorAll('.nav-link').forEach(link => {
            link.classList.remove('active');
        });
        document.querySelector(`[data-section="${sectionId}"]`).classList.add('active');

        // Load section data
        switch (sectionId) {
            case 'users':
                this.loadUsers();
                break;
            case 'tickets':
                this.loadHelpRequests();
                break;
            case 'dashboard':
                this.loadDashboard();
                break;
        }
    }

    startAutoRefresh() {
        setInterval(() => {
            const currentSection = document.querySelector('.content-section.active').id;
            if (currentSection === 'dashboard-section') {
                this.loadDashboard();
            }
        }, this.refreshInterval);
    }

    formatDateTime(dateString) {
        if (!dateString) return 'Never';
        return new Date(dateString).toLocaleString();
    }

    showSuccess(message) {
        this.showNotification(message, 'success');
    }

    showError(message) {
        this.showNotification(message, 'error');
    }

    showNotification(message, type) {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.classList.add('show');
        }, 100);
        
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }

    closeModal() {
        document.querySelectorAll('.modal').forEach(modal => {
            modal.style.display = 'none';
        });
    }
}

// Initialize admin panel when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new AdminPanel();
});