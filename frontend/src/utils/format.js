const moeda = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' });

export const formatarMoeda = (centavos) => moeda.format((centavos ?? 0) / 100);

export function formatarData(isoDate) {
  if (!isoDate) return '—';
  const [ano, mes, dia] = isoDate.slice(0, 10).split('-');
  return `${dia}/${mes}/${ano}`;
}

export function formatarDataHora(iso) {
  if (!iso) return '—';
  return new Date(iso).toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short' });
}

export function mascaraCpf(valor) {
  return valor
    .replace(/\D/g, '')
    .slice(0, 11)
    .replace(/(\d{3})(\d)/, '$1.$2')
    .replace(/(\d{3})(\d)/, '$1.$2')
    .replace(/(\d{3})(\d{1,2})$/, '$1-$2');
}

const moedaCompacta = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL', notation: 'compact', maximumFractionDigits: 1 });
const numero = new Intl.NumberFormat('pt-BR');
const MESES = ['jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez'];

export const formatarMoedaCompacta = (centavos) => moedaCompacta.format((centavos ?? 0) / 100);

export const formatarNumero = (valor) => (valor == null ? '—' : numero.format(valor));

/** Recebe uma razão (0,615) e devolve "61,5%". */
export function formatarPercentual(razao, casas = 1) {
  if (razao == null) return '—';
  return `${(razao * 100).toLocaleString('pt-BR', { minimumFractionDigits: casas, maximumFractionDigits: casas })}%`;
}

/** "2026-09" vira "set/26". */
export function formatarMes(anoMes) {
  const [ano, mes] = anoMes.split('-');
  return `${MESES[Number(mes) - 1]}/${ano.slice(2)}`;
}

export function formatarSegundos(segundos) {
  if (segundos == null) return '—';
  const minutos = Math.floor(segundos / 60);
  return minutos > 0 ? `${minutos}min ${segundos % 60}s` : `${segundos}s`;
}
