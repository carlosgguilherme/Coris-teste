import { CartesianGrid, Line, LineChart, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';
import KpiCard from '../../components/dashboard/KpiCard';
import Painel from '../../components/dashboard/Painel';
import { BarrasHorizontais, CORES, DicaGrafico, eixo } from '../../components/dashboard/graficos';
import { formatarMes, formatarMoeda, formatarMoedaCompacta, formatarNumero, formatarPercentual, formatarSegundos } from '../../utils/format';

const META_SINISTRALIDADE = 0.6;

export default function Sinistros({ dados }) {
  const { kpis, atendimento } = dados;

  return (
    <div className="dash-grid dash-grid--2">
      <div className="kpis span-todas">
        <KpiCard titulo="Sinistros avisados" valor={kpis.sinistros} />
        <KpiCard titulo="Frequência" formato="percentual" valor={kpis.frequencia} ajuda="Sinistros avisados ÷ apólices emitidas." />
        <KpiCard titulo="Custo médio (severidade)" formato="moeda" valor={kpis.severidadeCentavos} />
        <KpiCard titulo="Sinistralidade" formato="percentual" valor={kpis.sinistralidade} ajuda="Custo dos sinistros ÷ prêmio ganho." />
        <KpiCard titulo="Taxa de negativa" formato="percentual" valor={kpis.taxaNegativa} ajuda="Sinistros negados ÷ sinistros finalizados." />
      </div>

      <Painel titulo="Sinistralidade por destino" subtitulo="A linha vermelha é a meta de 60%.">
        <BarrasHorizontais
          dados={dados.porDestino}
          rotulo="destino"
          valor="sinistralidade"
          formatar={(valor) => formatarPercentual(valor, 0)}
          referencia={{ valor: META_SINISTRALIDADE, texto: 'Meta 60%' }}
          dica={(item) => [['Sinistralidade', formatarPercentual(item.sinistralidade)], ['Sinistros', formatarNumero(item.sinistros)]]}
        />
      </Painel>

      <Painel titulo="Custo por cobertura" subtitulo="Quanto cada tipo de cobertura custou.">
        <BarrasHorizontais
          dados={dados.porCobertura}
          rotulo="cobertura"
          valor="custoCentavos"
          formatar={formatarMoedaCompacta}
          dica={(item) => [['Custo', formatarMoeda(item.custoCentavos)], ['Sinistros', formatarNumero(item.sinistros)]]}
        />
      </Painel>

      <Painel titulo="Situação dos sinistros" subtitulo="Sinistros avisados no período, por status.">
        <ul className="lista-status">
          {dados.status.map((item) => (
            <li key={item.status}>
              <span>{item.status}</span>
              <strong>{formatarNumero(item.total)}</strong>
            </li>
          ))}
        </ul>
      </Painel>

      <Painel titulo="Central de atendimento 24h" subtitulo={`${formatarNumero(atendimento.atendimentos)} atendimentos · SLA: espera de até 3 minutos.`}>
        <div className="destaques">
          <div className="destaque">
            <span className="muted">NPS</span>
            <strong>{atendimento.nps ?? '—'}</strong>
          </div>
          <div className="destaque">
            <span className="muted">Dentro do SLA</span>
            <strong>{formatarPercentual(atendimento.sla)}</strong>
          </div>
        </div>
        <div className="table-wrapper">
          <table>
            <thead>
              <tr>
                <th>Canal</th>
                <th className="num">Atendimentos</th>
                <th className="num">Espera média</th>
                <th className="num">No SLA</th>
              </tr>
            </thead>
            <tbody>
              {atendimento.porCanal.map((canal) => (
                <tr key={canal.canal}>
                  <td><strong>{canal.canal}</strong></td>
                  <td className="num">{formatarNumero(canal.atendimentos)}</td>
                  <td className="num">{formatarSegundos(canal.esperaMediaSeg)}</td>
                  <td className="num">{formatarPercentual(canal.sla, 0)}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </Painel>

      <Painel titulo="NPS mês a mês" subtitulo="Últimos 12 meses, independente do período escolhido." className="span-todas">
        <ResponsiveContainer width="100%" height={240}>
          <LineChart data={atendimento.npsMensal} margin={{ top: 8, right: 24, bottom: 0, left: 0 }}>
            <CartesianGrid vertical={false} stroke={CORES.grade} />
            <XAxis dataKey="mes" {...eixo} tickFormatter={formatarMes} />
            <YAxis {...eixo} domain={[0, 100]} width={40} />
            <Tooltip content={<DicaGrafico formatarRotulo={formatarMes} linhas={(mes) => [['NPS', mes.nps ?? '—']]} />} />
            <Line dataKey="nps" stroke={CORES.serie} strokeWidth={2} dot={{ r: 4, fill: CORES.serie, strokeWidth: 2, stroke: '#fff' }} connectNulls />
          </LineChart>
        </ResponsiveContainer>
      </Painel>
    </div>
  );
}
