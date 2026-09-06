# 🔮 Destiny Palm AI

**Your Palm. Your Story. Your Destiny.**

Destiny Palm AI is a small, complete web app. You take or upload a photo of your
palm, the server measures what it can actually see in that photo, and you get a
traditional palmistry reading written in plain English.

It is built around one rule: **never say we detected something when we did not.**
Every feature in the reading carries a confidence value, lines are only drawn on
your photo when they were really traced, and demo mode is labelled everywhere.

> Palm readings are provided for entertainment and cultural purposes only. They
> are not scientifically proven predictions and should not be used as a basis for
> medical, financial, legal, or other important life decisions.

---

## What it does

| Step | What happens |
| --- | --- |
| 1. Landing | Explains the app, shows what it can and cannot do. |
| 2. Capture | Live camera with a palm guide, or upload a photo. The image is resized in your browser first. |
| 3. Image check | Brightness, sharpness, framing, hand detection. Bad photos get friendly advice, not a fake reading. |
| 4. Analysis | Classical computer vision finds the hand, the palm area and the main creases. |
| 5. Reading | Six cards: Love, Career, Work & Resources, Personality, Life Energy, Overall Destiny, plus lucky elements. |
| 6. Privacy | One button deletes your palm image from the server straight away. |

---

## How the image analysis really works

