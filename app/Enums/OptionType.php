<?php

namespace App\Enums;

use App\Concerns\HasEnum;
use Illuminate\Support\Arr;

enum OptionType: string
{
    use HasEnum;

    case TODO_LIST = 'todo_list';
    case MEMBER_CASTE = 'member_caste';
    case MEMBER_CATEGORY = 'member_category';
    case RELIGION = 'religion';
    case QUALIFICATION_LEVEL = 'qualification_level';
    case REGISTRATION_STAGE = 'registration_stage';
    case STUDENT_ENROLLMENT_TYPE = 'student_enrollment_type';
    case STUDENT_ENROLLMENT_STATUS = 'student_enrollment_status';
    case STUDENT_DIALOGUE_CATEGORY = 'student_dialogue_category';
    case STUDENT_DOCUMENT_TYPE = 'student_document_type';
    case STUDENT_TRANSFER_REASON = 'student_transfer_reason';
    case STUDENT_LEAVE_CATEGORY = 'student_leave_category';
    case STUDENT_GROUP = 'student_group';
    case EMPLOYEE_DIALOGUE_CATEGORY = 'employee_dialogue_category';
    case EMPLOYEE_DOCUMENT_TYPE = 'employee_document_type';
    case EMPLOYMENT_STATUS = 'employment_status';
    case EMPLOYMENT_TYPE = 'employment_type';
    case EMPLOYEE_GROUP = 'employee_group';
    case VEHICLE_DOCUMENT_TYPE = 'vehicle_document_type';
    case BOOK_AUTHOR = 'book_author';
    case BOOK_LANGUAGE = 'book_language';
    case BOOK_PUBLISHER = 'book_publisher';
    case BOOK_TOPIC = 'book_topic';
    case BOOK_CONDITION = 'book_condition';
    case BOOK_CATEGORY = 'book_category';
    case CALLING_PURPOSE = 'calling_purpose';
    case VISITING_PURPOSE = 'visiting_purpose';
    case GATE_PASS_PURPOSE = 'gate_pass_purpose';
    case ENQUIRY_STAGE = 'enquiry_stage';
    case ENQUIRY_TYPE = 'enquiry_type';
    case ENQUIRY_SOURCE = 'enquiry_source';
    case COMPLAINT_TYPE = 'complaint_type';
    case SUBJECT_TYPE = 'subject_type';
    case EVENT_TYPE = 'event_type';
    case ASSIGNMENT_TYPE = 'assignment_type';
    case ANNOUNCEMENT_TYPE = 'announcement_type';
    case FEE_CONCESSION_TYPE = 'fee_concession_type';
    case TRIP_TYPE = 'trip_type';
    case INCIDENT_CATEGORY = 'incident_category';
    case BLOG_CATEGORY = 'blog_category';
    case TRANSACTION_CATEGORY = 'transaction_category';
    case APPROVAL_REQUEST_PRIORITY = 'approval_request_priority';
    case APPROVAL_REQUEST_GROUP = 'approval_request_group';
    case APPROVAL_REQUEST_NATURE = 'approval_request_nature';
    case UNIT = 'unit';

