import DashboardLayout from '../layout/DashboardLayout';
import { LayoutDashboard, Users, Building2, Droplet, FileText, Megaphone, MessageSquare, AlertCircle, CheckCircle, Clock } from 'lucide-react';
import { Card } from '../ui/card';
import { Badge } from '../ui/badge';
import { Button } from '../ui/button';
import { Table, TableHeader, TableRow, TableHead, TableBody, TableCell } from '../ui/table';

const sidebarItems = [
  { path: '/admin/dashboard', label: 'Dashboard', icon: LayoutDashboard },
  { path: '/admin/donors', label: 'Donors', icon: Users },
  { path: '/admin/hospitals', label: 'Hospitals', icon: Building2 },
  { path: '/admin/blood-groups', label: 'Blood Groups', icon: Droplet },
  { path: '/admin/reports', label: 'Reports', icon: FileText },
  { path: '/admin/announcements', label: 'Announcements', icon: Megaphone },
  { path: '/admin/chat', label: 'Chat', icon: MessageSquare },
];

const mockRequests = [
  { id: 'REQ001', name: 'John Doe', bloodType: 'O+', quantity: '2 Units', urgency: 'Emergency', status: 'Pending', hospital: 'City Hospital' },
  { id: 'REQ002', name: 'Sarah Smith', bloodType: 'A+', quantity: '3 Units', urgency: 'Normal', status: 'Approved', hospital: 'General Hospital' },
  { id: 'REQ003', name: 'Mike Johnson', bloodType: 'B-', quantity: '1 Unit', urgency: 'Emergency', status: 'Processing', hospital: 'St. Mary\'s' },
  { id: 'REQ004', name: 'Emily Davis', bloodType: 'AB+', quantity: '2 Units', urgency: 'Normal', status: 'Completed', hospital: 'Regional Medical' },
  { id: 'REQ005', name: 'James Wilson', bloodType: 'O-', quantity: '4 Units', urgency: 'Emergency', status: 'Pending', hospital: 'Central Hospital' },
];

export default function AdminDashboard() {
  return (
    <DashboardLayout
      sidebarItems={sidebarItems}
      sidebarTitle="Admin Panel"
      userName="Admin User"
      userRole="System Administrator"
      notifications={5}
    >
      <div className="space-y-8">
        <div>
          <h1 className="text-gray-900 mb-2">Admin Dashboard</h1>
          <p className="text-gray-600">Overview of the blood donation system</p>
        </div>

        {/* Stats Cards */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          <Card className="p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-gray-600 mb-1">Total Donors</p>
                <h2 className="text-gray-900">1,284</h2>
              </div>
              <div className="bg-red-100 p-3 rounded-lg">
                <Users className="size-6 text-red-600" />
              </div>
            </div>
            <p className="text-green-600 mt-2">+12% from last month</p>
          </Card>

          <Card className="p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-gray-600 mb-1">Total Hospitals</p>
                <h2 className="text-gray-900">47</h2>
              </div>
              <div className="bg-blue-100 p-3 rounded-lg">
                <Building2 className="size-6 text-blue-600" />
              </div>
            </div>
            <p className="text-green-600 mt-2">+3 new this month</p>
          </Card>

          <Card className="p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-gray-600 mb-1">Pending Approvals</p>
                <h2 className="text-gray-900">23</h2>
              </div>
              <div className="bg-yellow-100 p-3 rounded-lg">
                <Clock className="size-6 text-yellow-600" />
              </div>
            </div>
            <p className="text-yellow-600 mt-2">Requires attention</p>
          </Card>

          <Card className="p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-gray-600 mb-1">Emergency Requests</p>
                <h2 className="text-gray-900">8</h2>
              </div>
              <div className="bg-red-100 p-3 rounded-lg">
                <AlertCircle className="size-6 text-red-600" />
              </div>
            </div>
            <p className="text-red-600 mt-2">Today</p>
          </Card>
        </div>

        {/* Blood Requests Table */}
        <Card className="p-6">
          <div className="flex items-center justify-between mb-6">
            <div>
              <h2 className="text-gray-900 mb-1">All Blood Requests</h2>
              <p className="text-gray-600">Manage and monitor blood requests</p>
            </div>
            <Button className="bg-red-600 hover:bg-red-700">
              <FileText className="size-4 mr-2" />
              Export Report
            </Button>
          </div>

          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Request ID</TableHead>
                <TableHead>Name</TableHead>
                <TableHead>Blood Type</TableHead>
                <TableHead>Quantity</TableHead>
                <TableHead>Hospital</TableHead>
                <TableHead>Urgency</TableHead>
                <TableHead>Status</TableHead>
                <TableHead>Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {mockRequests.map((request) => (
                <TableRow key={request.id}>
                  <TableCell>{request.id}</TableCell>
                  <TableCell>{request.name}</TableCell>
                  <TableCell>
                    <Badge variant="outline" className="border-red-600 text-red-600">
                      {request.bloodType}
                    </Badge>
                  </TableCell>
                  <TableCell>{request.quantity}</TableCell>
                  <TableCell>{request.hospital}</TableCell>
                  <TableCell>
                    {request.urgency === 'Emergency' ? (
                      <Badge className="bg-red-600 hover:bg-red-700">
                        <AlertCircle className="size-3 mr-1" />
                        Emergency
                      </Badge>
                    ) : (
                      <Badge variant="secondary">Normal</Badge>
                    )}
                  </TableCell>
                  <TableCell>
                    <Badge
                      variant="outline"
                      className={
                        request.status === 'Completed'
                          ? 'border-green-600 text-green-600'
                          : request.status === 'Pending'
                          ? 'border-yellow-600 text-yellow-600'
                          : 'border-blue-600 text-blue-600'
                      }
                    >
                      {request.status}
                    </Badge>
                  </TableCell>
                  <TableCell>
                    <div className="flex gap-2">
                      <Button size="sm" variant="outline">View</Button>
                      <Button size="sm" className="bg-red-600 hover:bg-red-700">
                        Action
                      </Button>
                    </div>
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </Card>
      </div>
    </DashboardLayout>
  );
}
