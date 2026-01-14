/**
 * Admin Portal Scripts
 * Handles: dashboard, donors, hospitals, reports, announcements
 */

import { initModal } from '../components/modal.js';
import { showNotification } from '../components/notifications.js';

// ============================================
// TABLE FILTERING & SORTING
// ============================================

export function initTableFilter(tableId, searchInputId) {
    const searchInput = document.getElementById(searchInputId);
    const table = document.getElementById(tableId);

    if (!searchInput || !table) return;

    searchInput.addEventListener('input', (e) => {
        const searchTerm = e.target.value.toLowerCase();
        const rows = table.querySelectorAll('tbody tr');

        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchTerm) ? '' : 'none';
        });
    });
}

export function initStatusFilter(tableId, filterSelectId) {
    const filterSelect = document.getElementById(filterSelectId);
    const table = document.getElementById(tableId);

    if (!filterSelect || !table) return;

    filterSelect.addEventListener('change', (e) => {
        const status = e.target.value.toLowerCase();
        const rows = table.querySelectorAll('tbody tr');

        rows.forEach(row => {
            if (status === 'all') {
                row.style.display = '';
            } else {
                const rowStatus = row.dataset.status?.toLowerCase() || '';
                row.style.display = rowStatus === status ? '' : 'none';
            }
        });
    });
}

// ============================================
// REQUEST MANAGEMENT
// ============================================

export function approveRequest(requestId) {
    showNotification(`Request ${requestId} approved successfully!`, 'success');
    // Update UI or reload
}

export function rejectRequest(requestId) {
    showNotification(`Request ${requestId} rejected.`, 'warning');
    // Update UI or reload
}

// ============================================
// DONOR MANAGEMENT
// ============================================

export function verifyDonor(donorId) {
    showNotification(`Donor ${donorId} verified successfully!`, 'success');
}

export function suspendDonor(donorId) {
    showNotification(`Donor ${donorId} has been suspended.`, 'warning');
}

// ============================================
// HOSPITAL MANAGEMENT
// ============================================

export function approveHospital(hospitalId) {
    showNotification(`Hospital ${hospitalId} approved!`, 'success');
}

// ============================================
// REPORTS
// ============================================

export function exportReport(format) {
    showNotification(`Exporting report as ${format.toUpperCase()}...`, 'info');
    // Implement export logic
}

// ============================================
// INITIALIZATION
// ============================================

document.addEventListener('DOMContentLoaded', () => {
    const path = window.location.pathname;

    // Initialize common table filters if elements exist
    initTableFilter('requestsTable', 'requestSearch');
    initTableFilter('donorsTable', 'donorSearch');
    initTableFilter('hospitalsTable', 'hospitalSearch');

    // Always initialize icons
    if (window.lucide) {
        window.lucide.createIcons();
    }
});

// Make functions available globally for onclick handlers
window.approveRequest = approveRequest;
window.rejectRequest = rejectRequest;
window.verifyDonor = verifyDonor;
window.suspendDonor = suspendDonor;
window.approveHospital = approveHospital;
window.exportReport = exportReport;
