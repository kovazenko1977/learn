import { LabelElement } from '../types';

export const useVariables = () => {
  const variables: Record<string, string> = {
    '{DATE}': new Date().toLocaleDateString('ru-RU'),
    '{BATCH}': '2024-001',
    '{PRODUCT}': 'ЧИНАЗЕС СО ВКУСОМ ГРЕЙПФРУТА',
    '{BARCODE}': '4811173002809',
    '{ALCOHOL}': '5.0%',
    '{VOLUME}': '0.5 л',
    '{EXPIRATION}': '12 месяцев',
    '{COMPOSITION}': 'Вода, сахар, ароматизатор, лимонная кислота',
    '{GOST}': 'СТБ 1122-2010',
    '{MANUFACTURER}': 'ООО "Напитки Про"',
  };

  const substitute = (content: string, customData?: Record<string, string>) => {
    let result = content;
    const data = { ...variables, ...customData };

    Object.entries(data).forEach(([key, value]) => {
      result = result.replace(new RegExp(key, 'g'), value || '');
    });

    return result;
  };

  return { substitute, variables };
};
