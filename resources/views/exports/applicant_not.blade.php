<table>
    <thead>
    <tr>
        <th colspan="4"> REA - Applicants that have not submited application</th>
    </tr>
    <tr>
        <th>Name</th>
        <th>Email</th>
    </tr>
    </thead>
    <tbody>
    @foreach($applications as $a)
        @if (count($a->applications)>0)
        @if ($a->applications[0]->status == null)
        <tr>
            <td>{{ $a->name }}</td>
            <td>{{ $a->email }}</td>
        </tr>
        @endif
        @endif
        @if (!count($a->applications)>0)
        <tr>
            <td>{{ $a->name }}</td>
            <td>{{ $a->email }}</td>
        </tr>
        @endif
    @endforeach
    </tbody>
</table>
