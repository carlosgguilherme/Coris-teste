import { useEffect, useState } from 'react';
import { dashboardApi } from '../api/dashboard';

export function useDashboard(visao, filtros) {
  const chave = `${visao}|${JSON.stringify(filtros)}`;
  const [estado, setEstado] = useState({ chave: null, dados: null, erro: null });

  useEffect(() => {
    const controller = new AbortController();

    dashboardApi
      .visao(visao, filtros, controller.signal)
      .then((dados) => setEstado({ chave, dados, erro: null }))
      .catch((error) => {
        if (error.name !== 'AbortError') setEstado({ chave, dados: null, erro: error.message });
      });

    return () => controller.abort();
    // a chave já representa a visão e os filtros
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [chave]);

  // Enquanto a nova consulta carrega, não devolve os dados da anterior
  const atual = estado.chave === chave;

  return { dados: atual ? estado.dados : null, erro: atual ? estado.erro : null, carregando: !atual };
}
