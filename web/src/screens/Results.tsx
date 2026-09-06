import { useState } from 'react';
import { Notice } from '../components/Notice';
import { PalmViewer } from '../components/PalmViewer';
import type { AnalyzeMeta, PalmAnalysis, Reading, ReadingSection, ValidationResult } from '../lib/types';

interface Props {
  imageUrl: string;
  analysis: PalmAnalysis;
  reading: Reading;
  validation: ValidationResult;
  meta: AnalyzeMeta;
  imageDeleted: boolean;
  onDeleteImage: () => void;
  onRestart: () => void;
}

function ReadingCard({ section }: { section: ReadingSection }) {
  const [showEvidence, setShowEvidence] = useState(false);

  return (
    <article className="card reading-card fade-up">
      <h3>
        <span aria-hidden="true">{section.emoji}</span>
        {section.title}
      </h3>
      <p className="headline">{section.headline}</p>
      {section.paragraphs.map((paragraph) => (
        <p key={paragraph.slice(0, 40)}>{paragraph}</p>
      ))}

      <div className="signal">
        <span>{section.signalLabel}</span>
        <span className="bar">
          <i style={{ width: `${section.signal}%` }} />
        </span>
        <span>{section.signal}</span>
      </div>

      <button type="button" className="detail-toggle" onClick={() => setShowEvidence(!showEvidence)}>
        {showEvidence ? 'Hide what this is based on' : 'What is this based on?'}
      </button>

      {showEvidence && (
        <div className="evidence">
          {section.evidence.map((item) => (
            <div className="evidence-row" key={item.feature}>
              <b>{item.feature}</b>
              <span>
                {item.observed}
                <span className={item.measured ? 'chip measured' : 'chip estimated'} style={{ marginLeft: 8 }}>
                  {item.measured ? `measured · ${Math.round(item.confidence * 100)}%` : 'not measured'}
                </span>
              </span>
            </div>
          ))}
        </div>
      )}
    </article>
  );
}

