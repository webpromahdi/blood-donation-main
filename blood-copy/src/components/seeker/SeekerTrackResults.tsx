import DashboardLayout from '../layout/DashboardLayout';
import { Send, CheckCircle, Clock, UserCheck, Calendar, Package, AlertCircle, MessageSquare, ClipboardList } from 'lucide-react';
import { Card } from '../ui/card';
import { Button } from '../ui/button';
import { Badge } from '../ui/badge';
import { useNavigate, useSearchParams } from 'react-router-dom';

const sidebarItems = [
  { path: '/seeker/request', label: 'Submit Blood Request', icon: Send },
  { path: '/seeker/tracking', label: 'Track Request', icon: ClipboardList },
  { path: '/seeker/chat', label: 'Chat with Donor', icon: MessageSquare },
];

const trackingSteps = [
  { id: 1, label: 'Request Submitted', icon: Send, description: 'Your blood request was successfully submitted' },
  { id: 2, label: 'Request Accepted', icon: CheckCircle, description: 'Admin verified and accepted your request' },
  { id: 3, label: 'Searching for Donor', icon: Clock, description: 'System is matching with compatible donors' },
  { id: 4, label: 'Donor Matched', icon: UserCheck, description: 'Compatible donor has been found' },
  { id: 5, label: 'Donation Scheduled', icon: Calendar, description: 'Donation appointment has been scheduled' },
  { id: 6, label: 'Completed', icon: Package, description: 'Blood donation completed successfully' },
];

// Mock data - simulating requests found by phone/ID
const mockRequests = [
  {
    id: 'REQ001',
    bloodType: 'O+',
    quantity: 2,
    hospital: 'City Hospital',
    urgency: 'Emergency',
    status: 'Donor Matched',
    currentStep: 4,
    date: '2024-11-24',
    patientName: 'John Doe',
    phone: '+1-555-0123',
  },
  {
    id: 'REQ002',
    bloodType: 'A-',
    quantity: 1,
    hospital: 'General Hospital',
    urgency: 'Normal',
    status: 'Searching for Donor',
    currentStep: 3,
    date: '2024-11-25',
    patientName: 'Jane Smith',
    phone: '+1-555-0123',
  },
  {
    id: 'REQ003',
    bloodType: 'B+',
    quantity: 3,
    hospital: 'Medical Center',
    urgency: 'Urgent',
    status: 'Completed',
    currentStep: 6,
    date: '2024-11-20',
    patientName: 'Bob Johnson',
    phone: '+1-555-0123',
  },
];

