export default function Dashboard() {
  return (
    <div className="space-y-6">
      <h1 className="text-3xl font-bold">Панель управления ERP</h1>
      <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div className="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
          <h3 className="text-lg font-semibold text-gray-700">Товары</h3>
          <p className="text-3xl font-bold text-blue-600 mt-2">1,248</p>
          <p className="text-sm text-gray-500 mt-1">Активных позиций в каталоге</p>
        </div>
        <div className="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
          <h3 className="text-lg font-semibold text-gray-700">Заказы</h3>
          <p className="text-3xl font-bold text-green-600 mt-2">42</p>
          <p className="text-sm text-gray-500 mt-1">Ожидают отгрузки</p>
        </div>
        <div className="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
          <h3 className="text-lg font-semibold text-gray-700">Клиенты</h3>
          <p className="text-3xl font-bold text-purple-600 mt-2">854</p>
          <p className="text-sm text-gray-500 mt-1">Партнерская сеть</p>
        </div>
      </div>
    </div>
  );
}
