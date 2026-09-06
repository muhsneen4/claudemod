import {
  decodeImage,
  laplacianVariance,
  clamp01,
  type DecodedImage,
} from './imaging.js';
import { findBlobs, measureHand, segmentSkin, type HandGeometry } from './hand.js';
import { buildCreaseField, regionMean, traceStrongestCrease, type CreaseField, type TracedLine, type Zone } from './creases.js';
import type {
  AnalyzeOptions,
  Curvature,
  Direction,
  LineFeature,
  MountFeature,
  Overlay,
  OverlaySegment,
  PalmAnalysis,
  PalmAnalysisProvider,
  PalmShape,
  Prominence,
  Visibility,
} from './types.js';

/**
 * The default provider: classical computer vision, running on this server.
 *
 * What it genuinely measures from the pixels:
 *   - brightness, sharpness (variance of the Laplacian) and framing
 *   - a skin coloured region, its outline, finger runs and the palm rectangle
 *   - the strongest crease path inside each palmistry zone, traced with
 *     dynamic programming, plus its contrast, continuity, bend and drift
 *
 * What it does NOT do, and never claims to do:
 *   - it is not a trained neural network and has no learned palm model
 *   - zone names ("heart line", "fate line") follow standard palm anatomy;
 *     the pipeline does not verify anatomy, so every feature ships with its own
 *     confidence value and the UI shows it
 */

const ANALYSED_MAX_SIDE = 480;

export const heuristicCvProvider: PalmAnalysisProvider = {
  id: 'heuristic-cv',
  technique: 'On-server classical computer vision (skin segmentation + crease tracing)',
  simulated: false,
  analyzePalm,
};

