/**
 * User Loader Script
 * Loads logged-in user data from session and updates page headers
 * Usage: import { loadCurrentUser, getCurrentUser } from '/src/scripts/user-loader.js';
 */

let currentUser = null;

/**
 * Fetch current user from session
 * @returns {Promise<Object|null>} User object or null if not logged in
 */
export async function loadCurrentUser() {
  try {
    const response = await fetch("/api/auth/check.php");
    const data = await response.json();

    if (data.success && data.logged_in && data.user) {
      currentUser = data.user;
      updateHeaderUserInfo(currentUser);
      return currentUser;
    } else {
      return null;
    }
  } catch (error) {
    return null;
  }
}

/**
 * Get cached current user (must call loadCurrentUser first)
 * @returns {Object|null}
 */
export function getCurrentUser() {
  return currentUser;
}

/**
 * Update header elements with user info
 * @param {Object} user
 */
function updateHeaderUserInfo(user) {
  if (!user) return;

  // Update user name in header (prefer data attributes)
  const nameSelectors = [
    "[data-user-name]", // Data attribute first
    ".text-sm.font-medium.text-gray-900", // Fallback pattern
    "#headerUserName", // ID fallback
  ];

  for (const selector of nameSelectors) {
    const el = document.querySelector(selector);
    if (
      el &&
      (el.textContent.includes("User") ||
        el.textContent.includes("Smith") ||
        el.textContent.includes("Hospital") ||
        el.textContent.includes("Guest"))
    ) {
      el.textContent = user.name || user.email;
      break;
    }
  }

  // Update role display if present
  const roleEl = document.querySelector(".text-xs.text-gray-500");
  if (roleEl) {
    const roleLabels = {
      admin: "System Administrator",
      donor: "Blood Donor",
      hospital: "Hospital Staff",
      seeker: "Blood Seeker",
    };
    // Only update if it looks like a role label
    const currentText = roleEl.textContent.toLowerCase();
    if (
      currentText.includes("administrator") ||
      currentText.includes("donor") ||
      currentText.includes("hospital") ||
      currentText.includes("seeker") ||
      currentText.includes("staff") ||
      currentText.includes("portal")
    ) {
      roleEl.textContent = roleLabels[user.role] || user.role;
    }
  }

  // Update welcome messages
  const welcomeEls = document.querySelectorAll("p, h1, h2");
  welcomeEls.forEach((el) => {
    if (el.textContent.includes("Welcome back, John")) {
      el.textContent = el.textContent.replace(
        "John",
        user.name?.split(" ")[0] || "User",
      );
    }
  });
}

/**
 * Require authentication - redirect to login if not logged in
 * @param {string[]} allowedRoles - Optional array of allowed roles
 */
export async function requireAuth(allowedRoles = null) {
  const user = await loadCurrentUser();

  if (!user) {
    window.location.href = "/src/pages/auth/login.html";
    return null;
  }

  if (allowedRoles && !allowedRoles.includes(user.role)) {
    alert("Access denied. You do not have permission to view this page.");
    window.location.href = "/src/pages/auth/login.html";
    return null;
  }

  return user;
}

/**
 * Initialize user loader on DOMContentLoaded
 * Auto-updates header if user is logged in
 */
export function initUserLoader() {
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", loadCurrentUser);
  } else {
    loadCurrentUser();
  }
}
