const BASE_URL = (import.meta.env.VITE_API_URL ?? '/api').replace(/\/$/, '');

export class ApiError extends Error {
  constructor(message, status, errors = {}) {
    super(message);
    this.status = status;
    this.errors = errors;
  }
}

// O Laravel devolve cada erro como lista (["CPF inválido."]); o formulário usa só a primeira mensagem.
function primeiraMensagemPorCampo(errors = {}) {
  return Object.fromEntries(
    Object.entries(errors).map(([campo, mensagens]) => [campo, Array.isArray(mensagens) ? mensagens[0] : mensagens]),
  );
}

export async function request(path, { method = 'GET', body, signal } = {}) {
  let response;

  try {
    response = await fetch(`${BASE_URL}${path}`, {
      method,
      signal,
      headers: {
        Accept: 'application/json',
        ...(body ? { 'Content-Type': 'application/json' } : {}),
      },
      body: body ? JSON.stringify(body) : undefined,
    });
  } catch (error) {
    if (error.name === 'AbortError') throw error;
    throw new ApiError('Não foi possível conectar à API.', 0);
  }

  if (response.status === 204) return null;

  const data = await response.json().catch(() => null);

  if (!response.ok) {
    throw new ApiError(data?.message ?? 'Erro inesperado.', response.status, primeiraMensagemPorCampo(data?.errors));
  }

  return data;
}
