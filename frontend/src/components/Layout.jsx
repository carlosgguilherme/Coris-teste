import { Link, Outlet } from 'react-router-dom';

export default function Layout() {
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
        </div>
      </header>

      <main className="container main">
        <Outlet />
      </main>
    </div>
  );
}
