import { create } from 'zustand';

interface User {
  id: string;
  name: string;
  email: string;
}

interface AuthState {
  token: string | null;
  user: User | null;
  isAuthenticated: boolean;
  login: (token: string, user: User) => void;
  logout: () => void;
  setUser: (user: User) => void;
}

export const useAuthStore = create<AuthState>((set) => ({
  token: localStorage.getItem('beza_token'),
  user: (() => {
    try {
      const stored = localStorage.getItem('beza_user');
      return stored ? (JSON.parse(stored) as User) : null;
    } catch {
      return null;
    }
  })(),
  get isAuthenticated(): boolean {
    return this.token !== null;
  },
  login: (token: string, user: User) => {
    localStorage.setItem('beza_token', token);
    localStorage.setItem('beza_user', JSON.stringify(user));
    set({ token, user, isAuthenticated: true });
  },
  logout: () => {
    localStorage.removeItem('beza_token');
    localStorage.removeItem('beza_user');
    set({ token: null, user: null, isAuthenticated: false });
  },
  setUser: (user: User) => {
    localStorage.setItem('beza_user', JSON.stringify(user));
    set({ user });
  },
}));
