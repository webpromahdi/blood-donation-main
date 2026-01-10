import DashboardLayout from '../layout/DashboardLayout';
import { Send, AlertCircle, Phone, MessageSquare, ClipboardList } from 'lucide-react';
import { Card } from '../ui/card';
import { Button } from '../ui/button';
import { Label } from '../ui/label';
import { Switch } from '../ui/switch';
import { Textarea } from '../ui/textarea';

const sidebarItems = [
  { path: '/seeker/request', label: 'Submit Blood Request', icon: Send },
  { path: '/seeker/tracking', label: 'Track Request', icon: ClipboardList },
  { path: '/seeker/chat', label: 'Chat with Donor', icon: MessageSquare },
];

export default function SeekerRequest() {
  return (
    <DashboardLayout
      sidebarItems={sidebarItems}
      sidebarTitle="Seeker Portal"
      userName="Guest User"
      userRole="Blood Seeker"
      notifications={0}
    >
      <div className="space-y-8">
        <div>
          <h1 className="text-gray-900 mb-2">Request Blood</h1>
          <p className="text-gray-600">Fill out the form below to request blood from donors in your area</p>
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
          {/* Main Form */}
          <div className="lg:col-span-2">
            <Card className="p-8">
              <div className="mb-8">
                <h2 className="text-gray-900 mb-2">Blood Request Form</h2>
                <p className="text-gray-600">All fields marked with * are required</p>
              </div>

              <form className="space-y-6">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div>
                    <label className="text-gray-700 mb-2 block">Patient Name *</label>
                    <input
                      type="text"
                      placeholder="Enter patient name"
                      className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500"
                      required
                    />
                  </div>

                  <div>
                    <label className="text-gray-700 mb-2 block">Age *</label>
                    <input
                      type="number"
                      placeholder="Patient age"
                      className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500"
                      required
                    />
                  </div>

                  <div>
                    <label className="text-gray-700 mb-2 block">Contact Phone *</label>
                    <input
                      type="tel"
                      placeholder="Your contact number"
                      className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500"
                      required
                    />
                  </div>

                  <div>
                    <label className="text-gray-700 mb-2 block">Email</label>
                    <input
                      type="email"
                      placeholder="Your email address"
                      className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500"
                    />
                  </div>

                  <div>
                    <label className="text-gray-700 mb-2 block">Blood Type Needed *</label>
                    <select
                      className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500"
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
                      placeholder="Number of units needed"
                      min="1"
                      className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500"
                      required
                    />
                  </div>

                  <div>
                    <label className="text-gray-700 mb-2 block">Hospital *</label>
                    <input
                      type="text"
                      placeholder="Hospital name"
                      className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500"
                      required
                    />
                  </div>

                  <div>
                    <label className="text-gray-700 mb-2 block">City *</label>
                    <input
                      type="text"
                      placeholder="Your city"
                      className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500"
                      required
                    />
                  </div>

                  <div className="md:col-span-2">
                    <label className="text-gray-700 mb-2 block">Required By Date *</label>
                    <input
                      type="date"
                      className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500"
                      required
                    />
                  </div>
                </div>

                <div>
                  <label className="text-gray-700 mb-2 block">Medical Reason / Additional Information</label>
                  <Textarea
                    placeholder="Please provide details about why blood is needed..."
                    className="min-h-24"
                  />
                </div>

                <div className="flex items-center justify-between p-4 bg-red-50 rounded-lg border border-red-200">
                  <div className="flex items-center gap-3">
                    <AlertCircle className="size-6 text-red-600 flex-shrink-0" />
                    <div>
                      <Label htmlFor="emergency-toggle" className="text-gray-900 cursor-pointer">
                        This is an Emergency
                      </Label>
                      <p className="text-gray-600">
                        Emergency requests will be prioritized and sent to all compatible donors immediately
                      </p>
                    </div>
                  </div>
                  <Switch id="emergency-toggle" />
                </div>

                <div className="flex gap-4 pt-6">
                  <Button type="submit" className="flex-1 bg-red-600 hover:bg-red-700 h-12">
                    <Send className="size-5 mr-2" />
                    Submit Blood Request
                  </Button>
                </div>
              </form>
            </Card>
          </div>

          {/* Side Info */}
          <div className="space-y-6">
            <Card className="p-6 bg-blue-50 border-blue-200">
              <div className="flex items-start gap-3 mb-4">
                <AlertCircle className="size-6 text-blue-600 flex-shrink-0 mt-1" />
                <div>
                  <h3 className="text-gray-900 mb-2">How it Works</h3>
                </div>
              </div>
              <ol className="space-y-3 text-gray-700">
                <li className="flex gap-3">
                  <span className="text-blue-600">1.</span>
                  <span>Fill out the blood request form</span>
                </li>
                <li className="flex gap-3">
                  <span className="text-blue-600">2.</span>
                  <span>System matches with compatible donors</span>
                </li>
                <li className="flex gap-3">
                  <span className="text-blue-600">3.</span>
                  <span>Donors will be notified immediately</span>
                </li>
                <li className="flex gap-3">
                  <span className="text-blue-600">4.</span>
                  <span>You'll receive confirmation once donors respond</span>
                </li>
                <li className="flex gap-3">
                  <span className="text-blue-600">5.</span>
                  <span>Track your request status online</span>
                </li>
              </ol>
            </Card>

            <Card className="p-6">
              <h3 className="text-gray-900 mb-4">Important Guidelines</h3>
              <ul className="space-y-3 text-gray-600">
                <li className="flex items-start gap-2">
                  <span className="text-red-600 mt-1">•</span>
                  <span>Provide accurate contact information</span>
                </li>
                <li className="flex items-start gap-2">
                  <span className="text-red-600 mt-1">•</span>
                  <span>Specify correct blood type</span>
                </li>
                <li className="flex items-start gap-2">
                  <span className="text-red-600 mt-1">•</span>
                  <span>Mention hospital details clearly</span>
                </li>
                <li className="flex items-start gap-2">
                  <span className="text-red-600 mt-1">•</span>
                  <span>Use emergency flag only for critical cases</span>
                </li>
              </ul>
            </Card>

            <Card className="p-6 border-l-4 border-l-red-600">
              <div className="flex items-start gap-3">
                <Phone className="size-6 text-red-600 flex-shrink-0 mt-1" />
                <div>
                  <h3 className="text-gray-900 mb-2">Emergency Hotline</h3>
                  <p className="text-red-600 mb-2">1-800-BLOOD-NOW</p>
                  <p className="text-gray-600">Available 24/7 for urgent requests</p>
                </div>
              </div>
            </Card>
          </div>
        </div>
      </div>
    </DashboardLayout>
  );
}
