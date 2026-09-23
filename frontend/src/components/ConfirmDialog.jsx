import { useEffect, useRef } from 'react';

export default function ConfirmDialog({ aberto, titulo, mensagem, textoConfirmar = 'Confirmar', carregando, onConfirmar, onCancelar }) {
  const dialogRef = useRef(null);

  useEffect(() => {
    const dialog = dialogRef.current;
    if (aberto && !dialog.open) dialog.showModal();
    if (!aberto && dialog.open) dialog.close();
  }, [aberto]);

  return (
    <dialog ref={dialogRef} className="dialog" onCancel={onCancelar}>
      <h2>{titulo}</h2>
      <p>{mensagem}</p>
      <div className="dialog__actions">
        <button type="button" className="btn btn--ghost" onClick={onCancelar} disabled={carregando}>
          Cancelar
        </button>
        <button type="button" className="btn btn--danger" onClick={onConfirmar} disabled={carregando}>
          {carregando ? 'Excluindo...' : textoConfirmar}
        </button>
      </div>
    </dialog>
  );
}
