import { useNavigate, useSearchParams } from 'react-router-dom';
import { Heart, ArrowLeft, CheckCircle, Clock, AlertCircle, Droplet, Calendar, MapPin } from 'lucide-react';
import { Card } from '../ui/card';
import { Badge } from '../ui/badge';
import { Button } from '../ui/button';

// Mock data - In a real app, this would be fetched from API based on phone/request ID
const mockRequests: { [key: string]: any } = {
  'REQ001': {
    id: 'REQ001',
    bloodType: 'O+',
    quantity: 2,
    urgency: 'Emergency',
    status: 'Donor Matched',
    submittedOn: '2024-11-24 10:30 AM',
    requiredBy: '2024-11-26',
    location: 'Downtown Area',
    timeline: [
      { step: 1, label: 'Request Submitted', description: 'Blood request successfully submitted', date: '2024-11-24 10:30 AM', completed: true },
      { step: 2, label: 'Under Review', description: 'Request is being verified by admin', date: '2024-11-24 10:35 AM', completed: true },
      { step: 3, label: 'Matching Donors', description: 'Searching for compatible donors', date: '2024-11-24 11:00 AM', completed: true },
      { step: 4, label: 'Donor Found', description: 'Compatible donors have been identified', date: '2024-11-24 02:15 PM', completed: true },
      { step: 5, label: 'Scheduled', description: 'Donation appointment scheduled', date: 'Pending', completed: false },
      { step: 6, label: 'Completed', description: 'Blood donation completed', date: 'Pending', completed: false },
    ],
    currentStep: 4,
  },
  '555-1234': {
    id: 'REQ002',
    bloodType: 'A-',
    quantity: 1,
    urgency: 'Normal',
    status: 'Pending Review',
    submittedOn: '2024-11-25 08:15 AM',
    requiredBy: '2024-11-30',
    location: 'Midtown Area',
    timeline: [
      { step: 1, label: 'Request Submitted', description: 'Blood request successfully submitted', date: '2024-11-25 08:15 AM', completed: true },
      { step: 2, label: 'Under Review', description: 'Request is being verified by admin', date: 'Pending', completed: false },
      { step: 3, label: 'Matching Donors', description: 'Searching for compatible donors', date: 'Pending', completed: false },
      { step: 4, label: 'Donor Found', description: 'Compatible donors to be identified', date: 'Pending', completed: false },
      { step: 5, label: 'Scheduled', description: 'Donation appointment to be scheduled', date: 'Pending', completed: false },
      { step: 6, label: 'Completed', description: 'Blood donation to be completed', date: 'Pending', completed: false },
    ],
    currentStep: 1,
  },
  'REQ003': {
    id: 'REQ003',
    bloodType: 'B+',
    quantity: 3,
    urgency: 'Emergency',
    status: 'Scheduled',
    submittedOn: '2024-11-23 02:45 PM',
    requiredBy: '2024-11-25',
    location: 'Northside Area',
    timeline: [
      { step: 1, label: 'Request Submitted', description: 'Blood request successfully submitted', date: '2024-11-23 02:45 PM', completed: true },
      { step: 2, label: 'Under Review', description: 'Request verified by admin', date: '2024-11-23 02:50 PM', completed: true },
      { step: 3, label: 'Matching Donors', description: 'Compatible donors searched', date: '2024-11-23 03:00 PM', completed: true },
      { step: 4, label: 'Donor Found', description: 'Compatible donors identified', date: '2024-11-23 05:30 PM', completed: true },
      { step: 5, label: 'Scheduled', description: 'Donation appointment scheduled', date: '2024-11-24 09:00 AM', completed: true },
      { step: 6, label: 'Completed', description: 'Pending donation completion', date: 'Pending', completed: false },
    ],
    currentStep: 5,
    appointmentDate: '2024-11-25',
    appointmentTime: '08:00 AM',
  },
  '555-5678': {
    id: 'REQ004',
    bloodType: 'AB+',
    quantity: 1,
    urgency: 'Normal',
    status: 'Completed',
    submittedOn: '2024-11-20 11:20 AM',
    requiredBy: '2024-11-22',
    location: 'Southside Area',
    timeline: [
      { step: 1, label: 'Request Submitted', description: 'Blood request successfully submitted', date: '2024-11-20 11:20 AM', completed: true },
      { step: 2, label: 'Under Review', description: 'Request verified by admin', date: '2024-11-20 11:25 AM', completed: true },
      { step: 3, label: 'Matching Donors', description: 'Compatible donors searched', date: '2024-11-20 12:00 PM', completed: true },
      { step: 4, label: 'Donor Found', description: 'Compatible donor identified', date: '2024-11-20 03:15 PM', completed: true },
      { step: 5, label: 'Scheduled', description: 'Donation appointment scheduled', date: '2024-11-21 10:00 AM', completed: true },
      { step: 6, label: 'Completed', description: 'Blood donation completed successfully', date: '2024-11-22 02:30 PM', completed: true },
    ],
    currentStep: 6,
    appointmentDate: '2024-11-22',
    appointmentTime: '02:00 PM',
    completedDate: '2024-11-22 02:30 PM',
  },
};

