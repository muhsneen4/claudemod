import { createHash } from 'node:crypto';
import type { LineFeature, PalmAnalysis } from '../analysis/types.js';
import {
  CURVATURE_WORDS,
  DIRECTION_WORDS,
  FATE_MEANING,
  HEAD_MEANING,
  HEART_MEANING,
  LENGTH_WORDS,
  LIFE_MEANING,
  LUCKY_COLORS,
  LUCKY_DAYS,
  PALM_SHAPE_MEANING,
  PALM_SHAPE_WORDS,
  PERSONAL_THEMES,
  POSITIVE_TRAITS,
  SUN_MEANING,
  VISIBILITY_WORDS,
} from './phrases.js';

export interface ReadingEvidence {
  feature: string;
  observed: string;
  confidence: number;
  measured: boolean;
}

export interface ReadingSection {
  id: 'love' | 'career' | 'wealth' | 'personality' | 'energy' | 'destiny';
  emoji: string;
  title: string;
  /**
   * 0-100 signal strength: how clearly this theme's markings showed up in the
   * photo. It is NOT a rating of the person or a prediction of any outcome.
   */
  signal: number;
  signalLabel: string;
  headline: string;
  paragraphs: string[];
  evidence: ReadingEvidence[];
}

export interface LuckyElements {
  number: number;
  day: string;
  color: { name: string; hex: string };
  trait: string;
  theme: string;
  disclaimer: string;
}

export interface Reading {
  sections: ReadingSection[];
  lucky: LuckyElements;
  summary: string;
  confidenceNote: string;
  disclaimer: string;
  /** Stable id derived from the measured features, useful for caching. */
  signature: string;
}

export const DISCLAIMER =
  'Palm readings are provided for entertainment and cultural purposes only. They are not scientifically proven predictions and should not be used as a basis for medical, financial, legal, or other important life decisions.';

/** Deterministic generator, so the same palm always gets the same wording. */
function seededPicker(signature: string): <T>(options: readonly T[], salt: number) => T {
  const digest = createHash('sha256').update(signature).digest();
  return <T>(options: readonly T[], salt: number): T => {
    const byte = digest[(salt * 7) % digest.length] ?? 0;
    const second = digest[(salt * 13 + 3) % digest.length] ?? 0;
    const index = (byte * 256 + second) % options.length;
    return options[index] as T;
  };
}

function featureSignature(analysis: PalmAnalysis): string {
  const parts = [
    analysis.provider,
    analysis.palmShape,
    analysis.fingerProportions,
    analysis.handSide,
    ...(['lifeLine', 'headLine', 'heartLine', 'fateLine', 'sunLine'] as const).map((key) => {
      const line = analysis[key];
      return `${key}:${line.visibility}:${line.length}:${line.curvature}:${line.direction}`;
    }),
    ...Object.entries(analysis.mounts).map(([name, mount]) => `${name}:${mount.prominence}`),
  ];
  return parts.join('|');
}

function signalFrom(...lines: LineFeature[]): number {
  const weights: Record<string, number> = { high: 1, medium: 0.7, low: 0.4, not_detected: 0.15 };
  const total = lines.reduce((sum, line) => sum + (weights[line.visibility] ?? 0.2) * (0.6 + line.confidence * 0.4), 0);
  return Math.round(Math.min(100, Math.max(12, (total / Math.max(1, lines.length)) * 100)));
}

function signalLabel(signal: number): string {
  if (signal >= 75) return 'Strong markings';
  if (signal >= 50) return 'Clear markings';
  if (signal >= 30) return 'Soft markings';
  return 'Faint markings';
}

function describeLine(name: string, line: LineFeature): string {
  if (line.visibility === 'not_detected') {
    return `Your ${name} was ${VISIBILITY_WORDS.not_detected}.`;
  }
  const pieces = [
    CURVATURE_WORDS[line.curvature],
    LENGTH_WORDS[line.length],
    DIRECTION_WORDS[line.direction],
  ].filter(Boolean);
  const tail = pieces.length > 0 ? `, ${pieces.join(', ')}` : '';
  return `Your ${name} came through ${VISIBILITY_WORDS[line.visibility]}${tail}.`;
}

function evidence(feature: string, line: LineFeature, simulated: boolean): ReadingEvidence {
  return {
    feature,
    observed:
      line.visibility === 'not_detected'
        ? 'not traced in this photo'
        : `${line.visibility} visibility, ${line.length} length, ${line.curvature} curve`,
    confidence: line.confidence,
    measured: !simulated && line.visibility !== 'not_detected',
  };
}

/**
 * Convert measured palm features into traditional palmistry interpretations.
 * Nothing here talks to a model - it is a pure function of the analysis, so the
 * same features always produce the same, testable reading.
 */