async function analyzePalm(image: Buffer, _options: AnalyzeOptions = {}): Promise<PalmAnalysis> {
  const startedAt = Date.now();
  const decoded = await decodeImage(image, ANALYSED_MAX_SIDE);
  const limitations: string[] = [];

  const brightness = averageBrightness(decoded);
  const sharpness = clamp01(Math.sqrt(laplacianVariance(decoded.gray, decoded.width, decoded.height)) / 0.055);

  const skin = segmentSkin(decoded);
  if (skin.usedLuminanceFallback) {
    limitations.push('The photo has almost no colour, so the hand was separated by brightness instead of skin tone. That is less reliable.');
  }

  const { labels, blobs } = findBlobs(skin.mask, decoded.width, decoded.height);
  const frameArea = decoded.width * decoded.height;
  const largest = blobs[0];
  const handCoverage = largest ? largest.area / frameArea : 0;
  const handRegions = blobs.filter((blob) => blob.area >= frameArea * 0.03 && largest && blob.area >= largest.area * 0.35).length;

  if (!largest || handCoverage < 0.04) {
    return emptyResult(decoded, {
      brightness,
      sharpness,
      handCoverage,
      handRegions,
      startedAt,
      limitations,
    });
  }

  const handMask = new Uint8Array(skin.mask.length);
  for (let i = 0; i < handMask.length; i += 1) handMask[i] = labels[i] === largest.label ? 1 : 0;

  const geometry = measureHand(labels, largest, decoded.width, decoded.height);
  const handBrightness = maskedMeanLuma(decoded, handMask);
  const field = buildCreaseField(decoded.gray, decoded.width, decoded.height, handMask);

  const thumbSide = geometry.thumbSide;
  if (thumbSide === 'unknown') {
    limitations.push('The thumb was not clearly visible, so the palmistry zones were placed using the default layout.');
  }
  if (geometry.fingerDirection !== 'up') {
    limitations.push('Fingers were not clearly pointing up in the photo, which lowers the accuracy of every zone.');
  }

  const zones = buildZones(geometry, thumbSide === 'right' ? 'right' : 'left');
  const heart = traceStrongestCrease(field, zones.heart);
  const head = traceStrongestCrease(field, zones.head);
  const life = traceStrongestCrease(field, zones.life);
  const fate = traceStrongestCrease(field, zones.fate);
  let sun = traceStrongestCrease(field, zones.sun);

  // Neighbouring zones can both land on the same crease. Reporting it twice
  // would invent a line that is not there, so the weaker claim is dropped.
  if (sharesCrease(fate, sun, 'x', geometry.palmRegion.width * 0.08)) {
    sun = null;
  }

  const qualityFactor = clamp01(0.35 + sharpness * 0.45 + clamp01(handCoverage * 2.4) * 0.2);

  const heartLine = toLineFeature(heart, qualityFactor, 'horizontal');
  const headLine = toLineFeature(head, qualityFactor, 'horizontal');
  const lifeLine = toLineFeature(life, qualityFactor, 'vertical');
  const fateLine = toLineFeature(fate, qualityFactor, 'vertical');
  const sunLine = toLineFeature(sun, qualityFactor, 'vertical');

  const creaseEnergy = (heartLine.confidence > 0 ? heart?.strength ?? 0 : 0) * 0.4 +
    (head?.strength ?? 0) * 0.35 +
    (life?.strength ?? 0) * 0.25;
  const palmSideLikely = creaseEnergy >= 0.16;
  const palmSideConfidence = clamp01(Math.abs(creaseEnergy - 0.16) * 2.2) * 0.5;
  limitations.push('Telling a palm from the back of a hand is done by crease texture only. It is a hint, not a certainty.');

  const detectedLines = [heartLine, headLine, lifeLine, fateLine, sunLine].filter(
    (line) => line.visibility !== 'not_detected',
  ).length;

  const palmDetected = handCoverage >= 0.06 && geometry.fingerCount >= 2 && detectedLines >= 1;
  const rawConfidence = clamp01(
    0.15 +
      clamp01(handCoverage * 2.2) * 0.25 +
      sharpness * 0.2 +
      clamp01(detectedLines / 5) * 0.25 +
      (geometry.fingerDirection === 'up' ? 0.15 : 0),
  );
  // Without a palm we are not confident about anything, whatever the pixels say.
  const confidence = palmDetected ? rawConfidence : rawConfidence * 0.4;

  const overlay = buildOverlay(decoded, geometry, palmDetected, [
    { line: 'heart' as const, feature: heartLine, trace: heart },
    { line: 'head' as const, feature: headLine, trace: head },
    { line: 'life' as const, feature: lifeLine, trace: life },
    { line: 'fate' as const, feature: fateLine, trace: fate },
    { line: 'sun' as const, feature: sunLine, trace: sun },
  ]);

  const palmShape = toPalmShape(geometry);
  const mounts = measureMounts(decoded, field, geometry, thumbSide === 'right' ? 'right' : 'left');

  // Palm facing the camera: a thumb on the right of the frame means a left hand.
  const handSide = thumbSide === 'right' ? 'left' : thumbSide === 'left' ? 'right' : 'unknown';

  return {
    provider: heuristicCvProvider.id,
    technique: heuristicCvProvider.technique,
    simulated: false,
    palmDetected,
    confidence,
    handSide,
    handSideConfidence: clamp01(geometry.thumbConfidence * 0.7),
    palmShape: palmShape.shape,
    palmShapeConfidence: palmShape.confidence,
    fingerProportions:
      geometry.fingerDirection !== 'up'
        ? 'unknown'
        : geometry.fingerRatio >= 0.95
          ? 'long'
          : geometry.fingerRatio >= 0.7
            ? 'balanced'
            : 'short',
    lifeLine,
    headLine,
    heartLine,
    fateLine,
    sunLine,
    mounts,
    imageQuality: {
      brightness,
      handBrightness,
      sharpness,
      handCoverage,
      handRegions,
      borderContact: clamp01(geometry.borderContact),
      fingersFound: geometry.fingerCount,
      palmSideLikely,
      palmSideConfidence,
      width: decoded.width,
      height: decoded.height,
    },
    overlay,
    limitations,
    processingTimeMs: Date.now() - startedAt,
  };
}

