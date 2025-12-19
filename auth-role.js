// auth-role.js
// Shared auth + role helpers for GitHub Pages (uses skysail_user from localStorage)

const SKYSAIL_USER_KEY = 'skysail_user';

// Get current user object or null
function getCurrentUser() {
    try {
        const raw = localStorage.getItem(SKYSAIL_USER_KEY);
        if (!raw) return null;
        const user = JSON.parse(raw);
        // Return user if it's a valid object (has at least id_user, id, or username)
        if (user && typeof user === 'object' && (user.id_user || user.id || user.username)) {
            return user;
        }
        return null;
    } catch (e) {
        console.error('Failed to parse user from localStorage', e);
        return null;
    }
}

// Clear user and go to login
function logoutAndRedirect(loginPath = './login.html') {
    localStorage.removeItem(SKYSAIL_USER_KEY);
    window.location.href = loginPath;
}

// Require any logged‑in user; otherwise redirect to login
function requireLogin(options = {}) {
    const {
        loginPath = './login.html',
    } = options;

    const user = getCurrentUser();
    // Check if user exists and has at least an id_user, id, or username
    if (!user || (!user.id_user && !user.id && !user.username)) {
        window.location.href = loginPath;
        return null;
    }
    return user;
}

// Require specific role(s); if not matched, redirect elsewhere
function requireRole(allowedRoles, options = {}) {
    const {
        loginPath = './login.html',
        forbiddenPath = './index.html',
    } = options;

    const user = requireLogin({ loginPath });
    if (!user) return null;

    const role = user.role || 'user';
    const allowed = Array.isArray(allowedRoles) ? allowedRoles : [allowedRoles];

    if (!allowed.includes(role)) {
        window.location.href = forbiddenPath;
        return null;
    }

    return user;
}

// Show/hide elements based on role after DOM is ready
function applyRoleVisibility() {
    const user = getCurrentUser();
    const role = user?.role || null;

    document.querySelectorAll('[data-role-visible]').forEach(el => {
        const allowed = el.getAttribute('data-role-visible').split(',').map(s => s.trim());
        if (!role || !allowed.includes(role)) {
            el.style.display = 'none';
        }
    });
}

document.addEventListener('DOMContentLoaded', applyRoleVisibility);


