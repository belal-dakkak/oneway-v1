<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head lang="en" dir="ltr">
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
  <title>Verify Delete Your OneWay Account</title>
  <style type="text/css" rel="stylesheet" media="all">
    /* Base ------------------------------ */
    *:not(br):not(tr):not(html) {
      font-family: Arial, 'Helvetica Neue', Helvetica, sans-serif;
      -webkit-box-sizing: border-box;
      box-sizing: border-box;
    }
    body {
      width: 100% !important;
      height: 100%;
      margin: 0;
      line-height: 1.4;
      background-color: #F5F7F9;
      color: #839197;
      -webkit-text-size-adjust: none;
    }

    /* Layout ------------------------------ */
    .email-wrapper {
      width: 100%;
      margin: 0;
      padding: 0;
      background-color: #F5F7F9;
    }
    .email-content {
      width: 100%;
      margin: 0;
      padding: 0;
    }

    /* Masthead ----------------------- */
    .email-masthead {
      padding: 25px 0;
      text-align: center;
    }
    .email-masthead_logo {
      max-width: 400px;
      border: 0;
    }
    .email-masthead_name {
      font-size: 20px;
      font-weight: bold;
      color: white;
      text-decoration: none;
      text-shadow: 0 1px 0 white;
    }

    /* Body ------------------------------ */
    .email-body {
      width: 100%;
      margin: 0;
      padding: 0;
      border-top: 1px solid #E7EAEC;
      border-bottom: 1px solid #E7EAEC;
      background-color: #FFFFFF;
    }
    .email-body_inner {
      padding: 0;
    }
    .email-footer {
      width: 100%;
      margin: 0 auto;
      padding: 0;
      text-align: center;
    }
    .email-footer p {
      color: white;
    }
    .body-action {
      width: 100%;
      margin: 30px auto;
      padding: 0;
      text-align: center;
    }
    .body-sub {
      margin-top: 25px;
      padding-top: 25px;
      border-top: 1px solid #E7EAEC;
    }
    .content-cell {
      padding: 35px;
    }
    /* Type ------------------------------ */
    p.sub {
        color : black; font-size: 24px;
    }
    p.center {
      text-align: center;
    }


    /*Media Queries ------------------------------ */
    @media only screen and (max-width: 600px) {
      .email-body_inner,
      .email-footer {
        width: 100% !important;
      }
    }
  </style>
</head>
<body>
  <table class="email-wrapper" width="100%" cellpadding="0" cellspacing="0">
    <tr>
      <td align="center">
        <table class="email-content" width="50%" cellpadding="0" cellspacing="0" style="margin: auto;">
          <!-- Logo -->
            <tr style="background-color: #000000; border-radius: 45px;">
                <td class="email-masthead">
                    <div class="col-6">
                        <a class="content-cell">
                            <br>
                            <br>
                            <br>
                            <br>
                        </a>
                    </div>
                </td>
            </tr>
          <!-- Email Body -->
          <tr>
            <td class="email-body" width="100%">
              <table class="email-body_inner" align="center" width="100%" cellpadding="0" cellspacing="0">
                <!-- Body content -->
                <tr dir="ltr">
                  <td dir="ltr" class="content-cell">
                  <p class="sub" style="margin-bottom: 10px;">Dear {{ $data['name'] }}</p>
                  <p style="color : black" dir="ltr"></p>
                  {{-- <p class="sub" dir="ltr">Please use the following code to access your account.</p> --}}
                    <!-- Action -->
                    <table class="body-action" align="center" width="100%" cellpadding="0" cellspacing="0">
                      <tr>
                        <td align="center">
                          <div>
                              <a id="clickCopy" dir="ltr" style="color: #84e621; padding: 4px; font-size: 26px ">{{$data['message']}}</a>
                          </div>
                        </td>
                      </tr>
                    </table>
                    <!-- Sub copy -->
                      <table dir="ltr" class="body-sub">
                          <tr>
                              <td>
                                  <p class="sub">
                                      Thanks for using OneWay App.
                                  </p>
                                  <p dir="ltr">
                                      <a class="content-cell" href="https://oneway.fashion">
                                          <img src="https://oneway.fashion/custom/logo-icon.png" width="200" alt="">
                                      </a>
                                  </p>
                              </td>
                          </tr>
                      </table>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
          <tr>
              <td style="background-color: #000000">
                  <table class="email-footer" align="center" width="570" cellpadding="0" cellspacing="0">
                      <tr>
                          <td class="content-cell">
                              <p class="sub center">
                                  <br>
                                  <img src="https://oneway.fashion/custom/logo-icon.png" width="200" alt="">
                                  <br>
                              </p>
                          </td>
                      </tr>
                  </table>
              </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
<script type="text/javascript">
    function copyToCl(code){
        let copyText = $(this);

        /* Select the text field */
        copyText.select();

        /* Copy the text inside the text field */
        navigator.clipboard.writeText(code);
    }
    document.getElementById("clickCopy").onclick = function() {
        copyToClipboard(document.getElementById("goodContent"));
    }
    function copyToClipboard(e) {
        var tempItem = document.createElement('input');

        tempItem.setAttribute('type','text');
        tempItem.setAttribute('display','none');

        let content = e;
        if (e instanceof HTMLElement) {
            content = e.innerHTML;
        }

        tempItem.setAttribute('value',content);
        document.body.appendChild(tempItem);

        tempItem.select();
        document.execCommand('Copy');

        tempItem.parentElement.removeChild(tempItem);
    }
</script>
</body>
</html>
