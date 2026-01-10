import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom';
import { useState } from 'react';

// Admin Pages
import AdminDashboard from './components/admin/AdminDashboard';
import DonorApproval from './components/admin/DonorApproval';
import HospitalApproval from './components/admin/HospitalApproval';
import BloodGroupManagement from './components/admin/BloodGroupManagement';
import AdminReports from './components/admin/AdminReports';
import AdminAnnouncements from './components/admin/AdminAnnouncements';
import AdminChat from './components/admin/AdminChat';

// Hospital Pages
import HospitalDashboard from './components/hospital/HospitalDashboard';
import RequestBlood from './components/hospital/RequestBlood';
import HospitalDonorList from './components/hospital/HospitalDonorList';
import HospitalVoluntaryRequests from './components/hospital/HospitalVoluntaryRequests';
import HospitalAppointments from './components/hospital/HospitalAppointments';
import HospitalChat from './components/hospital/HospitalChat';

// Donor Pages
import DonorDashboard from './components/donor/DonorDashboard';
import DonorHealthInfo from './components/donor/DonorHealthInfo';
import DonorVoluntaryDonation from './components/donor/DonorVoluntaryDonation';
import DonorHistory from './components/donor/DonorHistory';
import DonorCertificates from './components/donor/DonorCertificates';
import DonorChat from './components/donor/DonorChat';

// Seeker Pages
import SeekerRequest from './components/seeker/SeekerRequest';
import SeekerTracking from './components/seeker/SeekerTracking';
import SeekerTrackResults from './components/seeker/SeekerTrackResults';
import SeekerRequestDetails from './components/seeker/SeekerRequestDetails';
import SeekerChat from './components/seeker/SeekerChat';

// Guest Pages
import GuestHome from './components/guest/GuestHome';
import WhyDonate from './components/guest/WhyDonate';
import EligibilityRules from './components/guest/EligibilityRules';
import FAQ from './components/guest/FAQ';
import GuestTrackRequest from './components/guest/GuestTrackRequest';

// Auth/Selection Page
import UserSelection from './components/UserSelection';
import PortalLogin from './components/PortalLogin';
import Login from './components/auth/Login';
import Register from './components/auth/Register';

export default function App() {
  return (
    <Router>
      <Routes>
        {/* User Selection / Landing */}
        <Route path="/" element={<UserSelection />} />
        <Route path="/portal-login" element={<PortalLogin />} />
        
        {/* Auth Routes */}
        <Route path="/auth/login" element={<Login />} />
        <Route path="/auth/register" element={<Register />} />
        
        {/* Admin Routes */}
        <Route path="/admin/dashboard" element={<AdminDashboard />} />
        <Route path="/admin/donors" element={<DonorApproval />} />
        <Route path="/admin/hospitals" element={<HospitalApproval />} />
        <Route path="/admin/blood-groups" element={<BloodGroupManagement />} />
        <Route path="/admin/reports" element={<AdminReports />} />
        <Route path="/admin/announcements" element={<AdminAnnouncements />} />
        <Route path="/admin/chat" element={<AdminChat />} />
        
        {/* Hospital Routes */}
        <Route path="/hospital/dashboard" element={<HospitalDashboard />} />
        <Route path="/hospital/request" element={<RequestBlood />} />
        <Route path="/hospital/donors" element={<HospitalDonorList />} />
        <Route path="/hospital/voluntary" element={<HospitalVoluntaryRequests />} />
        <Route path="/hospital/appointments" element={<HospitalAppointments />} />
        <Route path="/hospital/chat" element={<HospitalChat />} />
        
        {/* Donor Routes */}
        <Route path="/donor/dashboard" element={<DonorDashboard />} />
        <Route path="/donor/health" element={<DonorHealthInfo />} />
        <Route path="/donor/voluntary" element={<DonorVoluntaryDonation />} />
        <Route path="/donor/history" element={<DonorHistory />} />
        <Route path="/donor/certificates" element={<DonorCertificates />} />
        <Route path="/donor/chat" element={<DonorChat />} />
        
        {/* Seeker Routes */}
        <Route path="/seeker/request" element={<SeekerRequest />} />
        <Route path="/seeker/tracking" element={<SeekerTracking />} />
        <Route path="/seeker/track-results" element={<SeekerTrackResults />} />
        <Route path="/seeker/request-details/:id" element={<SeekerRequestDetails />} />
        <Route path="/seeker/chat" element={<SeekerChat />} />
        
        {/* Guest Routes */}
        <Route path="/guest/home" element={<GuestHome />} />
        <Route path="/guest/why-donate" element={<WhyDonate />} />
        <Route path="/guest/eligibility" element={<EligibilityRules />} />
        <Route path="/guest/faq" element={<FAQ />} />
        <Route path="/guest/track-request" element={<GuestTrackRequest />} />
      </Routes>
    </Router>
  );
}