<?php

namespace Modules\Media\Events\Handlers;

use Illuminate\Support\Facades\DB;
use Modules\Media\Contracts\DeletingMedia;
use Modules\Media\Repositories\FileRepository;

class RemovePolymorphicLink
{
    public function handle($event = null)
    {
        if ($event instanceof DeletingMedia) {
            $file = app(FileRepository::class);
            $imageables = DB::table('media__imageables')->where('imageable_id', $event->getEntityId())
                ->where('imageable_type', $event->getClassName());
            if ($imageables->count() > 0) {
                foreach ($imageables->get() as $imageable) {
                    $media = $file->find($imageable->file_id);
                    $imageables->delete();
                    $file->destroy($media);
                }

            }

        }
    }
}
