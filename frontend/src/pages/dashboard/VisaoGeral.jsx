import GraficoMensal from '../../components/dashboard/GraficoMensal';
import KpiCard from '../../components/dashboard/KpiCard';

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

      <GraficoMensal serie={dados.serieMensal} />
    </div>
  );
}