export function buildReading(analysis: PalmAnalysis): Reading {
  const signature = featureSignature(analysis);
  const pick = seededPicker(signature);
  const simulated = analysis.simulated;

  const love: ReadingSection = {
    id: 'love',
    emoji: '❤️',
    title: 'Love & Relationships',
    signal: signalFrom(analysis.heartLine),
    signalLabel: signalLabel(signalFrom(analysis.heartLine)),
    headline:
      analysis.heartLine.visibility === 'not_detected'
        ? 'Your heart line stayed hidden in this photo'
        : pick(
            [
              'Warmth that shows in small, steady ways',
              'A heart that leads with honesty',
              'Feelings you keep close, but never cold',
              'Affection built on trust, not noise',
            ],
            1,
          ),
    paragraphs: [
      describeLine('heart line', analysis.heartLine),
      pick(HEART_MEANING[analysis.heartLine.curvature], 2),
      analysis.heartLine.visibility === 'high'
        ? 'Because the line reads clearly, traditional palmistry would call this one of the stronger themes in your hand.'
        : analysis.heartLine.visibility === 'not_detected'
          ? 'A clearer, brighter photo of the upper palm would let us read this area properly.'
          : 'The line is readable but not bold, which tradition links to feelings that are shared with people who have earned it.',
    ],
    evidence: [evidence('Heart line', analysis.heartLine, simulated)],
  };

  const careerSignal = signalFrom(analysis.fateLine, analysis.headLine);
  const career: ReadingSection = {
    id: 'career',
    emoji: '💼',
    title: 'Career & Direction',
    signal: careerSignal,
    signalLabel: signalLabel(careerSignal),
    headline: pick(
      [
        'Work that rewards patience',
        'A path you shape yourself',
        'Steady progress over quick wins',
        'Skill built through practice',
      ],
      3,
    ),
    paragraphs: [
      `${describeLine('fate line', analysis.fateLine)} Your palm reads as ${PALM_SHAPE_WORDS[analysis.palmShape]}.`,
      pick(FATE_MEANING[analysis.fateLine.visibility], 4),
      pick(PALM_SHAPE_MEANING[analysis.palmShape], 5),
    ],
    evidence: [
      evidence('Fate line', analysis.fateLine, simulated),
      {
        feature: 'Palm shape',
        observed: analysis.palmShape,
        confidence: analysis.palmShapeConfidence,
        measured: !simulated && analysis.palmShape !== 'unknown',
      },
    ],
  };

  const wealthSignal = signalFrom(analysis.fateLine, analysis.sunLine);
  const wealth: ReadingSection = {
    id: 'wealth',
    emoji: '💰',
    title: 'Work & Resources',
    signal: wealthSignal,
    signalLabel: signalLabel(wealthSignal),
    headline: pick(
      [
        'Value you build with your own hands',
        'Resources that grow slowly and stay',
        'Effort that turns into something solid',
        'Practical choices, lasting results',
      ],
      6,
    ),
    paragraphs: [
      `${describeLine('sun line', analysis.sunLine)} The mount below your index finger reads as ${analysis.mounts.jupiter.prominence}, and the mount below your little finger as ${analysis.mounts.mercury.prominence}.`,
      pick(SUN_MEANING[analysis.sunLine.visibility], 7),
      'Traditional palmistry treats these markings as signs of how someone likes to work, not as any promise about money. Nothing in a photo of a hand can tell you what you will earn.',
    ],
    evidence: [
      evidence('Sun line', analysis.sunLine, simulated),
      {
        feature: 'Jupiter mount',
        observed: analysis.mounts.jupiter.prominence,
        confidence: analysis.mounts.jupiter.confidence,
        measured: !simulated && analysis.mounts.jupiter.prominence !== 'unknown',
      },
    ],
  };

  const personalitySignal = signalFrom(analysis.headLine);
  const personality: ReadingSection = {
    id: 'personality',
    emoji: '🧠',
    title: 'Personality & Mind',
    signal: personalitySignal,
    signalLabel: signalLabel(personalitySignal),
    headline: pick(
      [
        'A mind that likes to understand things properly',
        'Thoughtful first, then decisive',
        'Ideas you turn over before you share them',
        'Curiosity with a practical edge',
      ],
      8,
    ),
    paragraphs: [
      `${describeLine('head line', analysis.headLine)} Your fingers read as ${analysis.fingerProportions === 'unknown' ? 'hard to measure in this photo' : `${analysis.fingerProportions} compared with the palm`}.`,
      pick(HEAD_MEANING[analysis.headLine.direction], 9),
      analysis.fingerProportions === 'long'
        ? 'Longer fingers are traditionally linked with people who enjoy detail and take their time with decisions.'
        : analysis.fingerProportions === 'short'
          ? 'Shorter fingers are traditionally linked with quick decisions and a dislike of unnecessary steps.'
          : 'Balanced finger length is traditionally read as a mix of patience and decisiveness.',
    ],
    evidence: [
      evidence('Head line', analysis.headLine, simulated),
      {
        feature: 'Finger proportions',
        observed: analysis.fingerProportions,
        confidence: analysis.palmShapeConfidence,
        measured: !simulated && analysis.fingerProportions !== 'unknown',
      },
    ],
  };

  const energySignal = signalFrom(analysis.lifeLine);
  const energy: ReadingSection = {
    id: 'energy',
    emoji: '🌱',
    title: 'Life Energy',
    signal: energySignal,
    signalLabel: signalLabel(energySignal),
    headline: pick(
      [
        'Energy you spend on what matters',
        'A rhythm that suits you',
        'Steady stamina, quietly kept',
        'Movement, rest, and back again',
      ],
      10,
    ),
    paragraphs: [
      describeLine('life line', analysis.lifeLine),
      pick(LIFE_MEANING[analysis.lifeLine.curvature], 11),
      'Palmistry tradition reads the life line as a picture of vitality and daily rhythm. It says nothing about how long anyone will live, and it is not a health check of any kind.',
    ],
    evidence: [evidence('Life line', analysis.lifeLine, simulated)],
  };

  const destinySignal = signalFrom(
    analysis.heartLine,
    analysis.headLine,
    analysis.lifeLine,
    analysis.fateLine,
    analysis.sunLine,
  );
  const destiny: ReadingSection = {
    id: 'destiny',
    emoji: '⭐',
    title: 'Overall Destiny',
    signal: destinySignal,
    signalLabel: signalLabel(destinySignal),
    headline: pick(
      [
        'A hand that suggests balance in the making',
        'Your own pace, your own road',
        'Quiet strength with room to grow',
        'A story still being written',
      ],
      12,
    ),
    paragraphs: [
      buildOverallSentence(analysis),
      pick(
        [
          'Read together, traditional palmistry would describe this combination as someone who thinks things through, feels more than they show, and keeps going once they start.',
          'Taken as a whole, this mix is traditionally read as a practical person with a warm side that close friends see most.',
          'Traditionally, a hand with this balance of markings is linked to people who prefer steady progress and honest relationships to fast, loud change.',
        ],
        13,
      ),
      'For entertainment, your reading points towards patience and self-trust. What actually happens next is up to your own choices, not your palm.',
    ],
    evidence: (['heartLine', 'headLine', 'lifeLine', 'fateLine', 'sunLine'] as const).map((key) =>
      evidence(key.replace('Line', ' line'), analysis[key], simulated),
    ),
  };

  const lucky: LuckyElements = {
    number: (Number.parseInt(createHash('sha256').update(signature).digest('hex').slice(0, 4), 16) % 9) + 1,
    day: pick(LUCKY_DAYS, 14),
    color: pick(LUCKY_COLORS, 15),
    trait: pick(POSITIVE_TRAITS, 16),
    theme: pick(PERSONAL_THEMES, 17),
    disclaimer: 'Lucky elements are generated for fun. They carry no predictive meaning.',
  };

  return {
    sections: [love, career, wealth, personality, energy, destiny],
    lucky,
    summary: buildSummary(analysis, pick),
    confidenceNote: buildConfidenceNote(analysis),
    disclaimer: DISCLAIMER,
    signature: createHash('sha256').update(signature).digest('hex').slice(0, 16),
  };
}

