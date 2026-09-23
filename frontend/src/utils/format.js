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
