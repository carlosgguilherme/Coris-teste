export function CarregandoDashboard() {
  return (
    <div className="dash-grid" aria-busy="true" aria-label="Carregando dashboard">
      <div className="kpis">
        {Array.from({ length: 6 }).map((_, indice) => (
          <div key={indice} className="card kpi skeleton" style={{ height: 112 }} />
        ))}
      </div>
      <div className="card skeleton" style={{ height: 340 }} />
    </div>
  );
}

