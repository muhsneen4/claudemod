import Anthropic from '@anthropic-ai/sdk';
import { config } from '../config.js';
import type { PalmAnalysis } from '../analysis/types.js';
import type { Reading } from '../interpretation/engine.js';

const SYSTEM_PROMPT = `You rewrite palm reading text for a palmistry entertainment app.

You will get a JSON object with sections. Rewrite the paragraphs of every section so they read naturally and warmly, in very simple English that anyone can understand.

Hard rules:
- Keep every factual detail the same. Do not invent lines, shapes or measurements that are not in the input.
- Never promise or predict a real world outcome: no guaranteed money, marriage, children, exam results, lottery wins, accidents, illness, recovery or lifespan.
- Never give medical, financial or legal advice, and never diagnose anything.
- Always frame palmistry as tradition: "traditional palmistry links this to", "this may suggest", "for entertainment".
- Keep the same number of paragraphs per section and stay close to the original length.
- Plain words, short sentences, no purple prose, no emojis.

Answer with JSON only, in this exact shape:
{"sections":[{"id":"love","headline":"...","paragraphs":["...","...","..."]}]}`;

/** Rewrite an existing reading with the Claude API. Server side only. */
export async function rewriteWithClaude(reading: Reading, analysis: PalmAnalysis): Promise<Reading> {
  const client = new Anthropic({
    apiKey: config.reading.anthropicApiKey,
    timeout: config.reading.anthropicTimeoutMs,
    maxRetries: 1,
  });

  const payload = {
    detection: {
      simulated: analysis.simulated,
      confidence: Number(analysis.confidence.toFixed(2)),
      palmShape: analysis.palmShape,
      fingerProportions: analysis.fingerProportions,
      lines: {
        life: analysis.lifeLine,
        head: analysis.headLine,
        heart: analysis.heartLine,
        fate: analysis.fateLine,
        sun: analysis.sunLine,
      },
    },
    sections: reading.sections.map((section) => ({
      id: section.id,
      title: section.title,
      headline: section.headline,
      paragraphs: section.paragraphs,
    })),
  };

  const response = await client.messages.create({
    model: config.reading.anthropicModel,
    max_tokens: 4000,
    system: SYSTEM_PROMPT,
    output_config: { effort: 'low' },
    messages: [{ role: 'user', content: JSON.stringify(payload) }],
  });

  if (response.stop_reason === 'refusal') {
    throw new Error('The language model declined to rewrite this reading.');
  }

  const text = response.content
    .filter((block): block is Anthropic.TextBlock => block.type === 'text')
    .map((block) => block.text)
    .join('')
    .trim();

  const parsed = parseSections(text);
  if (!parsed) throw new Error('The language model returned text we could not parse.');

  return {
    ...reading,
    sections: reading.sections.map((section) => {
      const replacement = parsed.get(section.id);
      if (!replacement) return section;
      return {
        ...section,
        headline: replacement.headline || section.headline,
        paragraphs:
          replacement.paragraphs.length === section.paragraphs.length
            ? replacement.paragraphs
            : section.paragraphs,
      };
    }),
  };
}

function parseSections(text: string): Map<string, { headline: string; paragraphs: string[] }> | null {
  const start = text.indexOf('{');
  const end = text.lastIndexOf('}');
  if (start === -1 || end <= start) return null;

  try {
    const data = JSON.parse(text.slice(start, end + 1)) as {
      sections?: Array<{ id?: string; headline?: string; paragraphs?: string[] }>;
    };
    if (!Array.isArray(data.sections)) return null;

    const map = new Map<string, { headline: string; paragraphs: string[] }>();
    for (const section of data.sections) {
      if (!section.id || !Array.isArray(section.paragraphs)) continue;
      map.set(section.id, {
        headline: typeof section.headline === 'string' ? section.headline : '',
        paragraphs: section.paragraphs.filter((paragraph): paragraph is string => typeof paragraph === 'string'),
      });
    }
    return map.size > 0 ? map : null;
  } catch {
    return null;
  }
}
