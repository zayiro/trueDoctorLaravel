<x-mail::message>
# Lote de liquidación listo para revisar

El lote **{{ $number }}** quedó en borrador con **{{ $lines }}** liquidación(es) de **{{ $beneficiaries }}** beneficiario(s), por un total de **{{ $total }}**.

Revísalo, excluye lo que no deba pagarse esta semana y apruébalo para registrar las transferencias.

<x-mail::button :url="$url">
Revisar el lote
</x-mail::button>

{{ config('app.name') }}
</x-mail::message>
