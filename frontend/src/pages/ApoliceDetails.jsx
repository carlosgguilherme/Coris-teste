import { useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import { apolicesApi } from '../api/apolices';
import ConfirmDialog from '../components/ConfirmDialog';
import StatusBadge from '../components/StatusBadge';
import { useToast } from '../components/Toast';
import { useApolice } from '../hooks/useApolice';
import { formatarData, formatarDataHora, formatarMoeda } from '../utils/format';

export default function ApoliceDetails() {
  const { id } = useParams();
  const navigate = useNavigate();
  const notificar = useToast();
  const { apolice, erro } = useApolice(id);
  const [confirmando, setConfirmando] = useState(false);
  const [excluindo, setExcluindo] = useState(false);

  async function excluir() {
    setExcluindo(true);
    try {
      await apolicesApi.excluir(id);
      notificar(`Apólice ${apolice.numero} excluída.`);
      navigate('/apolices');
    } catch (error) {
      notificar(error.message, 'erro');
      setExcluindo(false);
      setConfirmando(false);
    }
  }

  if (erro) {
    return (
      <div className="alert alert--erro">
        {erro.message} <Link to="/apolices">Voltar para a lista</Link>
      </div>
    );
  }

  if (!apolice) return <p className="vazio">Carregando...</p>;

  return (
    <>
      <div className="page-header">
        <div>
          <Link to="/apolices" className="voltar">← Apólices</Link>
          <h1 className="mono">{apolice.numero}</h1>
          <StatusBadge status={apolice.status} label={apolice.statusLabel} />
        </div>
        <div className="page-header__acoes">
          <Link to={`/apolices/${apolice.id}/editar`} className="btn btn--primary">Editar</Link>
          <button type="button" className="btn btn--ghost-danger" onClick={() => setConfirmando(true)}>Excluir</button>
        </div>
      </div>

      <div className="detalhes">
        <section className="card">
          <h2>Segurado</h2>
          <dl className="lista-dados">
            <Item label="Nome" valor={apolice.seguradoNome} />
            <Item label="CPF" valor={apolice.seguradoCpf} />
            <Item label="E-mail" valor={apolice.seguradoEmail} />
            <Item label="Nascimento" valor={formatarData(apolice.seguradoNascimento)} />
          </dl>
        </section>

        <section className="card">
          <h2>Viagem</h2>
          <dl className="lista-dados">
            <Item label="Destino" valor={apolice.destinoLabel} />
            <Item label="Plano" valor={apolice.planoLabel} />
            <Item label="Vigência" valor={`${formatarData(apolice.inicioVigencia)} a ${formatarData(apolice.fimVigencia)}`} />
            <Item label="Duração" valor={`${apolice.dias} dias`} />
          </dl>
        </section>

        <section className="card resumo">
          <h2>Prêmio</h2>
          <p className="resumo__valor">{formatarMoeda(apolice.valorPremio)}</p>
          <dl className="lista-dados">
            <Item label="Emitida em" valor={formatarDataHora(apolice.criadoEm)} />
            <Item label="Última alteração" valor={formatarDataHora(apolice.atualizadoEm)} />
          </dl>
        </section>
      </div>

      <ConfirmDialog
        aberto={confirmando}
        titulo="Excluir apólice"
        mensagem={`Deseja realmente excluir a apólice ${apolice.numero}? Esta ação não pode ser desfeita.`}
        textoConfirmar="Excluir"
        carregando={excluindo}
        onConfirmar={excluir}
        onCancelar={() => setConfirmando(false)}
      />
    </>
  );
}

function Item({ label, valor }) {
  return (
    <div>
      <dt>{label}</dt>
      <dd>{valor}</dd>
    </div>
  );
}
