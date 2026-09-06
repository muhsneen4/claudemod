import type { AnalyzeResponse, Capabilities } from './types';

/**
 * All requests are same origin (`/api/...`).
 * In development Vite proxies them to the API server, in production the API
 * serves the built frontend - so no API host or key ever reaches the browser.
 */
const BASE = '/api';

export class ApiRequestError extends Error {
  readonly code: string;
  readonly status: number;

  constructor(status: number, code: string, message: string) {
    super(message);
    this.name = 'ApiRequestError';
    this.status = status;
    this.code = code;
  }
}

async function parseError(response: Response): Promise<never> {
  let code = 'request_failed';
  let message = 'Something went wrong. Please try again.';
  try {
    const body = (await response.json()) as { error?: { code?: string; message?: string } };
    code = body.error?.code ?? code;
    message = body.error?.message ?? message;
  } catch {
    // A non JSON error (proxy, gateway, offline) keeps the friendly default.
  }
  throw new ApiRequestError(response.status, code, message);
}

export async function fetchCapabilities(signal?: AbortSignal): Promise<Capabilities> {
  const response = await fetch(`${BASE}/palm/capabilities`, { signal });
  if (!response.ok) return parseError(response);
  return (await response.json()) as Capabilities;
}

export async function analyzePalm(file: Blob, signal?: AbortSignal): Promise<AnalyzeResponse> {
  const form = new FormData();
  form.append('image', file, 'palm.jpg');

  const response = await fetch(`${BASE}/palm/analyze`, { method: 'POST', body: form, signal });
  if (!response.ok) return parseError(response);
  return (await response.json()) as AnalyzeResponse;
}

export async function deletePalmImage(url: string): Promise<boolean> {
  try {
    const response = await fetch(url, { method: 'DELETE' });
    if (!response.ok) return false;
    const body = (await response.json()) as { deleted?: boolean };
    return body.deleted === true;
  } catch {
    return false;
  }
}
