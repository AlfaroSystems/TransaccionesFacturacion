@props([
    'head' => null,   // slot con las <th>; o pasar el slot nombrado :head
])
<section class="overflow-x-auto -mx-1 px-1">
    <table class="ui-table">
        @isset($head)
            <thead><tr>{{ $head }}</tr></thead>
        @endisset
        <tbody>
            {{ $slot }}
        </tbody>
    </table>
</section>
