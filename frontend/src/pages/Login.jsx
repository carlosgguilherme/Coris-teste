import { useState } from 'react';
import { Navigate, useLocation, useNavigate } from 'react-router-dom';
import { useAuth } from '../auth/AuthContext';

export default function Login() {
  const { login, autenticado } = useAuth();
  const navigate = useNavigate();
  const location = useLocation();
  const [email, setEmail] = useState('');
  const [senha, setSenha] = useState('');
  const [erro, setErro] = useState(null);
  const [entrando, setEntrando] = useState(false);

  if (autenticado) return <Navigate to="/apolices" replace />;

  async function entrar(event) {
    event.preventDefault();
    setEntrando(true);
    setErro(null);

    try {
      await login(email, senha);
      navigate(location.state?.de ?? '/apolices', { replace: true });
    } catch (error) {
      setErro(error.message);
      setEntrando(false);
    }
  }

  return (
    <div className="login">
      <form className="card login__card" onSubmit={entrar}>
        <div className="login__marca">
          <img src="/favicon.svg" alt="" width="40" height="40" />
          <div>
            <strong>Seguro Viagem</strong>
            <span className="muted">Gestão de Apólices</span>
          </div>
        </div>

        {erro && <div className="alert alert--erro">{erro}</div>}

        <label className="field">
          <span>E-mail</span>
          <input type="email" value={email} onChange={(e) => setEmail(e.target.value)} autoComplete="username" required />
        </label>

        <label className="field">
          <span>Senha</span>
          <input type="password" value={senha} onChange={(e) => setSenha(e.target.value)} autoComplete="current-password" required />
        </label>

        <button type="submit" className="btn btn--primary" disabled={entrando}>
          {entrando ? 'Entrando...' : 'Entrar'}
        </button>
      </form>
    </div>
  );
}
