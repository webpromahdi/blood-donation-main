import { useNavigate } from 'react-router-dom';
import { Button } from './ui/button';
import { Card } from './ui/card';
import { Heart, Droplet, Users, Clock, Award, ChevronRight, CheckCircle, AlertCircle, HelpCircle, ArrowRight, LogIn, Search } from 'lucide-react';
import { useState } from 'react';

export default function UserSelection() {
  const navigate = useNavigate();
  const [trackingInput, setTrackingInput] = useState('');

  const whyDonateReasons = [
    {
      icon: Heart,
      title: 'Save Lives',
      description: 'One donation can save up to 3 lives'
    },
    {
      icon: Users,
      title: 'Help Community',
      description: 'Support patients in your local area'
    },
    {
      icon: Award,
      title: 'Health Benefits',
      description: 'Regular donation improves your health'
    },
    {
      icon: Clock,
      title: 'Quick Process',
      description: 'Takes only 10-15 minutes to donate'
    }
  ];

  const eligibilityRules = [
    { text: 'Age between 18-65 years', icon: CheckCircle },
    { text: 'Weight at least 50 kg (110 lbs)', icon: CheckCircle },
    { text: 'Healthy and feeling well', icon: CheckCircle },
    { text: 'No recent illness or infection', icon: AlertCircle }
  ];

  const faqs = [
    {
      question: 'Is blood donation safe?',
      answer: 'Yes, blood donation is completely safe. Sterile, single-use equipment is used.'
    },
    {
      question: 'How often can I donate?',
      answer: 'Healthy adults can donate every 3 months (whole blood donation).'
    },
    {
      question: 'Will it hurt?',
      answer: 'You may feel a slight pinch, but most donors report minimal discomfort.'
    }
  ];

  return (
    <div className="min-h-screen bg-white">
      {/* Header/Navigation */}
      <header className="bg-white border-b border-gray-200 sticky top-0 z-50 shadow-sm">
        <div className="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
          <div className="flex items-center gap-3">
            <div className="bg-red-600 p-2 rounded-lg">
              <Heart className="size-6 text-white fill-white" />
            </div>
            <div>
              <div className="text-red-600">BloodConnect</div>
              <div className="text-gray-500">Save Lives Today</div>
            </div>
          </div>
          <div className="flex items-center gap-3">
            <Button 
              onClick={() => navigate('/auth/login')}
              className="bg-red-600 hover:bg-red-700"
            >
              <Heart className="size-4 mr-2" />
              Donate Now
            </Button>
          </div>
        </div>
      </header>

      {/* Hero Banner */}
      <section className="bg-gradient-to-br from-red-50 via-white to-red-50 py-20 px-6">
        <div className="max-w-7xl mx-auto">
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
            <div>
              <div className="inline-block bg-red-100 text-red-600 px-4 py-2 rounded-full mb-6">
                <Droplet className="size-4 inline mr-2" />
                Every 2 Seconds Someone Needs Blood
              </div>
              <h1 className="text-red-600 mb-6">
                Donate Blood, Save Lives
              </h1>
              <p className="text-gray-600 mb-8">
                Join our community of heroes. Your single donation can save up to three lives. 
                Register today and make a difference in someone's life.
              </p>
              <div className="flex flex-wrap gap-4">
                <Button 
                  onClick={() => navigate('/auth/register?role=donor')}
                  className="bg-red-600 hover:bg-red-700"
                  size="lg"
                >
                  Become a Donor
                  <ArrowRight className="size-4 ml-2" />
                </Button>
                <Button 
                  onClick={() => navigate('/auth/login')}
                  variant="outline"
                  size="lg"
                  className="border-red-600 text-red-600 hover:bg-red-50"
                >
                  <LogIn className="size-4 mr-2" />
                  Login
                </Button>
              </div>
              <div className="mt-8 flex items-center gap-8 text-gray-600">
                <div>
                  <div className="text-red-600">50K+</div>
                  <div>Donors Registered</div>
                </div>
                <div>
                  <div className="text-red-600">100K+</div>
                  <div>Lives Saved</div>
                </div>
                <div>
                  <div className="text-red-600">24/7</div>
                  <div>Emergency Support</div>
                </div>
              </div>
            </div>
            <div className="relative">
              <div className="bg-gradient-to-br from-red-100 to-red-50 rounded-2xl p-12 shadow-2xl">
                <Droplet className="size-64 text-red-600 opacity-20 mx-auto" />
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* Why Donate Blood Section */}
      <section className="py-16 px-6">
        <div className="max-w-7xl mx-auto">
          <div className="text-center mb-12">
            <h2 className="text-gray-900 mb-4">Why Donate Blood?</h2>
            <p className="text-gray-600 max-w-2xl mx-auto">
              Blood donation is a simple act that can make a profound difference. 
              Here's why your contribution matters.
            </p>
          </div>
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            {whyDonateReasons.map((reason, index) => (
              <Card key={index} className="p-6 text-center hover:shadow-lg transition-all border border-gray-200">
                <div className="bg-red-100 size-16 rounded-full flex items-center justify-center mx-auto mb-4">
                  <reason.icon className="size-8 text-red-600" />
                </div>
                <h3 className="text-gray-900 mb-2">{reason.title}</h3>
                <p className="text-gray-600">{reason.description}</p>
              </Card>
            ))}
          </div>
        </div>
      </section>

      {/* Eligibility Rules Preview */}
      <section className="py-16 px-6 bg-gray-50">
        <div className="max-w-7xl mx-auto">
          <div className="max-w-3xl mx-auto">
            <div className="text-center mb-8">
              <h2 className="text-gray-900 mb-4">Are You Eligible to Donate?</h2>
              <p className="text-gray-600">
                Most healthy adults can donate blood. Check these basic eligibility criteria 
                to see if you qualify to become a blood donor.
              </p>
            </div>
            <div className="space-y-4 mb-8">
              {eligibilityRules.map((rule, index) => (
                <div key={index} className="flex items-start gap-3">
                  <rule.icon className={`size-6 ${rule.icon === CheckCircle ? 'text-green-600' : 'text-amber-600'} flex-shrink-0 mt-0.5`} />
                  <p className="text-gray-700">{rule.text}</p>
                </div>
              ))}
            </div>
            <div className="text-center">
              <Button 
                onClick={() => navigate('/guest/eligibility')}
                className="bg-red-600 hover:bg-red-700"
              >
                View Full Eligibility Criteria
                <ChevronRight className="size-4 ml-2" />
              </Button>
            </div>
          </div>
        </div>
      </section>

      {/* FAQ Preview */}
      <section className="py-16 px-6">
        <div className="max-w-7xl mx-auto">
          <div className="text-center mb-12">
            <h2 className="text-gray-900 mb-4">Frequently Asked Questions</h2>
            <p className="text-gray-600">Get answers to common questions about blood donation</p>
          </div>
          <div className="max-w-3xl mx-auto space-y-4">
            {faqs.map((faq, index) => (
              <Card key={index} className="p-6 border border-gray-200 hover:shadow-md transition-all">
                <div className="flex items-start gap-4">
                  <HelpCircle className="size-6 text-red-600 flex-shrink-0 mt-1" />
                  <div className="flex-1">
                    <h4 className="text-gray-900 mb-2">{faq.question}</h4>
                    <p className="text-gray-600">{faq.answer}</p>
                  </div>
                </div>
              </Card>
            ))}
          </div>
          <div className="text-center mt-8">
            <Button 
              onClick={() => navigate('/guest/faq')}
              variant="outline"
              className="border-red-600 text-red-600 hover:bg-red-50"
            >
              View All FAQs
              <ChevronRight className="size-4 ml-2" />
            </Button>
          </div>
        </div>
      </section>

      {/* Track Request Section */}
      <section className="py-16 px-6 bg-white">
        <div className="max-w-7xl mx-auto">
          <Card className="p-8 bg-gradient-to-r from-blue-50 to-white border-blue-200">
            <div className="max-w-2xl mx-auto">
              <div className="text-center mb-6">
                <h2 className="text-gray-900 mb-2">Track Your Blood Request</h2>
                <p className="text-gray-600">Enter your phone number or request ID to check the status of your blood request</p>
              </div>
              
              <form 
                onSubmit={(e) => {
                  e.preventDefault();
                  if (trackingInput.trim()) {
                    navigate(`/guest/track-request?query=${encodeURIComponent(trackingInput)}`);
                  }
                }}
                className="flex gap-3"
              >
                <input
                  type="text"
                  placeholder="Enter Phone Number or Request ID"
                  value={trackingInput}
                  onChange={(e) => setTrackingInput(e.target.value)}
                  className="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                  required
                />
                <Button 
                  type="submit"
                  className="bg-blue-600 hover:bg-blue-700 px-8"
                >
                  <Search className="size-5 mr-2" />
                  Track Request
                </Button>
              </form>
              
              <p className="text-gray-500 text-center mt-4">
                Try: REQ001, REQ003, 555-1234, or 555-5678
              </p>
            </div>
          </Card>
        </div>
      </section>

      {/* Footer */}
      <footer className="bg-gray-900 text-white py-12 px-6">
        <div className="max-w-7xl mx-auto">
          <div className="grid grid-cols-1 md:grid-cols-4 gap-8">
            <div>
              <div className="flex items-center gap-2 mb-4">
                <Heart className="size-6 text-red-600 fill-red-600" />
                <span>BloodConnect</span>
              </div>
              <p className="text-gray-400">
                Connecting donors with those in need, saving lives every day.
              </p>
            </div>
            <div>
              <h4 className="text-white mb-4">Quick Links</h4>
              <ul className="space-y-2 text-gray-400">
                <li className="cursor-pointer hover:text-white" onClick={() => navigate('/guest/home')}>Home</li>
                <li className="cursor-pointer hover:text-white" onClick={() => navigate('/guest/why-donate')}>Why Donate</li>
                <li className="cursor-pointer hover:text-white" onClick={() => navigate('/guest/eligibility')}>Eligibility</li>
                <li className="cursor-pointer hover:text-white" onClick={() => navigate('/guest/faq')}>FAQs</li>
              </ul>
            </div>
            <div>
              <h4 className="text-white mb-4">Portals</h4>
              <ul className="space-y-2 text-gray-400">
                <li className="cursor-pointer hover:text-white" onClick={() => navigate('/portal-login')}>Portal Login</li>
                <li className="cursor-pointer hover:text-white" onClick={() => navigate('/admin/dashboard')}>Admin</li>
                <li className="cursor-pointer hover:text-white" onClick={() => navigate('/hospital/dashboard')}>Hospital</li>
                <li className="cursor-pointer hover:text-white" onClick={() => navigate('/donor/dashboard')}>Donor</li>
              </ul>
            </div>
            <div>
              <h4 className="text-white mb-4">Contact</h4>
              <ul className="space-y-2 text-gray-400">
                <li>Email: info@bloodconnect.org</li>
                <li>Phone: 1-800-BLOOD-NOW</li>
                <li>Emergency: 24/7 Available</li>
              </ul>
            </div>
          </div>
          <div className="mt-8 pt-8 border-t border-gray-800 text-center text-gray-400">
            <p>&copy; 2024 BloodConnect. All rights reserved. Saving lives together.</p>
          </div>
        </div>
      </footer>
    </div>
  );
}