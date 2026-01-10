import DashboardLayout from '../layout/DashboardLayout';
import { LayoutDashboard, Users, Calendar, MessageSquare, Droplet, Phone, Mail, CheckCircle, X, Clock } from 'lucide-react';
import { Card } from '../ui/card';
import { Badge } from '../ui/badge';
import { Button } from '../ui/button';
import { Table, TableHeader, TableRow, TableHead, TableBody, TableCell } from '../ui/table';

const sidebarItems = [
  { path: '/hospital/dashboard', label: 'Dashboard', icon: LayoutDashboard },
  { path: '/hospital/request', label: 'Request Blood', icon: Droplet },
  { path: '/hospital/donors', label: 'Donor List', icon: Users },
  { path: '/hospital/voluntary', label: 'Voluntary Requests', icon: Calendar },
  { path: '/hospital/appointments', label: 'Appointments', icon: Calendar },
  { path: '/hospital/chat', label: 'Chat', icon: MessageSquare },
];

const voluntaryRequests = [
  {
    id: 'VR001',
    donorName: 'John Smith',
    bloodType: 'O+',
    phone: '555-0101',
    email: 'john@email.com',
    requestedDate: '2024-12-10',
    requestedTime: '10:00 AM',
    submittedOn: '2 hours ago',
    status: 'Pending',
  },
  {
    id: 'VR002',
    donorName: 'Maria Garcia',
    bloodType: 'A+',
    phone: '555-0102',
    email: 'maria@email.com',
    requestedDate: '2024-12-12',
    requestedTime: '02:00 PM',
    submittedOn: '5 hours ago',
    status: 'Pending',
  },
  {
    id: 'VR003',
    donorName: 'David Lee',
    bloodType: 'B-',
    phone: '555-0103',
    email: 'david@email.com',
    requestedDate: '2024-12-08',
    requestedTime: '11:00 AM',
    submittedOn: '1 day ago',
    status: 'Pending',
  },
  {
    id: 'VR004',
    donorName: 'Sarah Johnson',
    bloodType: 'O-',
    phone: '555-0104',
    email: 'sarah@email.com',
    requestedDate: '2024-12-15',
    requestedTime: '09:00 AM',
    submittedOn: '3 hours ago',
    status: 'Pending',
  },
];

