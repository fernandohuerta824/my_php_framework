<?php 

namespace Model;

use Model\Imagen as Imagen;
use mysqli;

abstract class ActiveRecord {
    protected static mysqli $db;

    protected static string $tabla = '';

    protected static array $columnas = [];

    protected static array $errores = [];

    public int $id;
    public Imagen $imagen;
 
    public static function setDB(mysqli $db) {
        self::$db = $db;
    }
    
    public function guardar() {
        if(!empty(static::$errores)) 
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
            // if($key === 'imagen') {
                //     $atributos[$key] = $value->getNombreFinal();
                //     continue;
                // }
                
            if($key === 'id') 
                continue;
            
            if(gettype($value) === 'string')
                $atributos[$key] = self::$db->real_escape_string($value);
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
    
            
            $instancia = new static($columnas);
    
            
            if (isset($registro['imagen'])) {
                $imagenValores = [
                    'name' => $registro['imagen'],
                    'size' => 1,
                    'tmp_name' => $instancia->imagen->getRutaParaGuardar() . $registro['imagen']
                ];
                $instancia->imagen = $imagenValores; 
            }
    
            $array[] = $instancia;
        }

        $resultado->free();
        return $array;
    }

    public static function getErrores(): array {
        return static::$errores;
    }   

    public static function todos(): array {
        $query = "SELECT * FROM " . static::$tabla;

        return self::consultar($query);
    }

    public static function obtener(int $cantidad, int $saltar): array {
        $query = "SELECT * FROM " . static::$tabla . " LIMIT $cantidad OFFSET $saltar";

        return self::consultar($query);
    }

    public static function SQL(string $consulta): array {
        return self::consultar($consulta);
    }

    public static function where(string $columna, string $valor): ActiveRecord|null {
        $valorQuery = self::$db->real_escape_string($valor);
        $query = "SELECT * FROM " . static::$tabla . " WHERE $columna = '$valorQuery' LIMIT 1";
        return self::consultar($query)[0] ?? null;
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
                    $this->$key = null;
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