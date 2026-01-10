import { useState } from 'react';
import DashboardLayout from '../layout/DashboardLayout';
import { Send, Search, MoreVertical, Droplet, MapPin, Phone, MessageSquare, ClipboardList } from 'lucide-react';
import { Card } from '../ui/card';
import { Button } from '../ui/button';
import { Badge } from '../ui/badge';

const sidebarItems = [
  { path: '/seeker/request', label: 'Submit Blood Request', icon: Send },
  { path: '/seeker/tracking', label: 'Track Request', icon: ClipboardList },
  { path: '/seeker/chat', label: 'Chat with Donor', icon: MessageSquare },
];

const donors = [
  { 
    id: 1, 
    name: 'John Smith', 
    bloodType: 'O+', 
    lastMessage: 'I can help with your request', 
    time: '10:30 AM', 
    unread: 2, 
    online: true,
    location: 'Downtown',
    phone: '555-0101',
    requestId: 'REQ001'
  },
  { 
    id: 2, 
    name: 'Maria Garcia', 
    bloodType: 'O+', 
    lastMessage: 'When do you need the donation?', 
    time: '9:45 AM', 
    unread: 0, 
    online: true,
    location: 'Westside',
    phone: '555-0102',
    requestId: 'REQ001'
  },
  { 
    id: 3, 
    name: 'David Lee', 
    bloodType: 'O+', 
    lastMessage: 'Available this weekend', 
    time: 'Yesterday', 
    unread: 1, 
    online: false,
    location: 'Uptown',
    phone: '555-0103',
    requestId: 'REQ001'
  },
  { 
    id: 4, 
    name: 'Sarah Johnson', 
    bloodType: 'A-', 
    lastMessage: 'Let me check my schedule', 
    time: 'Yesterday', 
    unread: 0, 
    online: false,
    location: 'Central',
    phone: '555-0104',
    requestId: 'REQ002'
  },
];

const mockMessages = [
  { id: 1, senderId: 1, text: 'Hello, I saw your blood request', time: '10:25 AM', isSeeker: false },
  { id: 2, senderId: 1, text: 'I can help with your request', time: '10:30 AM', isSeeker: false },
  { id: 3, senderId: 'seeker', text: 'Thank you so much! When are you available?', time: '10:32 AM', isSeeker: true },
  { id: 4, senderId: 1, text: 'I can donate this Friday afternoon', time: '10:35 AM', isSeeker: false },
  { id: 5, senderId: 'seeker', text: 'That works perfectly! Should we meet at City Hospital?', time: '10:37 AM', isSeeker: true },
];

