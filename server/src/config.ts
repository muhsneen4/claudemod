import 'dotenv/config';

function str(name: string, fallback = ''): string {
  const value = process.env[name];
  return value === undefined || value === '' ? fallback : value;
}

function int(name: string, fallback: number): number {
  const raw = process.env[name];
  if (!raw) return fallback;
  const parsed = Number.parseInt(raw, 10);
  return Number.isFinite(parsed) && parsed > 0 ? parsed : fallback;
}

function list(name: string): string[] {
  return str(name)
    .split(',')
    .map((item) => item.trim())
    .filter(Boolean);
}

export type AnalysisProviderName = 'heuristic-cv' | 'mock' | 'remote';
export type ReadingTextProviderName = 'template' | 'anthropic';

const analysisProvider = str('PALM_ANALYSIS_PROVIDER', 'heuristic-cv') as AnalysisProviderName;
const readingTextProvider = str('READING_TEXT_PROVIDER', 'template') as ReadingTextProviderName;

export const config = {
  env: str('NODE_ENV', 'development'),
  port: int('PORT', 8787),
  isProduction: str('NODE_ENV', 'development') === 'production',

  corsOrigins: list('CORS_ORIGINS'),

  analysis: {
    provider: (['heuristic-cv', 'mock', 'remote'] as const).includes(analysisProvider)
      ? analysisProvider
      : ('heuristic-cv' as AnalysisProviderName),
    remoteUrl: str('REMOTE_ANALYSIS_URL'),
    remoteApiKey: str('REMOTE_ANALYSIS_API_KEY'),
    remoteTimeoutMs: int('REMOTE_ANALYSIS_TIMEOUT_MS', 15000),
  },

  reading: {
    provider: (['template', 'anthropic'] as const).includes(readingTextProvider)
      ? readingTextProvider
      : ('template' as ReadingTextProviderName),
    anthropicApiKey: str('ANTHROPIC_API_KEY'),
    anthropicModel: str('ANTHROPIC_MODEL', 'claude-sonnet-5'),
    anthropicTimeoutMs: int('ANTHROPIC_TIMEOUT_MS', 20000),
  },

  uploads: {
    maxBytes: int('MAX_UPLOAD_BYTES', 8_000_000),
    allowedMimeTypes: ['image/jpeg', 'image/png', 'image/webp', 'image/heic', 'image/heif'],
  },

  rateLimit: {
    windowMs: int('RATE_LIMIT_WINDOW_MS', 60_000),
    max: int('RATE_LIMIT_MAX_REQUESTS', 20),
  },

  storage: {
    imageRetentionMs: int('IMAGE_RETENTION_MS', 15 * 60 * 1000),
  },

  admin: {
    token: str('ADMIN_API_TOKEN'),
    get enabled(): boolean {
      return str('ADMIN_API_TOKEN').length >= 16;
    },
  },
} as const;

/** Small, safe summary of the runtime setup. Contains no secrets. */
export function publicConfigSummary() {
  return {
    analysisProvider: config.analysis.provider,
    analysisIsSimulated: config.analysis.provider === 'mock',
    readingTextProvider:
      config.reading.provider === 'anthropic' && config.reading.anthropicApiKey
        ? 'anthropic'
        : 'template',
    maxUploadBytes: config.uploads.maxBytes,
    acceptedTypes: config.uploads.allowedMimeTypes,
    imageRetentionMs: config.storage.imageRetentionMs,
  };
}