function buildOverallSentence(analysis: PalmAnalysis): string {
  const traced = (['heartLine', 'headLine', 'lifeLine', 'fateLine', 'sunLine'] as const).filter(
    (key) => analysis[key].visibility !== 'not_detected',
  );
  const names = traced.map((key) => key.replace('Line', ' line')).join(', ');
  if (traced.length === 0) {
    return 'None of the main lines could be traced in this photo, so this summary stays general rather than personal.';
  }
  return `We could follow ${traced.length} of the five main lines in your photo: ${names}. Your palm reads as ${PALM_SHAPE_WORDS[analysis.palmShape]}.`;
}

function buildSummary(analysis: PalmAnalysis, pick: <T>(options: readonly T[], salt: number) => T): string {
  if (analysis.simulated) {
    return 'This is a demo reading. No analysis ran on your photo, so treat every line below as sample text.';
  }
  const strong = (['heartLine', 'headLine', 'lifeLine', 'fateLine', 'sunLine'] as const).filter(
    (key) => analysis[key].visibility === 'high',
  ).length;
  if (strong >= 3) {
    return pick(
      [
        'Your palm photographed well, and several main lines came through clearly. That gives this reading more to work with than usual.',
        'Most of the main lines showed up strongly in your photo, so the reading below leans on real detail rather than guesswork.',
      ],
      18,
    );
  }
  if (strong >= 1) {
    return 'Some lines came through clearly and others stayed soft, so parts of this reading are more detailed than others. That is normal.';
  }
  return 'Your lines came through faintly in this photo. The reading below is still based on what we measured, but a brighter, closer photo would give more detail.';
}

function buildConfidenceNote(analysis: PalmAnalysis): string {
  if (analysis.simulated) {
    return 'Demo mode: nothing in this reading was measured from your photo.';
  }
  const percent = Math.round(analysis.confidence * 100);
  return `Detection confidence for this photo: ${percent}%. This describes how clearly the software could see your palm, not how true the reading is.`;
}
