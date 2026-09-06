import sharp from 'sharp';

export interface SynthOptions {
  width?: number;
  height?: number;
  /** Draw the palm creases. Turn off to imitate the back of a hand. */
  creases?: boolean;
  /** 0-1 multiplier applied to every pixel, for dark photo tests. */
  exposure?: number;
  /** Gaussian blur radius, for blurry photo tests. */
  blur?: number;
  /** Draw a second hand next to the first. */
  secondHand?: boolean;
  /** Scale of the hand inside the frame. */
  scale?: number;
  skin?: string;
  background?: string;
}

/**
 * Build a synthetic palm photo with SVG.
 *
 * It is not a real hand, but it has the properties the pipeline looks for: a
 * skin coloured region, five finger runs at the top, a thumb bulge on one side
 * and darker crease curves in the right places. That makes the tests fast,
 * deterministic and free of any real person's biometric data.
 */
export async function synthPalm(options: SynthOptions = {}): Promise<Buffer> {
  const width = options.width ?? 600;
  const height = options.height ?? 800;
  const creases = options.creases ?? true;
  const scale = options.scale ?? 1;
  const skin = options.skin ?? '#d69a72';
  const background = options.background ?? '#1d2530';

  const hand = (offsetX: number): string => {
    const cx = width / 2 + offsetX;
    const s = scale;
    const palmTop = height * (0.5 - 0.1 * s);
    const palmBottom = height * (0.5 + 0.36 * s);
    const palmHalf = width * 0.22 * s;
    const fingerWidth = width * 0.085 * s;

    const finger = (index: number, length: number): string => {
      const x = cx - palmHalf * 0.85 + index * (palmHalf * 1.7) / 3.4;
      const top = palmTop - length;
      return `<rect x="${x}" y="${top}" width="${fingerWidth}" height="${length + 30}" rx="${fingerWidth / 2}" fill="${skin}"/>`;
    };

    const fingers = [0.28, 0.34, 0.32, 0.24]
      .map((ratio, index) => finger(index, height * ratio * s))
      .join('');

    const thumb = `<rect x="${cx - palmHalf - width * 0.1 * s}" y="${palmTop + height * 0.14 * s}" width="${fingerWidth * 1.2}" height="${height * 0.24 * s}" rx="${fingerWidth * 0.6}" fill="${skin}" transform="rotate(28 ${cx - palmHalf} ${palmTop + height * 0.2 * s})"/>`;

    const palm = `<rect x="${cx - palmHalf}" y="${palmTop - 10}" width="${palmHalf * 2}" height="${palmBottom - palmTop}" rx="${palmHalf * 0.35}" fill="${skin}"/>`;

    if (!creases) return `${fingers}${thumb}${palm}`;

    const shade = '#a86f4c';
    const line = (d: string, opacity: number, strokeWidth: number): string =>
      `<path d="${d}" fill="none" stroke="${shade}" stroke-opacity="${opacity}" stroke-width="${strokeWidth}" stroke-linecap="round"/>`;

    const left = cx - palmHalf;
    const right = cx + palmHalf;
    const span = palmBottom - palmTop;

    const creaseLines = [
      // heart line - across the upper palm
      line(
        `M ${left + palmHalf * 0.25} ${palmTop + span * 0.22} Q ${cx} ${palmTop + span * 0.1} ${right - palmHalf * 0.15} ${palmTop + span * 0.18}`,
        0.85,
        6,
      ),
      // head line - across the middle
      line(
        `M ${left + palmHalf * 0.2} ${palmTop + span * 0.4} Q ${cx} ${palmTop + span * 0.46} ${right - palmHalf * 0.3} ${palmTop + span * 0.5}`,
        0.8,
        6,
      ),
      // life line - arc around the thumb ball
      line(
        `M ${left + palmHalf * 0.35} ${palmTop + span * 0.32} Q ${left + palmHalf * 0.1} ${palmTop + span * 0.6} ${left + palmHalf * 0.5} ${palmTop + span * 0.9}`,
        0.8,
        6,
      ),
      // fate line - up the centre
      line(`M ${cx} ${palmTop + span * 0.92} L ${cx + palmHalf * 0.1} ${palmTop + span * 0.3}`, 0.7, 5),
      // sun line - under the ring finger
      line(`M ${cx + palmHalf * 0.55} ${palmTop + span * 0.62} L ${cx + palmHalf * 0.62} ${palmTop + span * 0.25}`, 0.6, 4),
    ].join('');

    return `${fingers}${thumb}${palm}${creaseLines}`;
  };

  const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="${width}" height="${height}">
    <rect width="${width}" height="${height}" fill="${background}"/>
    ${hand(options.secondHand ? -width * 0.22 : 0)}
    ${options.secondHand ? hand(width * 0.26) : ''}
  </svg>`;

  let pipeline = sharp(Buffer.from(svg)).png();
  if (options.blur) pipeline = pipeline.blur(options.blur);
  if (options.exposure !== undefined) pipeline = pipeline.linear(options.exposure, 0);

  return pipeline.toBuffer();
}

/** A photo with no hand at all. */
export async function synthBackground(): Promise<Buffer> {
  return sharp({
    create: { width: 600, height: 800, channels: 3, background: { r: 32, g: 44, b: 60 } },
  })
    .png()
    .toBuffer();
}
