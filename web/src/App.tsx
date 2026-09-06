import { Suspense, lazy, useCallback, useEffect, useRef, useState } from 'react';
import { Landing } from './screens/Landing';
import { Capture } from './screens/Capture';
import { Scanning } from './screens/Scanning';
import { Notice } from './components/Notice';
import { ApiRequestError, analyzePalm, deletePalmImage, fetchCapabilities } from './lib/api';
import type { PreparedImage } from './lib/image';
import type { AnalyzeResponse, Capabilities, ValidationResult } from './lib/types';

// The results view is the heaviest screen, so it only loads once it is needed.
const Results = lazy(() => import('./screens/Results').then((module) => ({ default: module.Results })));
const Privacy = lazy(() => import('./screens/Privacy').then((module) => ({ default: module.Privacy })));

type View = 'landing' | 'capture' | 'scanning' | 'results' | 'rejected' | 'privacy';

interface SuccessState {
  response: Extract<AnalyzeResponse, { status: 'ok' }>;
  localPreviewUrl: string;
}

export default function App() {
  const [view, setView] = useState<View>('landing');
  const [capabilities, setCapabilities] = useState<Capabilities | null>(null);
  const [preview, setPreview] = useState<PreparedImage | null>(null);
  const [result, setResult] = useState<SuccessState | null>(null);
  const [rejection, setRejection] = useState<ValidationResult | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [imageDeleted, setImageDeleted] = useState(false);
  const [finished, setFinished] = useState(false);
  const abortRef = useRef<AbortController | null>(null);

  useEffect(() => {
    const controller = new AbortController();
    fetchCapabilities(controller.signal)
      .then(setCapabilities)
      .catch(() => {
        // The app still works without this - it only removes a few labels.
      });
    return () => controller.abort();
  }, []);

  useEffect(() => {
    window.scrollTo({ top: 0, behavior: 'instant' as ScrollBehavior });
  }, [view]);

  const startAnalysis = useCallback(async (image: PreparedImage) => {
    setPreview(image);
    setError(null);
    setRejection(null);
    setFinished(false);
    setImageDeleted(false);
    setView('scanning');

    abortRef.current?.abort();
    const controller = new AbortController();
    abortRef.current = controller;

    // A slow network should not leave the user staring at a spinner forever.
    const timeout = window.setTimeout(() => controller.abort(), 45_000);

    try {
      const response = await analyzePalm(image.blob, controller.signal);
      setFinished(true);
      // Let the progress bar reach 100% before switching screens.
      await new Promise((resolve) => setTimeout(resolve, 380));

      if (response.status === 'unusable') {
        setRejection(response.validation);
        setView('rejected');
        return;
      }
      setResult({ response, localPreviewUrl: image.previewUrl });
      setView('results');
    } catch (cause) {
      setFinished(true);
      if (controller.signal.aborted) {
        setError('That took too long. Please check your connection and try again.');
      } else if (cause instanceof ApiRequestError) {
        setError(cause.message);
      } else {
        setError('We could not reach the palm reading service. Please try again in a moment.');
      }
      setView('rejected');
    } finally {
      window.clearTimeout(timeout);
    }
  }, []);

  const restart = useCallback(() => {
    if (preview) URL.revokeObjectURL(preview.previewUrl);
    setPreview(null);
    setResult(null);
    setRejection(null);
    setError(null);
    setImageDeleted(false);
    setView('capture');
  }, [preview]);

  const removeImage = useCallback(async () => {
    if (!result) return;
    const deleted = await deletePalmImage(result.response.image.url);
    setImageDeleted(deleted);
  }, [result]);

  const demo = capabilities?.provider.simulated ?? false;

  return (
    <div className="shell">
      <div className="aurora" aria-hidden="true">
        <span />
        <span />
        <span />
      </div>

      <a className="skip-link" href="#main">
        Skip to content
      </a>

      <header className="topbar">
        <div className="brand">
          <span className="brand-mark" aria-hidden="true">
            🔮
          </span>
          Destiny Palm AI
        </div>
        <span className={demo ? 'mode-pill demo' : 'mode-pill'}>
          {demo ? 'Demo data' : capabilities ? 'Live analysis' : 'Loading'}
        </span>
      </header>

      <main className="page" id="main">
        {view === 'landing' && (
          <Landing
            capabilities={capabilities}
            onStart={() => setView('capture')}
            onPrivacy={() => setView('privacy')}
          />
        )}

        {view === 'capture' && (
          <Capture capabilities={capabilities} onAnalyze={(image) => void startAnalysis(image)} onBack={() => setView('landing')} />
        )}

        {view === 'scanning' && preview && <Scanning previewUrl={preview.previewUrl} finished={finished} />}

        {view === 'rejected' && (
          <div className="stack-lg">
            <header className="fade-up">
              <h1 style={{ fontSize: 'clamp(1.5rem, 6vw, 2rem)' }}>Let's try that photo again</h1>
            </header>

            {error && (
              <Notice tone="danger" icon="⚠️" title="Something went wrong">
                {error}
              </Notice>
            )}

            {rejection?.issues.map((issue) => (
              <Notice
                key={issue.code}
                tone={issue.severity === 'blocking' ? 'danger' : 'warn'}
                icon={issue.severity === 'blocking' ? '📸' : '💡'}
                title={issue.title}
              >
                {issue.advice}
              </Notice>
            ))}

            {!error && !rejection && (
              <Notice tone="warn" icon="📸" title="We could not read that photo">
                Please try again with your open palm facing the camera in good light.
              </Notice>
            )}

            <div className="btn-row two">
              <button type="button" className="btn btn-primary" onClick={restart}>
                Try another photo
              </button>
              <button type="button" className="btn btn-secondary" onClick={() => setView('landing')}>
                Back to home
              </button>
            </div>
          </div>
        )}

        <Suspense fallback={<div className="skeleton" style={{ minHeight: 320 }} aria-label="Loading" />}>
          {view === 'results' && result && (
            <Results
              imageUrl={result.response.image.url}
              analysis={result.response.analysis}
              reading={result.response.reading}
              validation={result.response.validation}
              meta={result.response.meta}
              imageDeleted={imageDeleted}
              onDeleteImage={() => void removeImage()}
              onRestart={restart}
            />
          )}

          {view === 'privacy' && (
            <Privacy
              imageRetentionMinutes={Math.round((capabilities?.imageRetentionMs ?? 900_000) / 60_000)}
              canDelete={Boolean(result) && !imageDeleted}
              imageDeleted={imageDeleted}
              onDeleteImage={() => void removeImage()}
            />
          )}
        </Suspense>
      </main>

      <nav className="bottom-nav" aria-label="Main">
        <button
          type="button"
          aria-current={view === 'landing' ? 'page' : undefined}
          onClick={() => setView('landing')}
        >
          <span className="icon" aria-hidden="true">
            🏠
          </span>
          Home
        </button>
        <button
          type="button"
          aria-current={view === 'capture' || view === 'scanning' ? 'page' : undefined}
          onClick={() => setView('capture')}
        >
          <span className="icon" aria-hidden="true">
            🖐️
          </span>
          Read palm
        </button>
        <button
          type="button"
          aria-current={view === 'results' ? 'page' : undefined}
          onClick={() => result && setView('results')}
          disabled={!result}
        >
          <span className="icon" aria-hidden="true">
            ✨
          </span>
          Reading
        </button>
        <button
          type="button"
          aria-current={view === 'privacy' ? 'page' : undefined}
          onClick={() => setView('privacy')}
        >
          <span className="icon" aria-hidden="true">
            🔐
          </span>
          Privacy
        </button>
      </nav>
    </div>
  );
}
