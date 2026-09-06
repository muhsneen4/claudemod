/**
 * Anonymous usage counters for the optional admin dashboard.
 *
 * Deliberately minimal: counts, timings, a coarse device class and the last few
 * error codes. No images, no IP addresses, no user identifiers, nothing that
 * could identify a person. Swap `InMemoryMetricsSink` for a database backed sink
 * later without touching the routes.
 */

export type DeviceClass = 'mobile' | 'tablet' | 'desktop' | 'unknown';

export interface ReadingEvent {
  outcome: 'success' | 'rejected' | 'error';
  processingTimeMs: number;
  device: DeviceClass;
  provider: string;
  errorCode?: string;
}

export interface MetricsSnapshot {
  totalReadings: number;
  successfulReadings: number;
  rejectedImages: number;
  failedReadings: number;
  averageProcessingTimeMs: number;
  byDevice: Record<DeviceClass, number>;
  daily: Array<{ date: string; total: number; success: number; rejected: number; failed: number }>;
  recentErrors: Array<{ at: string; code: string }>;
  imagesInMemory: number;
}

export interface MetricsSink {
  record(event: ReadingEvent): void;
  snapshot(imagesInMemory: number): MetricsSnapshot;
}

class InMemoryMetricsSink implements MetricsSink {
  private total = 0;
  private success = 0;
  private rejected = 0;
  private failed = 0;
  private processingTotal = 0;
  private readonly devices: Record<DeviceClass, number> = { mobile: 0, tablet: 0, desktop: 0, unknown: 0 };
  private readonly days = new Map<string, { total: number; success: number; rejected: number; failed: number }>();
  private readonly errors: Array<{ at: string; code: string }> = [];

  record(event: ReadingEvent): void {
    const day = new Date().toISOString().slice(0, 10);
    const bucket = this.days.get(day) ?? { total: 0, success: 0, rejected: 0, failed: 0 };

    this.total += 1;
    bucket.total += 1;
    this.processingTotal += event.processingTimeMs;
    this.devices[event.device] += 1;

    if (event.outcome === 'success') {
      this.success += 1;
      bucket.success += 1;
    } else if (event.outcome === 'rejected') {
      this.rejected += 1;
      bucket.rejected += 1;
    } else {
      this.failed += 1;
      bucket.failed += 1;
      this.errors.unshift({ at: new Date().toISOString(), code: event.errorCode ?? 'unknown' });
      this.errors.splice(50);
    }

    this.days.set(day, bucket);
    if (this.days.size > 60) {
      const oldest = [...this.days.keys()].sort()[0];
      if (oldest) this.days.delete(oldest);
    }
  }

  snapshot(imagesInMemory: number): MetricsSnapshot {
    return {
      totalReadings: this.total,
      successfulReadings: this.success,
      rejectedImages: this.rejected,
      failedReadings: this.failed,
      averageProcessingTimeMs: this.total === 0 ? 0 : Math.round(this.processingTotal / this.total),
      byDevice: { ...this.devices },
      daily: [...this.days.entries()]
        .sort(([a], [b]) => (a < b ? 1 : -1))
        .slice(0, 30)
        .map(([date, value]) => ({ date, ...value })),
      recentErrors: [...this.errors],
      imagesInMemory,
    };
  }
}

export const metrics: MetricsSink = new InMemoryMetricsSink();

/** Very coarse device class from the user agent. Nothing is stored raw. */
export function classifyDevice(userAgent: string | undefined): DeviceClass {
  if (!userAgent) return 'unknown';
  const value = userAgent.toLowerCase();
  if (/ipad|tablet/.test(value)) return 'tablet';
  if (/mobi|android|iphone/.test(value)) return 'mobile';
  if (/mozilla|chrome|safari|firefox|edge/.test(value)) return 'desktop';
  return 'unknown';
}
