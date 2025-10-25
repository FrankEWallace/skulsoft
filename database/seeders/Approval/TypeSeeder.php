<?php

namespace Database\Seeders\Approval;

use App\Enums\Approval\Category;
use App\Models\Approval\Level;
use App\Models\Approval\Type;
use App\Models\Employee\Department;
use App\Models\Employee\Employee;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

class TypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $departments = Department::query()
            ->whereNull('team_id')
            ->get()
            ->pluck('id')
            ->toArray();

        $employees = Employee::query()
            ->select('employees.id', 'users.username')
            ->join('contacts', 'employees.contact_id', '=', 'contacts.id')
            ->join('users', 'contacts.user_id', '=', 'users.id')
            ->byTeam(1)
            ->whereIn('users.username', ['school1staff1', 'school1principal', 'school1manager', 'school1director'])
            ->get();

        $types = [
            [
                'category' => Category::ITEM_BASED->value,
                'name' => 'Purchase Approval',
                'department_id' => Arr::random($departments),
                'levels' => $employees->pluck('id')->toArray(),
                'config' => [
                    'item_based_type' => 'item_with_quantity',
                ],
            ],
            [
                'category' => Category::CONTACT_BASED->value,
                'name' => 'Vendor Approval',
                'department_id' => Arr::random($departments),
                'config' => [
                    'enable_contact_number' => true,
                    'is_contact_number_required' => true,
                    'enable_email' => true,
                    'is_email_required' => true,
                    'enable_website' => true,
                    'is_website_required' => true,
                    'enable_tax_number' => true,
                    'is_tax_number_required' => true,
                    'enable_address' => false,
                    'is_address_required' => false,
                ],
                'levels' => $employees->whereIn('username', ['school1staff1', 'school1principal', 'school1manager'])->pluck('id')->toArray(),
            ],
            [
                'category' => Category::PAYMENT_BASED->value,
                'name' => 'Payment Approval',
                'department_id' => Arr::random($departments),
                'config' => [
                    'enable_invoice_number' => true,
                    'is_invoice_number_required' => true,
                    'enable_invoice_date' => true,
                    'is_invoice_date_required' => true,
                    'enable_payment_mode' => true,
                    'is_payment_mode_required' => true,
                    'enable_payment_details' => true,
                    'is_payment_details_required' => false,
                ],
                'levels' => $employees->pluck('id')->toArray(),
            ],
            [
                'category' => Category::ITEM_BASED->value,
                'name' => 'Budget Approval',
                'department_id' => Arr::random($departments),
                'levels' => $employees->pluck('id')->toArray(),
                'config' => [
                    'item_based_type' => 'item_without_quantity',
                ],
            ],
            [
                'category' => Category::OTHER->value,
                'name' => 'Other Approval',
                'department_id' => Arr::random($departments),
                'levels' => $employees->whereIn('username', ['school1staff1', 'school1principal', 'school1manager'])->pluck('id')->toArray(),
            ],
        ];

        foreach ($types as $type) {
            $config = Arr::get($type, 'config', []);

            $approvalType = Type::forceCreate([
                'team_id' => 1,
                'category' => Arr::get($type, 'category'),
                'name' => Arr::get($type, 'name'),
                'department_id' => Arr::get($type, 'department_id'),
                'description' => fake()->sentence(),
                'config' => [
                    'is_active' => true,
                    'enable_file_upload' => true,
                    'is_file_upload_required' => true,
                    ...$config,
                ],
            ]);

            foreach (Arr::get($type, 'levels') as $index => $level) {
                match ($index) {
                    0 => $actions = [
                        'reject',
                    ],
                    1 => $actions = [
                        'hold',
                        'reject',
                    ],
                    2 => $actions = [
                        'edit',
                        'hold',
                        'cancel',
                        'reject',
                    ],
                    3 => $actions = [
                        'edit',
                        'hold',
                        'cancel',
                        'reject',
                    ],
                };

                Level::forceCreate([
                    'type_id' => $approvalType->id,
                    'employee_id' => $level,
                    'config' => [
                        'actions' => $actions,
                    ],
                ]);
            }
        }
    }
}
