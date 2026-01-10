import { useNavigate, useSearchParams } from 'react-router-dom';
import { Card } from '../ui/card';
import { Button } from '../ui/button';
import { Input } from '../ui/input';
import { Label } from '../ui/label';
import { Heart, Shield, Building2, Search, ArrowLeft, Eye, EyeOff, CheckCircle, User, Mail, Lock, Phone, MapPin, Calendar, Droplet } from 'lucide-react';
import { useState } from 'react';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '../ui/select';
import { Textarea } from '../ui/textarea';

export default function Register() {
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();
  const roleParam = searchParams.get('role') || 'donor';
  
  const [formData, setFormData] = useState({
    // Common fields
    email: '',
    password: '',
    confirmPassword: '',
    phone: '',
    
    // Donor specific
    bloodGroup: '',
    age: '',
    weight: '',
    address: '',
    
    // Hospital specific
    registrationNumber: '',
    hospitalAddress: '',
    city: '',
    contactPerson: ''
  });
  const [showPassword, setShowPassword] = useState(false);
  const [showConfirmPassword, setShowConfirmPassword] = useState(false);
  const [selectedRole, setSelectedRole] = useState(roleParam);
  const [errors, setErrors] = useState<{[key: string]: string}>({});

  const roles = [
    { id: 'donor', title: 'Donor', icon: Heart, path: '/donor/dashboard', color: 'red' },
    { id: 'admin', title: 'Admin', icon: Shield, path: '/admin/dashboard', color: 'blue' },
    { id: 'hospital', title: 'Hospital', icon: Building2, path: '/hospital/dashboard', color: 'green' },
    { id: 'seeker', title: 'Blood Seeker', icon: Search, path: '/seeker/request', color: 'purple' }
  ];

  const bloodGroups = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];

  const currentRole = roles.find(r => r.id === selectedRole) || roles[0];
  const CurrentIcon = currentRole.icon;

  const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
    const { name, value } = e.target;
    setFormData(prev => ({ ...prev, [name]: value }));
    // Clear error for this field when user starts typing
    if (errors[name]) {
      setErrors(prev => ({ ...prev, [name]: '' }));
    }
  };

  const handleSelectChange = (name: string, value: string) => {
    setFormData(prev => ({ ...prev, [name]: value }));
    if (errors[name]) {
      setErrors(prev => ({ ...prev, [name]: '' }));
    }
  };

  const validateForm = () => {
    const newErrors: {[key: string]: string} = {};

    // Common validation
    if (!formData.email.trim()) {
      newErrors.email = 'Email is required';
    } else if (!/\S+@\S+\.\S+/.test(formData.email)) {
      newErrors.email = 'Email is invalid';
    }

    if (!formData.password) {
      newErrors.password = 'Password is required';
    } else if (formData.password.length < 6) {
      newErrors.password = 'Password must be at least 6 characters';
    }

    if (!formData.confirmPassword) {
      newErrors.confirmPassword = 'Please confirm your password';
    } else if (formData.password !== formData.confirmPassword) {
      newErrors.confirmPassword = 'Passwords do not match';
    }

    if (!formData.phone.trim()) {
      newErrors.phone = 'Phone number is required';
    }

    // Role-specific validation
    if (selectedRole === 'donor') {
      if (!formData.bloodGroup) newErrors.bloodGroup = 'Blood group is required';
      if (!formData.age) newErrors.age = 'Age is required';
      if (!formData.weight) newErrors.weight = 'Weight is required';
      if (!formData.address.trim()) newErrors.address = 'Address is required';
    }

    if (selectedRole === 'hospital') {
      if (!formData.registrationNumber.trim()) newErrors.registrationNumber = 'Registration number is required';
      if (!formData.hospitalAddress.trim()) newErrors.hospitalAddress = 'Hospital address is required';
      if (!formData.city.trim()) newErrors.city = 'City is required';
      if (!formData.contactPerson.trim()) newErrors.contactPerson = 'Contact person is required';
    }

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleRegister = (e: React.FormEvent) => {
    e.preventDefault();
    
    // Validate form
    if (validateForm()) {
      // Demo mode - accept registration and redirect
      console.log('Registration data:', { ...formData, role: selectedRole });
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
            <h3 className="text-gray-900 mb-2">Create Your Account</h3>
            <p className="text-gray-600">Registering as <span className="text-red-600">{currentRole.title}</span></p>
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

          {/* Registration Form */}
          <form onSubmit={handleRegister} className="space-y-5">
            <div>
              <Label htmlFor="email" className="text-gray-700 mb-2 block">Email Address</Label>
              <div className="relative">
                <Mail className="absolute left-3 top-1/2 -translate-y-1/2 size-5 text-gray-400" />
                <Input
                  id="email"
                  name="email"
                  type="email"
                  placeholder="Enter your email"
                  value={formData.email}
                  onChange={handleChange}
                  className={`h-12 pl-10 ${errors.email ? 'border-red-500' : ''}`}
                />
              </div>
              {errors.email && (
                <p className="text-red-500 text-sm mt-1">{errors.email}</p>
              )}
            </div>

            <div>
              <Label htmlFor="password" className="text-gray-700 mb-2 block">Password</Label>
              <div className="relative">
                <Lock className="absolute left-3 top-1/2 -translate-y-1/2 size-5 text-gray-400" />
                <Input
                  id="password"
                  name="password"
                  type={showPassword ? 'text' : 'password'}
                  placeholder="Create a password"
                  value={formData.password}
                  onChange={handleChange}
                  className={`h-12 pl-10 pr-12 ${errors.password ? 'border-red-500' : ''}`}
                />
                <button
                  type="button"
                  onClick={() => setShowPassword(!showPassword)}
                  className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700"
                >
                  {showPassword ? <EyeOff className="size-5" /> : <Eye className="size-5" />}
                </button>
              </div>
              {errors.password && (
                <p className="text-red-500 text-sm mt-1">{errors.password}</p>
              )}
            </div>

            <div>
              <Label htmlFor="confirmPassword" className="text-gray-700 mb-2 block">Confirm Password</Label>
              <div className="relative">
                <Lock className="absolute left-3 top-1/2 -translate-y-1/2 size-5 text-gray-400" />
                <Input
                  id="confirmPassword"
                  name="confirmPassword"
                  type={showConfirmPassword ? 'text' : 'password'}
                  placeholder="Confirm your password"
                  value={formData.confirmPassword}
                  onChange={handleChange}
                  className={`h-12 pl-10 pr-12 ${errors.confirmPassword ? 'border-red-500' : ''}`}
                />
                <button
                  type="button"
                  onClick={() => setShowConfirmPassword(!showConfirmPassword)}
                  className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700"
                >
                  {showConfirmPassword ? <EyeOff className="size-5" /> : <Eye className="size-5" />}
                </button>
              </div>
              {errors.confirmPassword && (
                <p className="text-red-500 text-sm mt-1">{errors.confirmPassword}</p>
              )}
            </div>

            <div>
              <Label htmlFor="phone" className="text-gray-700 mb-2 block">Phone Number</Label>
              <div className="relative">
                <Phone className="absolute left-3 top-1/2 -translate-y-1/2 size-5 text-gray-400" />
                <Input
                  id="phone"
                  name="phone"
                  type="tel"
                  placeholder="Enter your phone number"
                  value={formData.phone}
                  onChange={handleChange}
                  className={`h-12 pl-10 ${errors.phone ? 'border-red-500' : ''}`}
                />
              </div>
              {errors.phone && (
                <p className="text-red-500 text-sm mt-1">{errors.phone}</p>
              )}
            </div>

            {/* Donor Specific Fields */}
            {selectedRole === 'donor' && (
              <>
                <div>
                  <Label htmlFor="bloodGroup" className="text-gray-700 mb-2 block">Blood Group</Label>
                  <Select
                    value={formData.bloodGroup}
                    onValueChange={(value) => handleSelectChange('bloodGroup', value)}
                  >
                    <SelectTrigger className="h-12">
                      <SelectValue placeholder="Select your blood group" />
                    </SelectTrigger>
                    <SelectContent>
                      {bloodGroups.map(group => (
                        <SelectItem key={group} value={group}>
                          {group}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                  {errors.bloodGroup && (
                    <p className="text-red-500 text-sm mt-1">{errors.bloodGroup}</p>
                  )}
                </div>

                <div>
                  <Label htmlFor="age" className="text-gray-700 mb-2 block">Age</Label>
                  <div className="relative">
                    <Calendar className="absolute left-3 top-1/2 -translate-y-1/2 size-5 text-gray-400" />
                    <Input
                      id="age"
                      name="age"
                      type="number"
                      placeholder="Enter your age"
                      value={formData.age}
                      onChange={handleChange}
                      className={`h-12 pl-10 ${errors.age ? 'border-red-500' : ''}`}
                    />
                  </div>
                  {errors.age && (
                    <p className="text-red-500 text-sm mt-1">{errors.age}</p>
                  )}
                </div>

                <div>
                  <Label htmlFor="weight" className="text-gray-700 mb-2 block">Weight (kg)</Label>
                  <div className="relative">
                    <Droplet className="absolute left-3 top-1/2 -translate-y-1/2 size-5 text-gray-400" />
                    <Input
                      id="weight"
                      name="weight"
                      type="number"
                      placeholder="Enter your weight"
                      value={formData.weight}
                      onChange={handleChange}
                      className={`h-12 pl-10 ${errors.weight ? 'border-red-500' : ''}`}
                    />
                  </div>
                  {errors.weight && (
                    <p className="text-red-500 text-sm mt-1">{errors.weight}</p>
                  )}
                </div>

                <div>
                  <Label htmlFor="address" className="text-gray-700 mb-2 block">Address</Label>
                  <div className="relative">
                    <MapPin className="absolute left-3 top-3 size-5 text-gray-400" />
                    <Textarea
                      id="address"
                      name="address"
                      placeholder="Enter your address"
                      value={formData.address}
                      onChange={handleChange}
                      className={`min-h-20 pl-10 ${errors.address ? 'border-red-500' : ''}`}
                    />
                  </div>
                  {errors.address && (
                    <p className="text-red-500 text-sm mt-1">{errors.address}</p>
                  )}
                </div>
              </>
            )}

            {/* Hospital Specific Fields */}
            {selectedRole === 'hospital' && (
              <>
                <div>
                  <Label htmlFor="registrationNumber" className="text-gray-700 mb-2 block">Registration Number</Label>
                  <div className="relative">
                    <Shield className="absolute left-3 top-1/2 -translate-y-1/2 size-5 text-gray-400" />
                    <Input
                      id="registrationNumber"
                      name="registrationNumber"
                      type="text"
                      placeholder="Enter registration number"
                      value={formData.registrationNumber}
                      onChange={handleChange}
                      className={`h-12 pl-10 ${errors.registrationNumber ? 'border-red-500' : ''}`}
                    />
                  </div>
                  {errors.registrationNumber && (
                    <p className="text-red-500 text-sm mt-1">{errors.registrationNumber}</p>
                  )}
                </div>

                <div>
                  <Label htmlFor="hospitalAddress" className="text-gray-700 mb-2 block">Hospital Address</Label>
                  <div className="relative">
                    <MapPin className="absolute left-3 top-3 size-5 text-gray-400" />
                    <Textarea
                      id="hospitalAddress"
                      name="hospitalAddress"
                      placeholder="Enter hospital address"
                      value={formData.hospitalAddress}
                      onChange={handleChange}
                      className={`min-h-20 pl-10 ${errors.hospitalAddress ? 'border-red-500' : ''}`}
                    />
                  </div>
                  {errors.hospitalAddress && (
                    <p className="text-red-500 text-sm mt-1">{errors.hospitalAddress}</p>
                  )}
                </div>

                <div>
                  <Label htmlFor="city" className="text-gray-700 mb-2 block">City</Label>
                  <div className="relative">
                    <MapPin className="absolute left-3 top-1/2 -translate-y-1/2 size-5 text-gray-400" />
                    <Input
                      id="city"
                      name="city"
                      type="text"
                      placeholder="Enter city"
                      value={formData.city}
                      onChange={handleChange}
                      className={`h-12 pl-10 ${errors.city ? 'border-red-500' : ''}`}
                    />
                  </div>
                  {errors.city && (
                    <p className="text-red-500 text-sm mt-1">{errors.city}</p>
                  )}
                </div>

                <div>
                  <Label htmlFor="contactPerson" className="text-gray-700 mb-2 block">Contact Person</Label>
                  <div className="relative">
                    <User className="absolute left-3 top-1/2 -translate-y-1/2 size-5 text-gray-400" />
                    <Input
                      id="contactPerson"
                      name="contactPerson"
                      type="text"
                      placeholder="Enter contact person name"
                      value={formData.contactPerson}
                      onChange={handleChange}
                      className={`h-12 pl-10 ${errors.contactPerson ? 'border-red-500' : ''}`}
                    />
                  </div>
                  {errors.contactPerson && (
                    <p className="text-red-500 text-sm mt-1">{errors.contactPerson}</p>
                  )}
                </div>
              </>
            )}

            <div className="flex items-start gap-2">
              <input 
                type="checkbox" 
                id="terms" 
                required
                className="mt-1 rounded border-gray-300" 
              />
              <label htmlFor="terms" className="text-sm text-gray-600">
                I agree to the <span className="text-red-600 hover:underline cursor-pointer">Terms of Service</span> and{' '}
                <span className="text-red-600 hover:underline cursor-pointer">Privacy Policy</span>
              </label>
            </div>

            <Button 
              type="submit"
              className="w-full bg-red-600 hover:bg-red-700 h-12"
              size="lg"
            >
              Create Account
            </Button>
          </form>

          {/* Login Link */}
          <div className="mt-6 text-center">
            <p className="text-gray-600">
              Already have an account?{' '}
              <button
                type="button"
                onClick={() => navigate(`/auth/login?role=${selectedRole}`)}
                className="text-red-600 hover:underline"
              >
                Login here
              </button>
            </p>
          </div>
        </Card>
      </div>
    </div>
  );
}