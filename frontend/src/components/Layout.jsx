import { Link, Outlet } from 'react-router-dom';
import { useAuth } from '../auth/AuthContext';

export default function Layout() {
  const { usuario, logout } = useAuth();

  return (
    <div className="app">
      <header className="topbar">
        <div className="container topbar__content">
          <Link to="/apolices" className="brand">
            <img src="/favicon.svg" alt="" width="32" height="32" />
            <div>
              <strong>Seguro Viagem</strong>
              <span>Gestão de Apólices</span>
            </div>
          </Link>

          <div className="topbar__usuario">
            <span>{usuario?.nome}</span>
            <button type="button" className="btn btn--small btn--topbar" onClick={logout}>
              Sair
            </button>
          </div>
        </div>
      </header>

      <main className="container main">
        <Outlet />
      </main>
    </div>
  );
}
