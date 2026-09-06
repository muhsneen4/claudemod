import { clamp01, type DecodedImage } from './imaging.js';

export interface Blob {
  label: number;
  area: number;
  minX: number;
  maxX: number;
  minY: number;
  maxY: number;
  borderPixels: number;
  topBorderPixels: number;
}

export interface SkinResult {
  mask: Uint8Array;
  /** Share of the frame classified as skin. */
  coverage: number;
  /** true when colour was unusable and a brightness fallback was used instead. */
  usedLuminanceFallback: boolean;
  meanSaturation: number;
}

/**
 * Colour based skin segmentation in the YCbCr space.
 * Chrominance rules behave far better across different skin tones than plain RGB
 * rules do, but they are still an approximation - strong colour casts, gloves or
 * skin-coloured backgrounds can fool them. Callers must treat the result as a
 * hint with a confidence, never as ground truth.
 */
export function segmentSkin(image: DecodedImage): SkinResult {
  const { rgb, width, height } = image;
  const total = width * height;
  const mask = new Uint8Array(total);
  let skinCount = 0;
  let saturationSum = 0;

  for (let i = 0, p = 0; i < total; i += 1, p += 3) {
    const r = rgb[p] ?? 0;
    const g = rgb[p + 1] ?? 0;
    const b = rgb[p + 2] ?? 0;

    const max = Math.max(r, g, b);
    const min = Math.min(r, g, b);
    saturationSum += max === 0 ? 0 : (max - min) / max;

    const y = 0.299 * r + 0.587 * g + 0.114 * b;
    const cb = 128 - 0.168736 * r - 0.331264 * g + 0.5 * b;
    const cr = 128 + 0.5 * r - 0.418688 * g - 0.081312 * b;

    const isSkin =
      y > 28 &&
      cb >= 77 &&
      cb <= 133 &&
      cr >= 133 &&
      cr <= 180 &&
      r >= g &&
      g >= b * 0.75 &&
      max - min > 8;

    if (isSkin) {
      mask[i] = 1;
      skinCount += 1;
    }
  }

  const meanSaturation = saturationSum / Math.max(1, total);
  const coverage = skinCount / Math.max(1, total);

  if (coverage < 0.04 && meanSaturation < 0.12) {
    // Near greyscale photo: colour tells us nothing, so fall back to brightness.
    const fallback = luminanceForeground(image);
    return { mask: fallback.mask, coverage: fallback.coverage, usedLuminanceFallback: true, meanSaturation };
  }

  cleanMask(mask, width, height);
  let cleaned = 0;
  for (let i = 0; i < total; i += 1) cleaned += mask[i] ?? 0;

  return { mask, coverage: cleaned / Math.max(1, total), usedLuminanceFallback: false, meanSaturation };
}

/** Otsu threshold on luma, used only when the photo has almost no colour. */
function luminanceForeground(image: DecodedImage): { mask: Uint8Array; coverage: number } {
  const { gray, width, height } = image;
  const histogram = new Array<number>(256).fill(0);
  for (let i = 0; i < gray.length; i += 1) {
    const bin = Math.min(255, Math.max(0, Math.round((gray[i] ?? 0) * 255)));
    histogram[bin] = (histogram[bin] ?? 0) + 1;
  }

  const total = gray.length;
  let sumAll = 0;
  for (let t = 0; t < 256; t += 1) sumAll += t * (histogram[t] ?? 0);

  let sumBackground = 0;
  let weightBackground = 0;
  let best = 0;
  let threshold = 128;

  for (let t = 0; t < 256; t += 1) {
    weightBackground += histogram[t] ?? 0;
    if (weightBackground === 0) continue;
    const weightForeground = total - weightBackground;
    if (weightForeground === 0) break;
    sumBackground += t * (histogram[t] ?? 0);
    const meanBackground = sumBackground / weightBackground;
    const meanForeground = (sumAll - sumBackground) / weightForeground;
    const between = weightBackground * weightForeground * (meanBackground - meanForeground) ** 2;
    if (between > best) {
      best = between;
      threshold = t;
    }
  }

  const mask = new Uint8Array(total);
  let count = 0;
  for (let i = 0; i < total; i += 1) {
    if ((gray[i] ?? 0) * 255 > threshold) {
      mask[i] = 1;
      count += 1;
    }
  }
  cleanMask(mask, width, height);
  let cleaned = 0;
  for (let i = 0; i < total; i += 1) cleaned += mask[i] ?? 0;
  return { mask, coverage: cleaned / Math.max(1, total) };
}

/** Majority filter - removes speckles and fills pinholes in one cheap pass. */
function cleanMask(mask: Uint8Array, width: number, height: number): void {
  const source = Uint8Array.from(mask);
  for (let y = 1; y < height - 1; y += 1) {
    for (let x = 1; x < width - 1; x += 1) {
      const i = y * width + x;
      let neighbours = 0;
      for (let dy = -1; dy <= 1; dy += 1) {
        for (let dx = -1; dx <= 1; dx += 1) {
          neighbours += source[i + dy * width + dx] ?? 0;
        }
      }
      mask[i] = neighbours >= 5 ? 1 : 0;
    }
  }
}

