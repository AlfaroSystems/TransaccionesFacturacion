<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer Contraseña</title>
</head>
<body style="margin: 0; padding: 0; width: 100%; background-color: #f0f7f5; font-family: 'Figtree', 'Segoe UI', Helvetica, Arial, sans-serif; -webkit-text-size-adjust: none; -ms-text-size-adjust: none;">
    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f0f7f5; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table width="100%" max-width="570" cellpadding="0" cellspacing="0" border="0" style="max-width: 570px; background-color: #ffffff; border-radius: 24px; box-shadow: 0 10px 30px rgba(0, 94, 102, 0.05); border: 1px solid #e2f0ed; overflow: hidden;">
                    <!-- Header with Branding -->
                    <tr>
                        <td align="center" style="padding: 35px 40px 25px 40px; background-color: #ffffff; border-bottom: 1px solid #f0f6f5;">
                            <table cellpadding="0" cellspacing="0" border="0" align="center">
                                <tr>
                                    <!-- Logo Cross / Grid -->
                                    <td style="padding-right: 10px;">
                                        <table cellpadding="0" cellspacing="0" border="0" style="border: 2px solid #3cb0a4; border-radius: 8px; width: 32px; height: 32px; text-align: center;">
                                            <tr>
                                                <td align="center" style="font-size: 18px; font-weight: bold; color: #3cb0a4; font-family: Arial, sans-serif; line-height: 1;">+</td>
                                            </tr>
                                        </table>
                                    </td>
                                    <td>
                                        <span style="font-size: 16px; font-weight: 800; color: #005e66; letter-spacing: 0.5px;">TRANSACCIONES FACTURACIÓN</span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Body Content -->
                    <tr>
                        <td style="padding: 40px 40px 30px 40px;">
                            <h1 style="margin: 0 0 20px 0; font-size: 20px; font-weight: 800; color: #005e66;">¡Hola, {{ $name }}!</h1>
                            <p style="margin: 0 0 25px 0; font-size: 14px; line-height: 1.6; color: #526b68;">
                                Recibiste este correo electrónico porque se realizó una solicitud de restablecimiento de contraseña para tu cuenta en nuestro sistema.
                            </p>

                            <!-- Button Area -->
                            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin: 30px 0;">
                                <tr>
                                    <td align="center">
                                        <a href="{{ $url }}" target="_blank" style="background-color: #3cb0a4; color: #ffffff; padding: 12px 35px; border-radius: 50px; text-decoration: none; font-size: 13px; font-weight: bold; display: inline-block; box-shadow: 0 6px 15px rgba(60, 176, 164, 0.25); text-transform: uppercase; letter-spacing: 0.5px;">
                                            Restablecer contraseña
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            <p style="margin: 0 0 15px 0; font-size: 13px; line-height: 1.6; color: #6a827f; font-weight: 500;">
                                ⏰ Este enlace de recuperación expirará en <strong>60 minutos</strong>.
                            </p>
                            <p style="margin: 0 0 30px 0; font-size: 13px; line-height: 1.6; color: #879e9b;">
                                Si tú no solicitaste este cambio, puedes ignorar este mensaje sin problemas; tu contraseña actual seguirá funcionando de manera segura.
                            </p>
                            <p style="margin: 0; font-size: 14px; color: #526b68; line-height: 1.5;">
                                Saludos,<br>
                                <strong style="color: #005e66;">El equipo de Soporte</strong>
                            </p>
                        </td>
                    </tr>

                    <!-- Footer Help URL -->
                    <tr>
                        <td style="padding: 25px 40px; background-color: #fafdfc; border-top: 1px solid #f0f6f5;">
                            <p style="margin: 0; font-size: 11px; line-height: 1.5; color: #9bb0ad;">
                                Si tienes problemas para hacer clic en el botón "Restablecer contraseña", copia y pega la siguiente dirección URL en tu navegador web:
                            </p>
                            <p style="margin: 10px 0 0 0; font-size: 11px; word-break: break-all;">
                                <a href="{{ $url }}" style="color: #3cb0a4; text-decoration: none; font-weight: 500;">{{ $url }}</a>
                            </p>
                        </td>
                    </tr>
                </table>

                <!-- Bottom Copyright Info -->
                <table width="100%" max-width="570" cellpadding="0" cellspacing="0" border="0" style="max-width: 570px; margin-top: 20px;">
                    <tr>
                        <td align="center" style="font-size: 10px; color: #a4bab7; font-weight: 500;">
                            &copy; 2026 Transacciones Facturación. Todos los derechos reservados.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>