import { useState } from 'react';
import { Area, CartesianGrid, ComposedChart, Line, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';
import { formatarMes, formatarMoeda, formatarMoedaCompacta, formatarNumero, formatarPercentual } from '../../utils/format';
import Painel from './Painel';
import Segmentado from './Segmentado';
import { CORES, DicaGrafico, eixo } from './graficos';

const METRICAS = [
  { id: 'premio', label: 'Prêmio', eixo: formatarMoedaCompacta, completo: (valor) => (valor == null ? '—' : formatarMoeda(valor)) },
  { id: 'apolices', label: 'Apólices', eixo: formatarNumero, completo: formatarNumero },
  { id: 'ticket', label: 'Ticket médio', eixo: formatarMoedaCompacta, completo: (valor) => (valor == null ? '—' : formatarMoeda(valor)) },
];

const DETALHES = [
  { id: 'total', label: 'Total' },
  { id: 'canal', label: 'Canal' },
  { id: 'plano', label: 'Plano' },
];

const MODOS = [
  { id: 'grafico', label: 'Gráfico' },
  { id: 'tabela', label: 'Tabela' },
];

/** Valor da métrica escolhida a partir de { premioCentavos, apolices }. */
function valorDa(metrica, soma) {
  if (!soma) return metrica === 'ticket' ? null : 0;
  if (metrica === 'premio') return soma.premioCentavos;
  if (metrica === 'apolices') return soma.apolices;
  return soma.apolices > 0 ? Math.round(soma.premioCentavos / soma.apolices) : null;
}

function somarMeses(meses, chave) {
  return meses.reduce(
    (total, mes) => ({ premioCentavos: total.premioCentavos + mes[chave].premioCentavos, apolices: total.apolices + mes[chave].apolices }),
    { premioCentavos: 0, apolices: 0 },
  );
}

export default function GraficoMensal({ serie }) {
  const [metricaId, setMetrica] = useState('premio');
  const [detalhe, setDetalhe] = useState('total');
  const [comparar, setComparar] = useState(true);
  const [modo, setModo] = useState('grafico');

  const metrica = METRICAS.find((item) => item.id === metricaId);
  const categorias = detalhe === 'canal' ? serie.canais : detalhe === 'plano' ? serie.planos : [];
  const campoCategoria = detalhe === 'canal' ? 'porCanal' : 'porPlano';
  const empilhar = metricaId !== 'ticket'; // prêmio e apólices somam; ticket médio não
  const ultimoMes = serie.meses.length - 1;

  const linhas = serie.meses.map((mes, indice) => ({
    mes: mes.mes,
    parcial: indice === ultimoMes,
    atual: valorDa(metricaId, mes.atual),
    anoAnterior: valorDa(metricaId, mes.anoAnterior),
    ...Object.fromEntries(categorias.map((categoria) => [categoria, valorDa(metricaId, mes[campoCategoria][categoria])])),
  }));

  return (
    <Painel titulo="Evolução mês a mês" subtitulo="Últimos 12 meses. O mês atual ainda está em andamento (ponto vazado)." className="span-todas">
      <div className="controles">
        <Segmentado rotulo="Métrica" opcoes={METRICAS} valor={metricaId} onMudar={setMetrica} />
        <Segmentado rotulo="Detalhar por" opcoes={DETALHES} valor={detalhe} onMudar={setDetalhe} />
        <label className={`alternar ${detalhe !== 'total' ? 'alternar--desativado' : ''}`}>
          <input type="checkbox" checked={comparar && detalhe === 'total'} disabled={detalhe !== 'total'} onChange={(event) => setComparar(event.target.checked)} />
          Comparar com o ano anterior
        </label>
        <Segmentado rotulo="Ver como" opcoes={MODOS} valor={modo} onMudar={setModo} />
      </div>

      <Resumo serie={serie} metrica={metrica} />

      {modo === 'grafico' && (
        <ul className="legenda-series">
          {(categorias.length > 0
            ? categorias.map((categoria, indice) => [categoria, CORES.categorias[indice]])
            : [['Este ano', CORES.serie], ...(comparar ? [['Ano anterior', CORES.comparacao]] : [])]
          ).map(([nome, cor]) => (
            <li key={nome}>
              <span className="legenda-series__cor" style={{ background: cor }} />
              {nome}
            </li>
          ))}
        </ul>
      )}

      {modo === 'tabela' ? (
        <TabelaMensal linhas={linhas} categorias={categorias} metrica={metrica} comparar={comparar && detalhe === 'total'} />
      ) : (
        <ResponsiveContainer width="100%" height={340}>
          <ComposedChart data={linhas} margin={{ top: 8, right: 24, bottom: 0, left: 8 }}>
            <defs>
              <linearGradient id="preenchimento-atual" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" stopColor={CORES.serie} stopOpacity={0.28} />
                <stop offset="100%" stopColor={CORES.serie} stopOpacity={0.02} />
              </linearGradient>
            </defs>
            <CartesianGrid vertical={false} stroke={CORES.grade} />
            <XAxis dataKey="mes" {...eixo} tickFormatter={formatarMes} />
            <YAxis {...eixo} tickFormatter={metrica.eixo} width={90} />
            <Tooltip content={<DicaMensal metrica={metrica} categorias={categorias} comparar={comparar} />} />

            {detalhe === 'total' && comparar && (
              <Line name="Ano anterior" dataKey="anoAnterior" stroke={CORES.comparacao} strokeWidth={2} dot={false} activeDot={{ r: 4 }} />
            )}
            {detalhe === 'total' && (
              <Area
                name="Este ano"
                dataKey="atual"
                stroke={CORES.serie}
                strokeWidth={2.5}
                fill="url(#preenchimento-atual)"
                dot={<PontoDoMes />}
                activeDot={{ r: 6, stroke: '#fff', strokeWidth: 2 }}
              />
            )}
            {detalhe !== 'total' &&
              categorias.map((categoria, indice) =>
                empilhar ? (
                  <Area
                    key={categoria}
                    name={categoria}
                    dataKey={categoria}
                    stackId="total"
                    stroke="#fff"
                    strokeWidth={1.5}
                    fill={CORES.categorias[indice]}
                    fillOpacity={0.9}
                  />
                ) : (
                  <Line key={categoria} name={categoria} dataKey={categoria} stroke={CORES.categorias[indice]} strokeWidth={2} dot={false} connectNulls />
                ),
              )}
          </ComposedChart>
        </ResponsiveContainer>
      )}
    </Painel>
  );
}

/** Ponto de cada mês; o mês atual (incompleto) fica vazado. */
function PontoDoMes({ cx, cy, payload }) {
  if (cx == null || cy == null) return null;
  return (
    <circle cx={cx} cy={cy} r={4} stroke={CORES.serie} strokeWidth={2} fill={payload.parcial ? '#fff' : CORES.serie} />
  );
}

function DicaMensal({ active, payload, label, metrica, categorias, comparar }) {
  if (!active || !payload?.length) return null;
  const linha = payload[0].payload;
  const titulo = `${formatarMes(label)}${linha.parcial ? ' · em andamento' : ''}`;

  const totalDoMes = categorias.reduce((soma, categoria) => soma + (linha[categoria] ?? 0), 0);
  const itens =
    categorias.length > 0
      ? [
          ...categorias.map((categoria, indice) => [categoria, metrica.completo(linha[categoria]), CORES.categorias[indice]]),
          ...(metrica.id !== 'ticket' ? [['Total', metrica.completo(totalDoMes)]] : []),
        ]
      : [
          ['Este ano', metrica.completo(linha.atual), CORES.serie],
          ...(comparar ? [['Ano anterior', metrica.completo(linha.anoAnterior), CORES.comparacao]] : []),
        ];

  if (categorias.length === 0 && comparar && linha.anoAnterior) {
    itens.push(['Variação', formatarPercentual((linha.atual - linha.anoAnterior) / linha.anoAnterior)]);
  }

  return <DicaGrafico active label={titulo} payload={payload} linhas={() => itens} />;
}

/** Números-resumo acima do gráfico: total, melhor mês e comparação com o ano anterior. */
function Resumo({ serie, metrica }) {
  const fechados = serie.meses.slice(0, -1); // o mês atual ainda não terminou
  const atual = valorDa(metrica.id, somarMeses(serie.meses, 'atual'));
  const atualFechados = valorDa(metrica.id, somarMeses(fechados, 'atual'));
  const anterior = valorDa(metrica.id, somarMeses(fechados, 'anoAnterior'));
  const variacao = anterior ? (atualFechados - anterior) / anterior : null;
  const melhor = fechados.reduce((maior, mes) => (valorDa(metrica.id, mes.atual) > valorDa(metrica.id, maior.atual) ? mes : maior), fechados[0]);

  return (
    <div className="resumo-serie">
      <div>
        <span className="muted">{metrica.id === 'ticket' ? 'Ticket médio dos 12 meses do gráfico' : 'Total dos 12 meses do gráfico'}</span>
        <strong>{metrica.completo(atual)}</strong>
      </div>
      {melhor && (
        <div>
          <span className="muted">Melhor mês</span>
          <strong>
            {formatarMes(melhor.mes)} · {metrica.eixo(valorDa(metrica.id, melhor.atual))}
          </strong>
        </div>
      )}
      <div>
        <span className="muted">vs. mesmos meses do ano anterior</span>
        <strong className={variacao == null ? '' : variacao >= 0 ? 'texto-bom' : 'texto-ruim'}>
          {variacao == null ? '—' : `${variacao >= 0 ? '▲' : '▼'} ${formatarPercentual(Math.abs(variacao))}`}
        </strong>
      </div>
    </div>
  );
}

function TabelaMensal({ linhas, categorias, metrica, comparar }) {
  return (
    <div className="table-wrapper">
      <table>
        <thead>
          <tr>
            <th>Mês</th>
            {categorias.length > 0 ? (
              categorias.map((categoria) => (
                <th key={categoria} className="num">
                  {categoria}
                </th>
              ))
            ) : (
              <>
                <th className="num">Este ano</th>
                {comparar && <th className="num">Ano anterior</th>}
                {comparar && <th className="num">Variação</th>}
              </>
            )}
          </tr>
        </thead>
        <tbody>
          {linhas.map((linha) => (
            <tr key={linha.mes}>
              <td>
                <strong>{formatarMes(linha.mes)}</strong>
                {linha.parcial && <span className="muted"> (em andamento)</span>}
              </td>
              {categorias.length > 0 ? (
                categorias.map((categoria) => (
                  <td key={categoria} className="num">
                    {metrica.completo(linha[categoria])}
                  </td>
                ))
              ) : (
                <>
                  <td className="num">{metrica.completo(linha.atual)}</td>
                  {comparar && <td className="num">{metrica.completo(linha.anoAnterior)}</td>}
                  {comparar && (
                    <td className="num">{linha.anoAnterior ? formatarPercentual((linha.atual - linha.anoAnterior) / linha.anoAnterior) : '—'}</td>
                  )}
                </>
              )}
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}
