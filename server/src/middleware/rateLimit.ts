import rateLimit from 'express-rate-limit';
import { config } from '../config.js';

/** Protects the expensive image pipeline from being hammered. */
export const analyzeRateLimiter = rateLimit({
  windowMs: config.rateLimit.windowMs,
  limit: config.rateLimit.max,
  standardHeaders: 'draft-7',
  legacyHeaders: false,
  message: {
    error: {
      code: 'rate_limited',
      message: 'You have sent a lot of readings in a short time. Please wait a minute and try again.',
    },
  },
});

/** A looser limit for cheap read-only endpoints. */
export const generalRateLimiter = rateLimit({
  windowMs: config.rateLimit.windowMs,
  limit: Math.max(60, config.rateLimit.max * 10),
  standardHeaders: 'draft-7',
  legacyHeaders: false,
});
