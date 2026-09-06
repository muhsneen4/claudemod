import { useState } from 'react';
import type { PalmAnalysis } from '../lib/types';
import { LINE_COLORS, LINE_LABELS, PalmIllustration } from './PalmIllustration';

interface Props {
  imageUrl: string;
  analysis: PalmAnalysis;
}

const LINE_MEANING: Record<string, string> = {
  heart:
    'The heart line sits nearest the fingers. Traditional palmistry links it to how someone shows affection and handles closeness.',
  head: 'The head line crosses the middle of the palm. Tradition connects it with thinking style, focus and decision making.',
  life: 'The life line curves around the base of the thumb. Traditionally it is read as energy and daily rhythm - never as lifespan.',
  fate: 'The fate line runs up the centre of the palm. Tradition associates it with direction in work and study.',
  sun: 'The sun line, also called the Apollo line, sits under the ring finger. It is traditionally linked with creativity and recognition.',
};

/**
 * Shows the user's own photo with the lines we actually traced.
 * If the pipeline did not measure any line with enough confidence, we show a
 * neutral diagram instead. We never draw invented lines on someone's hand.
 */
export function PalmViewer({ imageUrl, analysis }: Props) {
  const [active, setActive] = useState<string | null>(null);
  const overlay = analysis.overlay;

  if (!overlay.available || overlay.segments.length === 0) {
    return (
      <div className="stack">
        <div className="palm-viewer">
          <img src={imageUrl} alt="The palm photo you uploaded" loading="lazy" decoding="async" />
        </div>
        <div className="notice info">
          <span aria-hidden="true">🖐️</span>
          <div>
            <b>No lines are drawn on your photo</b>
            {overlay.reason ?? 'Nothing was detected clearly enough to mark.'} Here is a general diagram of where the
            main lines usually sit instead.
          </div>
        </div>
        <PalmIllustration highlight={active} className="fade-up" />
        <div className="line-legend">
          {Object.keys(LINE_LABELS).map((key) => (
            <button
              key={key}
              type="button"
              aria-pressed={active === key}
              onClick={() => setActive(active === key ? null : key)}
            >
              <span className="legend-dot" style={{ background: LINE_COLORS[key] }} />
              {LINE_LABELS[key]}
            </button>
          ))}
        </div>
        {active && <p className="muted">{LINE_MEANING[active]}</p>}
      </div>
    );
  }

  const toPath = (points: Array<{ x: number; y: number }>): string =>
    points
      .map((point, index) => `${index === 0 ? 'M' : 'L'} ${(point.x * 100).toFixed(2)} ${(point.y * 100).toFixed(2)}`)
      .join(' ');

  return (
    <div className="stack">
      <div className="palm-viewer">
        <img src={imageUrl} alt="Your palm photo with the detected lines marked" loading="lazy" decoding="async" />
        <svg viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true">
          {overlay.segments.map((segment) => (
            <g key={segment.line}>
              <path
                d={toPath(segment.points)}
                stroke={LINE_COLORS[segment.line]}
                opacity={active && active !== segment.line ? 0.2 : 0.95}
                strokeWidth={active === segment.line ? 3.4 : 2.4}
              />
              <path
                className="hit"
                d={toPath(segment.points)}
                onClick={() => setActive(active === segment.line ? null : segment.line)}
                style={{ pointerEvents: 'stroke' }}
              />
            </g>
          ))}
        </svg>
      </div>

      <div className="line-legend">
        {overlay.segments.map((segment) => (
          <button
            key={segment.line}
            type="button"
            aria-pressed={active === segment.line}
            onClick={() => setActive(active === segment.line ? null : segment.line)}
          >
            <span className="legend-dot" style={{ background: LINE_COLORS[segment.line] }} />
            {LINE_LABELS[segment.line]}
          </button>
        ))}
      </div>

      {active ? (
        <p className="muted">{LINE_MEANING[active]}</p>
      ) : (
        <p className="muted">
          Tap a line to read what traditional palmistry associates with it. Only lines the software actually traced are
          drawn here.
        </p>
      )}
    </div>
  );
}
