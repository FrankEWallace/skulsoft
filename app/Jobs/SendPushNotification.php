<?php

namespace App\Jobs;

use App\Models\Config\Template;
use App\Models\UserToken;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SendPushNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $users;

    protected $template;

    protected $params;

    protected $chunkSize = 20;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Collection $users, string $template, array $params = [])
    {
        $this->users = $users;
        $this->template = $template;
        $this->params = $params;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if ($this->users->count() == 0) {
            return;
        }

        $template = Template::query()
            ->where('type', 'push')
            ->where('code', $this->template)
            ->first();

        if (! $template) {
            return;
        }

        $to = UserToken::query()
            ->select('token')
            ->whereType('expo-push-token')
            ->whereIn('user_id', $this->users->pluck('id')->all())
            ->get()
            ->pluck('token')
            ->all();

        $title = $template->subject;
        $content = $template->content;

        $variables = Arr::get($this->params, 'variables', []);

        foreach ($variables as $key => $variable) {
            $content = Str::replace('##'.strtoupper($key).'##', $variable, $content);
        }

        $data = Arr::get($this->params, 'data', []);

        collect($to)->chunk($this->chunkSize)->each(function ($chunk) use ($title, $content, $data) {
            $messages = [];

            foreach ($chunk as $address) {
                $messages[] = [
                    'sound' => 'default',
                    'to' => $address,
                    'title' => $title,
                    'body' => $content,
                    'data' => $data,
                ];
            }

            Http::post('https://exp.host/--/api/v2/push/send', $messages);
        });
    }
}
