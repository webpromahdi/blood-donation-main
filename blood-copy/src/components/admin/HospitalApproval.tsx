import DashboardLayout from '../layout/DashboardLayout';
import { LayoutDashboard, Users, Building2, Droplet, FileText, Megaphone, MessageSquare, CheckCircle, XCircle, Eye, MapPin } from 'lucide-react';
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

const mockHospitals = [
  { id: 'HOS001', name: 'City General Hospital', email: 'admin@citygeneral.com', phone: '555-1001', address: '123 Main St, City', type: 'Public', capacity: '500 beds', status: 'Pending' },
  { id: 'HOS002', name: 'St. Mary Medical Center', email: 'info@stmary.com', phone: '555-1002', address: '456 Oak Ave, Town', type: 'Private', capacity: '300 beds', status: 'Pending' },
  { id: 'HOS003', name: 'Regional Healthcare', email: 'contact@regional.com', phone: '555-1003', address: '789 Elm St, Region', type: 'Public', capacity: '750 beds', status: 'Pending' },
  { id: 'HOS004', name: 'Community Hospital', email: 'hello@community.com', phone: '555-1004', address: '321 Pine Rd, Village', type: 'Private', capacity: '200 beds', status: 'Pending' },
];

export default function HospitalApproval() {
  const [selectedHospital, setSelectedHospital] = useState<typeof mockHospitals[0] | null>(null);

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
          <h1 className="text-gray-900 mb-2">Hospital Approval</h1>
          <p className="text-gray-600">Review and approve hospital registrations</p>
        </div>

        {/* Summary Cards */}
        <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
          <Card className="p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-gray-600 mb-1">Pending</p>
                <h2 className="text-gray-900">{mockHospitals.length}</h2>
              </div>
              <div className="bg-yellow-100 p-3 rounded-lg">
                <Building2 className="size-6 text-yellow-600" />
              </div>
            </div>
          </Card>

          <Card className="p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-gray-600 mb-1">Approved</p>
                <h2 className="text-gray-900">47</h2>
              </div>
              <div className="bg-green-100 p-3 rounded-lg">
                <CheckCircle className="size-6 text-green-600" />
              </div>
            </div>
          </Card>

          <Card className="p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-gray-600 mb-1">Rejected</p>
                <h2 className="text-gray-900">5</h2>
              </div>
              <div className="bg-red-100 p-3 rounded-lg">
                <XCircle className="size-6 text-red-600" />
              </div>
            </div>
          </Card>

          <Card className="p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-gray-600 mb-1">Active</p>
                <h2 className="text-gray-900">45</h2>
              </div>
              <div className="bg-blue-100 p-3 rounded-lg">
                <Building2 className="size-6 text-blue-600" />
              </div>
            </div>
          </Card>
        </div>

        {/* Hospitals Table */}
        <Card className="p-6">
          <div className="mb-6">
            <h2 className="text-gray-900 mb-1">Pending Hospital Applications</h2>
            <p className="text-gray-600">Review hospital information and verify credentials</p>
          </div>

          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Hospital ID</TableHead>
                <TableHead>Name</TableHead>
                <TableHead>Contact Email</TableHead>
                <TableHead>Phone</TableHead>
                <TableHead>Type</TableHead>
                <TableHead>Status</TableHead>
                <TableHead>Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {mockHospitals.map((hospital) => (
                <TableRow key={hospital.id}>
                  <TableCell>{hospital.id}</TableCell>
                  <TableCell>{hospital.name}</TableCell>
                  <TableCell>{hospital.email}</TableCell>
                  <TableCell>{hospital.phone}</TableCell>
                  <TableCell>
                    <Badge variant="outline" className={hospital.type === 'Public' ? 'border-blue-600 text-blue-600' : 'border-purple-600 text-purple-600'}>
                      {hospital.type}
                    </Badge>
                  </TableCell>
                  <TableCell>
                    <Badge variant="outline" className="border-yellow-600 text-yellow-600">
                      {hospital.status}
                    </Badge>
                  </TableCell>
                  <TableCell>
                    <div className="flex gap-2">
                      <Dialog>
                        <DialogTrigger asChild>
                          <Button 
                            size="sm" 
                            variant="outline"
                            onClick={() => setSelectedHospital(hospital)}
                          >
                            <Eye className="size-4 mr-1" />
                            View
                          </Button>
                        </DialogTrigger>
                        <DialogContent className="max-w-2xl">
                          <DialogHeader>
                            <DialogTitle>Hospital Details</DialogTitle>
                          </DialogHeader>
                          {selectedHospital && (
                            <div className="space-y-4">
                              <div className="grid grid-cols-2 gap-4">
                                <div>
                                  <p className="text-gray-600">Hospital ID</p>
                                  <p className="text-gray-900">{selectedHospital.id}</p>
                                </div>
                                <div>
                                  <p className="text-gray-600">Hospital Name</p>
                                  <p className="text-gray-900">{selectedHospital.name}</p>
                                </div>
                                <div>
                                  <p className="text-gray-600">Email</p>
                                  <p className="text-gray-900">{selectedHospital.email}</p>
                                </div>
                                <div>
                                  <p className="text-gray-600">Phone</p>
                                  <p className="text-gray-900">{selectedHospital.phone}</p>
                                </div>
                                <div className="col-span-2">
                                  <p className="text-gray-600">Address</p>
                                  <p className="text-gray-900 flex items-center gap-2">
                                    <MapPin className="size-4 text-gray-400" />
                                    {selectedHospital.address}
                                  </p>
                                </div>
                                <div>
                                  <p className="text-gray-600">Type</p>
                                  <Badge variant="outline" className={selectedHospital.type === 'Public' ? 'border-blue-600 text-blue-600 mt-1' : 'border-purple-600 text-purple-600 mt-1'}>
                                    {selectedHospital.type}
                                  </Badge>
                                </div>
                                <div>
                                  <p className="text-gray-600">Capacity</p>
                                  <p className="text-gray-900">{selectedHospital.capacity}</p>
                                </div>
                                <div>
                                  <p className="text-gray-600">Status</p>
                                  <Badge variant="outline" className="border-yellow-600 text-yellow-600 mt-1">
                                    {selectedHospital.status}
                                  </Badge>
                                </div>
                              </div>
                              <div className="border-t pt-4">
                                <p className="text-gray-600 mb-2">Verification Documents</p>
                                <div className="space-y-2">
                                  <p className="text-gray-900">✓ Hospital License - Verified</p>
                                  <p className="text-gray-900">✓ Medical Registration - Verified</p>
                                  <p className="text-gray-900">✓ Blood Bank Certification - Verified</p>
                                </div>
                              </div>
                              <div className="flex gap-3 pt-4">
                                <Button className="flex-1 bg-green-600 hover:bg-green-700">
                                  <CheckCircle className="size-4 mr-2" />
                                  Approve Hospital
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
