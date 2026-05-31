import { describe, it, expect, beforeEach } from 'vitest';
import { render, screen } from '@testing-library/react';
import { DashboardPage } from './dashboard-page';

describe('DashboardPage', () => {
  beforeEach(() => {
    localStorage.clear();
  });

  it('renders transactions table', () => {
    render(<DashboardPage />);
    expect(screen.getByText('المعاملات المالية')).toBeInTheDocument();
    expect(screen.getByText('المعرف')).toBeInTheDocument();
    expect(screen.getByText('النوع')).toBeInTheDocument();
    expect(screen.getByText('المبلغ')).toBeInTheDocument();
    expect(screen.getByText('الحالة')).toBeInTheDocument();
    expect(screen.getByText('التاريخ')).toBeInTheDocument();
  });

  it('shows pagination controls', () => {
    render(<DashboardPage />);
    expect(screen.getByText('السابق')).toBeInTheDocument();
    expect(screen.getByText('التالي')).toBeInTheDocument();
  });

  it('displays transaction rows', () => {
    render(<DashboardPage />);
    expect(screen.getByText('TXN-0001')).toBeInTheDocument();
    expect(screen.getByText('TXN-0010')).toBeInTheDocument();
  });
});
