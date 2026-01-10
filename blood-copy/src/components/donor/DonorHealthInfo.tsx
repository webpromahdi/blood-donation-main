import DashboardLayout from '../layout/DashboardLayout';
import { LayoutDashboard, Heart, FileText, Award, MessageSquare, Settings, Save, AlertCircle, Calendar } from 'lucide-react';
import { Card } from '../ui/card';
import { Button } from '../ui/button';
import { Label } from '../ui/label';
import { Switch } from '../ui/switch';

const sidebarItems = [
  { path: '/donor/dashboard', label: 'Dashboard', icon: LayoutDashboard },
  { path: '/donor/health', label: 'Health Info', icon: Heart },
  { path: '/donor/voluntary', label: 'Donate Voluntarily', icon: Calendar },
  { path: '/donor/history', label: 'Donation History', icon: FileText },
  { path: '/donor/certificates', label: 'Certificates', icon: Award },
  { path: '/donor/chat', label: 'Chat', icon: MessageSquare },
  { path: '/donor/settings', label: 'Settings', icon: Settings },
];

export default function DonorHealthInfo() {
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
          <h1 className="text-gray-900 mb-2">Health Information</h1>
          <p className="text-gray-600">Keep your health information up to date for safe donations</p>
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
          {/* Main Form */}
          <div className="lg:col-span-2">
            <Card className="p-6">
              <div className="mb-6">
                <h2 className="text-gray-900 mb-1">Personal Health Details</h2>
                <p className="text-gray-600">Please provide accurate health information</p>
              </div>

              <form className="space-y-6">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div>
                    <label className="text-gray-700 mb-2 block">Weight (kg) *</label>
                    <input
                      type="number"
                      placeholder="Your weight"
                      defaultValue="70"
                      className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500"
                      required
                    />
                  </div>

                  <div>
                    <label className="text-gray-700 mb-2 block">Height (cm) *</label>
                    <input
                      type="number"
                      placeholder="Your height"
                      defaultValue="175"
                      className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500"
                      required
                    />
                  </div>

                  <div>
                    <label className="text-gray-700 mb-2 block">Blood Pressure (Systolic)</label>
                    <input
                      type="number"
                      placeholder="e.g., 120"
                      className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500"
                    />
                  </div>

                  <div>
                    <label className="text-gray-700 mb-2 block">Blood Pressure (Diastolic)</label>
                    <input
                      type="number"
                      placeholder="e.g., 80"
                      className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500"
                    />
                  </div>

                  <div>
                    <label className="text-gray-700 mb-2 block">Hemoglobin Level</label>
                    <input
                      type="text"
                      placeholder="e.g., 14.5 g/dL"
                      className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500"
                    />
                  </div>

                  <div>
                    <label className="text-gray-700 mb-2 block">Last Medical Checkup</label>
                    <input
                      type="date"
                      className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500"
                    />
                  </div>
                </div>

                <div className="border-t border-gray-200 pt-6">
                  <h3 className="text-gray-900 mb-4">Medical Conditions</h3>
                  <p className="text-gray-600 mb-4">Please indicate if you have any of the following conditions:</p>

                  <div className="space-y-3">
                    {[
                      'Diabetes',
                      'Hypertension',
                      'Heart Disease',
                      'Asthma',
                      'Allergies',
                      'Recent Surgery (within 6 months)',
                      'Currently on Medication',
                    ].map((condition) => (
                      <div key={condition} className="flex items-center justify-between p-3 border border-gray-200 rounded-lg">
                        <Label htmlFor={condition} className="cursor-pointer">
                          {condition}
                        </Label>
                        <Switch id={condition} />
                      </div>
                    ))}
                  </div>
                </div>

                <div className="border-t border-gray-200 pt-6">
                  <h3 className="text-gray-900 mb-4">Lifestyle Information</h3>

                  <div className="space-y-4">
                    <div>
                      <label className="text-gray-700 mb-2 block">Do you smoke?</label>
                      <select className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500">
                        <option>No</option>
                        <option>Yes, occasionally</option>
                        <option>Yes, regularly</option>
                      </select>
                    </div>

                    <div>
                      <label className="text-gray-700 mb-2 block">Alcohol Consumption</label>
                      <select className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500">
                        <option>None</option>
                        <option>Occasional</option>
                        <option>Regular</option>
                      </select>
                    </div>

                    <div>
                      <label className="text-gray-700 mb-2 block">Exercise Frequency</label>
                      <select className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500">
                        <option>Daily</option>
                        <option>3-4 times/week</option>
                        <option>1-2 times/week</option>
                        <option>Rarely</option>
                      </select>
                    </div>
                  </div>
                </div>

                <div className="flex gap-4 pt-6">
                  <Button type="submit" className="flex-1 bg-red-600 hover:bg-red-700">
                    <Save className="size-4 mr-2" />
                    Save Health Information
                  </Button>
                  <Button type="button" variant="outline" className="flex-1">
                    Cancel
                  </Button>
                </div>
              </form>
            </Card>
          </div>

          {/* Side Info */}
          <div className="space-y-6">
            <Card className="p-6 bg-green-50 border-green-200">
              <div className="flex items-start gap-3 mb-4">
                <Heart className="size-6 text-green-600 flex-shrink-0 mt-1" />
                <div>
                  <h3 className="text-gray-900 mb-2">Eligibility Status</h3>
                  <p className="text-green-600 mb-4">You are currently eligible to donate blood</p>
                </div>
              </div>
              <div className="space-y-2 text-gray-700">
                <p>✓ Weight: Above 50kg</p>
                <p>✓ Age: 18-65 years</p>
                <p>✓ Last donation: Over 3 months</p>
                <p>✓ Health: No contraindications</p>
              </div>
            </Card>

            <Card className="p-6">
              <div className="mb-4">
                <h3 className="text-gray-900 mb-2">Health Guidelines</h3>
              </div>
              <ul className="space-y-3 text-gray-600">
                <li className="flex items-start gap-2">
                  <span className="text-red-600 mt-1">•</span>
                  <span>Maintain weight above 50 kg</span>
                </li>
                <li className="flex items-start gap-2">
                  <span className="text-red-600 mt-1">•</span>
                  <span>Stay hydrated before donation</span>
                </li>
                <li className="flex items-start gap-2">
                  <span className="text-red-600 mt-1">•</span>
                  <span>Eat a healthy meal before donating</span>
                </li>
                <li className="flex items-start gap-2">
                  <span className="text-red-600 mt-1">•</span>
                  <span>Avoid alcohol 24 hours before</span>
                </li>
                <li className="flex items-start gap-2">
                  <span className="text-red-600 mt-1">•</span>
                  <span>Get adequate rest before donation</span>
                </li>
              </ul>
            </Card>

            <Card className="p-6 border-l-4 border-l-yellow-500">
              <div className="flex items-start gap-3">
                <AlertCircle className="size-6 text-yellow-600 flex-shrink-0 mt-1" />
                <div>
                  <h3 className="text-gray-900 mb-2">Important</h3>
                  <p className="text-gray-600">
                    Always provide accurate health information. Inaccurate information can affect both you and the blood recipient.
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