/** Average luma over the pixels that belong to the hand. */
function maskedMeanLuma(image: DecodedImage, mask: Uint8Array): number {
  let sum = 0;
  let count = 0;
  for (let i = 0; i < image.gray.length; i += 1) {
    if (mask[i] === 1) {
      sum += image.gray[i] ?? 0;
      count += 1;
    }
  }
  return count === 0 ? 0 : sum / count;
}

function averageBrightness(image: DecodedImage): number {
  let total = 0;
  for (let i = 0; i < image.gray.length; i += 1) total += image.gray[i] ?? 0;
  return total / Math.max(1, image.gray.length);
}

interface ZoneSet {
  heart: Zone;
  head: Zone;
  life: Zone;
  fate: Zone;
  sun: Zone;
}

/**
 * Palmistry zones, expressed in palm-relative coordinates.
 * They are written for a palm whose thumb sits on the left of the frame and get
 * mirrored automatically for the other side.
 */
function buildZones(geometry: HandGeometry, thumbSide: 'left' | 'right'): ZoneSet {
  const { palmRegion } = geometry;
  const mirrored = thumbSide === 'right';

  const px = (u: number): number => Math.round(palmRegion.x + (mirrored ? 1 - u : u) * palmRegion.width);
  const py = (v: number): number => Math.round(palmRegion.y + v * palmRegion.height);

  const horizontal = (u0: number, u1: number, v0: number, v1: number): Zone => ({
    x0: Math.min(px(u0), px(u1)),
    x1: Math.max(px(u0), px(u1)),
    y0: py(v0),
    y1: py(v1),
    orientation: 'horizontal',
    reverse: mirrored,
  });

  const vertical = (u0: number, u1: number, v0: number, v1: number): Zone => ({
    x0: Math.min(px(u0), px(u1)),
    x1: Math.max(px(u0), px(u1)),
    y0: py(v0),
    y1: py(v1),
    orientation: 'vertical',
    reverse: false,
  });

  return {
    heart: horizontal(0.15, 0.95, 0.08, 0.34),
    head: horizontal(0.08, 0.9, 0.32, 0.6),
    life: vertical(0.04, 0.52, 0.12, 0.95),
    fate: vertical(0.34, 0.63, 0.2, 0.98),
    sun: vertical(0.65, 0.95, 0.15, 0.7),
  };
}

function toLineFeature(
  trace: TracedLine | null,
  qualityFactor: number,
  orientation: 'horizontal' | 'vertical',
): LineFeature {
  if (!trace) {
    return {
      visibility: 'not_detected',
      confidence: 0.15,
      length: 'unknown',
      curvature: 'unknown',
      direction: 'unknown',
      detectionMethod: 'cv-crease-trace',
      note: 'The zone for this line was too small or fell outside the detected palm.',
    };
  }

  const { strength, contrast, coverage, bend, drift } = trace;

  let visibility: Visibility = 'not_detected';
  if (strength >= 0.3 && contrast >= 1.45 && coverage >= 0.5) visibility = 'high';
  else if (strength >= 0.18 && contrast >= 1.2 && coverage >= 0.3) visibility = 'medium';
  else if (strength >= 0.1) visibility = 'low';

  const confidence = clamp01((0.2 + strength * 1.1 + (contrast - 1) * 0.28) * qualityFactor);

  const length = coverage >= 0.68 ? 'long' : coverage >= 0.38 ? 'medium' : 'short';

  let curvature: Curvature = 'straight';
  if (bend >= 0.11) curvature = 'pronounced';
  else if (bend >= 0.06) curvature = 'moderate';
  else if (bend >= 0.025) curvature = 'gentle';

  let direction: Direction = 'unknown';
  if (orientation === 'horizontal') {
    if (drift >= 0.14) direction = 'downward';
    else if (drift >= 0.05) direction = 'slightly_downward';
    else if (drift >= -0.05) direction = 'level';
    else direction = 'upward';
  }

  return {
    visibility,
    confidence,
    length: visibility === 'not_detected' ? 'unknown' : length,
    curvature: visibility === 'not_detected' ? 'unknown' : curvature,
    direction: visibility === 'not_detected' ? 'unknown' : direction,
    detectionMethod: 'cv-crease-trace',
    note: `Traced crease strength ${strength.toFixed(2)}, contrast ${contrast.toFixed(2)}x the surrounding skin.`,
  };
}

