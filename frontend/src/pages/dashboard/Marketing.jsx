import { Bar, BarChart, CartesianGrid, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';
import Painel from '../../components/dashboard/Painel';
import { BarrasHorizontais, CORES, DicaGrafico, eixo } from '../../components/dashboard/graficos';
import { formatarMoeda, formatarMoedaCompacta, formatarNumero, formatarPercentual } from '../../utils/format';

export default function Marketing({ dados }) {
  return (
    <div className="dash-grid dash-grid--2">
      <Painel titulo="Funil de conversão" subtitulo="Quantas cotações chegaram a cada etapa até virar apólice.">
        <Funil etapas={dados.funil} />
      </Painel>

      <Painel titulo="Conversão por dispositivo" subtitulo="Cotações feitas no site e no app.">
        <div className="destaques">
          {dados.dispositivos.map((item) => (
            <div key={item.device} className="destaque">
              <span className="muted">{item.device}</span>
              <strong>{formatarPercentual(item.conversao)}</strong>
              <span className="muted">{formatarNumero(item.cotacoes)} cotações</span>
            </div>
          ))}
        </div>
      </Painel>

      <Painel titulo="Campanhas" subtitulo="ROI = (prêmio gerado − investimento) ÷ investimento." className="span-todas">
        <Campanhas campanhas={dados.campanhas} />
      </Painel>

      <Painel titulo="Destinos mais vendidos" subtitulo="Apólices emitidas por destino.">
        <BarrasHorizontais
          dados={dados.destinos}
          rotulo="destino"
          valor="apolices"
          formatar={formatarNumero}
          dica={(item) => [['Apólices', formatarNumero(item.apolices)], ['Prêmio', formatarMoeda(item.premioCentavos)]]}
        />
      </Painel>

      <Painel titulo="Antecedência da compra" subtitulo="Quantos dias antes da viagem o cliente contratou o seguro.">
        <ResponsiveContainer width="100%" height={260}>
          <BarChart data={dados.antecedencia} margin={{ top: 8, right: 8, bottom: 0, left: 0 }}>
            <CartesianGrid vertical={false} stroke={CORES.grade} />
            <XAxis dataKey="faixa" {...eixo} />
            <YAxis {...eixo} tickFormatter={formatarNumero} width={48} />
            <Tooltip cursor={{ fill: '#eef3f9' }} content={<DicaGrafico linhas={(item) => [['Apólices', formatarNumero(item.apolices)]]} />} />
            <Bar dataKey="apolices" fill={CORES.serie} barSize={36} radius={[4, 4, 0, 0]} />
          </BarChart>
        </ResponsiveContainer>
      </Painel>
    </div>
  );
}

function Funil({ etapas }) {
  const inicio = etapas[0]?.total || 1;
  // % que desistiu entre a etapa anterior e esta (null quando a anterior não teve ninguém)
  const quedas = etapas.slice(1).map((etapa, indice) => (etapas[indice].total > 0 ? 1 - etapa.total / etapas[indice].total : null));
  const maiorQueda = quedas.some((queda) => queda > 0) ? quedas.indexOf(Math.max(...quedas)) + 1 : -1;

  return (
    <ol className="funil">
      {etapas.map((etapa, indice) => (
        <li key={etapa.etapa} className={indice === maiorQueda ? 'funil__etapa funil__etapa--alerta' : 'funil__etapa'}>
          <div className="funil__texto">
            <span>{etapa.label}</span>
            <strong>{formatarNumero(etapa.total)}</strong>
          </div>
          <div className="funil__trilho">
            <div className="funil__barra" style={{ width: `${(etapa.total / inicio) * 100}%` }} />
          </div>
          {indice > 0 && quedas[indice - 1] != null && (
            <span className="funil__queda">
              {indice === maiorQueda && <span aria-hidden="true">⚠ </span>}
              {formatarPercentual(quedas[indice - 1])} desistiram nesta etapa
              {indice === maiorQueda && ' · maior abandono'}
            </span>
          )}
        </li>
      ))}
    </ol>
  );
}

function Campanhas({ campanhas }) {
  if (campanhas.length === 0) return <p className="vazio">Nenhuma campanha no período.</p>;

  return (
    <div className="table-wrapper">
      <table>
        <thead>
          <tr>
            <th>Campanha</th>
            <th>Origem</th>
            <th className="num">Cotações</th>
            <th className="num">Conversão</th>
            <th className="num">Prêmio gerado</th>
            <th className="num">Investimento</th>
            <th className="num">ROI</th>
          </tr>
        </thead>
        <tbody>
          {campanhas.map((campanha) => (
            <tr key={campanha.nome}>
              <td><strong>{campanha.nome}</strong></td>
              <td className="muted">{campanha.utmSource}</td>
              <td className="num">{formatarNumero(campanha.cotacoes)}</td>
              <td className="num">{formatarPercentual(campanha.conversao)}</td>
              <td className="num">{formatarMoedaCompacta(campanha.premioCentavos)}</td>
              <td className="num">{formatarMoedaCompacta(campanha.investimentoCentavos)}</td>
              <td className={`num ${campanha.roi < 0 ? 'texto-ruim' : 'texto-bom'}`}>
                {campanha.roi == null ? '—' : `${campanha.roi < 0 ? '▼' : '▲'} ${formatarPercentual(campanha.roi, 0)}`}
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}