export default function SeekerTrackResults() {
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();
  const query = searchParams.get('query') || '';

  return (
    <DashboardLayout
      sidebarItems={sidebarItems}
      sidebarTitle="Seeker Portal"
      userName="Guest User"
      userRole="Blood Seeker"
      notifications={0}
    >
      <div className="space-y-8">
        <div>
          <h1 className="text-gray-900 mb-2">Tracking Results</h1>
          <p className="text-gray-600">
            Showing all blood requests for: <span className="text-gray-900">{query}</span>
          </p>
        </div>

        {/* Results */}
        <div className="space-y-8">
          {mockRequests.map((request) => (
            <Card key={request.id} className="p-6">
              {/* Request Header */}
              <div className="flex items-start justify-between mb-6 pb-6 border-b border-gray-200">
                <div>
                  <div className="flex items-center gap-3 mb-2">
                    <h2 className="text-gray-900">Request ID: {request.id}</h2>
                    <Badge 
                      className={
                        request.urgency === 'Emergency' 
                          ? 'bg-red-600 hover:bg-red-700' 
                          : request.urgency === 'Urgent'
                          ? 'bg-orange-600 hover:bg-orange-700'
                          : 'bg-blue-600 hover:bg-blue-700'
                      }
                    >
                      {request.urgency}
                    </Badge>
                  </div>
                  <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4">
                    <div>
                      <p className="text-gray-600">Patient Name</p>
                      <p className="text-gray-900">{request.patientName}</p>
                    </div>
                    <div>
                      <p className="text-gray-600">Blood Type</p>
                      <p className="text-gray-900">{request.bloodType}</p>
                    </div>
                    <div>
                      <p className="text-gray-600">Quantity</p>
                      <p className="text-gray-900">{request.quantity} Units</p>
                    </div>
                    <div>
                      <p className="text-gray-600">Hospital</p>
                      <p className="text-gray-900">{request.hospital}</p>
                    </div>
                  </div>
                </div>
                <Badge 
                  variant="outline" 
                  className={
                    request.currentStep === 6
                      ? 'border-green-600 text-green-600'
                      : 'border-blue-600 text-blue-600'
                  }
                >
                  {request.status}
                </Badge>
              </div>

              {/* Progress Tracker - Horizontal */}
              <div className="mb-6">
                <h3 className="text-gray-900 mb-6">Request Progress</h3>
                <div className="relative">
                  {/* Progress Line */}
                  <div className="absolute top-6 left-0 right-0 h-1 bg-gray-200 z-0">
                    <div 
                      className="h-full bg-green-600 transition-all duration-500"
                      style={{ 
                        width: `${(request.currentStep / trackingSteps.length) * 100}%` 
                      }}
                    />
                  </div>

                  {/* Steps */}
                  <div className="relative flex justify-between z-10">
                    {trackingSteps.map((step) => {
                      const isCompleted = step.id <= request.currentStep;
                      const isCurrent = step.id === request.currentStep;
                      const StepIcon = step.icon;

                      return (
                        <div key={step.id} className="flex flex-col items-center" style={{ width: '16.66%' }}>
                          <div 
                            className={`size-12 rounded-full flex items-center justify-center mb-2 ${
                              isCompleted 
                                ? isCurrent 
                                  ? 'bg-blue-600 ring-4 ring-blue-100' 
                                  : 'bg-green-600'
                                : 'bg-white border-2 border-gray-300'
                            }`}
                          >
                            <StepIcon 
                              className={`size-5 ${
                                isCompleted ? 'text-white' : 'text-gray-400'
                              }`} 
                            />
                          </div>
                          <p 
                            className={`text-center ${
                              isCompleted ? 'text-gray-900' : 'text-gray-400'
                            }`}
                          >
                            {step.label}
                          </p>
                        </div>
                      );
                    })}
                  </div>
                </div>
              </div>

              {/* Current Status Message */}
              <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div className="flex items-start justify-between gap-3">
                  <div className="flex items-start gap-3">
                    <AlertCircle className="size-5 text-blue-600 flex-shrink-0 mt-0.5" />
                    <div>
                      <p className="text-gray-900 mb-1">Current Status</p>
                      <p className="text-gray-700">
                        {request.currentStep === 1 && 'Your request has been submitted and is awaiting verification.'}
                        {request.currentStep === 2 && 'Your request has been accepted and verified by our admin team.'}
                        {request.currentStep === 3 && 'We are currently searching for compatible donors in your area.'}
                        {request.currentStep === 4 && 'Great news! A compatible donor has been matched to your request.'}
                        {request.currentStep === 5 && 'The donation appointment has been scheduled. You will be notified of the details.'}
                        {request.currentStep === 6 && 'The blood donation has been completed successfully. Thank you for using BloodConnect!'}
                      </p>
                    </div>
                  </div>
                  {request.currentStep >= 4 && (
                    <Button 
                      size="sm"
                      className="bg-blue-600 hover:bg-blue-700"
                      onClick={() => navigate('/seeker/chat')}
                    >
                      <MessageSquare className="size-4 mr-2" />
                      Message Donor
                    </Button>
                  )}
                </div>
              </div>

              {/* Detailed Timeline */}
              <div className="mt-6 pt-6 border-t border-gray-200">
                <h4 className="text-gray-900 mb-4">Detailed Timeline</h4>
                <div className="space-y-3">
                  {trackingSteps.slice(0, request.currentStep).map((step) => {
                    const StepIcon = step.icon;
                    return (
                      <div key={step.id} className="flex items-start gap-3">
                        <div className="size-8 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0">
                          <StepIcon className="size-4 text-green-600" />
                        </div>
                        <div className="flex-1">
                          <div className="flex items-center justify-between">
                            <p className="text-gray-900">{step.label}</p>
                            <p className="text-gray-500">{request.date}</p>
                          </div>
                          <p className="text-gray-600">{step.description}</p>
                        </div>
                      </div>
                    );
                  })}
                </div>
              </div>
            </Card>
          ))}
        </div>

        {/* No Results Message (if needed) */}
        {mockRequests.length === 0 && (
          <Card className="p-12 text-center">
            <AlertCircle className="size-12 text-gray-400 mx-auto mb-4" />
            <h3 className="text-gray-900 mb-2">No Requests Found</h3>
            <p className="text-gray-600 mb-6">
              We couldn't find any blood requests matching "{query}". Please check your phone number or request ID and try again.
            </p>
            <Button onClick={() => navigate('/seeker/request')} className="bg-red-600 hover:bg-red-700">
              Go Back
            </Button>
          </Card>
        )}
      </div>
    </DashboardLayout>
  );
}
