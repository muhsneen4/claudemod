import { Router, type Request, type Response } from 'express';
import { analyzePalm, getProvider, validateForAnalysis } from '../analysis/index.js';
import { toPreviewImage } from '../analysis/imaging.js';
import { buildReading, DISCLAIMER } from '../interpretation/engine.js';
import { narrate } from '../narration/index.js';
import { imageStore } from '../storage/ephemeral.js';
import { classifyDevice, metrics } from '../analytics/metrics.js';
import { resolveTier } from '../billing/entitlements.js';
import { assertDecodableImage, uploadPalmImage } from '../middleware/upload.js';
import { analyzeRateLimiter, generalRateLimiter } from '../middleware/rateLimit.js';
import { ApiError } from '../util/errors.js';
import { config, publicConfigSummary } from '../config.js';

export const palmRouter: Router = Router();

const UUID_PATTERN = /^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i;

/** What the app can do right now, so the UI never over-promises. */
palmRouter.get('/capabilities', generalRateLimiter, (_request: Request, response: Response) => {
  const provider = getProvider();
  response.json({
    ...publicConfigSummary(),
    provider: {
      id: provider.id,
      technique: provider.technique,
      simulated: provider.simulated,
    },
    entitlements: resolveTier(),
    disclaimer: DISCLAIMER,
  });
});

/**
 * The one endpoint that does the work:
 * upload -> decode check -> analysis -> image validation -> reading.
 */
palmRouter.post('/analyze', analyzeRateLimiter, uploadPalmImage, async (request: Request, response: Response) => {
  const startedAt = Date.now();
  const device = classifyDevice(request.get('user-agent'));
  const provider = getProvider();

  const file = request.file;
  if (!file) {
    metrics.record({ outcome: 'error', processingTimeMs: 0, device, provider: provider.id, errorCode: 'missing_image' });
    throw new ApiError(400, 'missing_image', 'Please choose or take a palm photo first.');
  }

  try {
    await assertDecodableImage(file.buffer);

    const analysis = await analyzePalm(file.buffer, { mimeType: file.mimetype });
    const validation = validateForAnalysis(analysis);

    if (!validation.usable) {
      metrics.record({
        outcome: 'rejected',
        processingTimeMs: Date.now() - startedAt,
        device,
        provider: provider.id,
      });
      response.json({
        status: 'unusable',
        validation,
        analysis,
        meta: buildMeta(provider, 'template', startedAt),
      });
      return;
    }

    const reading = buildReading(analysis);
    const narrated = await narrate(reading, analysis);

    const preview = await toPreviewImage(file.buffer);
    const stored = imageStore.put(preview.data, preview.contentType);

    metrics.record({
      outcome: 'success',
      processingTimeMs: Date.now() - startedAt,
      device,
      provider: provider.id,
    });

    response.json({
      status: 'ok',
      readingId: reading.signature,
      image: {
        id: stored.id,
        url: `/api/palm/image/${stored.id}`,
        expiresAt: new Date(stored.expiresAt).toISOString(),
        storage: 'server memory only, deleted automatically',
      },
      validation,
      analysis,
      reading: narrated.reading,
      meta: buildMeta(provider, narrated.textProvider, startedAt, narrated.fallbackReason),
    });
  } catch (error) {
    metrics.record({
      outcome: 'error',
      processingTimeMs: Date.now() - startedAt,
      device,
      provider: provider.id,
      errorCode: error instanceof ApiError ? error.code : 'internal_error',
    });
    throw error;
  }
});

/** Serve the temporary preview image back to the browser that uploaded it. */
palmRouter.get('/image/:id', generalRateLimiter, (request: Request, response: Response) => {
  const id = String(request.params.id ?? '');
  if (!UUID_PATTERN.test(id)) {
    throw new ApiError(400, 'invalid_image_id', 'That image reference is not valid.');
  }

  const item = imageStore.get(id);
  if (!item) {
    throw new ApiError(404, 'image_expired', 'This palm image is no longer stored. Readings keep images for a short time only.');
  }

  response.setHeader('content-type', item.contentType);
  response.setHeader('cache-control', 'private, max-age=300');
  response.setHeader('x-content-type-options', 'nosniff');
  response.send(item.data);
});

/** "Delete my palm image" - removes it from memory immediately. */
palmRouter.delete('/image/:id', generalRateLimiter, (request: Request, response: Response) => {
  const id = String(request.params.id ?? '');
  if (!UUID_PATTERN.test(id)) {
    throw new ApiError(400, 'invalid_image_id', 'That image reference is not valid.');
  }
  const deleted = imageStore.delete(id);
  response.json({ deleted, message: deleted ? 'Your palm image has been deleted.' : 'That image was already gone.' });
});

function buildMeta(
  provider: { id: string; technique: string; simulated: boolean },
  textProvider: string,
  startedAt: number,
  fallbackReason?: string,
) {
  return {
    analysisProvider: provider.id,
    analysisTechnique: provider.technique,
    analysisIsSimulated: provider.simulated,
    textProvider,
    textFallbackReason: fallbackReason,
    totalTimeMs: Date.now() - startedAt,
    imageRetentionMs: config.storage.imageRetentionMs,
    disclaimer: DISCLAIMER,
  };
}
