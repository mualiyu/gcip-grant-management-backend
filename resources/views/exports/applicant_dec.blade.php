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
        @if (count($applicants)>0)
        @foreach($applicants as $a)
        <tr>
            <td>{{ $a->name }}</td>
            <td>{{ $a->email }}</td>
        </tr>
        @endforeach
        @endif
    </tbody>
</table>
