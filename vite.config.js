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
        portalLogin: "pages/portal-login.html",
        // Admin pages
        adminDashboard: "pages/admin/dashboard.html",
        adminDonors: "pages/admin/donors.html",
        adminHospitals: "pages/admin/hospitals.html",
        adminBloodGroups: "pages/admin/blood-groups.html",
        adminReports: "pages/admin/reports.html",
        adminAnnouncements: "pages/admin/announcements.html",
        adminChat: "pages/admin/chat.html",
        // Hospital pages
        hospitalDashboard: "pages/hospital/dashboard.html",
        hospitalRequest: "pages/hospital/request.html",
        hospitalDonors: "pages/hospital/donors.html",
        hospitalVoluntary: "pages/hospital/voluntary.html",
        hospitalAppointments: "pages/hospital/appointments.html",
        hospitalChat: "pages/hospital/chat.html",
        // Donor pages
        donorDashboard: "pages/donor/dashboard.html",
        donorHealth: "pages/donor/health.html",
        donorVoluntary: "pages/donor/voluntary.html",
        donorHistory: "pages/donor/history.html",
        donorCertificates: "pages/donor/certificates.html",
        donorChat: "pages/donor/chat.html",
        // Seeker pages
        seekerRequest: "pages/seeker/request.html",
        seekerTracking: "pages/seeker/tracking.html",
        seekerTrackResults: "pages/seeker/track-results.html",
        seekerRequestDetails: "pages/seeker/request-details.html",
        seekerChat: "pages/seeker/chat.html",
        // Guest pages
        guestHome: "pages/guest/home.html",
        guestWhyDonate: "pages/guest/why-donate.html",
        guestEligibility: "pages/guest/eligibility.html",
        guestFaq: "pages/guest/faq.html",
        guestTrackRequest: "pages/guest/track-request.html",
      },
    },
  },
});