export function Results({
  imageUrl,
  analysis,
  reading,
  validation,
  meta,
  imageDeleted,
  onDeleteImage,
  onRestart,
}: Props) {
  const [showRaw, setShowRaw] = useState(false);
  const warnings = validation.issues.filter((issue) => issue.severity === 'warning');

  return (
    <div className="stack-lg">
      <header className="result-hero fade-up">
        <h1>
          Your Palm Reading{'\u00a0'}Is{'\u00a0'}Ready{'\u00a0'}✨
        </h1>
        <p className="muted">{reading.summary}</p>
      </header>

      {meta.analysisIsSimulated && (
        <Notice tone="warn" icon="⚠️" title="Demo data - nothing was detected">
          This server is running in demo mode. The reading below is sample text and is not based on your photo.
        </Notice>
      )}

      <section className="card fade-up">
        {imageDeleted ? (
          <Notice tone="info" icon="🗑️" title="Your palm image has been deleted">
            The photo is gone from the server. Your reading text is still here until you close or refresh the page.
          </Notice>
        ) : (
          <PalmViewer imageUrl={imageUrl} analysis={analysis} />
        )}

        <div className="evidence" style={{ marginTop: 16 }}>
          <div className="evidence-row">
            <b>Detection confidence</b>
            <span>{Math.round(analysis.confidence * 100)}%</span>
          </div>
          <div className="evidence-row">
            <b>Photo quality</b>
            <span>{Math.round(validation.qualityScore * 100)}%</span>
          </div>
          <div className="evidence-row">
            <b>Hand in the photo</b>
            <span>
              {analysis.handSide === 'unknown'
                ? 'could not tell'
                : `${analysis.handSide} hand · ${Math.round(analysis.handSideConfidence * 100)}% sure`}
            </span>
          </div>
          <div className="evidence-row">
            <b>Palm shape</b>
            <span>{analysis.palmShape}</span>
          </div>
          <div className="evidence-row">
            <b>Analysed by</b>
            <span>{meta.analysisTechnique}</span>
          </div>
        </div>
        <p className="muted" style={{ marginTop: 12 }}>
          {reading.confidenceNote}
        </p>
      </section>

      {warnings.length > 0 && (
        <Notice tone="warn" icon="💡" title="Things that could make your next reading better">
          <ul style={{ margin: '6px 0 0', paddingLeft: 18 }}>
            {warnings.map((issue) => (
              <li key={issue.code}>
                <b style={{ display: 'inline' }}>{issue.title}.</b> {issue.advice}
              </li>
            ))}
          </ul>
        </Notice>
      )}

      <div className="reading-grid">
        {reading.sections.map((section) => (
          <ReadingCard key={section.id} section={section} />
        ))}
      </div>

      <section className="card fade-up">
        <h3 className="section-title">🌟 Lucky Elements</h3>
        <p className="muted">{reading.lucky.disclaimer}</p>
        <div className="lucky-grid">
          <div className="lucky-item">
            <span>Lucky number</span>
            <b>{reading.lucky.number}</b>
          </div>
          <div className="lucky-item">
            <span>Lucky day</span>
            <b>{reading.lucky.day}</b>
          </div>
          <div className="lucky-item">
            <span>Lucky colour</span>
            <b>
              <i className="swatch" style={{ background: reading.lucky.color.hex }} aria-hidden="true" />
              {reading.lucky.color.name}
            </b>
          </div>
          <div className="lucky-item">
            <span>Positive trait</span>
            <b>{reading.lucky.trait}</b>
          </div>
          <div className="lucky-item">
            <span>Personal theme</span>
            <b>{reading.lucky.theme}</b>
          </div>
        </div>
      </section>

      <section className="card fade-up">
        <h3 className="section-title">Your photo and your privacy</h3>
        <p className="muted" style={{ marginTop: 8 }}>
          Your palm photo is held in the server's memory only, never written to disk, and dropped automatically after{' '}
          {Math.round(meta.imageRetentionMs / 60000)} minutes. You can remove it right now.
        </p>
        <div className="btn-row two" style={{ marginTop: 16 }}>
          <button type="button" className="btn btn-secondary" onClick={onDeleteImage} disabled={imageDeleted}>
            {imageDeleted ? 'Image deleted ✓' : '🗑️ Delete my palm image'}
          </button>
          <button type="button" className="btn btn-primary" onClick={onRestart}>
            Read Another Palm
          </button>
        </div>
      </section>

      <section className="card fade-up">
        <button type="button" className="detail-toggle" onClick={() => setShowRaw(!showRaw)}>
          {showRaw ? 'Hide the technical details' : 'Show the technical details'}
        </button>
        {showRaw && (
          <div className="stack" style={{ marginTop: 14 }}>
            {analysis.limitations.length > 0 && (
              <div>
                <b style={{ fontSize: 14 }}>What this analysis cannot do</b>
                <ul className="prose" style={{ paddingLeft: 18, marginTop: 6 }}>
                  {analysis.limitations.map((note) => (
                    <li key={note} className="muted">
                      {note}
                    </li>
                  ))}
                </ul>
              </div>
            )}
            <div className="evidence" style={{ borderTop: 0, paddingTop: 0 }}>
              {(['heartLine', 'headLine', 'lifeLine', 'fateLine', 'sunLine'] as const).map((key) => (
                <div className="evidence-row" key={key}>
                  <b>{key.replace('Line', ' line')}</b>
                  <span>
                    {analysis[key].visibility} · {analysis[key].note ?? '—'}
                  </span>
                </div>
              ))}
              <div className="evidence-row">
                <b>Processing time</b>
                <span>{meta.totalTimeMs} ms</span>
              </div>
              <div className="evidence-row">
                <b>Reading text written by</b>
                <span>{meta.textProvider === 'anthropic' ? 'Claude, from the measured features' : 'the built-in interpretation engine'}</span>
              </div>
            </div>
          </div>
        )}
        <p className="disclaimer">{reading.disclaimer}</p>
      </section>
    </div>
  );
}
