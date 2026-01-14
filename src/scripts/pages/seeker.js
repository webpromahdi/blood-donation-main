/**
 * Seeker Portal Scripts
 * Handles: tracking, request-details, chat
 */

import { initModal } from '../components/modal.js';
import { showNotification } from '../components/notifications.js';

// ============================================
// TRACKING PAGE - Lifecycle Status Management
// ============================================

const lifecycleSteps = [
    { key: 'submitted', label: 'Submitted', icon: 'send' },
    { key: 'approved', label: 'Approved', icon: 'check-circle' },
    { key: 'donor_assigned', label: 'Donor Assigned', icon: 'user-check' },
    { key: 'on_the_way', label: 'On the Way', icon: 'navigation' },
    { key: 'reached', label: 'Reached', icon: 'map-pin' },
    { key: 'completed', label: 'Completed', icon: 'heart' }
];

const statusConfig = {
    'submitted': {
        label: 'Request Submitted',
        message: 'Your request has been submitted and is awaiting admin review.',
        color: 'bg-gray-100 text-gray-700 border-gray-300',
        step: 0
    },
    'pending': {
        label: 'Under Admin Review',
        message: 'Your request is being reviewed by the admin.',
        color: 'bg-yellow-100 text-yellow-800 border-yellow-300',
        step: 0
    },
    'rejected': {
        label: 'Request Rejected',
        message: 'Your request was rejected by admin. Please contact support for more information.',
        color: 'bg-red-100 text-red-700 border-red-300',
        step: -1
    },
    'approved': {
        label: 'Searching for Donor',
        message: 'Your request has been approved and sent to compatible donors.',
        color: 'bg-blue-100 text-blue-700 border-blue-300',
        step: 1
    },
    'donor_assigned': {
        label: 'Donor Assigned',
        message: 'A donor has accepted your request and will arrive soon.',
        color: 'bg-indigo-100 text-indigo-700 border-indigo-300',
        step: 2
    },
    'on_the_way': {
        label: 'Donor On the Way',
        message: 'The donor is on their way to the hospital.',
        color: 'bg-purple-100 text-purple-700 border-purple-300',
        step: 3
    },
    'reached': {
        label: 'Donor Reached Hospital',
        message: 'The donor has arrived at the hospital. Donation in progress.',
        color: 'bg-teal-100 text-teal-700 border-teal-300',
        step: 4
    },
    'completed': {
        label: 'Donation Completed',
        message: 'Donation completed successfully. Thank you for using BloodConnect!',
        color: 'bg-green-100 text-green-700 border-green-300',
        step: 5
    }
};

export function getStatusInfo(status) {
    return statusConfig[status] || statusConfig['submitted'];
}

export function getUrgencyBadge(urgency) {
    return urgency === "Emergency"
        ? "bg-red-600 text-white"
        : "bg-blue-600 text-white";
}

export function renderTimeline(currentStep, isRejected) {
    if (isRejected) {
        return `
      <div class="flex items-center justify-center py-3 px-4 bg-red-50 rounded-lg border border-red-200">
        <i data-lucide="x-circle" class="size-5 text-red-500 mr-2"></i>
        <span class="text-sm text-red-700 font-medium">Request was rejected</span>
      </div>
    `;
    }

    return `
    <div class="flex items-center justify-between py-3 px-2 bg-gray-50 rounded-lg">
      ${lifecycleSteps.map((step, index) => {
        const isCompleted = index < currentStep;
        const isCurrent = index === currentStep;
        const isFuture = index > currentStep;

        let stepClass = '';
        let iconClass = '';
        let lineClass = '';

        if (isCompleted) {
            stepClass = 'bg-green-500';
            iconClass = 'text-white';
            lineClass = 'bg-green-500';
        } else if (isCurrent) {
            stepClass = 'bg-blue-500 ring-4 ring-blue-200';
            iconClass = 'text-white';
            lineClass = 'bg-gray-300';
        } else {
            stepClass = 'bg-gray-200';
            iconClass = 'text-gray-400';
            lineClass = 'bg-gray-200';
        }

        return `
          <div class="flex items-center ${index < lifecycleSteps.length - 1 ? 'flex-1' : ''}">
            <div class="flex flex-col items-center">
              <div class="size-7 rounded-full ${stepClass} flex items-center justify-center" title="${step.label}">
                <i data-lucide="${isCompleted ? 'check' : step.icon}" class="size-3.5 ${iconClass}"></i>
              </div>
              <span class="text-[10px] mt-1 ${isCurrent ? 'text-blue-600 font-semibold' : isFuture ? 'text-gray-400' : 'text-green-600'} text-center max-w-12 leading-tight">${step.label}</span>
            </div>
            ${index < lifecycleSteps.length - 1 ? `<div class="flex-1 h-0.5 ${lineClass} mx-1 mt-[-16px]"></div>` : ''}
          </div>
        `;
    }).join('')}
    </div>
  `;
}

// ============================================
// REQUEST DETAILS PAGE
// ============================================

export function initRequestDetailsPage() {
    // Initialize donor details modal
    initModal('donorDetailsModal', 'viewDonorDetailsBtn', ['closeModalBtn', 'closeModalBtn2']);

    // Initialize icons
    if (window.lucide) {
        window.lucide.createIcons();
    }
}

// ============================================
// INITIALIZATION
// ============================================

document.addEventListener('DOMContentLoaded', () => {
    // Detect which page we're on and initialize accordingly
    const path = window.location.pathname;

    if (path.includes('request-details')) {
        initRequestDetailsPage();
    }

    // Always initialize icons
    if (window.lucide) {
        window.lucide.createIcons();
    }
});

// Export for use in other modules
export { lifecycleSteps, statusConfig };
