import { blur, clamp, clamp01 } from './imaging.js';

export interface CreaseField {
  /** Crease strength per pixel, 0-1. High = a dark, thin line on the skin. */
  data: Float32Array;
  width: number;
  height: number;
}

/**
 * Build a crease map with a difference-of-gaussians high-pass filter.
 * Palm creases are thin, darker-than-their-surroundings lines, so subtracting a
 * heavily blurred copy of the image from a lightly blurred one makes them pop
 * while lighting gradients and skin tone cancel out.
 */
export function buildCreaseField(
  gray: Float32Array,
  width: number,
  height: number,
  handMask: Uint8Array,
): CreaseField {
  // Replace the background with the average skin tone first. Without this the
  // bright skin against a dark background creates a huge false "crease" all the
  // way around the hand outline, which then swamps the real creases.
  let sum = 0;
  let count = 0;
  for (let i = 0; i < gray.length; i += 1) {
    if (handMask[i] === 1) {
      sum += gray[i] ?? 0;
      count += 1;
    }
  }
  const skinMean = count > 0 ? sum / count : 0.5;

  const filled = new Float32Array(gray.length);
  const maskFloat = new Float32Array(gray.length);
  for (let i = 0; i < gray.length; i += 1) {
    const inside = handMask[i] === 1;
    filled[i] = inside ? gray[i] ?? 0 : skinMean;
    maskFloat[i] = inside ? 1 : 0;
  }

  const radius = Math.max(3, Math.round(Math.min(width, height) * 0.02));
  const fine = blur(filled, width, height, 1);
  const coarse = blur(filled, width, height, radius);

  // Blurring the mask and keeping only the solid core is a cheap erosion: it
  // drops the ring of pixels where the coarse blur still sees the background.
  const softMask = blur(maskFloat, width, height, Math.max(2, Math.round(radius * 0.6)));

  const raw = new Float32Array(gray.length);
  const inside = new Uint8Array(gray.length);
  for (let i = 0; i < gray.length; i += 1) {
    if ((softMask[i] ?? 0) < 0.985) continue;
    inside[i] = 1;
    const value = (coarse[i] ?? 0) - (fine[i] ?? 0);
    raw[i] = value > 0 ? value : 0;
  }

  // Normalise against a high percentile so different exposures compare fairly.
  const samples: number[] = [];
  for (let i = 0; i < raw.length; i += 3) {
    if (inside[i] === 1) samples.push(raw[i] ?? 0);
  }
  samples.sort((a, b) => a - b);
  const percentile = samples.length > 20 ? samples[Math.floor(samples.length * 0.995)] ?? 0 : 0;
  const scale = percentile > 2e-3 ? 1 / percentile : 0;

  const data = new Float32Array(raw.length);
  for (let i = 0; i < raw.length; i += 1) data[i] = clamp01((raw[i] ?? 0) * scale);

  return { data, width, height };
}

export interface Zone {
  /** Pixel rectangle to search inside. */
  x0: number;
  y0: number;
  x1: number;
  y1: number;
  orientation: 'horizontal' | 'vertical';
  /** Traverse the axis backwards, so paths always start at an anatomical origin. */
  reverse: boolean;
}

export interface TracedLine {
  /** Mean crease strength along the best path, 0-1. */
  strength: number;
  /** Path strength divided by the average strength of the whole zone. */
  contrast: number;
  /** Share of the path that stays above the local crease threshold, 0-1. */
  coverage: number;
  /** Sideways deviation from a straight line, relative to the path length. */
  bend: number;
  /** Signed drift across the path, relative to its length. Positive = downward/outward. */
  drift: number;
  /** Pixel coordinates of the traced path. */
  points: Array<{ x: number; y: number }>;
  /**
   * The part of the path that really sits on a crease, as indexes into
   * `points`. Outside this range the path is only crossing plain skin, so the
   * overlay must not draw it.
   */
  strongStart: number;
  strongEnd: number;
}

/**
 * Find the strongest continuous crease inside a zone with dynamic programming.
 * At every step the path may stay level or move one pixel sideways, so the
 * result follows a real, connected crease instead of picking bright pixels at
 * random. Returns null when the zone is too small to search.
 */
