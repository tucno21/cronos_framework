<?php

namespace App\Help;

class ImageBuilder
{
    protected $fileImage;
    protected $width;
    protected $height;
    protected $photoDelete = '';
    protected $format = ImageFormat::WEBP;
    protected $quality = 90;
    protected $maintainAspectRatio = false;

    public function __construct(array $fileImage)
    {
        $this->fileImage = $fileImage;
    }

    public function size(int $width, ?int $height = null): self
    {
        $this->width = $width;
        $this->height = $height;
        return $this;
    }

    public function delete(string $photoDelete): self
    {
        $this->photoDelete = $photoDelete;
        return $this;
    }

    public function format(ImageFormat $format): self
    {
        $this->format = $format;
        return $this;
    }

    public function quality(int $quality): self
    {
        $this->quality = $quality;
        return $this;
    }

    public function maintainAspectRatio(bool $maintainAspectRatio): self
    {
        $this->maintainAspectRatio = $maintainAspectRatio;
        return $this;
    }

    public function save(): ?string
    {
        // Llamar al método processImage de MoveFileImagen con las opciones configuradas
        return MoveFileImagen::processImage(
            $this->fileImage,
            $this->width,
            $this->height,
            $this->photoDelete,
            $this->format,
            $this->quality,
            $this->maintainAspectRatio
        );
    }
}
