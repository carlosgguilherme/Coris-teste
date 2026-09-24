import { request } from './client';

export const dashboardApi = {
  /** filtros: { periodo, canal, plano, destino } — os vazios não vão na URL */
  visao: (visao, filtros, signal) => {
    const params = new URLSearchParams(Object.entries(filtros).filter(([, valor]) => valor));
    return request(`/dashboard/${visao}?${params}`, { signal });
  },
};
