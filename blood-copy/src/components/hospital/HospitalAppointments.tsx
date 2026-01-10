import DashboardLayout from '../layout/DashboardLayout';
import { LayoutDashboard, Users, Calendar, MessageSquare, Droplet, CheckCircle, Clock, XCircle } from 'lucide-react';
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

const appointments = [
  { id: 'APT001', donor: 'John Smith', bloodType: 'O+', date: '2024-11-25', time: '10:00 AM', status: 'Confirmed', requestId: 'REQ001' },
  { id: 'APT002', donor: 'Maria Garcia', bloodType: 'A+', date: '2024-11-25', time: '11:30 AM', status: 'Confirmed', requestId: 'REQ002' },
  { id: 'APT003', donor: 'David Lee', bloodType: 'B-', date: '2024-11-26', time: '09:00 AM', status: 'Pending', requestId: 'REQ003' },
  { id: 'APT004', donor: 'Sarah Johnson', bloodType: 'O-', date: '2024-11-26', time: '02:00 PM', status: 'Confirmed', requestId: 'REQ004' },
  { id: 'APT005', donor: 'Michael Brown', bloodType: 'AB+', date: '2024-11-27', time: '03:30 PM', status: 'Pending', requestId: 'REQ005' },
  { id: 'APT006', donor: 'Emma Wilson', bloodType: 'A-', date: '2024-11-23', time: '01:00 PM', status: 'Completed', requestId: 'REQ006' },
];

export default function HospitalAppointments() {
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
          <h1 className="text-gray-900 mb-2">Appointments</h1>
          <p className="text-gray-600">Manage donation appointments and schedules</p>
        </div>

        {/* Stats */}
        <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
          <Card className="p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-gray-600 mb-1">Total Appointments</p>
                <h2 className="text-gray-900">{appointments.length}</h2>
              </div>
              <div className="bg-blue-100 p-3 rounded-lg">
                <Calendar className="size-6 text-blue-600" />
              </div>
            </div>
          </Card>

          <Card className="p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-gray-600 mb-1">Confirmed</p>
                <h2 className="text-gray-900">
                  {appointments.filter(a => a.status === 'Confirmed').length}
                </h2>
              </div>
              <div className="bg-green-100 p-3 rounded-lg">
                <CheckCircle className="size-6 text-green-600" />
              </div>
            </div>
          </Card>

          <Card className="p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-gray-600 mb-1">Pending</p>
                <h2 className="text-gray-900">
                  {appointments.filter(a => a.status === 'Pending').length}
                </h2>
              </div>
              <div className="bg-yellow-100 p-3 rounded-lg">
                <Clock className="size-6 text-yellow-600" />
              </div>
            </div>
          </Card>

          <Card className="p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-gray-600 mb-1">Completed</p>
                <h2 className="text-gray-900">
                  {appointments.filter(a => a.status === 'Completed').length}
                </h2>
              </div>
              <div className="bg-purple-100 p-3 rounded-lg">
                <CheckCircle className="size-6 text-purple-600" />
              </div>
            </div>
          </Card>
        </div>

        {/* Appointments Table */}
        <Card className="p-6">
          <div className="mb-6 flex items-center justify-between">
            <div>
              <h2 className="text-gray-900 mb-1">All Appointments</h2>
              <p className="text-gray-600">View and manage donation appointments</p>
            </div>
            <div className="flex gap-3">
              <Button variant="outline">Filter</Button>
              <Button className="bg-red-600 hover:bg-red-700">
                <Calendar className="size-4 mr-2" />
                Schedule New
              </Button>
            </div>
          </div>

          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Appointment ID</TableHead>
                <TableHead>Donor Name</TableHead>
                <TableHead>Blood Type</TableHead>
                <TableHead>Date</TableHead>
                <TableHead>Time</TableHead>
                <TableHead>Request ID</TableHead>
                <TableHead>Status</TableHead>
                <TableHead>Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {appointments.map((appointment) => (
                <TableRow key={appointment.id}>
                  <TableCell>{appointment.id}</TableCell>
                  <TableCell>
                    <div className="flex items-center gap-3">
                      <div className="size-10 bg-red-100 rounded-full flex items-center justify-center">
                        <Users className="size-5 text-red-600" />
                      </div>
                      <span className="text-gray-900">{appointment.donor}</span>
                    </div>
                  </TableCell>
                  <TableCell>
                    <Badge variant="outline" className="border-red-600 text-red-600">
                      {appointment.bloodType}
                    </Badge>
                  </TableCell>
                  <TableCell>{appointment.date}</TableCell>
                  <TableCell>{appointment.time}</TableCell>
                  <TableCell>
                    <Badge variant="outline">{appointment.requestId}</Badge>
                  </TableCell>
                  <TableCell>
                    <Badge
                      variant="outline"
                      className={
                        appointment.status === 'Confirmed'
                          ? 'border-green-600 text-green-600'
                          : appointment.status === 'Pending'
                          ? 'border-yellow-600 text-yellow-600'
                          : 'border-gray-600 text-gray-600'
                      }
                    >
                      {appointment.status === 'Confirmed' && <CheckCircle className="size-3 mr-1" />}
                      {appointment.status === 'Pending' && <Clock className="size-3 mr-1" />}
                      {appointment.status}
                    </Badge>
                  </TableCell>
                  <TableCell>
                    <div className="flex gap-2">
                      {appointment.status === 'Pending' && (
                        <>
                          <Button size="sm" className="bg-green-600 hover:bg-green-700">
                            <CheckCircle className="size-4 mr-1" />
                            Confirm
                          </Button>
                          <Button size="sm" variant="outline" className="border-red-600 text-red-600 hover:bg-red-50">
                            <XCircle className="size-4 mr-1" />
                            Cancel
                          </Button>
                        </>
                      )}
                      {appointment.status === 'Confirmed' && (
                        <>
                          <Button size="sm" variant="outline">
                            View
                          </Button>
                          <Button size="sm" className="bg-red-600 hover:bg-red-700">
                            <MessageSquare className="size-4 mr-1" />
                            Chat
                          </Button>
                        </>
                      )}
                      {appointment.status === 'Completed' && (
                        <Button size="sm" variant="outline">
                          View Details
                        </Button>
                      )}
                    </div>
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </Card>

        {/* Today's Schedule */}
        <Card className="p-6 bg-gradient-to-r from-blue-50 to-white border-l-4 border-l-blue-600">
          <div className="mb-4">
            <h2 className="text-gray-900 mb-1">Today's Schedule</h2>
            <p className="text-gray-600">Appointments scheduled for today</p>
          </div>

          <div className="space-y-3">
            {appointments
              .filter(a => a.date === '2024-11-25')
              .map((appointment) => (
                <div
                  key={appointment.id}
                  className="flex items-center justify-between p-4 bg-white rounded-lg border border-gray-200"
                >
                  <div className="flex items-center gap-4">
                    <div className="size-12 bg-red-100 rounded-full flex items-center justify-center">
                      <Users className="size-6 text-red-600" />
                    </div>
                    <div>
                      <h3 className="text-gray-900">{appointment.donor}</h3>
                      <p className="text-gray-600">
                        {appointment.time} • {appointment.bloodType} • {appointment.id}
                      </p>
                    </div>
                  </div>
                  <div className="flex items-center gap-3">
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
                    <Button size="sm" className="bg-red-600 hover:bg-red-700">
                      View
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
