import { Link, NavLink, Outlet } from 'react-router-dom';

export default function Layout() {
  return (
    <div className="app">
      <header className="topbar">
        <div className="container topbar__content">
          <Link to="/apolices" className="brand">
            <div>
              <strong>Mariano Seguros</strong>
              <span>Gestão de Apólices</span>
            </div>
          </Link>
          <nav className="menu">
            <NavLink to="/apolices" className="menu__item">
              Apólices
            </NavLink>
            <NavLink to="/dashboard" className="menu__item">
              Dashboard
            </NavLink>
          </nav>
        </div>
      </header>

      <main className="container main">
        <Outlet />
      </main>
    </div>
  );
}
