<?php

namespace App\Helpers;

class NumeroALetras
{
    private $unidades = [
        '', 'UNO', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE',
        'DIEZ', 'ONCE', 'DOCE', 'TRECE', 'CATORCE', 'QUINCE', 'DIECISÉIS', 'DIECISIETE', 'DIECIOCHO', 'DIECINUEVE',
    ];

    private $decenas = [
        '', '', 'VEINTE', 'TREINTA', 'CUARENTA', 'CINCUENTA', 'SESENTA', 'SETENTA', 'OCHENTA', 'NOVENTA',
    ];

    private $centenas = [
        '', 'CIENTO', 'DOSCIENTOS', 'TRESCIENTOS', 'CUATROCIENTOS', 'QUINIENTOS',
        'SEISCIENTOS', 'SETECIENTOS', 'OCHOCIENTOS', 'NOVECIENTOS',
    ];

    /**
     * Convierte un número a letras con moneda
     *
     * @param  float  $numero
     * @param  string  $moneda
     * @return string
     */
    public function convertir($numero, $moneda = 'SOLES')
    {
        $numero = number_format($numero, 2, '.', '');
        $partes = explode('.', $numero);
        $entero = (int) $partes[0];
        $decimal = (int) $partes[1];

        $letras = $this->convertirEntero($entero).' CON '.str_pad($decimal, 2, '0', STR_PAD_LEFT)."/100 $moneda";

        return $letras;
    }

    private function convertirEntero($num)
    {
        if ($num == 0) {
            return 'CERO';
        }
        if ($num < 20) {
            return $this->unidades[$num];
        }
        if ($num < 100) {
            $decena = intval($num / 10);
            $unidad = $num % 10;

            return $this->decenas[$decena].($unidad > 0 ? ' Y '.$this->unidades[$unidad] : '');
        }
        if ($num < 1000) {
            $centena = intval($num / 100);
            $resto = $num % 100;
            if ($num == 100) {
                return 'CIEN';
            }

            return $this->centenas[$centena].($resto > 0 ? ' '.$this->convertirEntero($resto) : '');
        }
        if ($num < 1000000) {
            $miles = intval($num / 1000);
            $resto = $num % 1000;
            $str = ($miles == 1 ? 'MIL' : $this->convertirEntero($miles).' MIL');
            if ($resto > 0) {
                $str .= ' '.$this->convertirEntero($resto);
            }

            return $str;
        }
        // millones
        $millones = intval($num / 1000000);
        $resto = $num % 1000000;
        $str = ($millones == 1 ? 'UN MILLÓN' : $this->convertirEntero($millones).' MILLONES');
        if ($resto > 0) {
            $str .= ' '.$this->convertirEntero($resto);
        }

        return $str;
    }
}
