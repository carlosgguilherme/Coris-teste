import { useNavigate } from 'react-router-dom';
import { apolicesApi } from '../api/apolices';
import ApoliceForm from '../components/ApoliceForm';
import { useToast } from '../components/Toast';

export default function ApoliceCreate() {
  const navigate = useNavigate();
  const notificar = useToast();

  async function criar(dados) {
    const apolice = await apolicesApi.criar(dados);
    notificar(`Apólice ${apolice.numero} emitida com sucesso.`);
    navigate(`/apolices/${apolice.id}`);
  }

  return (
    <>
      <div className="page-header">
        <div>
          <h1>Nova apólice</h1>
          <p className="muted">O valor do prêmio é calculado pelo plano, destino, dias de viagem e idade do segurado.</p>
        </div>
      </div>
      <ApoliceForm textoBotao="Emitir apólice" onSubmit={criar} />
    </>
  );
}
