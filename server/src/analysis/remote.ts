import { config } from '../config.js';
import { ApiError } from '../util/errors.js';
import type { AnalyzeOptions, PalmAnalysis, PalmAnalysisProvider } from './types.js';

/**
 * Adapter for a future model. Point REMOTE_ANALYSIS_URL at a service that
 * accepts `multipart/form-data` with an `image` field and answers with the same
 * `PalmAnalysis` JSON, and the rest of the app keeps working unchanged.
 *
 * The API key lives in the server environment only - the browser never sees it.
 */
export const remoteProvider: PalmAnalysisProvider = {
  id: 'remote',
  technique: 'External palm analysis model',
  simulated: false,
  analyzePalm: async (image: Buffer, options: AnalyzeOptions = {}): Promise<PalmAnalysis> => {
    if (!config.analysis.remoteUrl) {
      throw new ApiError(500, 'analysis_provider_misconfigured', 'The remote analysis provider has no URL configured.');
    }

    const startedAt = Date.now();
    const controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), config.analysis.remoteTimeoutMs);
    options.signal?.addEventListener('abort', () => controller.abort(), { once: true });

    try {
      const form = new FormData();
      form.append('image', new Blob([new Uint8Array(image)], { type: options.mimeType ?? 'image/jpeg' }), 'palm.jpg');

      const response = await fetch(config.analysis.remoteUrl, {
        method: 'POST',
        body: form,
        signal: controller.signal,
        headers: config.analysis.remoteApiKey
          ? { authorization: `Bearer ${config.analysis.remoteApiKey}` }
          : undefined,
      });

      if (!response.ok) {
        throw new ApiError(502, 'analysis_upstream_failed', 'The palm analysis service could not process this image.');
      }

      const payload = (await response.json()) as PalmAnalysis;
      return {
        ...payload,
        provider: payload.provider ?? remoteProvider.id,
        technique: payload.technique ?? remoteProvider.technique,
        simulated: payload.simulated === true,
        processingTimeMs: payload.processingTimeMs ?? Date.now() - startedAt,
      };
    } catch (error) {
      if (error instanceof ApiError) throw error;
      throw new ApiError(502, 'analysis_upstream_failed', 'The palm analysis service is not reachable right now.');
    } finally {
      clearTimeout(timeout);
    }
  },
};
