import { useEffect, useState } from 'react';
import { apolicesApi } from '../api/apolices';

let cache = null;

export function useOpcoes() {
  const [opcoes, setOpcoes] = useState(cache);
  const [erro, setErro] = useState(null);

  useEffect(() => {
    if (cache) return;

    apolicesApi
      .opcoes()
      .then((dados) => {
        cache = dados;
        setOpcoes(dados);
      })
      .catch(setErro);
  }, []);

  return { opcoes, erro };
}
