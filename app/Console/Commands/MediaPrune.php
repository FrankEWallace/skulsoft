<?php

namespace App\Console\Commands;

use App\Models\Media;
use Illuminate\Console\Command;

class MediaPrune extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'media:prune';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prune unused media';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $media = Media::query()
            ->select('name', 'id')
            ->where('created_at', '<', now()->subHour(1)->toDateTimeString())
            ->where(function ($q) {
                $q->whereNull('model_id')
                    ->orWhere('status', 0);
            })
            ->get();

        $media->each(function (Media $media) {
            if (\Storage::exists($media->name)) {
                \Storage::delete($media->name);
            }
        });

        Media::whereIn('id', $media->pluck('id')->all())->delete();

        $this->info($media->count().' unused media pruned.');
    }
}
