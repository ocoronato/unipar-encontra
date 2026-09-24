<?php

namespace App\Actions;

use App\Models\LostFoundItem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Salva as fotos de um objeto no disco "public" (Laravel Storage).
 *
 * Cada imagem é recriada com a extensão GD, nativa do PHP. Isso:
 * - remove os metadados EXIF, como a localização GPS de onde a foto foi
 *   tirada (uma foto feita em casa revelaria o endereço do usuário);
 * - reduz fotos grandes para no máximo 1600 px, deixando as páginas mais leves.
 */
class StoreItemPhotos
{
    public const MAX_DIMENSION = 1600;

    /**
     * @param  array<UploadedFile>  $files
     */
    public function __invoke(LostFoundItem $item, array $files): void
    {
        // Converte todas antes de gravar: se alguma falhar, nada é salvo.
        $images = array_map(fn (UploadedFile $file) => [$file, $this->toJpeg($file)], $files);

        foreach ($images as [$file, $jpeg]) {
            $path = "items/{$item->id}/".Str::uuid().'.jpg';

            Storage::disk('public')->put($path, $jpeg);

            $item->photos()->create([
                'path' => $path,
                'original_name' => Str::limit($file->getClientOriginalName(), 250, ''),
            ]);
        }
    }

    private function toJpeg(UploadedFile $file): string
    {
        // Decodificar uma foto de celular pode exigir mais memória que o padrão.
        ini_set('memory_limit', '512M');

        $source = @imagecreatefromstring(file_get_contents($file->getRealPath()));

        if ($source === false) {
            throw ValidationException::withMessages([
                'photos' => "Não foi possível processar a foto \"{$file->getClientOriginalName()}\". Tente outra imagem.",
            ]);
        }

        // Redimensiona mantendo a proporção.
        $width = imagesx($source);
        $height = imagesy($source);
        $scale = min(1, self::MAX_DIMENSION / max($width, $height));
        $newWidth = (int) round($width * $scale);
        $newHeight = (int) round($height * $scale);

        // Fundo branco para PNGs transparentes (JPEG não tem transparência).
        $image = imagecreatetruecolor($newWidth, $newHeight);
        imagefill($image, 0, 0, imagecolorallocate($image, 255, 255, 255));
        imagecopyresampled($image, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        $image = $this->fixOrientation($image, $file);

        ob_start();
        imagejpeg($image, null, 85);

        return ob_get_clean();
    }

    /**
     * Celulares salvam a rotação da foto no EXIF. Como o EXIF é removido,
     * a rotação é aplicada na própria imagem.
     */
    private function fixOrientation(\GdImage $image, UploadedFile $file): \GdImage
    {
        if (! function_exists('exif_read_data') || $file->getMimeType() !== 'image/jpeg') {
            return $image;
        }

        $orientation = @exif_read_data($file->getRealPath())['Orientation'] ?? 1;

        return match ($orientation) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => $image,
        };
    }
}
