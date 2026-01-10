import { useNavigate, useSearchParams } from 'react-router-dom';
import { Card } from '../ui/card';
import { Button } from '../ui/button';
import { Input } from '../ui/input';
import { Label } from '../ui/label';
import { Heart, Shield, Building2, Search, ArrowLeft, Eye, EyeOff, CheckCircle } from 'lucide-react';
import { useState } from 'react';

export default function Login() {
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();
  const roleParam = searchParams.get('role') || 'donor';
  
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [selectedRole, setSelectedRole] = useState(roleParam);

  const roles = [
    { id: 'donor', title: 'Donor', icon: Heart, path: '/donor/dashboard', color: 'red' },
    { id: 'admin', title: 'Admin', icon: Shield, path: '/admin/dashboard', color: 'blue' },
    { id: 'hospital', title: 'Hospital', icon: Building2, path: '/hospital/dashboard', color: 'green' },
    { id: 'seeker', title: 'Blood Seeker', icon: Search, path: '/seeker/request', color: 'purple' }
  ];

  const currentRole = roles.find(r => r.id === selectedRole) || roles[0];
  const CurrentIcon = currentRole.icon;

  const handleLogin = (e: React.FormEvent) => {
    e.preventDefault();
    
    // Demo mode - accept any credentials
    if (email && password) {
      // Redirect to appropriate dashboard based on role
      navigate(currentRole.path);
    }
  };

  return (
    <div className="min-h-screen bg-gradient-to-br from-red-50 via-white to-red-50 flex items-center justify-center p-6">
      <div className="w-full max-w-md">
        {/* Back Button */}
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

        <Card className="p-8 bg-white shadow-xl border-2 border-gray-100">
          {/* Role Icon */}
          <div className="text-center mb-6">
            <div className="bg-red-600 size-16 rounded-lg flex items-center justify-center mx-auto mb-4">
              <CurrentIcon className="size-8 text-white" />
            </div>
            <h3 className="text-gray-900 mb-2">Login to Your Account</h3>
            <p className="text-gray-600">Logging in as <span className="text-red-600">{currentRole.title}</span></p>
          </div>

          {/* Role Selector */}
          <div className="mb-6">
            <Label className="text-gray-700 mb-3 block">Select Your Role</Label>
            <div className="grid grid-cols-4 gap-2 p-1 bg-gray-100 rounded-lg">
              {roles.map((role) => (
                <button
                  key={role.id}
                  type="button"
                  onClick={() => setSelectedRole(role.id)}
                  className={`px-3 py-2 rounded-md transition-all ${
                    selectedRole === role.id
                      ? 'bg-white text-red-600 shadow-sm'
                      : 'text-gray-600 hover:text-gray-900'
                  }`}
                >
                  <role.icon className="size-5 mx-auto mb-1" />
                  <div className="text-xs">{role.title}</div>
                </button>
              ))}
            </div>
          </div>

          {/* Login Form */}
          <form onSubmit={handleLogin} className="space-y-5">
            <div>
              <Label htmlFor="email" className="text-gray-700 mb-2 block">Email Address</Label>
              <Input
                id="email"
                type="email"
                placeholder="Enter your email"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                required
                className="h-12"
              />
            </div>

            <div>
              <Label htmlFor="password" className="text-gray-700 mb-2 block">Password</Label>
              <div className="relative">
                <Input
                  id="password"
                  type={showPassword ? 'text' : 'password'}
                  placeholder="Enter your password"
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  required
                  className="h-12 pr-12"
                />
                <button
                  type="button"
                  onClick={() => setShowPassword(!showPassword)}
                  className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700"
                >
                  {showPassword ? <EyeOff className="size-5" /> : <Eye className="size-5" />}
                </button>
              </div>
            </div>

            <div className="flex items-center justify-between text-sm">
              <label className="flex items-center gap-2 text-gray-600 cursor-pointer">
                <input type="checkbox" className="rounded border-gray-300" />
                <span>Remember me</span>
              </label>
              <button type="button" className="text-red-600 hover:underline">
                Forgot password?
              </button>
            </div>

            <Button 
              type="submit"
              className="w-full bg-red-600 hover:bg-red-700 h-12"
              size="lg"
            >
              Login to Dashboard
            </Button>
          </form>

          {/* Sign Up Link */}
          <div className="mt-6 text-center">
            <p className="text-gray-600">
              Don't have an account?{' '}
              <button
                type="button"
                onClick={() => navigate(`/auth/register?role=${selectedRole}`)}
                className="text-red-600 hover:underline"
              >
                Create an account
              </button>
            </p>
          </div>
        </Card>
      </div>
    </div>
  );
}