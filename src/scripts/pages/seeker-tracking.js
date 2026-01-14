/**
 * Seeker Tracking Page Script
 * Handles the tracking page rendering and status management
 */

import { showNotification } from '../components/notifications.js';

// Lifecycle steps (in order)
export const lifecycleSteps = [
    { key: 'submitted', label: 'Submitted', icon: 'send' },
    { key: 'approved', label: 'Approved', icon: 'check-circle' },
    { key: 'donor_assigned', label: 'Donor Assigned', icon: 'user-check' },
    { key: 'on_the_way', label: 'On the Way', icon: 'navigation' },
    { key: 'reached', label: 'Reached', icon: 'map-pin' },
    { key: 'completed', label: 'Completed', icon: 'heart' }
];

// Status configuration
export const statusConfig = {
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

// Sample requests data
let requests = [
    {
        id: "REQ001",
        patientName: "John Doe",
        bloodType: "O+",
        quantity: 2,
        hospital: "City Hospital",
        urgency: "Emergency",
        status: "on_the_way",
        requiredBy: "2024-11-25",
        submittedOn: "2024-11-24 10:30 AM",
    },
    {
        id: "REQ002",
        patientName: "Jane Smith",
        bloodType: "A-",
        quantity: 1,
        hospital: "General Hospital",
        urgency: "Normal",
        status: "approved",
        requiredBy: "2024-11-28",
        submittedOn: "2024-11-25 08:15 AM",
    },
    {
        id: "REQ003",
        patientName: "Bob Johnson",
        bloodType: "B+",
        quantity: 3,
        hospital: "Medical Center",
        urgency: "Emergency",
        status: "donor_assigned",
        requiredBy: "2024-11-24",
        submittedOn: "2024-11-23 02:45 PM",
    },
    {
        id: "REQ004",
        patientName: "Alice Williams",
        bloodType: "AB+",
        quantity: 1,
        hospital: "Community Hospital",
        urgency: "Normal",
        status: "completed",
        requiredBy: "2024-11-22",
        submittedOn: "2024-11-20 11:20 AM",
    },
    {
        id: "REQ005",
        patientName: "Charlie Brown",
        bloodType: "O-",
        quantity: 2,
        hospital: "Regional Hospital",
        urgency: "Normal",
        status: "rejected",
        requiredBy: "2024-11-26",
        submittedOn: "2024-11-24 03:00 PM",
    },
];

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

function updateStats() {
    const total = requests.length;
    const active = requests.filter(
        (r) => r.status !== "completed" && r.status !== "rejected"
    ).length;
    const completed = requests.filter(
        (r) => r.status === "completed"
    ).length;
    const rejected = requests.filter(
        (r) => r.status === "rejected"
    ).length;

    const totalEl = document.getElementById("totalCount");
    const activeEl = document.getElementById("activeCount");
    const completedEl = document.getElementById("completedCount");
    const rejectedEl = document.getElementById("donorCount");

    if (totalEl) totalEl.textContent = total;
    if (activeEl) activeEl.textContent = active;
    if (completedEl) completedEl.textContent = completed;
    if (rejectedEl) rejectedEl.textContent = rejected;
}

function renderRequests() {
    const requestsContainer = document.querySelector(
        ".grid.grid-cols-1.md\\:grid-cols-2.gap-6"
    );
    if (!requestsContainer) return;

    // Sort: active requests first (by most recent), then completed/rejected
    const sortedRequests = [...requests].sort((a, b) => {
        const aActive = a.status !== 'completed' && a.status !== 'rejected';
        const bActive = b.status !== 'completed' && b.status !== 'rejected';
        if (aActive && !bActive) return -1;
        if (!aActive && bActive) return 1;
        return new Date(b.submittedOn) - new Date(a.submittedOn);
    });

    requestsContainer.innerHTML = sortedRequests
        .map((req) => {
            const statusInfo = getStatusInfo(req.status);
            const isRejected = req.status === 'rejected';
            const isCompleted = req.status === 'completed';

            return `
        <div class="p-6 bg-white border ${isRejected ? 'border-red-200' : isCompleted ? 'border-green-200' : 'border-gray-200'} rounded-xl hover:shadow-lg transition-shadow" data-id="${req.id}">
          <div class="flex items-start justify-between mb-4">
            <div>
              <div class="flex items-center gap-2 mb-2">
                <h3 class="font-semibold text-gray-900">${req.id}</h3>
                <span class="px-2 py-1 text-xs font-medium ${getUrgencyBadge(req.urgency)} rounded-full">${req.urgency}</span>
              </div>
              <p class="text-sm text-gray-600">Patient: ${req.patientName}</p>
            </div>
            <div class="flex items-center gap-2">
              <i data-lucide="droplet" class="size-5 text-red-600"></i>
              <span class="font-bold text-red-600">${req.bloodType}</span>
            </div>
          </div>

          <!-- Request Details -->
          <div class="grid grid-cols-2 gap-2 mb-4 text-sm">
            <div class="flex justify-between">
              <span class="text-gray-500">Quantity:</span>
              <span class="text-gray-900 font-medium">${req.quantity} Unit${req.quantity !== 1 ? "s" : ""}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-500">Hospital:</span>
              <span class="text-gray-900 font-medium">${req.hospital}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-500">Submitted:</span>
              <span class="text-gray-900">${req.submittedOn.split(' ')[0]}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-500">Required By:</span>
              <span class="text-gray-900 font-medium">${req.requiredBy}</span>
            </div>
          </div>

          <!-- Status Badge & Message -->
          <div class="mb-4 p-3 rounded-lg ${statusInfo.color} border">
            <div class="flex items-center gap-2 mb-1">
              <span class="text-sm font-semibold">${statusInfo.label}</span>
            </div>
            <p class="text-xs opacity-80">${statusInfo.message}</p>
          </div>

          <!-- Timeline -->
          ${renderTimeline(statusInfo.step, isRejected)}

          <!-- Actions -->
          <div class="flex items-center justify-end pt-4 mt-4 border-t border-gray-100">
            <a href="/src/pages/seeker/request-details.html?id=${req.id}" class="px-4 py-2 text-sm border border-gray-300 hover:bg-gray-100 rounded-lg flex items-center gap-2">
              <i data-lucide="eye" class="size-4"></i>
              View Details
            </a>
          </div>
        </div>
      `;
        })
        .join("");

    if (window.lucide) {
        window.lucide.createIcons();
    }
    updateStats();
}

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', () => {
    renderRequests();
});
