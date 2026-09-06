import { createApp } from './app.js';
import { config, publicConfigSummary } from './config.js';
import { imageStore } from './storage/ephemeral.js';

const app = createApp();
const server = app.listen(config.port, () => {
  console.log(`[destiny-palm] API listening on http://localhost:${config.port}`);
  console.log('[destiny-palm] configuration', publicConfigSummary());
  if (config.analysis.provider === 'mock') {
    console.warn('[destiny-palm] DEMO MODE: no real image analysis will run.');
  }
});

function shutdown(signal: string): void {
  console.log(`[destiny-palm] ${signal} received, shutting down.`);
  imageStore.stop();
  server.close(() => process.exit(0));
  setTimeout(() => process.exit(0), 5000).unref();
}

process.on('SIGINT', () => shutdown('SIGINT'));
process.on('SIGTERM', () => shutdown('SIGTERM'));
