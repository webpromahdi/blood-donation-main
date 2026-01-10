import DashboardLayout from '../layout/DashboardLayout';
import { LayoutDashboard, Heart, FileText, Award, MessageSquare, Download, Lock, CheckCircle, Calendar, Settings } from 'lucide-react';
import { Card } from '../ui/card';
import { Badge } from '../ui/badge';
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

// Simulated donation count - in real app this would come from backend
const completedDonations = 4;

const achievementCertificates = [
  { 
    id: 1, 
    title: 'Silver Certificate', 
    requiredDonations: 3, 
    gradient: 'from-gray-300 to-gray-400',
    unlockedGradient: 'from-gray-400 to-gray-500',
    date: '2024-09-15',
    downloads: 3
  },
  { 
    id: 2, 
    title: 'Platinum Certificate', 
    requiredDonations: 5, 
    gradient: 'from-gray-300 to-gray-400',
    unlockedGradient: 'from-blue-400 to-purple-500',
    date: '2024-10-20',
    downloads: 0
  },
  { 
    id: 3, 
    title: 'Diamond Certificate', 
    requiredDonations: 8, 
    gradient: 'from-gray-300 to-gray-400',
    unlockedGradient: 'from-cyan-400 to-blue-600',
    date: '2024-11-10',
    downloads: 0
  },
];

export default function DonorCertificates() {
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
          <h1 className="text-gray-900 mb-2">Certificates & Awards</h1>
          <p className="text-gray-600">Download and share your donation certificates</p>
        </div>

        {/* Donation Progress */}
        <Card className="p-6 bg-gradient-to-r from-red-50 to-red-100 border-red-200">
          <div className="flex items-center gap-4">
            <div className="bg-red-600 p-4 rounded-full">
              <Heart className="size-8 text-white fill-white" />
            </div>
            <div className="flex-1">
              <h3 className="text-gray-900 mb-1">Your Donation Progress</h3>
              <p className="text-gray-700">
                You have completed <span className="text-red-600">{completedDonations} donation{completedDonations !== 1 ? 's' : ''}</span>
              </p>
              <div className="flex gap-2 mt-2 flex-wrap">
                {completedDonations >= 3 && (
                  <Badge className="bg-green-100 text-green-700 border-green-300">
                    <CheckCircle className="size-3 mr-1" />
                    Silver Unlocked
                  </Badge>
                )}
                {completedDonations >= 5 && (
                  <Badge className="bg-blue-100 text-blue-700 border-blue-300">
                    <CheckCircle className="size-3 mr-1" />
                    Platinum Unlocked
                  </Badge>
                )}
                {completedDonations >= 8 && (
                  <Badge className="bg-cyan-100 text-cyan-700 border-cyan-300">
                    <CheckCircle className="size-3 mr-1" />
                    Diamond Unlocked
                  </Badge>
                )}
              </div>
            </div>
          </div>
        </Card>

        {/* Achievement Certificate Cards */}
        <div>
          <h2 className="text-gray-900 mb-4">Achievement Certificates</h2>
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {achievementCertificates.map((cert) => {
              const isUnlocked = completedDonations >= cert.requiredDonations;
              const donationsRemaining = cert.requiredDonations - completedDonations;
              
              return (
                <Card 
                  key={cert.id} 
                  className={`p-6 transition-all ${
                    isUnlocked 
                      ? 'hover:shadow-xl border-2 border-green-200' 
                      : 'opacity-60 border-2 border-gray-200'
                  }`}
                >
                  <div className="flex items-center justify-center mb-4 relative">
                    <div className={`size-20 bg-gradient-to-br ${
                      isUnlocked ? cert.unlockedGradient : cert.gradient
                    } rounded-full flex items-center justify-center`}>
                      {isUnlocked ? (
                        <Award className="size-10 text-white" />
                      ) : (
                        <Lock className="size-10 text-gray-500" />
                      )}
                    </div>
                    {isUnlocked && (
                      <div className="absolute -top-2 -right-2 bg-green-500 rounded-full p-1">
                        <CheckCircle className="size-5 text-white" />
                      </div>
                    )}
                  </div>
                  <div className="text-center mb-4">
                    <h3 className={`mb-2 ${isUnlocked ? 'text-gray-900' : 'text-gray-500'}`}>
                      {cert.title}
                    </h3>
                    {isUnlocked ? (
                      <>
                        <p className="text-gray-600 mb-2">Unlocked on {cert.date}</p>
                        <Badge className="bg-green-100 text-green-700 border-green-300">
                          <CheckCircle className="size-3 mr-1" />
                          Unlocked
                        </Badge>
                      </>
                    ) : (
                      <>
                        <p className="text-gray-500 mb-2">
                          Requires {cert.requiredDonations} donation{cert.requiredDonations !== 1 ? 's' : ''}
                        </p>
                        <Badge variant="outline" className="border-gray-400 text-gray-600">
                          <Lock className="size-3 mr-1" />
                          {donationsRemaining} more donation{donationsRemaining !== 1 ? 's' : ''} needed
                        </Badge>
                      </>
                    )}
                  </div>
                  <Button 
                    className={`w-full ${
                      isUnlocked 
                        ? 'bg-red-600 hover:bg-red-700' 
                        : 'bg-gray-300 cursor-not-allowed hover:bg-gray-300'
                    }`}
                    disabled={!isUnlocked}
                  >
                    {isUnlocked ? (
                      <>
                        <Download className="size-4 mr-2" />
                        Download PDF
                      </>
                    ) : (
                      <>
                        <Lock className="size-4 mr-2" />
                        Locked
                      </>
                    )}
                  </Button>
                  {isUnlocked && cert.downloads > 0 && (
                    <p className="text-center text-gray-500 mt-2">
                      Downloaded {cert.downloads} {cert.downloads === 1 ? 'time' : 'times'}
                    </p>
                  )}
                </Card>
              );
            })}
          </div>
        </div>
      </div>
    </DashboardLayout>
  );
}
