<?php

namespace Model;

use Exception;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\WebpEncoder;



class Imagen {
    protected static string $rutaParaGuardar = '';
    protected static int $tamanioMaximo = 0; // 4MB

    public $image;
    public string $name;
    public string $extension;
    public string $fullName;
    public string $nombreFinal;
    public string $tmpPath;
    public int $size;

    public function __construct(array $args = [], bool $isNew = true) {
        if($isNew)
            $this->tmpPath = $args['tmp_name'] ?? '';
        else 
            $this->tmpPath = static::$rutaParaGuardar . $args['name'] . '.png';
        
        $this->fullName = $args['name'] ?? '';
        
        if($isNew)
            $this->name = substr($args['name'], 0, strpos($args['name'], '.'));
        else
            $this->name = $args['name'] ?? '';
        if($isNew)
            $this->extension = substr($args['name'], strpos($args['name'], '.') + 1);
        else
            $this->extension = 'png';
        
        if($isNew)
            $this->nombreFinal = md5(uniqid(rand(), true));
        else 
            $this->nombreFinal = $args['name'];
        
        $this->size = $args['size'] ?? 0;
        if(!is_file($this->tmpPath))
            return;
        $manager = new ImageManager(Driver::class);
        
        try {
            $this->image = $manager->read($this->tmpPath);
        } catch(Exception $e) {
            $this->tmpPath = '';
            $this->fullName = '';
            $this->name = '';
        }
    }

    public function guardar(int $width = null, int $height = null, bool $baseFormat = true, bool $png = false, bool $webp = false, bool $avif = false) {
        if(!is_dir(static::$rutaParaGuardar)) 
            mkdir(static::$rutaParaGuardar, 0777, true);

        
        try {
            $this->image->resize(width: $width, height: $height);
            if($baseFormat) {
                $this->image->save(static::$rutaParaGuardar . $this->nombreFinal . '.' . $this->extension);
            }

            if($png) {
                $this->image->toPng()->save(static::$rutaParaGuardar . $this->nombreFinal . '.png');
            }

            if($webp) {
                $this->image->toWebp()->save(static::$rutaParaGuardar . $this->nombreFinal . '.webp');
            }

            if($avif) {
                $this->image->toAvif()->save(static::$rutaParaGuardar . $this->nombreFinal . '.avif');
        }
        } catch(Exception $e) {
            $this->borrar();
            throw new Exception('Error al guardar la imagen');
        }
    }

    public function borrar() {
        $archivos = glob(static::$rutaParaGuardar . $this->nombreFinal . '.*');
        foreach($archivos as $archivo) {
            unlink($archivo);
        }
    } 

    public function getNombreFinal(): string {
        return $this->nombreFinal;
    }

    public static function getRutaParaGuardar(): string {
        return static::$rutaParaGuardar;
    }

    public static function getTamanioMaximo() : int {
        return static::$tamanioMaximo;
    }

    public function validarTamanio(): bool {
        return ($this->size / 1024) / 1024 < static::$tamanioMaximo;
    }
}