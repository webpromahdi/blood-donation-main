import { Heart, CheckCircle, Users, Activity, Award, ArrowLeft } from 'lucide-react';
import { Button } from '../ui/button';
import { Card } from '../ui/card';
import { useNavigate } from 'react-router-dom';
import { ImageWithFallback } from '../figma/ImageWithFallback';

export default function WhyDonate() {
  const navigate = useNavigate();

  return (
    <div className="min-h-screen bg-white">
      {/* Header */}
      <header className="bg-white border-b border-gray-200">
        <div className="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
          <div className="flex items-center gap-3">
            <Heart className="size-8 text-red-600 fill-red-600" />
            <div>
              <div className="text-red-600">BloodConnect</div>
              <div className="text-gray-500">Save Lives Together</div>
            </div>
          </div>
          <Button variant="outline" onClick={() => navigate('/guest/home')}>
            <ArrowLeft className="size-4 mr-2" />
            Back to Home
          </Button>
        </div>
      </header>

      {/* Hero */}
      <section className="bg-gradient-to-br from-red-50 to-white py-16">
        <div className="max-w-7xl mx-auto px-6">
          <div className="max-w-3xl mx-auto text-center">
            <h1 className="text-gray-900 mb-6">Why Donate Blood?</h1>
            <p className="text-gray-600 mb-8">
              Blood donation is one of the most valuable contributions you can make to society. 
              Every donation has the power to save multiple lives and make a lasting impact on your community.
            </p>
          </div>
        </div>
      </section>

      {/* Image Section */}
      <section className="py-12 bg-white">
        <div className="max-w-7xl mx-auto px-6">
          <div className="rounded-2xl overflow-hidden shadow-xl">
            <ImageWithFallback
              src="https://images.unsplash.com/photo-1761250027507-c0be614c0254?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxwZW9wbGUlMjBoZWxwaW5nJTIwY29tbXVuaXR5fGVufDF8fHx8MTc2Mzk3ODc3N3ww&ixlib=rb-4.1.0&q=80&w=1080"
              alt="People helping community"
              className="w-full h-[400px] object-cover"
            />
          </div>
        </div>
      </section>

      {/* Main Benefits */}
      <section className="py-20 bg-white">
        <div className="max-w-7xl mx-auto px-6">
          <div className="text-center mb-16">
            <h2 className="text-gray-900 mb-4">The Impact of Your Donation</h2>
            <p className="text-gray-600">Here's why your blood donation matters</p>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <Card className="p-8">
              <div className="size-16 bg-red-100 rounded-lg flex items-center justify-center mb-6">
                <Heart className="size-8 text-red-600 fill-red-600" />
              </div>
              <h3 className="text-gray-900 mb-3">Save Multiple Lives</h3>
              <p className="text-gray-600">
                A single blood donation can save up to three lives. Your blood components (red cells, platelets, plasma) can help different patients.
              </p>
            </Card>

            <Card className="p-8">
              <div className="size-16 bg-blue-100 rounded-lg flex items-center justify-center mb-6">
                <Users className="size-8 text-blue-600" />
              </div>
              <h3 className="text-gray-900 mb-3">Help Cancer Patients</h3>
              <p className="text-gray-600">
                Cancer patients undergoing chemotherapy often need regular blood transfusions. Your donation directly supports their treatment.
              </p>
            </Card>

            <Card className="p-8">
              <div className="size-16 bg-green-100 rounded-lg flex items-center justify-center mb-6">
                <Activity className="size-8 text-green-600" />
              </div>
              <h3 className="text-gray-900 mb-3">Emergency Situations</h3>
              <p className="text-gray-600">
                Accident victims and emergency surgery patients rely on blood donations. Your contribution can mean the difference in critical moments.
              </p>
            </Card>
          </div>
        </div>
      </section>

      {/* Health Benefits */}
      <section className="py-20 bg-gray-50">
        <div className="max-w-7xl mx-auto px-6">
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
            <div>
              <h2 className="text-gray-900 mb-6">Health Benefits for Donors</h2>
              <p className="text-gray-600 mb-8">
                Donating blood isn't just good for others—it benefits your health too. Regular blood donation has been linked to several health advantages.
              </p>
              
              <div className="space-y-4">
                {[
                  'Free health screening before each donation',
                  'Reduces risk of heart disease',
                  'Helps maintain healthy iron levels',
                  'Burns calories (about 650 per donation)',
                  'Stimulates production of new blood cells',
                  'Provides psychological satisfaction',
                ].map((benefit) => (
                  <div key={benefit} className="flex items-start gap-3">
                    <CheckCircle className="size-6 text-green-600 flex-shrink-0 mt-0.5" />
                    <span className="text-gray-700">{benefit}</span>
                  </div>
                ))}
              </div>
            </div>

            <div className="grid grid-cols-2 gap-4">
              <Card className="p-6">
                <div className="size-12 bg-red-100 rounded-lg flex items-center justify-center mb-4">
                  <Heart className="size-6 text-red-600" />
                </div>
                <h3 className="text-gray-900 mb-2">3 Lives</h3>
                <p className="text-gray-600">Saved per donation</p>
              </Card>
              <Card className="p-6">
                <div className="size-12 bg-blue-100 rounded-lg flex items-center justify-center mb-4">
                  <Activity className="size-6 text-blue-600" />
                </div>
                <h3 className="text-gray-900 mb-2">45 Minutes</h3>
                <p className="text-gray-600">Average donation time</p>
              </Card>
              <Card className="p-6">
                <div className="size-12 bg-green-100 rounded-lg flex items-center justify-center mb-4">
                  <CheckCircle className="size-6 text-green-600" />
                </div>
                <h3 className="text-gray-900 mb-2">56 Days</h3>
                <p className="text-gray-600">Between donations</p>
              </Card>
              <Card className="p-6">
                <div className="size-12 bg-purple-100 rounded-lg flex items-center justify-center mb-4">
                  <Award className="size-6 text-purple-600" />
                </div>
                <h3 className="text-gray-900 mb-2">Safe & Easy</h3>
                <p className="text-gray-600">Process guaranteed</p>
              </Card>
            </div>
          </div>
        </div>
      </section>

      {/* Who Needs Blood */}
      <section className="py-20 bg-white">
        <div className="max-w-7xl mx-auto px-6">
          <div className="text-center mb-16">
            <h2 className="text-gray-900 mb-4">Who Needs Blood?</h2>
            <p className="text-gray-600">Your donation helps various patients in need</p>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            {[
              { title: 'Cancer Patients', desc: 'Chemotherapy and radiation therapy often require blood transfusions', percentage: '25%' },
              { title: 'Surgery Patients', desc: 'Major surgeries require blood transfusions for safety', percentage: '20%' },
              { title: 'Accident Victims', desc: 'Trauma from accidents requires immediate blood supply', percentage: '15%' },
              { title: 'Anemia Patients', desc: 'Chronic anemia and blood disorders need regular transfusions', percentage: '18%' },
              { title: 'Pregnancy Complications', desc: 'Maternal health emergencies require blood support', percentage: '12%' },
              { title: 'Others', desc: 'Various medical conditions and treatments', percentage: '10%' },
            ].map((item) => (
              <Card key={item.title} className="p-6 hover:shadow-lg transition-shadow">
                <div className="flex items-start justify-between mb-3">
                  <h3 className="text-gray-900">{item.title}</h3>
                  <span className="text-red-600">{item.percentage}</span>
                </div>
                <p className="text-gray-600">{item.desc}</p>
              </Card>
            ))}
          </div>
        </div>
      </section>

      {/* CTA */}
      <section className="py-20 bg-gradient-to-r from-red-600 to-red-700 text-white">
        <div className="max-w-4xl mx-auto px-6 text-center">
          <h2 className="text-white mb-4">Ready to Save Lives?</h2>
          <p className="mb-8 opacity-90">
            Join thousands of donors making a difference every day. Your journey to becoming a hero starts here.
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
              onClick={() => navigate('/guest/eligibility')}
            >
              Check Eligibility
            </Button>
          </div>
        </div>
      </section>

      {/* Footer */}
      <footer className="bg-gray-900 text-white py-12">
        <div className="max-w-7xl mx-auto px-6 text-center">
          <div className="flex items-center justify-center gap-3 mb-4">
            <Heart className="size-6 fill-red-600 text-red-600" />
            <span className="text-red-600">BloodConnect</span>
          </div>
          <p className="text-gray-400">© 2024 BloodConnect. All rights reserved.</p>
        </div>
      </footer>
    </div>
  );
}
