import { useState } from 'react';
import { Bar, BarChart, CartesianGrid, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';
import KpiCard from '../../components/dashboard/KpiCard';
import Painel from '../../components/dashboard/Painel';
import { BarrasHorizontais, CORES, DicaGrafico, eixo } from '../../components/dashboard/graficos';
import { formatarData, formatarMoeda, formatarMoedaCompacta, formatarNumero, formatarPercentual } from '../../utils/format';

const COR_COTACOES = '#86b6ef';

const diaEMes = (data) => formatarData(data).slice(0, 5);

export default function Marketing({ dados }) {
  const { kpis } = dados;

  return (
    <div className="dash-grid dash-grid--2">
      <div className="kpis span-todas">
        <KpiCard titulo="Cotações" valor={kpis.cotacoes} />
        <KpiCard titulo="Conversão" formato="percentual" valor={kpis.conversao} ajuda="Cotações que viraram apólice ÷ cotações." />
        <KpiCard titulo="Investimento" formato="moedaCompacta" valor={kpis.investimentoCentavos} ajuda="Quanto foi investido nas campanhas que estiveram no ar no período." />
        <KpiCard titulo="Prêmio gerado" formato="moedaCompacta" valor={kpis.premioCampanhasCentavos} ajuda="Prêmio das apólices vendidas pelas campanhas." />
        <KpiCard titulo="ROI" formato="percentual" valor={kpis.roi} ajuda="(Prêmio gerado − investimento) ÷ investimento." />
        <KpiCard titulo="Custo por apólice" formato="moeda" valor={kpis.custoPorApoliceCentavos} ajuda="Investimento ÷ apólices vendidas pelas campanhas." />
      </div>

      <Painel titulo="Funil de conversão" subtitulo="Quantas cotações chegaram a cada etapa até virar apólice.">
        <Funil etapas={dados.funil} />
      </Painel>

      <Painel titulo="Conversão por canal" subtitulo="De cada 100 cotações, quantas viram apólice em cada canal.">
        <BarrasHorizontais
          dados={dados.porCanal}
          rotulo="canal"
          valor="conversao"
          formatar={(valor) => formatarPercentual(valor, 0)}
          dica={(item) => [
            ['Conversão', formatarPercentual(item.conversao)],
            ['Cotações', formatarNumero(item.cotacoes)],
            ['Apólices', formatarNumero(item.apolices)],
          ]}
        />
      </Painel>

      <Campanhas campanhas={dados.campanhas} />

      <Painel titulo="Antecedência da compra" subtitulo="Quantos dias antes da viagem o cliente contratou o seguro. Ajuda a decidir quando lançar uma campanha." className="span-todas">
        <ResponsiveContainer width="100%" height={240}>
          <BarChart data={dados.antecedencia} margin={{ top: 8, right: 8, bottom: 0, left: 0 }}>
            <CartesianGrid vertical={false} stroke={CORES.grade} />
            <XAxis dataKey="faixa" {...eixo} />
            <YAxis {...eixo} tickFormatter={formatarNumero} width={48} />
            <Tooltip cursor={{ fill: '#eef3f9' }} content={<DicaGrafico linhas={(item) => [['Apólices', formatarNumero(item.apolices)]]} />} />
            <Bar dataKey="apolices" fill={CORES.serie} barSize={48} radius={[4, 4, 0, 0]} />
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

/** Uma campanha em detalhe (escolhida na lista) e o comparativo entre todas. */
function Campanhas({ campanhas }) {
  const [selecionadaId, setSelecionadaId] = useState(null);

  if (campanhas.length === 0) {
    return (
      <Painel titulo="Campanhas" className="span-todas">
        <p className="vazio">Nenhuma campanha no ar no período.</p>
      </Painel>
    );
  }

  const campanha = campanhas.find((item) => item.id === selecionadaId) ?? campanhas[0];

  return (
    <>
      <Painel titulo="Campanha em detalhe" subtitulo="Resultado da campanha do início ao fim, semana a semana." className="span-todas">
        <div className="filtros-grafico">
          <select value={campanha.id} onChange={(event) => setSelecionadaId(Number(event.target.value))} aria-label="Campanha">
            {campanhas.map((item) => (
              <option key={item.id} value={item.id}>
                {item.nome} ({item.utmSource})
              </option>
            ))}
          </select>
          <span className="muted">
            No ar de {formatarData(campanha.inicio)} a {formatarData(campanha.fim)}
          </span>
        </div>

        <div className="destaques">
          <Destaque titulo="Investimento" valor={formatarMoeda(campanha.investimentoCentavos)} />
          <Destaque titulo="Prêmio gerado" valor={formatarMoeda(campanha.premioCentavos)} />
          <Destaque titulo="ROI" valor={formatarPercentual(campanha.roi, 0)} classe={campanha.roi < 0 ? 'texto-ruim' : 'texto-bom'} />
          <Destaque titulo="Custo por apólice" valor={campanha.custoPorApoliceCentavos == null ? '—' : formatarMoeda(campanha.custoPorApoliceCentavos)} />
          <Destaque titulo="Conversão" valor={formatarPercentual(campanha.conversao)} detalhe={`${formatarNumero(campanha.apolices)} de ${formatarNumero(campanha.cotacoes)} cotações`} />
        </div>

        <ul className="legenda-series">
          <li>
            <span className="legenda-series__cor" style={{ background: COR_COTACOES }} />
            Cotações
          </li>
          <li>
            <span className="legenda-series__cor" style={{ background: CORES.serie }} />
            Viraram apólice
          </li>
        </ul>
        <ResponsiveContainer width="100%" height={280}>
          <BarChart data={campanha.semanas} margin={{ top: 8, right: 8, bottom: 0, left: 0 }} barGap={2}>
            <CartesianGrid vertical={false} stroke={CORES.grade} />
            <XAxis dataKey="semana" {...eixo} tickFormatter={(semana) => `Sem. ${diaEMes(semana)}`} />
            <YAxis {...eixo} tickFormatter={formatarNumero} width={48} allowDecimals={false} />
            <Tooltip
              cursor={{ fill: '#eef3f9' }}
              content={
                <DicaGrafico
                  formatarRotulo={(semana) => `Semana de ${formatarData(semana)}`}
                  linhas={(semana) => [
                    ['Cotações', formatarNumero(semana.cotacoes), COR_COTACOES],
                    ['Viraram apólice', formatarNumero(semana.apolices), CORES.serie],
                    ['Conversão', semana.cotacoes > 0 ? formatarPercentual(semana.apolices / semana.cotacoes) : '—'],
                  ]}
                />
              }
            />
            <Bar dataKey="cotacoes" fill={COR_COTACOES} radius={[4, 4, 0, 0]} maxBarSize={32} />
            <Bar dataKey="apolices" fill={CORES.serie} radius={[4, 4, 0, 0]} maxBarSize={32} />
          </BarChart>
        </ResponsiveContainer>
      </Painel>

      <Painel titulo="Comparativo das campanhas" subtitulo="Clique em uma campanha para ver o detalhe acima. ROI = (prêmio gerado − investimento) ÷ investimento." className="span-todas">
        <div className="table-wrapper">
          <table>
            <thead>
              <tr>
                <th>Campanha</th>
                <th>Origem</th>
                <th className="num">Cotações</th>
                <th className="num">Apólices</th>
                <th className="num">Conversão</th>
                <th className="num">Investimento</th>
                <th className="num">Prêmio gerado</th>
                <th className="num">Custo por apólice</th>
                <th className="num">ROI</th>
              </tr>
            </thead>
            <tbody>
              {campanhas.map((item) => (
                <tr key={item.id} className={item.id === campanha.id ? 'linha-clicavel linha-selecionada' : 'linha-clicavel'} onClick={() => setSelecionadaId(item.id)}>
                  <td><strong>{item.nome}</strong></td>
                  <td className="muted">{item.utmSource}</td>
                  <td className="num">{formatarNumero(item.cotacoes)}</td>
                  <td className="num">{formatarNumero(item.apolices)}</td>
                  <td className="num">{formatarPercentual(item.conversao)}</td>
                  <td className="num">{formatarMoedaCompacta(item.investimentoCentavos)}</td>
                  <td className="num">{formatarMoedaCompacta(item.premioCentavos)}</td>
                  <td className="num">{item.custoPorApoliceCentavos == null ? '—' : formatarMoeda(item.custoPorApoliceCentavos)}</td>
                  <td className={`num ${item.roi < 0 ? 'texto-ruim' : 'texto-bom'}`}>
                    {item.roi == null ? '—' : `${item.roi < 0 ? '▼' : '▲'} ${formatarPercentual(item.roi, 0)}`}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </Painel>
    </>
  );
}

function Destaque({ titulo, valor, detalhe, classe = '' }) {
  return (
    <div className="destaque">
      <span className="muted">{titulo}</span>
      <strong className={classe}>{valor}</strong>
      {detalhe && <span className="muted">{detalhe}</span>}
    </div>
  );
}