export default function SeekerChat() {
  const [selectedDonor, setSelectedDonor] = useState(donors[0]);
  const [messageInput, setMessageInput] = useState('');
  const [searchTerm, setSearchTerm] = useState('');

  const filteredDonors = donors.filter(donor =>
    donor.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
    donor.bloodType.toLowerCase().includes(searchTerm.toLowerCase())
  );

  const handleSendMessage = () => {
    if (messageInput.trim()) {
      // In a real app, this would send the message to the backend
      console.log('Sending message:', messageInput);
      setMessageInput('');
    }
  };

  return (
    <DashboardLayout
      sidebarItems={sidebarItems}
      sidebarTitle="Seeker Portal"
      userName="Guest User"
      userRole="Blood Seeker"
      notifications={0}
    >
      <div className="space-y-6">
        <div>
          <h1 className="text-gray-900 mb-2">Messages</h1>
          <p className="text-gray-600">Chat with donors who responded to your blood requests</p>
        </div>

        <Card className="h-[calc(100vh-280px)] flex overflow-hidden">
          {/* Contacts Sidebar */}
          <div className="w-96 border-r border-gray-200 flex flex-col">
            <div className="p-4 border-b border-gray-200">
              <h2 className="text-gray-900 mb-4">Connected Donors ({filteredDonors.length})</h2>
              <div className="relative">
                <Search className="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-gray-400" />
                <input
                  type="text"
                  placeholder="Search donors..."
                  value={searchTerm}
                  onChange={(e) => setSearchTerm(e.target.value)}
                  className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500"
                />
              </div>
            </div>

            <div className="flex-1 overflow-y-auto">
              {filteredDonors.map((donor) => (
                <div
                  key={donor.id}
                  onClick={() => setSelectedDonor(donor)}
                  className={`p-4 border-b border-gray-200 cursor-pointer transition-colors ${
                    selectedDonor.id === donor.id ? 'bg-red-50' : 'hover:bg-gray-50'
                  }`}
                >
                  <div className="flex items-start gap-3">
                    <div className="relative">
                      <div className="size-12 bg-red-100 rounded-full flex items-center justify-center">
                        <Droplet className="size-5 text-red-600 fill-red-600" />
                      </div>
                      {donor.online && (
                        <div className="absolute bottom-0 right-0 size-3 bg-green-500 rounded-full border-2 border-white"></div>
                      )}
                    </div>
                    <div className="flex-1 min-w-0">
                      <div className="flex items-start justify-between mb-1">
                        <div>
                          <h3 className="text-gray-900">{donor.name}</h3>
                          <div className="flex items-center gap-2 mt-1">
                            <Badge variant="outline" className="border-red-600 text-red-600">
                              {donor.bloodType}
                            </Badge>
                            <span className="text-gray-500 text-sm">{donor.requestId}</span>
                          </div>
                        </div>
                        {donor.unread > 0 && (
                          <Badge className="bg-red-600 hover:bg-red-700 ml-2">
                            {donor.unread}
                          </Badge>
                        )}
                      </div>
                      <p className="text-gray-600 truncate mt-2">{donor.lastMessage}</p>
                      <div className="flex items-center gap-3 mt-1">
                        <p className="text-gray-500">{donor.time}</p>
                        {donor.online && <span className="text-green-600 text-sm">Online</span>}
                      </div>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          </div>

          {/* Chat Window */}
          <div className="flex-1 flex flex-col">
            {/* Chat Header */}
            <div className="p-4 border-b border-gray-200 bg-white">
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-3">
                  <div className="relative">
                    <div className="size-12 bg-red-100 rounded-full flex items-center justify-center">
                      <Droplet className="size-6 text-red-600 fill-red-600" />
                    </div>
                    {selectedDonor.online && (
                      <div className="absolute bottom-0 right-0 size-3 bg-green-500 rounded-full border-2 border-white"></div>
                    )}
                  </div>
                  <div>
                    <div className="flex items-center gap-2">
                      <h3 className="text-gray-900">{selectedDonor.name}</h3>
                      {selectedDonor.online && (
                        <span className="text-green-600 text-sm">Online</span>
                      )}
                    </div>
                    <div className="flex items-center gap-3 text-gray-600 mt-1">
                      <div className="flex items-center gap-1">
                        <Droplet className="size-3" />
                        <span className="text-sm">{selectedDonor.bloodType}</span>
                      </div>
                      <div className="flex items-center gap-1">
                        <MapPin className="size-3" />
                        <span className="text-sm">{selectedDonor.location}</span>
                      </div>
                      <div className="flex items-center gap-1">
                        <Phone className="size-3" />
                        <span className="text-sm">{selectedDonor.phone}</span>
                      </div>
                    </div>
                  </div>
                </div>
                <button className="p-2 hover:bg-gray-100 rounded-lg">
                  <MoreVertical className="size-5 text-gray-600" />
                </button>
              </div>
            </div>

            {/* Messages */}
            <div className="flex-1 overflow-y-auto p-6 space-y-4 bg-gray-50">
              {mockMessages.map((message) => (
                <div
                  key={message.id}
                  className={`flex ${message.isSeeker ? 'justify-end' : 'justify-start'}`}
                >
                  <div className={`max-w-md ${message.isSeeker ? 'order-2' : ''}`}>
                    <div
                      className={`px-4 py-3 rounded-lg ${
                        message.isSeeker
                          ? 'bg-red-600 text-white'
                          : 'bg-white text-gray-900 border border-gray-200'
                      }`}
                    >
                      <p>{message.text}</p>
                    </div>
                    <p
                      className={`text-sm text-gray-500 mt-1 ${
                        message.isSeeker ? 'text-right' : 'text-left'
                      }`}
                    >
                      {message.time}
                    </p>
                  </div>
                </div>
              ))}
            </div>

            {/* Message Input */}
            <div className="p-4 border-t border-gray-200 bg-white">
              <div className="flex gap-3">
                <input
                  type="text"
                  placeholder="Type your message..."
                  value={messageInput}
                  onChange={(e) => setMessageInput(e.target.value)}
                  onKeyPress={(e) => e.key === 'Enter' && handleSendMessage()}
                  className="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500"
                />
                <Button 
                  className="bg-red-600 hover:bg-red-700 px-6"
                  onClick={handleSendMessage}
                >
                  <Send className="size-4 mr-2" />
                  Send
                </Button>
              </div>
              <p className="text-gray-500 text-sm mt-2">
                Request ID: {selectedDonor.requestId}
              </p>
            </div>
          </div>
        </Card>
      </div>
    </DashboardLayout>
  );
}
