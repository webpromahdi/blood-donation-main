import DashboardLayout from '../layout/DashboardLayout';
import { LayoutDashboard, Heart, FileText, Award, MessageSquare, Settings, Calendar, AlertCircle, CheckCircle, Clock, Droplet } from 'lucide-react';
import { Card } from '../ui/card';
import { Badge } from '../ui/badge';
import { Button } from '../ui/button';

const sidebarItems = [
  { path: '/donor/dashboard', label: 'Dashboard', icon: LayoutDashboard },
  { path: '/donor/health', label: 'Health Info', icon: Heart },
  { path: '/donor/voluntary', label: 'Donate Voluntarily', icon: Calendar },
  { path: '/donor/history', label: 'Donation History', icon: FileText },
  { path: '/donor/certificates', label: 'Certificates', icon: Award },
  { path: '/donor/chat', label: 'Chat', icon: MessageSquare },
  { path: '/donor/settings', label: 'Settings', icon: Settings },
];

const upcomingAppointments = [
  { id: 'APT001', hospital: 'City Hospital', date: '2024-11-25', time: '10:00 AM', status: 'Confirmed' },
  { id: 'APT002', hospital: 'St. Mary Medical', date: '2024-12-01', time: '02:00 PM', status: 'Pending' },
];

const emergencyAlerts = [
  { id: 1, bloodType: 'O+', hospital: 'Regional Hospital', urgency: 'Critical', time: '2 hours ago' },
  { id: 2, bloodType: 'O+', hospital: 'Community Medical', urgency: 'Urgent', time: '5 hours ago' },
];

const nonEmergencyRequests = [
  { id: 1, bloodType: 'O+', hospital: 'General Hospital', requestType: 'Routine', time: '1 day ago', units: 2 },
  { id: 2, bloodType: 'O+', hospital: 'Valley Medical Center', requestType: 'Scheduled', time: '2 days ago', units: 1 },
  { id: 3, bloodType: 'O+', hospital: 'Lakeside Clinic', requestType: 'Routine', time: '3 days ago', units: 3 },
];

