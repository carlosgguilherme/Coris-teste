import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { MemoryRouter } from 'react-router-dom';
import { describe, expect, it, vi } from 'vitest';
import { apolicesApi } from '../api/apolices';
import { ApiError } from '../api/client';
import ApoliceForm from './ApoliceForm';

const opcoes = {
  planos: [{ valor: 'plus', label: 'Plus', valorDiariaCentavos: 2490, coberturaMedicaCentavos: 6000000 }],
  destinos: [{ valor: 'europa', label: 'Europa' }],
  status: [
    { valor: 'ativa', label: 'Ativa' },
    { valor: 'cancelada', label: 'Cancelada' },
  ],
};

function renderizar(props) {
  vi.spyOn(apolicesApi, 'opcoes').mockResolvedValue(opcoes);
  vi.spyOn(apolicesApi, 'cotar').mockResolvedValue({ valorPremioCentavos: 32370, dias: 10 });

  return render(
    <MemoryRouter>
      <ApoliceForm textoBotao="Emitir apólice" onSubmit={vi.fn()} {...props} />
    </MemoryRouter>,
  );
}

describe('ApoliceForm', () => {
  it('exibe o erro devolvido pela API no campo correspondente', async () => {
    const onSubmit = vi.fn().mockRejectedValue(new ApiError('Dados inválidos', 422, { seguradoCpf: 'CPF inválido.' }));
    renderizar({ onSubmit });

    await userEvent.click(screen.getByRole('button', { name: 'Emitir apólice' }));

    expect(await screen.findByText('CPF inválido.')).toBeInTheDocument();
    expect(screen.getByLabelText('CPF')).toHaveAttribute('aria-invalid', 'true');
  });

  it('não envia status ao emitir uma nova apólice', async () => {
    const onSubmit = vi.fn().mockResolvedValue();
    renderizar({ onSubmit });

    await userEvent.type(screen.getByLabelText('Nome completo'), 'Carlos Pereira');
    await userEvent.click(screen.getByRole('button', { name: 'Emitir apólice' }));

    expect(onSubmit).toHaveBeenCalledWith(expect.objectContaining({ seguradoNome: 'Carlos Pereira', status: undefined }));
  });

  it('calcula o prêmio quando todos os campos estão preenchidos', async () => {
    renderizar({
      inicial: {
        seguradoNome: 'Carlos Pereira',
        seguradoCpf: '529.982.247-25',
        seguradoEmail: 'carlos@email.com',
        seguradoNascimento: '1995-05-10',
        destino: 'europa',
        plano: 'plus',
        inicioVigencia: '2026-10-01',
        fimVigencia: '2026-10-10',
      },
    });

    expect(await screen.findByText('R$ 323,70', { normalizer: (t) => t.replace(/\s/g, ' ') })).toBeInTheDocument();
    await waitFor(() => expect(apolicesApi.cotar).toHaveBeenCalledOnce());
  });

  it('bloqueia o CPF e avisa sobre o endosso na edição', () => {
    renderizar({ edicao: true, inicial: { seguradoCpf: '529.982.247-25' } });

    expect(screen.getByLabelText('CPF')).toHaveAttribute('readonly');
    expect(screen.getByText(/gera um endosso/)).toBeInTheDocument();
  });
});
