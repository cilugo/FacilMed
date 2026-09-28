// ---------- Máscaras ----------
const somenteDigitos = (valor) => valor.replace(/\D/g, '');

function mascaraCNPJ(valor) {
  return somenteDigitos(valor)
    .slice(0, 14)
    .replace(/^(\d{2})(\d)/, '$1.$2')
    .replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3')
    .replace(/\.(\d{3})(\d)/, '.$1/$2')
    .replace(/(\d{4})(\d)/, '$1-$2');
}

function mascaraTelefone(valor) {
  const d = somenteDigitos(valor).slice(0, 11);
  if (d.length <= 10) {
    return d.replace(/(\d{2})(\d)/, '($1) $2').replace(/(\d{4})(\d)/, '$1-$2');
  }
  return d.replace(/(\d{2})(\d)/, '($1) $2').replace(/(\d{5})(\d)/, '$1-$2');
}

const mascaras = {
  cnpj: mascaraCNPJ,
  cnes: (v) => somenteDigitos(v).slice(0, 7),
  telefone: mascaraTelefone,
};

Object.entries(mascaras).forEach(([id, aplicar]) => {
  const campo = document.getElementById(id);
  campo.addEventListener('input', () => { campo.value = aplicar(campo.value); });
});

// ---------- Mostrar / ocultar senha ----------
document.querySelectorAll('.mostrar-senha').forEach((botao) => {
  botao.addEventListener('click', () => {
    const campo = document.getElementById(botao.dataset.alvo);
    const visivel = campo.type === 'text';
    campo.type = visivel ? 'password' : 'text';
    botao.setAttribute('aria-pressed', String(!visivel));
    botao.setAttribute('aria-label', visivel ? 'Mostrar senha' : 'Ocultar senha');
  });
});

// ---------- Validações ----------
function cnpjValido(cnpj) {
  const d = somenteDigitos(cnpj);
  if (d.length !== 14 || /^(\d)\1+$/.test(d)) return false;
  const digito = (tamanho) => {
    const pesos = tamanho === 12
      ? [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]
      : [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
    const soma = pesos.reduce((total, peso, i) => total + Number(d[i]) * peso, 0);
    const resto = soma % 11;
    return resto < 2 ? 0 : 11 - resto;
  };
  return digito(12) === Number(d[12]) && digito(13) === Number(d[13]);
}

const regras = {
  'razao-social': (v) => v.trim().length >= 3 || 'Informe o nome da instituição.',
  cnpj: (v) => cnpjValido(v) || 'CNPJ inválido.',
  cnes: (v) => v.length === 7 || 'O CNES tem 7 dígitos.',
  endereco: (v) => v.trim().length >= 10 || 'Informe o endereço completo.',
  telefone: (v) => somenteDigitos(v).length >= 10 || 'Telefone incompleto.',
  email: (v) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v) || 'E-mail inválido.',
  responsavel: (v) => v.trim().split(/\s+/).length >= 2 || 'Informe nome e sobrenome.',
  cargo: (v) => v.trim().length >= 2 || 'Informe o cargo.',
  usuario: (v) => /^[a-zA-Z0-9._]{4,}$/.test(v) || 'Use ao menos 4 caracteres: letras, números, ponto ou _.',
  senha: (v) => v.length >= 8 || 'A senha precisa ter pelo menos 8 caracteres.',
};

function validarCampo(id) {
  const campo = document.getElementById(id);
  const container = campo.closest('.campo');
  const resultado = campo.value === '' ? 'Campo obrigatório.' : regras[id](campo.value);
  const mensagem = resultado === true ? '' : resultado;

  container.classList.toggle('campo--invalido', mensagem !== '');
  container.querySelector('.erro').textContent = mensagem;
  campo.setAttribute('aria-invalid', String(mensagem !== ''));
  return mensagem === '';
}

function validarTipo() {
  const selecionado = document.querySelector('input[name="tipo"]:checked');
  document.querySelector('.tipos').classList.toggle('tipos--invalido', !selecionado);
  document.getElementById('erro-tipo').textContent = selecionado ? '' : 'Selecione clínica ou hospital.';
  return Boolean(selecionado);
}

Object.keys(regras).forEach((id) => {
  document.getElementById(id).addEventListener('blur', () => validarCampo(id));
});

document.querySelectorAll('input[name="tipo"]').forEach((radio) => {
  radio.addEventListener('change', validarTipo);
});

// ---------- Envio ----------
document.getElementById('form-clinica').addEventListener('submit', (evento) => {
  evento.preventDefault();

  const tipoOk = validarTipo();
  const invalidos = Object.keys(regras).filter((id) => !validarCampo(id));

  if (!tipoOk) {
    document.querySelector('input[name="tipo"]').focus();
    return;
  }
  if (invalidos.length) {
    document.getElementById(invalidos[0]).focus();
    return;
  }
  const dados = Object.fromEntries(new FormData(evento.target));
  console.log('Dados da instituição:', dados);
  // Envio para o backend, por exemplo:
  // fetch('/api/cadastro/instituicao', {
  //   method: 'POST',
  //   headers: { 'Content-Type': 'application/json' },
  //   body: JSON.stringify(dados),
  // });
});
