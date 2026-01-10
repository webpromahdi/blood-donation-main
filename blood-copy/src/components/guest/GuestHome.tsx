import { Heart, Droplet, Users, Building2, ArrowRight, CheckCircle, Search } from 'lucide-react';
import { Button } from '../ui/button';
import { Card } from '../ui/card';
import { useNavigate } from 'react-router-dom';
import { ImageWithFallback } from '../figma/ImageWithFallback';
import { useState } from 'react';

export default function GuestHome() {
  const navigate = useNavigate();
  const [trackingInput, setTrackingInput] = useState('');

  return (
    <div className="min-h-screen bg-white">
      {/* Header/Navigation */}
      <header className="bg-white border-b border-gray-200 sticky top-0 z-50">
        <div className="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
          <div className="flex items-center gap-3">
            <Heart className="size-8 text-red-600 fill-red-600" />
            <div>
              <div className="text-red-600">BloodConnect</div>
              <div className="text-gray-500">Save Lives Together</div>
            </div>
          </div>
          <nav className="flex items-center gap-6">
            <button 
              onClick={() => navigate('/guest/why-donate')}
              className="text-gray-700 hover:text-red-600 transition-colors"
            >
              Why Donate
            </button>
            <button 
              onClick={() => navigate('/guest/eligibility')}
              className="text-gray-700 hover:text-red-600 transition-colors"
            >
              Eligibility
            </button>
            <button 
              onClick={() => navigate('/guest/faq')}
              className="text-gray-700 hover:text-red-600 transition-colors"
            >
              FAQ
            </button>
            <Button 
              className="bg-red-600 hover:bg-red-700"
              onClick={() => navigate('/')}
            >
              Get Started
            </Button>
          </nav>
        </div>
      </header>

      {/* Hero Section */}
      <section className="bg-gradient-to-br from-red-50 to-white py-20">
        <div className="max-w-7xl mx-auto px-6">
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
            <div>
              <h1 className="text-gray-900 mb-6">Every Drop Counts. Be a Hero Today.</h1>
              <p className="text-gray-600 mb-8">
                Join thousands of donors who save lives every day. Your blood donation can make the difference between life and death for someone in need.
              </p>
              <div className="flex gap-4">
                <Button 
                  className="bg-red-600 hover:bg-red-700"
                  size="lg"
                  onClick={() => navigate('/donor/dashboard')}
                >
                  <Heart className="size-5 mr-2" />
                  Become a Donor
                </Button>
                <Button 
                  variant="outline" 
                  size="lg"
                  onClick={() => navigate('/seeker/request')}
                >
                  Request Blood
                  <ArrowRight className="size-5 ml-2" />
                </Button>
              </div>
              
              {/* Stats */}
              <div className="grid grid-cols-3 gap-6 mt-12 pt-12 border-t border-gray-200">
                <div>
                  <h3 className="text-gray-900 mb-1">1,284</h3>
                  <p className="text-gray-600">Active Donors</p>
                </div>
                <div>
                  <h3 className="text-gray-900 mb-1">47</h3>
                  <p className="text-gray-600">Partner Hospitals</p>
                </div>
                <div>
                  <h3 className="text-gray-900 mb-1">3,200+</h3>
                  <p className="text-gray-600">Lives Saved</p>
                </div>
              </div>
            </div>

            <div className="relative">
              <div className="rounded-2xl overflow-hidden shadow-2xl">
                <ImageWithFallback
                  src="https://images.unsplash.com/photo-1615461066159-fea0960485d5?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxtZWRpY2FsJTIwYmxvb2QlMjBkb25hdGlvbiUyMGhlYWx0aGNhcmV8ZW58MXx8fHwxNzY0MDExNDI3fDA&ixlib=rb-4.1.0&q=80&w=1080"
                  alt="Blood Donation"
                  className="w-full h-[500px] object-cover"
                />
              </div>
              <div className="absolute -bottom-6 -left-6 bg-white p-6 rounded-lg shadow-xl">
                <div className="flex items-center gap-3">
                  <div className="size-12 bg-red-100 rounded-full flex items-center justify-center">
                    <Heart className="size-6 text-red-600 fill-red-600" />
                  </div>
                  <div>
                    <div className="text-gray-900">8 Donations</div>
                    <div className="text-gray-600">This Week</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* Track Request Section */}
      <section className="py-16 bg-white">
        <div className="max-w-7xl mx-auto px-6">
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
            </div>
          </Card>
        </div>
      </section>

      {/* Why Donate Section */}
      <section className="py-20 bg-white">
        <div className="max-w-7xl mx-auto px-6">
          <div className="text-center mb-16">
            <h2 className="text-gray-900 mb-4">Why Donate Blood?</h2>
            <p className="text-gray-600 max-w-2xl mx-auto">
              Blood donation is a simple act that can save up to three lives. Here's why your donation matters.
            </p>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
            <Card className="p-8 hover:shadow-xl transition-shadow">
              <div className="size-16 bg-red-100 rounded-lg flex items-center justify-center mb-6">
                <Heart className="size-8 text-red-600 fill-red-600" />
              </div>
              <h3 className="text-gray-900 mb-3">Save Lives</h3>
              <p className="text-gray-600">
                One donation can save up to three lives. Your blood helps patients during surgeries, cancer treatment, and emergencies.
              </p>
            </Card>

            <Card className="p-8 hover:shadow-xl transition-shadow">
              <div className="size-16 bg-green-100 rounded-lg flex items-center justify-center mb-6">
                <CheckCircle className="size-8 text-green-600" />
              </div>
              <h3 className="text-gray-900 mb-3">Health Benefits</h3>
              <p className="text-gray-600">
                Regular blood donation improves cardiovascular health, reduces iron overload, and promotes overall wellbeing.
              </p>
            </Card>

            <Card className="p-8 hover:shadow-xl transition-shadow">
              <div className="size-16 bg-blue-100 rounded-lg flex items-center justify-center mb-6">
                <Users className="size-8 text-blue-600" />
              </div>
              <h3 className="text-gray-900 mb-3">Community Impact</h3>
              <p className="text-gray-600">
                Join a community of heroes making a real difference. Your contribution strengthens healthcare for everyone.
              </p>
            </Card>
          </div>
        </div>
      </section>

      {/* How It Works */}
      <section className="py-20 bg-gray-50">
        <div className="max-w-7xl mx-auto px-6">
          <div className="text-center mb-16">
            <h2 className="text-gray-900 mb-4">How It Works</h2>
            <p className="text-gray-600">Simple steps to start saving lives</p>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-4 gap-8">
            {[
              { step: 1, title: 'Register', desc: 'Sign up as a blood donor', icon: Users },
              { step: 2, title: 'Get Notified', desc: 'Receive alerts for matching requests', icon: Droplet },
              { step: 3, title: 'Donate', desc: 'Visit hospital and donate blood', icon: Heart },
              { step: 4, title: 'Save Lives', desc: 'Get certificate and track impact', icon: CheckCircle },
            ].map((item) => (
              <div key={item.step} className="text-center">
                <div className="size-16 bg-red-600 text-white rounded-full flex items-center justify-center mx-auto mb-4">
                  <item.icon className="size-8" />
                </div>
                <div className="size-8 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto mb-4">
                  {item.step}
                </div>
                <h3 className="text-gray-900 mb-2">{item.title}</h3>
                <p className="text-gray-600">{item.desc}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* CTA Section */}
      <section className="py-20 bg-gradient-to-r from-red-600 to-red-700 text-white">
        <div className="max-w-4xl mx-auto px-6 text-center">
          <h2 className="text-white mb-4">Ready to Make a Difference?</h2>
          <p className="mb-8 opacity-90">
            Join our community of donors today and start saving lives. Registration is quick and easy.
          </p>
          <div className="flex gap-4 justify-center">
            <Button 
              size="lg" 
              className="bg-white text-red-600 hover:bg-gray-100"
              onClick={() => navigate('/donor/dashboard')}
            >
              <Heart className="size-5 mr-2" />
              Register as Donor
            </Button>
            <Button 
              size="lg" 
              variant="outline" 
              className="border-white text-white hover:bg-white/10"
              onClick={() => navigate('/guest/why-donate')}
            >
              Learn More
              <ArrowRight className="size-5 ml-2" />
            </Button>
          </div>
        </div>
      </section>

      {/* Footer */}
      <footer className="bg-gray-900 text-white py-12">
        <div className="max-w-7xl mx-auto px-6">
          <div className="grid grid-cols-1 md:grid-cols-4 gap-8">
            <div>
              <div className="flex items-center gap-3 mb-4">
                <Heart className="size-6 fill-red-600 text-red-600" />
                <span className="text-red-600">BloodConnect</span>
              </div>
              <p className="text-gray-400">
                Connecting donors with those in need. Save lives together.
              </p>
            </div>
            <div>
              <h3 className="text-white mb-4">Quick Links</h3>
              <ul className="space-y-2 text-gray-400">
                <li><button onClick={() => navigate('/guest/why-donate')} className="hover:text-white">Why Donate</button></li>
                <li><button onClick={() => navigate('/guest/eligibility')} className="hover:text-white">Eligibility</button></li>
                <li><button onClick={() => navigate('/guest/faq')} className="hover:text-white">FAQ</button></li>
              </ul>
            </div>
            <div>
              <h3 className="text-white mb-4">Portals</h3>
              <ul className="space-y-2 text-gray-400">
                <li><button onClick={() => navigate('/donor/dashboard')} className="hover:text-white">Donor Portal</button></li>
                <li><button onClick={() => navigate('/hospital/dashboard')} className="hover:text-white">Hospital Portal</button></li>
                <li><button onClick={() => navigate('/seeker/request')} className="hover:text-white">Blood Seeker</button></li>
              </ul>
            </div>
            <div>
              <h3 className="text-white mb-4">Emergency</h3>
              <p className="text-red-400">1-800-BLOOD-NOW</p>
              <p className="text-gray-400 mt-2">Available 24/7</p>
            </div>
          </div>
          <div className="border-t border-gray-800 mt-8 pt-8 text-center text-gray-400">
            <p>© 2024 BloodConnect. All rights reserved.</p>
          </div>
        </div>
      </footer>
    </div>
  );
}
