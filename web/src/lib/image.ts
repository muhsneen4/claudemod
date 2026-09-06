export interface PreparedImage {
  blob: Blob;
  previewUrl: string;
  width: number;
  height: number;
  originalBytes: number;
  bytes: number;
}

const MAX_SIDE = 1400;
const TARGET_TYPE = 'image/webp';

/**
 * Shrink and re-encode the photo in the browser before uploading.
 * A modern phone photo is 4-8 MB; this usually sends under 300 KB, which makes
 * the upload fast on mobile data and keeps the server's work small.
 * Everything happens locally - the original file is never uploaded.
 */
export async function prepareImage(file: File): Promise<PreparedImage> {
  const originalBytes = file.size;
  const bitmap = await loadBitmap(file);

  const scale = Math.min(1, MAX_SIDE / Math.max(bitmap.width, bitmap.height));
  const width = Math.max(1, Math.round(bitmap.width * scale));
  const height = Math.max(1, Math.round(bitmap.height * scale));

  const canvas = document.createElement('canvas');
  canvas.width = width;
  canvas.height = height;
  const context = canvas.getContext('2d');
  if (!context) throw new Error('Your browser could not process this image.');
  context.drawImage(bitmap, 0, 0, width, height);
  if ('close' in bitmap) bitmap.close();

  const blob = await canvasToBlob(canvas, TARGET_TYPE, 0.86);
  return {
    blob,
    previewUrl: URL.createObjectURL(blob),
    width,
    height,
    originalBytes,
    bytes: blob.size,
  };
}

async function loadBitmap(file: File): Promise<ImageBitmap | HTMLImageElement> {
  if ('createImageBitmap' in window) {
    try {
      return await createImageBitmap(file);
    } catch {
      // Safari on older iOS throws for some HEIC files - fall through.
    }
  }

  const url = URL.createObjectURL(file);
  try {
    return await new Promise<HTMLImageElement>((resolve, reject) => {
      const image = new Image();
      image.onload = () => resolve(image);
      image.onerror = () => reject(new Error('We could not open that image.'));
      image.src = url;
    });
  } finally {
    setTimeout(() => URL.revokeObjectURL(url), 10_000);
  }
}

function canvasToBlob(canvas: HTMLCanvasElement, type: string, quality: number): Promise<Blob> {
  return new Promise((resolve, reject) => {
    canvas.toBlob(
      (blob) => {
        if (blob && blob.size > 0) resolve(blob);
        else if (type !== 'image/jpeg') canvasToBlob(canvas, 'image/jpeg', quality).then(resolve, reject);
        else reject(new Error('We could not prepare that image for upload.'));
      },
      type,
      quality,
    );
  });
}

export function formatBytes(bytes: number): string {
  if (bytes < 1024) return `${bytes} B`;
  if (bytes < 1024 * 1024) return `${Math.round(bytes / 1024)} KB`;
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}
