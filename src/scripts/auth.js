/**
 * Authentication Utilities
 * Shared functions for login state, logout, and session management
 */

// API base path - adjust if your project is in a subdirectory
const API_BASE = '/api';

/**
 * Check if user is currently logged in
 * @returns {Promise<{loggedIn: boolean, user: object|null}>}
 */
export async function checkAuth() {
  try {
    const response = await fetch(`${API_BASE}/auth/check.php`, {
      method: 'GET',
      credentials: 'include'
    });
    const data = await response.json();

    if (data.success && data.logged_in) {
      return { loggedIn: true, user: data.user };
    }
    return { loggedIn: false, user: null };
  } catch (error) {
    console.error('Auth check failed:', error);
    return { loggedIn: false, user: null };
  }
}

/**
 * Logout the current user
 * @returns {Promise<boolean>}
 */
export async function logout() {
  try {
    const response = await fetch(`${API_BASE}/auth/logout.php`, {
      method: 'POST',
      credentials: 'include'
    });
    const data = await response.json();
    return data.success === true;
  } catch (error) {
    console.error('Logout failed:', error);
    return false;
  }
}

/**
 * Redirect to login if not authenticated
 * @param {string[]} allowedRoles - Optional array of allowed roles
 */
export async function requireAuth(allowedRoles = null) {
  const { loggedIn, user } = await checkAuth();

  if (!loggedIn) {
    window.location.href = '/blood-donation-main/src/pages/auth/login.html';
    return null;
  }

  if (allowedRoles && !allowedRoles.includes(user.role)) {
    // Redirect to appropriate dashboard based on actual role
    const dashboards = {
      donor: '/blood-donation-main/src/pages/donor/dashboard.html',
      admin: '/blood-donation-main/src/pages/admin/dashboard.html',
      hospital: '/blood-donation-main/src/pages/hospital/dashboard.html',
      seeker: '/blood-donation-main/src/pages/seeker/request.html'
    };
    window.location.href = dashboards[user.role] || '/blood-donation-main/';
    return null;
  }

  return user;
}

/**
 * Setup logout button handler
 * Call this on page load for any page with a logout button
 */
export function setupLogoutButton(buttonSelector = '#logoutBtn, [data-logout]') {
  const buttons = document.querySelectorAll(buttonSelector);

  buttons.forEach(btn => {
    btn.addEventListener('click', async (e) => {
      e.preventDefault();

      const success = await logout();

      if (success) {
        window.location.href = '/blood-donation-main/src/pages/auth/login.html';
      } else {
        alert('Logout failed. Please try again.');
      }
    });
  });
}

/**
 * Update user info display on page
 * @param {object} user - User object with name, email, role
 */
export function updateUserDisplay(user) {
  const nameEls = document.querySelectorAll('[data-user-name]');
  const emailEls = document.querySelectorAll('[data-user-email]');
  const roleEls = document.querySelectorAll('[data-user-role]');

  nameEls.forEach(el => el.textContent = user.name || user.email);
  emailEls.forEach(el => el.textContent = user.email);
  roleEls.forEach(el => el.textContent = user.role);
}

/**
 * Initialize auth on a protected page
 * Checks auth, updates UI, and sets up logout
 */
export async function initProtectedPage(allowedRoles = null) {
  const user = await requireAuth(allowedRoles);

  if (user) {
    updateUserDisplay(user);
    setupLogoutButton();
  }

  return user;
}
