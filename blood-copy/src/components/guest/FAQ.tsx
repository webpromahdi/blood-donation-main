import { ChevronDown, ArrowLeft } from 'lucide-react';
import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { Card } from '../ui/card';
import { Button } from '../ui/button';

const faqItems = [
  {
    question: "Who can donate blood?",
    answer: "Generally, anyone who is healthy, weighs at least 110 pounds, and is 17 years or older can donate blood. Specific eligibility requirements may vary by location and individual health conditions."
  },
  {
    question: "How often can I donate blood?",
    answer: "Whole blood donations can be made every 56 days (8 weeks). Platelet donations can be made every 7 days, up to 24 times per year. Double red cell donations can be made every 112 days (16 weeks)."
  },
  {
    question: "Is blood donation safe?",
    answer: "Yes, blood donation is very safe. Only sterile, single-use needles and equipment are used, and a trained professional performs the donation. There is no risk of contracting a disease by donating blood."
  },
  {
    question: "How long does blood donation take?",
    answer: "The entire process takes about 1 hour, including registration, health screening, the donation itself (8-10 minutes), and refreshments afterward. The actual blood collection takes only about 8-10 minutes."
  },
  {
    question: "What should I do before donating blood?",
    answer: "Get a good night's sleep, eat a healthy meal, drink plenty of fluids, and avoid fatty foods. Bring your donor card or ID, and a list of medications you're taking."
  },
  {
    question: "What happens to my blood after donation?",
    answer: "Your blood is tested for blood type and infectious diseases, then separated into components (red cells, platelets, plasma). These components are stored and distributed to hospitals based on patient needs."
  },
  {
    question: "Can I donate if I'm taking medication?",
    answer: "Most medications do not disqualify you from donating blood. However, certain medications may require a waiting period. Please consult with our medical staff about your specific medications."
  },
  {
    question: "Will I feel weak after donating?",
    answer: "Most people feel fine after donating. You may feel lightheaded or dizzy for a brief period. We provide refreshments and ask you to rest for 10-15 minutes after donation. Drink plenty of fluids and avoid strenuous activity for the rest of the day."
  },
  {
    question: "What is my blood type used for?",
    answer: "Different blood types are used for different purposes. O-negative is the universal donor type, used in emergencies. AB-positive is the universal plasma donor. Your specific blood type will be matched to patients who need it."
  },
  {
    question: "Can I donate if I have a tattoo or piercing?",
    answer: "Generally yes, but you may need to wait 3-12 months after getting a tattoo or piercing, depending on where it was done and local regulations. Contact us for specific guidance."
  },
  {
    question: "How much blood is taken during donation?",
    answer: "For a whole blood donation, approximately 1 pint (about 450-500 ml) is collected. This represents less than 10% of your total blood volume, which your body replaces within 24-48 hours."
  },
  {
    question: "Can I donate blood if I'm traveling?",
    answer: "Travel to certain countries may temporarily defer you from donating blood due to risk of exposure to infectious diseases. The deferral period varies by destination. Please check with us about your specific travel history."
  }
];

export default function FAQ() {
  const [openIndex, setOpenIndex] = useState<number | null>(null);
  const navigate = useNavigate();

  const toggleQuestion = (index: number) => {
    setOpenIndex(openIndex === index ? null : index);
  };

  return (
    <div className="min-h-screen bg-gray-50">
      <div className="max-w-4xl mx-auto px-6 py-8">
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
            <h1 className="text-red-600 mb-2">Frequently Asked Questions</h1>
            <p className="text-gray-600">Find answers to common questions about blood donation</p>
          </div>

          <div className="space-y-4">
            {faqItems.map((item, index) => (
              <Card key={index} className="overflow-hidden">
                <button
                  onClick={() => toggleQuestion(index)}
                  className="w-full flex items-center justify-between p-6 text-left hover:bg-gray-50 transition-colors"
                >
                  <span className="text-gray-900 pr-4">{item.question}</span>
                  <ChevronDown
                    className={`size-5 text-gray-500 flex-shrink-0 transition-transform ${
                      openIndex === index ? 'transform rotate-180' : ''
                    }`}
                  />
                </button>
                {openIndex === index && (
                  <div className="px-6 pb-6 text-gray-600 border-t border-gray-100 pt-4">
                    {item.answer}
                  </div>
                )}
              </Card>
            ))}
          </div>

          <Card className="bg-red-50 border-red-200 p-6">
            <h2 className="text-red-600 mb-2">Still Have Questions?</h2>
            <p className="text-gray-700 mb-4">
              Can't find the answer you're looking for? Our team is here to help!
            </p>
            <div className="space-y-2 text-gray-700">
              <p><strong>Phone:</strong> 1-800-RED-CROSS</p>
              <p><strong>Email:</strong> info@bloodconnect.org</p>
              <p><strong>Hours:</strong> Monday - Friday, 9:00 AM - 5:00 PM</p>
            </div>
          </Card>
        </div>
      </div>
    </div>
  );
}
