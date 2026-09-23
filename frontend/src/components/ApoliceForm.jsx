import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { apolicesApi } from '../api/apolices';
import { ApiError } from '../api/client';
import { useOpcoes } from '../hooks/useOpcoes';
import { formatarMoeda, mascaraCpf } from '../utils/format';

const VAZIO = {
  seguradoNome: '',
  seguradoCpf: '',
  seguradoEmail: '',
  seguradoNascimento: '',
  destino: '',
  plano: '',
  inicioVigencia: '',
  fimVigencia: '',
  status: 'ativa',
};

const CAMPOS_COTACAO = ['seguradoNome', 'seguradoCpf', 'seguradoEmail', 'seguradoNascimento', 'destino', 'plano', 'inicioVigencia', 'fimVigencia'];

export default function ApoliceForm({ inicial, edicao = false, textoBotao, onSubmit }) {
  const { opcoes } = useOpcoes();
  const [form, setForm] = useState({ ...VAZIO, ...inicial });
  const [erros, setErros] = useState({});
  const [erroGeral, setErroGeral] = useState(null);
  const [enviando, setEnviando] = useState(false);
  const cotacao = useCotacao(form);

  const alterar = (campo) => (event) => {
    const valor = campo === 'seguradoCpf' ? mascaraCpf(event.target.value) : event.target.value;
    setForm((atual) => ({ ...atual, [campo]: valor }));
    setErros((atuais) => ({ ...atuais, [campo]: undefined }));
  };

  async function enviar(event) {
    event.preventDefault();
    setEnviando(true);
    setErroGeral(null);

    try {
      await onSubmit(edicao ? form : { ...form, status: undefined });
    } catch (error) {
      if (error instanceof ApiError && Object.keys(error.errors).length > 0) {
        setErros(error.errors);
      } else {
        setErroGeral(error.message);
      }
    } finally {
      setEnviando(false);
    }
  }

  const campo = (nome) => ({
    id: nome,
    value: form[nome],
    onChange: alterar(nome),
    'aria-invalid': Boolean(erros[nome]),
  });

  return (
    <form className="form-layout" onSubmit={enviar} noValidate>
      <div className="card form-card">
        {erroGeral && <div className="alert alert--erro">{erroGeral}</div>}

        <fieldset>
          <legend>Dados do segurado</legend>
          <div className="grid">
            <Campo label="Nome completo" erro={erros.seguradoNome} className="span-2">
              <input {...campo('seguradoNome')} autoComplete="name" />
            </Campo>
            <Campo label="CPF" erro={erros.seguradoCpf}>
              <input {...campo('seguradoCpf')} inputMode="numeric" placeholder="000.000.000-00" />
            </Campo>
            <Campo label="Data de nascimento" erro={erros.seguradoNascimento}>
              <input {...campo('seguradoNascimento')} type="date" />
            </Campo>
            <Campo label="E-mail" erro={erros.seguradoEmail} className="span-2">
              <input {...campo('seguradoEmail')} type="email" autoComplete="email" />
            </Campo>
          </div>
        </fieldset>

        <fieldset>
          <legend>Viagem e cobertura</legend>
          <div className="grid">
            <Campo label="Destino" erro={erros.destino}>
              <select {...campo('destino')}>
                <option value="">Selecione...</option>
                {opcoes?.destinos.map((destino) => (
                  <option key={destino.valor} value={destino.valor}>{destino.label}</option>
                ))}
              </select>
            </Campo>
            <Campo label="Plano" erro={erros.plano}>
              <select {...campo('plano')}>
                <option value="">Selecione...</option>
                {opcoes?.planos.map((plano) => (
                  <option key={plano.valor} value={plano.valor}>
                    {plano.label} — {formatarMoeda(plano.valorDiaria)}/dia
                  </option>
                ))}
              </select>
            </Campo>
            <Campo label="Início da vigência" erro={erros.inicioVigencia}>
              <input {...campo('inicioVigencia')} type="date" />
            </Campo>
            <Campo label="Fim da vigência" erro={erros.fimVigencia}>
              <input {...campo('fimVigencia')} type="date" min={form.inicioVigencia || undefined} />
            </Campo>
            {edicao && (
              <Campo label="Status" erro={erros.status}>
                <select {...campo('status')}>
                  {opcoes?.status.map((status) => (
                    <option key={status.valor} value={status.valor}>{status.label}</option>
                  ))}
                </select>
              </Campo>
            )}
          </div>
        </fieldset>
      </div>

      <aside className="card resumo">
        <h2>Prêmio estimado</h2>
        {cotacao ? (
          <>
            <p className="resumo__valor">{formatarMoeda(cotacao.valorPremio)}</p>
            <p className="muted">{cotacao.dias} {cotacao.dias === 1 ? 'dia' : 'dias'} de cobertura</p>
          </>
        ) : (
          <p className="muted">Preencha os dados para calcular o valor do seguro.</p>
        )}

        <PlanoSelecionado opcoes={opcoes} plano={form.plano} />

        <div className="resumo__acoes">
          <button type="submit" className="btn btn--primary" disabled={enviando}>
            {enviando ? 'Salvando...' : textoBotao}
          </button>
          <Link to="/apolices" className="btn btn--ghost">Voltar</Link>
        </div>
      </aside>
    </form>
  );
}

function Campo({ label, erro, className = '', children }) {
  return (
    <label className={`field ${className}`}>
      <span>{label}</span>
      {children}
      {erro && <small className="field__erro">{erro}</small>}
    </label>
  );
}

function PlanoSelecionado({ opcoes, plano }) {
  const selecionado = opcoes?.planos.find((item) => item.valor === plano);
  if (!selecionado) return null;

  return (
    <dl className="resumo__plano">
      <dt>Plano {selecionado.label}</dt>
      <dd>Cobertura médica de até {formatarMoeda(selecionado.coberturaMedica)}</dd>
    </dl>
  );
}

function useCotacao(form) {
  const [cotacao, setCotacao] = useState(null);
  const completo = CAMPOS_COTACAO.every((campo) => form[campo]);
  const chave = CAMPOS_COTACAO.map((campo) => form[campo]).join('|');

  useEffect(() => {
    if (!completo) {
      setCotacao(null);
      return undefined;
    }

    const controller = new AbortController();
    const timer = setTimeout(() => {
      apolicesApi
        .cotar(form, controller.signal)
        .then(setCotacao)
        .catch((error) => {
          if (error.name !== 'AbortError') setCotacao(null);
        });
    }, 400);

    return () => {
      clearTimeout(timer);
      controller.abort();
    };
  }, [chave, completo]);

  return cotacao;
}
