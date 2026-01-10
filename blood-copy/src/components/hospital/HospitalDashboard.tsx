import DashboardLayout from '../layout/DashboardLayout';
import { LayoutDashboard, Users, Calendar, MessageSquare, Droplet, AlertCircle, CheckCircle, Clock } from 'lucide-react';
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

const activeRequests = [
  { id: 'REQ001', bloodType: 'O+', quantity: '2 Units', status: 'Processing', urgency: 'Emergency', requestedOn: '2 hours ago' },
  { id: 'REQ002', bloodType: 'A+', quantity: '3 Units', status: 'Approved', urgency: 'Normal', requestedOn: '5 hours ago' },
  { id: 'REQ003', bloodType: 'B-', quantity: '1 Unit', status: 'Pending', urgency: 'Emergency', requestedOn: '1 day ago' },
];

const recentDonors = [
  { name: 'John Smith', bloodType: 'O+', lastDonation: '2 weeks ago', availability: 'Available', phone: '555-0101' },
  { name: 'Maria Garcia', bloodType: 'A+', lastDonation: '1 month ago', availability: 'Available', phone: '555-0102' },
  { name: 'David Lee', bloodType: 'B-', lastDonation: '3 weeks ago', availability: 'Not Available', phone: '555-0103' },
];

export default function HospitalDashboard() {
  return (
    <DashboardLayout
      sidebarItems={sidebarItems}
      sidebarTitle="Hospital Portal"
      userName="City Hospital"
      userRole="Hospital Admin"
      notifications={3}
    >
      <div className="space-y-8">
        <div>
          <h1 className="text-gray-900 mb-2">Hospital Dashboard</h1>
          <p className="text-gray-600">Manage blood requests and appointments</p>
        </div>

        {/* Stats Cards */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          <Card className="p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-gray-600 mb-1">Active Requests</p>
                <h2 className="text-gray-900">8</h2>
              </div>
              <div className="bg-blue-100 p-3 rounded-lg">
                <Droplet className="size-6 text-blue-600" />
              </div>
            </div>
            <p className="text-blue-600 mt-2">Currently processing</p>
          </Card>

          <Card className="p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-gray-600 mb-1">Completed Requests</p>
                <h2 className="text-gray-900">47</h2>
              </div>
              <div className="bg-green-100 p-3 rounded-lg">
                <CheckCircle className="size-6 text-green-600" />
              </div>
            </div>
            <p className="text-green-600 mt-2">This month</p>
          </Card>

          <Card className="p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-gray-600 mb-1">Urgent Alerts</p>
                <h2 className="text-gray-900">3</h2>
              </div>
              <div className="bg-red-100 p-3 rounded-lg">
                <AlertCircle className="size-6 text-red-600" />
              </div>
            </div>
            <p className="text-red-600 mt-2">Requires attention</p>
          </Card>

          <Card className="p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-gray-600 mb-1">Available Donors</p>
                <h2 className="text-gray-900">124</h2>
              </div>
              <div className="bg-purple-100 p-3 rounded-lg">
                <Users className="size-6 text-purple-600" />
              </div>
            </div>
            <p className="text-purple-600 mt-2">In your area</p>
          </Card>
        </div>

        {/* Quick Actions */}
        <Card className="p-6 bg-gradient-to-r from-red-50 to-white border-l-4 border-l-red-600">
          <div className="flex items-center justify-between">
            <div>
              <h2 className="text-gray-900 mb-2">Need Blood Urgently?</h2>
              <p className="text-gray-600">Request blood from available donors in your network</p>
            </div>
            <Button className="bg-red-600 hover:bg-red-700">
              <Droplet className="size-4 mr-2" />
              Request Blood
            </Button>
          </div>
        </Card>

        {/* Active Requests Table */}
        <Card className="p-6">
          <div className="flex items-center justify-between mb-6">
            <div>
              <h2 className="text-gray-900 mb-1">Active Blood Requests</h2>
              <p className="text-gray-600">Track your ongoing blood requests</p>
            </div>
            <Button variant="outline">View All</Button>
          </div>

          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Request ID</TableHead>
                <TableHead>Blood Type</TableHead>
                <TableHead>Quantity</TableHead>
                <TableHead>Urgency</TableHead>
                <TableHead>Status</TableHead>
                <TableHead>Requested</TableHead>
                <TableHead>Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {activeRequests.map((request) => (
                <TableRow key={request.id}>
                  <TableCell>{request.id}</TableCell>
                  <TableCell>
                    <Badge variant="outline" className="border-red-600 text-red-600">
                      {request.bloodType}
                    </Badge>
                  </TableCell>
                  <TableCell>{request.quantity}</TableCell>
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
                        request.status === 'Approved'
                          ? 'border-green-600 text-green-600'
                          : request.status === 'Pending'
                          ? 'border-yellow-600 text-yellow-600'
                          : 'border-blue-600 text-blue-600'
                      }
                    >
                      {request.status}
                    </Badge>
                  </TableCell>
                  <TableCell>{request.requestedOn}</TableCell>
                  <TableCell>
                    <Button size="sm" variant="outline">View</Button>
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </Card>

        {/* Available Donors */}
        <Card className="p-6">
          <div className="flex items-center justify-between mb-6">
            <div>
              <h2 className="text-gray-900 mb-1">Recent Donors</h2>
              <p className="text-gray-600">Donors who have contributed recently</p>
            </div>
            <Button variant="outline">View All Donors</Button>
          </div>

          <div className="space-y-4">
            {recentDonors.map((donor, index) => (
              <div key={index} className="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:shadow-md transition-shadow">
                <div className="flex items-center gap-4">
                  <div className="size-12 bg-red-100 rounded-full flex items-center justify-center">
                    <Users className="size-6 text-red-600" />
                  </div>
                  <div>
                    <h3 className="text-gray-900">{donor.name}</h3>
                    <p className="text-gray-600">{donor.phone}</p>
                  </div>
                </div>
                <div className="flex items-center gap-4">
                  <div>
                    <Badge variant="outline" className="border-red-600 text-red-600">
                      {donor.bloodType}
                    </Badge>
                  </div>
                  <div className="text-right">
                    <p className="text-gray-600">Last Donation</p>
                    <p className="text-gray-900">{donor.lastDonation}</p>
                  </div>
                  <Badge
                    variant="outline"
                    className={
                      donor.availability === 'Available'
                        ? 'border-green-600 text-green-600'
                        : 'border-gray-600 text-gray-600'
                    }
                  >
                    {donor.availability}
                  </Badge>
                  <Button size="sm" className="bg-red-600 hover:bg-red-700">
                    <MessageSquare className="size-4 mr-1" />
                    Contact
                  </Button>
                </div>
              </div>
            ))}
          </div>
        </Card>
      </div>
    </DashboardLayout>
  );
}
