import { describe, it, expect, beforeEach } from 'vitest';
import { useAuthStore } from './auth-store';

describe('AuthStore', () => {
  beforeEach(() => {
    localStorage.clear();
    useAuthStore.setState({ token: null, user: null, isAuthenticated: false });
  });

  it('starts unauthenticated', () => {
    const state = useAuthStore.getState();
    expect(state.token).toBeNull();
    expect(state.isAuthenticated).toBe(false);
  });

  it('login updates state and localStorage', () => {
    const { login } = useAuthStore.getState();
    login('test-token', { id: '1', name: 'أحمد', email: 'ahmed@test.com' });

    const state = useAuthStore.getState();
    expect(state.token).toBe('test-token');
    expect(state.isAuthenticated).toBe(true);
    expect(state.user?.name).toBe('أحمد');
    expect(localStorage.getItem('beza_token')).toBe('test-token');
  });

  it('logout clears state and localStorage', () => {
    const { login, logout } = useAuthStore.getState();
    login('test-token', { id: '1', name: 'أحمد', email: 'ahmed@test.com' });
    logout();

    const state = useAuthStore.getState();
    expect(state.token).toBeNull();
    expect(state.isAuthenticated).toBe(false);
    expect(localStorage.getItem('beza_token')).toBeNull();
  });
});
