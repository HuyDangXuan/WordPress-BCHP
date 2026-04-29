import test from 'node:test';
import assert from 'node:assert/strict';

import { loadEnv } from '../src/config/env.js';

test('loadEnv requires all documented service variables', () => {
  assert.throws(() => loadEnv({}), /MONGO_URI/);
});
