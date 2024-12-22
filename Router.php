<?php

namespace MVC;

class Router {
    protected array $rutasGET  = [];

    protected array $rutasPOST = [];

    public function __construct() {
        
    }

    public function get(string $url, $fn) {
        $this->rutasGET[$url] = $fn;    
    }

    public function post(string $url, $fn) {
        $this->rutasPOST[$url] = $fn;
    }

    public function comprobarRutas() {
        $url = strtok($_SERVER['REQUEST_URI'], '?') ?? '/';
        $metodo = $_SERVER['REQUEST_METHOD'];

        if($metodo === 'GET') 
            $fn = $this->rutasGET[$url] ?? null;
    
        if($metodo === 'POST') 
            $fn = $this->rutasPOST[$url] ?? null;
        
        if($fn) 
            return call_user_func($fn, $this);

        http_response_code(404);     
        $this->render('404', ['titulo' => '404']);  
    }

    public function render(string $view, array $args = []) {
        foreach($args as $key => $value) 
            $$key = $value;

        ob_start();
        include __DIR__ . "/views/$view.php";
        $contenido = ob_get_clean();
        include __DIR__ . "/views/layout.php";
    }
}