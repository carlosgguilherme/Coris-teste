import '@testing-library/jest-dom/vitest';
import { cleanup } from '@testing-library/react';
import { afterEach } from 'vitest';
import { limparSessao } from '../auth/sessao';

afterEach(() => {
  cleanup();
  limparSessao();
});
