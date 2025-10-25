<?php

namespace App\Lists;

use App\Concerns\ListItem;

class SampleList
{
    use ListItem;

    protected static $items = [
        'DATA1' => 'data1',
        'DATA2' => 'data2',
    ];

    protected static $details = [];

    protected static $trans = '';
}
