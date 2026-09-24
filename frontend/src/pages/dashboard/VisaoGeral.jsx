import { CartesianGrid, Legend, Line, LineChart, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';
import KpiCard from '../../components/dashboard/KpiCard';
import Painel from '../../components/dashboard/Painel';
import { CORES, DicaGrafico, eixo } from '../../components/dashboard/graficos';
import { formatarMes, formatarMoeda, formatarMoedaCompacta } from '../../utils/format';

export default function VisaoGeral({ dados }) {
  const { kpis } = dados;

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

      <Painel titulo="Prêmio emitido por mês" subtitulo="Últimos 12 meses comparados ao mesmo mês do ano anterior. O mês atual ainda está em andamento.">
        <ResponsiveContainer width="100%" height={320}>
          <LineChart data={dados.premioMensal} margin={{ top: 8, right: 24, bottom: 0, left: 8 }}>
            <CartesianGrid vertical={false} stroke={CORES.grade} />
            <XAxis dataKey="mes" {...eixo} tickFormatter={formatarMes} />
            <YAxis {...eixo} tickFormatter={formatarMoedaCompacta} width={90} />
            <Tooltip
              content={
                <DicaGrafico
                  formatarRotulo={formatarMes}
                  linhas={(mes) => [
                    ['Este ano', formatarMoeda(mes.atualCentavos), CORES.serie],
                    ['Ano anterior', formatarMoeda(mes.anoAnteriorCentavos), CORES.comparacao],
                  ]}
                />
              }
            />
            <Legend verticalAlign="top" align="right" iconType="plainline" height={32} wrapperStyle={{ fontSize: 13 }} />
            <Line name="Ano anterior" dataKey="anoAnteriorCentavos" stroke={CORES.comparacao} strokeWidth={2} dot={false} />
            <Line name="Este ano" dataKey="atualCentavos" stroke={CORES.serie} strokeWidth={2} dot={{ r: 4, fill: CORES.serie, strokeWidth: 2, stroke: '#fff' }} activeDot={{ r: 5 }} />
          </LineChart>
        </ResponsiveContainer>
      </Painel>
    </div>
  );
}
