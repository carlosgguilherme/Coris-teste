import { lazy, Suspense } from 'react';
import { Navigate, Route, Routes } from 'react-router-dom';
import Layout from './components/Layout';
import ApoliceCreate from './pages/ApoliceCreate';
import ApoliceDetails from './pages/ApoliceDetails';
import ApoliceEdit from './pages/ApoliceEdit';
import ApoliceList from './pages/ApoliceList';

// A dashboard usa a biblioteca de gráficos, então só é baixada quando alguém abre a tela
const Dashboard = lazy(() => import('./pages/Dashboard'));

export default function App() {
  return (
    <Routes>
      <Route element={<Layout />}>
        <Route index element={<Navigate to="/apolices" replace />} />
        <Route path="/apolices" element={<ApoliceList />} />
        <Route path="/apolices/nova" element={<ApoliceCreate />} />
        <Route path="/apolices/:id" element={<ApoliceDetails />} />
        <Route path="/apolices/:id/editar" element={<ApoliceEdit />} />
        <Route
          path="/dashboard"
          element={
            <Suspense fallback={<p className="vazio">Carregando...</p>}>
              <Dashboard />
            </Suspense>
          }
        />
        <Route path="*" element={<Navigate to="/apolices" replace />} />
      </Route>
    </Routes>
  );
}
