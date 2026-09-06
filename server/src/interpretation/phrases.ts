import type { Curvature, Direction, Length, PalmShape, Visibility } from '../analysis/types.js';

/**
 * Phrase banks for the interpretation engine.
 *
 * Two rules apply to every line of text in this file:
 *  1. Nothing is stated as a fact about the future. Traditional palmistry is
 *     described as a tradition, using words like "links", "associates" or
 *     "may suggest".
 *  2. Nothing promises money, marriage, children, health, illness or safety.
 */

export const VISIBILITY_WORDS: Record<Visibility, string> = {
  high: 'clear and easy to follow',
  medium: 'visible, though softer in places',
  low: 'faint and broken up',
  not_detected: 'not clear enough to trace in this photo',
};

export const CURVATURE_WORDS: Record<Curvature, string> = {
  straight: 'running almost straight',
  gentle: 'with a soft bend',
  moderate: 'with a noticeable curve',
  pronounced: 'with a strong, sweeping curve',
  unknown: '',
};

export const LENGTH_WORDS: Record<Length, string> = {
  short: 'covering a short stretch of the palm',
  medium: 'reaching about halfway across its zone',
  long: 'running a long way across the palm',
  unknown: '',
};

export const DIRECTION_WORDS: Record<Direction, string> = {
  upward: 'lifting slightly towards the fingers',
  level: 'staying fairly level',
  slightly_downward: 'dipping gently towards the wrist',
  downward: 'sloping clearly towards the wrist',
  unknown: '',
};

export const PALM_SHAPE_WORDS: Record<PalmShape, string> = {
  square: 'a square palm, about as wide as it is tall',
  rectangular: 'a longer, rectangular palm',
  oval: 'a softly oval palm',
  broad: 'a broad palm, wider than it is tall',
  unknown: 'a palm shape we could not measure well in this photo',
};

/** Heart line -> love and relationships. */
export const HEART_MEANING: Record<Curvature, string[]> = {
  pronounced: [
    'Traditional palmistry links a strongly curved heart line with people who show warmth openly and say what they feel.',
    'In old palmistry books, a deep curve here is associated with an expressive, generous way of caring for others.',
  ],
  moderate: [
    'Palmistry tradition connects a curved heart line with someone who feels deeply but still thinks before speaking.',
    'A curve like this is traditionally read as a balance between open affection and quiet privacy.',
  ],
  gentle: [
    'A soft bend in the heart line is traditionally linked to steady affection rather than dramatic ups and downs.',
    'Traditionally, this gentle shape is associated with people who build closeness slowly and keep it.',
  ],
  straight: [
    'A straight heart line is traditionally associated with a practical, loyal approach to relationships.',
    'Palmistry links this shape with people who show love through actions more than words.',
  ],
  unknown: [
    'The shape of this line was not clear enough to read in the usual way.',
  ],
};

/** Head line -> thinking style. */
export const HEAD_MEANING: Record<Direction, string[]> = {
  upward: [
    'A head line that lifts towards the fingers is traditionally read as a practical, goal-focused mind.',
    'Tradition connects this rise with quick decisions and an eye for what works.',
  ],
  level: [
    'A level head line is traditionally associated with clear, orderly thinking.',
    'Palmistry links this straight run with people who like facts, plans and simple explanations.',
  ],
  slightly_downward: [
    'A head line that dips gently is traditionally linked with imagination working alongside logic.',
    'This gentle slope is traditionally read as a mind that enjoys ideas, stories and possibilities.',
  ],
  downward: [
    'A clearly sloping head line is traditionally associated with a creative, inward-looking mind.',
    'Palmistry connects this slope with daydreaming, design thinking and original ideas.',
  ],
  unknown: [
    'The direction of this line was not clear enough to read in the usual way.',
  ],
};

