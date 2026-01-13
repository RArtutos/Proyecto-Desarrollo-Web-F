document.addEventListener("DOMContentLoaded", () => {
  const usuario = document.getElementById("usuario");
  const contrasenia = document.getElementById("contrasenia");
  const confirmar = document.getElementById("confirmar");
  const form = document.querySelector("form");

  const forbiddenChars = /[*='"<>;(){}\[\]/\\`|!:?,#$%&^~+ ]/;
  const forbiddenKeys = [
    "*",
    "=",
    "'",
    '"',
    "<",
    ">",
    ";",
    "(",
    ")",
    "{",
    "}",
    "[",
    "]",
    "/",
    "\\",
    "`",
    "|",
    "!",
    "?",
    ":",
    ",",
    " ",
    "#",
    "$",
    "%",
    "&",
    "+",
    "~",
    "^",
    "\0",
    "\n",
    "\r",
    "\t",
  ];
  const MIN_PASS = 8;

  function bloquearCaracteres(e) {
    if (forbiddenKeys.includes(e.key)) e.preventDefault();
  }

  [usuario, contrasenia, confirmar].forEach((campo) => {
    if (campo) campo.addEventListener("keydown", bloquearCaracteres);
  });

  if (togglePass && contrasenia) {
    togglePass.addEventListener("click", () => {
      const visible = contrasenia.type === "text";
      contrasenia.type = visible ? "password" : "text";
      const icon = togglePass.querySelector("i");
      icon.className = visible ? "bi bi-eye-slash" : "bi bi-eye";
    });
  }

  if (form) {
    form.addEventListener("submit", (e) => {
      const campos = [usuario, contrasenia, confirmar];

      for (let campo of campos) {
        if (campo && forbiddenChars.test(campo.value)) {
          e.preventDefault();
          alert(
            "No se permiten caracteres especiales por motivos de seguridad."
          );
          return;
        }
      }

      if (contrasenia && contrasenia.value.length < MIN_PASS) {
        e.preventDefault();
        alert(`La contraseña debe tener mínimo ${MIN_PASS} caracteres.`);
        return;
      }

      if (contrasenia && confirmar && contrasenia.value !== confirmar.value) {
        e.preventDefault();
        alert("Las contraseñas no coinciden.");
        return;
      }
    });
  }
});
