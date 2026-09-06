import { createHash } from 'node:crypto';
import type {
  AnalyzeOptions,
  Curvature,
  Direction,
  LineFeature,
  MountFeature,
  PalmAnalysis,
  PalmAnalysisProvider,
  Visibility,
} from './types.js';

/**
 * Demo provider. It measures NOTHING.
 *
 * Every value below is generated from a hash of the file so the same photo gives
 * the same answer, which makes the UI pleasant to develop against. The result is
 * flagged `simulated: true`, every feature is marked `detectionMethod:
 * "simulated"`, and the API refuses to hide that flag from the client, so the
 * app can always say plainly that this is demo data.
 */
export const mockProvider: PalmAnalysisProvider = {
  id: 'mock',
  technique: 'Simulated demo data - no image analysis is performed',
  simulated: true,
  analyzePalm: async (image: Buffer, _options: AnalyzeOptions = {}): Promise<PalmAnalysis> => {
    const startedAt = Date.now();
    const digest = createHash('sha256').update(image).digest();
    let cursor = 0;
    const next = (): number => {
      const byte = digest[cursor % digest.length] ?? 0;
      cursor += 1;
      return byte / 255;
    };
    const pick = <T>(options: readonly T[]): T => options[Math.floor(next() * options.length) % options.length] as T;

    const line = (): LineFeature => ({
      visibility: pick<Visibility>(['high', 'medium', 'low']),
      confidence: 0,
      length: pick(['short', 'medium', 'long'] as const),
      curvature: pick<Curvature>(['straight', 'gentle', 'moderate', 'pronounced']),
      direction: pick<Direction>(['upward', 'level', 'slightly_downward']),
      detectionMethod: 'simulated',
      note: 'Demo mode: this value was generated, not measured.',
    });

    const mount = (): MountFeature => ({
      prominence: pick(['low', 'medium', 'high'] as const),
      confidence: 0,
      detectionMethod: 'simulated',
    });

    return {
      provider: mockProvider.id,
      technique: mockProvider.technique,
      simulated: true,
      palmDetected: true,
      confidence: 0,
      handSide: pick(['left', 'right'] as const),
      handSideConfidence: 0,
      palmShape: pick(['square', 'rectangular', 'oval', 'broad'] as const),
      palmShapeConfidence: 0,
      fingerProportions: pick(['short', 'balanced', 'long'] as const),
      lifeLine: line(),
      headLine: line(),
      heartLine: line(),
      fateLine: line(),
      sunLine: line(),
      mounts: {
        venus: mount(),
        jupiter: mount(),
        saturn: mount(),
        apollo: mount(),
        mercury: mount(),
        luna: mount(),
      },
      imageQuality: {
        brightness: 0.5,
        handBrightness: 0.5,
        sharpness: 0.5,
        handCoverage: 0.4,
        handRegions: 1,
        borderContact: 0.1,
        fingersFound: 5,
        palmSideLikely: true,
        palmSideConfidence: 0,
        width: 0,
        height: 0,
      },
      overlay: {
        available: false,
        reason: 'Demo mode never draws lines on your photo, because nothing was really detected.',
        segments: [],
      },
      limitations: [
        'Demo mode is active. No image analysis ran and none of these features were detected in your photo.',
      ],
      processingTimeMs: Date.now() - startedAt,
    };
  },
};
