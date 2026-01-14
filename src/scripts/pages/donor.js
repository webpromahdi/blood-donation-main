/**
 * Donor Portal Scripts
 * Handles: dashboard, health form, history, certificates, voluntary
 */

import { showNotification } from '../components/notifications.js';

// ============================================
// HEALTH FORM
// ============================================

export function initHealthForm() {
    const form = document.querySelector('form');

    if (form) {
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            showNotification('Health information saved successfully!', 'success');
        });
    }
}

// ============================================
// DASHBOARD - Stats & Activity
// ============================================

export function updateDashboardStats(stats) {
    const elements = {
        totalDonations: document.getElementById('totalDonations'),
        nextEligible: document.getElementById('nextEligible'),
        livesImpacted: document.getElementById('livesImpacted'),
        certCount: document.getElementById('certCount')
    };

    Object.entries(elements).forEach(([key, el]) => {
        if (el && stats[key] !== undefined) {
            el.textContent = stats[key];
        }
    });
}

// ============================================
// DONATION HISTORY
// ============================================

export function renderDonationHistory(donations) {
    const container = document.getElementById('donationHistoryList');
    if (!container) return;

    container.innerHTML = donations.map(donation => `
    <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
      <div class="flex items-center gap-4">
        <div class="size-10 bg-red-100 rounded-full flex items-center justify-center">
          <i data-lucide="droplet" class="size-5 text-red-600"></i>
        </div>
        <div>
          <h4 class="font-medium text-gray-900">${donation.hospital}</h4>
          <p class="text-sm text-gray-600">${donation.date}</p>
        </div>
      </div>
      <span class="px-3 py-1 text-sm font-medium ${donation.status === 'Completed' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700'} rounded-full">
        ${donation.status}
      </span>
    </div>
  `).join('');

    if (window.lucide) {
        window.lucide.createIcons();
    }
}

// ============================================
// VOLUNTARY DONATION
// ============================================

export function initVoluntaryForm() {
    const form = document.getElementById('voluntaryForm');

    if (form) {
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            showNotification('Voluntary donation registered! We will contact you soon.', 'success');
        });
    }
}

// ============================================
// INITIALIZATION
// ============================================

document.addEventListener('DOMContentLoaded', () => {
    const path = window.location.pathname;

    if (path.includes('health')) {
        initHealthForm();
    } else if (path.includes('voluntary')) {
        initVoluntaryForm();
    }

    // Always initialize icons
    if (window.lucide) {
        window.lucide.createIcons();
    }
});
