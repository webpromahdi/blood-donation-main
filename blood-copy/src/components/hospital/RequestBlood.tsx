import DashboardLayout from '../layout/DashboardLayout';
import { LayoutDashboard, Users, Calendar, MessageSquare, Droplet, AlertCircle, Send } from 'lucide-react';
import { Card } from '../ui/card';
import { Button } from '../ui/button';
import { Label } from '../ui/label';
import { Switch } from '../ui/switch';
import { Textarea } from '../ui/textarea';

const sidebarItems = [
  { path: '/hospital/dashboard', label: 'Dashboard', icon: LayoutDashboard },
  { path: '/hospital/request', label: 'Request Blood', icon: Droplet },
  { path: '/hospital/donors', label: 'Donor List', icon: Users },
  { path: '/hospital/voluntary', label: 'Voluntary Requests', icon: Calendar },
  { path: '/hospital/appointments', label: 'Appointments', icon: Calendar },
  { path: '/hospital/chat', label: 'Chat', icon: MessageSquare },
];

export default function RequestBlood() {
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
          <h1 className="text-gray-900 mb-2">Request Blood</h1>
          <p className="text-gray-600">Submit a new blood request to the donor network</p>
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
          {/* Main Form */}
          <div className="lg:col-span-2">
            <Card className="p-6">
              <div className="mb-6">
                <h2 className="text-gray-900 mb-1">Blood Request Form</h2>
                <p className="text-gray-600">Fill in the details for your blood requirement</p>
              </div>

              <form className="space-y-6">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div>
                    <label className="text-gray-700 mb-2 block">Patient Name *</label>
                    <input
                      type="text"
                      placeholder="Enter patient name"
                      className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500"
                      required
                    />
                  </div>

                  <div>
                    <label className="text-gray-700 mb-2 block">Patient ID</label>
                    <input
                      type="text"
                      placeholder="Optional patient ID"
                      className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500"
                    />
                  </div>

                  <div>
                    <label className="text-gray-700 mb-2 block">Blood Type *</label>
                    <select
                      className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500"
                      required
                    >
                      <option value="">Select blood type</option>
                      <option value="O+">O+</option>
                      <option value="O-">O-</option>
                      <option value="A+">A+</option>
                      <option value="A-">A-</option>
                      <option value="B+">B+</option>
                      <option value="B-">B-</option>
                      <option value="AB+">AB+</option>
                      <option value="AB-">AB-</option>
                    </select>
                  </div>

                  <div>
                    <label className="text-gray-700 mb-2 block">Quantity (Units) *</label>
                    <input
                      type="number"
                      placeholder="Number of units"
                      min="1"
                      className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500"
                      required
                    />
                  </div>

                  <div>
                    <label className="text-gray-700 mb-2 block">Required By Date *</label>
                    <input
                      type="date"
                      className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500"
                      required
                    />
                  </div>

                  <div>
                    <label className="text-gray-700 mb-2 block">Contact Number *</label>
                    <input
                      type="tel"
                      placeholder="Emergency contact"
                      className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500"
                      required
                    />
                  </div>
                </div>

                <div>
                  <label className="text-gray-700 mb-2 block">Purpose / Medical Reason</label>
                  <Textarea
                    placeholder="Brief description of why blood is needed..."
                    className="min-h-24"
                  />
                </div>

                <div className="flex items-center justify-between p-4 bg-red-50 rounded-lg border border-red-200">
                  <div className="flex items-center gap-3">
                    <AlertCircle className="size-6 text-red-600" />
                    <div>
                      <Label htmlFor="emergency-toggle" className="text-gray-900 cursor-pointer">
                        Mark as Emergency
                      </Label>
                      <p className="text-gray-600">
                        Emergency requests will be prioritized and sent to all available donors
                      </p>
                    </div>
                  </div>
                  <Switch id="emergency-toggle" />
                </div>

                <div className="flex gap-4 pt-6">
                  <Button type="submit" className="flex-1 bg-red-600 hover:bg-red-700">
                    <Send className="size-4 mr-2" />
                    Submit Request
                  </Button>
                </div>
              </form>
            </Card>
          </div>

          {/* Side Info */}
          <div className="space-y-6">
            <Card className="p-6">
              <div className="mb-4">
                <h3 className="text-gray-900 mb-2">Request Guidelines</h3>
              </div>
              <ul className="space-y-3 text-gray-600">
                <li className="flex items-start gap-2">
                  <span className="text-red-600 mt-1">•</span>
                  <span>Provide accurate patient information</span>
                </li>
                <li className="flex items-start gap-2">
                  <span className="text-red-600 mt-1">•</span>
                  <span>Specify exact blood type and quantity</span>
                </li>
                <li className="flex items-start gap-2">
                  <span className="text-red-600 mt-1">•</span>
                  <span>Use emergency flag only for critical cases</span>
                </li>
                <li className="flex items-start gap-2">
                  <span className="text-red-600 mt-1">•</span>
                  <span>Ensure contact information is correct</span>
                </li>
                <li className="flex items-start gap-2">
                  <span className="text-red-600 mt-1">•</span>
                  <span>Provide medical reason for verification</span>
                </li>
              </ul>
            </Card>

            <Card className="p-6 border-l-4 border-l-yellow-500">
              <div className="flex items-start gap-3">
                <AlertCircle className="size-6 text-yellow-600 flex-shrink-0 mt-1" />
                <div>
                  <h3 className="text-gray-900 mb-2">Emergency Protocol</h3>
                  <p className="text-gray-600">
                    Emergency requests will trigger immediate notifications to all compatible donors in the system
                  </p>
                </div>
              </div>
            </Card>
          </div>
        </div>
      </div>
    </DashboardLayout>
  );
}