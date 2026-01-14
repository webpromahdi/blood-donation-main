/**
 * Hospital Portal Scripts
 * Handles: dashboard, request form, donors, appointments
 */

import { showNotification } from '../components/notifications.js';

// ============================================
// REQUEST FORM
// ============================================

export function initRequestForm() {
    const form = document.getElementById('bloodRequestForm');

    if (form) {
        form.addEventListener('submit', (e) => {
            e.preventDefault();

            // Get form data
            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());

            // Validate required fields
            const required = ['patientName', 'bloodType', 'quantity', 'requiredBy'];
            const missing = required.filter(field => !data[field]);

            if (missing.length > 0) {
                showNotification('Please fill in all required fields.', 'error');
                return;
            }

            // Submit success
            showNotification('Blood request submitted successfully!', 'success');
            form.reset();
        });
    }
}

// ============================================
// DONOR TRACKING
// ============================================

const donorJourneySteps = [
    { key: 'accepted', label: 'Request Accepted', icon: 'check-circle' },
    { key: 'on_the_way', label: 'On the Way', icon: 'navigation' },
    { key: 'arrived', label: 'Arrived', icon: 'map-pin' },
    { key: 'donating', label: 'Donating', icon: 'heart' },
    { key: 'completed', label: 'Completed', icon: 'check' }
];

export function renderDonorJourney(currentStep) {
    return donorJourneySteps.map((step, index) => {
        const isCompleted = index < currentStep;
        const isCurrent = index === currentStep;

        let stepClass = isCompleted ? 'bg-green-500' : isCurrent ? 'bg-blue-500' : 'bg-gray-200';
        let iconClass = isCompleted || isCurrent ? 'text-white' : 'text-gray-400';

        return `
      <div class="flex items-center gap-2">
        <div class="size-8 rounded-full ${stepClass} flex items-center justify-center">
          <i data-lucide="${isCompleted ? 'check' : step.icon}" class="size-4 ${iconClass}"></i>
        </div>
        <span class="text-sm ${isCurrent ? 'font-semibold text-blue-600' : 'text-gray-600'}">${step.label}</span>
      </div>
    `;
    }).join('<div class="w-8 h-0.5 bg-gray-200 mx-2"></div>');
}

// ============================================
// APPOINTMENTS
// ============================================

export function initAppointments() {
    const confirmBtns = document.querySelectorAll('.confirm-appointment');
    const cancelBtns = document.querySelectorAll('.cancel-appointment');

    confirmBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.dataset.id;
            showNotification(`Appointment ${id} confirmed!`, 'success');
        });
    });

    cancelBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.dataset.id;
            showNotification(`Appointment ${id} cancelled.`, 'warning');
        });
    });
}

// ============================================
// DASHBOARD STATS
// ============================================

export function updateHospitalStats(stats) {
    const elements = {
        pendingRequests: document.getElementById('pendingRequests'),
        activeDonors: document.getElementById('activeDonors'),
        completedToday: document.getElementById('completedToday'),
        bloodStock: document.getElementById('bloodStock')
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

document.addEventListener('DOMContentLoaded', () => {
    const path = window.location.pathname;

    if (path.includes('request')) {
        initRequestForm();
    } else if (path.includes('appointments')) {
        initAppointments();
    }

    // Always initialize icons
    if (window.lucide) {
        window.lucide.createIcons();
    }
});

// Export for external use
export { donorJourneySteps };
