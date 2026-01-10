import DashboardLayout from '../layout/DashboardLayout';
import { LayoutDashboard, Users, Building2, Droplet, FileText, Megaphone, MessageSquare, Send, AlertCircle, CheckCircle } from 'lucide-react';
import { Card } from '../ui/card';
import { Button } from '../ui/button';
import { Badge } from '../ui/badge';
import { Textarea } from '../ui/textarea';

const sidebarItems = [
  { path: '/admin/dashboard', label: 'Dashboard', icon: LayoutDashboard },
  { path: '/admin/donors', label: 'Donors', icon: Users },
  { path: '/admin/hospitals', label: 'Hospitals', icon: Building2 },
  { path: '/admin/blood-groups', label: 'Blood Groups', icon: Droplet },
  { path: '/admin/reports', label: 'Reports', icon: FileText },
  { path: '/admin/announcements', label: 'Announcements', icon: Megaphone },
  { path: '/admin/chat', label: 'Chat', icon: MessageSquare },
];

const recentAlerts = [
  { id: 1, title: 'Emergency: O- Blood Needed', message: 'Critical shortage at City Hospital. Immediate donors needed.', type: 'Emergency', timestamp: '2 hours ago', recipients: 'O- Donors', status: 'Sent' },
  { id: 2, title: 'Blood Donation Camp - Dec 15', message: 'Organized at Community Center. All donors welcome.', type: 'Announcement', timestamp: '5 hours ago', recipients: 'All Users', status: 'Sent' },
  { id: 3, title: 'System Maintenance Notice', message: 'Portal will be down for maintenance on Saturday 2-4 AM.', type: 'Info', timestamp: '1 day ago', recipients: 'All Users', status: 'Sent' },
  { id: 4, title: 'New Hospital Registration', message: 'St. Mary Medical Center joined the network.', type: 'Announcement', timestamp: '2 days ago', recipients: 'All Donors', status: 'Sent' },
];

export default function AdminAnnouncements() {
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
          <h1 className="text-gray-900 mb-2">Announcements & Alerts</h1>
          <p className="text-gray-600">Send emergency alerts and announcements to users</p>
        </div>

        {/* Quick Stats */}
        <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
          <Card className="p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-gray-600 mb-1">Total Sent</p>
                <h2 className="text-gray-900">47</h2>
              </div>
              <div className="bg-blue-100 p-3 rounded-lg">
                <Send className="size-6 text-blue-600" />
              </div>
            </div>
          </Card>

          <Card className="p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-gray-600 mb-1">Emergency Alerts</p>
                <h2 className="text-gray-900">12</h2>
              </div>
              <div className="bg-red-100 p-3 rounded-lg">
                <AlertCircle className="size-6 text-red-600" />
              </div>
            </div>
          </Card>

          <Card className="p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-gray-600 mb-1">This Week</p>
                <h2 className="text-gray-900">8</h2>
              </div>
              <div className="bg-green-100 p-3 rounded-lg">
                <Megaphone className="size-6 text-green-600" />
              </div>
            </div>
          </Card>

          <Card className="p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-gray-600 mb-1">Reach</p>
                <h2 className="text-gray-900">1,331</h2>
              </div>
              <div className="bg-purple-100 p-3 rounded-lg">
                <Users className="size-6 text-purple-600" />
              </div>
            </div>
          </Card>
        </div>

        {/* Send New Alert */}
        <Card className="p-6">
          <div className="mb-6">
            <h2 className="text-gray-900 mb-1">Send New Alert</h2>
            <p className="text-gray-600">Compose and send announcements or emergency alerts</p>
          </div>

          <div className="space-y-4">
            <div>
              <label className="text-gray-700 mb-2 block">Alert Type</label>
              <select className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500">
                <option>Emergency Alert</option>
                <option>General Announcement</option>
                <option>Information Notice</option>
                <option>Event Notification</option>
              </select>
            </div>

            <div>
              <label className="text-gray-700 mb-2 block">Recipients</label>
              <select className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500">
                <option>All Users</option>
                <option>All Donors</option>
                <option>All Hospitals</option>
                <option>Specific Blood Type (O+)</option>
                <option>Specific Blood Type (O-)</option>
                <option>Specific Blood Type (A+)</option>
                <option>Specific Blood Type (A-)</option>
                <option>Specific Blood Type (B+)</option>
                <option>Specific Blood Type (B-)</option>
                <option>Specific Blood Type (AB+)</option>
                <option>Specific Blood Type (AB-)</option>
              </select>
            </div>

            <div>
              <label className="text-gray-700 mb-2 block">Alert Title</label>
              <input
                type="text"
                placeholder="Enter alert title..."
                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500"
              />
            </div>

            <div>
              <label className="text-gray-700 mb-2 block">Message</label>
              <Textarea
                placeholder="Enter your message here..."
                className="min-h-32"
              />
            </div>

            <div className="flex gap-3">
              <Button className="flex-1 bg-red-600 hover:bg-red-700">
                <Send className="size-4 mr-2" />
                Send Alert Now
              </Button>
              <Button variant="outline" className="flex-1">
                Schedule for Later
              </Button>
            </div>
          </div>
        </Card>

        {/* Recent Alerts */}
        <Card className="p-6">
          <div className="mb-6">
            <h2 className="text-gray-900 mb-1">Recent Alerts</h2>
            <p className="text-gray-600">View previously sent announcements and alerts</p>
          </div>

          <div className="space-y-4">
            {recentAlerts.map((alert) => (
              <div
                key={alert.id}
                className="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow"
              >
                <div className="flex items-start justify-between mb-3">
                  <div className="flex-1">
                    <div className="flex items-center gap-3 mb-2">
                      <h3 className="text-gray-900">{alert.title}</h3>
                      <Badge
                        className={
                          alert.type === 'Emergency'
                            ? 'bg-red-600 hover:bg-red-700'
                            : alert.type === 'Announcement'
                            ? 'bg-blue-600 hover:bg-blue-700'
                            : 'bg-gray-600 hover:bg-gray-700'
                        }
                      >
                        {alert.type}
                      </Badge>
                      <Badge variant="outline" className="border-green-600 text-green-600">
                        <CheckCircle className="size-3 mr-1" />
                        {alert.status}
                      </Badge>
                    </div>
                    <p className="text-gray-600 mb-2">{alert.message}</p>
                    <div className="flex items-center gap-4 text-gray-500">
                      <span>Recipients: {alert.recipients}</span>
                      <span>•</span>
                      <span>{alert.timestamp}</span>
                    </div>
                  </div>
                  <Button size="sm" variant="outline">
                    View Details
                  </Button>
                </div>
              </div>
            ))}
          </div>
        </Card>

        {/* Emergency Quick Actions */}
        <Card className="p-6 border-l-4 border-l-red-600">
          <div className="flex items-start gap-4">
            <div className="bg-red-100 p-3 rounded-lg">
              <AlertCircle className="size-6 text-red-600" />
            </div>
            <div className="flex-1">
              <h3 className="text-gray-900 mb-2">Emergency Quick Actions</h3>
              <p className="text-gray-600 mb-4">
                Send immediate alerts to donors for critical blood requirements
              </p>
              <div className="flex gap-3">
                <Button className="bg-red-600 hover:bg-red-700">
                  Alert O- Donors
                </Button>
                <Button className="bg-red-600 hover:bg-red-700">
                  Alert All Donors
                </Button>
                <Button variant="outline">
                  Custom Alert
                </Button>
              </div>
            </div>
          </div>
        </Card>
      </div>
    </DashboardLayout>
  );
}
