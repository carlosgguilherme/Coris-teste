import { Navigate, Route, Routes } from 'react-router-dom';
import RotaProtegida from './auth/RotaProtegida';
import Layout from './components/Layout';
import ApoliceCreate from './pages/ApoliceCreate';
import ApoliceDetails from './pages/ApoliceDetails';
import ApoliceEdit from './pages/ApoliceEdit';
import ApoliceList from './pages/ApoliceList';
import Login from './pages/Login';

export default function App() {
  return (
    <Routes>
      <Route path="/login" element={<Login />} />
      <Route element={<RotaProtegida />}>
        <Route element={<Layout />}>
          <Route index element={<Navigate to="/apolices" replace />} />
          <Route path="/apolices" element={<ApoliceList />} />
          <Route path="/apolices/nova" element={<ApoliceCreate />} />
          <Route path="/apolices/:id" element={<ApoliceDetails />} />
          <Route path="/apolices/:id/editar" element={<ApoliceEdit />} />
        </Route>
      </Route>
      <Route path="*" element={<Navigate to="/apolices" replace />} />
    </Routes>
  );
}