export default function HospitalVoluntaryRequests() {
  return (
    <DashboardLayout
      sidebarItems={sidebarItems}
      sidebarTitle="Hospital Portal"
      userName="City Hospital"
      userRole="Hospital Admin"
      notifications={3}
    >
      <div className="space-y-8">
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-gray-900 mb-2">Voluntary Donation Requests</h1>
            <p className="text-gray-600">Review and manage voluntary donation appointments from donors</p>
          </div>
          <Badge className="bg-blue-600 hover:bg-blue-700 px-4 py-2">
            {voluntaryRequests.filter(r => r.status === 'Pending').length} Pending Requests
          </Badge>
        </div>

        {/* Summary Cards */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
          <Card className="p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-gray-600 mb-1">Total Requests</p>
                <h2 className="text-gray-900">{voluntaryRequests.length}</h2>
              </div>
              <div className="bg-blue-100 p-3 rounded-lg">
                <Calendar className="size-6 text-blue-600" />
              </div>
            </div>
            <p className="text-blue-600 mt-2">This week</p>
          </Card>

          <Card className="p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-gray-600 mb-1">Pending</p>
                <h2 className="text-gray-900">{voluntaryRequests.filter(r => r.status === 'Pending').length}</h2>
              </div>
              <div className="bg-yellow-100 p-3 rounded-lg">
                <Clock className="size-6 text-yellow-600" />
              </div>
            </div>
            <p className="text-yellow-600 mt-2">Awaiting response</p>
          </Card>

          <Card className="p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-gray-600 mb-1">Active Donors</p>
                <h2 className="text-gray-900">{new Set(voluntaryRequests.map(r => r.donorName)).size}</h2>
              </div>
              <div className="bg-green-100 p-3 rounded-lg">
                <Users className="size-6 text-green-600" />
              </div>
            </div>
            <p className="text-green-600 mt-2">Unique donors</p>
          </Card>
        </div>

        {/* Requests Table */}
        <Card className="p-6">
          <div className="mb-6">
            <h2 className="text-gray-900 mb-1">Pending Voluntary Requests</h2>
            <p className="text-gray-600">Review and respond to donation requests from volunteers</p>
          </div>

          <div className="overflow-x-auto">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Request ID</TableHead>
                  <TableHead>Donor Name</TableHead>
                  <TableHead>Blood Type</TableHead>
                  <TableHead>Contact</TableHead>
                  <TableHead>Requested Date</TableHead>
                  <TableHead>Requested Time</TableHead>
                  <TableHead>Submitted</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead>Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {voluntaryRequests.map((request) => (
                  <TableRow key={request.id}>
                    <TableCell>{request.id}</TableCell>
                    <TableCell>
                      <div className="flex items-center gap-3">
                        <div className="size-10 bg-blue-100 rounded-full flex items-center justify-center">
                          <Users className="size-5 text-blue-600" />
                        </div>
                        <div>
                          <div className="text-gray-900">{request.donorName}</div>
                        </div>
                      </div>
                    </TableCell>
                    <TableCell>
                      <Badge variant="outline" className="border-red-600 text-red-600">
                        {request.bloodType}
                      </Badge>
                    </TableCell>
                    <TableCell>
                      <div className="space-y-1">
                        <div className="flex items-center gap-1 text-gray-600">
                          <Phone className="size-3" />
                          <span className="text-sm">{request.phone}</span>
                        </div>
                        <div className="flex items-center gap-1 text-gray-600">
                          <Mail className="size-3" />
                          <span className="text-sm">{request.email}</span>
                        </div>
                      </div>
                    </TableCell>
                    <TableCell>{request.requestedDate}</TableCell>
                    <TableCell>
                      <div className="flex items-center gap-2">
                        <Clock className="size-4 text-blue-600" />
                        {request.requestedTime}
                      </div>
                    </TableCell>
                    <TableCell>{request.submittedOn}</TableCell>
                    <TableCell>
                      <Badge variant="outline" className="border-yellow-600 text-yellow-600">
                        {request.status}
                      </Badge>
                    </TableCell>
                    <TableCell>
                      <div className="flex gap-2">
                        <Button size="sm" className="bg-green-600 hover:bg-green-700">
                          <CheckCircle className="size-4 mr-1" />
                          Accept
                        </Button>
                        <Button size="sm" variant="outline" className="border-red-600 text-red-600 hover:bg-red-50">
                          <X className="size-4 mr-1" />
                          Decline
                        </Button>
                        <Button size="sm" className="bg-blue-600 hover:bg-blue-700">
                          <MessageSquare className="size-4 mr-1" />
                          Chat
                        </Button>
                      </div>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          </div>
        </Card>

        {/* Quick Actions */}
        <Card className="p-6 bg-gradient-to-r from-blue-50 to-white border-l-4 border-l-blue-600">
          <div className="flex items-start gap-4">
            <div className="bg-blue-100 p-3 rounded-lg">
              <Calendar className="size-6 text-blue-600" />
            </div>
            <div className="flex-1">
              <h2 className="text-gray-900 mb-2">Manage Voluntary Donations</h2>
              <p className="text-gray-600 mb-4">
                Accept voluntary donation requests to schedule appointments with donors. Use the chat feature to communicate any specific requirements or schedule changes.
              </p>
              <div className="flex gap-3">
                <Button variant="outline">
                  View Calendar
                </Button>
                <Button className="bg-blue-600 hover:bg-blue-700">
                  <MessageSquare className="size-4 mr-2" />
                  Contact All Donors
                </Button>
              </div>
            </div>
          </div>
        </Card>
      </div>
    </DashboardLayout>
  );
}
