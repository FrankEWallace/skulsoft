<?php

namespace App\Enums\Exam;

use App\Concerns\HasEnum;

enum OnlineExamQuestionType: string
{
    use HasEnum;

    case MCQ = 'mcq';
    case SINGLE_LINE_QUESTION = 'single_line_question';
    case MULTI_LINE_QUESTION = 'multi_line_question';
    // case FILE_UPLOAD = 'file_upload';

    public static function translation(): string
    {
        return 'exam.online_exam.question_types.';
    }
}
