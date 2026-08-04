function login(event) {
    if (event) {
        event.preventDefault();
    }

    const email = document.getElementById('Email')?.value.trim() || '';
    const senha = document.getElementById('Senha')?.value.trim() || '';
    const mensagem = document.getElementById('Mensagem');

 
    if (!email || !senha) {
        if (mensagem) {
            mensagem.textContent = 'Preencha todos os campos para prosseguir.';
        }
        return false;
    }

   
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        if (mensagem) {
            mensagem.textContent = 'Por favor, insira um e-mail válido.';
        }
        return false;
    }

    
    if (mensagem) {
        mensagem.textContent = '';
    }

    window.location.assign('../pagina2/pg2teste.html');
    return false;
}
