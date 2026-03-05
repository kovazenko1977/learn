export default function Dashboard() {
  return (
    <div className="space-y-6">
      <h1 className="text-3xl font-bold">ERP Dashboard</h1>
      <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div className="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
          <h3 className="text-lg font-semibold text-gray-700">Products</h3>
          <p className="text-3xl font-bold text-blue-600 mt-2">1,248</p>
          <p className="text-sm text-gray-500 mt-1">Active items in catalog</p>
        </div>
        <div className="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
          <h3 className="text-lg font-semibold text-gray-700">Orders</h3>
          <p className="text-3xl font-bold text-green-600 mt-2">42</p>
          <p className="text-sm text-gray-500 mt-1">Pending shipments</p>
        </div>
        <div className="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
          <h3 className="text-lg font-semibold text-gray-700">Clients</h3>
          <p className="text-3xl font-bold text-purple-600 mt-2">854</p>
          <p className="text-sm text-gray-500 mt-1">Partner network size</p>
        </div>
      </div>
    </div>
  );
}
