import { useState } from 'react';
import { Area, Bar, CartesianGrid, ComposedChart, Legend, Line, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';
import KpiCard from '../../components/dashboard/KpiCard';
import Painel from '../../components/dashboard/Painel';
import { CORES, DicaGrafico, eixo } from '../../components/dashboard/graficos';
import { formatarMes, formatarMoeda, formatarMoedaCompacta } from '../../utils/format';

const COR_ANO_ANTERIOR = '#eb6834';

export default function VisaoGeral({ dados }) {
  const { kpis } = dados;
  const [meses, setMeses] = useState(12);
  const [tipo, setTipo] = useState('linha');
  const [mostrarAnoAnterior, setMostrarAnoAnterior] = useState(true);

  const serie = dados.premioMensal.slice(-meses);

  return (
    <div className="dash-grid">
      <div className="kpis">
        <KpiCard titulo="Prêmio emitido" formato="moedaCompacta" {...kpis.premioEmitidoCentavos} ajuda="Soma dos prêmios das apólices emitidas no período (sem as canceladas)." />
        <KpiCard titulo="Apólices emitidas" {...kpis.apolices} />
        <KpiCard titulo="Ticket médio" formato="moeda" {...kpis.ticketMedioCentavos} ajuda="Prêmio emitido ÷ número de apólices." />
        <KpiCard titulo="Conversão" formato="percentual" {...kpis.conversao} ajuda="Cotações que viraram apólice ÷ cotações." />
        <KpiCard titulo="Sinistralidade" formato="percentual" melhorQuando="menor" {...kpis.sinistralidade} ajuda="Custo dos sinistros ÷ prêmio ganho no período." />
        <KpiCard titulo="NPS" formato="nps" {...kpis.nps} ajuda="% de promotores (notas 9 e 10) − % de detratores (notas 0 a 6)." />
      </div>

      <Painel titulo="Prêmio emitido por mês" subtitulo="Comparado ao mesmo mês do ano anterior. O mês atual ainda está em andamento.">
        <div className="filtros-grafico">
          <select value={meses} onChange={(event) => setMeses(Number(event.target.value))} aria-label="Período do gráfico">
            <option value={6}>Últimos 6 meses</option>
            <option value={12}>Últimos 12 meses</option>
          </select>
          <select value={tipo} onChange={(event) => setTipo(event.target.value)} aria-label="Tipo de gráfico">
            <option value="linha">Linha</option>
            <option value="colunas">Colunas</option>
          </select>
          <label>
            <input type="checkbox" checked={mostrarAnoAnterior} onChange={(event) => setMostrarAnoAnterior(event.target.checked)} />
            Mostrar ano anterior
          </label>
        </div>

        <ResponsiveContainer width="100%" height={320}>
          <ComposedChart data={serie} margin={{ top: 8, right: 24, bottom: 0, left: 8 }}>
            <defs>
              <linearGradient id="degrade-premio" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" stopColor={CORES.serie} stopOpacity={0.35} />
                <stop offset="100%" stopColor={CORES.serie} stopOpacity={0.02} />
              </linearGradient>
            </defs>
            <CartesianGrid vertical={false} stroke={CORES.grade} />
            <XAxis dataKey="mes" {...eixo} tickFormatter={formatarMes} />
            <YAxis {...eixo} tickFormatter={formatarMoedaCompacta} width={90} />
            <Tooltip
              cursor={{ fill: '#eef3f9' }}
              content={
                <DicaGrafico
                  formatarRotulo={formatarMes}
                  linhas={(mes) => [
                    ['Este ano', formatarMoeda(mes.atualCentavos), CORES.serie],
                    ...(mostrarAnoAnterior ? [['Ano anterior', formatarMoeda(mes.anoAnteriorCentavos), COR_ANO_ANTERIOR]] : []),
                  ]}
                />
              }
            />
            <Legend verticalAlign="top" align="right" height={32} wrapperStyle={{ fontSize: 13 }} />

            {tipo === 'linha' ? (
              <Area
                name="Este ano"
                dataKey="atualCentavos"
                stroke={CORES.serie}
                strokeWidth={2.5}
                fill="url(#degrade-premio)"
                dot={{ r: 4, fill: CORES.serie, strokeWidth: 2, stroke: '#fff' }}
                activeDot={{ r: 6 }}
              />
            ) : (
              <Bar name="Este ano" dataKey="atualCentavos" fill={CORES.serie} radius={[4, 4, 0, 0]} maxBarSize={28} />
            )}

            {mostrarAnoAnterior &&
              (tipo === 'linha' ? (
                <Line name="Ano anterior" dataKey="anoAnteriorCentavos" stroke={COR_ANO_ANTERIOR} strokeWidth={2} dot={false} activeDot={{ r: 5 }} />
              ) : (
                <Bar name="Ano anterior" dataKey="anoAnteriorCentavos" fill={COR_ANO_ANTERIOR} radius={[4, 4, 0, 0]} maxBarSize={28} />
              ))}
          </ComposedChart>
        </ResponsiveContainer>
      </Painel>
    </div>
  );
}
