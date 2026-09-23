const CHAVE = 'seguro-viagem:sessao';

let sessaoEmMemoria = null;

export function lerSessao() {
  if (sessaoEmMemoria) return sessaoEmMemoria;

  try {
    sessaoEmMemoria = JSON.parse(localStorage.getItem(CHAVE)) ?? null;
  } catch {
    sessaoEmMemoria = null;
  }

  return sessaoEmMemoria;
}

export function salvarSessao(sessao) {
  sessaoEmMemoria = sessao;

  try {
    localStorage.setItem(CHAVE, JSON.stringify(sessao));
  } catch {
    // sem localStorage (ex.: navegação privada) a sessão vale só até recarregar a página
  }
}

export function limparSessao() {
  sessaoEmMemoria = null;

  try {
    localStorage.removeItem(CHAVE);
  } catch {
    // nada a limpar
  }
}
