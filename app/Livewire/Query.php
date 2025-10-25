<?php

namespace App\Livewire;

use App\Enums\Reception\QueryStatus;
use App\Models\Reception\Query as ReceptionQuery;
use App\Support\FormatCodeNumber;
use Illuminate\Support\Arr;
use Livewire\Component;

class Query extends Component
{
    use FormatCodeNumber;

    public $email = '';

    public $name = '';

    public $phone = '';

    public $subject = '';

    public $message = '';

    public $response = '';

    public $error = false;

    public function render()
    {
        return view('livewire.query');
    }

    private function codeNumber(): array
    {
        $numberPrefix = config('config.reception.query_number_prefix');
        $numberSuffix = config('config.reception.query_number_suffix');
        $digit = config('config.reception.query_number_digit', 0);

        $numberFormat = $numberPrefix.'%NUMBER%'.$numberSuffix;

        $numberFormat = $this->preFormatForDate($numberFormat);

        $codeNumber = (int) ReceptionQuery::query()
            ->byTeam(1)
            ->whereNumberFormat($numberFormat)
            ->max('number') + 1;

        return $this->getCodeNumber(number: $codeNumber, digit: $digit, format: $numberFormat);
    }

    public function submit()
    {
        $this->validate([
            'email' => 'required|email|max:50',
            'name' => 'required|max:50',
            'phone' => 'required|max:50',
            'subject' => 'required|min:10|max:200',
            'message' => 'required|min:10|max:1000',
        ]);

        $codeNumber = $this->codeNumber();

        $query = ReceptionQuery::forceCreate([
            'code_number' => Arr::get($codeNumber, 'code_number'),
            'number_format' => Arr::get($codeNumber, 'number_format'),
            'number' => Arr::get($codeNumber, 'number'),
            'team_id' => 1,
            'email' => $this->email,
            'name' => $this->name,
            'phone' => $this->phone,
            'subject' => $this->subject,
            'status' => QueryStatus::SUBMITTED,
            'message' => $this->message,
        ]);

        $this->email = '';
        $this->name = '';
        $this->subject = '';
        $this->phone = '';
        $this->message = '';

        $this->response = 'Thank you for contacting us, we will get back to you in next 24 hours!';
        $this->error = false;
    }
}
