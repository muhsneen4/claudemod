/**
 * Monetisation scaffolding.
 *
 * The first version is completely free and shows no upsell. Everything a paid
 * tier would unlock is already named here, so adding a checkout later means
 * changing `resolveTier` and flipping flags - not rewriting the reading flow.
 */

export type Tier = 'free' | 'premium';

export interface Entitlements {
  tier: Tier;
  features: {
    basicReading: boolean;
    lineOverlay: boolean;
    /** Longer, section by section deep dive. */
    detailedReading: boolean;
    pdfReport: boolean;
    savedReadings: boolean;
    compareTwoPalms: boolean;
    compatibilityReading: boolean;
    dailyInsight: boolean;
  };
  /** Readings allowed per rate limit window. */
  readingsPerWindow: number;
}

const FREE: Entitlements = {
  tier: 'free',
  features: {
    basicReading: true,
    lineOverlay: true,
    detailedReading: false,
    pdfReport: false,
    savedReadings: false,
    compareTwoPalms: false,
    compatibilityReading: false,
    dailyInsight: false,
  },
  readingsPerWindow: 20,
};

const PREMIUM: Entitlements = {
  tier: 'premium',
  features: {
    basicReading: true,
    lineOverlay: true,
    detailedReading: true,
    pdfReport: true,
    savedReadings: true,
    compareTwoPalms: true,
    compatibilityReading: true,
    dailyInsight: true,
  },
  readingsPerWindow: 200,
};

/**
 * Everyone is on the free tier today. Replace this with a real lookup (session,
 * licence key, payment provider webhook) when billing is added.
 */
export function resolveTier(_token?: string): Entitlements {
  return FREE;
}

export const TIERS = { FREE, PREMIUM };
