import DashboardLayout from '../layout/DashboardLayout';
import { LayoutDashboard, Heart, FileText, Award, MessageSquare, Settings, Send, Search, MoreVertical, Building2, Calendar } from 'lucide-react';
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

const contacts = [
  { id: 1, name: 'City Hospital', lastMessage: 'Thank you for your donation!', time: '10:30 AM', unread: 0, online: true },
  { id: 2, name: 'St. Mary Medical', lastMessage: 'Your appointment is confirmed', time: '9:45 AM', unread: 1, online: false },
];

const messages = [
  { id: 1, sender: 'City Hospital', content: 'Hello John, thank you for accepting the donation request', time: '10:25 AM', isOwn: false },
  { id: 2, sender: 'You', content: 'You\'re welcome! Happy to help.', time: '10:27 AM', isOwn: true },
  { id: 3, sender: 'City Hospital', content: 'Thank you for your donation!', time: '10:30 AM', isOwn: false },
];

export default function DonorChat() {
  return (
    <DashboardLayout
      sidebarItems={sidebarItems}
      sidebarTitle="Donor Portal"
      userName="John Smith"
      userRole="Blood Donor"
      notifications={4}
    >
      <Card className="h-[calc(100vh-180px)] flex overflow-hidden">
        {/* Contacts List */}
        <div className="w-80 border-r border-gray-200 flex flex-col">
          <div className="p-4 border-b border-gray-200">
            <h2 className="text-gray-900 mb-4">Messages</h2>
            <div className="relative">
              <Search className="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-gray-400" />
              <input
                type="text"
                placeholder="Search..."
                className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500"
              />
            </div>
          </div>

          <div className="flex-1 overflow-y-auto">
            {contacts.map((contact) => (
              <div
                key={contact.id}
                className="p-4 border-b border-gray-200 hover:bg-gray-50 cursor-pointer transition-colors"
              >
                <div className="flex items-start gap-3">
                  <div className="relative">
                    <div className="size-12 bg-red-100 rounded-full flex items-center justify-center">
                      <Building2 className="size-5 text-red-600" />
                    </div>
                    {contact.online && (
                      <div className="absolute bottom-0 right-0 size-3 bg-green-500 border-2 border-white rounded-full" />
                    )}
                  </div>
                  <div className="flex-1 min-w-0">
                    <div className="flex items-center justify-between mb-1">
                      <h3 className="text-gray-900 truncate">{contact.name}</h3>
                      <span className="text-gray-500">{contact.time}</span>
                    </div>
                    <p className="text-gray-600 truncate">{contact.lastMessage}</p>
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>

        {/* Chat Window */}
        <div className="flex-1 flex flex-col">
          {/* Chat Header */}
          <div className="p-4 border-b border-gray-200">
            <div className="flex items-center justify-between">
              <div className="flex items-center gap-3">
                <div className="relative">
                  <div className="size-10 bg-red-100 rounded-full flex items-center justify-center">
                    <Building2 className="size-5 text-red-600" />
                  </div>
                  <div className="absolute bottom-0 right-0 size-3 bg-green-500 border-2 border-white rounded-full" />
                </div>
                <div>
                  <h3 className="text-gray-900">City Hospital</h3>
                  <p className="text-gray-500">Online</p>
                </div>
              </div>
              <button className="p-2 hover:bg-gray-100 rounded-lg transition-colors">
                <MoreVertical className="size-5 text-gray-600" />
              </button>
            </div>
          </div>

          {/* Messages */}
          <div className="flex-1 overflow-y-auto p-4 space-y-4">
            {messages.map((message) => (
              <div
                key={message.id}
                className={`flex ${message.isOwn ? 'justify-end' : 'justify-start'}`}
              >
                <div
                  className={`max-w-md px-4 py-2 rounded-lg ${
                    message.isOwn
                      ? 'bg-red-600 text-white'
                      : 'bg-gray-100 text-gray-900'
                  }`}
                >
                  <p>{message.content}</p>
                  <p
                    className={`text-right mt-1 ${
                      message.isOwn ? 'text-red-100' : 'text-gray-500'
                    }`}
                  >
                    {message.time}
                  </p>
                </div>
              </div>
            ))}
          </div>

          {/* Message Input */}
          <div className="p-4 border-t border-gray-200">
            <div className="flex gap-3">
              <input
                type="text"
                placeholder="Type your message..."
                className="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500"
              />
              <Button className="bg-red-600 hover:bg-red-700">
                <Send className="size-4 mr-2" />
                Send
              </Button>
            </div>
          </div>
        </div>
      </Card>
    </DashboardLayout>
  );
}
