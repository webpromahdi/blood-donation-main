import { createIcons, icons } from "lucide";

createIcons({ icons });

// Initialize all Lucide icons on page load
document.addEventListener("DOMContentLoaded", () => {
  createIcons({ icons });
});

// Expose lucide globally for dynamic icon updates
window.lucide = {
  createIcons: () => createIcons({ icons }),
};

// Re-initialize icons when content is dynamically added
window.initIcons = () => {
  createIcons({ icons });
};

// FAQ accordion functionality
window.toggleFaq = (button) => {
  const content = button.nextElementSibling;
  const icon = button.querySelector('[data-lucide="chevron-down"]');

  if (content.classList.contains("hidden")) {
    content.classList.remove("hidden");
    icon?.classList.add("rotate-180");
  } else {
    content.classList.add("hidden");
    icon?.classList.remove("rotate-180");
  }
};

// Mobile sidebar toggle
window.toggleSidebar = () => {
  const sidebar = document.getElementById("sidebar");
  sidebar?.classList.toggle("-translate-x-full");
};

// Track request form handler
window.handleTrackRequest = (event) => {
  event.preventDefault();
  const input = event.target.querySelector("input");
  const query = input?.value?.trim();
  if (query) {
    window.location.href = `/pages/guest/track-request.html?query=${encodeURIComponent(
      query
    )}`;
  }
};