/** Iterative flood fill labelling - no recursion, so big blobs cannot blow the stack. */
export function findBlobs(mask: Uint8Array, width: number, height: number): { labels: Int32Array; blobs: Blob[] } {
  const labels = new Int32Array(mask.length).fill(-1);
  const blobs: Blob[] = [];
  const queue = new Int32Array(mask.length + 1);

  for (let start = 0; start < mask.length; start += 1) {
    if (mask[start] !== 1 || labels[start] !== -1) continue;

    const label = blobs.length;
    let head = 0;
    let tail = 0;
    queue[tail += 1] = start;
    labels[start] = label;

    const blob: Blob = {
      label,
      area: 0,
      minX: width,
      maxX: 0,
      minY: height,
      maxY: 0,
      borderPixels: 0,
      topBorderPixels: 0,
    };

    while (head < tail) {
      const index = queue[head += 1] ?? 0;
      const x = index % width;
      const y = (index - x) / width;

      blob.area += 1;
      if (x < blob.minX) blob.minX = x;
      if (x > blob.maxX) blob.maxX = x;
      if (y < blob.minY) blob.minY = y;
      if (y > blob.maxY) blob.maxY = y;
      if (x === 0 || y === 0 || x === width - 1 || y === height - 1) {
        blob.borderPixels += 1;
        if (y === 0) blob.topBorderPixels += 1;
      }

      if (x > 0 && mask[index - 1] === 1 && labels[index - 1] === -1) {
        labels[index - 1] = label;
        queue[tail += 1] = index - 1;
      }
      if (x < width - 1 && mask[index + 1] === 1 && labels[index + 1] === -1) {
        labels[index + 1] = label;
        queue[tail += 1] = index + 1;
      }
      if (y > 0 && mask[index - width] === 1 && labels[index - width] === -1) {
        labels[index - width] = label;
        queue[tail += 1] = index - width;
      }
      if (y < height - 1 && mask[index + width] === 1 && labels[index + width] === -1) {
        labels[index + width] = label;
        queue[tail += 1] = index + width;
      }
    }

    blobs.push(blob);
  }

  blobs.sort((a, b) => b.area - a.area);
  return { labels, blobs };
}

export interface RowRun {
  start: number;
  end: number;
}

export interface HandGeometry {
  /** Bounding box of the hand region. */
  bounds: { minX: number; maxX: number; minY: number; maxY: number };
  /** Palm area only - fingers and wrist removed. */
  palmRegion: { x: number; y: number; width: number; height: number };
  fingerCount: number;
  fingerZoneHeight: number;
  palmZoneHeight: number;
  /** Ratio of finger length to palm height. */
  fingerRatio: number;
  /** Palm width divided by palm height. */
  palmAspect: number;
  /** Which edge the fingers point to. Only "up" is fully supported. */
  fingerDirection: 'up' | 'down' | 'sideways' | 'unknown';
  /** Side of the image the thumb sticks out on, as seen in the photo. */
  thumbSide: 'left' | 'right' | 'unknown';
  thumbConfidence: number;
  borderContact: number;
}

/**
 * Turn a hand shaped blob into measurements.
 * The trick used here is run-length analysis per row: rows crossing the fingers
 * contain several separate runs of skin, rows crossing the palm contain one wide
 * run. That single observation gives us the finger zone, the palm zone, the
 * number of visible fingers and the thumb side.
 */
