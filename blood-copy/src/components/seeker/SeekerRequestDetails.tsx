import { useParams, useNavigate } from 'react-router-dom';
import DashboardLayout from '../layout/DashboardLayout';
import { Send, MessageSquare, ClipboardList, ArrowLeft, CheckCircle, Clock, UserCheck, Calendar, Package, AlertCircle, Droplet, MapPin, Phone, Mail } from 'lucide-react';
import { Card } from '../ui/card';
import { Badge } from '../ui/badge';
import { Button } from '../ui/button';

const sidebarItems = [
  { path: '/seeker/request', label: 'Submit Blood Request', icon: Send },
  { path: '/seeker/tracking', label: 'Track Request', icon: ClipboardList },
  { path: '/seeker/chat', label: 'Chat with Donor', icon: MessageSquare },
];

// Mock data - In a real app, this would come from an API based on the ID
const requestsData: { [key: string]: any } = {
  'REQ001': {
    id: 'REQ001',
    bloodType: 'O+',
    quantity: 2,
    hospital: 'City Hospital',
    hospitalAddress: '123 Main Street, Downtown',
    hospitalPhone: '555-1234',
    urgency: 'Emergency',
    status: 'Donor Matched',
    submittedOn: '2024-11-24 10:30 AM',
    patientName: 'John Doe',
    patientAge: 45,
    contactPhone: '555-9876',
    contactEmail: 'john.doe@example.com',
    requiredBy: '2024-11-26',
    medicalReason: 'Emergency surgery - requires immediate blood transfusion',
    respondedDonors: [
      { id: 1, name: 'Michael Johnson', bloodType: 'O+', location: 'Downtown', phone: '555-0101', status: 'Confirmed', lastDonation: '3 months ago' },
      { id: 2, name: 'Sarah Williams', bloodType: 'O+', location: 'Westside', phone: '555-0102', status: 'Available', lastDonation: '5 months ago' },
      { id: 3, name: 'David Brown', bloodType: 'O+', location: 'Uptown', phone: '555-0103', status: 'Available', lastDonation: '2 months ago' },
      { id: 4, name: 'Emily Davis', bloodType: 'O+', location: 'Central', phone: '555-0104', status: 'Pending', lastDonation: '6 months ago' },
      { id: 5, name: 'Robert Miller', bloodType: 'O+', location: 'Downtown', phone: '555-0105', status: 'Confirmed', lastDonation: '4 months ago' },
    ],
    timeline: [
      { step: 1, label: 'Request Submitted', description: 'Your blood request was successfully submitted', date: '2024-11-24 10:30 AM', completed: true },
      { step: 2, label: 'Request Verified', description: 'Admin verified your request and details', date: '2024-11-24 10:35 AM', completed: true },
      { step: 3, label: 'Matching Donors', description: 'System is notifying compatible donors', date: '2024-11-24 11:00 AM', completed: true },
      { step: 4, label: 'Donor Matched', description: 'Compatible donors have confirmed availability', date: '2024-11-24 02:15 PM', completed: true },
      { step: 5, label: 'Donation Scheduled', description: 'Donation appointment scheduled', date: 'Pending', completed: false },
      { step: 6, label: 'Completed', description: 'Blood donation completed successfully', date: 'Pending', completed: false },
    ],
    currentStep: 4,
    appointment: {
      scheduled: true,
      date: '2024-11-26',
      time: '10:00 AM',
      donor: 'Michael Johnson',
      location: 'City Hospital - Blood Donation Center',
    }
  },
  'REQ002': {
    id: 'REQ002',
    bloodType: 'A-',
    quantity: 1,
    hospital: 'General Hospital',
    hospitalAddress: '456 Oak Avenue, Midtown',
    hospitalPhone: '555-5678',
    urgency: 'Normal',
    status: 'Searching for Donor',
    submittedOn: '2024-11-25 08:15 AM',
    patientName: 'Jane Smith',
    patientAge: 32,
    contactPhone: '555-8765',
    contactEmail: 'jane.smith@example.com',
    requiredBy: '2024-11-30',
    medicalReason: 'Scheduled surgery preparation',
    respondedDonors: [
      { id: 1, name: 'Lisa Anderson', bloodType: 'A-', location: 'Midtown', phone: '555-0201', status: 'Pending', lastDonation: '4 months ago' },
      { id: 2, name: 'James Wilson', bloodType: 'A-', location: 'Eastside', phone: '555-0202', status: 'Pending', lastDonation: '7 months ago' },
    ],
    timeline: [
      { step: 1, label: 'Request Submitted', description: 'Your blood request was successfully submitted', date: '2024-11-25 08:15 AM', completed: true },
      { step: 2, label: 'Request Verified', description: 'Admin verified your request and details', date: '2024-11-25 08:20 AM', completed: true },
      { step: 3, label: 'Matching Donors', description: 'System is notifying compatible donors', date: '2024-11-25 09:00 AM', completed: true },
      { step: 4, label: 'Donor Matched', description: 'Waiting for donor confirmation', date: 'Pending', completed: false },
      { step: 5, label: 'Donation Scheduled', description: 'Donation appointment to be scheduled', date: 'Pending', completed: false },
      { step: 6, label: 'Completed', description: 'Blood donation to be completed', date: 'Pending', completed: false },
    ],
    currentStep: 3,
    appointment: null
  },
  'REQ003': {
    id: 'REQ003',
    bloodType: 'B+',
    quantity: 3,
    hospital: 'Medical Center',
    hospitalAddress: '789 Pine Road, Northside',
    hospitalPhone: '555-9012',
    urgency: 'Emergency',
    status: 'Donation Scheduled',
    submittedOn: '2024-11-23 02:45 PM',
    patientName: 'Bob Johnson',
    patientAge: 58,
    contactPhone: '555-7654',
    contactEmail: 'bob.johnson@example.com',
    requiredBy: '2024-11-25',
    medicalReason: 'Critical blood loss - urgent transfusion required',
    respondedDonors: [
      { id: 1, name: 'Tom Harris', bloodType: 'B+', location: 'Northside', phone: '555-0301', status: 'Confirmed', lastDonation: '5 months ago' },
      { id: 2, name: 'Maria Garcia', bloodType: 'B+', location: 'Downtown', phone: '555-0302', status: 'Confirmed', lastDonation: '3 months ago' },
      { id: 3, name: 'Chris Lee', bloodType: 'B+', location: 'Westside', phone: '555-0303', status: 'Confirmed', lastDonation: '6 months ago' },
    ],
    timeline: [
      { step: 1, label: 'Request Submitted', description: 'Your blood request was successfully submitted', date: '2024-11-23 02:45 PM', completed: true },
      { step: 2, label: 'Request Verified', description: 'Admin verified your request and details', date: '2024-11-23 02:50 PM', completed: true },
      { step: 3, label: 'Matching Donors', description: 'System notified compatible donors', date: '2024-11-23 03:00 PM', completed: true },
      { step: 4, label: 'Donor Matched', description: 'Compatible donors confirmed availability', date: '2024-11-23 05:30 PM', completed: true },
      { step: 5, label: 'Donation Scheduled', description: 'Donation appointments scheduled', date: '2024-11-24 09:00 AM', completed: true },
      { step: 6, label: 'Completed', description: 'Pending donation completion', date: 'Pending', completed: false },
    ],
    currentStep: 5,
    appointment: {
      scheduled: true,
      date: '2024-11-25',
      time: '08:00 AM',
      donor: 'Tom Harris, Maria Garcia, Chris Lee',
      location: 'Medical Center - Emergency Blood Bank',
    }
  },
  'REQ004': {
    id: 'REQ004',
    bloodType: 'AB+',
    quantity: 1,
    hospital: 'Community Hospital',
    hospitalAddress: '321 Elm Street, Southside',
    hospitalPhone: '555-3456',
    urgency: 'Normal',
    status: 'Completed',
    submittedOn: '2024-11-20 11:20 AM',
    patientName: 'Alice Williams',
    patientAge: 28,
    contactPhone: '555-6543',
    contactEmail: 'alice.williams@example.com',
    requiredBy: '2024-11-22',
    medicalReason: 'Post-surgery blood requirement',
    respondedDonors: [
      { id: 1, name: 'Kevin Martinez', bloodType: 'AB+', location: 'Southside', phone: '555-0401', status: 'Completed', lastDonation: '8 months ago' },
    ],
    timeline: [
      { step: 1, label: 'Request Submitted', description: 'Your blood request was successfully submitted', date: '2024-11-20 11:20 AM', completed: true },
      { step: 2, label: 'Request Verified', description: 'Admin verified your request and details', date: '2024-11-20 11:25 AM', completed: true },
      { step: 3, label: 'Matching Donors', description: 'System notified compatible donors', date: '2024-11-20 12:00 PM', completed: true },
      { step: 4, label: 'Donor Matched', description: 'Compatible donor confirmed availability', date: '2024-11-20 03:15 PM', completed: true },
      { step: 5, label: 'Donation Scheduled', description: 'Donation appointment scheduled', date: '2024-11-21 10:00 AM', completed: true },
      { step: 6, label: 'Completed', description: 'Blood donation completed successfully', date: '2024-11-22 02:30 PM', completed: true },
    ],
    currentStep: 6,
    appointment: {
      scheduled: true,
      date: '2024-11-22',
      time: '02:00 PM',
      donor: 'Kevin Martinez',
      location: 'Community Hospital - Donation Unit',
      completed: true
    }
  }
};

