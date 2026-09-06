interface Props {
  imageRetentionMinutes: number;
  canDelete: boolean;
  imageDeleted: boolean;
  onDeleteImage: () => void;
}

export function Privacy({ imageRetentionMinutes, canDelete, imageDeleted, onDeleteImage }: Props) {
  return (
    <div className="stack-lg">
      <header className="fade-up">
        <h1 style={{ fontSize: 'clamp(1.5rem, 6vw, 2rem)' }}>Privacy & disclaimer</h1>
        <p className="muted" style={{ marginTop: 8 }}>
          Plain answers about what happens to your palm photo.
        </p>
      </header>

      <section className="card prose fade-up">
        <h2 className="section-title">What happens to your photo</h2>
        <p className="muted">
          Your photo is resized in your own browser first, then sent to this app's server over HTTPS. The server holds
          it in memory while it measures the image and writes your reading. It is never saved to a disk, never put in a
          database, and never sent to an advertising or tracking service.
        </p>
        <p className="muted">
          A small preview copy stays in memory for up to {imageRetentionMinutes} minutes so the results screen can show
          your palm back to you. After that it is dropped automatically. Restarting the server also clears everything.
        </p>

        <h3>Can you delete it sooner?</h3>
        <p className="muted">Yes. The button below removes the copy immediately.</p>
        <button
          type="button"
          className="btn btn-secondary"
          style={{ marginTop: 12, maxWidth: 320 }}
          onClick={onDeleteImage}
          disabled={!canDelete || imageDeleted}
        >
          {imageDeleted ? 'Image deleted ✓' : canDelete ? '🗑️ Delete my palm image' : 'No image stored right now'}
        </button>

        <h3>What we do not collect</h3>
        <ul>
          <li>No account, no email address, no name.</li>
          <li>No advertising or analytics scripts run in this app.</li>
          <li>Only anonymous counters - how many readings ran, how long they took, and error codes.</li>
        </ul>

        <h3>Honest wording</h3>
        <p className="muted">
          We do not say "100% private". Your photo does travel to a server to be analysed, and whoever runs that server
          controls it. If you are self-hosting this app, that server is yours. If a language model is enabled to polish
          the reading text, only the measured features and the reading text are sent to it - never your photo.
        </p>
      </section>

      <section className="card fade-up-2">
        <h2 className="section-title">Disclaimer</h2>
        <p className="muted" style={{ marginTop: 8 }}>
          Palm readings are provided for entertainment and cultural purposes only. They are not scientifically proven
          predictions and should not be used as a basis for medical, financial, legal, or other important life
          decisions.
        </p>
        <p className="muted" style={{ marginTop: 12 }}>
          If something in your life worries you, please talk to a qualified professional rather than a palm reading app.
        </p>
      </section>
    </div>
  );
}
