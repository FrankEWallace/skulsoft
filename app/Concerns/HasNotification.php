<?php

namespace App\Concerns;

use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

trait HasNotification
{
    public function validateSent()
    {
        $notification = $this->getMeta('notification');
        $submittedAt = Arr::get($notification, 'submitted_at');
        $sendingAt = Arr::get($notification, 'sending_at');
        $sentAt = Arr::get($notification, 'sent_at');

        if (!empty($sentAt)) {
            throw ValidationException::withMessages(['message' => trans('general.notification.already_sent')]);
        }

        if (!empty($sendingAt)) {
            throw ValidationException::withMessages(['message' => trans('general.notification.sending')]);
        }

        if (!empty($submittedAt)) {
            $submittedTime = Carbon::parse($submittedAt);
            $timeDiff = now()->diffInMinutes($submittedTime);

            if ($timeDiff < 15) {
                throw ValidationException::withMessages(['message' => trans('general.notification.already_submitted')]);
            }
        }
    }

    public function markNotificationAsSent()
    {
        $meta = $this->meta ?? [];
        $meta['notification']['sent_at'] = now()->toDateTimeString();
        $this->meta = $meta;
        $this->save();
    }

    public function getNotificationDetail()
    {
        $isSent = false;
        $message = null;
        $type = 'info';

        $notification = $this->getMeta('notification');
        $submittedAt = Arr::get($notification, 'submitted_at');
        $sendingAt = Arr::get($notification, 'sending_at');
        $sentAt = Arr::get($notification, 'sent_at');

        $message = trans('global.notification.not_submitted', ['attribute' => trans('student.attendance.attendance')]);
        if (!empty($sentAt)) {
            $sentAt = \Cal::dateTime($sentAt)?->formatted;
            $message = trans('global.notification.already_sent', ['attribute' => trans('student.attendance.attendance'), 'at' => $sentAt]);
            $isSent = true;
        } elseif (!empty($sendingAt)) {
            $sendingAt = \Cal::dateTime($sendingAt)?->formatted;
            $message = trans('global.notification.sending', ['attribute' => trans('student.attendance.attendance'), 'at' => $sendingAt]);
        } elseif (!empty($submittedAt)) {

            $submittedTime = Carbon::parse($submittedAt);
            $timeDiff = now()->diffInMinutes($submittedTime);

            if ($timeDiff < 15) {
                $submittedAt = \Cal::dateTime($submittedAt)?->formatted;
                $message = trans('global.notification.already_submitted', ['attribute' => trans('student.attendance.attendance'), 'at' => $submittedAt]);
            } else {
                $message = trans('global.notification.failed', ['attribute' => trans('student.attendance.attendance')]);
                $type = 'danger';
            }
        }

        return [
            'is_sent' => $isSent,
            'message' => $message,
            'type' => $type,
        ];
    }
}