export default function SeekerRequestDetails() {
  const { id } = useParams();
  const navigate = useNavigate();

  const request = requestsData[id || ''] || requestsData['REQ001'];

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'Completed':
        return 'bg-green-100 text-green-800 border-green-200';
      case 'Donor Matched':
      case 'Donation Scheduled':
        return 'bg-blue-100 text-blue-800 border-blue-200';
      case 'Searching for Donor':
        return 'bg-yellow-100 text-yellow-800 border-yellow-200';
      default:
        return 'bg-gray-100 text-gray-800 border-gray-200';
    }
  };

  const getDonorStatusColor = (status: string) => {
    switch (status) {
      case 'Confirmed':
      case 'Completed':
        return 'bg-green-100 text-green-800 border-green-200';
      case 'Available':
        return 'bg-blue-100 text-blue-800 border-blue-200';
      case 'Pending':
        return 'bg-yellow-100 text-yellow-800 border-yellow-200';
      default:
        return 'bg-gray-100 text-gray-800 border-gray-200';
    }
  };

  return (
    <DashboardLayout
      sidebarItems={sidebarItems}
      sidebarTitle="Seeker Portal"
      userName="Guest User"
      userRole="Blood Seeker"
      notifications={0}
    >
      <div className="space-y-8">
        {/* Header */}
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-4">
            <Button
              variant="outline"
              size="sm"
              onClick={() => navigate('/seeker/tracking')}
            >
              <ArrowLeft className="size-4 mr-2" />
              Back to Requests
            </Button>
            <div>
              <h1 className="text-gray-900 mb-2">Request Details</h1>
              <p className="text-gray-600">Request ID: {request.id}</p>
            </div>
          </div>
          <Badge variant="outline" className={getStatusColor(request.status)}>
            {request.status}
          </Badge>
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
          {/* Main Content */}
          <div className="lg:col-span-2 space-y-6">
            {/* Request Information */}
            <Card className="p-6">
              <div className="flex items-start justify-between mb-6">
                <div>
                  <h2 className="text-gray-900 mb-2">Request Information</h2>
                </div>
                <Badge 
                  className={
                    request.urgency === 'Emergency' 
                      ? 'bg-red-600 hover:bg-red-700' 
                      : 'bg-blue-600 hover:bg-blue-700'
                  }
                >
                  <AlertCircle className="size-3 mr-1" />
                  {request.urgency}
                </Badge>
              </div>

              <div className="grid grid-cols-2 gap-6">
                <div>
                  <p className="text-gray-600 mb-1">Patient Name</p>
                  <p className="text-gray-900">{request.patientName}</p>
                </div>
                <div>
                  <p className="text-gray-600 mb-1">Age</p>
                  <p className="text-gray-900">{request.patientAge} years</p>
                </div>
                <div>
                  <p className="text-gray-600 mb-1">Blood Type</p>
                  <Badge variant="outline" className="border-red-600 text-red-600">
                    <Droplet className="size-3 mr-1" />
                    {request.bloodType}
                  </Badge>
                </div>
                <div>
                  <p className="text-gray-600 mb-1">Quantity Needed</p>
                  <p className="text-gray-900">{request.quantity} Units</p>
                </div>
                <div>
                  <p className="text-gray-600 mb-1">Hospital</p>
                  <p className="text-gray-900">{request.hospital}</p>
                </div>
                <div>
                  <p className="text-gray-600 mb-1">Required By</p>
                  <p className="text-gray-900">{request.requiredBy}</p>
                </div>
                <div className="col-span-2">
                  <p className="text-gray-600 mb-1">Medical Reason</p>
                  <p className="text-gray-900">{request.medicalReason}</p>
                </div>
              </div>
            </Card>

            {/* Request Timeline */}
            <Card className="p-6">
              <h2 className="text-gray-900 mb-6">Request Timeline</h2>

              <div className="space-y-6">
                {request.timeline.map((step: any) => {
                  const StepIcon = step.completed ? CheckCircle : Clock;
                  return (
                    <div key={step.step} className="flex gap-4">
                      <div className="flex flex-col items-center">
                        <div 
                          className={`size-10 rounded-full flex items-center justify-center ${
                            step.completed 
                              ? step.step === request.currentStep
                                ? 'bg-blue-600 ring-4 ring-blue-100'
                                : 'bg-green-600'
                              : 'bg-gray-200'
                          }`}
                        >
                          <StepIcon 
                            className={`size-5 ${
                              step.completed ? 'text-white' : 'text-gray-400'
                            }`} 
                          />
                        </div>
                        {step.step < request.timeline.length && (
                          <div className={`w-0.5 h-12 mt-2 ${
                            step.completed ? 'bg-green-200' : 'bg-gray-200'
                          }`}></div>
                        )}
                      </div>
                      <div className="flex-1 pb-6">
                        <h3 className={`mb-1 ${step.completed ? 'text-gray-900' : 'text-gray-400'}`}>
                          {step.label}
                        </h3>
                        <p className={step.completed ? 'text-gray-600' : 'text-gray-400'}>
                          {step.description}
                        </p>
                        <p className="text-gray-500 mt-1">{step.date}</p>
                      </div>
                    </div>
                  );
                })}
              </div>
            </Card>

            {/* Responded Donors */}
            <Card className="p-6">
              <div className="flex items-center justify-between mb-6">
                <h2 className="text-gray-900">Donors Who Responded ({request.respondedDonors.length})</h2>
              </div>

              <div className="space-y-4">
                {request.respondedDonors.map((donor: any) => (
                  <div key={donor.id} className="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:border-red-300 transition-colors">
                    <div className="flex items-center gap-4">
                      <div className="size-12 bg-red-100 rounded-full flex items-center justify-center">
                        <Droplet className="size-6 text-red-600 fill-red-600" />
                      </div>
                      <div>
                        <h3 className="text-gray-900">{donor.name}</h3>
                        <div className="flex items-center gap-3 text-gray-600 mt-1">
                          <div className="flex items-center gap-1">
                            <Droplet className="size-3" />
                            <span className="text-sm">{donor.bloodType}</span>
                          </div>
                          <div className="flex items-center gap-1">
                            <MapPin className="size-3" />
                            <span className="text-sm">{donor.location}</span>
                          </div>
                          <div className="flex items-center gap-1">
                            <Phone className="size-3" />
                            <span className="text-sm">{donor.phone}</span>
                          </div>
                        </div>
                        <div className="mt-1">
                          <Badge variant="outline" className={getDonorStatusColor(donor.status)}>
                            {donor.status}
                          </Badge>
                        </div>
                      </div>
                    </div>
                    <Button 
                      size="sm" 
                      className="bg-red-600 hover:bg-red-700"
                      onClick={() => navigate('/seeker/chat')}
                    >
                      <MessageSquare className="size-4 mr-1" />
                      Chat
                    </Button>
                  </div>
                ))}
              </div>
            </Card>
          </div>

          {/* Sidebar */}
          <div className="space-y-6">
            {/* Contact Information */}
            <Card className="p-6">
              <h3 className="text-gray-900 mb-4">Contact Information</h3>
              <div className="space-y-4">
                <div className="flex items-start gap-3">
                  <Phone className="size-5 text-gray-600 mt-0.5" />
                  <div>
                    <p className="text-gray-600">Phone</p>
                    <p className="text-gray-900">{request.contactPhone}</p>
                  </div>
                </div>
                <div className="flex items-start gap-3">
                  <Mail className="size-5 text-gray-600 mt-0.5" />
                  <div>
                    <p className="text-gray-600">Email</p>
                    <p className="text-gray-900 break-all">{request.contactEmail}</p>
                  </div>
                </div>
              </div>
            </Card>

            {/* Hospital Information */}
            <Card className="p-6">
              <h3 className="text-gray-900 mb-4">Hospital Details</h3>
              <div className="space-y-4">
                <div>
                  <p className="text-gray-600 mb-1">Hospital Name</p>
                  <p className="text-gray-900">{request.hospital}</p>
                </div>
                <div>
                  <p className="text-gray-600 mb-1">Address</p>
                  <p className="text-gray-900">{request.hospitalAddress}</p>
                </div>
                <div>
                  <p className="text-gray-600 mb-1">Phone</p>
                  <p className="text-gray-900">{request.hospitalPhone}</p>
                </div>
              </div>
            </Card>

            {/* Appointment Information */}
            {request.appointment && (
              <Card className={`p-6 border-l-4 ${
                request.appointment.completed 
                  ? 'border-l-green-600 bg-green-50' 
                  : 'border-l-blue-600 bg-blue-50'
              }`}>
                <div className="flex items-start gap-3">
                  <Calendar className={`size-6 flex-shrink-0 mt-1 ${
                    request.appointment.completed ? 'text-green-600' : 'text-blue-600'
                  }`} />
                  <div>
                    <h3 className="text-gray-900 mb-2">
                      {request.appointment.completed ? 'Completed Appointment' : 'Scheduled Appointment'}
                    </h3>
                    <div className="space-y-2 text-gray-700">
                      <p><strong>Date:</strong> {request.appointment.date}</p>
                      <p><strong>Time:</strong> {request.appointment.time}</p>
                      <p><strong>Donor:</strong> {request.appointment.donor}</p>
                      <p><strong>Location:</strong> {request.appointment.location}</p>
                    </div>
                    {request.appointment.completed && (
                      <Badge className="bg-green-600 hover:bg-green-700 mt-3">
                        <CheckCircle className="size-3 mr-1" />
                        Completed
                      </Badge>
                    )}
                  </div>
                </div>
              </Card>
            )}

            {/* Quick Actions */}
            <Card className="p-6">
              <h3 className="text-gray-900 mb-4">Quick Actions</h3>
              <div className="space-y-3">
                <Button 
                  className="w-full bg-red-600 hover:bg-red-700"
                  onClick={() => navigate('/seeker/chat')}
                >
                  <MessageSquare className="size-4 mr-2" />
                  Chat with Donors
                </Button>
                <Button 
                  variant="outline"
                  className="w-full"
                  onClick={() => navigate('/seeker/request')}
                >
                  <Send className="size-4 mr-2" />
                  Create New Request
                </Button>
              </div>
            </Card>

            {/* Help Section */}
            <Card className="p-6 bg-gray-50">
              <div className="flex items-start gap-3">
                <AlertCircle className="size-6 text-gray-600 flex-shrink-0 mt-1" />
                <div>
                  <h3 className="text-gray-900 mb-2">Need Help?</h3>
                  <p className="text-gray-600 mb-4">
                    Contact our support team if you need assistance with your request
                  </p>
                  <p className="text-red-600">Emergency: 1-800-BLOOD-NOW</p>
                </div>
              </div>
            </Card>
          </div>
        </div>
      </div>
    </DashboardLayout>
  );
}
