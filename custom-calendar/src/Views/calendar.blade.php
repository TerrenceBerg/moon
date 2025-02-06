<div class="container">
    <h2>Custom 13-Month Calendar for {{ $year }}</h2>
    <a href="?year={{ $year - 1 }}">⬅️ Previous Year</a> |
    <a href="?year={{ $year + 1 }}">Next Year ➡️</a>

    <table border="1" width="100%">
        @foreach ($calendar as $month => $days)
            <tr>
                <th colspan="28">Month {{ $month }}</th>
            </tr>
            <tr>
                @foreach ($days as $day => $date)
                    <td style="{{ $date['is_today'] ? 'background: yellow;' : '' }}">
                        <b>Day {{ $day }}</b><br>
                        📅 {{ $date['gregorian'] }}
                    </td>
                @endforeach
            </tr>
        @endforeach
    </table>
</div>
