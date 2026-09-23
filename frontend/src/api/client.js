import { lerSessao } from '../auth/sessao';

const BASE_URL = (import.meta.env.VITE_API_URL ?? '/api').replace(/\/$/, '');

let aoExpirarSessao = () => {};

export function definirAoExpirarSessao(callback) {
  aoExpirarSessao = callback;
}

export class ApiError extends Error {
  constructor(message, status, errors = {}) {
    super(message);
    this.status = status;
    this.errors = errors;
  }
}

export async function request(path, { method = 'GET', body, signal } = {}) {
  const token = lerSessao()?.token;
  const headers = {};
  if (body) headers['Content-Type'] = 'application/json';
  if (token) headers.Authorization = `Bearer ${token}`;

  let response;

  try {
    response = await fetch(`${BASE_URL}${path}`, {
      method,
      signal,
      headers,
      body: body ? JSON.stringify(body) : undefined,
    });
  } catch (error) {
    if (error.name === 'AbortError') throw error;
    throw new ApiError('Não foi possível conectar à API.', 0);
  }

  if (response.status === 204) return null;

  const data = await response.json().catch(() => null);

  if (response.status === 401 && token) {
    aoExpirarSessao();
  }

  if (!response.ok) {
    throw new ApiError(data?.message ?? 'Erro inesperado.', response.status, data?.errors ?? {});
  }

  return data;
}
