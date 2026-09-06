import path from 'node:path';
import { fileURLToPath } from 'node:url';
import express, { type Express, type Request, type Response } from 'express';
import cors from 'cors';
import helmet from 'helmet';
import { config } from './config.js';
import { palmRouter } from './routes/palm.js';
import { adminRouter } from './routes/admin.js';
import { errorHandler, notFound } from './middleware/errors.js';

const here = path.dirname(fileURLToPath(import.meta.url));
/** In production the built frontend is copied next to the server bundle. */
const webRoot = path.resolve(here, '../../web/dist');

export function createApp(): Express {
  const app = express();

  app.disable('x-powered-by');
  app.set('trust proxy', 1);

  app.use(
    helmet({
      contentSecurityPolicy: {
        directives: {
          defaultSrc: ["'self'"],
          scriptSrc: ["'self'"],
          styleSrc: ["'self'", "'unsafe-inline'"],
          imgSrc: ["'self'", 'data:', 'blob:'],
          connectSrc: ["'self'"],
          fontSrc: ["'self'", 'data:'],
          objectSrc: ["'none'"],
          frameAncestors: ["'none'"],
          baseUri: ["'self'"],
        },
      },
      crossOriginResourcePolicy: { policy: 'same-site' },
      referrerPolicy: { policy: 'no-referrer' },
    }),
  );

  app.use(
    cors({
      origin: (origin, callback) => {
        if (!origin) return callback(null, true);
        if (config.corsOrigins.length === 0) return callback(null, !config.isProduction);
        return callback(null, config.corsOrigins.includes(origin));
      },
      methods: ['GET', 'POST', 'DELETE'],
      maxAge: 600,
    }),
  );

  app.use(express.json({ limit: '32kb' }));

  app.get('/api/health', (_request: Request, response: Response) => {
    response.json({ status: 'ok', uptimeSeconds: Math.round(process.uptime()) });
  });

  app.use('/api/palm', palmRouter);
  app.use('/api/admin', adminRouter);

  // Serve the built single page app when it exists (production one-service deploy).
  app.use(express.static(webRoot, { index: false, maxAge: '1h', fallthrough: true }));
  app.get(/^\/(?!api\/).*/, (_request: Request, response: Response, next) => {
    response.sendFile(path.join(webRoot, 'index.html'), (error) => {
      if (error) next();
    });
  });

  app.use(notFound);
  app.use(errorHandler);

  return app;
}
