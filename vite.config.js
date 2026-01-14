import { defineConfig } from "vite";
import tailwindcss from "@tailwindcss/vite";

export default defineConfig({
  plugins: [tailwindcss()],
  root: ".",
  build: {
    outDir: "dist",
    rollupOptions: {
      input: {
        main: "index.html",
        portalLogin: "src/pages/portal-login.html",
        // Admin pages
        adminDashboard: "src/pages/admin/dashboard.html",
        adminDonors: "src/pages/admin/donors.html",
        adminHospitals: "src/pages/admin/hospitals.html",
        adminBloodGroups: "src/pages/admin/blood-groups.html",
        adminReports: "src/pages/admin/reports.html",
        adminAnnouncements: "src/pages/admin/announcements.html",
        adminChat: "src/pages/admin/chat.html",
        // Hospital pages
        hospitalDashboard: "src/pages/hospital/dashboard.html",
        hospitalRequest: "src/pages/hospital/request.html",
        hospitalDonors: "src/pages/hospital/donors.html",
        hospitalVoluntary: "src/pages/hospital/voluntary.html",
        hospitalAppointments: "src/pages/hospital/appointments.html",
        hospitalChat: "src/pages/hospital/chat.html",
        // Donor pages
        donorDashboard: "src/pages/donor/dashboard.html",
        donorHealth: "src/pages/donor/health.html",
        donorVoluntary: "src/pages/donor/voluntary.html",
        donorHistory: "src/pages/donor/history.html",
        donorCertificates: "src/pages/donor/certificates.html",
        donorChat: "src/pages/donor/chat.html",
        // Seeker pages
        seekerRequest: "src/pages/seeker/request.html",
        seekerTracking: "src/pages/seeker/tracking.html",
        seekerTrackResults: "src/pages/seeker/track-results.html",
        seekerRequestDetails: "src/pages/seeker/request-details.html",
        seekerChat: "src/pages/seeker/chat.html",
        // Guest pages
        guestHome: "src/pages/guest/home.html",
        guestWhyDonate: "src/pages/guest/why-donate.html",
        guestEligibility: "src/pages/guest/eligibility.html",
        guestFaq: "src/pages/guest/faq.html",
        guestTrackRequest: "src/pages/guest/track-request.html",
      },
    },
  },
});

