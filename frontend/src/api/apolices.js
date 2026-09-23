import { request } from './client';

export const apolicesApi = {
  listar: ({ busca, status } = {}, signal) => {
    const params = new URLSearchParams();
    if (busca) params.set('busca', busca);
    if (status) params.set('status', status);
    const query = params.toString();

    return request(`/apolices${query ? `?${query}` : ''}`, { signal });
  },
  buscar: (id) => request(`/apolices/${id}`),
  criar: (dados) => request('/apolices', { method: 'POST', body: dados }),
  atualizar: (id, dados) => request(`/apolices/${id}`, { method: 'PUT', body: dados }),
  excluir: (id) => request(`/apolices/${id}`, { method: 'DELETE' }),
  cotar: (dados, signal) => request('/apolices/cotacao', { method: 'POST', body: dados, signal }),
  opcoes: () => request('/opcoes'),
};