    public function detail(): array
    {
        return match ($this) {
            self::TODO_LIST => [
                'type' => 'todo_list',
                'module' => 'utility',
                'sub_module' => 'todo_list',
                'permission' => 'utility:config',
                'team' => false,
            ],
            self::MEMBER_CASTE => [
                'type' => 'caste',
                'module' => 'contact',
                'sub_module' => 'caste',
                'permission' => 'contact:config',
                'team' => true,
            ],
            self::MEMBER_CATEGORY => [
                'type' => 'category',
                'module' => 'contact',
                'sub_module' => 'category',
                'permission' => 'contact:config',
                'team' => true,
            ],
            self::RELIGION => [
                'type' => 'religion',
                'module' => 'contact',
                'sub_module' => 'religion',
                'permission' => 'contact:config',
                'team' => true,
            ],
            self::REGISTRATION_STAGE => [
                'type' => 'registration_stage',
                'module' => 'student',
                'sub_module' => 'registration_stage',
                'permission' => 'student:config',
                'team' => true,
            ],
            self::STUDENT_ENROLLMENT_TYPE => [
                'type' => 'student_enrollment_type',
                'module' => 'student',
                'sub_module' => 'enrollment_type',
                'permission' => 'student:config',
                'team' => true,
            ],
            self::STUDENT_ENROLLMENT_STATUS => [
                'type' => 'student_enrollment_status',
                'module' => 'student',
                'sub_module' => 'enrollment_status',
                'permission' => 'student:config',
                'team' => true,
            ],
            self::STUDENT_DOCUMENT_TYPE => [
                'type' => 'student_document_type',
                'module' => 'student',
                'sub_module' => 'document_type',
                'permission' => 'student:config',
                'team' => true,
            ],
            self::STUDENT_DIALOGUE_CATEGORY => [
                'type' => 'student_dialogue_category',
                'module' => 'student',
                'sub_module' => 'dialogue_category',
                'permission' => 'student:config',
                'team' => true,
            ],
            self::STUDENT_TRANSFER_REASON => [
                'type' => 'student_transfer_reason',
                'module' => 'student',
                'sub_module' => 'transfer_reason',
                'permission' => 'student:config',
                'team' => true,
            ],
            self::STUDENT_LEAVE_CATEGORY => [
                'type' => 'student_leave_category',
                'module' => 'student',
                'sub_module' => 'leave_category',
                'permission' => 'student:config',
                'team' => true,
            ],
            self::STUDENT_GROUP => [
                'type' => 'student_group',
                'module' => 'student',
                'sub_module' => 'group',
                'permission' => 'student:config',
                'team' => true,
            ],
            self::QUALIFICATION_LEVEL => [
                'type' => 'qualification_level',
                'module' => 'employee',
                'sub_module' => 'qualification_level',
                'permission' => 'employee:config',
                'team' => true,
            ],
            self::EMPLOYEE_DIALOGUE_CATEGORY => [
                'type' => 'employee_dialogue_category',
                'module' => 'employee',
                'sub_module' => 'dialogue_category',
                'permission' => 'employee:config',
                'team' => true,
            ],
            self::EMPLOYEE_DOCUMENT_TYPE => [
                'type' => 'employee_document_type',
                'module' => 'employee',
                'sub_module' => 'document_type',
                'permission' => 'employee:config',
                'team' => true,
            ],
            self::EMPLOYMENT_STATUS => [
                'type' => 'employment_status',
                'module' => 'employee',
                'sub_module' => 'employment_status',
                'permission' => 'employee:config',
                'team' => true,
            ],
            self::EMPLOYEE_GROUP => [
                'type' => 'employee_group',
                'module' => 'employee',
                'sub_module' => 'group',
                'permission' => 'employee:config',
                'team' => true,
            ],
            self::EMPLOYMENT_TYPE => [
                'type' => 'employment_type',
                'module' => 'employee',
                'sub_module' => 'employment_type',
                'permission' => 'employee:config',
                'team' => true,
            ],
            self::VEHICLE_DOCUMENT_TYPE => [
                'type' => 'vehicle_document_type',
                'module' => 'transport.vehicle',
                'sub_module' => 'document_type',
                'permission' => 'vehicle:config',
                'team' => true,
            ],
            self::BOOK_AUTHOR => [
                'type' => 'book_author',
                'module' => 'library',
                'sub_module' => 'book_author',
                'permission' => 'library:config',
                'team' => true,
            ],
            self::BOOK_PUBLISHER => [
                'type' => 'book_publisher',
                'module' => 'library',
                'sub_module' => 'book_publisher',
                'permission' => 'library:config',
                'team' => true,
            ],
            self::BOOK_LANGUAGE => [
                'type' => 'book_language',
                'module' => 'library',
                'sub_module' => 'book_language',
                'permission' => 'library:config',
                'team' => true,
            ],
            self::BOOK_TOPIC => [
                'type' => 'book_topic',
                'module' => 'library',
                'sub_module' => 'book_topic',
                'permission' => 'library:config',
                'team' => true,
            ],
            self::BOOK_CONDITION => [
                'type' => 'book_condition',
                'module' => 'library',
                'sub_module' => 'book_condition',
                'permission' => 'library:config',
                'team' => true,
            ],
            self::BOOK_CATEGORY => [
                'type' => 'book_category',
                'module' => 'library',
                'sub_module' => 'book_category',
                'permission' => 'library:config',
                'team' => true,
            ],
            self::CALLING_PURPOSE => [
                'type' => 'calling_purpose',
                'module' => 'reception.call_log',
                'sub_module' => 'purpose',
                'permission' => 'reception:config',
                'team' => true,
            ],
            self::VISITING_PURPOSE => [
                'type' => 'visiting_purpose',
                'module' => 'reception.visitor_log',
                'sub_module' => 'purpose',
                'permission' => 'reception:config',
                'team' => true,
            ],
            self::GATE_PASS_PURPOSE => [
                'type' => 'gate_pass_purpose',
                'module' => 'reception.gate_pass',
                'sub_module' => 'purpose',
                'permission' => 'reception:config',
                'team' => true,
            ],
            self::ENQUIRY_STAGE => [
                'type' => 'enquiry_stage',
                'module' => 'reception.enquiry',
                'sub_module' => 'stage',
                'permission' => 'reception:config',
                'team' => true,
            ],
            self::ENQUIRY_TYPE => [
                'type' => 'enquiry_type',
                'module' => 'reception.enquiry',
                'sub_module' => 'type',
                'permission' => 'reception:config',
                'team' => true,
            ],
            self::ENQUIRY_SOURCE => [
                'type' => 'enquiry_source',
                'module' => 'reception.enquiry',
                'sub_module' => 'source',
                'permission' => 'reception:config',
                'team' => true,
            ],
            self::COMPLAINT_TYPE => [
                'type' => 'complaint_type',
                'module' => 'reception.complaint',
                'sub_module' => 'type',
                'permission' => 'reception:config',
                'team' => true,
            ],
            self::SUBJECT_TYPE => [
                'type' => 'subject_type',
                'module' => 'academic.subject',
                'sub_module' => 'type',
                'permission' => 'academic:config',
                'team' => true,
            ],
            self::EVENT_TYPE => [
                'type' => 'event_type',
                'module' => 'calendar.event',
                'sub_module' => 'type',
                'permission' => 'calendar:config',
                'team' => true,
            ],
            self::ASSIGNMENT_TYPE => [
                'type' => 'assignment_type',
                'module' => 'resource.assignment',
                'sub_module' => 'type',
                'permission' => 'resource:config',
                'team' => true,
            ],
            self::ANNOUNCEMENT_TYPE => [
                'type' => 'announcement_type',
                'module' => 'communication.announcement',
                'sub_module' => 'type',
                'permission' => 'communication:config',
                'team' => true,
            ],
            self::FEE_CONCESSION_TYPE => [
                'type' => 'fee_concession_type',
                'module' => 'finance',
                'sub_module' => 'fee_concession_type',
                'permission' => 'finance:config',
                'team' => true,
            ],
            self::TRIP_TYPE => [
                'type' => 'trip_type',
                'module' => 'activity',
                'sub_module' => 'trip_type',
                'permission' => 'activity:config',
                'team' => true,
            ],
            self::INCIDENT_CATEGORY => [
                'type' => 'incident_category',
                'module' => 'discipline',
                'sub_module' => 'incident_category',
                'permission' => 'discipline:config',
                'team' => true,
            ],
            self::BLOG_CATEGORY => [
                'type' => 'blog_category',
                'module' => 'blog',
                'sub_module' => 'category',
                'permission' => 'blog:config',
                'team' => true,
            ],
            self::TRANSACTION_CATEGORY => [
                'type' => 'transaction_category',
                'module' => 'finance.transaction',
                'sub_module' => 'category',
                'permission' => 'finance:config',
                'team' => true,
            ],
            self::APPROVAL_REQUEST_PRIORITY => [
                'type' => 'approval_request_priority',
                'module' => 'approval',
                'sub_module' => 'request_priority',
                'permission' => 'approval:config',
                'team' => true,
            ],
            self::APPROVAL_REQUEST_GROUP => [
                'type' => 'approval_request_group',
                'module' => 'approval',
                'sub_module' => 'request_group',
                'permission' => 'approval:config',
                'team' => true,
            ],
            self::APPROVAL_REQUEST_NATURE => [
                'type' => 'approval_request_nature',
                'module' => 'approval',
                'sub_module' => 'request_nature',
                'permission' => 'approval:config',
                'team' => true,
            ],
            self::UNIT => [
                'type' => 'unit',
                'module' => 'inventory',
                'sub_module' => 'unit',
                'permission' => 'inventory:config',
            ],
            default => []
        };
    }

    public static function getOptions(): array
    {
        $options = [];

        foreach (self::cases() as $option) {
            $detail = $option->detail();

            $module = Arr::get($detail, 'module');
            $subModule = Arr::get($detail, 'sub_module');

            $options[] = ['label' => trans($module.'.'.$subModule.'.'.$subModule), 'value' => $option->value];
        }

        return $options;
    }
}
