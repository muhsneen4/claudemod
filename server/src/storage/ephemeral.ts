import { randomUUID } from 'node:crypto';
import { config } from '../config.js';

interface StoredImage {
  data: Buffer;
  contentType: string;
  createdAt: number;
  expiresAt: number;
}

/**
 * In-memory, time limited image store.
 *
 * Uploaded palm photos never touch the disk and never reach a database. They sit
 * in this map only so the results screen can show the picture back to you, and
 * they are dropped automatically once IMAGE_RETENTION_MS has passed, when the
 * user presses "Delete my palm image", or when the process restarts.
 *
 * To move to another backend (Redis, S3 with lifecycle rules), implement the
 * same four methods and swap the export.
 */
class EphemeralImageStore {
  private readonly items = new Map<string, StoredImage>();
  private readonly maxItems = 500;
  private timer: NodeJS.Timeout | undefined;

  put(data: Buffer, contentType: string): { id: string; expiresAt: number } {
    this.startSweeper();
    if (this.items.size >= this.maxItems) {
      const oldest = this.items.keys().next();
      if (!oldest.done) this.items.delete(oldest.value);
    }

    const id = randomUUID();
    const now = Date.now();
    const expiresAt = now + config.storage.imageRetentionMs;
    this.items.set(id, { data, contentType, createdAt: now, expiresAt });
    return { id, expiresAt };
  }

  get(id: string): StoredImage | undefined {
    const item = this.items.get(id);
    if (!item) return undefined;
    if (item.expiresAt <= Date.now()) {
      this.items.delete(id);
      return undefined;
    }
    return item;
  }

  delete(id: string): boolean {
    return this.items.delete(id);
  }

  size(): number {
    return this.items.size;
  }

  /** Stop the background timer - used by tests and graceful shutdown. */
  stop(): void {
    if (this.timer) clearInterval(this.timer);
    this.timer = undefined;
  }

  private startSweeper(): void {
    if (this.timer) return;
    this.timer = setInterval(() => {
      const now = Date.now();
      for (const [id, item] of this.items) {
        if (item.expiresAt <= now) this.items.delete(id);
      }
    }, 60_000);
    this.timer.unref?.();
  }
}

export const imageStore = new EphemeralImageStore();
