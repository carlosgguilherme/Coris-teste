import { Navigate, Route, Routes } from 'react-router-dom';
import Layout from './components/Layout';
import ApoliceCreate from './pages/ApoliceCreate';
import ApoliceDetails from './pages/ApoliceDetails';
import ApoliceEdit from './pages/ApoliceEdit';
import ApoliceList from './pages/ApoliceList';

export default function App() {
  return (
    <Routes>
      <Route element={<Layout />}>
        <Route index element={<Navigate to="/apolices" replace />} />
        <Route path="/apolices" element={<ApoliceList />} />
        <Route path="/apolices/nova" element={<ApoliceCreate />} />
        <Route path="/apolices/:id" element={<ApoliceDetails />} />
        <Route path="/apolices/:id/editar" element={<ApoliceEdit />} />
        <Route path="*" element={<Navigate to="/apolices" replace />} />
      </Route>
    </Routes>
  );
}
