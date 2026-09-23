import { describe, expect, it } from 'vitest';
import { formatarData, formatarMoeda, mascaraCpf } from './format';

describe('format', () => {
  it('formata centavos em reais', () => {
    expect(formatarMoeda(134064)).toBe('R$ 1.340,64');
    expect(formatarMoeda(null)).toBe('R$ 0,00');
  });

  it('converte data ISO para o padrão brasileiro', () => {
    expect(formatarData('2026-10-01')).toBe('01/10/2026');
    expect(formatarData(null)).toBe('—');
  });

  it('aplica a máscara de CPF enquanto o usuário digita', () => {
    expect(mascaraCpf('529')).toBe('529');
    expect(mascaraCpf('5299822')).toBe('529.982.2');
    expect(mascaraCpf('52998224725999')).toBe('529.982.247-25');
  });
});
