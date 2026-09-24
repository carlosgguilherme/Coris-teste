export default function Painel({ titulo, subtitulo, className = '', children }) {
  return (
    <section className={`card painel ${className}`}>
      <header className="painel__cabecalho">
        <h2>{titulo}</h2>
        {subtitulo && <p className="muted">{subtitulo}</p>}
      </header>
      {children}
    </section>
  );
}
