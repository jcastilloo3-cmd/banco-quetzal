document.addEventListener('DOMContentLoaded', function () {
  var boton = document.querySelector('.menu-boton');
  var menu = document.getElementById('menu-principal');
  if (boton && menu) {
    boton.addEventListener('click', function () {
      var abierto = menu.classList.toggle('abierto');
      boton.setAttribute('aria-expanded', abierto ? 'true' : 'false');
    });
  }

  document.querySelectorAll('input').forEach(function (input) {
    input.addEventListener('blur', function () { input.classList.add('tocado'); });
  });

  document.querySelectorAll('[data-igual-a]').forEach(function (conf) {
    var original = document.getElementById(conf.getAttribute('data-igual-a'));
    function validar() {
      conf.setCustomValidity(conf.value !== original.value ? 'Las contraseñas no coinciden.' : '');
    }
    conf.addEventListener('input', validar);
    original.addEventListener('input', validar);
  });

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

  document.querySelectorAll('[data-imprimir]').forEach(function (b) {
    b.addEventListener('click', function () { window.print(); });
  });

  document.querySelectorAll('form[method="post"]').forEach(function (f) {
    f.addEventListener('submit', function () {
      var b = f.querySelector('button[type="submit"]');
      if (b) { setTimeout(function () { b.disabled = true; }, 0); }
    });
  });
});
