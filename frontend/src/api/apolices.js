import { request } from './client';

export const apolicesApi = {
  listar: ({ busca, status, pagina = 1 } = {}, signal) => {
    const params = new URLSearchParams({ pagina: String(pagina) });
    if (busca) params.set('busca', busca);
    if (status) params.set('status', status);

    return request(`/apolices?${params}`, { signal });
  },
  resumo: () => request('/apolices/resumo'),
  buscar: (id) => request(`/apolices/${id}`),
  endossos: (id) => request(`/apolices/${id}/endossos`),
  criar: (dados) => request('/apolices', { method: 'POST', body: dados }),
  atualizar: (id, dados) => request(`/apolices/${id}`, { method: 'PUT', body: dados }),
  excluir: (id) => request(`/apolices/${id}`, { method: 'DELETE' }),
  cotar: (dados, signal) => request('/apolices/cotacao', { method: 'POST', body: dados, signal }),
  opcoes: () => request('/opcoes'),
};
