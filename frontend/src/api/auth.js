import { request } from './client';

export const authApi = {
  login: (email, senha) => request('/auth/login', { method: 'POST', body: { email, senha } }),
};
