export interface PerformanceReport {
  url: string;
  loadTimeMs: number;
  jsErrors: number;
  memoryUsageMb: number;
  timestamp: string;
}

export class PerformanceTracker {
  private static instance: PerformanceTracker;
  private errors: string[] = [];
  private pageLoadTimes: Map<string, number> = new Map();
  private enabled = false;

  static getInstance(): PerformanceTracker {
    if (!PerformanceTracker.instance) {
      PerformanceTracker.instance = new PerformanceTracker();
    }
    return PerformanceTracker.instance;
  }

  enable(): void {
    this.enabled = true;

    if (typeof window !== 'undefined') {
      window.addEventListener('error', (event) => {
        this.errors.push(`${event.message} at ${event.filename}:${event.lineno}`);
        this.sendReport({
          url: window.location.href,
          loadTimeMs: performance.now(),
          jsErrors: this.errors.length,
          memoryUsageMb: (performance as any).memory?.usedJSHeapSize
            ? Math.round((performance as any).memory.usedJSHeapSize / 1048576)
            : 0,
          timestamp: new Date().toISOString(),
        });
      });
    }
  }

  disable(): void {
    this.enabled = false;
  }

  isEnabled(): boolean {
    return this.enabled;
  }

  trackPageLoad(url: string): void {
    if (!this.enabled) return;

    const loadTime = typeof performance !== 'undefined'
      ? performance.now()
      : 0;

    this.pageLoadTimes.set(url, loadTime);

    this.sendReport({
      url,
      loadTimeMs: Math.round(loadTime),
      jsErrors: this.errors.length,
      memoryUsageMb: (performance as any).memory?.usedJSHeapSize
        ? Math.round((performance as any).memory.usedJSHeapSize / 1048576)
        : 0,
      timestamp: new Date().toISOString(),
    });
  }

  getErrorCount(): number {
    return this.errors.length;
  }

  getPageLoadTime(url: string): number | undefined {
    return this.pageLoadTimes.get(url);
  }

  getErrors(): string[] {
    return [...this.errors];
  }

  sendReport(report: PerformanceReport): void {
    if (!this.enabled) return;

    fetch('/api/v1/analytics/snapshot', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(report),
    }).catch(() => {
      // Fail silently — performance monitoring should not break the app
    });
  }
}

export const performanceTracker = PerformanceTracker.getInstance();
