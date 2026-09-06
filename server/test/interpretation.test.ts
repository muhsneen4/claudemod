import assert from 'node:assert/strict';
import test from 'node:test';
import { heuristicCvProvider } from '../src/analysis/heuristicCv.js';
import { mockProvider } from '../src/analysis/mock.js';
import { buildReading } from '../src/interpretation/engine.js';
import { synthPalm } from './fixtures/synthPalm.js';

/** Claims the app must never make, in any reading. */
const FORBIDDEN = [
  'you will die',
  'guaranteed',
  'you will become rich',
  'you will be rich',
  'you will marry',
  'will get married on',
  'you will win',
  'lottery',
  'you will get pregnant',
  'you will have a baby',
  'you will get sick',
  'you will have an accident',
  'diagnos',
  '100% accurate',
  'scientifically proven',
  'you must',
  'invest in',
];

function collectText(reading: ReturnType<typeof buildReading>): string {
  return [
    reading.summary,
    reading.confidenceNote,
    ...reading.sections.flatMap((section) => [section.headline, ...section.paragraphs]),
  ]
    .join(' ')
    .toLowerCase();
}

test('a reading covers every category and stays inside the safe language rules', async () => {
  const analysis = await heuristicCvProvider.analyzePalm(await synthPalm());
  const reading = buildReading(analysis);

  assert.deepEqual(
    reading.sections.map((section) => section.id),
    ['love', 'career', 'wealth', 'personality', 'energy', 'destiny'],
  );

  const text = collectText(reading);
  for (const phrase of FORBIDDEN) {
    assert.ok(!text.includes(phrase), `reading contained a forbidden phrase: "${phrase}"`);
  }
  assert.ok(reading.disclaimer.includes('entertainment'));
});

test('readings stay safe across many different feature combinations', async () => {
  const images = await Promise.all([
    synthPalm(),
    synthPalm({ scale: 0.8 }),
    synthPalm({ skin: '#8a5a3b' }),
    synthPalm({ skin: '#f0c9a5' }),
    synthPalm({ secondHand: true, scale: 0.7 }),
  ]);

  for (const image of images) {
    for (const provider of [heuristicCvProvider, mockProvider]) {
      const reading = buildReading(await provider.analyzePalm(image));
      const text = collectText(reading);
      for (const phrase of FORBIDDEN) {
        assert.ok(!text.includes(phrase), `"${phrase}" appeared in a ${provider.id} reading`);
      }
      for (const section of reading.sections) {
        assert.ok(section.paragraphs.length >= 2, 'every section needs real content');
        assert.ok(section.signal >= 0 && section.signal <= 100);
      }
    }
  }
});

test('the reading is built only from measured features, and repeats exactly', async () => {
  const analysis = await heuristicCvProvider.analyzePalm(await synthPalm());
  const first = buildReading(analysis);
  const second = buildReading(analysis);

  assert.equal(first.signature, second.signature);
  assert.deepEqual(first.sections, second.sections);
  assert.deepEqual(first.lucky, second.lucky);
});

test('different palms get different readings', async () => {
  const wide = await heuristicCvProvider.analyzePalm(await synthPalm({ width: 800, height: 700 }));
  const tall = await heuristicCvProvider.analyzePalm(await synthPalm({ width: 500, height: 900 }));

  const a = buildReading(wide);
  const b = buildReading(tall);
  assert.notEqual(a.signature, b.signature);
});

test('demo readings say plainly that nothing was measured', async () => {
  const reading = buildReading(await mockProvider.analyzePalm(await synthPalm()));

  assert.ok(reading.summary.toLowerCase().includes('demo'));
  assert.ok(reading.confidenceNote.toLowerCase().includes('demo'));
  for (const section of reading.sections) {
    for (const item of section.evidence) {
      assert.equal(item.measured, false);
    }
  }
});

test('lucky elements are labelled as entertainment', async () => {
  const reading = buildReading(await heuristicCvProvider.analyzePalm(await synthPalm()));

  assert.ok(reading.lucky.number >= 1 && reading.lucky.number <= 9);
  assert.ok(reading.lucky.disclaimer.toLowerCase().includes('fun'));
});
