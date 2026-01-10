import DashboardLayout from '../layout/DashboardLayout';
import { LayoutDashboard, Users, Building2, Droplet, FileText, Megaphone, MessageSquare, Download, Calendar, Filter } from 'lucide-react';
import { Card } from '../ui/card';
import { Button } from '../ui/button';
import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer, LineChart, Line, PieChart, Pie, Cell } from 'recharts';

const sidebarItems = [
  { path: '/admin/dashboard', label: 'Dashboard', icon: LayoutDashboard },
  { path: '/admin/donors', label: 'Donors', icon: Users },
  { path: '/admin/hospitals', label: 'Hospitals', icon: Building2 },
  { path: '/admin/blood-groups', label: 'Blood Groups', icon: Droplet },
  { path: '/admin/reports', label: 'Reports', icon: FileText },
  { path: '/admin/announcements', label: 'Announcements', icon: Megaphone },
  { path: '/admin/chat', label: 'Chat', icon: MessageSquare },
];

const donationData = [
  { month: 'Jan', donations: 145, requests: 132 },
  { month: 'Feb', donations: 167, requests: 158 },
  { month: 'Mar', donations: 189, requests: 171 },
  { month: 'Apr', donations: 156, requests: 149 },
  { month: 'May', donations: 178, requests: 165 },
  { month: 'Jun', donations: 198, requests: 184 },
];

const bloodTypeData = [
  { name: 'O+', value: 423, color: '#EF4444' },
  { name: 'A+', value: 342, color: '#F97316' },
  { name: 'B+', value: 289, color: '#F59E0B' },
  { name: 'AB+', value: 156, color: '#10B981' },
  { name: 'O-', value: 185, color: '#3B82F6' },
  { name: 'A-', value: 128, color: '#6366F1' },
  { name: 'B-', value: 94, color: '#8B5CF6' },
  { name: 'AB-', value: 67, color: '#EC4899' },
];

export default function AdminReports() {
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
            <h1 className="text-gray-900 mb-2">Reports & Analytics</h1>
            <p className="text-gray-600">Generate and download system reports</p>
          </div>
          <div className="flex gap-3">
            <Button variant="outline">
              <Filter className="size-4 mr-2" />
              Filter
            </Button>
            <Button variant="outline">
              <Calendar className="size-4 mr-2" />
              Date Range
            </Button>
          </div>
        </div>

        {/* Quick Reports */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          <Card className="p-6 hover:shadow-lg transition-shadow cursor-pointer">
            <div className="flex items-center justify-between mb-4">
              <div className="bg-red-100 p-3 rounded-lg">
                <FileText className="size-6 text-red-600" />
              </div>
            </div>
            <h3 className="text-gray-900 mb-2">Donor Report</h3>
            <p className="text-gray-600 mb-4">Complete donor statistics</p>
            <Button className="w-full bg-red-600 hover:bg-red-700" size="sm">
              <Download className="size-4 mr-2" />
              Download PDF
            </Button>
          </Card>

          <Card className="p-6 hover:shadow-lg transition-shadow cursor-pointer">
            <div className="flex items-center justify-between mb-4">
              <div className="bg-blue-100 p-3 rounded-lg">
                <Building2 className="size-6 text-blue-600" />
              </div>
            </div>
            <h3 className="text-gray-900 mb-2">Hospital Report</h3>
            <p className="text-gray-600 mb-4">Hospital activity summary</p>
            <Button className="w-full bg-red-600 hover:bg-red-700" size="sm">
              <Download className="size-4 mr-2" />
              Download PDF
            </Button>
          </Card>

          <Card className="p-6 hover:shadow-lg transition-shadow cursor-pointer">
            <div className="flex items-center justify-between mb-4">
              <div className="bg-purple-100 p-3 rounded-lg">
                <FileText className="size-6 text-purple-600" />
              </div>
            </div>
            <h3 className="text-gray-900 mb-2">Monthly Summary</h3>
            <p className="text-gray-600 mb-4">Comprehensive overview</p>
            <Button className="w-full bg-red-600 hover:bg-red-700" size="sm">
              <Download className="size-4 mr-2" />
              Download PDF
            </Button>
          </Card>
        </div>

        {/* Charts */}
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <Card className="p-6">
            <div className="mb-6">
              <h2 className="text-gray-900 mb-1">Donations vs Requests</h2>
              <p className="text-gray-600">Monthly comparison</p>
            </div>
            <ResponsiveContainer width="100%" height={300}>
              <LineChart data={donationData}>
                <CartesianGrid strokeDasharray="3 3" />
                <XAxis dataKey="month" />
                <YAxis />
                <Tooltip />
                <Legend />
                <Line type="monotone" dataKey="donations" stroke="#EF4444" strokeWidth={2} />
                <Line type="monotone" dataKey="requests" stroke="#3B82F6" strokeWidth={2} />
              </LineChart>
            </ResponsiveContainer>
          </Card>

          <Card className="p-6">
            <div className="mb-6">
              <h2 className="text-gray-900 mb-1">Blood Type Distribution</h2>
              <p className="text-gray-600">Donor count by blood type</p>
            </div>
            <ResponsiveContainer width="100%" height={300}>
              <PieChart>
                <Pie
                  data={bloodTypeData}
                  cx="50%"
                  cy="50%"
                  labelLine={false}
                  label={({ name, value }) => `${name}: ${value}`}
                  outerRadius={100}
                  fill="#8884d8"
                  dataKey="value"
                >
                  {bloodTypeData.map((entry, index) => (
                    <Cell key={`cell-${index}`} fill={entry.color} />
                  ))}
                </Pie>
                <Tooltip />
              </PieChart>
            </ResponsiveContainer>
          </Card>
        </div>

        <Card className="p-6">
          <div className="mb-6">
            <h2 className="text-gray-900 mb-1">Monthly Donation Trends</h2>
            <p className="text-gray-600">Compare donations and requests over time</p>
          </div>
          <ResponsiveContainer width="100%" height={350}>
            <BarChart data={donationData}>
              <CartesianGrid strokeDasharray="3 3" />
              <XAxis dataKey="month" />
              <YAxis />
              <Tooltip />
              <Legend />
              <Bar dataKey="donations" fill="#EF4444" />
              <Bar dataKey="requests" fill="#3B82F6" />
            </BarChart>
          </ResponsiveContainer>
        </Card>

        {/* Custom Report Generator */}
        <Card className="p-6">
          <div className="mb-6">
            <h2 className="text-gray-900 mb-1">Custom Report Generator</h2>
            <p className="text-gray-600">Create a custom report with specific parameters</p>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div>
              <label className="text-gray-700 mb-2 block">Report Type</label>
              <select className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500">
                <option>Donor Report</option>
                <option>Hospital Report</option>
                <option>Blood Inventory</option>
                <option>Request History</option>
              </select>
            </div>
            <div>
              <label className="text-gray-700 mb-2 block">Date Range</label>
              <select className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500">
                <option>Last 7 Days</option>
                <option>Last 30 Days</option>
                <option>Last 3 Months</option>
                <option>Last Year</option>
                <option>Custom Range</option>
              </select>
            </div>
            <div>
              <label className="text-gray-700 mb-2 block">Format</label>
              <select className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500">
                <option>PDF</option>
                <option>Excel</option>
                <option>CSV</option>
              </select>
            </div>
          </div>

          <Button className="bg-red-600 hover:bg-red-700">
            <Download className="size-4 mr-2" />
            Generate Custom Report
          </Button>
        </Card>
      </div>
    </DashboardLayout>
  );
}