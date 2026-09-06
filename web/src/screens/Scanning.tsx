import { useEffect, useState } from 'react';

interface Props {
  previewUrl: string;
  /** True once the server has answered - the bar then completes. */
  finished: boolean;
}

const STAGES = [
  'Scanning palm structure',
  'Finding major lines',
  'Analyzing palm shape',
  'Mapping palm features',
  'Preparing your reading',
];

/**
 * The progress bar follows real elapsed time and stops at 92% until the server
 * actually answers, so it never claims work that has not happened yet.
 */
export function Scanning({ previewUrl, finished }: Props) {
  const [progress, setProgress] = useState(6);

  useEffect(() => {
    if (finished) {
      setProgress(100);
      return;
    }
    const timer = window.setInterval(() => {
      setProgress((current) => (current >= 92 ? 92 : current + Math.max(1, (95 - current) / 12)));
    }, 220);
    return () => window.clearInterval(timer);
  }, [finished]);

  const stageIndex = Math.min(STAGES.length - 1, Math.floor((progress / 100) * STAGES.length));

  return (
    <div className="stack-lg">
      <header style={{ textAlign: 'center' }} className="fade-up">
        <h1 style={{ fontSize: 'clamp(1.5rem, 6vw, 2rem)' }}>Reading your palm…</h1>
        <p className="muted" style={{ marginTop: 8 }}>
          This usually takes a few seconds.
        </p>
      </header>

      <div className="scan-stage" aria-hidden="true">
        <span className="scan-ring" />
        <div className="scan-image">
          <img src={previewUrl} alt="" />
          <span className="scan-line" />
        </div>
      </div>

      <section className="card fade-up-2">
        <div className="progress" role="progressbar" aria-valuenow={Math.round(progress)} aria-valuemin={0} aria-valuemax={100} aria-label="Analysis progress">
          <span style={{ width: `${progress}%` }} />
        </div>
        <p className="muted" style={{ marginTop: 10 }} aria-live="polite">
          {Math.round(progress)}% · {STAGES[stageIndex]}
        </p>

        <ul className="stage-list">
          {STAGES.map((stage, index) => (
            <li key={stage} className={index < stageIndex ? 'done' : index === stageIndex ? 'active' : ''}>
              <span className="stage-dot" aria-hidden="true" />
              {stage}
              {index < stageIndex ? ' ✓' : ''}
            </li>
          ))}
        </ul>
      </section>
    </div>
  );
}
