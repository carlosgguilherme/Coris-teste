import { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react';
import { authApi } from '../api/auth';
import { definirAoExpirarSessao } from '../api/client';
import { lerSessao, limparSessao, salvarSessao } from './sessao';

const AuthContext = createContext(null);

export function AuthProvider({ children }) {
  const [sessao, setSessao] = useState(lerSessao);

  const logout = useCallback(() => {
    limparSessao();
    setSessao(null);
  }, []);

  const login = useCallback(async (email, senha) => {
    const novaSessao = await authApi.login(email, senha);
    salvarSessao(novaSessao);
    setSessao(novaSessao);
  }, []);

  useEffect(() => definirAoExpirarSessao(logout), [logout]);

  const valor = useMemo(
    () => ({ usuario: sessao?.usuario ?? null, autenticado: Boolean(sessao?.token), login, logout }),
    [sessao, login, logout],
  );

  return <AuthContext.Provider value={valor}>{children}</AuthContext.Provider>;
}

export const useAuth = () => useContext(AuthContext);
