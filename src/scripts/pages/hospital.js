/**
 * Hospital Portal Scripts
 * Handles: dashboard, request form, donors, appointments
 */

import { showNotification } from "../components/notifications.js";

// ============================================
// ACCOUNT STATUS CHECK
// ============================================

/**
 * Check if hospital account is approved and handle UI accordingly
 * @returns {Promise<boolean>} - Returns true if approved, false if pending
 */
export async function checkAccountStatus() {
  try {
    const response = await fetch("/api/hospital/profile.php", {
      credentials: "include",
    });

    const data = await response.json();

    if (!data.success) {
      console.error("Failed to fetch profile:", data.message);
      return false;
    }

    const status = data.account_status || data.profile?.status;

    if (status !== "approved") {
      // Show pending approval message, hide dashboard content
      showPendingApprovalUI();
      return false;
    }

    // Account is approved, show dashboard content
    showApprovedDashboardUI();
    return true;
  } catch (error) {
    console.error("Error checking account status:", error);
    // On error, assume pending for safety
    showPendingApprovalUI();
    return false;
  }
}

/**
 * Show pending approval message and hide dashboard content
 */
function showPendingApprovalUI() {
  const pendingMessage = document.getElementById("pendingApprovalMessage");
  const dashboardContent = document.getElementById("dashboardContent");

  if (pendingMessage) {
    pendingMessage.classList.remove("hidden");
  }

  if (dashboardContent) {
    dashboardContent.classList.add("hidden");
  }

  // Reinitialize icons for the pending message
  if (window.lucide) {
    window.lucide.createIcons();
  }
}

/**
 * Show full dashboard and hide pending message
 */
function showApprovedDashboardUI() {
  const pendingMessage = document.getElementById("pendingApprovalMessage");
  const dashboardContent = document.getElementById("dashboardContent");

  if (pendingMessage) {
    pendingMessage.classList.add("hidden");
  }

  if (dashboardContent) {
    dashboardContent.classList.remove("hidden");
  }
}

// ============================================
// REQUEST FORM
// ============================================

export function initRequestForm() {
  const form = document.getElementById("bloodRequestForm");

  if (form) {
    form.addEventListener("submit", (e) => {
      e.preventDefault();

      // Get form data
      const formData = new FormData(form);
      const data = Object.fromEntries(formData.entries());

      // Validate required fields
      const required = ["patientName", "bloodType", "quantity", "requiredBy"];
      const missing = required.filter((field) => !data[field]);

      if (missing.length > 0) {
        showNotification("Please fill in all required fields.", "error");
        return;
      }

      // Submit success
      showNotification("Blood request submitted successfully!", "success");
      form.reset();
    });
  }
}

// ============================================
// DONOR TRACKING
// ============================================

const donorJourneySteps = [
  { key: "accepted", label: "Request Accepted", icon: "check-circle" },
  { key: "on_the_way", label: "On the Way", icon: "navigation" },
  { key: "arrived", label: "Arrived", icon: "map-pin" },
  { key: "donating", label: "Donating", icon: "heart" },
  { key: "completed", label: "Completed", icon: "check" },
];

export function renderDonorJourney(currentStep) {
  return donorJourneySteps
    .map((step, index) => {
      const isCompleted = index < currentStep;
      const isCurrent = index === currentStep;

      let stepClass = isCompleted
        ? "bg-green-500"
        : isCurrent
          ? "bg-blue-500"
          : "bg-gray-200";
      let iconClass = isCompleted || isCurrent ? "text-white" : "text-gray-400";

      return `
      <div class="flex items-center gap-2">
        <div class="size-8 rounded-full ${stepClass} flex items-center justify-center">
          <i data-lucide="${isCompleted ? "check" : step.icon}" class="size-4 ${iconClass}"></i>
        </div>
        <span class="text-sm ${isCurrent ? "font-semibold text-blue-600" : "text-gray-600"}">${step.label}</span>
      </div>
    `;
    })
    .join('<div class="w-8 h-0.5 bg-gray-200 mx-2"></div>');
}

// ============================================
// APPOINTMENTS
// ============================================

export function initAppointments() {
  const confirmBtns = document.querySelectorAll(".confirm-appointment");
  const cancelBtns = document.querySelectorAll(".cancel-appointment");

  confirmBtns.forEach((btn) => {
    btn.addEventListener("click", () => {
      const id = btn.dataset.id;
      showNotification(`Appointment ${id} confirmed!`, "success");
    });
  });

  cancelBtns.forEach((btn) => {
    btn.addEventListener("click", () => {
      const id = btn.dataset.id;
      showNotification(`Appointment ${id} cancelled.`, "warning");
    });
  });
}

// ============================================
// DASHBOARD STATS
// ============================================

export function updateHospitalStats(stats) {
  const elements = {
    pendingRequests: document.getElementById("pendingRequests"),
    activeDonors: document.getElementById("activeDonors"),
    completedToday: document.getElementById("completedToday"),
    bloodStock: document.getElementById("bloodStock"),
  };

  Object.entries(elements).forEach(([key, el]) => {
    if (el && stats[key] !== undefined) {
      el.textContent = stats[key];
    }
  });
}

// ============================================
// INITIALIZATION
// ============================================

document.addEventListener("DOMContentLoaded", async () => {
  const path = window.location.pathname;

  // Check account status first for all hospital pages
  const isApproved = await checkAccountStatus();

  // Only initialize page-specific features if account is approved
  if (isApproved) {
    if (path.includes("request")) {
      initRequestForm();
    } else if (path.includes("appointments")) {
      initAppointments();
    }
  }

  // Always initialize icons
  if (window.lucide) {
    window.lucide.createIcons();
  }
});

// Export for external use
export { donorJourneySteps };
