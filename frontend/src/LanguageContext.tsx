import React, { createContext, useContext, useState, useEffect } from 'react';

type Language = 'RU' | 'EN';

const translations = {
  RU: {
    appName: 'CRM Заявки',
    dashboard: 'Дашборд',
    tickets: 'Заявки',
    formBuilder: 'Конструктор форм',
    settings: 'Настройки',
    profile: 'Профиль',
    logout: 'Выйти',
    loginTitle: 'Вход в CRM',
    username: 'Имя пользователя',
    password: 'Пароль',
    loginBtn: 'Войти',
    recoverPassword: 'Восстановить пароль',
    search: 'Поиск...',
    status: 'Статус',
    priority: 'Приоритет',
    category: 'Категория',
    assignee: 'Исполнитель',
    creator: 'Создатель',
    deadline: 'Срок выполнения',
    actions: 'Действия',
    newTicket: 'Создать заявку',
    title: 'Название',
    description: 'Описание',
    create: 'Создать',
    save: 'Сохранить',
    cancel: 'Отмена',
    edit: 'Редактировать',
    delete: 'Удалить',
    comments: 'Комментарии',
    writeComment: 'Написать комментарий...',
    addComment: 'Отправить',
    theme: 'Тема',
    lang: 'Язык',
    all: 'Все',
    averageResolutionTime: 'Ср. время выполнения',
    activeTickets: 'Активные заявки',
    overdueSLA: 'Просрочено SLA',
    onTimeSLA: 'В рамках SLA',
    employeeWorkload: 'Нагрузка на сотрудников',
    hours: 'ч',
    exportExcel: 'Экспорт в Excel',
    storageMode: 'Режим хранения',
    mysqlConfig: 'Конфигурация MySQL',
    usersManagement: 'Управление пользователями',
    addNewUser: 'Добавить пользователя',
    fullName: 'ФИО',
    email: 'Email',
    role: 'Роль',
    admin: 'Администратор',
    responsible: 'Ответственный сотрудник',
    head: 'Начальник отдела',
    executor: 'Исполнитель',
    bulkAssign: 'Массовое назначение',
    dragDropAssign: 'Перетащите заявку для назначения',
    recoveryTitle: 'Восстановление пароля',
    recoverySuccess: 'Инструкции по восстановлению отправлены.',
    recoveryError: 'Пользователь не найден.',
    new: 'Новая',
    assigned: 'Назначена',
    work: 'В работе',
    completed: 'Выполнено',
    rejected: 'Отклонено',
    low: 'Низкий',
    medium: 'Средний',
    high: 'Высокий',
    critical: 'Критический',
    customFields: 'Дополнительные поля',
    addCustomField: 'Добавить кастомное поле',
    fieldName: 'Имя поля (ID)',
    fieldLabel: 'Метка поля',
    fieldType: 'Тип поля',
    required: 'Обязательное',
    optionsComma: 'Опции (через запятую)',
    sortOrder: 'Порядок сортировки',
    uploadFile: 'Прикрепить файл/фото',
    ticketDetails: 'Детали заявки',
    noTickets: 'Заявок не найдено',
    assignedTasks: 'Назначенные задачи',
    unassigned: 'Не назначен',
    slaSettings: 'Настройки SLA (в часах)'
  },
  EN: {
    appName: 'CRM Tickets',
    dashboard: 'Dashboard',
    tickets: 'Tickets',
    formBuilder: 'Form Builder',
    settings: 'Settings',
    profile: 'Profile',
    logout: 'Logout',
    loginTitle: 'CRM Login',
    username: 'Username',
    password: 'Password',
    loginBtn: 'Login',
    recoverPassword: 'Forgot Password',
    search: 'Search...',
    status: 'Status',
    priority: 'Priority',
    category: 'Category',
    assignee: 'Assignee',
    creator: 'Creator',
    deadline: 'Deadline',
    actions: 'Actions',
    newTicket: 'Create Ticket',
    title: 'Title',
    description: 'Description',
    create: 'Create',
    save: 'Save',
    cancel: 'Cancel',
    edit: 'Edit',
    delete: 'Delete',
    comments: 'Comments',
    writeComment: 'Write a comment...',
    addComment: 'Send',
    theme: 'Theme',
    lang: 'Language',
    all: 'All',
    averageResolutionTime: 'Avg Resolution Time',
    activeTickets: 'Active Tickets',
    overdueSLA: 'SLA Overdue',
    onTimeSLA: 'SLA On Time',
    employeeWorkload: 'Employee Workload',
    hours: 'h',
    exportExcel: 'Export to Excel',
    storageMode: 'Storage Mode',
    mysqlConfig: 'MySQL Configuration',
    usersManagement: 'User Management',
    addNewUser: 'Add User',
    fullName: 'Full Name',
    email: 'Email',
    role: 'Role',
    admin: 'Administrator',
    responsible: 'Responsible Employee',
    head: 'Department Head',
    executor: 'Executor',
    bulkAssign: 'Bulk Assignment',
    dragDropAssign: 'Drag ticket to assign',
    recoveryTitle: 'Password Recovery',
    recoverySuccess: 'Recovery instructions sent.',
    recoveryError: 'User not found.',
    new: 'New',
    assigned: 'Assigned',
    work: 'In Progress',
    completed: 'Completed',
    rejected: 'Rejected',
    low: 'Low',
    medium: 'Medium',
    high: 'High',
    critical: 'Critical',
    customFields: 'Custom Fields',
    addCustomField: 'Add Custom Field',
    fieldName: 'Field Name (ID)',
    fieldLabel: 'Field Label',
    fieldType: 'Field Type',
    required: 'Required',
    optionsComma: 'Options (comma-separated)',
    sortOrder: 'Sort Order',
    uploadFile: 'Attach file/photo',
    ticketDetails: 'Ticket Details',
    noTickets: 'No tickets found',
    assignedTasks: 'Assigned Tasks',
    unassigned: 'Unassigned',
    slaSettings: 'SLA Settings (in hours)'
  }
};

interface LanguageContextType {
  language: Language;
  setLanguage: (lang: Language) => void;
  t: (key: keyof typeof translations['RU']) => string;
}

const LanguageContext = createContext<LanguageContextType | undefined>(undefined);

export const LanguageProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const [language, setLanguageState] = useState<Language>(() => {
    return (localStorage.getItem('crm_lang') as Language) || 'RU';
  });

  const setLanguage = (lang: Language) => {
    setLanguageState(lang);
    localStorage.setItem('crm_lang', lang);
  };

  const t = (key: keyof typeof translations['RU']) => {
    return translations[language][key] || translations['RU'][key] || String(key);
  };

  return (
    <LanguageContext.Provider value={{ language, setLanguage, t }}>
      {children}
    </LanguageContext.Provider>
  );
};

export const useLanguage = () => {
  const context = useContext(LanguageContext);
  if (!context) throw new Error('useLanguage must be used within LanguageProvider');
  return context;
};
