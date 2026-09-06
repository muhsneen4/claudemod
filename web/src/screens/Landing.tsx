import { PalmIllustration } from '../components/PalmIllustration';
import type { Capabilities } from '../lib/types';

interface Props {
  capabilities: Capabilities | null;
  onStart: () => void;
  onPrivacy: () => void;
}

const STEPS = [
  { title: 'Take or upload a palm photo', text: 'Open palm, good light, fingers pointing up.' },
  { title: 'We check the photo first', text: 'If it is too dark, blurry or cropped, we tell you how to fix it.' },
  { title: 'The image is analysed', text: 'Software looks for your hand, the palm area and the main creases.' },
  { title: 'You get your reading', text: 'Traditional palmistry meanings, written in plain English.' },
];

export function Landing({ capabilities, onStart, onPrivacy }: Props) {
  const demoMode = capabilities?.provider.simulated ?? false;

  return (
    <div className="stack-lg">
      <section className="hero fade-up">
        <span className="eyebrow">Destiny Palm AI</span>
        <h1>Your Palm. Your Story. Your Destiny.</h1>
        <p className="lede">
          Upload a clear picture of your palm and explore a personalised palmistry reading, built from what the software
          can actually see in your photo.
        </p>

        <div className="hero-visual" aria-hidden="true">
          <span className="halo" />
          <PalmIllustration />
        </div>

        <button type="button" className="btn btn-primary" onClick={onStart} style={{ maxWidth: 360, margin: '0 auto' }}>
          Read My Palm ✨
        </button>
        <p className="muted" style={{ marginTop: 12 }}>
          Takes less than 60 seconds.
        </p>
      </section>

      {demoMode && (
        <div className="notice warn fade-up-2" role="status">
          <span aria-hidden="true">⚠️</span>
          <div>
            <b>Demo mode is switched on</b>
            This server is set to return sample data. Nothing in your photo will be analysed until a real analysis
            provider is enabled.
          </div>
        </div>
      )}

      <section className="trust-row fade-up-2">
        <div className="trust">
          <span aria-hidden="true">🧠</span>
          <div>
            <b>Image analysis</b>
            <div className="muted">{capabilities?.provider.technique ?? 'Loading…'}</div>
          </div>
        </div>
        <div className="trust">
          <span aria-hidden="true">🔐</span>
          <div>
            <b>Short-lived images</b>
            <div className="muted">Held in server memory only, then deleted.</div>
          </div>
        </div>
        <div className="trust">
          <span aria-hidden="true">✨</span>
          <div>
            <b>Personalised reading</b>
            <div className="muted">Built from the features found in your photo.</div>
          </div>
        </div>
      </section>

      <section className="card fade-up-3">
        <h2 className="section-title">How it works</h2>
        <p className="muted" style={{ marginBottom: 16 }}>
          Four simple steps, no account needed.
        </p>
        <div className="steps">
          {STEPS.map((step, index) => (
            <div className="step" key={step.title}>
              <span className="step-number" aria-hidden="true">
                {index + 1}
              </span>
              <div>
                <b>{step.title}</b>
                <div className="muted">{step.text}</div>
              </div>
            </div>
          ))}
        </div>
      </section>

      <section className="card fade-up-3">
        <h2 className="section-title">What this app does and does not claim</h2>
        <div className="prose" style={{ marginTop: 10 }}>
          <p className="muted">
            Palmistry is a cultural tradition, not a science. This app measures real things in your photo - brightness,
            sharpness, the shape of your hand and the creases it can trace - and then applies traditional palmistry
            meanings to them. It cannot see your future, your health or your bank balance, and it never pretends to.
          </p>
          <ul>
            <li>Every feature comes with a confidence value, shown next to your reading.</li>
            <li>If a line cannot be traced, we say so instead of inventing one.</li>
            <li>Lines are only drawn on your photo when they were really detected.</li>
          </ul>
        </div>
        <p className="disclaimer" style={{ marginTop: 16 }}>
          Palm readings are provided for entertainment and cultural purposes only. They are not scientifically proven
          predictions and should not be used as a basis for medical, financial, legal, or other important life
          decisions.{' '}
          <button type="button" className="detail-toggle" onClick={onPrivacy}>
            Read the privacy notice
          </button>
        </p>
      </section>
    </div>
  );
}
