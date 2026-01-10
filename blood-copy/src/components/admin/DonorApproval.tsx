import DashboardLayout from '../layout/DashboardLayout';
import { LayoutDashboard, Users, Building2, Droplet, FileText, Megaphone, MessageSquare, CheckCircle, XCircle, Eye } from 'lucide-react';
import { Card } from '../ui/card';
import { Badge } from '../ui/badge';
import { Button } from '../ui/button';
import { Table, TableHeader, TableRow, TableHead, TableBody, TableCell } from '../ui/table';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '../ui/dialog';
import { useState } from 'react';

const sidebarItems = [
  { path: '/admin/dashboard', label: 'Dashboard', icon: LayoutDashboard },
  { path: '/admin/donors', label: 'Donors', icon: Users },
  { path: '/admin/hospitals', label: 'Hospitals', icon: Building2 },
  { path: '/admin/blood-groups', label: 'Blood Groups', icon: Droplet },
  { path: '/admin/reports', label: 'Reports', icon: FileText },
  { path: '/admin/announcements', label: 'Announcements', icon: Megaphone },
  { path: '/admin/chat', label: 'Chat', icon: MessageSquare },
];

const mockDonors = [
  { id: 'DON001', name: 'Alex Thompson', email: 'alex@email.com', phone: '555-0101', bloodType: 'O+', age: 28, lastDonation: 'Never', status: 'Pending' },
  { id: 'DON002', name: 'Maria Garcia', email: 'maria@email.com', phone: '555-0102', bloodType: 'A+', age: 32, lastDonation: 'Never', status: 'Pending' },
  { id: 'DON003', name: 'David Lee', email: 'david@email.com', phone: '555-0103', bloodType: 'B+', age: 25, lastDonation: 'Never', status: 'Pending' },
  { id: 'DON004', name: 'Jennifer Brown', email: 'jennifer@email.com', phone: '555-0104', bloodType: 'AB-', age: 30, lastDonation: 'Never', status: 'Pending' },
  { id: 'DON005', name: 'Robert Wilson', email: 'robert@email.com', phone: '555-0105', bloodType: 'O-', age: 35, lastDonation: 'Never', status: 'Pending' },
];

