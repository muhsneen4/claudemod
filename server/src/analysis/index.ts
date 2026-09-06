import { config } from '../config.js';
import { heuristicCvProvider } from './heuristicCv.js';
import { mockProvider } from './mock.js';
import { remoteProvider } from './remote.js';
import type { AnalyzePalm, PalmAnalysisProvider } from './types.js';

const providers: Record<string, PalmAnalysisProvider> = {
  [heuristicCvProvider.id]: heuristicCvProvider,
  [mockProvider.id]: mockProvider,
  [remoteProvider.id]: remoteProvider,
};

/** Register another implementation at boot time without touching the routes. */
export function registerProvider(provider: PalmAnalysisProvider): void {
  providers[provider.id] = provider;
}

export function getProvider(id: string = config.analysis.provider): PalmAnalysisProvider {
  return providers[id] ?? heuristicCvProvider;
}

/** The single entry point the rest of the server uses. */
export const analyzePalm: AnalyzePalm = (image, options) => getProvider().analyzePalm(image, options);

export * from './types.js';
export { validateForAnalysis } from './validation.js';
