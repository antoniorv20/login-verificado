document.querySelector('form').onsubmit = function(e) {
    const password = document.querySelector('input[name="nueva_password"]').value;
    const confirmar = document.querySelector('input[name="confirmar_contrasenia"]').value;

    if(password !== confirmar){
        alert('Las contraseñas no coinciden');
    e.preventDefault();
}
}