import { useEffect, useRef, useState } from 'react';

interface Props {
  onCapture: (file: File) => void;
  onCancel: () => void;
}

/**
 * Live camera view with a palm shaped guide.
 * Browsers only allow this on https (or localhost). When the camera cannot be
 * opened - permission denied, no camera, insecure page - the parent screen
 * falls back to the normal "take a photo" file input, which every phone has.
 */
export function CameraCapture({ onCapture, onCancel }: Props) {
  const videoRef = useRef<HTMLVideoElement>(null);
  const streamRef = useRef<MediaStream | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [ready, setReady] = useState(false);

  useEffect(() => {
    let cancelled = false;

    async function start() {
      if (!navigator.mediaDevices?.getUserMedia) {
        setError('This browser cannot open the camera here. Please upload a photo instead.');
        return;
      }
      try {
        const stream = await navigator.mediaDevices.getUserMedia({
          video: { facingMode: { ideal: 'environment' }, width: { ideal: 1920 } },
          audio: false,
        });
        if (cancelled) {
          stream.getTracks().forEach((track) => track.stop());
          return;
        }
        streamRef.current = stream;
        if (videoRef.current) {
          videoRef.current.srcObject = stream;
          await videoRef.current.play().catch(() => undefined);
        }
        setReady(true);
      } catch {
        if (!cancelled) {
          setError('We could not open your camera. Check the camera permission, or upload a photo instead.');
        }
      }
    }

    void start();
    return () => {
      cancelled = true;
      streamRef.current?.getTracks().forEach((track) => track.stop());
      streamRef.current = null;
    };
  }, []);

  function takeShot() {
    const video = videoRef.current;
    if (!video || !video.videoWidth) return;

    const canvas = document.createElement('canvas');
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    const context = canvas.getContext('2d');
    if (!context) return;
    context.drawImage(video, 0, 0, canvas.width, canvas.height);

    canvas.toBlob(
      (blob) => {
        if (!blob) return;
        onCapture(new File([blob], 'palm.jpg', { type: 'image/jpeg' }));
      },
      'image/jpeg',
      0.92,
    );
  }

  if (error) {
    return (
      <div className="stack">
        <div className="notice warn" role="alert">
          <span aria-hidden="true">📷</span>
          <div>{error}</div>
        </div>
        <button type="button" className="btn btn-secondary" onClick={onCancel}>
          Go back
        </button>
      </div>
    );
  }

  return (
    <div className="stack">
      <div className="preview-shell">
        <video ref={videoRef} playsInline muted autoPlay aria-label="Camera preview" />
        <div className="camera-overlay">
          <div className="camera-outline" />
        </div>
      </div>
      <p className="muted" style={{ textAlign: 'center' }}>
        Place your open palm inside the outline and hold still.
      </p>
      <div className="btn-row two">
        <button type="button" className="btn btn-primary" onClick={takeShot} disabled={!ready}>
          Capture palm
        </button>
        <button type="button" className="btn btn-secondary" onClick={onCancel}>
          Cancel
        </button>
      </div>
    </div>
  );
}