export function measureHand(
  labels: Int32Array,
  blob: Blob,
  width: number,
  height: number,
): HandGeometry {
  const rowRuns: RowRun[][] = [];
  for (let y = 0; y < height; y += 1) {
    const runs: RowRun[] = [];
    let start = -1;
    for (let x = 0; x < width; x += 1) {
      const isBlob = labels[y * width + x] === blob.label;
      if (isBlob && start === -1) start = x;
      if ((!isBlob || x === width - 1) && start !== -1) {
        const end = isBlob ? x : x - 1;
        if (end - start >= 1) runs.push({ start, end });
        start = -1;
      }
    }
    rowRuns.push(runs);
  }

  const blobHeight = Math.max(1, blob.maxY - blob.minY + 1);
  const blobWidth = Math.max(1, blob.maxX - blob.minX + 1);
  const minRunWidth = Math.max(2, Math.round(blobWidth * 0.04));

  const fingerRow = (y: number): boolean => {
    const runs = (rowRuns[y] ?? []).filter((run) => run.end - run.start >= minRunWidth);
    return runs.length >= 3;
  };

  let topFingerRows = 0;
  let bottomFingerRows = 0;
  const middle = blob.minY + Math.round(blobHeight / 2);
  for (let y = blob.minY; y <= blob.maxY; y += 1) {
    if (!fingerRow(y)) continue;
    if (y < middle) topFingerRows += 1;
    else bottomFingerRows += 1;
  }

  let fingerDirection: HandGeometry['fingerDirection'] = 'unknown';
  if (topFingerRows >= 3 && topFingerRows >= bottomFingerRows * 2) fingerDirection = 'up';
  else if (bottomFingerRows >= 3 && bottomFingerRows >= topFingerRows * 2) fingerDirection = 'down';
  else if (topFingerRows + bottomFingerRows < 3 && blobWidth > blobHeight * 1.15) fingerDirection = 'sideways';

  // Finger zone = the band of multi-run rows nearest to the finger tips.
  let fingerZoneEnd = blob.minY;
  if (fingerDirection === 'up') {
    for (let y = blob.minY; y <= blob.maxY; y += 1) {
      if (fingerRow(y)) fingerZoneEnd = y;
      else if (y - fingerZoneEnd > blobHeight * 0.06 && fingerZoneEnd > blob.minY) break;
    }
  }

  const fingerZoneHeight = fingerDirection === 'up' ? Math.max(0, fingerZoneEnd - blob.minY) : 0;

  let fingerCount = 0;
  for (let y = blob.minY; y <= Math.max(blob.minY, fingerZoneEnd); y += 1) {
    const runs = (rowRuns[y] ?? []).filter((run) => run.end - run.start >= minRunWidth);
    if (runs.length > fingerCount) fingerCount = runs.length;
  }

  // Palm zone = single wide run rows just below the fingers.
  const palmTop = fingerDirection === 'up' ? Math.min(blob.maxY, fingerZoneEnd + 1) : blob.minY;
  let palmBottom = blob.maxY;
  let widest = 0;
  for (let y = palmTop; y <= blob.maxY; y += 1) {
    const runs = rowRuns[y] ?? [];
    const rowWidth = runs.reduce((acc, run) => Math.max(acc, run.end - run.start), 0);
    if (rowWidth > widest) widest = rowWidth;
  }
  for (let y = palmTop; y <= blob.maxY; y += 1) {
    const runs = rowRuns[y] ?? [];
    const rowWidth = runs.reduce((acc, run) => Math.max(acc, run.end - run.start), 0);
    if (rowWidth < widest * 0.45) {
      palmBottom = Math.max(palmTop + 1, y - 1);
      break;
    }
  }

  const palmZoneHeight = Math.max(1, palmBottom - palmTop);

  // Width is measured just below the fingers only. Lower down the thumb sticks
  // out, and including it would push every palmistry zone sideways.
  const widthBandEnd = Math.min(palmBottom, palmTop + Math.round(palmZoneHeight * 0.4));
  let palmMinX = width;
  let palmMaxX = 0;
  for (let y = palmTop; y <= widthBandEnd; y += 1) {
    for (const run of rowRuns[y] ?? []) {
      if (run.end - run.start < minRunWidth) continue;
      if (run.start < palmMinX) palmMinX = run.start;
      if (run.end > palmMaxX) palmMaxX = run.end;
    }
  }
  if (palmMaxX <= palmMinX) {
    palmMinX = blob.minX;
    palmMaxX = blob.maxX;
  }

  // Thumb side: the thumb widens the lower half of the palm on one side only.
  const upperBand = { start: palmTop, end: palmTop + Math.round(palmZoneHeight * 0.35) };
  const lowerBand = { start: palmTop + Math.round(palmZoneHeight * 0.45), end: palmBottom };
  const edge = (from: number, to: number, side: 'left' | 'right'): number => {
    const values: number[] = [];
    for (let y = from; y <= to; y += 1) {
      const runs = (rowRuns[y] ?? []).filter((run) => run.end - run.start >= minRunWidth);
      if (runs.length === 0) continue;
      values.push(side === 'left' ? Math.min(...runs.map((r) => r.start)) : Math.max(...runs.map((r) => r.end)));
    }
    if (values.length === 0) return side === 'left' ? palmMinX : palmMaxX;
    return values.reduce((a, b) => a + b, 0) / values.length;
  };

  const leftGrowth = edge(upperBand.start, upperBand.end, 'left') - edge(lowerBand.start, lowerBand.end, 'left');
  const rightGrowth = edge(lowerBand.start, lowerBand.end, 'right') - edge(upperBand.start, upperBand.end, 'right');
  const palmWidth = Math.max(1, palmMaxX - palmMinX);
  const difference = (leftGrowth - rightGrowth) / palmWidth;

  let thumbSide: HandGeometry['thumbSide'] = 'unknown';
  let thumbConfidence = 0;
  if (Math.abs(difference) > 0.06) {
    thumbSide = difference > 0 ? 'left' : 'right';
    thumbConfidence = clamp01(Math.abs(difference) * 3);
  }

  return {
    bounds: { minX: blob.minX, maxX: blob.maxX, minY: blob.minY, maxY: blob.maxY },
    palmRegion: {
      x: palmMinX,
      y: palmTop,
      width: Math.max(1, palmMaxX - palmMinX),
      height: palmZoneHeight,
    },
    fingerCount,
    fingerZoneHeight,
    palmZoneHeight,
    fingerRatio: fingerZoneHeight / palmZoneHeight,
    palmAspect: Math.max(1, palmMaxX - palmMinX) / palmZoneHeight,
    fingerDirection,
    thumbSide,
    thumbConfidence,
    borderContact: blob.borderPixels / Math.max(1, 2 * (blob.maxX - blob.minX + blob.maxY - blob.minY)),
  };
}
