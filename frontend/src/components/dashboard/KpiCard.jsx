import { formatarMoeda, formatarMoedaCompacta, formatarNumero, formatarPercentual } from '../../utils/format';

const FORMATOS = {
  moeda: formatarMoeda,
  moedaCompacta: formatarMoedaCompacta,
  numero: formatarNumero,
  percentual: (valor) => formatarPercentual(valor),
  nps: (valor) => (valor == null ? '—' : String(valor)),
};

/**
 * Variação em relação ao período anterior. Para percentuais e NPS mostra a diferença
 * em pontos; para valores absolutos, a variação percentual.
 */
function variacao(valor, anterior, formato) {
  if (valor == null || anterior == null || anterior === 0) return null;

  if (formato === 'percentual') {
    const pontos = (valor - anterior) * 100;
    return { delta: pontos, texto: `${Math.abs(pontos).toLocaleString('pt-BR', { maximumFractionDigits: 1 })} p.p.` };
  }
  if (formato === 'nps') {
    const pontos = valor - anterior;
    return { delta: pontos, texto: `${Math.abs(pontos)} pts` };
  }

  const relativa = (valor - anterior) / anterior;
  return { delta: relativa, texto: formatarPercentual(Math.abs(relativa)) };
}

export default function KpiCard({ titulo, valor, anterior, formato = 'numero', melhorQuando = 'maior', ajuda }) {
  const comparacao = variacao(valor, anterior, formato);
  const subiu = comparacao?.delta > 0;
  const bom = comparacao && comparacao.delta !== 0 && subiu === (melhorQuando === 'maior');

  return (
    <div className="card kpi" title={[formato === 'moedaCompacta' ? formatarMoeda(valor) : null, ajuda].filter(Boolean).join(' · ')}>
      <span className="kpi__titulo">{titulo}</span>
      <strong className="kpi__valor">{FORMATOS[formato](valor)}</strong>
      {comparacao ? (
        <span className={`kpi__variacao ${comparacao.delta === 0 ? '' : bom ? 'kpi__variacao--bom' : 'kpi__variacao--ruim'}`}>
          <span aria-hidden="true">{comparacao.delta === 0 ? '=' : subiu ? '▲' : '▼'}</span>
          {comparacao.texto} <span className="muted">vs. período anterior</span>
        </span>
      ) : (
        anterior !== undefined && <span className="kpi__variacao muted">sem dados para comparar</span>
      )}
    </div>
  );
}
