import Sidebar from './Sidebar';
import Header from './Header';

interface DashboardLayoutProps {
  children: React.ReactNode;
  sidebarItems: {
    path: string;
    label: string;
    icon: React.ElementType;
  }[];
  sidebarTitle: string;
  userName?: string;
  userRole?: string;
  notifications?: number;
}

export default function DashboardLayout({
  children,
  sidebarItems,
  sidebarTitle,
  userName,
  userRole,
  notifications
}: DashboardLayoutProps) {
  return (
    <div className="min-h-screen bg-gray-50">
      <Sidebar items={sidebarItems} title={sidebarTitle} />
      <Header userName={userName} userRole={userRole} notifications={notifications} />
      <main className="ml-64 pt-16">
        <div className="p-8">
          {children}
        </div>
      </main>
    </div>
  );
}
