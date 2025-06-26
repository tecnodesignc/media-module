<?php

namespace Modules\Media\Console;

use Illuminate\Console\Command;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Modules\Media\Jobs\RebuildThumbnails;
use Modules\Media\Repositories\FileRepository;

class RefreshThumbnailCommand extends Command
{
    use DispatchesJobs;
    protected $name = 'encore:media:refresh';
    protected $description = 'Create and or refresh the thumbnails';
    /**
     * @var FileRepository
     */
    private FileRepository $file;

    public function __construct(FileRepository $file)
    {
        parent::__construct();
        $this->file = $file;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->line('Preparing to regenerate all thumbnails...');

        $this->dispatch(new RebuildThumbnails($this->file->all()->pluck('path')));

        $this->info('All thumbnails refreshed');
    }
}
