import { useState } from 'react';

interface Transaction {
  id: string;
  type: string;
  amount: string;
  status: string;
  date: string;
}

const MOCK_TRANSACTIONS: Transaction[] = Array.from({ length: 25 }, (_, i) => ({
  id: `TXN-${String(i + 1).padStart(4, '0')}`,
  type: i % 2 === 0 ? 'تحويل' : 'إيداع',
  amount: `${(i + 1) * 1000} ل.س`,
  status: i % 3 === 0 ? 'مكتمل' : i % 3 === 1 ? 'معلق' : 'فاشل',
  date: new Date(2026, 4, 31 - i).toLocaleDateString('ar-SA'),
}));

const PAGE_SIZE = 10;

const statusColors: Record<string, string> = {
  مكتمل: '#1a6b4e',
  معلق: '#e6a817',
  فاشل: '#c0392b',
};

export function DashboardPage() {
  const [page, setPage] = useState(1);
  const [sortDir, setSortDir] = useState<'asc' | 'desc'>('desc');
  const [statusFilter, setStatusFilter] = useState<string>('');

  const filtered = statusFilter
    ? MOCK_TRANSACTIONS.filter((t) => t.status === statusFilter)
    : [...MOCK_TRANSACTIONS];

  const sorted = [...filtered].sort((a, b) => {
    const dateA = new Date(a.date).getTime();
    const dateB = new Date(b.date).getTime();
    return sortDir === 'desc' ? dateB - dateA : dateA - dateB;
  });

  const totalPages = Math.ceil(sorted.length / PAGE_SIZE);
  const paginated = sorted.slice((page - 1) * PAGE_SIZE, page * PAGE_SIZE);

  return (
    <div dir="rtl" style={{ padding: 24 }}>
      <h2>المعاملات المالية</h2>

      <div style={{ marginBottom: 16, display: 'flex', gap: 8 }}>
        <select
          value={statusFilter}
          onChange={(e) => { setStatusFilter(e.target.value); setPage(1); }}
          style={{ padding: 8, borderRadius: 4, border: '1px solid #ccc' }}
        >
          <option value="">جميع الحالات</option>
          <option value="مكتمل">مكتمل</option>
          <option value="معلق">معلق</option>
          <option value="فاشل">فاشل</option>
        </select>

        <button
          onClick={() => setSortDir(sortDir === 'desc' ? 'asc' : 'desc')}
          style={{ padding: '8px 16px', borderRadius: 4, border: '1px solid #ccc', cursor: 'pointer' }}
        >
          {sortDir === 'desc' ? '↑ الأحدث' : '↓ الأقدم'}
        </button>
      </div>

      <table style={{ width: '100%', borderCollapse: 'collapse' }}>
        <thead>
          <tr style={{ background: '#f5f5f5' }}>
            <th style={thStyle}>المعرف</th>
            <th style={thStyle}>النوع</th>
            <th style={thStyle}>المبلغ</th>
            <th style={thStyle}>الحالة</th>
            <th style={thStyle}>التاريخ</th>
          </tr>
        </thead>
        <tbody>
          {paginated.map((tx) => (
            <tr key={tx.id} style={{ borderBottom: '1px solid #ddd' }}>
              <td style={tdStyle}>{tx.id}</td>
              <td style={tdStyle}>{tx.type}</td>
              <td style={tdStyle}>{tx.amount}</td>
              <td style={tdStyle}>
                <span
                  style={{
                    padding: '4px 8px',
                    borderRadius: 4,
                    color: 'white',
                    background: statusColors[tx.status] || '#999',
                    fontSize: 13,
                  }}
                >
                  {tx.status}
                </span>
              </td>
              <td style={tdStyle}>{tx.date}</td>
            </tr>
          ))}
        </tbody>
      </table>

      <div style={{ marginTop: 16, display: 'flex', justifyContent: 'center', gap: 8 }}>
        <button
          disabled={page <= 1}
          onClick={() => setPage(page - 1)}
          style={{ padding: '8px 16px', borderRadius: 4, border: '1px solid #ccc', cursor: 'pointer' }}
        >
          السابق
        </button>
        <span style={{ padding: '8px 16px' }}>
          {page} / {totalPages}
        </span>
        <button
          disabled={page >= totalPages}
          onClick={() => setPage(page + 1)}
          style={{ padding: '8px 16px', borderRadius: 4, border: '1px solid #ccc', cursor: 'pointer' }}
        >
          التالي
        </button>
      </div>
    </div>
  );
}

const thStyle: React.CSSProperties = { padding: 12, textAlign: 'right', borderBottom: '2px solid #ddd' };
const tdStyle: React.CSSProperties = { padding: 12, textAlign: 'right' };
