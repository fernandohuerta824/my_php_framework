<?php

namespace Classes;

class Paginacion {
    public int $paginaActual;
    public int $registroPorPagina;
    public int $totalRegistros;
    public int $pivoteNumeros;

    public function __construct(int $paginaActual = 1, int $registroPorPagina = 10, int $totalRegistros = 0, $pivoteNumeros = 9) {
        $this->pivoteNumeros = $pivoteNumeros;
        $this->registroPorPagina = (int) $registroPorPagina;
        $this->totalRegistros = (int) $totalRegistros;
        $this->paginaActual = (int) $paginaActual < 1 ? 1 : ((int) $paginaActual > $this->totalPaginas() ? $this->totalPaginas() : (int) $paginaActual);

    }

    public function offset(): int {
        return $this->registroPorPagina * ($this->paginaActual - 1);
    }

    public function totalPaginas() : int {
        return (int)ceil($this->totalRegistros / $this->registroPorPagina);
    }

    public function paginaAnterior() : int|bool {
        return $this->paginaActual - 1 < 1 ?  false : $this->paginaActual - 1 ;
    }

    public function paginaSiguiente() : int|bool {
        $totalPaginas = $this->totalPaginas();
        return $this->paginaActual + 1 > $totalPaginas ?  false : $this->paginaActual + 1 ;
    }

    
    public function enlaceAnterior () {
        $hmtl = '';
        if($this->paginaAnterior()) {
            $html = "
                <a 
                    class='paginacion__enlace paginacion__enlace--texto'     href='?page={$this->paginaAnterior()}'
                >
                    &laquo; Anterior 
                </a>
            ";

        }
        return $html;
    }
    
    public function enlaceSiguiente () {
        $hmtl = '';
        if($this->paginaSiguiente()) {
            $html = "
                <a 
                    class='paginacion__enlace paginacion__enlace--texto'     href='?page={$this->paginaSiguiente()}'
                >
                    Siguiente &raquo;
                </a>
            ";

        }
        return $html;
    }

    public function paginacion() {
        $html = '';
        if($this->totalRegistros > 1) {
            $html = "
                <div class='paginacion'>
                    {$this->enlaceAnterior()}
                    {$this->numeroPaginas()}
                    {$this->enlaceSiguiente()}
                </div>
            ";
        }

        return $html;
    }

    public function numeroPaginas() {

        if($this->registroPorPagina >= $this->totalRegistros)
            return '';
        $html = '';
        $mitad = floor($this->pivoteNumeros / 2);
        $inicio = intval(
            $this->paginaActual - $mitad <= 1 ? 
                1 : 
                ($this->paginaActual + $mitad > $this->totalPaginas() ?
                    ($this->totalPaginas() - $this->pivoteNumeros + 1 <= 1 ? 1 : $this->totalPaginas() - $this->pivoteNumeros + 1):
                    $this->paginaActual - $mitad
                )
        );
        // debug($this->paginaActual - $mitad);
        $fin = intval(
            $this->paginaActual - $mitad < 1 ? 
                $this->pivoteNumeros : 
                ($this->paginaActual + $mitad > $this->totalPaginas() ?
                    $this->totalPaginas() :
                    $this->paginaActual + $mitad
                )
        );
        
        
        for($i = $inicio; $i <= $fin && $i <= $this->totalPaginas(); $i++) {
            if($this->paginaActual === $i)
                $html .= "<span class='paginacion__numero paginacion__numero--actual'>$i</span>";
            else
                $html .= "<a class='paginacion__numero' class='' href='?page=$i'>$i</a>";
        }

        return $html;
    }
}