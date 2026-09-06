import sharp from 'sharp';

export interface DecodedImage {
  width: number;
  height: number;
  originalWidth: number;
  originalHeight: number;
  /** RGB bytes, 3 per pixel. */
  rgb: Uint8Array;
  /** Luma in the 0-1 range, one value per pixel. */
  gray: Float32Array;
}

/**
 * Decode an uploaded file into raw pixels.
 * The image is scaled down first: analysis quality does not improve above a few
 * hundred pixels, and small buffers keep the request fast and memory-safe.
 */
export async function decodeImage(buffer: Buffer, maxSide = 480): Promise<DecodedImage> {
  const pipeline = sharp(buffer, { failOn: 'error', limitInputPixels: 40_000_000 }).rotate();
  const metadata = await pipeline.metadata();

  const { data, info } = await pipeline
    .resize({ width: maxSide, height: maxSide, fit: 'inside', withoutEnlargement: true })
    .removeAlpha()
    .toColorspace('srgb')
    .raw()
    .toBuffer({ resolveWithObject: true });

  const width = info.width;
  const height = info.height;
  const rgb = new Uint8Array(data.buffer, data.byteOffset, data.byteLength);
  const gray = new Float32Array(width * height);

  for (let i = 0, p = 0; i < gray.length; i += 1, p += 3) {
    const r = rgb[p] ?? 0;
    const g = rgb[p + 1] ?? 0;
    const b = rgb[p + 2] ?? 0;
    gray[i] = (0.299 * r + 0.587 * g + 0.114 * b) / 255;
  }

  return {
    width,
    height,
    originalWidth: metadata.width ?? width,
    originalHeight: metadata.height ?? height,
    rgb,
    gray,
  };
}

/** Separable box blur, repeated twice - a fast and good enough gaussian. */
export function blur(source: Float32Array, width: number, height: number, radius: number): Float32Array {
  if (radius < 1) return new Float32Array(source);
  let current: Float32Array = new Float32Array(source);
  for (let pass = 0; pass < 2; pass += 1) {
    current = boxBlurPass(current, width, height, radius);
  }
  return current;
}

function boxBlurPass(source: Float32Array, width: number, height: number, radius: number): Float32Array {
  const horizontal = new Float32Array(source.length);
  const window = radius * 2 + 1;

  for (let y = 0; y < height; y += 1) {
    const row = y * width;
    let sum = 0;
    for (let x = -radius; x <= radius; x += 1) {
      sum += source[row + clamp(x, 0, width - 1)] ?? 0;
    }
    for (let x = 0; x < width; x += 1) {
      horizontal[row + x] = sum / window;
      const outgoing = source[row + clamp(x - radius, 0, width - 1)] ?? 0;
      const incoming = source[row + clamp(x + radius + 1, 0, width - 1)] ?? 0;
      sum += incoming - outgoing;
    }
  }

  const vertical = new Float32Array(source.length);
  for (let x = 0; x < width; x += 1) {
    let sum = 0;
    for (let y = -radius; y <= radius; y += 1) {
      sum += horizontal[clamp(y, 0, height - 1) * width + x] ?? 0;
    }
    for (let y = 0; y < height; y += 1) {
      vertical[y * width + x] = sum / window;
      const outgoing = horizontal[clamp(y - radius, 0, height - 1) * width + x] ?? 0;
      const incoming = horizontal[clamp(y + radius + 1, 0, height - 1) * width + x] ?? 0;
      sum += incoming - outgoing;
    }
  }

  return vertical;
}

/**
 * Variance of the Laplacian: the classic, well understood sharpness measure.
 * A blurry photo has almost no high frequency energy, so the variance collapses.
 */
export function laplacianVariance(gray: Float32Array, width: number, height: number): number {
  let sum = 0;
  let sumSquares = 0;
  let count = 0;

  for (let y = 1; y < height - 1; y += 1) {
    for (let x = 1; x < width - 1; x += 1) {
      const i = y * width + x;
      const value =
        4 * (gray[i] ?? 0) -
        (gray[i - 1] ?? 0) -
        (gray[i + 1] ?? 0) -
        (gray[i - width] ?? 0) -
        (gray[i + width] ?? 0);
      sum += value;
      sumSquares += value * value;
      count += 1;
    }
  }

  if (count === 0) return 0;
  const mean = sum / count;
  return sumSquares / count - mean * mean;
}

export function mean(values: ArrayLike<number>): number {
  if (values.length === 0) return 0;
  let total = 0;
  for (let i = 0; i < values.length; i += 1) total += values[i] ?? 0;
  return total / values.length;
}

export function clamp(value: number, min: number, max: number): number {
  return value < min ? min : value > max ? max : value;
}

export function clamp01(value: number): number {
  return clamp(value, 0, 1);
}

/** Re-encode an image for display: small, modern format, no metadata kept. */
export async function toPreviewImage(buffer: Buffer, maxSide = 900): Promise<{ data: Buffer; contentType: string }> {
  const data = await sharp(buffer, { failOn: 'error', limitInputPixels: 40_000_000 })
    .rotate()
    .resize({ width: maxSide, height: maxSide, fit: 'inside', withoutEnlargement: true })
    .webp({ quality: 82 })
    .toBuffer();
  return { data, contentType: 'image/webp' };
}