export default function DonorApproval() {
  const [selectedDonor, setSelectedDonor] = useState<typeof mockDonors[0] | null>(null);

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
          <h1 className="text-gray-900 mb-2">Donor Approval</h1>
          <p className="text-gray-600">Review and approve pending donor registrations</p>
        </div>

        {/* Summary Cards */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
          <Card className="p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-gray-600 mb-1">Pending Approvals</p>
                <h2 className="text-gray-900">{mockDonors.length}</h2>
              </div>
              <div className="bg-yellow-100 p-3 rounded-lg">
                <Users className="size-6 text-yellow-600" />
              </div>
            </div>
          </Card>

          <Card className="p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-gray-600 mb-1">Approved Today</p>
                <h2 className="text-gray-900">12</h2>
              </div>
              <div className="bg-green-100 p-3 rounded-lg">
                <CheckCircle className="size-6 text-green-600" />
              </div>
            </div>
          </Card>

          <Card className="p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-gray-600 mb-1">Rejected Today</p>
                <h2 className="text-gray-900">3</h2>
              </div>
              <div className="bg-red-100 p-3 rounded-lg">
                <XCircle className="size-6 text-red-600" />
              </div>
            </div>
          </Card>
        </div>

        {/* Pending Donors Table */}
        <Card className="p-6">
          <div className="mb-6">
            <h2 className="text-gray-900 mb-1">Pending Donor Accounts</h2>
            <p className="text-gray-600">Review donor information and approve or reject applications</p>
          </div>

          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Donor ID</TableHead>
                <TableHead>Name</TableHead>
                <TableHead>Email</TableHead>
                <TableHead>Phone</TableHead>
                <TableHead>Blood Type</TableHead>
                <TableHead>Age</TableHead>
                <TableHead>Status</TableHead>
                <TableHead>Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {mockDonors.map((donor) => (
                <TableRow key={donor.id}>
                  <TableCell>{donor.id}</TableCell>
                  <TableCell>{donor.name}</TableCell>
                  <TableCell>{donor.email}</TableCell>
                  <TableCell>{donor.phone}</TableCell>
                  <TableCell>
                    <Badge variant="outline" className="border-red-600 text-red-600">
                      {donor.bloodType}
                    </Badge>
                  </TableCell>
                  <TableCell>{donor.age}</TableCell>
                  <TableCell>
                    <Badge variant="outline" className="border-yellow-600 text-yellow-600">
                      {donor.status}
                    </Badge>
                  </TableCell>
                  <TableCell>
                    <div className="flex gap-2">
                      <Dialog>
                        <DialogTrigger asChild>
                          <Button 
                            size="sm" 
                            variant="outline"
                            onClick={() => setSelectedDonor(donor)}
                          >
                            <Eye className="size-4 mr-1" />
                            View
                          </Button>
                        </DialogTrigger>
                        <DialogContent className="max-w-2xl">
                          <DialogHeader>
                            <DialogTitle>Donor Details</DialogTitle>
                          </DialogHeader>
                          {selectedDonor && (
                            <div className="space-y-4">
                              <div className="grid grid-cols-2 gap-4">
                                <div>
                                  <p className="text-gray-600">Donor ID</p>
                                  <p className="text-gray-900">{selectedDonor.id}</p>
                                </div>
                                <div>
                                  <p className="text-gray-600">Full Name</p>
                                  <p className="text-gray-900">{selectedDonor.name}</p>
                                </div>
                                <div>
                                  <p className="text-gray-600">Email</p>
                                  <p className="text-gray-900">{selectedDonor.email}</p>
                                </div>
                                <div>
                                  <p className="text-gray-600">Phone</p>
                                  <p className="text-gray-900">{selectedDonor.phone}</p>
                                </div>
                                <div>
                                  <p className="text-gray-600">Blood Type</p>
                                  <Badge variant="outline" className="border-red-600 text-red-600 mt-1">
                                    {selectedDonor.bloodType}
                                  </Badge>
                                </div>
                                <div>
                                  <p className="text-gray-600">Age</p>
                                  <p className="text-gray-900">{selectedDonor.age} years</p>
                                </div>
                                <div>
                                  <p className="text-gray-600">Last Donation</p>
                                  <p className="text-gray-900">{selectedDonor.lastDonation}</p>
                                </div>
                                <div>
                                  <p className="text-gray-600">Status</p>
                                  <Badge variant="outline" className="border-yellow-600 text-yellow-600 mt-1">
                                    {selectedDonor.status}
                                  </Badge>
                                </div>
                              </div>
                              <div className="border-t pt-4">
                                <p className="text-gray-600 mb-2">Health Information</p>
                                <p className="text-gray-900">No pre-existing conditions reported</p>
                                <p className="text-gray-900">Weight: 70 kg</p>
                                <p className="text-gray-900">Eligible for donation</p>
                              </div>
                              <div className="flex gap-3 pt-4">
                                <Button className="flex-1 bg-green-600 hover:bg-green-700">
                                  <CheckCircle className="size-4 mr-2" />
                                  Approve Donor
                                </Button>
                                <Button variant="outline" className="flex-1 border-red-600 text-red-600 hover:bg-red-50">
                                  <XCircle className="size-4 mr-2" />
                                  Reject
                                </Button>
                              </div>
                            </div>
                          )}
                        </DialogContent>
                      </Dialog>
                      <Button size="sm" className="bg-green-600 hover:bg-green-700">
                        <CheckCircle className="size-4 mr-1" />
                        Approve
                      </Button>
                      <Button size="sm" variant="outline" className="border-red-600 text-red-600 hover:bg-red-50">
                        <XCircle className="size-4 mr-1" />
                        Reject
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
