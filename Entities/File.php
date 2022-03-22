<?php

namespace Modules\Media\Entities;

use Astrotomic\Translatable\Translatable;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Core\Traits\NamespacedEntity;
use Modules\Media\Helpers\FileHelper;
use Modules\Media\Image\Facade\Imagy;
use Modules\Media\ValueObjects\MediaPath;
use Modules\Tag\Contracts\TaggableInterface;
use Modules\Tag\Traits\TaggableTrait;
use Modules\User\Entities\Sentinel\User;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Class File
 * @package Modules\Media\Entities
 * @property MediaPath path
 */
class File extends Model implements TaggableInterface, Responsable
{
    use Translatable, NamespacedEntity, TaggableTrait;

    /**
     * All the different images types where thumbnails should be created
     * @var array
     */
    private array $imageExtensions = ['jpg', 'png', 'jpeg', 'gif'];

    /**
     * @var string
     */
    protected $table = 'media__files';
    /**
     * @var array|string[]
     */
    public array $translatedAttributes = ['description', 'alt_attribute', 'keywords'];
    /**
     * @var array|string[]
     */
    protected $fillable = [
        'id',
        'is_folder',
        'description',
        'alt_attribute',
        'keywords',
        'filename',
        'path',
        'extension',
        'mimetype',
        'width',
        'height',
        'filesize',
        'folder_id',
        'created_by',
    ];
    /**
     * @var array|string[]
     */
    protected $appends = ['path_string', 'media_type'];
    /**
     * @var array|string[]
     */
    protected $casts = ['is_folder' => 'boolean'];
    /**
     * @var string
     */
    protected static string $entityNamespace = 'encorecms/media';

    /**
     * @return mixed
     */
    public function parent_folder(): mixed
    {
        return $this->belongsTo(__CLASS__, 'folder_id');
    }

    /**
     * @param $value
     * @return MediaPath
     */
    public function getPathAttribute($value): MediaPath
    {
        return new MediaPath($value);
    }

    /**
     * @return string
     */
    public function getPathStringAttribute(): string
    {
        return (string)$this->path;
    }

    /**
     * @return string
     */
    public function getMediaTypeAttribute(): string
    {
        return FileHelper::getTypeByMimetype($this->mimetype);
    }

    /**
     * @return bool
     */
    public function isFolder(): bool
    {
        return $this->is_folder;
    }

    /**
     * @return bool
     */
    public function isImage(): bool
    {
        return in_array(pathinfo($this->path, PATHINFO_EXTENSION), $this->imageExtensions);
    }

    /**
     * @param $type
     * @return bool
     */
    public function getThumbnail($type): bool
    {
        if ($this->isImage() && $this->getKey()) {
            return Imagy::getThumbnail($this->path, $type);
        }

        return false;
    }

    /**
     * Create an HTTP response that represents the object.
     * @param Request $request
     * @return BinaryFileResponse
     */
    public function toResponse($request): BinaryFileResponse
    {
        return response()
            ->file(public_path($this->path->getRelativeUrl()), [
                'Content-Type' => $this->mimetype,
            ]);
    }

    /**
     * Created by relation
     * @return BelongsTo
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Created by relation
     * @return BelongsTo
     */
    public function folder(): BelongsTo
    {
        return $this->belongsTo(File::class, 'folder_id');
    }


}
