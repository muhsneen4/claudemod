import { useRef, useState } from 'react';
import { CameraCapture } from '../components/CameraCapture';
import { Notice } from '../components/Notice';
import { formatBytes, prepareImage, type PreparedImage } from '../lib/image';
import type { Capabilities } from '../lib/types';

interface Props {
  capabilities: Capabilities | null;
  onAnalyze: (image: PreparedImage) => void;
  onBack: () => void;
}

const CHECKS = [
  'Good lighting',
  'Palm facing the camera',
  'All fingers visible',
  'A sharp, steady photo',
  'The whole palm inside the frame',
];

export function Capture({ capabilities, onAnalyze, onBack }: Props) {
  const [mode, setMode] = useState<'choose' | 'camera'>('choose');
  const [image, setImage] = useState<PreparedImage | null>(null);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const uploadRef = useRef<HTMLInputElement>(null);
  const cameraRef = useRef<HTMLInputElement>(null);

  const maxBytes = capabilities?.maxUploadBytes ?? 8_000_000;

  async function accept(file: File | undefined) {
    if (!file) return;
    setError(null);

    if (!file.type.startsWith('image/')) {
      setError('That file is not an image. Please choose a photo of your palm.');
      return;
    }
    if (file.size > maxBytes * 6) {
      setError(`That photo is very large (${formatBytes(file.size)}). Please choose a smaller one.`);
      return;
    }

    setBusy(true);
    try {
      const prepared = await prepareImage(file);
      if (image) URL.revokeObjectURL(image.previewUrl);
      setImage(prepared);
      setMode('choose');
    } catch (cause) {
      setError(cause instanceof Error ? cause.message : 'We could not open that image. Please try another photo.');
    } finally {
      setBusy(false);
    }
  }

  function reset() {
    if (image) URL.revokeObjectURL(image.previewUrl);
    setImage(null);
    setError(null);
  }

  if (mode === 'camera') {
    return (
      <div className="stack-lg">
        <h1 className="section-title">Place your palm inside the frame</h1>
        <CameraCapture onCapture={(file) => void accept(file)} onCancel={() => setMode('choose')} />
      </div>
    );
  }

  return (
    <div className="stack-lg">
      <header className="fade-up">
        <h1 style={{ fontSize: 'clamp(1.5rem, 6vw, 2rem)' }}>Scan your palm</h1>
        <p className="muted" style={{ marginTop: 8 }}>
          Place your palm inside the frame. A clear photo gives a much better reading.
        </p>
      </header>

      {image ? (
        <section className="card fade-up">
          <div className="preview-shell">
            <img src={image.previewUrl} alt="Preview of the palm photo you chose" />
          </div>
          <p className="muted" style={{ marginTop: 12 }}>
            Ready to analyse · {image.width}×{image.height} · {formatBytes(image.bytes)}
            {image.originalBytes > image.bytes * 1.2 && ` (compressed from ${formatBytes(image.originalBytes)})`}
          </p>
          <div className="btn-row two" style={{ marginTop: 16 }}>
            <button type="button" className="btn btn-primary" onClick={() => onAnalyze(image)}>
              Analyze My Palm
            </button>
            <button type="button" className="btn btn-secondary" onClick={reset}>
              Choose another photo
            </button>
          </div>
        </section>
      ) : (
        <>
          <section className="card fade-up">
            <div className="frame-guide" aria-hidden="true">
              <span className="corner tl" />
              <span className="corner tr" />
              <span className="corner bl" />
              <span className="corner br" />
              <span style={{ fontSize: 52, opacity: 0.5 }}>🖐️</span>
            </div>
            <ul className="checklist" style={{ marginTop: 20 }}>
              {CHECKS.map((check) => (
                <li key={check}>
                  <span className="tick" aria-hidden="true">
                    ✓
                  </span>
                  {check}
                </li>
              ))}
            </ul>
          </section>

          <div className="btn-row two fade-up-2">
            <button type="button" className="btn btn-primary" onClick={() => setMode('camera')} disabled={busy}>
              {busy ? <span className="spinner" aria-hidden="true" /> : '📷'} Take Photo
            </button>
            <button type="button" className="btn btn-secondary" onClick={() => uploadRef.current?.click()} disabled={busy}>
              🖼️ Upload Image
            </button>
          </div>

          <button type="button" className="btn btn-ghost fade-up-2" onClick={() => cameraRef.current?.click()}>
            Use your phone camera app instead
          </button>
        </>
      )}

      {error && (
        <Notice tone="danger" icon="⚠️" title="We could not use that file">
          {error}
        </Notice>
      )}

      <input
        ref={uploadRef}
        type="file"
        accept="image/jpeg,image/png,image/webp,image/heic,image/heif"
        className="sr-only"
        onChange={(event) => void accept(event.target.files?.[0])}
      />
      <input
        ref={cameraRef}
        type="file"
        accept="image/*"
        capture="environment"
        className="sr-only"
        onChange={(event) => void accept(event.target.files?.[0])}
      />

      <button type="button" className="btn btn-ghost" onClick={onBack}>
        Back to home
      </button>
    </div>
  );
}
