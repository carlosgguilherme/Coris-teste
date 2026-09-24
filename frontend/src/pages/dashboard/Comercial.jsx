import { Cell, Pie, PieChart, ResponsiveContainer, Tooltip } from 'recharts';
import Painel from '../../components/dashboard/Painel';
import { CORES, DicaGrafico } from '../../components/dashboard/graficos';
import { formatarMoeda, formatarMoedaCompacta, formatarNumero, formatarPercentual } from '../../utils/format';

export default function Comercial({ dados }) {
  const maiorPremio = Math.max(...dados.canais.map((canal) => canal.premioCentavos), 1);
  const totalApolices = dados.planos.reduce((soma, plano) => soma + plano.apolices, 0);

  return (
    <div className="dash-grid dash-grid--2">
      <Painel titulo="Vendas por canal" subtitulo="Prêmio emitido e ticket médio de cada canal de venda." className="span-todas">
        <div className="table-wrapper">
          <table>
            <thead>
              <tr>
                <th>Canal</th>
                <th className="num">Apólices</th>
                <th className="num">Ticket médio</th>
                <th>Prêmio emitido</th>
              </tr>
            </thead>
            <tbody>
              {dados.canais.map((canal) => (
                <tr key={canal.canal}>
                  <td><strong>{canal.canal}</strong></td>
                  <td className="num">{formatarNumero(canal.apolices)}</td>
                  <td className="num">{formatarMoeda(canal.ticketMedioCentavos)}</td>
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

      <Painel titulo="Mix de planos" subtitulo="Participação de cada plano nas apólices emitidas.">
        <div className="donut">
          <ResponsiveContainer width="100%" height={220}>
            <PieChart>
              <Pie data={dados.planos} dataKey="apolices" nameKey="plano" innerRadius="62%" outerRadius="90%" paddingAngle={1} stroke="#fff" strokeWidth={2}>
                {dados.planos.map((plano, indice) => (
                  <Cell key={plano.plano} fill={CORES.categorias[indice]} />
                ))}
              </Pie>
              <Tooltip
                content={
                  <DicaGrafico
                    linhas={(plano) => [
                      [plano.plano, formatarNumero(plano.apolices)],
                      ['Prêmio', formatarMoeda(plano.premioCentavos)],
                    ]}
                  />
                }
              />
            </PieChart>
          </ResponsiveContainer>
          <ul className="legenda">
            {dados.planos.map((plano, indice) => (
              <li key={plano.plano}>
                <span className="legenda__cor" style={{ background: CORES.categorias[indice] }} />
                <strong>{plano.plano}</strong>
                <span>{formatarPercentual(plano.apolices / (totalApolices || 1), 0)}</span>
              </li>
            ))}
          </ul>
        </div>
      </Painel>

      <Painel titulo="Ticket médio por plano" subtitulo="Quanto cada plano rende, em média, por apólice.">
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
