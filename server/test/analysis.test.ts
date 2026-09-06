import assert from 'node:assert/strict';
import test from 'node:test';
import { heuristicCvProvider } from '../src/analysis/heuristicCv.js';
import { mockProvider } from '../src/analysis/mock.js';
import { validateForAnalysis } from '../src/analysis/validation.js';
import { synthBackground, synthPalm } from './fixtures/synthPalm.js';

test('a clear palm photo is detected and its main lines are traced', async () => {
  const analysis = await heuristicCvProvider.analyzePalm(await synthPalm());

  assert.equal(analysis.simulated, false);
  assert.equal(analysis.palmDetected, true);
  assert.ok(analysis.confidence > 0.5, `confidence was ${analysis.confidence}`);
  assert.equal(analysis.heartLine.detectionMethod, 'cv-crease-trace');
  assert.notEqual(analysis.heartLine.visibility, 'not_detected');
  assert.notEqual(analysis.headLine.visibility, 'not_detected');
  assert.notEqual(analysis.lifeLine.visibility, 'not_detected');

  const validation = validateForAnalysis(analysis);
  assert.equal(validation.usable, true);
  assert.equal(validation.issues.length, 0);
});

test('detected lines produce a real overlay with in-range coordinates', async () => {
  const analysis = await heuristicCvProvider.analyzePalm(await synthPalm());

  assert.equal(analysis.overlay.available, true);
  assert.ok(analysis.overlay.segments.length >= 2);
  for (const segment of analysis.overlay.segments) {
    assert.ok(segment.points.length >= 2);
    for (const point of segment.points) {
      assert.ok(point.x >= 0 && point.x <= 1, `x out of range: ${point.x}`);
      assert.ok(point.y >= 0 && point.y <= 1, `y out of range: ${point.y}`);
    }
  }
});

test('an image with no hand is never reported as a palm', async () => {
  const analysis = await heuristicCvProvider.analyzePalm(await synthBackground());

  assert.equal(analysis.palmDetected, false);
  assert.equal(analysis.overlay.available, false);
  assert.equal(analysis.overlay.segments.length, 0);
  assert.equal(analysis.heartLine.visibility, 'not_detected');

  const validation = validateForAnalysis(analysis);
  assert.equal(validation.usable, false);
  assert.ok(validation.issues.some((issue) => issue.code === 'no_hand_detected'));
});

test('a hand without any creases is flagged instead of being read anyway', async () => {
  const analysis = await heuristicCvProvider.analyzePalm(await synthPalm({ creases: false }));
  const validation = validateForAnalysis(analysis);

  assert.equal(validation.usable, false);
  assert.ok(validation.issues.some((issue) => issue.code === 'possibly_back_of_hand'));
});

test('a dark photo is blocked with a lighting message', async () => {
  const analysis = await heuristicCvProvider.analyzePalm(await synthPalm({ exposure: 0.2 }));
  const validation = validateForAnalysis(analysis);

  assert.equal(validation.usable, false);
  assert.equal(validation.issues[0]?.code, 'image_too_dark');
});

test('a blurry photo is blocked with a focus message', async () => {
  const analysis = await heuristicCvProvider.analyzePalm(await synthPalm({ blur: 12 }));
  const validation = validateForAnalysis(analysis);

  assert.equal(validation.usable, false);
  assert.ok(validation.issues.some((issue) => issue.code === 'image_blurry'));
});

test('a second hand in the frame is reported', async () => {
  const analysis = await heuristicCvProvider.analyzePalm(await synthPalm({ secondHand: true, scale: 0.7 }));

  assert.ok(analysis.imageQuality.handRegions > 1);
  const validation = validateForAnalysis(analysis);
  assert.ok(validation.issues.some((issue) => issue.code === 'multiple_hands'));
});

test('the same photo always produces the same measurements', async () => {
  const image = await synthPalm();
  const first = await heuristicCvProvider.analyzePalm(image);
  const second = await heuristicCvProvider.analyzePalm(image);

  assert.equal(first.confidence, second.confidence);
  assert.equal(first.heartLine.visibility, second.heartLine.visibility);
  assert.equal(first.palmShape, second.palmShape);
});

test('demo mode is always labelled and never draws lines on the photo', async () => {
  const analysis = await mockProvider.analyzePalm(await synthPalm());

  assert.equal(analysis.simulated, true);
  assert.equal(analysis.overlay.available, false);
  assert.equal(analysis.heartLine.detectionMethod, 'simulated');
  assert.ok(analysis.limitations.some((note) => note.toLowerCase().includes('demo mode')));
});
