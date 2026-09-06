interface Props {
  /** Show the five classic palmistry lines on the diagram. */
  showLines?: boolean;
  /** Line to highlight, if any. */
  highlight?: string | null;
  className?: string;
}

export const LINE_COLORS: Record<string, string> = {
  heart: '#ff8fb1',
  head: '#52c7ff',
  life: '#63e6c0',
  fate: '#a084ff',
  sun: '#e9c67e',
};

export const LINE_LABELS: Record<string, string> = {
  heart: 'Heart line',
  head: 'Head line',
  life: 'Life line',
  fate: 'Fate line',
  sun: 'Sun line',
};

/** Paths follow the usual position of each line on an open right palm. */
const DIAGRAM_PATHS: Record<string, string> = {
  heart: 'M28 78 C 48 62, 92 60, 116 72',
  head: 'M24 100 C 52 96, 92 104, 112 116',
  life: 'M30 92 C 30 128, 46 156, 74 172',
  fate: 'M74 176 C 76 140, 74 108, 78 76',
  sun: 'M104 160 C 106 132, 104 110, 108 88',
};

/**
 * Hand drawn in SVG - no image file to download, scales perfectly and works as
 * both the landing visual and the educational diagram we show when nothing
 * could be detected in a real photo.
 */
export function PalmIllustration({ showLines = true, highlight = null, className }: Props) {
  return (
    <svg
      viewBox="0 0 150 210"
      className={className}
      role="img"
      aria-label="Illustration of an open palm with the main palmistry lines"
    >
      <defs>
        <linearGradient id="palm-skin" x1="0" y1="0" x2="1" y2="1">
          <stop offset="0%" stopColor="rgba(255,255,255,0.16)" />
          <stop offset="100%" stopColor="rgba(160,132,255,0.12)" />
        </linearGradient>
        <filter id="palm-glow" x="-40%" y="-40%" width="180%" height="180%">
          <feGaussianBlur stdDeviation="2.4" result="blur" />
          <feMerge>
            <feMergeNode in="blur" />
            <feMergeNode in="SourceGraphic" />
          </feMerge>
        </filter>
      </defs>

      <g fill="url(#palm-skin)" stroke="rgba(255,255,255,0.34)" strokeWidth="1.2" strokeLinejoin="round">
        <rect x="34" y="18" width="18" height="62" rx="9" />
        <rect x="56" y="8" width="18" height="72" rx="9" />
        <rect x="78" y="12" width="18" height="68" rx="9" />
        <rect x="100" y="26" width="17" height="54" rx="8.5" />
        <rect
          x="12"
          y="86"
          width="17"
          height="56"
          rx="8.5"
          transform="rotate(26 20 92)"
        />
        <rect x="26" y="62" width="94" height="122" rx="34" />
      </g>

      {showLines && (
        <g strokeWidth="2.4" strokeLinecap="round" fill="none" filter="url(#palm-glow)">
          {Object.entries(DIAGRAM_PATHS).map(([key, d]) => (
            <path
              key={key}
              d={d}
              stroke={LINE_COLORS[key]}
              opacity={highlight && highlight !== key ? 0.22 : 0.92}
            />
          ))}
        </g>
      )}
    </svg>
  );
}
