<?php

namespace Modules\Media\Entities;

use Illuminate\Database\Eloquent\Model;

class FileTranslation extends Model
{
    /**
     * @var bool
     */
    public $timestamps = false;
    /**
     * @var array|string[]
     */
    protected $fillable = ['description', 'alt_attribute', 'keywords'];
    /**
     * @var string
     */
    protected $table = 'media__file_translations';
}
