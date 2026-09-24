/** Grupo de botões em que só um fica ativo (ex.: Prêmio | Apólices | Ticket). */
export default function Segmentado({ rotulo, opcoes, valor, onMudar }) {
  return (
    <div className="segmentado" role="group" aria-label={rotulo}>
      <span className="segmentado__rotulo">{rotulo}</span>
      <div className="segmentado__botoes">
        {opcoes.map((opcao) => (
          <button
            key={opcao.id}
            type="button"
            aria-pressed={opcao.id === valor}
            className={opcao.id === valor ? 'segmentado__botao segmentado__botao--ativo' : 'segmentado__botao'}
            onClick={() => onMudar(opcao.id)}
          >
            {opcao.label}
          </button>
        ))}
      </div>
    </div>
  );
}
