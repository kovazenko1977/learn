import React, { useState, useEffect } from 'react';
import { useLanguage } from '../LanguageContext';
import {
  FileText,
  Clock,
  AlertCircle,
  CheckCircle2,
  Users,
  TrendingUp,
  AlertTriangle
} from 'lucide-react';

interface DashboardStats {
  statusCounts: {
    new: number;
    assigned: number;
    work: number;
    completed: number;
    rejected: number;
  };
  totalTickets: number;
  averageResolutionTimeHours: number;
  slaStatus: {
    overdue: number;
    onTime: number;
  };
  workload: Array<{
    id: string;
    fullName: string;
    username: string;
    activeCount: number;
    completedCount: number;
    totalAssigned: number;
  }>;
}

export const Dashboard: React.FC = () => {
  const { t } = useLanguage();
  const [stats, setStats] = useState<DashboardStats | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchStats();
  }, []);

  const fetchStats = async () => {
    try {
      const token = localStorage.getItem('crm_token');
      const res = await fetch('/api/analytics/dashboard', {
        headers: {
          'Authorization': `Bearer ${token}`
        }
      });
      if (res.ok) {
        const data = await res.json();
        setStats(data);
      }
    } catch (error) {
      console.error('Failed to fetch dashboard stats:', error);
    } finally {
      setLoading(false);
    }
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center min-h-[300px]">
        <div className="w-8 h-8 border-4 border-primary-500 border-t-transparent rounded-full animate-spin"></div>
      </div>
    );
  }

  if (!stats) return null;

  const activeTicketsCount = (stats.statusCounts.new || 0) +
                             (stats.statusCounts.assigned || 0) +
                             (stats.statusCounts.work || 0);

  const slaPercentage = stats.slaStatus.onTime + stats.slaStatus.overdue > 0
    ? Math.round((stats.slaStatus.onTime / (stats.slaStatus.onTime + stats.slaStatus.overdue)) * 100)
    : 100;

  return (
    <div className="space-y-6">
      {/* 4 KPI Metrics Grid */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        {/* Total & Active Tickets */}
        <div className="bg-white dark:bg-slate-900 rounded-2xl p-5 border border-slate-200/50 dark:border-slate-800/50 shadow-sm flex items-center gap-4">
          <div className="w-12 h-12 rounded-xl bg-primary-100 dark:bg-primary-950/40 text-primary-600 dark:text-primary-400 flex items-center justify-center">
            <FileText className="w-6 h-6" />
          </div>
          <div>
            <p className="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">{t('activeTickets')}</p>
            <h3 className="text-2xl font-bold text-slate-900 dark:text-slate-50 mt-1">
              {activeTicketsCount} <span className="text-sm font-normal text-slate-400 dark:text-slate-500">/ {stats.totalTickets}</span>
            </h3>
          </div>
        </div>

        {/* Avg Resolution Time */}
        <div className="bg-white dark:bg-slate-900 rounded-2xl p-5 border border-slate-200/50 dark:border-slate-800/50 shadow-sm flex items-center gap-4">
          <div className="w-12 h-12 rounded-xl bg-amber-100 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 flex items-center justify-center">
            <Clock className="w-6 h-6" />
          </div>
          <div>
            <p className="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">{t('averageResolutionTime')}</p>
            <h3 className="text-2xl font-bold text-slate-900 dark:text-slate-50 mt-1">
              {stats.averageResolutionTimeHours} <span className="text-sm font-normal text-slate-400 dark:text-slate-500">{t('hours')}</span>
            </h3>
          </div>
        </div>

        {/* Overdue SLA */}
        <div className="bg-white dark:bg-slate-900 rounded-2xl p-5 border border-slate-200/50 dark:border-slate-800/50 shadow-sm flex items-center gap-4">
          <div className="w-12 h-12 rounded-xl bg-red-100 dark:bg-red-950/40 text-red-600 dark:text-red-400 flex items-center justify-center">
            <AlertCircle className="w-6 h-6" />
          </div>
          <div>
            <p className="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">{t('overdueSLA')}</p>
            <h3 className="text-2xl font-bold text-slate-900 dark:text-slate-50 mt-1">
              {stats.slaStatus.overdue}
            </h3>
          </div>
        </div>

        {/* SLA Compliance % */}
        <div className="bg-white dark:bg-slate-900 rounded-2xl p-5 border border-slate-200/50 dark:border-slate-800/50 shadow-sm flex items-center gap-4">
          <div className="w-12 h-12 rounded-xl bg-green-100 dark:bg-green-950/40 text-green-600 dark:text-green-400 flex items-center justify-center">
            <CheckCircle2 className="w-6 h-6" />
          </div>
          <div>
            <p className="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">{t('onTimeSLA')}</p>
            <h3 className="text-2xl font-bold text-slate-900 dark:text-slate-50 mt-1">
              {slaPercentage}%
            </h3>
          </div>
        </div>
      </div>

      {/* Ticket Distribution and SLA Compliance Visual Layout */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Ticket Status Distribution Card */}
        <div className="bg-white dark:bg-slate-900 rounded-2xl p-6 border border-slate-200/50 dark:border-slate-800/50 shadow-sm lg:col-span-2 space-y-6">
          <div className="flex items-center justify-between">
            <h4 className="font-bold text-slate-900 dark:text-slate-50 flex items-center gap-2">
              <TrendingUp className="w-5 h-5 text-primary-500" />
              <span>Распределение заявок по статусам</span>
            </h4>
          </div>

          <div className="space-y-4">
            {[
              { label: t('new'), value: stats.statusCounts.new || 0, color: 'bg-blue-500', hover: 'text-blue-500' },
              { label: t('assigned'), value: stats.statusCounts.assigned || 0, color: 'bg-purple-500', hover: 'text-purple-500' },
              { label: t('work'), value: stats.statusCounts.work || 0, color: 'bg-amber-500', hover: 'text-amber-500' },
              { label: t('completed'), value: stats.statusCounts.completed || 0, color: 'bg-green-500', hover: 'text-green-500' },
              { label: t('rejected'), value: stats.statusCounts.rejected || 0, color: 'bg-red-500', hover: 'text-red-500' }
            ].map(item => {
              const percentage = stats.totalTickets > 0 ? (item.value / stats.totalTickets) * 100 : 0;
              return (
                <div key={item.label} className="space-y-2">
                  <div className="flex justify-between text-sm">
                    <span className="font-medium text-slate-600 dark:text-slate-300">{item.label}</span>
                    <span className="font-bold text-slate-900 dark:text-slate-100">{item.value} ({Math.round(percentage)}%)</span>
                  </div>
                  <div className="w-full bg-slate-100 dark:bg-slate-800 h-2.5 rounded-full overflow-hidden">
                    <div
                      className={`${item.color} h-full rounded-full transition-all duration-500`}
                      style={{ width: `${percentage}%` }}
                    ></div>
                  </div>
                </div>
              );
            })}
          </div>
        </div>

        {/* SLA Health Indicator Widget */}
        <div className="bg-white dark:bg-slate-900 rounded-2xl p-6 border border-slate-200/50 dark:border-slate-800/50 shadow-sm flex flex-col justify-between">
          <div>
            <h4 className="font-bold text-slate-900 dark:text-slate-50 flex items-center gap-2 mb-4">
              <AlertTriangle className="w-5 h-5 text-amber-500" />
              <span>Контроль SLA (Сроки)</span>
            </h4>
            <p className="text-sm text-slate-500 dark:text-slate-400 leading-relaxed">
              Мониторинг соблюдения дедлайнов. Своевременное выполнение заявок является критическим показателем качества обслуживания.
            </p>
          </div>

          <div className="py-6 flex items-center justify-center">
            {/* Minimalist SVG Gauge Chart */}
            <div className="relative w-36 h-36">
              <svg className="w-full h-full transform -rotate-90" viewBox="0 0 100 100">
                {/* Background circle */}
                <circle
                  cx="50" cy="50" r="40"
                  className="stroke-slate-100 dark:stroke-slate-800"
                  strokeWidth="8" fill="transparent"
                />
                {/* Accent circle */}
                <circle
                  cx="50" cy="50" r="40"
                  className="stroke-green-500"
                  strokeWidth="8" fill="transparent"
                  strokeDasharray={`${2 * Math.PI * 40}`}
                  strokeDashoffset={`${2 * Math.PI * 40 * (1 - slaPercentage / 100)}`}
                  strokeLinecap="round"
                />
              </svg>
              <div className="absolute inset-0 flex flex-col items-center justify-center">
                <span className="text-2xl font-bold text-slate-900 dark:text-slate-50">{slaPercentage}%</span>
                <span className="text-[10px] text-slate-400 dark:text-slate-500 uppercase tracking-wider">OK SLA</span>
              </div>
            </div>
          </div>

          <div className="grid grid-cols-2 gap-2 text-center text-xs">
            <div className="p-2 bg-green-50 dark:bg-green-950/20 rounded-xl border border-green-100/50 dark:border-green-900/10">
              <span className="block text-green-600 dark:text-green-400 font-bold text-sm">{stats.slaStatus.onTime}</span>
              <span className="text-slate-500 dark:text-slate-400">В рамках SLA</span>
            </div>
            <div className="p-2 bg-red-50 dark:bg-red-950/20 rounded-xl border border-red-100/50 dark:border-red-900/10">
              <span className="block text-red-600 dark:text-red-400 font-bold text-sm">{stats.slaStatus.overdue}</span>
              <span className="text-slate-500 dark:text-slate-400">Просрочено</span>
            </div>
          </div>
        </div>
      </div>

      {/* Employee Workload Table */}
      <div className="bg-white dark:bg-slate-900 rounded-2xl p-6 border border-slate-200/50 dark:border-slate-800/50 shadow-sm">
        <h4 className="font-bold text-slate-900 dark:text-slate-50 flex items-center gap-2 mb-4">
          <Users className="w-5 h-5 text-primary-500" />
          <span>{t('employeeWorkload')}</span>
        </h4>

        <div className="overflow-x-auto">
          <table className="w-full text-left text-sm">
            <thead>
              <tr className="border-b border-slate-100 dark:border-slate-800 text-slate-400 dark:text-slate-500 font-medium">
                <th className="pb-3">{t('fullName')}</th>
                <th className="pb-3 text-center">{t('activeTickets')}</th>
                <th className="pb-3 text-center">{t('completed')}</th>
                <th className="pb-3 text-center">Всего назначено</th>
                <th className="pb-3 text-right">Эффективность</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100 dark:divide-slate-800/50">
              {stats.workload.map(employee => {
                const efficiency = employee.totalAssigned > 0
                  ? Math.round((employee.completedCount / employee.totalAssigned) * 100)
                  : 100;
                return (
                  <tr key={employee.id} className="text-slate-700 dark:text-slate-300">
                    <td className="py-3 font-semibold text-slate-900 dark:text-slate-50">{employee.fullName}</td>
                    <td className="py-3 text-center">
                      <span className="inline-block px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800 dark:bg-amber-950/40 dark:text-amber-400">
                        {employee.activeCount}
                      </span>
                    </td>
                    <td className="py-3 text-center">
                      <span className="inline-block px-2.5 py-0.5 rounded-full text-xs font-bold bg-green-100 text-green-800 dark:bg-green-950/40 dark:text-green-400">
                        {employee.completedCount}
                      </span>
                    </td>
                    <td className="py-3 text-center font-medium">{employee.totalAssigned}</td>
                    <td className="py-3 text-right">
                      <div className="flex items-center justify-end gap-2">
                        <span className="font-bold text-slate-900 dark:text-slate-50">{efficiency}%</span>
                        <div className="w-16 bg-slate-100 dark:bg-slate-800 h-1.5 rounded-full overflow-hidden">
                          <div
                            className="bg-primary-500 h-full rounded-full"
                            style={{ width: `${efficiency}%` }}
                          ></div>
                        </div>
                      </div>
                    </td>
                  </tr>
                );
              })}
              {stats.workload.length === 0 && (
                <tr>
                  <td colSpan={5} className="py-4 text-center text-slate-400 dark:text-slate-500">
                    Исполнители не зарегистрированы в системе.
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
};
