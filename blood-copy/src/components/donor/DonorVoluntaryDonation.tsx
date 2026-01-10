import { useState } from 'react';
import DashboardLayout from '../layout/DashboardLayout';
import { LayoutDashboard, Heart, FileText, Award, MessageSquare, Settings, Calendar, MapPin, Clock, Search, CheckCircle } from 'lucide-react';
import { Card } from '../ui/card';
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

const hospitals = [
  { id: 'H001', name: 'City Hospital', location: 'Downtown', distance: '2.5 km' },
  { id: 'H002', name: 'Regional Hospital', location: 'Uptown', distance: '4.2 km' },
  { id: 'H003', name: 'St. Mary Medical', location: 'Westside', distance: '5.8 km' },
  { id: 'H004', name: 'Community Medical Center', location: 'Eastside', distance: '3.1 km' },
  { id: 'H005', name: 'Valley Hospital', location: 'Central', distance: '6.5 km' },
];

const timeSlots = [
  '08:00 AM', '09:00 AM', '10:00 AM', '11:00 AM', 
  '12:00 PM', '01:00 PM', '02:00 PM', '03:00 PM', 
  '04:00 PM', '05:00 PM'
];

export default function DonorVoluntaryDonation() {
  const [selectedHospital, setSelectedHospital] = useState('');
  const [selectedDate, setSelectedDate] = useState('');
  const [selectedTime, setSelectedTime] = useState('');
  const [searchTerm, setSearchTerm] = useState('');

  const filteredHospitals = hospitals.filter(hospital =>
    hospital.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
    hospital.location.toLowerCase().includes(searchTerm.toLowerCase())
  );

  const handleSubmit = () => {
    if (selectedHospital && selectedDate && selectedTime) {
      alert('Voluntary donation request submitted successfully!');
      // Reset form
      setSelectedHospital('');
      setSelectedDate('');
      setSelectedTime('');
      setSearchTerm('');
    } else {
      alert('Please complete all fields before submitting.');
    }
  };

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
          <h1 className="text-gray-900 mb-2">Donate Voluntarily</h1>
          <p className="text-gray-600">Schedule a voluntary blood donation at your preferred hospital</p>
        </div>

        {/* Select Hospital */}
        <Card className="p-6">
          <div className="mb-6">
            <h2 className="text-gray-900 mb-2">Select Hospital</h2>
            <p className="text-gray-600 mb-4">Choose a hospital near you for your donation</p>
            
            {/* Search Bar */}
            <div className="relative mb-4">
              <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 size-5 text-gray-400" />
              <input
                type="text"
                placeholder="Search hospitals by name or location..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500"
              />
            </div>
          </div>

          {/* Hospital List */}
          <div className="space-y-3 max-h-96 overflow-y-auto">
            {filteredHospitals.map((hospital) => (
              <div
                key={hospital.id}
                onClick={() => setSelectedHospital(hospital.name)}
                className={`p-4 border rounded-lg cursor-pointer transition-all ${
                  selectedHospital === hospital.name
                    ? 'border-red-600 bg-red-50'
                    : 'border-gray-200 hover:border-red-300 hover:shadow-md'
                }`}
              >
                <div className="flex items-center justify-between">
                  <div className="flex items-center gap-3">
                    <div className={`size-10 rounded-full flex items-center justify-center ${
                      selectedHospital === hospital.name ? 'bg-red-600' : 'bg-gray-100'
                    }`}>
                      <Heart className={`size-5 ${
                        selectedHospital === hospital.name ? 'text-white' : 'text-gray-600'
                      }`} />
                    </div>
                    <div>
                      <h3 className="text-gray-900">{hospital.name}</h3>
                      <div className="flex items-center gap-2 text-gray-600">
                        <MapPin className="size-4" />
                        <span>{hospital.location} • {hospital.distance}</span>
                      </div>
                    </div>
                  </div>
                  {selectedHospital === hospital.name && (
                    <CheckCircle className="size-6 text-red-600" />
                  )}
                </div>
              </div>
            ))}
          </div>
        </Card>

        {/* Choose Date & Time */}
        <Card className="p-6">
          <div className="mb-6">
            <h2 className="text-gray-900 mb-2">Choose Date & Time</h2>
            <p className="text-gray-600">Select your preferred date and time slot</p>
          </div>

          <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {/* Date Picker */}
            <div>
              <label className="block text-gray-700 mb-2">Select Date</label>
              <input
                type="date"
                value={selectedDate}
                onChange={(e) => setSelectedDate(e.target.value)}
                min={new Date().toISOString().split('T')[0]}
                className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500"
              />
            </div>

            {/* Time Slots */}
            <div>
              <label className="block text-gray-700 mb-2">Select Time Slot</label>
              <div className="grid grid-cols-2 gap-2 max-h-64 overflow-y-auto p-2">
                {timeSlots.map((time) => (
                  <button
                    key={time}
                    onClick={() => setSelectedTime(time)}
                    className={`px-4 py-3 border rounded-lg transition-all ${
                      selectedTime === time
                        ? 'border-red-600 bg-red-600 text-white'
                        : 'border-gray-300 hover:border-red-300 hover:bg-red-50'
                    }`}
                  >
                    <Clock className="size-4 inline mr-2" />
                    {time}
                  </button>
                ))}
              </div>
            </div>
          </div>
        </Card>

        {/* Confirm Appointment */}
        <Card className="p-6">
          <div className="mb-6">
            <h2 className="text-gray-900 mb-2">Confirm Appointment</h2>
            <p className="text-gray-600">Review your donation details before submitting</p>
          </div>

          {/* Summary */}
          <div className="bg-gray-50 rounded-lg p-6 mb-6">
            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div>
                <p className="text-gray-600 mb-1">Selected Hospital</p>
                <p className="text-gray-900">{selectedHospital || 'Not selected'}</p>
              </div>
              <div>
                <p className="text-gray-600 mb-1">Selected Date</p>
                <p className="text-gray-900">{selectedDate || 'Not selected'}</p>
              </div>
              <div>
                <p className="text-gray-600 mb-1">Selected Time</p>
                <p className="text-gray-900">{selectedTime || 'Not selected'}</p>
              </div>
            </div>
          </div>

          <div className="flex gap-4">
            <Button
              variant="outline"
              className="flex-1"
              onClick={() => {
                setSelectedHospital('');
                setSelectedDate('');
                setSelectedTime('');
                setSearchTerm('');
              }}
            >
              Clear Form
            </Button>
            <Button
              className="flex-1 bg-red-600 hover:bg-red-700"
              onClick={handleSubmit}
            >
              <CheckCircle className="size-5 mr-2" />
              Request Voluntary Donation
            </Button>
          </div>
        </Card>
      </div>
    </DashboardLayout>
  );
}
