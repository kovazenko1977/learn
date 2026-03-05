'use client';
import { useAuth } from '@/context/auth-context';
import { useRouter } from 'next/navigation';
import { useEffect } from 'react';

export default function LKPage() {
  const { user, logout, loading } = useAuth();
  const router = useRouter();

  useEffect(() => {
    if (!loading && !user) {
      router.push('/login');
    }
  }, [user, loading, router]);

  if (loading || !user) return <p>Loading...</p>;

  return (
    <div className="space-y-8">
      <div className="flex justify-between items-center">
        <h1 className="text-3xl font-bold text-gray-900">Personal Account</h1>
        <button
          onClick={logout}
          className="bg-red-50 text-red-600 px-4 py-2 rounded-md hover:bg-red-100 transition-colors text-sm font-medium border border-red-200"
        >
          Sign Out
        </button>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
        <div className="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
          <h2 className="text-xl font-semibold mb-4 border-b pb-2">Profile Information</h2>
          <div className="space-y-3">
            <p><span className="font-medium text-gray-500">Username:</span> {user.username}</p>
            <p><span className="font-medium text-gray-500">Role:</span> {user.role}</p>
            <p><span className="font-medium text-gray-500">User ID:</span> {user.id}</p>
          </div>
        </div>

        <div className="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
          <h2 className="text-xl font-semibold mb-4 border-b pb-2">Account Actions</h2>
          <div className="grid grid-cols-1 gap-3">
            <button className="text-left px-4 py-2 rounded border hover:bg-gray-50 transition-colors">Order History</button>
            <button className="text-left px-4 py-2 rounded border hover:bg-gray-50 transition-colors">Download Certificates</button>
            <button className="text-left px-4 py-2 rounded border hover:bg-gray-50 transition-colors">Payment Methods</button>
          </div>
        </div>
      </div>
    </div>
  );
}
