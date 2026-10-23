<!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <meta name="supported-color-schemes" content="light dark">
    <title>{$email.titolo|escape:'html':'UTF-8'}</title>
    {literal}<style>
@media only screen and (max-width:620px){.email-shell{width:100%!important}.email-body{padding:28px 22px!important}.email-title{font-size:29px!important}.email-outer{padding:12px 8px!important}.email-value{font-size:17px!important}}
@media(prefers-color-scheme:dark){.email-background{background-color:#102b27!important}.email-card{background-color:#1d302b!important}.email-text{color:#fbf9f5!important}.email-muted{color:#c8d5cc!important}.email-panel{background-color:#265d4b!important}.email-rule{border-color:#667670!important}.email-eyebrow{color:#d9be84!important}}
</style>{/literal}
</head>
<body class="email-background" bgcolor="#f6f2eb"
    style="margin:0;padding:0;background-color:#f6f2eb;color:#1d2d29;font-family:Arial,Helvetica,sans-serif;-webkit-text-size-adjust:100%;">
    <div
        style="display:none;font-size:1px;line-height:1px;color:#f6f2eb;max-height:0;max-width:0;opacity:0;overflow:hidden;mso-hide:all;">
        {$email.titolo|escape:'html':'UTF-8'} · Lumèa</div>
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
        <tr>
            <td align="center" class="email-outer" style="padding:32px 16px;">
                <!--[if mso]><table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0"><tr><td><![endif]-->
                <table role="presentation" class="email-shell email-card" width="100%" cellspacing="0" cellpadding="0"
                    border="0" bgcolor="#fffdf9"
                    style="max-width:600px;background-color:#fffdf9;border:1px solid #dfe5e1;border-radius:12px;overflow:hidden;">
                    <tr>
                        <td align="center" bgcolor="#173f37"
                            style="padding:28px 24px 25px;background-color:#173f37;border-bottom:3px solid #d9be84;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center">
                                <tr>
                                    <td align="center" width="46" height="46"
                                        style="width:46px;height:46px;border:1px solid #d9be84;border-radius:12px;color:#f1d79e;font-family:Georgia,'Times New Roman',serif;font-size:30px;line-height:46px;">
                                        L</td>
                                    <td
                                        style="padding-left:14px;font-family:Georgia,'Times New Roman',serif;font-size:32px;line-height:38px;color:#fffdf9;">
                                        Lumèa</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td class="email-body" style="padding:34px 36px 30px;">
                            <p class="email-eyebrow"
                                style="margin:0 0 12px;color:#916b35;font-size:11px;font-weight:bold;line-height:17px;letter-spacing:1.6px;">
                                {$email.etichetta|escape:'html':'UTF-8'}</p>
                            <h1 class="email-title email-text"
                                style="margin:0 0 22px;font-family:Georgia,'Times New Roman',serif;font-size:34px;line-height:1.18;font-weight:normal;color:#173f37;">
                                {$email.titolo|escape:'html':'UTF-8'}</h1>
                            <p class="email-text"
                                style="margin:0 0 10px;color:#1d2d29;font-size:16px;line-height:25px;">Ciao
                                {$email.nome|escape:'html':'UTF-8'},</p>
                            <p class="email-muted"
                                style="margin:0 0 24px;color:#65716d;font-size:15px;line-height:25px;">
                                {$email.introduzione|escape:'html':'UTF-8'}</p>
                            <table role="presentation" class="email-panel" width="100%" cellspacing="0" cellpadding="0"
                                border="0"
                                bgcolor="{if $email.tipo eq 'assegnazione'}#eaf1ee{else}#f6f2eb{/if}"
                                style="background-color:{if $email.tipo eq 'assegnazione'}#eaf1ee{else}#f6f2eb{/if};border:1px solid {if $email.tipo eq 'coupon'}#d9be84{else}#dfe5e1{/if};border-radius:8px;">
                                {if $email.tipo eq 'coupon'}
                                <tr>
                                    <td style="padding:22px 20px 10px;">
                                        <p class="email-eyebrow"
                                            style="margin:0 0 8px;font-size:11px;font-weight:bold;letter-spacing:1.2px;color:#916b35;">
                                            IL TUO CODICE COUPON</p>
                                        <p class="email-text"
                                            style="margin:0;font-family:Georgia,'Times New Roman',serif;font-size:27px;line-height:34px;color:#173f37;overflow-wrap:anywhere;word-break:break-word;">
                                            {$email.codice|escape:'html':'UTF-8'}</p>
                                    </td>
                                </tr>
                                {/if}
                                {foreach $email.righe as $riga}
                                <tr>
                                    <td class="email-rule"
                                        style="padding:14px 20px;{if not $riga@last}border-bottom:1px solid #dfe5e1;{/if}">
                                        <p class="email-muted"
                                            style="margin:0 0 4px;font-size:12px;line-height:18px;color:#65716d;">
                                            {$riga.etichetta|escape:'html':'UTF-8'}</p>
                                        <p class="email-value email-text"
                                            style="margin:0;font-size:18px;font-weight:bold;line-height:25px;color:#173f37;overflow-wrap:break-word;">
                                            {$riga.valore|escape:'html':'UTF-8'}</p>
                                    </td>
                                </tr>
                                {/foreach}
                            </table>
                            {if $email.precedente ne ''}
                            <div class="email-rule"
                                style="margin-top:18px;padding:14px 16px;border-left:3px solid #b08a52;">
                                <p class="email-muted"
                                    style="margin:0 0 4px;font-size:12px;line-height:18px;color:#65716d;">Appuntamento
                                    precedente sostituito</p>
                                <p class="email-text" style="margin:0;font-size:14px;line-height:22px;color:#1d2d29;">
                                    {$email.precedente|escape:'html':'UTF-8'}</p>
                            </div>
                            {/if}
                            <p class="email-muted"
                                style="margin:23px 0 0;color:#65716d;font-size:14px;line-height:23px;">
                                {$email.nota|escape:'html':'UTF-8'}</p>
                        </td>
                    </tr>
                    <tr>
                        <td class="email-rule" align="center" style="padding:22px 24px;border-top:1px solid #dfe5e1;">
                            <p class="email-text"
                                style="margin:0 0 7px;font-family:Georgia,'Times New Roman',serif;font-size:20px;line-height:26px;color:#173f37;">
                                Lumèa</p>
                            <p class="email-muted"
                                style="margin:0 0 10px;font-size:13px;line-height:20px;color:#65716d;">Grazie per averci
                                scelto.</p>
                            <p class="email-muted" style="margin:0;font-size:12px;line-height:19px;color:#65716d;">
                                Questa è una comunicazione automatica<br>relativa al tuo account o appuntamento.</p>
                        </td>
                    </tr>
                </table>
                <!--[if mso]></td></tr></table><![endif]-->
            </td>
        </tr>
    </table>
</body>
</html>
