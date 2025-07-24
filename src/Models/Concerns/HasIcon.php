<?php

namespace Waterhole\Models\Concerns;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Http\UploadedFile;
use Intervention\Image\Image as ImageObject;
use Intervention\Image\ImageManager;

/**
 * Methods to manage a model's `icon` attribute.
 *
 * The `icon` attribute is stored as `type:value`, where `type` is one of the
 * following:
 *
 * - `emoji`, where `value` is an emoji character (eg. `emoji:😊`)
 * - `svg`, where `value` is the name of a Blade Icon (eg. `svg:tabler-heart`)
 * - `file`, where `value` is the path to an image file
 *
 * @property string $icon
 * @property ?string $icon_file The path to the icon file, if the icon is the
 *   `file` type.
 */
trait HasIcon
{
    use HasImageAttributes;

    /**
     * Save the icon using input from an <x-waterhole::icon-picker> component.
     */
    public function saveIcon(array $icon): void
    {
        if (empty($icon['type'])) {
            $this->removeImage('icon_file', 'icons');
            $this->icon = null;
            $this->save();

            return;
        }

        if ($icon['type'] === 'file') {
            if ($icon['file'] ?? null instanceof UploadedFile) {
                // TODO: support SVG
                $this->uploadImage(ImageManager::imagick()->read($icon['file']), 'icon_file', 'icons', function (
                    ImageObject $image,
                ) {
                    return $image
                        ->resize(50, 50, function ($constraint) {
                            $constraint->aspectRatio();
                            $constraint->upsize();
                        })
                        ->encode('png');
                });
            }
        } else {
            $this->icon = $icon['type'] . ':' . ($icon[$icon['type']] ?? '');
            $this->save();
        }
    }

    protected function iconFile(): Attribute
    {
        return Attribute::make(
            get: fn($value, array $attributes) => str_starts_with(
                $attributes['icon'] ?? '',
                'file:',
            )
                ? substr($attributes['icon'], 5)
                : null,
            set: fn(?string $value) => ['icon' => $value ? 'file:' . $value : null],
        );
    }
}
