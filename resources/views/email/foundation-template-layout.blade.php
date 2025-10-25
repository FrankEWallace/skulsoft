<!-- Emails use the XHTML Strict doctype -->
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "https://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="https://www.w3.org/1999/xhtml">
<head>
  <!-- The character set should be utf-8 -->
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <meta name="viewport" content="width=device-width"/>
  <!-- Link to the email's CSS, which will be inlined into the email -->
  <link rel="stylesheet" href="{{url('/mail/css/foundation-emails.css')}}">
  <title>{{config('config.general.app_name', config('app.name', 'ScriptMint'))}}</title>
  <style>
    .header {
      background: #340E57;
    }

    .header .container {
      background: #340E57;
    }

    .wrapper.secondary {
      background: #f3f3f3;
    }

    .header .columns {
      padding-bottom: 0;
    }

    .header p {
      color: #fff;
      margin-bottom: 0;
    }

    .header .wrapper-inner {
      padding: 20px;
      /*controls the height of the header*/
    }

    table.button.primary table td {
    background: #340E57;
    border: 0px solid #340E57; }

    table.button.primary table a {
    border: 0 solid #340E57; }

    table.button.primary:hover table td {
    background: #2a0b46; }

    table.button.primary:hover table a {
    border: 0 solid #2a0b46; }

    .footer-icon {
        width: 20px !important;
        display: inline-block;
    }
  </style>
</head>

<body>
  <!-- Wrapper for the body of the email -->
    <table class="body" data-made-with-foundation="">
        <tr>
          <td class="float-center" align="center" valign="top">
            <center data-parsed="">
              <!-- move the above styles into your custom stylesheet -->
              <table align="center" class="wrapper header float-center">
                <tr>
                  <td class="wrapper-inner">
                    <table align="center" class="container">
                      <tbody>
                        <tr>
                          <td>
                            <table class="row collapse">
                              <tbody>
                                <tr>
                                  <th class="small-6 large-6 columns first" valign="middle">
                                    <table>
                                      <tr>
                                        <th> <img style="max-height: 60px;" src="{{config('config.assets.icon')}}"> </th>
                                      </tr>
                                    </table>
                                  </th>
                                  <th class="small-6 large-6 columns last" valign="middle">
                                    <table>
                                      <tr>
                                        <th>
                                          <p class="text-right">{{config('config.general.app_name')}}</p>
                                        </th>
                                      </tr>
                                    </table>
                                  </th>
                                </tr>
                              </tbody>
                            </table>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </td>
                </tr>
              </table>
              <table align="center" class="container float-center">
                <tbody>
                  <tr>
                    <td>
                      <table class="spacer">
                        <tbody>
                          <tr>
                            <td height="16px" style="font-size:16px;line-height:16px;">&#xA0;</td>
                          </tr>
                        </tbody>
                      </table>
                      <table class="row">
                        <tbody>
                          <tr>
                            <th class="small-12 large-12 columns first last">
                              <table>
                                <tr>
                                  <th>
                                    @yield('content')
                                  </th>
                                  <th class="expander"></th>
                                </tr>
                              </table>
                            </th>
                          </tr>
                        </tbody>
                      </table>
                      <table class="wrapper secondary" align="center">
                        <tr>
                          <td class="wrapper-inner">
                            <table class="spacer">
                              <tbody>
                                <tr>
                                  <td height="16px" style="font-size:16px;line-height:16px;">&#xA0;</td>
                                </tr>
                              </tbody>
                            </table>
                            <table class="row">
                              <tbody>
                                <tr>
                                  <th class="small-12 large-6 columns first">
                                    <table>
                                      <tr>
                                        <th>
                                          <table class="menu">
                                            <tr>
                                              <td>
                                                <table align="center">
                                                  <tr>
                                                    @if(config('config.social_network.facebook'))
                                                        <th class="menu-item text-center">
                                                            <a href="{{config('config.social_network.facebook')}}">
                                                                <img src="{{url('/images/brand/facebook.png')}}" class="footer-icon" />
                                                            </a>
                                                        </th>
                                                    @endif
                                                    @if(config('config.social_network.twitter'))
                                                        <th class="menu-item text-center">
                                                            <a href="{{config('config.social_network.twitter')}}">
                                                                <img src="{{url('/images/brand/twitter.png')}}" class="footer-icon" />
                                                            </a>
                                                        </th>
                                                    @endif
                                                    @if(config('config.social_network.google'))
                                                        <th class="menu-item text-center">
                                                            <a href="{{config('config.social_network.google')}}">
                                                                <img src="{{url('/images/brand/google.png')}}" class="footer-icon" />
                                                            </a>
                                                        </th>
                                                    @endif
                                                    @if(config('config.social_network.linkedin'))
                                                        <th class="menu-item text-center">
                                                            <a href="{{config('config.social_network.linkedin')}}">
                                                                <img src="{{url('/images/brand/linkedin.png')}}" class="footer-icon" />
                                                            </a>
                                                        </th>
                                                    @endif
                                                    @if(config('config.social_network.youtube'))
                                                        <th class="menu-item text-center">
                                                            <a href="{{config('config.social_network.youtube')}}">
                                                                <img src="{{url('/images/brand/youtube.png')}}" class="footer-icon" />
                                                            </a>
                                                        </th>
                                                    @endif
                                                    @if(config('config.social_network.github'))
                                                        <th class="menu-item text-center">
                                                            <a href="{{config('config.social_network.github')}}">
                                                                <img src="{{url('/images/brand/github.png')}}" class="footer-icon" />
                                                            </a>
                                                        </th>
                                                    @endif
                                                  </tr>
                                                </table>
                                              </td>
                                            </tr>
                                          </table>
                                        </th>
                                      </tr>
                                    </table>
                                  </th>
                                </tr>
                              </tbody>
                            </table>
                            <table class="row">
                              <tbody>
                                <tr>
                                  <th class="small-12 columns center">
                                    <p class="text-center">Phone: {{config('config.general.app_phone')}} | Email: <a style="color: inherit;" href="mailto:{{config('config.general.app_email')}}">{{config('config.general.app_email')}}</a></p>

                                    @if(config('config.general.app_website'))
                                        <p class="text-center">Website: <a style="color: inherit;" href="{{config('config.general.app_website')}}">{{config('config.general.app_website')}}</a></p>
                                    @endif
                                  </th>
                                </tr>
                              </tbody>
                            </table>
                          </td>
                        </tr>
                      </table>
                    </td>
                  </tr>
                </tbody>
              </table>
            </center>
          </td>
        </tr>
      </table>
</body>
</html>
