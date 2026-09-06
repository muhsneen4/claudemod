import { config } from '../config.js';
import type { PalmAnalysis } from '../analysis/types.js';
import type { Reading } from '../interpretation/engine.js';
import { rewriteWithClaude } from './anthropic.js';

export interface NarrationResult {
  reading: Reading;
  /** Which text layer produced the final wording. */
  textProvider: 'template' | 'anthropic';
  /** Set when the language model was configured but could not be used. */
  fallbackReason?: string;
}

/**
 * Optional language layer.
 *
 * The interpretation engine always produces a complete, readable reading on its
 * own. When an Anthropic API key is configured, the same reading is rewritten so
 * it flows more naturally - but the facts, the features and the safety rules
 * come from the engine, and any failure silently falls back to the engine text.
 */
export async function narrate(reading: Reading, analysis: PalmAnalysis): Promise<NarrationResult> {
  if (config.reading.provider !== 'anthropic' || !config.reading.anthropicApiKey) {
    return { reading, textProvider: 'template' };
  }

  try {
    const rewritten = await rewriteWithClaude(reading, analysis);
    return { reading: rewritten, textProvider: 'anthropic' };
  } catch (error) {
    return {
      reading,
      textProvider: 'template',
      fallbackReason: error instanceof Error ? error.message : 'unknown error',
    };
  }
}
