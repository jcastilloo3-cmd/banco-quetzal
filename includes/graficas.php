<?php
function escala_redonda(float $max): float
{
    if ($max <= 0) {
        return 100.0;
    }
    $exp = 10 ** floor(log10($max));
    foreach ([1, 2, 2.5, 5, 10] as $m) {
        if ($max <= $m * $exp) {
            return $m * $exp;
        }
    }
    return 10 * $exp;
}

function monto_corto(float $v): string
{
    if ($v >= 1000000) {
        return 'Q' . rtrim(rtrim(number_format($v / 1000000, 1), '0'), '.') . 'M';
    }
    if ($v >= 1000) {
        return 'Q' . rtrim(rtrim(number_format($v / 1000, 1), '0'), '.') . 'k';
    }
    return 'Q' . number_format($v, 0);
}

function grafica_comparativa(float $depositos, float $retiros): string
{
    $w = 520; $h = 300; $izq = 64; $der = 16; $arr = 24; $aba = 40;
    $max = escala_redonda(max($depositos, $retiros));
    $alto = $h - $arr - $aba;
    $ancho = $w - $izq - $der;
    $svg = '<svg class="grafica" viewBox="0 0 ' . $w . ' ' . $h . '" role="img" '
         . 'aria-label="Depósitos ' . e(dinero($depositos)) . ' contra retiros ' . e(dinero($retiros)) . '">';

    for ($i = 0; $i <= 4; $i++) {
        $y = $arr + $alto - ($alto * $i / 4);
        $svg .= '<line class="grafica__guia" x1="' . $izq . '" x2="' . ($w - $der) . '" y1="' . $y . '" y2="' . $y . '"/>';
        $svg .= '<text class="grafica__eje" x="' . ($izq - 8) . '" y="' . ($y + 4) . '" text-anchor="end">'
              . e(monto_corto($max * $i / 4)) . '</text>';
    }

    $barras = [['Depósitos', $depositos, 'deposito'], ['Retiros', $retiros, 'retiro']];
    $slot = $ancho / 2;
    $bw = min(120, $slot * 0.5);
    foreach ($barras as $i => [$nombre, $valor, $clase]) {
        $bh = $max > 0 ? $alto * $valor / $max : 0;
        $x = $izq + $slot * $i + ($slot - $bw) / 2;
        $y = $arr + $alto - $bh;
        $svg .= '<rect class="grafica__barra grafica__barra--' . $clase . '" x="' . round($x, 1) . '" y="' . round($y, 1)
              . '" width="' . round($bw, 1) . '" height="' . round(max($bh, 0), 1) . '" rx="4"><title>'
              . e($nombre . ': ' . dinero($valor)) . '</title></rect>';
        $svg .= '<text class="grafica__valor" x="' . round($x + $bw / 2, 1) . '" y="' . round($y - 6, 1)
              . '" text-anchor="middle">' . e(dinero($valor)) . '</text>';
        $svg .= '<text class="grafica__etiqueta" x="' . round($x + $bw / 2, 1) . '" y="' . ($h - 14)
              . '" text-anchor="middle">' . e($nombre) . '</text>';
    }
    $svg .= '<line class="grafica__base" x1="' . $izq . '" x2="' . ($w - $der) . '" y1="' . ($arr + $alto) . '" y2="' . ($arr + $alto) . '"/>';
    return $svg . '</svg>';
}

function grafica_serie(array $dias): string
{
    $w = 640; $h = 300; $izq = 64; $der = 16; $arr = 20; $aba = 44;
    $max = 0.0;
    foreach ($dias as $d) {
        $max = max($max, (float) $d['dep'], (float) $d['ret']);
    }
    $max = escala_redonda($max);
    $alto = $h - $arr - $aba;
    $ancho = $w - $izq - $der;
    $n = max(count($dias), 1);
    $slot = $ancho / $n;
    $bw = min(26, $slot * 0.32);

    $svg = '<svg class="grafica" viewBox="0 0 ' . $w . ' ' . $h . '" role="img" aria-label="Depósitos contra retiros por día">';
    for ($i = 0; $i <= 4; $i++) {
        $y = $arr + $alto - ($alto * $i / 4);
        $svg .= '<line class="grafica__guia" x1="' . $izq . '" x2="' . ($w - $der) . '" y1="' . $y . '" y2="' . $y . '"/>';
        $svg .= '<text class="grafica__eje" x="' . ($izq - 8) . '" y="' . ($y + 4) . '" text-anchor="end">'
              . e(monto_corto($max * $i / 4)) . '</text>';
    }
    $i = 0;
    foreach ($dias as $fecha => $d) {
        $cx = $izq + $slot * $i + $slot / 2;
        foreach ([['dep', 'deposito', 'Depósitos', -1], ['ret', 'retiro', 'Retiros', 1]] as [$k, $clase, $nom, $lado]) {
            $v = (float) $d[$k];
            $bh = $alto * $v / $max;
            $x = $lado < 0 ? $cx - $bw - 1 : $cx + 1;
            $svg .= '<rect class="grafica__barra grafica__barra--' . $clase . '" x="' . round($x, 1) . '" y="'
                  . round($arr + $alto - $bh, 1) . '" width="' . round($bw, 1) . '" height="' . round($bh, 1)
                  . '" rx="3"><title>' . e(date('d/m', strtotime($fecha)) . ' · ' . $nom . ': ' . dinero($v)) . '</title></rect>';
        }
        $svg .= '<text class="grafica__etiqueta" x="' . round($cx, 1) . '" y="' . ($h - 18) . '" text-anchor="middle">'
              . e(date('d/m', strtotime($fecha))) . '</text>';
        $i++;
    }
    $svg .= '<line class="grafica__base" x1="' . $izq . '" x2="' . ($w - $der) . '" y1="' . ($arr + $alto) . '" y2="' . ($arr + $alto) . '"/>';
    return $svg . '</svg>';
}
