import { useEffect, useState } from 'react';
import { dashboardApi } from '../api/dashboard';

export function useDashboard(visao, periodo) {
  const chave = `${visao}|${periodo}`;
  const [estado, setEstado] = useState({ chave: null, dados: null, erro: null });

  useEffect(() => {
    const controller = new AbortController();

    dashboardApi
      .visao(visao, periodo, controller.signal)
      .then((dados) => setEstado({ chave, dados, erro: null }))
      .catch((error) => {
        if (error.name !== 'AbortError') setEstado({ chave, dados: null, erro: error.message });
      });

    return () => controller.abort();
  }, [chave, visao, periodo]);

  // Enquanto a nova visão carrega, não devolve os dados da visão anterior
  const atual = estado.chave === chave;

  return { dados: atual ? estado.dados : null, erro: atual ? estado.erro : null, carregando: !atual };
}
