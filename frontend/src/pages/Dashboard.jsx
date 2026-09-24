import { useSearchParams } from 'react-router-dom';
import { CarregandoDashboard } from '../components/dashboard/EstadoDashboard';
import { useDashboard } from '../hooks/useDashboard';
import { useOpcoes } from '../hooks/useOpcoes';
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
  { valor: '30d', label: 'Últimos 30 dias' },
  { valor: '90d', label: 'Últimos 90 dias' },
  { valor: '12m', label: 'Últimos 12 meses' },
  { valor: '24m', label: 'Últimos 24 meses' },
];

export default function Dashboard() {
  // Visão e filtros ficam na URL, então dá para compartilhar o link da tela
  const [parametros, setParametros] = useSearchParams();
  const { opcoes } = useOpcoes();
  const visao = VISOES.find((item) => item.id === parametros.get('visao')) ?? VISOES[0];

  const filtros = {
    periodo: PERIODOS.some((item) => item.valor === parametros.get('periodo')) ? parametros.get('periodo') : '12m',
    canal: parametros.get('canal') ?? '',
    plano: parametros.get('plano') ?? '',
    destino: parametros.get('destino') ?? '',
  };
  const { dados, erro, carregando } = useDashboard(visao.id, filtros);

  const campos = [
    { chave: 'canal', rotulo: 'Canal', todos: 'Todos os canais', opcoes: opcoes?.canais ?? [] },
    { chave: 'plano', rotulo: 'Plano', todos: 'Todos os planos', opcoes: opcoes?.planos ?? [] },
    { chave: 'destino', rotulo: 'Destino', todos: 'Todos os destinos', opcoes: opcoes?.destinos ?? [] },
  ];
  const ativos = campos.filter((campo) => filtros[campo.chave]);

  const alterar = (mudancas) =>
    setParametros((atuais) => {
      const novos = new URLSearchParams(atuais);
      Object.entries(mudancas).forEach(([chave, valor]) => (valor ? novos.set(chave, valor) : novos.delete(chave)));
      return novos;
    });

  const Visao = visao.componente;

  return (
    <>
      <div className="page-header">
        <div>
          <h1>Dashboard</h1>
          <p className="muted">Vendas, marketing, sinistros e atendimento em um só lugar.</p>
        </div>
      </div>

      <div className="card barra-filtros">
        <Filtro rotulo="Período" valor={filtros.periodo} opcoes={PERIODOS} onMudar={(valor) => alterar({ periodo: valor })} />
        {campos.map((campo) => (
          <Filtro
            key={campo.chave}
            rotulo={campo.rotulo}
            valor={filtros[campo.chave]}
            opcoes={[{ valor: '', label: campo.todos }, ...campo.opcoes]}
            onMudar={(valor) => alterar({ [campo.chave]: valor })}
          />
        ))}
      </div>

      {ativos.length > 0 && (
        <div className="chips" aria-label="Filtros ativos">
          {ativos.map((campo) => (
            <button key={campo.chave} type="button" className="chip" onClick={() => alterar({ [campo.chave]: '' })} title="Remover filtro">
              {campo.rotulo}: <strong>{campo.opcoes.find((opcao) => opcao.valor === filtros[campo.chave])?.label ?? filtros[campo.chave]}</strong>
              <span aria-hidden="true">×</span>
            </button>
          ))}
          <button type="button" className="chip chip--limpar" onClick={() => alterar({ canal: '', plano: '', destino: '' })}>
            Limpar filtros
          </button>
        </div>
      )}

      <nav className="abas" role="tablist" aria-label="Visões da dashboard">
        {VISOES.map((item) => (
          <button
            key={item.id}
            type="button"
            role="tab"
            aria-selected={item.id === visao.id}
            className={item.id === visao.id ? 'aba aba--ativa' : 'aba'}
            onClick={() => alterar({ visao: item.id })}
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

function Filtro({ rotulo, valor, opcoes, onMudar }) {
  return (
    <label className="filtro">
      <span>{rotulo}</span>
      <select value={valor} onChange={(event) => onMudar(event.target.value)}>
        {opcoes.map((opcao) => (
          <option key={opcao.valor} value={opcao.valor}>
            {opcao.label}
          </option>
        ))}
      </select>
    </label>
  );
}