function toPalmShape(geometry: HandGeometry): { shape: PalmShape; confidence: number } {
  if (geometry.fingerDirection !== 'up') return { shape: 'unknown', confidence: 0.1 };
  const aspect = geometry.palmAspect;
  if (aspect >= 1.12) return { shape: 'broad', confidence: 0.55 };
  if (aspect >= 0.94) return { shape: 'square', confidence: 0.6 };
  if (aspect >= 0.8) return { shape: 'oval', confidence: 0.55 };
  return { shape: 'rectangular', confidence: 0.55 };
}

function measureMounts(
  decoded: DecodedImage,
  field: CreaseField,
  geometry: HandGeometry,
  thumbSide: 'left' | 'right',
): PalmAnalysis['mounts'] {
  const { palmRegion } = geometry;
  const mirrored = thumbSide === 'right';
  const px = (u: number): number => Math.round(palmRegion.x + (mirrored ? 1 - u : u) * palmRegion.width);
  const py = (v: number): number => Math.round(palmRegion.y + v * palmRegion.height);

  const palmLuma = rectMeanLuma(
    decoded,
    palmRegion.x,
    palmRegion.y,
    palmRegion.x + palmRegion.width,
    palmRegion.y + palmRegion.height,
  );

  const measure = (u0: number, u1: number, v0: number, v1: number): MountFeature => {
    const x0 = Math.min(px(u0), px(u1));
    const x1 = Math.max(px(u0), px(u1));
    const luma = rectMeanLuma(decoded, x0, py(v0), x1, py(v1));
    const texture = regionMean(field, x0, py(v0), x1, py(v1));
    // A raised mount catches more light than the palm average under front light.
    const relative = palmLuma > 0.01 ? luma / palmLuma : 1;
    let prominence: Prominence = 'medium';
    if (relative >= 1.06) prominence = 'high';
    else if (relative <= 0.95) prominence = 'low';
    // Shading is a weak proxy for a 3D bump, so confidence stays deliberately low.
    return {
      prominence,
      confidence: clamp01(Math.abs(relative - 1) * 3 + texture * 0.2) * 0.4,
      detectionMethod: 'cv-shape',
    };
  };

  return {
    venus: measure(0.02, 0.34, 0.45, 0.95),
    jupiter: measure(0.14, 0.36, 0.02, 0.16),
    saturn: measure(0.38, 0.57, 0.02, 0.16),
    apollo: measure(0.58, 0.77, 0.02, 0.16),
    mercury: measure(0.78, 0.96, 0.03, 0.18),
    luna: measure(0.62, 0.95, 0.55, 0.95),
  };
}

function rectMeanLuma(image: DecodedImage, x0: number, y0: number, x1: number, y1: number): number {
  let sum = 0;
  let count = 0;
  for (let y = Math.max(0, y0); y <= Math.min(image.height - 1, y1); y += 1) {
    for (let x = Math.max(0, x0); x <= Math.min(image.width - 1, x1); x += 1) {
      sum += image.gray[y * image.width + x] ?? 0;
      count += 1;
    }
  }
  return count === 0 ? 0 : sum / count;
}

