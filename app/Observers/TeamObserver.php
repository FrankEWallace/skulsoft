<?php

namespace App\Observers;

use App\Actions\CreateDefaultOption;
use App\Actions\Finance\CreateDefaultLedgerType;
use App\Helpers\SysHelper;
use App\Models\Team;
use App\Models\User;

class TeamObserver
{
    /**
     * Handle the Team "created" event.
     *
     * @return void
     */
    public function created(Team $team)
    {
        $user = User::where('meta->is_default', true)->first();

        if ($user) {
            SysHelper::setTeam($team->id);
            $user->assignRole('admin');
            SysHelper::setTeam(auth()->user()?->current_team_id);
        }

        $team->config = [
            'name' => $team->name,
            'title1' => 'School Motto',
            'title2' => 'School Accreditation Body',
            'title3' => 'School Affiliation Body',
            'address_line1' => 'Building Name',
            'address_line2' => 'Street Name',
            'city' => 'City Name',
            'state' => 'State Name',
            'zipcode' => 'Zipcode',
            'email' => 'school@example.com',
            'phone' => '1234567890',
            'website' => 'https://schoolwebsite.com',
            'incharge1' => [
                'name' => 'Principal Name',
                'title' => 'Principal',
                'contact_number' => '1234567890',
                'email' => 'principal@schoolwebsite.com',
            ],
            'incharge2' => [
                'name' => 'Vice Principal Name',
                'title' => 'Vice Principal',
                'contact_number' => '1234567890',
                'email' => 'viceprincipal@schoolwebsite.com',
            ],
        ];

        $team->save();

        (new CreateDefaultLedgerType)->execute($team->id);

        (new CreateDefaultOption)->execute($team->id);
    }

    /**
     * Handle the Team "updated" event.
     *
     * @return void
     */
    public function updated(Team $team)
    {
        //
    }

    /**
     * Handle the Team "deleted" event.
     *
     * @return void
     */
    public function deleted(Team $team)
    {
        \DB::table('roles')->whereTeamId($team->id)->delete();
        \DB::table('model_has_roles')->whereTeamId($team->id)->delete();
    }

    /**
     * Handle the Team "restored" event.
     *
     * @return void
     */
    public function restored(Team $team)
    {
        //
    }

    /**
     * Handle the Team "force deleted" event.
     *
     * @return void
     */
    public function forceDeleted(Team $team)
    {
        //
    }
}
