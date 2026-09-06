import type { PalmAnalysis } from './types.js';

export type IssueSeverity = 'blocking' | 'warning';

export interface ImageIssue {
  code:
    | 'no_hand_detected'
    | 'multiple_hands'
    | 'palm_too_small'
    | 'image_too_dark'
    | 'image_too_bright'
    | 'image_blurry'
    | 'fingers_cropped'
    | 'fingers_not_visible'
    | 'hand_rotated'
    | 'possibly_back_of_hand'
    | 'low_detail';
  severity: IssueSeverity;
  title: string;
  advice: string;
}

export interface ValidationResult {
  usable: boolean;
  issues: ImageIssue[];
  /** 0-1 summary of how good this photo is for a reading. */
  qualityScore: number;
}

/**
 * Turn raw measurements into friendly, actionable feedback.
 *
 * Blocking issues stop the reading; warnings let the user carry on, because a
 * slightly imperfect photo still gives a reading, just with lower confidence.
 * Only one root cause is reported where several symptoms share it - telling
 * someone their photo is blurry AND has no hand in it is confusing when the
 * blur is the reason we could not find the hand.
 */
export function validateForAnalysis(analysis: PalmAnalysis): ValidationResult {
  if (analysis.simulated) {
    return { usable: true, issues: [], qualityScore: 0.5 };
  }

  const issues: ImageIssue[] = [];
  const quality = analysis.imageQuality;
  const hasSkinRegion = quality.handCoverage >= 0.05;

  if (!hasSkinRegion) {
    issues.push({
      code: 'no_hand_detected',
      severity: 'blocking',
      title: 'We could not see a hand',
      advice: 'Hold your open palm towards the camera so it fills most of the frame, then take the photo again.',
    });
    return { usable: false, issues, qualityScore: scoreOf(analysis) };
  }

  if (quality.handCoverage < 0.1) {
    issues.push({
      code: 'palm_too_small',
      severity: 'blocking',
      title: 'Your palm is too small in the picture',
      advice: 'Move your hand closer to the camera until the palm fills most of the frame.',
    });
  } else if (quality.handCoverage < 0.16) {
    issues.push({
      code: 'palm_too_small',
      severity: 'warning',
      title: 'Your palm looks small in the frame',
      advice: 'Moving your hand a little closer will bring out more detail.',
    });
  }

  if (quality.handBrightness < 0.18) {
    issues.push({
      code: 'image_too_dark',
      severity: 'blocking',
      title: 'The photo is too dark',
      advice: 'Move next to a window or turn on a light, then take the photo again.',
    });
  } else if (quality.handBrightness > 0.93) {
    issues.push({
      code: 'image_too_bright',
      severity: 'warning',
      title: 'The photo is very bright',
      advice: 'Step out of direct light or turn the flash off so the palm lines stay visible.',
    });
  }

  if (quality.sharpness < 0.14) {
    issues.push({
      code: 'image_blurry',
      severity: 'blocking',
      title: 'The photo looks blurry',
      advice: 'Rest your elbow on something, tap the screen to focus, then take the photo again.',
    });
  } else if (quality.sharpness < 0.28) {
    issues.push({
      code: 'low_detail',
      severity: 'warning',
      title: 'Fine palm lines are hard to see',
      advice: 'A sharper, closer photo will give a more detailed reading.',
    });
  }

  if (quality.handRegions > 1) {
    issues.push({
      code: 'multiple_hands',
      severity: 'warning',
      title: 'More than one hand may be in the photo',
      advice: 'Photograph one palm at a time so the reading stays about a single hand.',
    });
  }

  if (quality.borderContact > 0.35) {
    issues.push({
      code: 'fingers_cropped',
      severity: 'warning',
      title: 'Part of your hand is cut off',
      advice: 'Pull the camera back a little so the whole hand fits inside the picture.',
    });
  }

  const noLinesAtAll =
    analysis.lifeLine.visibility === 'not_detected' &&
    analysis.headLine.visibility === 'not_detected' &&
    analysis.heartLine.visibility === 'not_detected';

  if (!analysis.palmDetected && quality.fingersFound >= 3 && noLinesAtAll) {
    // A clear hand with no creases at all is the strongest back-of-hand signal.
    issues.push({
      code: 'possibly_back_of_hand',
      severity: 'blocking',
      title: 'We could not find any palm lines',
      advice: 'Turn your hand over so the inside of your palm faces the camera, then try again.',
    });
  } else if (analysis.palmDetected && !quality.palmSideLikely) {
    issues.push({
      code: 'possibly_back_of_hand',
      severity: 'warning',
      title: 'This may be the back of your hand',
      advice: 'We found very few creases. If you photographed the back of your hand, turn it over and try again.',
    });
  }

  if (analysis.palmDetected && quality.fingersFound > 0 && quality.fingersFound < 4) {
    issues.push({
      code: 'fingers_not_visible',
      severity: 'warning',
      title: 'Not all fingers are visible',
      advice: 'Open your fingers slightly and keep the whole hand inside the frame.',
    });
  }

  if (analysis.palmDetected && analysis.palmShape === 'unknown') {
    issues.push({
      code: 'hand_rotated',
      severity: 'warning',
      title: 'Your hand looks tilted or rotated',
      advice: 'Point your fingers towards the top of the picture for the most accurate zones.',
    });
  }

  // Only fall back to the generic message when nothing more specific explains it.
  if (!analysis.palmDetected && !issues.some((issue) => issue.severity === 'blocking')) {
    issues.push({
      code: 'no_hand_detected',
      severity: 'blocking',
      title: 'We could not read your palm clearly',
      advice: 'Take another photo with your open palm facing the camera in good, even light.',
    });
  }

  return {
    usable: !issues.some((issue) => issue.severity === 'blocking'),
    issues,
    qualityScore: scoreOf(analysis),
  };
}

function scoreOf(analysis: PalmAnalysis): number {
  const quality = analysis.imageQuality;
  return Math.max(
    0,
    Math.min(
      1,
      0.25 * Math.min(1, quality.handCoverage * 3) +
        0.3 * quality.sharpness +
        0.2 * Math.max(0, 1 - Math.abs(quality.handBrightness - 0.55) * 2) +
        0.25 * analysis.confidence,
    ),
  );
}