function buildOverlay(
  decoded: DecodedImage,
  geometry: HandGeometry,
  palmDetected: boolean,
  lines: Array<{ line: OverlaySegment['line']; feature: LineFeature; trace: TracedLine | null }>,
): Overlay {
  if (!palmDetected) {
    return { available: false, reason: 'No palm was detected, so nothing is drawn on your photo.', segments: [] };
  }

  const segments: OverlaySegment[] = [];
  for (const item of lines) {
    if (!item.trace) continue;
    if (item.feature.visibility === 'not_detected' || item.feature.visibility === 'low') continue;
    if (item.feature.confidence < 0.3) continue;

    // Draw only the stretch that actually sits on a crease. The tracer has to
    // cross the whole zone, but the ends often run over plain skin.
    const measured = item.trace.points.slice(item.trace.strongStart, item.trace.strongEnd + 1);
    if (measured.length < 6) continue;

    const points = simplify(measured).map((point) => ({
      x: point.x / decoded.width,
      y: point.y / decoded.height,
    }));
    if (points.length < 2) continue;
    segments.push({ line: item.line, points, strength: item.trace.strength });
  }

  if (segments.length === 0) {
    return {
      available: false,
      reason: 'No crease was clear enough to mark on your photo, so we show a general palm diagram instead.',
      segments: [],
    };
  }

  return {
    available: true,
    palmRegion: {
      x: geometry.palmRegion.x / decoded.width,
      y: geometry.palmRegion.y / decoded.height,
      width: geometry.palmRegion.width / decoded.width,
      height: geometry.palmRegion.height / decoded.height,
    },
    segments,
  };
}

/**
 * True when two traced paths sit on top of each other, which means one zone
 * caught the neighbouring line rather than its own.
 */
function sharesCrease(a: TracedLine | null, b: TracedLine | null, axis: 'x' | 'y', tolerance: number): boolean {
  if (!a || !b) return false;
  const mean = (trace: TracedLine): number =>
    trace.points.reduce((sum, point) => sum + point[axis], 0) / Math.max(1, trace.points.length);
  return Math.abs(mean(a) - mean(b)) <= tolerance;
}

/** Keep every Nth point so the overlay stays smooth without shipping 400 points. */
function simplify(points: Array<{ x: number; y: number }>, target = 26): Array<{ x: number; y: number }> {
  if (points.length <= target) return points;
  const step = points.length / target;
  const output: Array<{ x: number; y: number }> = [];
  for (let i = 0; i < target; i += 1) {
    const point = points[Math.floor(i * step)];
    if (point) output.push(point);
  }
  const last = points[points.length - 1];
  if (last) output.push(last);
  return output;
}

function emptyResult(
  decoded: DecodedImage,
  input: {
    brightness: number;
    sharpness: number;
    handCoverage: number;
    handRegions: number;
    startedAt: number;
    limitations: string[];
  },
): PalmAnalysis {
  const missing: LineFeature = {
    visibility: 'not_detected',
    confidence: 0,
    length: 'unknown',
    curvature: 'unknown',
    direction: 'unknown',
    detectionMethod: 'cv-crease-trace',
    note: 'No palm region was found in this image.',
  };
  const missingMount: MountFeature = { prominence: 'unknown', confidence: 0, detectionMethod: 'cv-shape' };

  return {
    provider: heuristicCvProvider.id,
    technique: heuristicCvProvider.technique,
    simulated: false,
    palmDetected: false,
    confidence: 0,
    handSide: 'unknown',
    handSideConfidence: 0,
    palmShape: 'unknown',
    palmShapeConfidence: 0,
    fingerProportions: 'unknown',
    lifeLine: missing,
    headLine: missing,
    heartLine: missing,
    fateLine: missing,
    sunLine: missing,
    mounts: {
      venus: missingMount,
      jupiter: missingMount,
      saturn: missingMount,
      apollo: missingMount,
      mercury: missingMount,
      luna: missingMount,
    },
    imageQuality: {
      brightness: input.brightness,
      handBrightness: input.brightness,
      sharpness: input.sharpness,
      handCoverage: input.handCoverage,
      handRegions: input.handRegions,
      borderContact: 0,
      fingersFound: 0,
      palmSideLikely: false,
      palmSideConfidence: 0,
      width: decoded.width,
      height: decoded.height,
    },
    overlay: { available: false, reason: 'No palm was detected in this image.', segments: [] },
    limitations: input.limitations,
    processingTimeMs: Date.now() - input.startedAt,
  };
}
