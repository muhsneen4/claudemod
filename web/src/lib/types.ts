/** Mirrors the server response shapes. Kept small and explicit on purpose. */

export type Visibility = 'high' | 'medium' | 'low' | 'not_detected';

export interface LineFeature {
  visibility: Visibility;
  confidence: number;
  length: string;
  curvature: string;
  direction: string;
  detectionMethod: string;
  note?: string;
}

export interface OverlaySegment {
  line: 'life' | 'head' | 'heart' | 'fate' | 'sun';
  points: Array<{ x: number; y: number }>;
  strength: number;
}

export interface PalmAnalysis {
  provider: string;
  technique: string;
  simulated: boolean;
  palmDetected: boolean;
  confidence: number;
  handSide: string;
  handSideConfidence: number;
  palmShape: string;
  palmShapeConfidence: number;
  fingerProportions: string;
  lifeLine: LineFeature;
  headLine: LineFeature;
  heartLine: LineFeature;
  fateLine: LineFeature;
  sunLine: LineFeature;
  mounts: Record<string, { prominence: string; confidence: number }>;
  imageQuality: {
    brightness: number;
    handBrightness: number;
    sharpness: number;
    handCoverage: number;
    handRegions: number;
    borderContact: number;
    fingersFound: number;
    palmSideLikely: boolean;
    palmSideConfidence: number;
    width: number;
    height: number;
  };
  overlay: {
    available: boolean;
    reason?: string;
    palmRegion?: { x: number; y: number; width: number; height: number };
    segments: OverlaySegment[];
  };
  limitations: string[];
  processingTimeMs: number;
}

export interface ImageIssue {
  code: string;
  severity: 'blocking' | 'warning';
  title: string;
  advice: string;
}

export interface ValidationResult {
  usable: boolean;
  issues: ImageIssue[];
  qualityScore: number;
}

export interface ReadingSection {
  id: 'love' | 'career' | 'wealth' | 'personality' | 'energy' | 'destiny';
  emoji: string;
  title: string;
  signal: number;
  signalLabel: string;
  headline: string;
  paragraphs: string[];
  evidence: Array<{ feature: string; observed: string; confidence: number; measured: boolean }>;
}

export interface Reading {
  sections: ReadingSection[];
  lucky: {
    number: number;
    day: string;
    color: { name: string; hex: string };
    trait: string;
    theme: string;
    disclaimer: string;
  };
  summary: string;
  confidenceNote: string;
  disclaimer: string;
  signature: string;
}

export interface AnalyzeMeta {
  analysisProvider: string;
  analysisTechnique: string;
  analysisIsSimulated: boolean;
  textProvider: string;
  textFallbackReason?: string;
  totalTimeMs: number;
  imageRetentionMs: number;
  disclaimer: string;
}

export type AnalyzeResponse =
  | {
      status: 'ok';
      readingId: string;
      image: { id: string; url: string; expiresAt: string; storage: string };
      validation: ValidationResult;
      analysis: PalmAnalysis;
      reading: Reading;
      meta: AnalyzeMeta;
    }
  | {
      status: 'unusable';
      validation: ValidationResult;
      analysis: PalmAnalysis;
      meta: AnalyzeMeta;
    };

export interface Capabilities {
  analysisProvider: string;
  analysisIsSimulated: boolean;
  readingTextProvider: string;
  maxUploadBytes: number;
  acceptedTypes: string[];
  imageRetentionMs: number;
  provider: { id: string; technique: string; simulated: boolean };
  disclaimer: string;
}
