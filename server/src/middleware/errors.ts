import type { NextFunction, Request, Response } from 'express';
import { MulterError } from 'multer';
import { config } from '../config.js';
import { ApiError } from '../util/errors.js';

export function notFound(_request: Request, response: Response): void {
  response.status(404).json({ error: { code: 'not_found', message: 'That endpoint does not exist.' } });
}

/** Single place where every failure becomes a safe, predictable JSON body. */
export function errorHandler(
  error: unknown,
  _request: Request,
  response: Response,
  next: NextFunction,
): void {
  if (response.headersSent) {
    next(error);
    return;
  }

  if (error instanceof ApiError) {
    response.status(error.status).json({ error: { code: error.code, message: error.message, details: error.details } });
    return;
  }

  if (error instanceof MulterError) {
    const message =
      error.code === 'LIMIT_FILE_SIZE'
        ? `That photo is larger than ${Math.round(config.uploads.maxBytes / 1_000_000)} MB. Please try a smaller one.`
        : 'We could not read that upload. Please try again with a single photo.';
    response.status(413).json({ error: { code: 'upload_rejected', message } });
    return;
  }

  if (!config.isProduction) {
    console.error('[destiny-palm] unhandled error', error);
  }

  response.status(500).json({
    error: { code: 'internal_error', message: 'Something went wrong on our side. Please try again in a moment.' },
  });
}
