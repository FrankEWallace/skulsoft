<div style="padding: 10px 15px;">
    <table width="100%" border="0">
        <tr>
            <td width="33%" valign="top">
                <img src="{{ url(config('config.assets.logo')) }}" style="max-width: 150px;" />
            </td>
            <td valign="top" align="right">
                <div class="heading text-right">{{ config('config.team.config.name') }}</div>
                @if (config('config.team.config.title1'))
                    <div class="sub-heading mt-1 text-right">{{ config('config.team.config.title1') }}</div>
                @endif
                @if (config('config.team.config.title2'))
                    <div class="sub-heading mt-1 text-right">{{ config('config.team.config.title2') }}</div>
                @endif
                @if (config('config.team.config.title3'))
                    <div class="sub-heading mt-1 text-right">{{ config('config.team.config.title3') }}</div>
                @endif
                <div class="mt-1 text-right">
                    <span>{{ Arr::toAddress([
                        'address_line1' => config('config.team.config.address_line1'),
                        'address_line2' => config('config.team.config.address_line2'),
                        'city' => config('config.team.config.city'),
                        'state' => config('config.team.config.state'),
                        'zip_code' => config('config.team.config.zip_code'),
                        'country' => config('config.team.config.country'),
                    ]) }}</span>
                </div>
                @if (config('config.team.config.phone'))
                    <div class="mt-1 text-right">
                        <span>{{ config('config.team.config.phone') }}</span>
                    </div>
                @endif
                @if (config('config.team.config.email'))
                    <div class="mt-1 text-right">
                        <span>{{ config('config.team.config.email') }}</span>
                    </div>
                @endif
                @if (config('config.team.config.website'))
                    <div class="mt-1 text-right">{{ config('config.team.config.website') }}</div>
                @endif
            </td>
        </tr>
    </table>
</div>
