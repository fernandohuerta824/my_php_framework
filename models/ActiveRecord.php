<?php 

namespace Model;

use Model\Imagen as Imagen;
use mysqli;
use Model\PonenteImagen;

abstract class ActiveRecord {
    protected static mysqli $db;

    protected static string $tabla = '';

    protected static array $columnas = [];

    protected static array $alertas = [];

    public int $id;
    public Imagen|null $imagen;
 
    public static function setDB(mysqli $db) {
        self::$db = $db;
    }
    
    public function guardar() {
        if(!empty(static::$alertas)) 
            return false;
        $atributos = $this->sanitizarAtributos();
        
        $columnas = join(', ', array_keys($atributos));

        $valores = join("', '", array_values($atributos));
        
        
        if($this->id) {
            $registro = self::encontrarPorID($this->id);

            if(!$registro) 
                return false;

            $query = "UPDATE " . static::$tabla . " SET ";
            $query .= join(', ', array_map(fn($key) => "$key = '$atributos[$key]'", array_keys($atributos)));
            $query .= " WHERE id = " . $this->id;
        } else {
            $query = "INSERT INTO " . static::$tabla . " ($columnas) VALUES ('$valores')";
        }

        self::$db->query($query);
        
        return self::$db->insert_id ?? false;
    }

    private function sanitizarAtributos(): array {
        $atributos = $this->atributos();
        foreach($atributos as $key => $value) {
            if($key === 'imagen') {
                $atributos[$key] = $value->getNombreFinal();
                continue;
            }
                
            if($key === 'id') 
                continue;
            
            if(gettype($value) === 'string')
                $atributos[$key] = self::$db->real_escape_string($value);
            if(gettype($value) === 'NULL')
                $atributos[$key] = NULL;
        }
        
       
        return $atributos;
    }

    private function atributos(): array {
        $atributos = [];
        foreach(static::$columnas as $columna) 
            $atributos[$columna] = $this->$columna;

        return $atributos;
    }

    public static function encontrarPorID(int|string $id): ActiveRecord|null {
        $id = intval($id);
        $query = "SELECT * FROM " . static::$tabla . " WHERE id = $id";

        return self::consultar($query)[0];
    }

    protected static function consultar(string $query): array {
        $resultado = self::$db->query($query);

        if(!$resultado) 
            return [];
        $columnas = [];
        $array = [];

        while ($registro = $resultado->fetch_assoc()) {
            $columnas = $registro;
    
            if (isset($registro['imagen'])) {
                $clase = static::class . 'Imagen';
                
                $columnas['imagen'] = new $clase(['name' => $registro['imagen']], false);
 
            }
            $instancia = new static($columnas);
            
            $array[] = $instancia;
        }

        $resultado->free();
        return $array;
    }

    public static function getAlertas(): array {
        return static::$alertas;
    }   

    public static function todos(string $orden = 'ASC'): array {
        $query = "SELECT * FROM " . static::$tabla . " ORDER BY id $orden";
        return self::consultar($query);
    }

    public static function obtener(int $cantidad, int $saltar): array {
        $query = "SELECT * FROM " . static::$tabla . " LIMIT $cantidad OFFSET $saltar";

        return self::consultar($query);
    }

    public static function SQL(string $consulta): array {
        return self::consultar($consulta);
    }

    public static function totalDeRegistros(string $columna = '', string $valor = ''): int {
        $query = 'SELECT count(*) as totalRegistros FROM ' . static::$tabla;
        if($columna)
            $query .= " WHERE $columna = $valor"; 
        $resultado = self::$db->query($query);
        $numero = (int)$resultado->fetch_assoc()['totalRegistros'];
        return $numero;
    }

    public static function paginar(int $porPagina, int $offset) : array {
        $query = "SELECT * FROM " . static::$tabla . " LIMIT $porPagina offset $offset";
        return self::consultar($query);
    }

    public static function where(string $columna, string $valor): ActiveRecord|null {
        $valorQuery = self::$db->real_escape_string($valor);
        $query = "SELECT * FROM " . static::$tabla . " WHERE $columna = '$valorQuery' LIMIT 1";
        return self::consultar($query)[0] ?? null;
    }

    public static function whereArray(array $array = []): array {
        $query = "SELECT * FROM " . static::$tabla . " WHERE ";
        foreach($array as $key => $value) {
            $query .= "$key = '$value' AND ";
        }
        $query = substr($query, 0, -5);

        return self::consultar($query);
    }

    public static function ordenar(string $columna, string $orden = 'ASC') {
        $query = "SELECT * FROM " . static::$tabla . " ORDER BY $columna $orden";
        return self::consultar($query);
    }
    
    public static function belongsTo(string $columna, string $valor): array {
        $valorQuery = self::$db->real_escape_string($valor);
        $query = "SELECT * FROM " . static::$tabla . " WHERE $columna = '$valorQuery' ";
        
        return self::consultar($query);
    }

    public function borrar(): mysqli|bool {
        $query = "DELETE FROM " . static::$tabla . " WHERE id = " . $this->id;

        return self::$db->query($query);
    }

    public function sincronizar($args = []) {
        foreach($args as $key => $value) {
            if($key === 'id')
                continue;
            if(property_exists($this, $key) && !is_null($value))
                if(gettype($this->$key) === 'integer' || gettype($this->$key) === 'double')
                    $this->$key = !$value ?  0 : $value;
                else
                    $this->$key = $value;

        }
    }

    public function resetar() {
        foreach($this as $key => $value) {
            if($key === 'id')
                continue;
            switch (gettype($value)) {
                case 'string':
                    $this->$key = '';
                    break;
                case 'integer':
                case 'double': // Los números flotantes también caen aquí.
                    $this->$key = 0;
                    break;
                case 'array':
                    $this->$key = [];
                    break;
                case 'object':
                    $this->$key = new Imagen([]);
                    break; // Opcional: o puedes devolver una nueva instancia.
                case 'boolean':
                    $this->$key = false;
                    break;
                case 'NULL':
                    $this->$key = null;
                    break;
                default:
                    $this->$key = null;
                    break; // Para otros tipos no especificados.
            }
        }
    }

    public function getId(): int {
        return $this->id;
    }

    public function getImagen(): Imagen {
        return $this->imagen;
    }
}