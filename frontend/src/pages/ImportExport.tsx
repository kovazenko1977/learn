import React from 'react';

const ImportExport: React.FC = () => {
  const handleImport = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (file) {
      // Logic for CSV/Excel parsing would go here
      alert(`Файл ${file.name} выбран для импорта`);
    }
  };

  return (
    <div className="p-6 bg-white rounded-lg shadow mt-6">
      <h2 className="text-xl font-bold mb-4">Импорт / Экспорт данных</h2>
      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div className="border-2 border-dashed border-gray-300 rounded-lg p-8 flex flex-col items-center">
          <p className="text-gray-600 mb-4 text-center">Выберите Excel или CSV файл для массового импорта товаров</p>
          <input
            type="file"
            accept=".csv, .xlsx, .xls"
            onChange={handleImport}
            className="hidden"
            id="file-import"
          />
          <label
            htmlFor="file-import"
            className="bg-green-600 text-white px-6 py-2 rounded cursor-pointer hover:bg-green-700 transition"
          >
            Выбрать файл
          </label>
        </div>
        <div className="bg-gray-50 rounded-lg p-8 flex flex-col items-center justify-center">
          <p className="text-gray-600 mb-4 text-center">Экспортировать текущий каталог в Excel</p>
          <button className="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 transition">
            Скачать Excel
          </button>
        </div>
      </div>
    </div>
  );
};

export default ImportExport;
