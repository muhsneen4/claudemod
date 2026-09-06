import multer from 'multer';
import sharp from 'sharp';
import { config } from '../config.js';
import { ApiError } from '../util/errors.js';

/**
 * Uploads stay in memory and never reach the file system, so a malicious file
 * name or path can never be written anywhere. Size and count are capped before
 * a single byte is buffered.
 */
export const uploadPalmImage = multer({
  storage: multer.memoryStorage(),
  limits: {
    fileSize: config.uploads.maxBytes,
    files: 1,
    fields: 4,
    parts: 6,
  },
  fileFilter: (_request, file, callback) => {
    if (!(config.uploads.allowedMimeTypes as readonly string[]).includes(file.mimetype)) {
      callback(new ApiError(415, 'unsupported_file_type', 'Please upload a JPEG, PNG or WebP photo.'));
      return;
    }
    callback(null, true);
  },
}).single('image');

const SAFE_FORMATS = new Set(['jpeg', 'jpg', 'png', 'webp', 'heif', 'heic', 'avif']);

/**
 * Never trust the declared content type. This re-reads the header bytes with the
 * image decoder itself and rejects anything that is not a real, sane photo.
 */
export async function assertDecodableImage(buffer: Buffer): Promise<{ format: string; width: number; height: number }> {
  if (buffer.length < 100) {
    throw new ApiError(400, 'invalid_image', 'That file looks empty. Please choose a palm photo.');
  }

  let metadata;
  try {
    metadata = await sharp(buffer, { failOn: 'error', limitInputPixels: 40_000_000 }).metadata();
  } catch {
    throw new ApiError(400, 'invalid_image', 'We could not open that file as an image. Please try a JPEG or PNG photo.');
  }

  const format = metadata.format ?? '';
  if (!SAFE_FORMATS.has(format)) {
    throw new ApiError(415, 'unsupported_file_type', 'Please upload a JPEG, PNG or WebP photo.');
  }

  const width = metadata.width ?? 0;
  const height = metadata.height ?? 0;
  if (width < 80 || height < 80) {
    throw new ApiError(400, 'image_too_small', 'That image is very small. Please use a photo of at least 300 by 300 pixels.');
  }
  if (width * height > 40_000_000) {
    throw new ApiError(413, 'image_too_large', 'That image is very large. Please use a smaller photo.');
  }

  return { format, width, height };
}
