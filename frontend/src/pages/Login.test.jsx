import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { MemoryRouter, Route, Routes } from 'react-router-dom';
import { describe, expect, it, vi } from 'vitest';
import { authApi } from '../api/auth';
import { ApiError } from '../api/client';
import { AuthProvider } from '../auth/AuthContext';
import { lerSessao } from '../auth/sessao';
import Login from './Login';

function renderizar() {
  return render(
    <MemoryRouter initialEntries={['/login']}>
      <AuthProvider>
        <Routes>
          <Route path="/login" element={<Login />} />
          <Route path="/apolices" element={<p>Lista de apólices</p>} />
        </Routes>
      </AuthProvider>
    </MemoryRouter>,
  );
}

async function preencherEEntrar(senha) {
  await userEvent.type(screen.getByLabelText('E-mail'), 'admin@seguroviagem.com');
  await userEvent.type(screen.getByLabelText('Senha'), senha);
  await userEvent.click(screen.getByRole('button', { name: 'Entrar' }));
}

describe('Login', () => {
  it('guarda a sessão e redireciona para a lista', async () => {
    vi.spyOn(authApi, 'login').mockResolvedValue({ token: 'jwt', usuario: { nome: 'Admin', email: 'admin@seguroviagem.com' } });
    renderizar();

    await preencherEEntrar('Admin@123');

    expect(await screen.findByText('Lista de apólices')).toBeInTheDocument();
    expect(lerSessao().token).toBe('jwt');
  });

  it('mostra a mensagem quando as credenciais são inválidas', async () => {
    vi.spyOn(authApi, 'login').mockRejectedValue(new ApiError('E-mail ou senha inválidos.', 401));
    renderizar();

    await preencherEEntrar('errada');

    expect(await screen.findByText('E-mail ou senha inválidos.')).toBeInTheDocument();
    expect(lerSessao()).toBeNull();
  });
});
