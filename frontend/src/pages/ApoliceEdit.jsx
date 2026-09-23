import { useNavigate, useParams } from 'react-router-dom';
import { apolicesApi } from '../api/apolices';
import ApoliceForm from '../components/ApoliceForm';
import { useToast } from '../components/Toast';
import { useApolice } from '../hooks/useApolice';

export default function ApoliceEdit() {
  const { id } = useParams();
  const navigate = useNavigate();
  const notificar = useToast();
  const { apolice, erro } = useApolice(id);

  async function salvar(dados) {
    await apolicesApi.atualizar(id, dados);
    notificar('Apólice atualizada com sucesso.');
    navigate(`/apolices/${id}`);
  }

  if (erro) return <div className="alert alert--erro">{erro.message}</div>;
  if (!apolice) return <p className="vazio">Carregando...</p>;

  return (
    <>
      <div className="page-header">
        <div>
          <h1>Editar apólice</h1>
          <p className="muted mono">{apolice.numero}</p>
        </div>
      </div>
      <ApoliceForm inicial={apolice} edicao textoBotao="Salvar alterações" onSubmit={salvar} />
    </>
  );
}
