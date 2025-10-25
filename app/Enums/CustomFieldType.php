<?php

namespace App\Enums;

use App\Concerns\HasEnum;

enum CustomFieldType: string
{
    use HasEnum;

    case TEXT_INPUT = 'text_input';
    case EMAIL_INPUT = 'email_input';
    case NUMBER_INPUT = 'number_input';
    case CURRENCY_INPUT = 'currency_input';
    case MULTI_LINE_TEXT_INPUT = 'multi_line_text_input';
    case DATE_PICKER = 'date_picker';
    case TIME_PICKER = 'time_picker';
    case DATE_TIME_PICKER = 'date_time_picker';
    case SELECT_INPUT = 'select_input';
    case MULTI_SELECT_INPUT = 'multi_select_input';
    case CHECKBOX_INPUT = 'checkbox_input';
    case RADIO_INPUT = 'radio_input';
    case PARAGRAPH = 'paragraph';
    case CAMERA_IMAGE = 'camera_image';
    // case LINE_BREAK = 'line_break';
    case FILE_UPLOAD = 'file_upload';

    public static function translation(): string
    {
        return 'custom_field.types.';
    }
}
