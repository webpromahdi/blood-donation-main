/**
 * Modal Component
 * Reusable modal open/close functionality
 */

/**
 * Opens a modal by ID
 * @param {string} modalId - The ID of the modal element
 */
export function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.style.overflow = 'hidden';
        // Re-initialize icons in modal
        if (window.lucide) {
            window.lucide.createIcons();
        }
    }
}

/**
 * Closes a modal by ID
 * @param {string} modalId - The ID of the modal element
 */
export function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.style.overflow = '';
    }
}

/**
 * Initialize modal with standard behaviors
 * @param {string} modalId - The ID of the modal element
 * @param {string} openBtnId - The ID of the button that opens the modal
 * @param {string[]} closeBtnIds - Array of IDs for close buttons
 */
export function initModal(modalId, openBtnId, closeBtnIds = []) {
    const modal = document.getElementById(modalId);
    const openBtn = document.getElementById(openBtnId);

    // Open button
    if (openBtn) {
        openBtn.addEventListener('click', () => openModal(modalId));
    }

    // Close buttons
    closeBtnIds.forEach(btnId => {
        const btn = document.getElementById(btnId);
        if (btn) {
            btn.addEventListener('click', () => closeModal(modalId));
        }
    });

    // Close on backdrop click
    if (modal) {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                closeModal(modalId);
            }
        });
    }

    // Close on Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modal && !modal.classList.contains('hidden')) {
            closeModal(modalId);
        }
    });
}

// Make functions available globally for inline calls
window.openModal = openModal;
window.closeModal = closeModal;
window.initModal = initModal;
