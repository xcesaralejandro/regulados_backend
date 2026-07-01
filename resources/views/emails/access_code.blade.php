<x-mail::message>
  # Hola {{ $user->name }} {{ $user->surname }},

  Has solicitado un código de acceso para ingresar a {{ config('app.name') }}. Utiliza el siguiente código de verificación de un solo uso para completar el proceso:

  <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" style="margin: 25px auto;">
    <tr>
      <td style="font-family: 'Courier New', Courier, monospace; font-size: 36px; font-weight: bold; color: #1e293b; letter-spacing: 6px; padding: 10px 20px; border-bottom: 4px solid #4f46e5; text-align: center; background-color: #f8fafc;">
        {{ $user->access_code }}
      </td>
    </tr>
  </table>

  <div style="text-align: center; margin-bottom: 25px;">
    <small style="color: #64748b;">Este código expirará en <strong>{{ $user::ACCESS_CODE_DURATION }} minutos</strong> por motivos de seguridad.</small>
  </div>

  Si tú no has solicitado este proceso, puedes ignorar este mensaje. Tu cuenta permanece protegida.

  Atentamente,<br>
  **El equipo de {{ config('app.name') }}**
</x-mail::message>