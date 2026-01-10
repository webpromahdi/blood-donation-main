import DashboardLayout from '../layout/DashboardLayout';
import { LayoutDashboard, Users, Building2, Droplet, FileText, Megaphone, MessageSquare, Plus, Edit, Trash2 } from 'lucide-react';
import { Card } from '../ui/card';
import { Badge } from '../ui/badge';
import { Button } from '../ui/button';

const sidebarItems = [
  { path: '/admin/dashboard', label: 'Dashboard', icon: LayoutDashboard },
  { path: '/admin/donors', label: 'Donors', icon: Users },
  { path: '/admin/hospitals', label: 'Hospitals', icon: Building2 },
  { path: '/admin/blood-groups', label: 'Blood Groups', icon: Droplet },
  { path: '/admin/reports', label: 'Reports', icon: FileText },
  { path: '/admin/announcements', label: 'Announcements', icon: Megaphone },
  { path: '/admin/chat', label: 'Chat', icon: MessageSquare },
];

const bloodGroups = [
  { type: 'A+', donors: 342, available: 125, required: 45, compatibility: 'Can donate to A+, AB+' },
  { type: 'A-', donors: 128, available: 48, required: 22, compatibility: 'Can donate to A+, A-, AB+, AB-' },
  { type: 'B+', donors: 289, available: 98, required: 38, compatibility: 'Can donate to B+, AB+' },
  { type: 'B-', donors: 94, available: 32, required: 15, compatibility: 'Can donate to B+, B-, AB+, AB-' },
  { type: 'AB+', donors: 156, available: 58, required: 28, compatibility: 'Can donate to AB+ only' },
  { type: 'AB-', donors: 67, available: 24, required: 12, compatibility: 'Can donate to AB+, AB-' },
  { type: 'O+', donors: 423, available: 156, required: 67, compatibility: 'Can donate to all positive types' },
  { type: 'O-', donors: 185, available: 72, required: 34, compatibility: 'Universal donor - all types' },
];

export default function BloodGroupManagement() {
  return (
    <DashboardLayout
      sidebarItems={sidebarItems}
      sidebarTitle="Admin Panel"
      userName="Admin User"
      userRole="System Administrator"
      notifications={5}
    >
      <div className="space-y-8">
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-gray-900 mb-2">Blood Group Management</h1>
            <p className="text-gray-600">Monitor blood inventory and donor statistics</p>
          </div>
          <Button className="bg-red-600 hover:bg-red-700">
            <Plus className="size-4 mr-2" />
            Add Blood Group
          </Button>
        </div>

        {/* Summary Stats */}
        <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
          <Card className="p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-gray-600 mb-1">Total Blood Types</p>
                <h2 className="text-gray-900">8</h2>
              </div>
              <div className="bg-red-100 p-3 rounded-lg">
                <Droplet className="size-6 text-red-600" />
              </div>
            </div>
          </Card>

          <Card className="p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-gray-600 mb-1">Total Donors</p>
                <h2 className="text-gray-900">1,684</h2>
              </div>
              <div className="bg-blue-100 p-3 rounded-lg">
                <Users className="size-6 text-blue-600" />
              </div>
            </div>
          </Card>

          <Card className="p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-gray-600 mb-1">Units Available</p>
                <h2 className="text-gray-900">613</h2>
              </div>
              <div className="bg-green-100 p-3 rounded-lg">
                <Droplet className="size-6 text-green-600" />
              </div>
            </div>
          </Card>

          <Card className="p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-gray-600 mb-1">Units Required</p>
                <h2 className="text-gray-900">261</h2>
              </div>
              <div className="bg-yellow-100 p-3 rounded-lg">
                <Droplet className="size-6 text-yellow-600" />
              </div>
            </div>
          </Card>
        </div>

        {/* Blood Type Cards Grid */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          {bloodGroups.map((group) => (
            <Card key={group.type} className="p-6 hover:shadow-lg transition-shadow">
              <div className="flex items-start justify-between mb-4">
                <div>
                  <div className="flex items-center gap-2 mb-1">
                    <Droplet className="size-5 text-red-600 fill-red-600" />
                    <h2 className="text-gray-900">{group.type}</h2>
                  </div>
                  <Badge variant="outline" className="border-red-600 text-red-600">
                    Blood Type
                  </Badge>
                </div>
                <div className="flex gap-2">
                  <button className="p-2 hover:bg-gray-100 rounded-lg transition-colors">
                    <Edit className="size-4 text-gray-600" />
                  </button>
                  <button className="p-2 hover:bg-red-50 rounded-lg transition-colors">
                    <Trash2 className="size-4 text-red-600" />
                  </button>
                </div>
              </div>

              <div className="space-y-3">
                <div className="flex justify-between items-center">
                  <span className="text-gray-600">Registered Donors</span>
                  <span className="text-gray-900">{group.donors}</span>
                </div>
                <div className="flex justify-between items-center">
                  <span className="text-gray-600">Units Available</span>
                  <Badge className="bg-green-100 text-green-700 hover:bg-green-100">
                    {group.available}
                  </Badge>
                </div>
                <div className="flex justify-between items-center">
                  <span className="text-gray-600">Units Required</span>
                  <Badge className="bg-yellow-100 text-yellow-700 hover:bg-yellow-100">
                    {group.required}
                  </Badge>
                </div>
              </div>

              <div className="mt-4 pt-4 border-t border-gray-200">
                <p className="text-gray-600 mb-1">Compatibility</p>
                <p className="text-gray-900">{group.compatibility}</p>
              </div>

              <div className="mt-4">
                <Button className="w-full bg-red-600 hover:bg-red-700" size="sm">
                  View Details
                </Button>
              </div>
            </Card>
          ))}
        </div>

        {/* Inventory Alert */}
        <Card className="p-6 border-l-4 border-l-yellow-500">
          <div className="flex items-start gap-4">
            <div className="bg-yellow-100 p-3 rounded-lg">
              <Droplet className="size-6 text-yellow-600" />
            </div>
            <div className="flex-1">
              <h3 className="text-gray-900 mb-1">Low Inventory Alert</h3>
              <p className="text-gray-600 mb-4">
                Blood types <strong>B-</strong> and <strong>AB-</strong> are running low. Consider sending emergency alerts to registered donors.
              </p>
              <Button className="bg-red-600 hover:bg-red-700">
                <Megaphone className="size-4 mr-2" />
                Send Emergency Alert
              </Button>
            </div>
          </div>
        </Card>
      </div>
    </DashboardLayout>
  );
}
