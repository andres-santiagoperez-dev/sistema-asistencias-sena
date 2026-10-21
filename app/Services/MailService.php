<?php
namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Servicio de Envío de Correos Electrónicos
 * Sistema de Control de Asistencia SENA
 */
class MailService
{
    private array $config;

    public function __construct()
    {
        $configFile = dirname(__DIR__) . '/config/mail.php';
        if (file_exists($configFile)) {
            $this->config = require $configFile;
        } else {
            $this->config = [
                'host'        => 'smtp.hostinger.com',
                'port'        => 465,
                'smtp_secure' => 'ssl',
                'smtp_auth'   => true,
                'username'    => 'ingresoscta@adsocta.com',
                'password'    => '',
                'from_email'  => 'ingresoscta@adsocta.com',
                'from_name'   => 'SENA - Control de Asistencia',
                'code_expiry_minutes' => 15,
            ];
        }
    }

    /**
     * Enviar código de verificación de 6 dígitos para recuperación de contraseña
     *
     * @param string $destinatarioEmail Correo del usuario registrado
     * @param string $nombreUsuario Nombre o identificación del usuario
     * @param string $codigo Código de 6 dígitos
     * @param int $minutosExpiracion Tiempo de validez en minutos
     * @return array ['success' => bool, 'message' => string]
     */
    public function enviarCodigoRecuperacion(string $destinatarioEmail, string $nombreUsuario, string $codigo, int $minutosExpiracion = 15): array
    {
        // Verificar si la contraseña SMTP está configurada
        if (empty(trim($this->config['password'] ?? ''))) {
            return [
                'success' => false,
                'message' => 'El servidor de correo no tiene configurada la contraseña del correo institucional de Hostinger. Por favor configúrela en app/config/mail.php.'
            ];
        }

        $mail = new PHPMailer(true);

        try {
            // Configuración del Servidor SMTP
            $mail->isSMTP();
            $mail->Host       = $this->config['host'];
            $mail->SMTPAuth   = $this->config['smtp_auth'];
            $mail->Username   = $this->config['username'];
            $mail->Password   = $this->config['password'];
            $mail->SMTPSecure = $this->config['smtp_secure'] === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = (int)$this->config['port'];
            $mail->CharSet    = 'UTF-8';
            $mail->Encoding   = 'base64';

            // Medidas clave Anti-Spam
            $domain = substr(strrchr($this->config['from_email'] ?? 'adsocta.com', "@"), 1) ?: 'adsocta.com';
            $mail->Hostname   = $domain; // Coincide con el dominio corporativo del remitente (adsocta.com)
            $mail->XMailer    = ' ';     // Elimina la cabecera X-Mailer para no ser clasificado como script automatizado
            $mail->addCustomHeader('Auto-Submitted', 'auto-generated');
            $mail->addCustomHeader('X-Auto-Response-Suppress', 'OOF, AutoReply');

            // Remitente y Destinatario
            $mail->setFrom($this->config['from_email'], $this->config['from_name']);
            $mail->addAddress($destinatarioEmail, $nombreUsuario);
            $mail->addReplyTo($this->config['from_email'], $this->config['from_name']);

            // Contenido del Correo
            $mail->isHTML(true);
            $mail->Subject = "{$codigo} es tu código de verificación SENA";
            $mail->Body    = $this->construirPlantillaHTML($nombreUsuario, $codigo, $minutosExpiracion);
            $mail->AltBody = "Hola {$nombreUsuario},\n\nTu código de verificación para restablecer tu contraseña en el Sistema de Control de Asistencia SENA es: {$codigo}\n\nEste código es válido por {$minutosExpiracion} minutos. Si no solicitaste este cambio, puedes ignorar este mensaje.\n\nSENA - Servicio Nacional de Aprendizaje";

            $mail->send();

            return [
                'success' => true,
                'message' => 'Código de verificación enviado exitosamente al correo.'
            ];
        } catch (\Throwable $e) {
            $detalleError = !empty($mail->ErrorInfo) ? $mail->ErrorInfo : $e->getMessage();
            error_log('Error en MailService: ' . $detalleError);
            return [
                'success' => false,
                'message' => 'No fue posible enviar el correo de verificación. Detalle: ' . $detalleError
            ];
        }
    }

