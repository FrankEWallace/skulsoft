<?php

namespace App\Support;

use Illuminate\Support\Str;

trait MailTemplateParser
{
    public function parse(string $html = ''): string
    {
        $html = Str::of($html)->replaceMatches('/\[.*?\)/', function ($match) {
            if (Str::contains($match[0], '](')) {
                $text = Str::of($match[0])->after('[')->before('](');
                $url = Str::of($match[0])->after('](')->before(')');

                if (filter_var($url, FILTER_VALIDATE_URL) === false) {
                    $url = '#';
                }

                // Foundation email template button
                // return '<table class="button rounded primary small-expanded">
                //     <tbody>
                //     <tr>
                //         <td>
                //         <table>
                //             <tbody>
                //             <tr>
                //                 <td><a href="'.$url.'">'.$text.'</a></td>
                //             </tr>
                //             </tbody>
                //         </table>
                //         </td>
                //     </tr>
                //     </tbody>
                // </table>';

                return '<table role="presentation" border="0" cellpadding="0" cellspacing="0" class="btn btn-primary">
                <tbody>
                  <tr>
                    <td align="left">
                      <table role="presentation" border="0" cellpadding="0" cellspacing="0">
                        <tbody>
                          <tr>
                            <td> <a href="'.$url.'" target="_blank">'.$text.'</a> </td>
                          </tr>
                        </tbody>
                      </table>
                    </td>
                  </tr>
                </tbody>
              </table>';
            }

            return $match[0];
        });

        return $html;
    }
}
