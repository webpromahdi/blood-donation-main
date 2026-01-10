import DashboardLayout from '../layout/DashboardLayout';
import { Send, MessageSquare, ClipboardList, Eye, AlertCircle, Droplet } from 'lucide-react';
import { Card } from '../ui/card';
import { Badge } from '../ui/badge';
import { Button } from '../ui/button';
import { useNavigate } from 'react-router-dom';

const sidebarItems = [
  { path: '/seeker/request', label: 'Submit Blood Request', icon: Send },
  { path: '/seeker/tracking', label: 'Track Request', icon: ClipboardList },
  { path: '/seeker/chat', label: 'Chat with Donor', icon: MessageSquare },
];

// Mock data - list of all requests created by the seeker
const bloodRequests = [
  {
    id: 'REQ001',
    bloodType: 'O+',
    quantity: 2,
    hospital: 'City Hospital',
    urgency: 'Emergency',
    status: 'Donor Matched',
    submittedOn: '2024-11-24 10:30 AM',
    patientName: 'John Doe',
    respondedDonors: 5,
  },
  {
    id: 'REQ002',
    bloodType: 'A-',
    quantity: 1,
    hospital: 'General Hospital',
    urgency: 'Normal',
    status: 'Searching for Donor',
    submittedOn: '2024-11-25 08:15 AM',
    patientName: 'Jane Smith',
    respondedDonors: 2,
  },
  {
    id: 'REQ003',
    bloodType: 'B+',
    quantity: 3,
    hospital: 'Medical Center',
    urgency: 'Emergency',
    status: 'Donation Scheduled',
    submittedOn: '2024-11-23 02:45 PM',
    patientName: 'Bob Johnson',
    respondedDonors: 8,
  },
  {
    id: 'REQ004',
    bloodType: 'AB+',
    quantity: 1,
    hospital: 'Community Hospital',
    urgency: 'Normal',
    status: 'Completed',
    submittedOn: '2024-11-20 11:20 AM',
    patientName: 'Alice Williams',
    respondedDonors: 3,
  },
];

export default function SeekerTracking() {
  const navigate = useNavigate();

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

  const getUrgencyColor = (urgency: string) => {
    return urgency === 'Emergency'
      ? 'bg-red-600 hover:bg-red-700 text-white'
      : 'bg-blue-600 hover:bg-blue-700 text-white';
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
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-gray-900 mb-2">Track Your Requests</h1>
            <p className="text-gray-600">View and monitor all your blood donation requests</p>
          </div>
          <Button 
            onClick={() => navigate('/seeker/request')}
            className="bg-red-600 hover:bg-red-700"
          >
            <Send className="size-4 mr-2" />
            New Request
          </Button>
        </div>

        {/* Summary Cards */}
        <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
          <Card className="p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-gray-600 mb-1">Total Requests</p>
                <p className="text-gray-900">{bloodRequests.length}</p>
              </div>
              <div className="size-12 bg-blue-100 rounded-lg flex items-center justify-center">
                <ClipboardList className="size-6 text-blue-600" />
              </div>
            </div>
          </Card>

          <Card className="p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-gray-600 mb-1">Active Requests</p>
                <p className="text-gray-900">
                  {bloodRequests.filter(r => r.status !== 'Completed').length}
                </p>
              </div>
              <div className="size-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                <AlertCircle className="size-6 text-yellow-600" />
              </div>
            </div>
          </Card>

          <Card className="p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-gray-600 mb-1">Donors Responded</p>
                <p className="text-gray-900">
                  {bloodRequests.reduce((sum, r) => sum + r.respondedDonors, 0)}
                </p>
              </div>
              <div className="size-12 bg-green-100 rounded-lg flex items-center justify-center">
                <Droplet className="size-6 text-green-600" />
              </div>
            </div>
          </Card>

          <Card className="p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-gray-600 mb-1">Completed</p>
                <p className="text-gray-900">
                  {bloodRequests.filter(r => r.status === 'Completed').length}
                </p>
              </div>
              <div className="size-12 bg-red-100 rounded-lg flex items-center justify-center">
                <Droplet className="size-6 text-red-600 fill-red-600" />
              </div>
            </div>
          </Card>
        </div>

        {/* Requests List */}
        <Card>
          <div className="p-6 border-b border-gray-200">
            <h2 className="text-gray-900">All Blood Requests</h2>
          </div>
          
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead className="bg-gray-50 border-b border-gray-200">
                <tr>
                  <th className="px-6 py-4 text-left text-gray-700">Request ID</th>
                  <th className="px-6 py-4 text-left text-gray-700">Patient Name</th>
                  <th className="px-6 py-4 text-left text-gray-700">Blood Type</th>
                  <th className="px-6 py-4 text-left text-gray-700">Quantity</th>
                  <th className="px-6 py-4 text-left text-gray-700">Hospital</th>
                  <th className="px-6 py-4 text-left text-gray-700">Urgency</th>
                  <th className="px-6 py-4 text-left text-gray-700">Status</th>
                  <th className="px-6 py-4 text-left text-gray-700">Submitted</th>
                  <th className="px-6 py-4 text-left text-gray-700">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-200">
                {bloodRequests.map((request) => (
                  <tr key={request.id} className="hover:bg-gray-50 transition-colors">
                    <td className="px-6 py-4">
                      <span className="text-gray-900">{request.id}</span>
                    </td>
                    <td className="px-6 py-4">
                      <span className="text-gray-900">{request.patientName}</span>
                    </td>
                    <td className="px-6 py-4">
                      <Badge variant="outline" className="border-red-600 text-red-600">
                        {request.bloodType}
                      </Badge>
                    </td>
                    <td className="px-6 py-4">
                      <span className="text-gray-900">{request.quantity} Units</span>
                    </td>
                    <td className="px-6 py-4">
                      <span className="text-gray-900">{request.hospital}</span>
                    </td>
                    <td className="px-6 py-4">
                      <Badge className={getUrgencyColor(request.urgency)}>
                        {request.urgency}
                      </Badge>
                    </td>
                    <td className="px-6 py-4">
                      <Badge variant="outline" className={getStatusColor(request.status)}>
                        {request.status}
                      </Badge>
                    </td>
                    <td className="px-6 py-4">
                      <span className="text-gray-600">{request.submittedOn}</span>
                    </td>
                    <td className="px-6 py-4">
                      <Button
                        size="sm"
                        variant="outline"
                        onClick={() => navigate(`/seeker/request-details/${request.id}`)}
                        className="border-red-600 text-red-600 hover:bg-red-50"
                      >
                        <Eye className="size-4 mr-1" />
                        View Details
                      </Button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>

          {bloodRequests.length === 0 && (
            <div className="p-12 text-center">
              <ClipboardList className="size-12 text-gray-400 mx-auto mb-4" />
              <h3 className="text-gray-900 mb-2">No Requests Yet</h3>
              <p className="text-gray-600 mb-6">
                You haven't created any blood requests yet.
              </p>
              <Button 
                onClick={() => navigate('/seeker/request')}
                className="bg-red-600 hover:bg-red-700"
              >
                <Send className="size-4 mr-2" />
                Create Your First Request
              </Button>
            </div>
          )}
        </Card>
      </div>
    </DashboardLayout>
  );
}
