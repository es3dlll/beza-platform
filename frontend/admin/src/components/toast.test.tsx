import { describe, it, expect, beforeEach } from 'vitest';
import { render, screen, act } from '@testing-library/react';
import { ToastContainer, useToastStore } from './toast';

describe('Toast system', () => {
  beforeEach(() => {
    useToastStore.setState({ toasts: [] });
  });

  it('shows success toast message', () => {
    act(() => {
      useToastStore.getState().addToast('تمت العملية بنجاح', 'success');
    });

    render(<ToastContainer />);
    expect(screen.getByText('تمت العملية بنجاح')).toBeInTheDocument();
  });

  it('shows error toast message', () => {
    act(() => {
      useToastStore.getState().addToast('فشلت العملية', 'error');
    });

    render(<ToastContainer />);
    expect(screen.getByText('فشلت العملية')).toBeInTheDocument();
  });

  it('shows warning toast message', () => {
    act(() => {
      useToastStore.getState().addToast('تحذير: رصيد منخفض', 'warning');
    });

    render(<ToastContainer />);
    expect(screen.getByText('تحذير: رصيد منخفض')).toBeInTheDocument();
  });
});
