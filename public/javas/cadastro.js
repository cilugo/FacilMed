/*
 * CADASTRO — máscaras e "mostrar senha". 29/09/2026.
 *
 * Vem do protótipo da Mariana (prototipo-antigo/paginas/cadpac).
 * Só CONFORTO VISUAL: quem valida é o servidor (FormRequest), que tira
 * pontos e traços antes de conferir. Por isso a validação em JS do
 * protótipo não foi trazida — com o JS desligado o cadastro funciona igual
 * e o erro aparece embaixo do campo, vindo do Laravel (AGENTS.md §3).
 *
 * Uso no HTML:
 *   <input data-mascara="cpf|cnpj|telefone|cep">
 *   <button class="mostrar-senha" data-alvo="id-do-campo">
 */
(function () {
  const digitos = (v) => v.replace(/\D/g, '');

  const mascaras = {
    cpf: (v) => digitos(v).slice(0, 11)
      .replace(/(\d{3})(\d)/, '$1.$2')
      .replace(/(\d{3})(\d)/, '$1.$2')
      .replace(/(\d{3})(\d{1,2})$/, '$1-$2'),

    cnpj: (v) => digitos(v).slice(0, 14)
      .replace(/^(\d{2})(\d)/, '$1.$2')
      .replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3')
      .replace(/\.(\d{3})(\d)/, '.$1/$2')
      .replace(/(\d{4})(\d)/, '$1-$2'),

    telefone: (v) => {
      const d = digitos(v).slice(0, 11);
      if (d.length <= 10) return d.replace(/(\d{2})(\d)/, '($1) $2').replace(/(\d{4})(\d)/, '$1-$2');
      return d.replace(/(\d{2})(\d)/, '($1) $2').replace(/(\d{5})(\d)/, '$1-$2');
    },

    cep: (v) => digitos(v).slice(0, 8).replace(/(\d{5})(\d)/, '$1-$2'),
  };

  document.querySelectorAll('[data-mascara]').forEach((campo) => {
    const aplicar = mascaras[campo.dataset.mascara];
    if (!aplicar) return;
    campo.addEventListener('input', () => { campo.value = aplicar(campo.value); });
    if (campo.value) campo.value = aplicar(campo.value); // valor que voltou do servidor (old())
  });

  document.querySelectorAll('.mostrar-senha').forEach((botao) => {
    botao.addEventListener('click', () => {
      const campo = document.getElementById(botao.dataset.alvo);
      if (!campo) return;
      const visivel = campo.type === 'text';
      campo.type = visivel ? 'password' : 'text';
      botao.setAttribute('aria-pressed', String(!visivel));
      botao.setAttribute('aria-label', visivel ? 'Mostrar senha' : 'Ocultar senha');
    });
  });

  // Com erro vindo do servidor, leva a pessoa até o primeiro campo marcado.
  const primeiroErro = document.querySelector('.campo--invalido input, .campo--invalido select, .campo--invalido textarea, .tipos--invalido input');
  if (primeiroErro) primeiroErro.focus({ preventScroll: false });
})();