export default function DonorDashboard() {
  return (
    <DashboardLayout
      sidebarItems={sidebarItems}
      sidebarTitle="Donor Portal"
      userName="John Smith"
      userRole="Blood Donor"
      notifications={4}
    >
      <div className="space-y-8">
        <div>
          <h1 className="text-gray-900 mb-2">Donor Dashboard</h1>
          <p className="text-gray-600">Welcome back, John! Thank you for saving lives.</p>
        </div>

        {/* Stats Cards */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
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
            <p className="text-green-600 mt-2">Last donation: 2 weeks ago</p>
          </Card>

          <Card className="p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-gray-600 mb-1">Lives Saved</p>
                <h2 className="text-gray-900">36+</h2>
              </div>
              <div className="bg-green-100 p-3 rounded-lg">
                <Heart className="size-6 text-green-600 fill-green-600" />
              </div>
            </div>
            <p className="text-green-600 mt-2">Impact of your donations</p>
          </Card>

          <Card className="p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-gray-600 mb-1">Next Eligible</p>
                <h2 className="text-gray-900">2 weeks</h2>
              </div>
              <div className="bg-blue-100 p-3 rounded-lg">
                <Calendar className="size-6 text-blue-600" />
              </div>
            </div>
            <p className="text-blue-600 mt-2">You can donate again</p>
          </Card>

          <Card className="p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-gray-600 mb-1">Blood Type</p>
                <h2 className="text-gray-900">O+</h2>
              </div>
              <div className="bg-purple-100 p-3 rounded-lg">
                <Droplet className="size-6 text-purple-600" />
              </div>
            </div>
            <p className="text-purple-600 mt-2">Universal donor</p>
          </Card>
        </div>

        {/* Emergency Alerts */}
        <Card className="p-6 border-l-4 border-l-red-600 bg-gradient-to-r from-red-50 to-white">
          <div className="flex items-start gap-4 mb-4">
            <div className="bg-red-100 p-3 rounded-lg">
              <AlertCircle className="size-6 text-red-600" />
            </div>
            <div className="flex-1">
              <h2 className="text-gray-900 mb-2">Emergency Requests Matching Your Blood Type</h2>
              <p className="text-gray-600 mb-4">Hospitals near you need your help urgently</p>
            </div>
          </div>

          <div className="space-y-3">
            {emergencyAlerts.map((alert) => (
              <div key={alert.id} className="flex items-center justify-between p-4 bg-white rounded-lg border border-red-200">
                <div className="flex items-center gap-4">
                  <div className="size-12 bg-red-100 rounded-full flex items-center justify-center">
                    <Droplet className="size-6 text-red-600 fill-red-600" />
                  </div>
                  <div>
                    <div className="flex items-center gap-2 mb-1">
                      <h3 className="text-gray-900">{alert.hospital}</h3>
                      <Badge className="bg-red-600 hover:bg-red-700">{alert.urgency}</Badge>
                    </div>
                    <p className="text-gray-600">Blood Type: <strong>{alert.bloodType}</strong> • {alert.time}</p>
                  </div>
                </div>
                <div className="flex gap-2">
                  <Button size="sm" variant="outline">Later</Button>
                  <Button size="sm" className="bg-red-600 hover:bg-red-700">
                    <CheckCircle className="size-4 mr-1" />
                    Accept
                  </Button>
                </div>
              </div>
            ))}
          </div>
        </Card>

        {/* Non-Emergency Requests */}
        <Card className="p-6 border-l-4 border-l-blue-600 bg-gradient-to-r from-blue-50 to-white">
          <div className="flex items-start gap-4 mb-4">
            <div className="bg-blue-100 p-3 rounded-lg">
              <Clock className="size-6 text-blue-600" />
            </div>
            <div className="flex-1">
              <h2 className="text-gray-900 mb-2">Non-Emergency Requests Matching Your Blood Type</h2>
              <p className="text-gray-600 mb-4">Routine blood requests from hospitals in your area</p>
            </div>
          </div>

          <div className="space-y-3">
            {nonEmergencyRequests.map((request) => (
              <div key={request.id} className="flex items-center justify-between p-4 bg-white rounded-lg border border-blue-200">
                <div className="flex items-center gap-4">
                  <div className="size-12 bg-blue-100 rounded-full flex items-center justify-center">
                    <Droplet className="size-6 text-blue-600" />
                  </div>
                  <div>
                    <div className="flex items-center gap-2 mb-1">
                      <h3 className="text-gray-900">{request.hospital}</h3>
                      <Badge variant="outline" className="border-blue-600 text-blue-600">{request.requestType}</Badge>
                    </div>
                    <p className="text-gray-600">Blood Type: <strong>{request.bloodType}</strong> • {request.units} {request.units === 1 ? 'unit' : 'units'} • {request.time}</p>
                  </div>
                </div>
                <div className="flex gap-2">
                  <Button size="sm" variant="outline">Decline</Button>
                  <Button size="sm" className="bg-blue-600 hover:bg-blue-700">
                    <CheckCircle className="size-4 mr-1" />
                    Accept
                  </Button>
                </div>
              </div>
            ))}
          </div>
        </Card>

        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
          {/* Upcoming Appointments */}
          <Card className="p-6">
            <div className="mb-6">
              <h2 className="text-gray-900 mb-1">Upcoming Appointments</h2>
              <p className="text-gray-600">Your scheduled donation appointments</p>
            </div>

            <div className="space-y-4">
              {upcomingAppointments.map((appointment) => (
                <div key={appointment.id} className="p-4 border border-gray-200 rounded-lg hover:shadow-md transition-shadow">
                  <div className="flex items-start justify-between mb-3">
                    <div className="flex items-center gap-3">
                      <div className="size-10 bg-blue-100 rounded-full flex items-center justify-center">
                        <Calendar className="size-5 text-blue-600" />
                      </div>
                      <div>
                        <h3 className="text-gray-900">{appointment.hospital}</h3>
                        <p className="text-gray-600">{appointment.date} at {appointment.time}</p>
                      </div>
                    </div>
                    <Badge
                      variant="outline"
                      className={
                        appointment.status === 'Confirmed'
                          ? 'border-green-600 text-green-600'
                          : 'border-yellow-600 text-yellow-600'
                      }
                    >
                      {appointment.status}
                    </Badge>
                  </div>
                  <div className="flex gap-2">
                    <Button size="sm" variant="outline" className="flex-1">
                      Reschedule
                    </Button>
                    <Button size="sm" className="flex-1 bg-red-600 hover:bg-red-700">
                      View Details
                    </Button>
                  </div>
                </div>
              ))}

              {upcomingAppointments.length === 0 && (
                <div className="text-center py-8 text-gray-500">
                  <Calendar className="size-12 mx-auto mb-3 text-gray-400" />
                  <p>No upcoming appointments</p>
                </div>
              )}
            </div>
          </Card>

          {/* Donation Statistics */}
          <Card className="p-6">
            <div className="mb-6">
              <h2 className="text-gray-900 mb-1">Donation Statistics</h2>
              <p className="text-gray-600">Your contribution over time</p>
            </div>

            <div className="space-y-4">
              <div className="p-4 bg-gradient-to-r from-red-50 to-white rounded-lg border border-red-100">
                <div className="flex items-center justify-between mb-2">
                  <span className="text-gray-700">This Year</span>
                  <span className="text-red-600">4 donations</span>
                </div>
                <div className="w-full bg-gray-200 rounded-full h-2">
                  <div className="bg-red-600 h-2 rounded-full" style={{ width: '33%' }}></div>
                </div>
                <p className="text-gray-500 mt-1">Goal: 12 donations/year</p>
              </div>

              <div className="grid grid-cols-2 gap-4">
                <div className="p-4 bg-blue-50 rounded-lg border border-blue-100">
                  <p className="text-gray-600 mb-1">Total Volume</p>
                  <h3 className="text-gray-900">6 Liters</h3>
                </div>
                <div className="p-4 bg-green-50 rounded-lg border border-green-100">
                  <p className="text-gray-600 mb-1">Certificates</p>
                  <h3 className="text-gray-900">3 Awards</h3>
                </div>
              </div>

              <div className="pt-4 border-t border-gray-200">
                <h3 className="text-gray-900 mb-3">Recent Milestones</h3>
                <div className="space-y-2">
                  <div className="flex items-center gap-3">
                    <div className="size-8 bg-yellow-100 rounded-full flex items-center justify-center">
                      <Award className="size-4 text-yellow-600" />
                    </div>
                    <div>
                      <p className="text-gray-900">10 Donations Milestone</p>
                      <p className="text-gray-500">Achieved 1 month ago</p>
                    </div>
                  </div>
                  <div className="flex items-center gap-3">
                    <div className="size-8 bg-blue-100 rounded-full flex items-center justify-center">
                      <Heart className="size-4 text-blue-600" />
                    </div>
                    <div>
                      <p className="text-gray-900">Life Saver Badge</p>
                      <p className="text-gray-500">Earned 3 months ago</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </Card>
        </div>

        {/* Quick Actions */}
        <Card className="p-6">
          <div className="mb-4">
            <h2 className="text-gray-900 mb-1">Quick Actions</h2>
            <p className="text-gray-600">Manage your donor profile</p>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            <Button variant="outline" className="h-auto py-4 flex-col gap-2">
              <Heart className="size-6 text-red-600" />
              <span>Update Health Info</span>
            </Button>
            <Button variant="outline" className="h-auto py-4 flex-col gap-2">
              <Calendar className="size-6 text-blue-600" />
              <span>Schedule Donation</span>
            </Button>
            <Button variant="outline" className="h-auto py-4 flex-col gap-2">
              <Award className="size-6 text-yellow-600" />
              <span>Download Certificates</span>
            </Button>
          </div>
        </Card>
      </div>
    </DashboardLayout>
  );
}
