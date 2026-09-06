/**
 * The contract every palm-analysis provider must satisfy.
 *
 * The whole point of this file is swappability: today the default provider is a
 * classical computer-vision pipeline that runs on this server, tomorrow it can
 * be a trained model behind an HTTP endpoint. Everything downstream (validation
 * messages, the interpretation engine, the UI) only ever reads `PalmAnalysis`.
 *
 * Honesty rules baked into the type:
 *  - `simulated` is true whenever the numbers were invented rather than measured.
 *  - every feature carries its own `confidence` and `detectionMethod`.
 *  - `overlay.available` is false unless real pixel coordinates were measured,
 *    so the UI can never draw invented lines on a user's photo.
 */

export type Visibility = 'high' | 'medium' | 'low' | 'not_detected';
export type Length = 'short' | 'medium' | 'long' | 'unknown';
export type Curvature = 'straight' | 'gentle' | 'moderate' | 'pronounced' | 'unknown';
export type Direction = 'upward' | 'level' | 'slightly_downward' | 'downward' | 'unknown';
export type Prominence = 'low' | 'medium' | 'high' | 'unknown';
export type PalmShape = 'square' | 'rectangular' | 'oval' | 'broad' | 'unknown';
export type FingerProportions = 'short' | 'balanced' | 'long' | 'unknown';
export type HandSide = 'left' | 'right' | 'unknown';

export type DetectionMethod =
  /** measured from the image by the on-server classical CV pipeline */
  | 'cv-crease-trace'
  | 'cv-shape'
  /** returned by an external model */
  | 'model'
  /** invented for demo purposes - never presented as a real detection */
  | 'simulated';

export interface LineFeature {
  visibility: Visibility;
  /** 0-1. How sure the pipeline is about the visibility rating itself. */
  confidence: number;
  length: Length;
  curvature: Curvature;
  direction: Direction;
  detectionMethod: DetectionMethod;
  /** Human readable note about what was actually measured. */
  note?: string;
}

export interface MountFeature {
  prominence: Prominence;
  confidence: number;
  detectionMethod: DetectionMethod;
}

export interface ImageQuality {
  /** 0-1 average brightness of the whole frame. */
  brightness: number;
  /** 0-1 average brightness of the detected hand only, which is what matters. */
  handBrightness: number;
  /** 0-1 relative sharpness score (variance of Laplacian, normalised). */
  sharpness: number;
  /** 0-1 share of the frame covered by the detected hand. */
  handCoverage: number;
  /** How many separate hand-sized skin regions were found. */
  handRegions: number;
  /** 0-1 share of the hand outline that touches the image border. */
  borderContact: number;
  /** Number of finger-like protrusions found on the hand outline. */
  fingersFound: number;
  /** true when the crease texture looks like a palm rather than a hand back. */
  palmSideLikely: boolean;
  palmSideConfidence: number;
  /** Pixel size of the image that was analysed. */
  width: number;
  height: number;
}

export interface OverlayPoint {
  /** 0-1 coordinates, relative to the analysed image. */
  x: number;
  y: number;
}

export interface OverlaySegment {
  line: 'life' | 'head' | 'heart' | 'fate' | 'sun';
  /** Real, measured pixel path - normalised to 0-1. Never invented. */
  points: OverlayPoint[];
  strength: number;
}

export interface Overlay {
  /** false = the UI must not draw anything on top of the user's photo. */
  available: boolean;
  reason?: string;
  palmRegion?: { x: number; y: number; width: number; height: number };
  segments: OverlaySegment[];
}

export interface PalmAnalysis {
  /** Provider id, e.g. "heuristic-cv". Shown to the user for transparency. */
  provider: string;
  /** Short label of the technique, shown in the UI. */
  technique: string;
  /** true when nothing was really measured (demo mode). */
  simulated: boolean;

  palmDetected: boolean;
  /** 0-1 overall confidence that a usable palm was found. */
  confidence: number;

  handSide: HandSide;
  handSideConfidence: number;

  palmShape: PalmShape;
  palmShapeConfidence: number;

  fingerProportions: FingerProportions;

  lifeLine: LineFeature;
  headLine: LineFeature;
  heartLine: LineFeature;
  fateLine: LineFeature;
  sunLine: LineFeature;

  mounts: {
    venus: MountFeature;
    jupiter: MountFeature;
    saturn: MountFeature;
    apollo: MountFeature;
    mercury: MountFeature;
    luna: MountFeature;
  };

  imageQuality: ImageQuality;
  overlay: Overlay;

  /** Plain-language notes about the limits of this analysis. */
  limitations: string[];
  processingTimeMs: number;
}

export interface AnalyzeOptions {
  /** Content type of the buffer, used by providers that forward the file. */
  mimeType?: string;
  signal?: AbortSignal;
}

/**
 * The one function every provider implements.
 * Replace the implementation, keep the signature, and the rest of the app works.
 */
export type AnalyzePalm = (image: Buffer, options?: AnalyzeOptions) => Promise<PalmAnalysis>;

export interface PalmAnalysisProvider {
  id: string;
  technique: string;
  simulated: boolean;
  analyzePalm: AnalyzePalm;
}
