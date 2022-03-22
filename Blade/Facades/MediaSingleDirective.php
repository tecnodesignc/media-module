<?php

namespace Modules\Media\Blade\Facades;

use Illuminate\Support\Facades\Facade;

class MediaSingleDirective extends Facade
{
    /**
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'media.single.directive';
    }
}
