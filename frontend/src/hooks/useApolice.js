import { useEffect, useState } from 'react';
import { apolicesApi } from '../api/apolices';

export function useApolice(id) {
  const [apolice, setApolice] = useState(null);
  const [erro, setErro] = useState(null);

  useEffect(() => {
    let ativo = true;
    setApolice(null);
    setErro(null);

    apolicesApi
      .buscar(id)
      .then((dados) => ativo && setApolice(dados))
      .catch((error) => ativo && setErro(error));

    return () => {
      ativo = false;
    };
  }, [id]);

  return { apolice, erro };
}
