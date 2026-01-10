import { CheckCircle, XCircle, ArrowLeft } from 'lucide-react';
import { Button } from '../ui/button';
import { Card } from '../ui/card';
import { useNavigate } from 'react-router-dom';

export default function EligibilityRules() {
  const navigate = useNavigate();

  return (
    <div className="min-h-screen bg-gray-50">
      <div className="max-w-7xl mx-auto px-6 py-8">
        <Button
          onClick={() => navigate('/')}
          variant="outline"
          className="mb-6 border-red-600 text-red-600 hover:bg-red-50"
        >
          <ArrowLeft className="size-4 mr-2" />
          Back to Home
        </Button>

        <div className="space-y-6">
          <div>
            <h1 className="text-red-600 mb-2">Blood Donation Eligibility Criteria</h1>
            <p className="text-gray-600">Check if you meet the requirements to donate blood</p>
          </div>

          <div className="grid grid-cols-1 lg:grid-cols-2 gap-12">
            {/* You CAN Donate */}
            <div>
              <div className="flex items-center gap-3 mb-8">
                <CheckCircle className="size-8 text-green-600" />
                <h2 className="text-gray-900">You CAN Donate If:</h2>
              </div>

              <Card className="p-6">
                <ul className="space-y-4">
                  {[
                    'You are between 18-65 years old',
                    'You weigh at least 50 kg (110 lbs)',
                    'You are in good general health',
                    'Your hemoglobin level is normal',
                    'You have not donated blood in last 56 days',
                    'Your blood pressure is normal',
                    'You are well-rested and have eaten',
                    'You are not pregnant or breastfeeding',
                    'You have no recent tattoos (within 6 months)',
                    'You have not had major surgery recently',
                  ].map((item) => (
                    <li key={item} className="flex items-start gap-3">
                      <CheckCircle className="size-5 text-green-600 flex-shrink-0 mt-0.5" />
                      <span className="text-gray-700">{item}</span>
                    </li>
                  ))}
                </ul>
              </Card>
            </div>

            {/* You CANNOT Donate */}
            <div>
              <div className="flex items-center gap-3 mb-8">
                <XCircle className="size-8 text-red-600" />
                <h2 className="text-gray-900">You CANNOT Donate If:</h2>
              </div>

              <Card className="p-6">
                <ul className="space-y-4">
                  {[
                    'You are currently sick with cold or flu',
                    'You have a fever or infection',
                    'You are pregnant or recently gave birth',
                    'You have HIV/AIDS or hepatitis',
                    'You have heart disease or blood disorders',
                    'You are taking certain medications',
                    'You have had recent dental work',
                    'You have active tuberculosis',
                    'You have recently traveled to malaria-endemic areas',
                    'You are under the influence of alcohol',
                  ].map((item) => (
                    <li key={item} className="flex items-start gap-3">
                      <XCircle className="size-5 text-red-600 flex-shrink-0 mt-0.5" />
                      <span className="text-gray-700">{item}</span>
                    </li>
                  ))}
                </ul>
              </Card>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
