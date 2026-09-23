import { describe, expect, it, vi } from 'vitest';
import { salvarSessao } from '../auth/sessao';
import { ApiError, definirAoExpirarSessao, request } from './client';

function respostaFalsa(status, corpo) {
  return Promise.resolve({ status, ok: status < 400, json: () => Promise.resolve(corpo) });
}

describe('request', () => {
  it('envia o token da sessão no cabeçalho Authorization', async () => {
    salvarSessao({ token: 'abc123' });
    const fetch = vi.spyOn(globalThis, 'fetch').mockReturnValue(respostaFalsa(200, { ok: true }));

    await request('/apolices');

    expect(fetch.mock.calls[0][1].headers.Authorization).toBe('Bearer abc123');
  });

  it('converte erro de validação em ApiError com os campos', async () => {
    vi.spyOn(globalThis, 'fetch').mockReturnValue(
      respostaFalsa(422, { message: 'Dados inválidos', errors: { seguradoCpf: 'CPF inválido.' } }),
    );

    const erro = await request('/apolices', { method: 'POST', body: {} }).catch((e) => e);

    expect(erro).toBeInstanceOf(ApiError);
    expect(erro.status).toBe(422);
    expect(erro.errors).toEqual({ seguradoCpf: 'CPF inválido.' });
  });

  it('encerra a sessão quando a API responde 401', async () => {
    salvarSessao({ token: 'expirado' });
    const aoExpirar = vi.fn();
    definirAoExpirarSessao(aoExpirar);
    vi.spyOn(globalThis, 'fetch').mockReturnValue(respostaFalsa(401, { message: 'Sessão expirada' }));

    await expect(request('/apolices')).rejects.toThrow('Sessão expirada');
    expect(aoExpirar).toHaveBeenCalledOnce();
  });

  it('informa quando a API está fora do ar', async () => {
    vi.spyOn(globalThis, 'fetch').mockRejectedValue(new TypeError('Failed to fetch'));

    await expect(request('/apolices')).rejects.toThrow('Não foi possível conectar à API.');
  });
});
