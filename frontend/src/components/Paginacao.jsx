export default function Paginacao({ paginacao, onMudar }) {
  if (!paginacao || paginacao.totalPaginas <= 1) return null;

  const { pagina, totalPaginas, total } = paginacao;

  return (
    <nav className="paginacao" aria-label="Paginação">
      <span className="muted">
        Página {pagina} de {totalPaginas} · {total} apólices
      </span>
      <div className="paginacao__botoes">
        <button type="button" className="btn btn--small btn--ghost" disabled={pagina <= 1} onClick={() => onMudar(pagina - 1)}>
          Anterior
        </button>
        <button type="button" className="btn btn--small btn--ghost" disabled={pagina >= totalPaginas} onClick={() => onMudar(pagina + 1)}>
          Próxima
        </button>
      </div>
    </nav>
  );
}
