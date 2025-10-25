<?php

namespace Mint\Service\Http\Requests;

use App\Helpers\SysHelper;
use App\Rules\StrongPassword;
use App\Rules\Username;
use App\Support\ServerPreRequisite;
use App\Support\TestSymlink;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class InstallRequest extends FormRequest
{
    use ServerPreRequisite, TestSymlink;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $option = request()->query('option');

        $rules = [
            'db_port' => 'required|integer',
            'db_host' => 'required|max:100',
            'db_name' => 'required|max:30',
            'db_username' => 'required|max:30',
        ];

        if (!isset($option) || $option === 'user') {
            $rules['name'] = 'required|max:100';
            $rules['email'] = 'required|email|max:50';
            $rules['username'] = ['required', new Username];
            // $rules['password'] = ['required', 'same:password_confirmation', new StrongPassword];
            $rules['password'] = ['required', 'min:6', 'same:password_confirmation'];
        }

        if (!isset($option) || $option === 'license') {
            $rules['access_code'] = 'required';
            $rules['registered_email'] = 'required|email';
        }

        return $rules;
    }

    /**
     * Translate fields with user friendly name.
     *
     * @return array
     */
    public function attributes()
    {
        if (SysHelper::isInstalled()) {
            throw ValidationException::withMessages(['message' => trans('general.errors.invalid_action')]);
        }

        return [
            'db_port' => trans('setup.install.props.db_port'),
            'db_host' => trans('setup.install.props.db_host'),
            'db_name' => trans('setup.install.props.db_name'),
            'db_username' => trans('setup.install.props.db_username'),
            'name' => trans('setup.install.props.name'),
            'email' => trans('setup.install.props.email'),
            'username' => trans('setup.install.props.username'),
            'password' => trans('setup.install.props.password'),
            'password_confirmation' => trans('setup.install.props.password_confirmation'),
        ];
    }

    /**
     * Validate database
     */
    public function validateDatabase(): void
    {
        try {
            $link = @mysqli_connect(
                request('db_host'),
                request('db_username'),
                request('db_password'),
                request('db_name'),
                request('db_port')
            );
        } catch (\Exception $e) {
            throw ValidationException::withMessages(['message' => trans('setup.errors.db_connection_fail')]);
        }

        if (request('db_imported')) {
            $migrations = [];

            foreach (\File::allFiles(base_path('/database/migrations')) as $file) {
                $migrations[] = basename($file, '.php');
            }

            $dbMigrations = \DB::table('migrations')->get()->pluck('migration')->all();

            if (array_diff($migrations, $dbMigrations)) {
                throw ValidationException::withMessages(['message' => trans('setup.errors.db_import_mismatch')]);
            }
        } else {
            $showTableQuery = mysqli_query($link, 'show tables');
            $countTable = mysqli_num_rows($showTableQuery);

            if ($countTable) {
                try {
                    mysqli_query($link, 'SET FOREIGN_KEY_CHECKS = 0');

                    while ($table = mysqli_fetch_array($showTableQuery)) {
                        mysqli_query($link, 'drop table ' . $table[0]);
                    }

                    mysqli_query($link, 'SET FOREIGN_KEY_CHECKS = 1');
                } catch (\Exception $e) {
                    throw ValidationException::withMessages(['message' => trans('setup.errors.could_not_delete_table')]);
                }
            }
        }

        $version_query = mysqli_query($link, 'SHOW VARIABLES where Variable_name = "version"');
        $version = $version_query->fetch_assoc();
        $this->checkDbVersion(Arr::get($version, 'Value', '1.0.0'));
    }

    public function testSymlink(): void
    {
        // if ($this->input('check_symlink') === 'no') {
        //     return;
        // }

        // if (!$this->createSymlink()) {
        //     throw ValidationException::withMessages(['message' => trans('setup.errors.symlink_failed')]);
        // }
    }
}
