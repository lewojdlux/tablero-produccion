<x-mail::message>

## ORDEN DE TRABAJO FINALIZADA

Se ha registrado la finalización de una orden de trabajo en el sistema.

<br>

<table width="100%" cellpadding="8" cellspacing="0" style="border:1px solid #e5e5e5;border-collapse:collapse;font-size:14px;">
<tr style="background:#f8f8f8;">
<td width="40%"><strong>Número OT</strong></td>
<td>#{{ $orden->n_documento }}</td>
</tr>

<tr>
<td><strong>Cliente</strong></td>
<td>{{ $orden->tercero }}</td>
</tr>

<tr style="background:#f8f8f8;">
<td><strong>Fecha de finalización</strong></td>
<td>{{ now()->format('d/m/Y H:i') }}</td>
</tr>
</table>

<br>

<x-mail::button :url="url('/ordenes-trabajo/asignados')" color="gray">
Ver orden de trabajo
</x-mail::button>

<br>

Si requiere revisar más información, ingrese al sistema de gestión.

<br>

Atentamente,
**Sistema de gestión de órdenes de trabajo**

</x-mail::message>