export default function GuestTrackRequest() {
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();
  const query = searchParams.get('query') || '';

  // Find request by ID or phone number
  const request = mockRequests[query] || null;

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'Completed':
        return 'bg-green-100 text-green-800 border-green-200';
      case 'Donor Found':
      case 'Donor Matched':
      case 'Scheduled':
        return 'bg-blue-100 text-blue-800 border-blue-200';
      case 'Pending Review':
      case 'Under Review':
        return 'bg-yellow-100 text-yellow-800 border-yellow-200';
      default:
        return 'bg-gray-100 text-gray-800 border-gray-200';
    }
  };

  const getUrgencyColor = (urgency: string) => {
    return urgency === 'Emergency'
      ? 'bg-red-600 text-white'
      : 'bg-blue-600 text-white';
  };

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Header */}
      <header className="bg-white border-b border-gray-200 sticky top-0 z-50">
        <div className="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
          <div className="flex items-center gap-3">
            <Heart className="size-8 text-red-600 fill-red-600" />
            <div>
              <div className="text-red-600">BloodConnect</div>
              <div className="text-gray-500">Track Your Request</div>
            </div>
          </div>
          <Button 
            variant="outline"
            onClick={() => navigate('/guest/home')}
          >
            <ArrowLeft className="size-4 mr-2" />
            Back to Home
          </Button>
        </div>
      </header>

      {/* Main Content */}
      <div className="max-w-4xl mx-auto px-6 py-12">
        {request ? (
          <div className="space-y-8">
            {/* Header */}
            <div className="flex items-center justify-between">
              <div>
                <h1 className="text-gray-900 mb-2">Request Status</h1>
                <p className="text-gray-600">Request ID: {request.id}</p>
              </div>
              <Badge variant="outline" className={getStatusColor(request.status)}>
                {request.status}
              </Badge>
            </div>

            {/* Request Information */}
            <Card className="p-8">
              <div className="flex items-start justify-between mb-6">
                <h2 className="text-gray-900">Request Information</h2>
                <Badge className={getUrgencyColor(request.urgency)}>
                  <AlertCircle className="size-3 mr-1" />
                  {request.urgency}
                </Badge>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div className="flex items-start gap-3">
                  <div className="size-10 bg-red-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <Droplet className="size-5 text-red-600" />
                  </div>
                  <div>
                    <p className="text-gray-600 mb-1">Blood Type</p>
                    <p className="text-gray-900">{request.bloodType}</p>
                  </div>
                </div>

                <div className="flex items-start gap-3">
                  <div className="size-10 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <Droplet className="size-5 text-blue-600" />
                  </div>
                  <div>
                    <p className="text-gray-600 mb-1">Quantity Needed</p>
                    <p className="text-gray-900">{request.quantity} Units</p>
                  </div>
                </div>

                <div className="flex items-start gap-3">
                  <div className="size-10 bg-purple-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <MapPin className="size-5 text-purple-600" />
                  </div>
                  <div>
                    <p className="text-gray-600 mb-1">Location</p>
                    <p className="text-gray-900">{request.location}</p>
                  </div>
                </div>

                <div className="flex items-start gap-3">
                  <div className="size-10 bg-green-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <Calendar className="size-5 text-green-600" />
                  </div>
                  <div>
                    <p className="text-gray-600 mb-1">Required By</p>
                    <p className="text-gray-900">{request.requiredBy}</p>
                  </div>
                </div>

                <div className="flex items-start gap-3">
                  <div className="size-10 bg-gray-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <Clock className="size-5 text-gray-600" />
                  </div>
                  <div>
                    <p className="text-gray-600 mb-1">Submitted On</p>
                    <p className="text-gray-900">{request.submittedOn}</p>
                  </div>
                </div>

                {request.completedDate && (
                  <div className="flex items-start gap-3">
                    <div className="size-10 bg-green-100 rounded-lg flex items-center justify-center flex-shrink-0">
                      <CheckCircle className="size-5 text-green-600" />
                    </div>
                    <div>
                      <p className="text-gray-600 mb-1">Completed On</p>
                      <p className="text-gray-900">{request.completedDate}</p>
                    </div>
                  </div>
                )}
              </div>
            </Card>

            {/* Request Timeline */}
            <Card className="p-8">
              <h2 className="text-gray-900 mb-8">Request Timeline</h2>

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

            {/* Appointment Information (if scheduled) */}
            {request.appointmentDate && (
              <Card className={`p-6 border-l-4 ${
                request.completedDate 
                  ? 'border-l-green-600 bg-green-50' 
                  : 'border-l-blue-600 bg-blue-50'
              }`}>
                <div className="flex items-start gap-3">
                  <Calendar className={`size-6 flex-shrink-0 mt-1 ${
                    request.completedDate ? 'text-green-600' : 'text-blue-600'
                  }`} />
                  <div>
                    <h3 className="text-gray-900 mb-2">
                      {request.completedDate ? 'Completed Appointment' : 'Scheduled Appointment'}
                    </h3>
                    <div className="space-y-1 text-gray-700">
                      <p><strong>Date:</strong> {request.appointmentDate}</p>
                      <p><strong>Time:</strong> {request.appointmentTime}</p>
                    </div>
                    {request.completedDate && (
                      <Badge className="bg-green-600 hover:bg-green-700 mt-3">
                        <CheckCircle className="size-3 mr-1" />
                        Completed
                      </Badge>
                    )}
                  </div>
                </div>
              </Card>
            )}

            {/* Help Section */}
            <Card className="p-6 bg-gray-50">
              <div className="flex items-start gap-3">
                <AlertCircle className="size-6 text-gray-600 flex-shrink-0 mt-1" />
                <div>
                  <h3 className="text-gray-900 mb-2">Need Help?</h3>
                  <p className="text-gray-600 mb-4">
                    If you have any questions about your request, please contact our support team.
                  </p>
                  <p className="text-red-600">Emergency Hotline: 1-800-BLOOD-NOW</p>
                  <p className="text-gray-600 mt-1">Available 24/7</p>
                </div>
              </div>
            </Card>
          </div>
        ) : (
          // Request not found
          <Card className="p-12 text-center">
            <div className="size-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
              <AlertCircle className="size-8 text-gray-400" />
            </div>
            <h2 className="text-gray-900 mb-2">Request Not Found</h2>
            <p className="text-gray-600 mb-6">
              We couldn't find a blood request matching "{query}". Please check your request ID or phone number and try again.
            </p>
            <Button 
              onClick={() => navigate('/guest/home')}
              className="bg-red-600 hover:bg-red-700"
            >
              <ArrowLeft className="size-4 mr-2" />
              Back to Home
            </Button>
          </Card>
        )}
      </div>

      {/* Footer */}
      <footer className="bg-gray-900 text-white py-8 mt-16">
        <div className="max-w-7xl mx-auto px-6 text-center text-gray-400">
          <p>© 2024 BloodConnect. All rights reserved.</p>
        </div>
      </footer>
    </div>
  );
}
