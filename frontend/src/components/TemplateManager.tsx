import React, { useState, useEffect } from 'react';
import axios from 'axios';

const TemplateManager = () => {
  const [templates, setTemplates] = useState([]);
  const [category, setCategory] = useState('all');

  useEffect(() => {
    fetchTemplates();
  }, [category]);

  const fetchTemplates = async () => {
    const url = category === 'all' ? '/api/templates' : `/api/templates?category=${category}`;
    const response = await axios.get(url);
    setTemplates(response.data);
  };

  return (
    <div className="p-6">
      <div className="mb-6 flex space-x-4">
        {['all', 'alcohol', 'box', 'keg', 'pallet', 'transport'].map(cat => (
          <button
            key={cat}
            onClick={() => setCategory(cat)}
            className={`px-4 py-2 rounded ${category === cat ? 'bg-blue-600 text-white' : 'bg-gray-200'}`}
          >
            {cat.toUpperCase()}
          </button>
        ))}
      </div>
      <div className="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-6">
        {templates.map(tpl => (
          <div key={tpl.id} className="border rounded-lg p-4 bg-white hover:shadow-lg cursor-pointer">
            <h3 className="font-bold">{tpl.name}</h3>
            <p className="text-sm text-gray-500">{tpl.width} x {tpl.height} mm</p>
          </div>
        ))}
      </div>
    </div>
  );
};

export default TemplateManager;
