<?php

namespace App\Support;

use App\Models\Guardian;
use App\Models\Student\Student;
use App\Models\User;

trait AsStudentOrGuardian
{
    public function getStudentContactIds(?User $user = null): array
    {
        $user ??= auth()->user();

        if (! $user->is_student_or_guardian) {
            return [];
        }

        if ($user->hasRole('guardian')) {
            return Guardian::query()
                ->select('guardians.primary_contact_id')
                ->join('contacts', 'guardians.contact_id', '=', 'contacts.id')
                ->where('contacts.user_id', '=', $user->id)
                ->pluck('guardians.primary_contact_id')
                ->all();
        }

        return Student::query()
            ->join('contacts', function ($join) use ($user) {
                $join->on('students.contact_id', '=', 'contacts.id')
                    ->where('contacts.user_id', '=', $user->id);
            })
            ->get()
            ->pluck('contact_id')
            ->all();
    }
}