/** Life line -> energy and daily rhythm. Never health, never lifespan. */
export const LIFE_MEANING: Record<Curvature, string[]> = {
  pronounced: [
    'A wide sweeping life line is traditionally linked with an active, outgoing rhythm of living.',
    'In palmistry, a broad curve here is associated with enthusiasm and a love of movement.',
  ],
  moderate: [
    'A curved life line is traditionally read as a balanced mix of activity and rest.',
    'Tradition links this shape with people who enjoy both busy days and quiet evenings.',
  ],
  gentle: [
    'A softer curve is traditionally associated with a calm, steady pace.',
    'Palmistry connects this shape with routine, patience and staying power.',
  ],
  straight: [
    'A straighter life line is traditionally linked with a careful, measured way of using energy.',
    'Tradition reads this as someone who protects their time and space.',
  ],
  unknown: ['This line was not clear enough to read in the usual way.'],
};

/** Fate line -> direction in work. */
export const FATE_MEANING: Record<Visibility, string[]> = {
  high: [
    'A clear fate line is traditionally associated with a strong sense of direction in work.',
    'Palmistry links a well marked fate line with people who commit to a path and stay on it.',
  ],
  medium: [
    'A partly visible fate line is traditionally read as a path that takes shape over time.',
    'Tradition connects this with someone who tries a few directions before settling.',
  ],
  low: [
    'A faint fate line is traditionally linked with independence rather than a fixed career track.',
    'In palmistry, a light fate line is associated with people who make their own way instead of following one plan.',
  ],
  not_detected: [
    'We could not trace a fate line in this photo. Traditionally, a hard to see fate line is read as a flexible, self-directed path, though here it may simply be the lighting.',
  ],
};

/** Sun line -> recognition and creative expression. */
export const SUN_MEANING: Record<Visibility, string[]> = {
  high: [
    'A visible sun line is traditionally associated with creativity that other people notice.',
    'Palmistry links this marking with enjoyment of craft, performance or making things.',
  ],
  medium: [
    'A partial sun line is traditionally read as talent that shows up in the right setting.',
    'Tradition connects this with creative energy that grows when it is encouraged.',
  ],
  low: [
    'A faint sun line is traditionally linked with quieter, private creativity.',
    'In tradition, a light sun line suggests satisfaction that comes from the work itself rather than applause.',
  ],
  not_detected: [
    'No sun line stood out in this photo. Many palms simply do not show one clearly.',
  ],
};

export const PALM_SHAPE_MEANING: Record<PalmShape, string[]> = {
  square: [
    'Traditional palmistry calls this a practical hand and links it with reliability and common sense.',
    'A square palm is traditionally read as grounded, organised and good with real world problems.',
  ],
  rectangular: [
    'A longer palm is traditionally associated with sensitivity and careful thought.',
    'Tradition links this shape with people who notice details others walk past.',
  ],
  oval: [
    'An oval palm is traditionally read as adaptable, sociable and easy to talk to.',
    'Palmistry associates this softer shape with flexibility and warmth.',
  ],
  broad: [
    'A broad palm is traditionally linked with energy, action and hands-on work.',
    'Tradition reads this width as confidence and a liking for getting started.',
  ],
  unknown: ['We could not measure the palm shape well enough to read it.'],
};

export const LUCKY_COLORS = [
  { name: 'Deep Indigo', hex: '#4c46c6' },
  { name: 'Warm Gold', hex: '#e0b44a' },
  { name: 'Sea Green', hex: '#2fa98a' },
  { name: 'Rose Quartz', hex: '#e08aa6' },
  { name: 'Midnight Blue', hex: '#2b4a8b' },
  { name: 'Amber', hex: '#e08a3c' },
  { name: 'Soft Violet', hex: '#a071e8' },
  { name: 'Copper', hex: '#c1714a' },
] as const;

export const LUCKY_DAYS = [
  'Monday',
  'Tuesday',
  'Wednesday',
  'Thursday',
  'Friday',
  'Saturday',
  'Sunday',
] as const;

export const POSITIVE_TRAITS = [
  'patience',
  'curiosity',
  'loyalty',
  'quiet courage',
  'humour',
  'generosity',
  'focus',
  'kindness',
  'honesty',
  'imagination',
] as const;

export const PERSONAL_THEMES = [
  'Steady growth',
  'Open doors',
  'Quiet confidence',
  'New beginnings',
  'Building slowly',
  'Trusting your judgement',
  'Making space for rest',
  'Following curiosity',
] as const;
