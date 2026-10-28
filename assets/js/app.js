/* Banco Quetzal - comportamiento del lado del cliente (sin dependencias) */
document.addEventListener('DOMContentLoaded', function () {
  // Menú responsivo
  var boton = document.querySelector('.menu-boton');
  var menu = document.getElementById('menu-principal');
  if (boton && menu) {
    boton.addEventListener('click', function () {
      var abierto = menu.classList.toggle('abierto');
      boton.setAttribute('aria-expanded', abierto ? 'true' : 'false');
    });
  }

  // Marcar campos ya tocados para mostrar el borde de error solo después de interactuar
  document.querySelectorAll('input').forEach(function (input) {
    input.addEventListener('blur', function () { input.classList.add('tocado'); });
  });

  // Confirmación de contraseña: valida que coincida antes de enviar
  document.querySelectorAll('[data-igual-a]').forEach(function (conf) {
    var original = document.getElementById(conf.getAttribute('data-igual-a'));
    function validar() {
      conf.setCustomValidity(conf.value !== original.value ? 'Las contraseñas no coinciden.' : '');
    }
    conf.addEventListener('input', validar);
    original.addEventListener('input', validar);
  });

  // Transferencia: el monto no puede superar el máximo de la cuenta seleccionada
  var monto = document.getElementById('monto');
  var radios = document.querySelectorAll('input[name="tercero"][data-max]');
  if (monto && radios.length) {
    var aplicarMax = function () {
      var sel = document.querySelector('input[name="tercero"]:checked');
      if (sel) { monto.max = sel.getAttribute('data-max'); }
    };
    radios.forEach(function (r) { r.addEventListener('change', aplicarMax); });
    aplicarMax();
  }

  // Imprimir estado de cuenta
  document.querySelectorAll('[data-imprimir]').forEach(function (b) {
    b.addEventListener('click', function () { window.print(); });
  });

  // Evitar doble envío de formularios
  document.querySelectorAll('form[method="post"]').forEach(function (f) {
    f.addEventListener('submit', function () {
      var b = f.querySelector('button[type="submit"]');
      if (b) { setTimeout(function () { b.disabled = true; }, 0); }
    });
  });
});
