import { NavLink } from 'react-router-dom';
import { Heart } from 'lucide-react';
import { cn } from '../../lib/utils';

interface SidebarProps {
  items: {
    path: string;
    label: string;
    icon: React.ElementType;
  }[];
  title: string;
}

export default function Sidebar({ items, title }: SidebarProps) {
  return (
    <div className="w-64 bg-white border-r border-gray-200 h-screen fixed left-0 top-0 flex flex-col">
      <div className="p-6 border-b border-gray-200">
        <div className="flex items-center gap-3">
          <div className="bg-red-600 p-2 rounded-lg">
            <Heart className="size-6 text-white fill-white" />
          </div>
          <div>
            <div className="text-red-600">BloodConnect</div>
            <div className="text-gray-500">{title}</div>
          </div>
        </div>
      </div>

      <nav className="flex-1 overflow-y-auto py-4">
        {items.map((item) => (
          <NavLink
            key={item.path}
            to={item.path}
            className={({ isActive }) =>
              cn(
                'flex items-center gap-3 px-6 py-3 text-gray-700 hover:bg-red-50 hover:text-red-600 transition-colors',
                isActive && 'bg-red-50 text-red-600 border-r-4 border-red-600'
              )
            }
          >
            <item.icon className="size-5" />
            <span>{item.label}</span>
          </NavLink>
        ))}
      </nav>

      <div className="p-4 border-t border-gray-200">
        <div className="text-center text-gray-500">
          <p>Emergency: <span className="text-red-600">911</span></p>
        </div>
      </div>
    </div>
  );
}
