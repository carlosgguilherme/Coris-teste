import { useSearchParams } from 'react-router-dom';
import { CarregandoDashboard } from '../components/dashboard/EstadoDashboard';
import { useDashboard } from '../hooks/useDashboard';
import Comercial from './dashboard/Comercial';
import Marketing from './dashboard/Marketing';
import Sinistros from './dashboard/Sinistros';
import VisaoGeral from './dashboard/VisaoGeral';

const VISOES = [
  { id: 'visao-geral', label: 'Visão geral', componente: VisaoGeral },
  { id: 'marketing', label: 'Marketing', componente: Marketing },
  { id: 'comercial', label: 'Comercial', componente: Comercial },
  { id: 'sinistros', label: 'Sinistros e atendimento', componente: Sinistros },
];

const PERIODOS = [
  { id: '30d', label: 'Últimos 30 dias' },
  { id: '90d', label: 'Últimos 90 dias' },
  { id: '12m', label: 'Últimos 12 meses' },
  { id: '24m', label: 'Últimos 24 meses' },
];

export default function Dashboard() {
  // A visão e o período ficam na URL, então dá para compartilhar o link da tela
  const [parametros, setParametros] = useSearchParams();
  const visao = VISOES.find((item) => item.id === parametros.get('visao')) ?? VISOES[0];
  const periodo = PERIODOS.find((item) => item.id === parametros.get('periodo'))?.id ?? '12m';
  const { dados, erro, carregando } = useDashboard(visao.id, periodo);

  const alterar = (chave, valor) =>
    setParametros((atuais) => {
      const novos = new URLSearchParams(atuais);
      novos.set(chave, valor);
      return novos;
    });

  const Visao = visao.componente;

  return (
    <>
      <div className="page-header">
        <div>
          <h1>Dashboard</h1>
          <p className="muted"></p>
        </div>
        <label className="filtro-periodo">
          <span className="muted">Período</span>
          <select value={periodo} onChange={(event) => alterar('periodo', event.target.value)}>
            {PERIODOS.map((item) => (
              <option key={item.id} value={item.id}>
                {item.label}
              </option>
            ))}
          </select>
        </label>
      </div>

      <nav className="abas" role="tablist" aria-label="Visões da dashboard">
        {VISOES.map((item) => (
          <button
            key={item.id}
            type="button"
            role="tab"
            aria-selected={item.id === visao.id}
            className={item.id === visao.id ? 'aba aba--ativa' : 'aba'}
            onClick={() => alterar('visao', item.id)}
          >
            {item.label}
          </button>
        ))}
      </nav>

      {erro && <div className="alert alert--erro">{erro}</div>}
      {carregando && <CarregandoDashboard />}
      {dados && <Visao dados={dados} />}
    </>
  );
}
