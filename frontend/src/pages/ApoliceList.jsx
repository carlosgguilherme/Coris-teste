import { useEffect, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { apolicesApi } from '../api/apolices';
import ConfirmDialog from '../components/ConfirmDialog';
import StatusBadge from '../components/StatusBadge';
import { useToast } from '../components/Toast';
import { formatarData, formatarMoeda } from '../utils/format';

export default function ApoliceList() {
  const navigate = useNavigate();
  const notificar = useToast();
  const [apolices, setApolices] = useState([]);
  const [filtros, setFiltros] = useState({ busca: '', status: '' });
  const [carregando, setCarregando] = useState(true);
  const [erro, setErro] = useState(null);
  const [paraExcluir, setParaExcluir] = useState(null);
  const [excluindo, setExcluindo] = useState(false);
  const [versao, setVersao] = useState(0);

  useEffect(() => {
    const controller = new AbortController();
    const timer = setTimeout(() => {
      setCarregando(true);
      apolicesApi
        .listar(filtros, controller.signal)
        .then((dados) => {
          setApolices(dados);
          setErro(null);
        })
        .catch((error) => {
          if (error.name !== 'AbortError') setErro(error.message);
        })
        .finally(() => setCarregando(false));
    }, 300);

    return () => {
      clearTimeout(timer);
      controller.abort();
    };
  }, [filtros, versao]);

  async function excluir() {
    setExcluindo(true);
    try {
      await apolicesApi.excluir(paraExcluir.id);
      notificar(`Apólice ${paraExcluir.numero} excluída.`);
      setParaExcluir(null);
      setVersao((v) => v + 1);
    } catch (error) {
      notificar(error.message, 'erro');
    } finally {
      setExcluindo(false);
    }
  }

  const totalPremios = apolices
    .filter((apolice) => apolice.status === 'ativa')
    .reduce((total, apolice) => total + apolice.valorPremioCentavos, 0);

  return (
    <>
      <div className="page-header">
        <div>
          <h1>Apólices</h1>
          <p className="muted">Consulte, emita e gerencie as apólices de seguro viagem.</p>
        </div>
        <Link to="/apolices/nova" className="btn btn--primary">+ Nova apólice</Link>
      </div>

      <section className="stats">
        <div className="card stat">
          <span>Apólices listadas</span>
          <strong>{apolices.length}</strong>
        </div>
        <div className="card stat">
          <span>Ativas</span>
          <strong>{apolices.filter((a) => a.status === 'ativa').length}</strong>
        </div>
        <div className="card stat">
          <span>Prêmios ativos</span>
          <strong>{formatarMoeda(totalPremios)}</strong>
        </div>
      </section>

      <div className="card">
        <div className="filtros">
          <input
            type="search"
            placeholder="Buscar por nome, CPF, e-mail ou nº da apólice"
            value={filtros.busca}
            onChange={(e) => setFiltros((f) => ({ ...f, busca: e.target.value }))}
            aria-label="Buscar"
          />
          <select
            value={filtros.status}
            onChange={(e) => setFiltros((f) => ({ ...f, status: e.target.value }))}
            aria-label="Filtrar por status"
          >
            <option value="">Todos os status</option>
            <option value="ativa">Ativas</option>
            <option value="cancelada">Canceladas</option>
          </select>
        </div>

        {erro && <div className="alert alert--erro">{erro}</div>}

        <div className="table-wrapper">
          <table>
            <thead>
              <tr>
                <th>Nº apólice</th>
                <th>Segurado</th>
                <th>Destino / Plano</th>
                <th>Vigência</th>
                <th className="num">Prêmio</th>
                <th>Status</th>
                <th aria-label="Ações" />
              </tr>
            </thead>
            <tbody>
              {apolices.map((apolice) => (
                <tr key={apolice.id} onClick={() => navigate(`/apolices/${apolice.id}`)}>
                  <td className="mono">{apolice.numero}</td>
                  <td>
                    <strong>{apolice.seguradoNome}</strong>
                    <small className="muted block">{apolice.seguradoCpf}</small>
                  </td>
                  <td>
                    {apolice.destinoLabel}
                    <small className="muted block">{apolice.planoLabel}</small>
                  </td>
                  <td>
                    {formatarData(apolice.inicioVigencia)} a {formatarData(apolice.fimVigencia)}
                    <small className="muted block">{apolice.dias} dias</small>
                  </td>
                  <td className="num">{formatarMoeda(apolice.valorPremioCentavos)}</td>
                  <td><StatusBadge status={apolice.status} label={apolice.statusLabel} /></td>
                  <td onClick={(e) => e.stopPropagation()}>
                    <div className="acoes">
                      <Link to={`/apolices/${apolice.id}/editar`} className="btn btn--small btn--ghost">Editar</Link>
                      <button type="button" className="btn btn--small btn--ghost-danger" onClick={() => setParaExcluir(apolice)}>
                        Excluir
                      </button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>

          {!carregando && !erro && apolices.length === 0 && (
            <p className="vazio">Nenhuma apólice encontrada.</p>
          )}
          {carregando && apolices.length === 0 && <p className="vazio">Carregando...</p>}
        </div>
      </div>

      <ConfirmDialog
        aberto={Boolean(paraExcluir)}
        titulo="Excluir apólice"
        mensagem={paraExcluir && `Deseja realmente excluir a apólice ${paraExcluir.numero} de ${paraExcluir.seguradoNome}? Esta ação não pode ser desfeita.`}
        textoConfirmar="Excluir"
        carregando={excluindo}
        onConfirmar={excluir}
        onCancelar={() => setParaExcluir(null)}
      />
    </>
  );
}
