import DashboardLayout from '../layout/DashboardLayout';
import { LayoutDashboard, Users, Calendar, MessageSquare, Droplet, Phone, Mail, MapPin, Filter } from 'lucide-react';
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

const donors = [
  { id: 'DON001', name: 'John Smith', bloodType: 'O+', age: 28, location: 'Downtown', phone: '555-0101', email: 'john@email.com', lastDonation: '2 weeks ago', totalDonations: 12, availability: 'Available' },
  { id: 'DON002', name: 'Maria Garcia', bloodType: 'A+', age: 32, location: 'Uptown', phone: '555-0102', email: 'maria@email.com', lastDonation: '1 month ago', totalDonations: 8, availability: 'Available' },
  { id: 'DON003', name: 'David Lee', bloodType: 'B-', age: 25, location: 'Westside', phone: '555-0103', email: 'david@email.com', lastDonation: '3 weeks ago', totalDonations: 5, availability: 'Not Available' },
  { id: 'DON004', name: 'Sarah Johnson', bloodType: 'O-', age: 30, location: 'Eastside', phone: '555-0104', email: 'sarah@email.com', lastDonation: '1 week ago', totalDonations: 15, availability: 'Available' },
  { id: 'DON005', name: 'Michael Brown', bloodType: 'AB+', age: 35, location: 'Central', phone: '555-0105', email: 'michael@email.com', lastDonation: '2 months ago', totalDonations: 20, availability: 'Available' },
  { id: 'DON006', name: 'Emma Wilson', bloodType: 'A-', age: 27, location: 'Northside', phone: '555-0106', email: 'emma@email.com', lastDonation: '3 months ago', totalDonations: 6, availability: 'Available' },
];

export default function HospitalDonorList() {
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
            <h1 className="text-gray-900 mb-2">Donor List</h1>
            <p className="text-gray-600">Browse and contact registered blood donors</p>
          </div>
          <div className="flex gap-3">
            <Button variant="outline">
              <Filter className="size-4 mr-2" />
              Filter by Location
            </Button>
          </div>
        </div>

        {/* Quick Filter */}
        <Card className="p-6">
          <div className="flex flex-wrap gap-3">
            <Button variant="outline" className="border-red-600 text-red-600">
              All Donors
            </Button>
            <Button variant="outline">O+</Button>
            <Button variant="outline">O-</Button>
            <Button variant="outline">A+</Button>
            <Button variant="outline">A-</Button>
            <Button variant="outline">B+</Button>
            <Button variant="outline">B-</Button>
            <Button variant="outline">AB+</Button>
            <Button variant="outline">AB-</Button>
            <div className="flex-1" />
            <Button variant="outline">
              Available Only
            </Button>
          </div>
        </Card>

        {/* Donors Table */}
        <Card className="p-6">
          <div className="mb-6">
            <h2 className="text-gray-900 mb-1">Registered Donors</h2>
            <p className="text-gray-600">Total {donors.length} donors in your network</p>
          </div>

          <div className="overflow-x-auto">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Donor ID</TableHead>
                  <TableHead>Name</TableHead>
                  <TableHead>Blood Type</TableHead>
                  <TableHead>Location</TableHead>
                  <TableHead>Last Donation</TableHead>
                  <TableHead>Total Donations</TableHead>
                  <TableHead>Availability</TableHead>
                  <TableHead>Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {donors.map((donor) => (
                  <TableRow key={donor.id}>
                    <TableCell>{donor.id}</TableCell>
                    <TableCell>
                      <div className="flex items-center gap-3">
                        <div className="size-10 bg-red-100 rounded-full flex items-center justify-center">
                          <Users className="size-5 text-red-600" />
                        </div>
                        <div>
                          <div className="text-gray-900">{donor.name}</div>
                          <div className="text-gray-500">{donor.age} years</div>
                        </div>
                      </div>
                    </TableCell>
                    <TableCell>
                      <Badge variant="outline" className="border-red-600 text-red-600">
                        {donor.bloodType}
                      </Badge>
                    </TableCell>
                    <TableCell>
                      <div className="flex items-center gap-2 text-gray-600">
                        <MapPin className="size-4" />
                        {donor.location}
                      </div>
                    </TableCell>
                    <TableCell>{donor.lastDonation}</TableCell>
                    <TableCell>
                      <Badge variant="secondary">{donor.totalDonations} times</Badge>
                    </TableCell>
                    <TableCell>
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
                    </TableCell>
                    <TableCell>
                      <div className="flex gap-2">
                        <Button size="sm" variant="outline">
                          <Phone className="size-4 mr-1" />
                          Call
                        </Button>
                        <Button size="sm" className="bg-red-600 hover:bg-red-700">
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
      </div>
    </DashboardLayout>
  );
}