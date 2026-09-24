import { request } from './client';

export const dashboardApi = {
  visao: (visao, periodo, signal) => request(`/dashboard/${visao}?periodo=${periodo}`, { signal }),
};