export function traceStrongestCrease(field: CreaseField, zone: Zone): TracedLine | null {
  const x0 = Math.max(0, Math.min(zone.x0, zone.x1));
  const x1 = Math.min(field.width - 1, Math.max(zone.x0, zone.x1));
  const y0 = Math.max(0, Math.min(zone.y0, zone.y1));
  const y1 = Math.min(field.height - 1, Math.max(zone.y0, zone.y1));

  const alongLength = zone.orientation === 'horizontal' ? x1 - x0 + 1 : y1 - y0 + 1;
  const acrossLength = zone.orientation === 'horizontal' ? y1 - y0 + 1 : x1 - x0 + 1;
  if (alongLength < 8 || acrossLength < 4) return null;

  const valueAt = (along: number, across: number): number => {
    const alongIndex = zone.reverse ? alongLength - 1 - along : along;
    const x = zone.orientation === 'horizontal' ? x0 + alongIndex : x0 + across;
    const y = zone.orientation === 'horizontal' ? y0 + across : y0 + alongIndex;
    return field.data[y * field.width + x] ?? 0;
  };

  const scores = new Float32Array(alongLength * acrossLength);
  const backtrack = new Int16Array(alongLength * acrossLength);

  for (let across = 0; across < acrossLength; across += 1) {
    scores[across] = valueAt(0, across);
  }

  for (let along = 1; along < alongLength; along += 1) {
    for (let across = 0; across < acrossLength; across += 1) {
      let best = -Infinity;
      let bestOffset = 0;
      for (let offset = -1; offset <= 1; offset += 1) {
        const previous = across + offset;
        if (previous < 0 || previous >= acrossLength) continue;
        const candidate = scores[(along - 1) * acrossLength + previous] ?? -Infinity;
        // A tiny penalty for wandering keeps the path smooth.
        const penalised = candidate - (offset === 0 ? 0 : 0.012);
        if (penalised > best) {
          best = penalised;
          bestOffset = offset;
        }
      }
      const index = along * acrossLength + across;
      scores[index] = best + valueAt(along, across);
      backtrack[index] = bestOffset;
    }
  }

  let endAcross = 0;
  let endScore = -Infinity;
  for (let across = 0; across < acrossLength; across += 1) {
    const value = scores[(alongLength - 1) * acrossLength + across] ?? -Infinity;
    if (value > endScore) {
      endScore = value;
      endAcross = across;
    }
  }

  const path: number[] = new Array<number>(alongLength).fill(0);
  let across = endAcross;
  for (let along = alongLength - 1; along >= 0; along -= 1) {
    path[along] = across;
    // backtrack stores the offset from the previous row, so add it to step back.
    across += backtrack[along * acrossLength + across] ?? 0;
    across = clamp(across, 0, acrossLength - 1);
  }

  let pathSum = 0;
  let zoneSum = 0;
  let zoneCount = 0;
  let above = 0;
  const points: Array<{ x: number; y: number }> = [];

  for (let along = 0; along < alongLength; along += 1) {
    const value = valueAt(along, path[along] ?? 0);
    pathSum += value;
    for (let a = 0; a < acrossLength; a += 1) {
      zoneSum += valueAt(along, a);
      zoneCount += 1;
    }
    const alongIndex = zone.reverse ? alongLength - 1 - along : along;
    const acrossIndex = path[along] ?? 0;
    const x = zone.orientation === 'horizontal' ? x0 + alongIndex : x0 + acrossIndex;
    const y = zone.orientation === 'horizontal' ? y0 + acrossIndex : y0 + alongIndex;
    points.push({ x, y });
  }

  const strength = pathSum / alongLength;
  const zoneMean = zoneSum / Math.max(1, zoneCount);
  const threshold = Math.max(0.08, zoneMean * 1.25);
  for (let along = 0; along < alongLength; along += 1) {
    if (valueAt(along, path[along] ?? 0) >= threshold) above += 1;
  }

  // Longest stretch that stays on a crease, allowing short gaps where a real
  // crease fades. Only this part is safe to draw on a photo.
  let bestStart = 0;
  let bestEnd = -1;
  let runStart = -1;
  let gap = 0;
  const allowedGap = Math.max(3, Math.round(alongLength * 0.06));
  for (let along = 0; along < alongLength; along += 1) {
    const strong = valueAt(along, path[along] ?? 0) >= threshold;
    if (strong) {
      if (runStart === -1) runStart = along;
      gap = 0;
      if (along - runStart > bestEnd - bestStart) {
        bestStart = runStart;
        bestEnd = along;
      }
    } else if (runStart !== -1) {
      gap += 1;
      if (gap > allowedGap) {
        runStart = -1;
        gap = 0;
      }
    }
  }

  const first = path[0] ?? 0;
  const last = path[alongLength - 1] ?? 0;
  let maxDeviation = 0;
  for (let along = 0; along < alongLength; along += 1) {
    const straight = first + ((last - first) * along) / Math.max(1, alongLength - 1);
    maxDeviation = Math.max(maxDeviation, Math.abs((path[along] ?? 0) - straight));
  }

  return {
    strength,
    contrast: zoneMean > 1e-4 ? strength / zoneMean : 0,
    coverage: above / alongLength,
    bend: maxDeviation / alongLength,
    drift: (last - first) / alongLength,
    points,
    strongStart: bestEnd >= bestStart ? bestStart : 0,
    strongEnd: bestEnd >= bestStart ? bestEnd : alongLength - 1,
  };
}

/** Average crease strength in a rectangle - used for mounts and texture checks. */
export function regionMean(
  field: CreaseField,
  x0: number,
  y0: number,
  x1: number,
  y1: number,
): number {
  let sum = 0;
  let count = 0;
  for (let y = Math.max(0, y0); y <= Math.min(field.height - 1, y1); y += 1) {
    for (let x = Math.max(0, x0); x <= Math.min(field.width - 1, x1); x += 1) {
      sum += field.data[y * field.width + x] ?? 0;
      count += 1;
    }
  }
  return count === 0 ? 0 : sum / count;
}
