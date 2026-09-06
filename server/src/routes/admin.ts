import { timingSafeEqual } from 'node:crypto';
import { Router, type NextFunction, type Request, type Response } from 'express';
import { config } from '../config.js';
import { metrics } from '../analytics/metrics.js';
import { imageStore } from '../storage/ephemeral.js';
import { generalRateLimiter } from '../middleware/rateLimit.js';
import { ApiError } from '../util/errors.js';

export const adminRouter: Router = Router();

function requireAdmin(request: Request, _response: Response, next: NextFunction): void {
  if (!config.admin.enabled) {
    throw new ApiError(404, 'admin_disabled', 'The admin dashboard is not enabled on this server.');
  }

  const header = request.get('authorization') ?? '';
  const token = header.startsWith('Bearer ') ? header.slice(7) : '';
  const expected = Buffer.from(config.admin.token);
  const received = Buffer.from(token);

  if (received.length !== expected.length || !timingSafeEqual(received, expected)) {
    throw new ApiError(401, 'unauthorized', 'A valid admin token is required.');
  }

  next();
}

/** Anonymous counters only - see analytics/metrics.ts for what is collected. */
adminRouter.get('/metrics', generalRateLimiter, requireAdmin, (_request: Request, response: Response) => {
  response.json(metrics.snapshot(imageStore.size()));
});
