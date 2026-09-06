import assert from 'node:assert/strict';
import test, { after, before } from 'node:test';
import type { AddressInfo } from 'node:net';
import type { Server } from 'node:http';
import { createApp } from '../src/app.js';
import { imageStore } from '../src/storage/ephemeral.js';
import { synthBackground, synthPalm } from './fixtures/synthPalm.js';

let server: Server;
let baseUrl = '';

before(async () => {
  server = createApp().listen(0);
  await new Promise((resolve) => server.once('listening', resolve));
  const address = server.address() as AddressInfo;
  baseUrl = `http://127.0.0.1:${address.port}`;
});

after(async () => {
  imageStore.stop();
  await new Promise((resolve) => server.close(resolve));
});

async function postImage(buffer: Buffer, filename = 'palm.png', type = 'image/png'): Promise<Response> {
  const form = new FormData();
  form.append('image', new Blob([new Uint8Array(buffer)], { type }), filename);
  return fetch(`${baseUrl}/api/palm/analyze`, { method: 'POST', body: form });
}

test('health check answers', async () => {
  const response = await fetch(`${baseUrl}/api/health`);
  assert.equal(response.status, 200);
  assert.equal(((await response.json()) as { status: string }).status, 'ok');
});

test('capabilities describe the real provider and never leak secrets', async () => {
  const response = await fetch(`${baseUrl}/api/palm/capabilities`);
  const body = (await response.json()) as Record<string, unknown>;

  assert.equal(response.status, 200);
  assert.equal((body.provider as { id: string }).id, 'heuristic-cv');
  assert.equal((body.provider as { simulated: boolean }).simulated, false);
  assert.ok(String(body.disclaimer).includes('entertainment'));
  assert.ok(!JSON.stringify(body).toLowerCase().includes('api_key'));
  assert.ok(!JSON.stringify(body).includes('sk-'));
});

test('a good palm photo returns a full reading with an image reference', async () => {
  const response = await postImage(await synthPalm());
  const body = (await response.json()) as any;

  assert.equal(response.status, 200);
  assert.equal(body.status, 'ok');
  assert.equal(body.reading.sections.length, 6);
  assert.equal(body.meta.analysisIsSimulated, false);
  assert.match(body.image.url, /^\/api\/palm\/image\/[0-9a-f-]{36}$/);

  const image = await fetch(`${baseUrl}${body.image.url}`);
  assert.equal(image.status, 200);
  assert.equal(image.headers.get('content-type'), 'image/webp');

  const deleted = await fetch(`${baseUrl}${body.image.url}`, { method: 'DELETE' });
  assert.equal(((await deleted.json()) as { deleted: boolean }).deleted, true);

  const gone = await fetch(`${baseUrl}${body.image.url}`);
  assert.equal(gone.status, 404);
});

test('an unusable photo is refused with friendly advice and no reading', async () => {
  const response = await postImage(await synthBackground());
  const body = (await response.json()) as any;

  assert.equal(response.status, 200);
  assert.equal(body.status, 'unusable');
  assert.equal(body.reading, undefined);
  assert.ok(body.validation.issues.length > 0);
  assert.ok(body.validation.issues[0].advice.length > 10);
});

test('a request with no file is rejected clearly', async () => {
  const response = await fetch(`${baseUrl}/api/palm/analyze`, { method: 'POST', body: new FormData() });
  assert.equal(response.status, 400);
  assert.equal(((await response.json()) as any).error.code, 'missing_image');
});

test('a file that is not an image is rejected', async () => {
  const response = await postImage(Buffer.from('#!/bin/sh\nrm -rf /\n'.repeat(20)), 'evil.sh', 'image/png');
  assert.equal(response.status, 400);
  assert.equal(((await response.json()) as any).error.code, 'invalid_image');
});

test('a disallowed content type is rejected before decoding', async () => {
  const response = await postImage(Buffer.alloc(2000, 1), 'x.svg', 'image/svg+xml');
  assert.equal(response.status, 415);
});

test('image ids are validated instead of being trusted', async () => {
  const response = await fetch(`${baseUrl}/api/palm/image/..%2F..%2Fetc%2Fpasswd`);
  assert.equal(response.status, 400);
});

test('the admin dashboard stays closed without a token', async () => {
  const response = await fetch(`${baseUrl}/api/admin/metrics`);
  assert.ok(response.status === 404 || response.status === 401);
});

test('unknown api routes answer with json, not html', async () => {
  const response = await fetch(`${baseUrl}/api/does-not-exist`);
  assert.equal(response.status, 404);
  assert.equal(((await response.json()) as any).error.code, 'not_found');
});