The default provider (`heuristic-cv`) runs on your own server with
[sharp](https://sharp.pixelplumbing.com/). It is **classical computer vision, not
a trained neural network**, and the app says so on screen.

What it genuinely measures:

1. **Exposure and focus** - average brightness of the hand, and sharpness from
   the variance of the Laplacian.
2. **The hand** - skin segmentation in the YCbCr colour space, cleaned up with a
   majority filter, then flood-fill labelling to find separate hands.
3. **Hand geometry** - run-length analysis of each row. Rows crossing the fingers
   contain several runs, rows crossing the palm contain one wide run. That single
   idea gives the finger zone, the palm rectangle, the finger count, the finger
   to palm ratio and the thumb side.
4. **The creases** - a difference-of-gaussians high-pass filter with the
   background replaced by average skin tone, so the hand outline does not drown
   out real creases. Inside each palmistry zone, a dynamic-programming search
   traces the strongest connected crease path, then reports its strength,
   contrast, continuity, bend and drift.
5. **Mounts** - relative shading of each mount area, reported with deliberately
   low confidence because shading is a weak proxy for a 3D bump.

What it does **not** do:

- It has no learned model of a palm, so zone names (heart line, fate line) follow
  standard palm anatomy rather than verified anatomy in your photo.
- Telling a palm from the back of a hand is based on crease texture alone. It is
  a hint, and the app says so.
- Skin segmentation by colour is approximate. Strong colour casts, gloves or
  skin-coloured backgrounds can fool it, and the app falls back to a brightness
  based method for near-greyscale photos.

Every one of these limits is returned in `analysis.limitations` and shown in the
"technical details" panel on the results page.

### Swapping in a real model

`server/src/analysis/types.ts` defines the whole contract. A provider is just:

```ts
interface PalmAnalysisProvider {
  id: string;
  technique: string;
  simulated: boolean;
  analyzePalm(image: Buffer, options?: AnalyzeOptions): Promise<PalmAnalysis>;
}
```

Three providers ship with the app:

| Provider | `PALM_ANALYSIS_PROVIDER` | Notes |
| --- | --- | --- |
| Classical CV | `heuristic-cv` | Default. Runs locally, no API key. |
| Demo data | `mock` | Measures nothing. Clearly labelled in the UI and in the API response. |
| External model | `remote` | POSTs the image to `REMOTE_ANALYSIS_URL` and expects the same JSON back. |

To add your own, implement the interface and call `registerProvider()` in
`server/src/analysis/index.ts`. Nothing else in the app has to change.

---

## Project layout

```
destiny-palm-ai/
├── server/                     Express API (TypeScript, ESM)
│   ├── src/
│   │   ├── analysis/           image decoding, skin + hand geometry, crease
│   │   │                       tracing, providers, image validation
│   │   ├── interpretation/     palmistry rules -> readings (pure functions)
│   │   ├── narration/          optional Claude rewrite of the reading text
│   │   ├── storage/            in-memory, time limited image store
│   │   ├── analytics/          anonymous counters for the admin endpoint
│   │   ├── billing/            tier + feature flags for future paid plans
│   │   ├── middleware/         uploads, rate limits, error handling
│   │   └── routes/             /api/palm/*, /api/admin/*
│   └── test/                   node:test suite with synthetic palm fixtures
└── web/                        React + Vite frontend
    └── src/
        ├── screens/            Landing, Capture, Scanning, Results, Privacy
        ├── components/         camera, palm viewer, illustration, notices
        └── lib/                API client, browser-side image compression
```

---

## Getting started

### Requirements

- Node.js 20 or newer
- npm 10 or newer

### Install and run

```bash
git clone <your-repo-url>
cd destiny-palm-ai
npm install
cp .env.example .env        # edit if you like, the defaults work as they are

# terminal 1 - API on http://localhost:8787
npm run dev:server

# terminal 2 - app on http://localhost:5173
npm run dev:web
```

Open <http://localhost:5173>. Vite proxies `/api` to the API server, so the
frontend never needs an API host or key of its own.

### Tests

```bash
npm test          # server suite: analysis, validation, interpretation, API
npm run typecheck # strict TypeScript across both packages
```

The tests build synthetic palm images with SVG, so they are fast, deterministic
and contain nobody's real hand.

### Production build

```bash
npm run build     # builds web/dist and server/dist
npm start         # one process: API + the built frontend on PORT
```

In production the API also serves `web/dist`, so you only deploy one service.

---

## Environment variables

Copy `.env.example` to `.env`. Everything has a working default except the
optional keys.

| Variable | Default | What it does |
| --- | --- | --- |
| `PORT` | `8787` | Port the API listens on. |
| `NODE_ENV` | `development` | Set to `production` when you deploy. |
| `CORS_ORIGINS` | empty | Comma separated origins allowed to call the API. |
| `PALM_ANALYSIS_PROVIDER` | `heuristic-cv` | `heuristic-cv`, `mock` or `remote`. |
| `REMOTE_ANALYSIS_URL` | empty | Your own model endpoint, used by the `remote` provider. |
| `REMOTE_ANALYSIS_API_KEY` | empty | Sent as a bearer token to that endpoint. Server side only. |
| `READING_TEXT_PROVIDER` | `template` | `template` (offline) or `anthropic`. |
| `ANTHROPIC_API_KEY` | empty | Enables the Claude rewrite. **Never reaches the browser.** |
| `ANTHROPIC_MODEL` | `claude-opus-5` | Model used for the rewrite. |
| `MAX_UPLOAD_BYTES` | `8000000` | Upload size limit. |
| `RATE_LIMIT_WINDOW_MS` | `60000` | Rate limit window. |
| `RATE_LIMIT_MAX_REQUESTS` | `20` | Readings allowed per window per IP. |
| `IMAGE_RETENTION_MS` | `900000` | How long a palm image stays in memory. |
| `ADMIN_API_TOKEN` | empty | Set a long random value to enable `/api/admin/metrics`. |
| `VITE_API_BASE_URL` | `http://localhost:8787` | Dev proxy target only. |

**Secrets never reach the frontend.** Only `VITE_`-prefixed variables are visible
to Vite, and the only one used is a dev proxy target. The app calls `/api/...` on
its own origin, so there is nothing to leak.

---

## API

| Method | Path | Purpose |
| --- | --- | --- |
| `GET` | `/api/health` | Liveness check. |
| `GET` | `/api/palm/capabilities` | Which provider is active, limits, disclaimer. |
| `POST` | `/api/palm/analyze` | `multipart/form-data` with an `image` field. Returns the analysis, the validation result and the reading. |
| `GET` | `/api/palm/image/:id` | The temporary preview image. |
| `DELETE` | `/api/palm/image/:id` | "Delete my palm image". |
| `GET` | `/api/admin/metrics` | Anonymous counters. Needs `Authorization: Bearer <ADMIN_API_TOKEN>`. |

`POST /api/palm/analyze` answers with `status: "ok"` and a full reading, or with
`status: "unusable"` plus friendly advice and **no reading at all**.

<details>
<summary>Example analysis object</summary>

```json
{
  "provider": "heuristic-cv",
  "technique": "On-server classical computer vision (skin segmentation + crease tracing)",
  "simulated": false,
  "palmDetected": true,
  "confidence": 0.92,
  "handSide": "right",
  "palmShape": "square",
  "heartLine": {
    "visibility": "high",
    "confidence": 0.88,
    "length": "long",
    "curvature": "gentle",
    "direction": "level",
    "detectionMethod": "cv-crease-trace",
    "note": "Traced crease strength 0.71, contrast 14.50x the surrounding skin."
  },
  "overlay": { "available": true, "segments": [{ "line": "heart", "points": [] }] },
  "limitations": ["Telling a palm from the back of a hand is done by crease texture only."]
}
```

</details>

---

## Privacy

- Photos are resized in your browser before upload.
- The server keeps the image **in memory only**. Nothing is written to disk or to
  a database.
- The copy is dropped automatically after `IMAGE_RETENTION_MS`, when you press
  "Delete my palm image", or when the process restarts.
- No accounts, no cookies, no analytics scripts, no advertising code.
- Only anonymous counters are kept: totals, timings, a coarse device class and
  error codes.
- If the Claude rewrite is enabled, only the measured features and the reading
  text are sent to the Anthropic API - never your photo.

The app deliberately does not claim "100% private", because your photo does
travel to a server. If you self-host, that server is yours.

---

## Security

- Uploads stay in memory, so a hostile file name can never touch the filesystem.
- The declared content type is not trusted: the image header is re-read with the
  decoder and anything that is not a real JPEG, PNG, WebP or HEIF is rejected.
- Size, pixel count, file count and field count are all capped.
- `helmet` sets a strict Content Security Policy, `nosniff` and `frameAncestors: none`.
- CORS is an allowlist.
- Rate limiting protects the expensive analysis endpoint.
- The admin token is compared in constant time, and the endpoint stays disabled
  until you set a token of at least 16 characters.
- Image ids must match a UUID pattern before they are looked up.

---

## Performance

- Photos are compressed to WebP in the browser, usually well under 300 KB.
- Analysis runs on a 480 px copy - more pixels do not make crease tracing better.
- The React bundle is small (~165 KB total, gzipped far less) with the results
  and privacy screens code-split.
- No web fonts, no icon library, no animation library. Animations are CSS only
  and respect `prefers-reduced-motion`.
- Preview images are re-encoded to WebP with metadata stripped.

---

## Deployment

### Any Node host (Render, Railway, Fly.io, a VPS)

```bash
npm ci
npm run build
NODE_ENV=production PORT=8080 npm start
```

Serve it behind HTTPS. The live camera needs a secure origin - on plain HTTP the
app automatically falls back to the phone's camera app through a file input.

### Docker

```bash
docker build -t destiny-palm-ai .
docker run -p 8080:8080 --env-file .env -e PORT=8080 destiny-palm-ai
```

### Split hosting (static frontend + separate API)

Deploy `web/dist` to any static host and the API anywhere Node runs, then:

1. Point the static host's `/api/*` rewrite at the API, **or**
2. set `CORS_ORIGINS` to your frontend origin and put the API behind the same
   domain with a proxy.

Keeping both on one origin is simplest and keeps the CSP tight.

---

## Ready for later

The code already has the seams for the obvious next features, so adding them does
not mean a rewrite:

- **Paid tiers** - `server/src/billing/entitlements.ts` names every premium
  feature (detailed reading, PDF report, saved readings, palm comparison,
  compatibility, daily insight). Change `resolveTier` and flip the flags.
- **Admin dashboard** - `/api/admin/metrics` already returns totals, daily
  counts, success and failure rates, average processing time, device classes and
  recent error codes. Point a dashboard at it.
- **A real model** - implement one interface, as described above.
- **Another storage backend** - `storage/ephemeral.ts` has four methods.

---

## Licence

MIT. Use it, change it, ship it - just keep the disclaimer honest.
