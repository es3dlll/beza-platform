import { describe, it, expect, beforeEach } from 'vitest';
import { render, screen } from '@testing-library/react';
import { MemoryRouter } from 'react-router-dom';
import { DashboardLayout } from './layout';
import { useAuthStore } from '../store/auth-store';

describe('DashboardLayout', () => {
  beforeEach(() => {
    localStorage.clear();
    useAuthStore.setState({ token: 'valid', user: { id: '1', name: 'أحمد', email: 'ahmed@test.com' }, isAuthenticated: true });
  });

  it('renders user name in header', () => {
    render(
      <MemoryRouter>
        <DashboardLayout />
      </MemoryRouter>,
    );

    expect(screen.getByText('أحمد')).toBeInTheDocument();
  });

  it('renders logout button', () => {
    render(
      <MemoryRouter>
        <DashboardLayout />
      </MemoryRouter>,
    );

    expect(screen.getByText('تسجيل خروج')).toBeInTheDocument();
  });

  it('renders sidebar navigation', () => {
    render(
      <MemoryRouter>
        <DashboardLayout />
      </MemoryRouter>,
    );

    expect(screen.getByText('بيزا')).toBeInTheDocument();
    expect(screen.getByText('لوحة التحكم')).toBeInTheDocument();
    expect(screen.getByText('المعاملات')).toBeInTheDocument();
  });
});
