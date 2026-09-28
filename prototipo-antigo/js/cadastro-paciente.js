// ---------- Máscaras ----------
const somenteDigitos = (valor) => valor.replace(/\D/g, '');

function mascaraCPF(valor) {
  return somenteDigitos(valor)
    .slice(0, 11)
    .replace(/(\d{3})(\d)/, '$1.$2')
    .replace(/(\d{3})(\d)/, '$1.$2')
    .replace(/(\d{3})(\d{1,2})$/, '$1-$2');
}

function mascaraData(valor) {
  return somenteDigitos(valor)
    .slice(0, 8)
    .replace(/(\d{2})(\d)/, '$1/$2')
    .replace(/(\d{2})(\d)/, '$1/$2');
}

function mascaraTelefone(valor) {
  const d = somenteDigitos(valor).slice(0, 11);
  if (d.length <= 10) {
    return d.replace(/(\d{2})(\d)/, '($1) $2').replace(/(\d{4})(\d)/, '$1-$2');
  }
  return d.replace(/(\d{2})(\d)/, '($1) $2').replace(/(\d{5})(\d)/, '$1-$2');
}

const mascaras = {
  cpf: mascaraCPF,
  nascimento: mascaraData,
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
function cpfValido(cpf) {
  const d = somenteDigitos(cpf);
  if (d.length !== 11 || /^(\d)\1+$/.test(d)) return false;
  const digito = (base) => {
    let soma = 0;
    for (let i = 0; i < base; i++) soma += Number(d[i]) * (base + 1 - i);
    const resto = (soma * 10) % 11;
    return resto === 10 ? 0 : resto;
  };
  return digito(9) === Number(d[9]) && digito(10) === Number(d[10]);
}

function dataValida(texto) {
  const [dia, mes, ano] = texto.split('/').map(Number);
  if (!dia || !mes || !ano || String(ano).length !== 4) return false;
  const data = new Date(ano, mes - 1, dia);
  const existe = data.getFullYear() === ano && data.getMonth() === mes - 1 && data.getDate() === dia;
  return existe && data < new Date();
}

const regras = {
  nome: (v) => v.trim().split(/\s+/).length >= 2 || 'Informe nome e sobrenome.',
  cpf: (v) => cpfValido(v) || 'CPF inválido.',
  nascimento: (v) => dataValida(v) || 'Data inválida.',
  sexo: (v) => v !== '' || 'Selecione uma opção.',
  telefone: (v) => somenteDigitos(v).length >= 10 || 'Telefone incompleto.',
  email: (v) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v) || 'E-mail inválido.',
  senha: (v) => v.length >= 8 || 'A senha precisa ter pelo menos 8 caracteres.',
  'confirmar-senha': (v) => v === document.getElementById('senha').value || 'As senhas não coincidem.',
};

function validarCampo(id) {
  const campo = document.getElementById(id);
  const container = campo.closest('.campo');
  const valor = campo.value;
  const resultado = valor === '' ? 'Campo obrigatório.' : regras[id](valor);
  const mensagem = resultado === true ? '' : resultado;

  container.classList.toggle('campo--invalido', mensagem !== '');
  container.querySelector('.erro').textContent = mensagem;
  campo.setAttribute('aria-invalid', String(mensagem !== ''));
  return mensagem === '';
}

// Valida ao sair do campo
Object.keys(regras).forEach((id) => {
  document.getElementById(id).addEventListener('blur', () => validarCampo(id));
});

// ---------- Envio ----------
document.getElementById('form-paciente').addEventListener('submit', (evento) => {
  evento.preventDefault();
  const invalidos = Object.keys(regras).filter((id) => !validarCampo(id));

  if (invalidos.length > 0) {
    document.getElementById(invalidos[0]).focus();
    return;
  }

  const dados = Object.fromEntries(new FormData(evento.target));
  delete dados['confirmar-senha'];
  console.log('Dados do paciente:', dados);
  // Aqui você envia para o backend ou vai para a próxima tela, por exemplo:
  // window.location.href = '/cadastro/paciente/concluido';
});
