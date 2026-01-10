import { useNavigate } from 'react-router-dom';
import { Button } from './ui/button';
import { Card } from './ui/card';
import { Shield, Building2, Heart, Search, ChevronRight, ArrowLeft } from 'lucide-react';
import { useState } from 'react';

export default function PortalLogin() {
  const navigate = useNavigate();
  const [selectedRole, setSelectedRole] = useState('donor');

  const loginOptions = [
    {
      value: 'admin',
      title: 'Admin',
      icon: Shield,
      path: '/admin/dashboard',
    },
    {
      value: 'hospital',
      title: 'Hospital',
      icon: Building2,
      path: '/hospital/dashboard',
    },
    {
      value: 'donor',
      title: 'Donor',
      icon: Heart,
      path: '/donor/dashboard',
    },
    {
      value: 'seeker',
      title: 'Seeker',
      icon: Search,
      path: '/seeker/request',
    }
  ];

  const handleLogin = () => {
    const selectedOption = loginOptions.find(opt => opt.value === selectedRole);
    if (selectedOption) {
      navigate(selectedOption.path);
    }
  };

  return (
    <div className="min-h-screen bg-gradient-to-br from-red-50 via-white to-red-50 flex items-center justify-center p-6">
      <div className="w-full max-w-6xl">
        {/* Back to Home */}
        <div className="mb-8">
          <Button
            variant="outline"
            onClick={() => navigate('/')}
            className="border-gray-300 text-gray-600 hover:bg-gray-50"
          >
            <ArrowLeft className="size-4 mr-2" />
            Back to Home
          </Button>
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
          {/* Left Side - Branding */}
          <div className="text-center lg:text-left">
            <div className="flex items-center justify-center lg:justify-start gap-3 mb-6">
              <div className="bg-red-600 p-3 rounded-lg">
                <Heart className="size-8 text-white fill-white" />
              </div>
              <div>
                <div className="text-red-600 text-2xl">BloodConnect</div>
                <div className="text-gray-500">Portal Access</div>
              </div>
            </div>
            <h1 className="text-gray-900 mb-4">Welcome Back</h1>
            <p className="text-gray-600 mb-6">
              Select your role and login to access your dedicated portal. 
              Manage donations, requests, and save lives together.
            </p>
            <div className="space-y-3 text-gray-600">
              <div className="flex items-center gap-3">
                <div className="bg-red-100 p-2 rounded-full">
                  <Shield className="size-4 text-red-600" />
                </div>
                <span>Secure and encrypted access</span>
              </div>
              <div className="flex items-center gap-3">
                <div className="bg-red-100 p-2 rounded-full">
                  <Heart className="size-4 text-red-600" />
                </div>
                <span>Real-time blood request updates</span>
              </div>
              <div className="flex items-center gap-3">
                <div className="bg-red-100 p-2 rounded-full">
                  <Building2 className="size-4 text-red-600" />
                </div>
                <span>24/7 emergency support</span>
              </div>
            </div>
          </div>

          {/* Right Side - Login Card */}
          <div>
            <Card className="p-8 bg-white shadow-xl">
              <div className="text-center mb-6">
                {(() => {
                  const SelectedIcon = loginOptions.find(opt => opt.value === selectedRole)?.icon || Heart;
                  return (
                    <div className="bg-red-600 size-16 rounded-lg flex items-center justify-center mx-auto mb-4">
                      <SelectedIcon className="size-8 text-white" />
                    </div>
                  );
                })()}
                <h3 className="text-gray-900 mb-2">Portal Login</h3>
                <p className="text-gray-600">Select your role to continue</p>
              </div>
              
              <div className="space-y-4">
                <div>
                  <label className="text-gray-700 mb-2 block">Select Role</label>
                  <div className="grid grid-cols-4 gap-2 p-1 bg-gray-100 rounded-lg">
                    {loginOptions.map((option) => (
                      <button
                        key={option.value}
                        onClick={() => setSelectedRole(option.value)}
                        className={`px-3 py-2 rounded-md transition-all ${
                          selectedRole === option.value
                            ? 'bg-white text-red-600 shadow-sm'
                            : 'text-gray-600 hover:text-gray-900'
                        }`}
                      >
                        <option.icon className="size-5 mx-auto mb-1" />
                        <div className="text-xs">{option.title}</div>
                      </button>
                    ))}
                  </div>
                </div>

                <Button 
                  onClick={handleLogin}
                  className="w-full bg-red-600 hover:bg-red-700"
                  size="lg"
                >
                  Login as {loginOptions.find(opt => opt.value === selectedRole)?.title}
                  <ChevronRight className="size-4 ml-2" />
                </Button>

                <div className="text-center text-sm text-gray-500 mt-4">
                  Don't have an account? <span className="text-red-600 cursor-pointer hover:underline">Register here</span>
                </div>
              </div>
            </Card>

            <div className="text-center mt-6 text-gray-500">
              <p>Emergency Hotline: <span className="text-red-600">1-800-BLOOD-NOW</span></p>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
