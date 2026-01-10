import { Bell, Search, User, LogOut } from 'lucide-react';
import { Button } from '../ui/button';
import { Badge } from '../ui/badge';
import { useNavigate } from 'react-router-dom';

interface HeaderProps {
  userName?: string;
  userRole?: string;
  notifications?: number;
}

export default function Header({ userName = 'Admin User', userRole = 'Administrator', notifications = 3 }: HeaderProps) {
  const navigate = useNavigate();

  return (
    <header className="h-16 bg-white border-b border-gray-200 fixed top-0 left-64 right-0 z-10 flex items-center justify-between px-8">
      <div className="flex items-center gap-4 flex-1">
        <div className="relative flex-1 max-w-md">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 size-5 text-gray-400" />
          <input
            type="text"
            placeholder="Search..."
            className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500"
          />
        </div>
      </div>

      <div className="flex items-center gap-4">
        <button className="relative p-2 hover:bg-gray-100 rounded-lg transition-colors">
          <Bell className="size-5 text-gray-700" />
          {notifications > 0 && (
            <Badge className="absolute -top-1 -right-1 size-5 flex items-center justify-center p-0 bg-red-600 text-white">
              {notifications}
            </Badge>
          )}
        </button>

        <div className="flex items-center gap-3 pl-4 border-l border-gray-200">
          <div className="text-right">
            <div className="text-gray-900">{userName}</div>
            <div className="text-gray-500">{userRole}</div>
          </div>
          <div className="size-10 bg-red-100 rounded-full flex items-center justify-center">
            <User className="size-5 text-red-600" />
          </div>
        </div>

        <Button 
          variant="ghost" 
          size="sm"
          onClick={() => navigate('/')}
          className="text-gray-600 hover:text-red-600"
        >
          <LogOut className="size-4" />
        </Button>
      </div>
    </header>
  );
}
