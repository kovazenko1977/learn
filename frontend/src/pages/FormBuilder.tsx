import React, { useState, useEffect } from 'react';
import { useLanguage } from '../LanguageContext';
import { Plus, Trash2, ArrowUp, ArrowDown, Save, ToggleLeft, ToggleRight, SquarePlay } from 'lucide-react';

interface FormField {
  id: string;
  name: string;
  label: string;
  type: 'text' | 'number' | 'select' | 'checkbox';
  category: string;
  options: string[];
  required: boolean;
  sort_order: number;
}

export const FormBuilder: React.FC = () => {
  const { t } = useLanguage();
  const [fields, setFields] = useState<FormField[]>([]);
  const [categories, setCategories] = useState<string[]>(['IT', 'Plumbing', 'Electrical', 'Hardware']);
  const [selectedCategory, setSelectedCategory] = useState('IT');

  // New Field State
  const [name, setName] = useState('');
  const [label, setLabel] = useState('');
  const [type, setType] = useState<'text' | 'number' | 'select' | 'checkbox'>('text');
  const [optionsStr, setOptionsStr] = useState('');
  const [required, setRequired] = useState(false);
  const [sortOrder, setSortOrder] = useState(0);

  useEffect(() => {
    fetchFields();
    fetchCategories();
  }, []);

  const fetchCategories = async () => {
    try {
      const token = localStorage.getItem('crm_token');
      const res = await fetch('/api/admin/settings', {
        headers: { 'Authorization': `Bearer ${token}` }
      });
      if (res.ok) {
        const data = await res.json();
        if (data.categories) {
          setCategories(data.categories);
          setSelectedCategory(data.categories[0] || 'IT');
        }
      }
    } catch (e) {
      console.error(e);
    }
  };

  const fetchFields = async () => {
    try {
      const token = localStorage.getItem('crm_token');
      const res = await fetch('/api/admin/form-fields', {
        headers: {
          'Authorization': `Bearer ${token}`
        }
      });
      if (res.ok) {
        const data = await res.json();
        setFields(data);
      }
    } catch (e) {
      console.error('Failed to load fields:', e);
    }
  };

  const handleAddField = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!name || !label) return;

    const payload = {
      name: name.toLowerCase().replace(/[^a-z0-9]/g, '_'),
      label,
      type,
      category: selectedCategory,
      options: type === 'select' ? optionsStr.split(',').map(s => s.trim()) : [],
      required,
      sort_order: sortOrder
    };

    try {
      const token = localStorage.getItem('crm_token');
      const res = await fetch('/api/admin/form-fields', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${token}`
        },
        body: JSON.stringify(payload)
      });

      if (res.ok) {
        // Reset inputs
        setName('');
        setLabel('');
        setOptionsStr('');
        setRequired(false);
        setSortOrder(0);
        fetchFields();
      }
    } catch (error) {
      console.error('Failed to save field:', error);
    }
  };

  const handleDeleteField = async (id: string) => {
    try {
      const token = localStorage.getItem('crm_token');
      const res = await fetch(`/api/admin/form-fields/${id}`, {
        method: 'DELETE',
        headers: {
          'Authorization': `Bearer ${token}`
        }
      });

      if (res.ok) {
        fetchFields();
      }
    } catch (error) {
      console.error('Failed to delete field:', error);
    }
  };

  // Reorder sort order
  const handleMove = async (field: FormField, direction: 'up' | 'down') => {
    const categoryFields = fields.filter(f => f.category === selectedCategory);
    const idx = categoryFields.findIndex(f => f.id === field.id);
    if (idx === -1) return;

    let targetIdx = direction === 'up' ? idx - 1 : idx + 1;
    if (targetIdx < 0 || targetIdx >= categoryFields.length) return;

    const targetField = categoryFields[targetIdx];

    // Swap sort orders
    const originalSort = field.sort_order;
    const targetSort = targetField.sort_order;

    try {
      const token = localStorage.getItem('crm_token');
      await fetch('/api/admin/form-fields', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${token}`
        },
        body: JSON.stringify({ ...field, sort_order: targetSort })
      });

      await fetch('/api/admin/form-fields', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${token}`
        },
        body: JSON.stringify({ ...targetField, sort_order: originalSort })
      });

      fetchFields();
    } catch (e) {
      console.error(e);
    }
  };

  const filteredFields = fields.filter(f => f.category === selectedCategory);

  return (
    <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
      {/* 1. Add/Edit Field Form */}
      <div className="bg-white dark:bg-slate-900 rounded-2xl p-6 border border-slate-200/50 dark:border-slate-800/50 shadow-sm space-y-4 h-fit">
        <div className="flex items-center gap-2 pb-3 border-b border-slate-100 dark:border-slate-800">
          <SquarePlay className="w-5 h-5 text-primary-500" />
          <h4 className="font-bold text-slate-900 dark:text-slate-50 text-sm">Добавить новое поле</h4>
        </div>

        <form onSubmit={handleAddField} className="space-y-4 text-xs">
          <div>
            <label className="block text-slate-400 font-bold mb-1.5">Категория заявки</label>
            <select
              value={selectedCategory}
              onChange={(e) => setSelectedCategory(e.target.value)}
              className="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/40 text-slate-900 dark:text-slate-50"
            >
              {categories.map(cat => (
                <option key={cat} value={cat}>{cat}</option>
              ))}
            </select>
          </div>

          <div>
            <label className="block text-slate-400 font-bold mb-1.5">Системное имя (ID)</label>
            <input
              type="text"
              required
              placeholder="e.g. serial_number"
              value={name}
              onChange={(e) => setName(e.target.value)}
              className="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/40 text-slate-900"
            />
          </div>

          <div>
            <label className="block text-slate-400 font-bold mb-1.5">{t('fieldLabel')}</label>
            <input
              type="text"
              required
              placeholder="e.g. Серийный номер"
              value={label}
              onChange={(e) => setLabel(e.target.value)}
              className="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/40 text-slate-900"
            />
          </div>

          <div>
            <label className="block text-slate-400 font-bold mb-1.5">{t('fieldType')}</label>
            <select
              value={type}
              onChange={(e: any) => setType(e.target.value)}
              className="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/40 text-slate-900"
            >
              <option value="text">Текст (text)</option>
              <option value="number">Число (number)</option>
              <option value="select">Список значений (select)</option>
              <option value="checkbox">Галочка (checkbox)</option>
            </select>
          </div>

          {type === 'select' && (
            <div>
              <label className="block text-slate-400 font-bold mb-1.5">{t('optionsComma')}</label>
              <input
                type="text"
                required
                placeholder="Опция 1, Опция 2, Опция 3"
                value={optionsStr}
                onChange={(e) => setOptionsStr(e.target.value)}
                className="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/40 text-slate-900"
              />
            </div>
          )}

          <div className="flex items-center justify-between py-2 border-t border-b border-slate-100 dark:border-slate-800/60">
            <span className="text-slate-600 dark:text-slate-300 font-semibold">{t('required')}</span>
            <button
              type="button"
              onClick={() => setRequired(!required)}
              className="text-slate-500 hover:text-slate-800 transition"
            >
              {required ? (
                <ToggleRight className="w-8 h-8 text-primary-500" />
              ) : (
                <ToggleLeft className="w-8 h-8 text-slate-400" />
              )}
            </button>
          </div>

          <div>
            <label className="block text-slate-400 font-bold mb-1.5">{t('sortOrder')}</label>
            <input
              type="number"
              value={sortOrder}
              onChange={(e) => setSortOrder(Number(e.target.value))}
              className="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/40 text-slate-900"
            />
          </div>

          <button
            type="submit"
            className="w-full py-2.5 bg-primary-600 hover:bg-primary-700 text-white font-bold rounded-xl shadow-md transition"
          >
            Добавить поле
          </button>
        </form>
      </div>

      {/* 2. List & Sorting of Fields */}
      <div className="bg-white dark:bg-slate-900 rounded-2xl p-6 border border-slate-200/50 dark:border-slate-800/50 shadow-sm lg:col-span-2 space-y-4">
        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-3 border-b border-slate-100 dark:border-slate-800">
          <h4 className="font-bold text-slate-900 dark:text-slate-50 text-sm">
            Конструктор полей для категории: <span className="text-primary-600 dark:text-primary-400">{selectedCategory}</span>
          </h4>
          <select
            value={selectedCategory}
            onChange={(e) => setSelectedCategory(e.target.value)}
            className="px-3 py-1.5 text-xs rounded-xl border bg-slate-50 dark:bg-slate-950/40 text-slate-800"
          >
            {categories.map(cat => (
              <option key={cat} value={cat}>{cat}</option>
            ))}
          </select>
        </div>

        <div className="space-y-2.5">
          {filteredFields.map((field, index) => (
            <div
              key={field.id}
              className="p-4 bg-slate-50 dark:bg-slate-950/30 border border-slate-200/50 dark:border-slate-800/30 rounded-xl flex items-center justify-between text-xs"
            >
              <div>
                <div className="flex items-center gap-2">
                  <span className="font-bold text-slate-900 dark:text-slate-100 text-sm">{field.label}</span>
                  <span className="text-[10px] bg-slate-200 dark:bg-slate-800 px-1.5 py-0.5 rounded font-bold uppercase text-slate-600 dark:text-slate-400">
                    {field.type}
                  </span>
                  {field.required && (
                    <span className="text-[9px] bg-red-100 text-red-800 dark:bg-red-950/30 dark:text-red-400 px-1 py-0.5 rounded font-bold uppercase">
                      Required
                    </span>
                  )}
                </div>
                <div className="text-[10px] text-slate-400 mt-1">
                  ID: <code className="bg-slate-100 dark:bg-slate-800 px-1 rounded">{field.name}</code>
                  {field.options.length > 0 && ` | Options: [${field.options.join(', ')}]`}
                </div>
              </div>

              <div className="flex items-center gap-1.5">
                {/* Ordering buttons */}
                <button
                  disabled={index === 0}
                  onClick={() => handleMove(field, 'up')}
                  className="p-1.5 rounded-lg border bg-white dark:bg-slate-800 text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 disabled:opacity-40 transition"
                >
                  <ArrowUp className="w-3.5 h-3.5" />
                </button>
                <button
                  disabled={index === filteredFields.length - 1}
                  onClick={() => handleMove(field, 'down')}
                  className="p-1.5 rounded-lg border bg-white dark:bg-slate-800 text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 disabled:opacity-40 transition"
                >
                  <ArrowDown className="w-3.5 h-3.5" />
                </button>

                {/* Delete button */}
                <button
                  onClick={() => handleDeleteField(field.id)}
                  className="p-1.5 rounded-lg border bg-red-50 text-red-600 hover:bg-red-100 hover:text-red-700 dark:bg-red-950/20 dark:text-red-400 transition"
                >
                  <Trash2 className="w-3.5 h-3.5" />
                </button>
              </div>
            </div>
          ))}

          {filteredFields.length === 0 && (
            <div className="p-8 text-center border border-dashed border-slate-200 dark:border-slate-800 rounded-2xl text-slate-400">
              Пока нет кастомных полей для этой категории.
            </div>
          )}
        </div>
      </div>
    </div>
  );
};
