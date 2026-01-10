import DashboardLayout from '../layout/DashboardLayout';
import { LayoutDashboard, Heart, FileText, Award, MessageSquare, Settings, Download, Droplet, Calendar } from 'lucide-react';
import { Card } from '../ui/card';
import { Badge } from '../ui/badge';
import { Button } from '../ui/button';
import { Table, TableHeader, TableRow, TableHead, TableBody, TableCell } from '../ui/table';

const sidebarItems = [
  { path: '/donor/dashboard', label: 'Dashboard', icon: LayoutDashboard },
  { path: '/donor/health', label: 'Health Info', icon: Heart },
  { path: '/donor/voluntary', label: 'Donate Voluntarily', icon: Calendar },
  { path: '/donor/history', label: 'Donation History', icon: FileText },
  { path: '/donor/certificates', label: 'Certificates', icon: Award },
  { path: '/donor/chat', label: 'Chat', icon: MessageSquare },
  { path: '/donor/settings', label: 'Settings', icon: Settings },
];

const donationHistory = [
  { id: 'DON012', date: '2024-11-10', hospital: 'City Hospital', quantity: '500 ml', purpose: 'Emergency Request', status: 'Completed' },
  { id: 'DON011', date: '2024-08-15', hospital: 'St. Mary Medical', quantity: '450 ml', purpose: 'Blood Camp', status: 'Completed' },
  { id: 'DON010', date: '2024-05-20', hospital: 'Regional Hospital', quantity: '500 ml', purpose: 'Surgery', status: 'Completed' },
  { id: 'DON009', date: '2024-02-10', hospital: 'Community Hospital', quantity: '450 ml', purpose: 'Regular Donation', status: 'Completed' },
  { id: 'DON008', date: '2023-11-05', hospital: 'City Hospital', quantity: '500 ml', purpose: 'Emergency Request', status: 'Completed' },
];

export default function DonorHistory() {
  return (
    <DashboardLayout
      sidebarItems={sidebarItems}
      sidebarTitle="Donor Portal"
      userName="John Smith"
      userRole="Blood Donor"
      notifications={4}
    >
      <div className="space-y-8">
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-gray-900 mb-2">Donation History</h1>
            <p className="text-gray-600">View your complete donation records</p>
          </div>
          <Button className="bg-red-600 hover:bg-red-700">
            <Download className="size-4 mr-2" />
            Export History
          </Button>
        </div>

        {/* Summary Cards */}
        <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
          <Card className="p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-gray-600 mb-1">Total Donations</p>
                <h2 className="text-gray-900">12</h2>
              </div>
              <div className="bg-red-100 p-3 rounded-lg">
                <Droplet className="size-6 text-red-600 fill-red-600" />
              </div>
            </div>
          </Card>

          <Card className="p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-gray-600 mb-1">Total Volume</p>
                <h2 className="text-gray-900">6 L</h2>
              </div>
              <div className="bg-blue-100 p-3 rounded-lg">
                <Droplet className="size-6 text-blue-600" />
              </div>
            </div>
          </Card>

          <Card className="p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-gray-600 mb-1">This Year</p>
                <h2 className="text-gray-900">4</h2>
              </div>
              <div className="bg-green-100 p-3 rounded-lg">
                <FileText className="size-6 text-green-600" />
              </div>
            </div>
          </Card>

          <Card className="p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-gray-600 mb-1">Lives Saved</p>
                <h2 className="text-gray-900">36+</h2>
              </div>
              <div className="bg-purple-100 p-3 rounded-lg">
                <Heart className="size-6 text-purple-600 fill-purple-600" />
              </div>
            </div>
          </Card>
        </div>

        {/* History Table */}
        <Card className="p-6">
          <div className="mb-6">
            <h2 className="text-gray-900 mb-1">Complete Donation Records</h2>
            <p className="text-gray-600">Chronological list of all your donations</p>
          </div>

          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Donation ID</TableHead>
                <TableHead>Date</TableHead>
                <TableHead>Hospital</TableHead>
                <TableHead>Quantity</TableHead>
                <TableHead>Purpose</TableHead>
                <TableHead>Status</TableHead>
                <TableHead>Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {donationHistory.map((donation) => (
                <TableRow key={donation.id}>
                  <TableCell>{donation.id}</TableCell>
                  <TableCell>{donation.date}</TableCell>
                  <TableCell>{donation.hospital}</TableCell>
                  <TableCell>
                    <Badge variant="outline" className="border-red-600 text-red-600">
                      {donation.quantity}
                    </Badge>
                  </TableCell>
                  <TableCell>{donation.purpose}</TableCell>
                  <TableCell>
                    <Badge variant="outline" className="border-green-600 text-green-600">
                      {donation.status}
                    </Badge>
                  </TableCell>
                  <TableCell>
                    <div className="flex gap-2">
                      <Button size="sm" variant="outline">View</Button>
                      <Button size="sm" className="bg-red-600 hover:bg-red-700">
                        <Download className="size-4 mr-1" />
                        Certificate
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