    /**
     * Enviar llamado de atención formal por retardo o inasistencia al aprendiz
     *
     * @param array $datos [
     *     'tipo'              => 'retardo' | 'inasistencia',
     *     'destinatarioEmail' => string,
     *     'nombreAprendiz'    => string,
     *     'documento'         => string,
     *     'programa'          => string,
     *     'numeroFicha'       => string,
     *     'fecha'             => string (Y-m-d),
     *     'hora'              => string (h:i A),
     *     'minutosRetardo'    => int,
     *     'nombreInstructor'  => string,
     *     'bloque'            => string
     * ]
     * @return array ['success' => bool, 'message' => string]
     */
    public function enviarLlamadoAtencion(array $datos): array
    {
        if (empty(trim($this->config['password'] ?? ''))) {
            return [
                'success' => false,
                'message' => 'Contraseña SMTP no configurada en app/config/mail.php'
            ];
        }

        $destinatario = trim($datos['destinatarioEmail'] ?? '');
        if (empty($destinatario) || !filter_var($destinatario, FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'message' => 'Correo del aprendiz no válido o no registrado.'
            ];
        }

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = $this->config['host'];
            $mail->SMTPAuth   = $this->config['smtp_auth'];
            $mail->Username   = $this->config['username'];
            $mail->Password   = $this->config['password'];
            $mail->SMTPSecure = $this->config['smtp_secure'] === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = (int)$this->config['port'];
            $mail->CharSet    = 'UTF-8';
            $mail->Encoding   = 'base64';

            $domain = substr(strrchr($this->config['from_email'] ?? 'adsocta.com', "@"), 1) ?: 'adsocta.com';
            $mail->Hostname   = $domain;
            $mail->XMailer    = ' ';
            $mail->addCustomHeader('Auto-Submitted', 'auto-generated');
            $mail->addCustomHeader('X-Auto-Response-Suppress', 'OOF, AutoReply');

            $mail->setFrom($this->config['from_email'], $this->config['from_name']);
            $mail->addAddress($destinatario, $datos['nombreAprendiz'] ?? 'Aprendiz SENA');
            $mail->addReplyTo($this->config['from_email'], $this->config['from_name']);

            $tipo = $datos['tipo'] ?? 'retardo';
            $numeroFicha = $datos['numeroFicha'] ?? '';

            $meses = [
                1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
                5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
                9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre'
            ];
            $tsFecha = strtotime($datos['fecha'] ?? date('Y-m-d'));
            $dia = date('d', $tsFecha);
            $mes = $meses[(int)date('m', $tsFecha)] ?? 'enero';
            $anio = date('Y', $tsFecha);
            $fechaLegible = "{$dia} de {$mes} de {$anio}";

            if ($tipo === 'retardo') {
                $mail->Subject = "Llamado de atención escrito por retardo - Ficha {$numeroFicha} - SENA";
            } else {
                $mail->Subject = "Llamado de atención escrito por inasistencia - Ficha {$numeroFicha} - SENA";
            }

            $mail->isHTML(true);
            $mail->Body    = $this->construirPlantillaLlamadoHTML($datos, $fechaLegible);
            $mail->AltBody = $this->construirTextoLlamadoPlano($datos, $fechaLegible);

            $mail->send();

            return [
                'success' => true,
                'message' => 'Llamado de atención enviado exitosamente al correo del aprendiz.'
            ];
        } catch (\Throwable $e) {
            $detalleError = !empty($mail->ErrorInfo) ? $mail->ErrorInfo : $e->getMessage();
            error_log('Error en MailService::enviarLlamadoAtencion: ' . $detalleError);
            return [
                'success' => false,
                'message' => 'No fue posible enviar el llamado de atención: ' . $detalleError
            ];
        }
    }

    /**
     * Construye la plantilla HTML del llamado de atención formal con la normativa SENA
     */
    private function construirPlantillaLlamadoHTML(array $datos, string $fechaLegible): string
    {
        $tipo = $datos['tipo'] ?? 'retardo';
        $nombreAprendiz = htmlspecialchars($datos['nombreAprendiz'] ?? 'Aprendiz');
        $programa = htmlspecialchars($datos['programa'] ?? 'ADSO');
        $numeroFicha = htmlspecialchars($datos['numeroFicha'] ?? '');
        $documento = htmlspecialchars($datos['documento'] ?? 'N/A');
        $hora = htmlspecialchars($datos['hora'] ?? date('h:i A'));
        $minutos = (int)($datos['minutosRetardo'] ?? 0);
        $nombreInstructor = htmlspecialchars($datos['nombreInstructor'] ?? 'Instructor Titular');
        $bloque = htmlspecialchars($datos['bloque'] ?? 'Sesión de Formación');
        $fechaActual = date('d/m/Y H:i');

        if ($tipo === 'retardo') {
            $motivoTitulo = "Llamado de Atención Escrito por Retardo";
            $motivoTexto = "al registrar llegada tardía a formación el día <strong>{$fechaLegible}</strong> a las <strong>{$hora}</strong> ({$minutos} minutos de retardo) a formación se le hace un llamado escrito y se le recuerda las normas del reglamento para aprendices SENA:";
            $badgeNovedad = "<span style=\"background-color: #fef3c7; color: #92400e; padding: 4px 10px; border-radius: 4px; font-weight: bold; font-size: 12px; border: 1px solid #fde68a;\">Retardo ({$minutos} min)</span>";
        } else {
            $motivoTitulo = "Llamado de Atención Escrito por Inasistencia";
            $motivoTexto = "al no llegar a formación el día <strong>{$fechaLegible}</strong> a formación se le hace un llamado escrito y se le recuerda las normas del reglamento para aprendices SENA:";
            $badgeNovedad = "<span style=\"background-color: #fee2e2; color: #991b1b; padding: 4px 10px; border-radius: 4px; font-weight: bold; font-size: 12px; border: 1px solid #fecaca;\">Inasistencia</span>";
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$motivoTitulo}</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f6fb; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1e293b;">
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f4f6fb; padding: 30px 10px;">
        <tr>
            <td align="center">
                <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 16px rgba(0,0,0,0.06); border: 1px solid #e2e8f0;">
                    
                    <!-- Cabecera Institucional SENA -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #166534 0%, #2e7d32 100%); padding: 26px 30px; text-align: center;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 20px; font-weight: 700; letter-spacing: 0.5px;">
                                SERVICIO NACIONAL DE APRENDIZAJE - SENA
                            </h1>
                            <p style="color: #bbf7d0; margin: 6px 0 0 0; font-size: 13px; font-weight: 500;">
                                Centro de Comercio y Servicios · Control de Asistencias
                            </p>
                        </td>
                    </tr>

                    <!-- Contenido Principal -->
                    <tr>
                        <td style="padding: 32px 32px 25px 32px;">
                            <h2 style="color: #0f172a; margin: 0 0 16px 0; font-size: 18px; font-weight: 700;">
                                {$motivoTitulo}
                            </h2>

                            <p style="color: #334155; font-size: 15px; line-height: 1.6; margin: 0 0 16px 0;">
                                Estimado(a) aprendiz <strong>{$nombreAprendiz}</strong>,
                            </p>

                            <p style="color: #334155; font-size: 14.5px; line-height: 1.6; margin: 0 0 20px 0;">
                                Como integrante matriculado en el programa de formación <strong>{$programa}</strong> ficha <strong>{$numeroFicha}</strong> {$motivoTexto}
                            </p>

                            <!-- Cita Textual del Artículo 22 del Reglamento -->
                            <div style="background-color: #f8fafc; border-left: 4px solid #166534; padding: 16px 20px; border-radius: 6px; margin: 20px 0; border-top: 1px solid #e2e8f0; border-right: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0;">
                                <p style="margin: 0; font-size: 13.5px; line-height: 1.65; color: #1e293b;">
                                    <strong style="color: #166534;">ARTÍCULO 22. Participación y Cumplimiento.</strong> El Aprendiz SENA, como gestor principal de su proceso de formación debe participar de manera activa y oportuna en las diferentes actividades, presenciales y/o virtuales, que conforman la ruta de aprendizaje. Los procesos de formación en el SENA promueven la responsabilidad de cada Aprendiz en la gestión de su proceso de aprendizaje, facilitando su acceso a diversas fuentes de conocimiento.
                                </p>
                            </div>

                            <!-- Ficha Detallada de la Novedad -->
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; margin: 20px 0;">
                                <tr>
                                    <td style="padding: 12px 16px; border-bottom: 1px solid #dcfce7; font-size: 13px; color: #14532d; width: 50%;">
                                        <strong>Aprendiz:</strong> {$nombreAprendiz}
                                    </td>
                                    <td style="padding: 12px 16px; border-bottom: 1px solid #dcfce7; font-size: 13px; color: #14532d; width: 50%;">
                                        <strong>Documento:</strong> {$documento}
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 16px; border-bottom: 1px solid #dcfce7; font-size: 13px; color: #14532d;">
                                        <strong>Programa:</strong> {$programa}
                                    </td>
                                    <td style="padding: 12px 16px; border-bottom: 1px solid #dcfce7; font-size: 13px; color: #14532d;">
                                        <strong>Ficha:</strong> {$numeroFicha}
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 16px; font-size: 13px; color: #14532d;">
                                        <strong>Fecha:</strong> {$fechaLegible}
                                    </td>
                                    <td style="padding: 12px 16px; font-size: 13px; color: #14532d;">
                                        <strong>Hora / Novedad:</strong> {$hora} · {$badgeNovedad}
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="2" style="padding: 10px 16px; border-top: 1px solid #dcfce7; font-size: 12px; color: #15803d;">
                                        <strong>Instructor titular:</strong> {$nombreInstructor} · {$bloque}
                                    </td>
                                </tr>
                            </table>

                            <!-- Nota de Advertencia y Reglamento -->
                            <div style="background-color: #fffbeb; border-left: 4px solid #f59e0b; padding: 12px 16px; border-radius: 4px; margin: 20px 0 10px 0;">
                                <p style="color: #92400e; font-size: 12.5px; line-height: 1.5; margin: 0;">
                                    <strong>Recordatorio Institucional:</strong> La puntualidad y el cumplimiento continuo en el horario oficial de formación son requisitos indispensables para la permanencia en el programa. La reiteración de retardos sin justificación médica o causa de fuerza mayor comprobada dará lugar a las medidas formativas y sancionatorias contempladas en el Reglamento del Aprendiz SENA.
                                </p>
                            </div>

                            <p style="color: #94a3b8; font-size: 11.5px; margin: 15px 0 0 0;">
                                Registro automático del Sistema de Control de Asistencia emitido el {$fechaActual}.
                            </p>
                        </td>
                    </tr>

                    <!-- Pie de Página -->
                    <tr>
                        <td style="background-color: #f8fafc; border-top: 1px solid #e2e8f0; padding: 18px 30px; text-align: center;">
                            <p style="color: #94a3b8; font-size: 11.5px; margin: 0; line-height: 1.5;">
                                Servicio Nacional de Aprendizaje SENA · Regional Tolima · Centro de Comercio y Servicios<br>
                                Sistema de Control de Asistencias · <a href="https://adsocta.com" style="color: #2e7d32; text-decoration: none;">adsocta.com</a>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }

    /**
     * Construye el cuerpo del correo en texto plano
     */
    private function construirTextoLlamadoPlano(array $datos, string $fechaLegible): string
    {
        $tipo = $datos['tipo'] ?? 'retardo';
        $nombreAprendiz = $datos['nombreAprendiz'] ?? 'Aprendiz';
        $programa = $datos['programa'] ?? 'ADSO';
        $numeroFicha = $datos['numeroFicha'] ?? '';
        $documento = $datos['documento'] ?? 'N/A';
        $hora = $datos['hora'] ?? date('h:i A');
        $minutos = (int)($datos['minutosRetardo'] ?? 0);
        $nombreInstructor = $datos['nombreInstructor'] ?? 'Instructor Titular';

        if ($tipo === 'retardo') {
            $motivo = "al registrar llegada tardía a formación el día {$fechaLegible} a las {$hora} ({$minutos} minutos de retardo) a formación se le hace un llamado escrito y se le recuerda las normas del reglamento para aprendices SENA:";
        } else {
            $motivo = "al no llegar a formación el día {$fechaLegible} a formación se le hace un llamado escrito y se le recuerda las normas del reglamento para aprendices SENA:";
        }

        return <<<TEXT
SERVICIO NACIONAL DE APRENDIZAJE - SENA
Centro de Comercio y Servicios · Control de Asistencias

Estimado(a) aprendiz {$nombreAprendiz}

Como integrante matriculado en el programa de formación {$programa} ficha {$numeroFicha} {$motivo}

ARTÍCULO 22. Participación y Cumplimiento. El Aprendiz SENA, como gestor principal de su proceso de formación debe participar de manera activa y oportuna en las diferentes actividades, presenciales y/o virtuales, que conforman la ruta de aprendizaje. Los procesos de formación en el SENA promueven la responsabilidad de cada Aprendiz en la gestión de su proceso de aprendizaje, facilitando su acceso a diversas fuentes de conocimiento.

Detalles de la sesión:
- Aprendiz: {$nombreAprendiz}
- Documento: {$documento}
- Programa: {$programa}
- Ficha: {$numeroFicha}
- Fecha: {$fechaLegible}
- Hora de Entrada: {$hora}
- Instructor: {$nombreInstructor}

Le recordamos que la puntualidad es fundamental en su proceso formativo en el SENA.

Sistema de Control de Asistencia SENA
TEXT;
    }

    /**
     * Construye la plantilla HTML del correo de verificación institucional
     */
    private function construirPlantillaHTML(string $nombre, string $codigo, int $minutos): string
    {
        $fechaActual = date('d/m/Y H:i');

        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Código de Verificación SENA</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f6fb; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1e293b;">
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f4f6fb; padding: 30px 10px;">
        <tr>
            <td align="center">
                <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 540px; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.06); border: 1px solid #e2e8f0;">
                    <!-- Cabecera Institucional SENA -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #166534 0%, #2e7d32 100%); padding: 28px 30px; text-align: center;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 22px; font-weight: 700; letter-spacing: 0.5px;">
                                SERVICIO NACIONAL DE APRENDIZAJE - SENA
                            </h1>
                            <p style="color: #bbf7d0; margin: 6px 0 0 0; font-size: 13px; font-weight: 500;">
                                Centro de Comercio y Servicios · Control de Asistencias
                            </p>
                        </td>
                    </tr>

                    <!-- Contenido Principal -->
                    <tr>
                        <td style="padding: 35px 35px 25px 35px;">
                            <h2 style="color: #0f172a; margin: 0 0 12px 0; font-size: 18px; font-weight: 600;">
                                Recuperación de Contraseña
                            </h2>
                            <p style="color: #475569; font-size: 14px; line-height: 1.6; margin: 0 0 20px 0;">
                                Hola, <strong>{$nombre}</strong>. Hemos recibido una solicitud para restablecer la contraseña de acceso a tu cuenta en el Sistema de Control de Asistencia.
                            </p>
                            <p style="color: #475569; font-size: 14px; line-height: 1.6; margin: 0 0 25px 0;">
                                Utiliza el siguiente código de verificación de un solo uso para continuar con el proceso:
                            </p>

                            <!-- Caja del Código OTP -->
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom: 25px;">
                                <tr>
                                    <td align="center" style="background-color: #f0fdf4; border: 2px dashed #86efac; border-radius: 10px; padding: 20px;">
                                        <span style="font-family: 'Consolas', 'Courier New', monospace; font-size: 36px; font-weight: 800; letter-spacing: 10px; color: #166534; display: inline-block;">
                                            {$codigo}
                                        </span>
                                        <div style="color: #15803d; font-size: 12px; font-weight: 600; margin-top: 8px;">
                                            ⏱ Válido únicamente durante los próximos {$minutos} minutos
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            <!-- Nota de Seguridad -->
                            <div style="background-color: #fffbeb; border-left: 4px solid #f59e0b; padding: 12px 14px; border-radius: 4px; margin-bottom: 25px;">
                                <p style="color: #92400e; font-size: 12.5px; line-height: 1.5; margin: 0;">
                                    <strong>Importante por tu seguridad:</strong> Si tú no solicitaste este código, es posible que alguien haya ingresado tu correo por error. Puedes ignorar este mensaje con tranquilidad; tu contraseña no cambiará.
                                </p>
                            </div>

                            <p style="color: #64748b; font-size: 12px; line-height: 1.5; margin: 0;">
                                Solicitud generada el: <strong>{$fechaActual}</strong> (Hora de Colombia).
                            </p>
                        </td>
                    </tr>

                    <!-- Pie de Página Institucional -->
                    <tr>
                        <td style="background-color: #f8fafc; border-top: 1px solid #e2e8f0; padding: 18px 30px; text-align: center;">
                            <p style="color: #94a3b8; font-size: 11.5px; margin: 0; line-height: 1.4;">
                                Este es un correo automático generado por el Sistema de Asistencias SENA.<br>
                                Por favor no respondas a este mensaje.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }
}
