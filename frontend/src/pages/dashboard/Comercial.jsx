import { Bar, BarChart, CartesianGrid, LabelList, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';
import KpiCard from '../../components/dashboard/KpiCard';
import Painel from '../../components/dashboard/Painel';
import { BarrasHorizontais, CORES, DicaGrafico, eixo } from '../../components/dashboard/graficos';
import { formatarMoeda, formatarMoedaCompacta, formatarNumero, formatarPercentual } from '../../utils/format';

/** Topo do eixo com 15% de folga acima da maior coluna (para o percentual caber), arredondado. Ex.: 600 vira 800. */
function limiteComFolga(maior) {
  const passo = 2 * 10 ** Math.floor(Math.log10(Math.max(maior, 1)));
  return Math.ceil((maior * 1.15) / passo) * passo;
}

export default function Comercial({ dados }) {
  const maiorPremio = Math.max(...dados.canais.map((canal) => canal.premioCentavos), 1);
  const totalApolices = dados.planos.reduce((soma, plano) => soma + plano.apolices, 0);
  const planos = dados.planos.map((plano) => ({ ...plano, participacao: plano.apolices / (totalApolices || 1) }));

  const { kpis } = dados;

  return (
    <div className="dash-grid dash-grid--2">
      <div className="kpis span-todas">
        <KpiCard titulo="Prêmio emitido" formato="moedaCompacta" {...kpis.premioEmitidoCentavos} ajuda="Soma dos prêmios das apólices vendidas no período (sem as canceladas)." />
        <KpiCard titulo="Apólices vendidas" {...kpis.apolices} />
        <KpiCard titulo="Ticket médio" formato="moeda" {...kpis.ticketMedioCentavos} ajuda="Prêmio emitido ÷ apólices vendidas." />
        <KpiCard titulo="Cancelamentos" melhorQuando="menor" {...kpis.canceladas} ajuda="Apólices vendidas no período que foram canceladas." />
      </div>

      <Painel titulo="Vendas por canal" subtitulo="Quanto cada canal vendeu e quanto representa do total." className="span-todas">
        <div className="table-wrapper">
          <table>
            <thead>
              <tr>
                <th>Canal</th>
                <th className="num">Apólices</th>
                <th className="num">Ticket médio</th>
                <th className="num">Participação</th>
                <th>Prêmio emitido</th>
              </tr>
            </thead>
            <tbody>
              {dados.canais.map((canal) => (
                <tr key={canal.canal}>
                  <td><strong>{canal.canal}</strong></td>
                  <td className="num">{formatarNumero(canal.apolices)}</td>
                  <td className="num">{formatarMoeda(canal.ticketMedioCentavos)}</td>
                  <td className="num">{formatarPercentual(canal.participacao)}</td>
                  <td className="celula-barra">
                    <div className="barra-trilho">
                      <div className="barra-inline" style={{ width: `${(canal.premioCentavos / maiorPremio) * 100}%` }} />
                    </div>
                    <span>{formatarMoedaCompacta(canal.premioCentavos)}</span>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </Painel>

      <Painel titulo="Vendas por destino" subtitulo="Prêmio emitido para cada destino.">
        <BarrasHorizontais
          dados={dados.destinos}
          rotulo="destino"
          valor="premioCentavos"
          formatar={formatarMoedaCompacta}
          dica={(item) => [
            ['Prêmio', formatarMoeda(item.premioCentavos)],
            ['Apólices', formatarNumero(item.apolices)],
            ['Ticket médio', formatarMoeda(item.ticketMedioCentavos)],
          ]}
        />
      </Painel>

      <Painel titulo="Mix de planos" subtitulo="Participação de cada plano nas apólices vendidas.">
        <ResponsiveContainer width="100%" height={260}>
          <BarChart data={planos} margin={{ top: 24, right: 8, bottom: 0, left: 0 }}>
            <CartesianGrid vertical={false} stroke={CORES.grade} />
            <XAxis dataKey="plano" {...eixo} />
            <YAxis {...eixo} tickFormatter={formatarNumero} width={48} allowDecimals={false} domain={[0, limiteComFolga]} />
            <Tooltip
              cursor={{ fill: '#eef3f9' }}
              content={
                <DicaGrafico
                  linhas={(plano) => [
                    ['Apólices', formatarNumero(plano.apolices)],
                    ['Participação', formatarPercentual(plano.participacao)],
                    ['Prêmio', formatarMoeda(plano.premioCentavos)],
                  ]}
                />
              }
            />
            <Bar dataKey="apolices" fill={CORES.serie} barSize={56} radius={[4, 4, 0, 0]}>
              <LabelList dataKey="participacao" position="top" formatter={(valor) => formatarPercentual(valor, 0)} fill="#1b2533" fontSize={13} fontWeight={600} />
            </Bar>
          </BarChart>
        </ResponsiveContainer>
      </Painel>

      <Painel titulo="Ticket médio por plano" subtitulo="Quanto cada plano rende, em média, por apólice." className="span-todas">
        <div className="destaques">
          {dados.planos.map((plano) => (
            <div key={plano.plano} className="destaque">
              <span className="muted">{plano.plano}</span>
              <strong>{formatarMoeda(plano.ticketMedioCentavos)}</strong>
              <span className="muted">{formatarNumero(plano.apolices)} apólices</span>
            </div>
          ))}
        </div>
      </Painel>
    </div>
  );
}
